<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `download_logs.user_id` semula `cascadeOnDelete`, sehingga menghapus satu
     * akun mahasiswa juga menghapus riwayat unduhannya — membuat statistik
     * dosen menyusut surut dan menghilangkan jejak penelusuran berkas.
     * Kaitannya diubah menjadi `nullOnDelete` supaya barisnya tetap ada
     * sebagai peristiwa, dengan pengunduh yang dikosongkan.
     *
     * `book_id` sengaja dibiarkan cascade: catatan unduhan buku yang sudah
     * dihapus permanen tidak lagi punya makna bagi laporan mana pun.
     */

    /**
     * PRAGMA foreign_keys pada SQLite tidak berpengaruh di dalam transaksi,
     * jadi migrasi ini harus berjalan di luar transaksi.
     */
    public $withinTransaction = false;

    /** Seluruh kolom yang dipindahkan saat tabel dibangun ulang. */
    private const KOLOM = ['id', 'book_id', 'user_id', 'prodi_id', 'mode', 'created_at', 'updated_at'];

    public function up(): void
    {
        $this->ubahKaitanPengunduh(nullable: true);
    }

    public function down(): void
    {
        // Kolomnya akan kembali NOT NULL, jadi baris tanpa pengunduh harus
        // dibuang lebih dulu; kalau tidak, pembangunan ulang gagal di tengah.
        DB::table('download_logs')->whereNull('user_id')->delete();

        $this->ubahKaitanPengunduh(nullable: false);
    }

    private function ubahKaitanPengunduh(bool $nullable): void
    {
        if (DB::getDriverName() === 'sqlite') {
            $this->bangunUlangSqlite($nullable);

            return;
        }

        // MySQL/PostgreSQL: kaitan asing dapat dilepas dan dipasang ulang.
        Schema::table('download_logs', function (Blueprint $table) use ($nullable) {
            $table->dropForeign(['user_id']);
            $table->unsignedBigInteger('user_id')->nullable($nullable)->change();

            $kaitan = $table->foreign('user_id')->references('id')->on('users');

            $nullable ? $kaitan->nullOnDelete() : $kaitan->cascadeOnDelete();
        });
    }

    /**
     * SQLite tidak dapat mengubah kaitan asing yang sudah ada, jadi tabelnya
     * dibangun ulang: buat tabel baru, pindahkan isi, buang yang lama, lalu
     * ganti nama. Kaitan asing dimatikan selama proses karena `DROP TABLE`
     * dengan kaitan aktif akan merambat ke tabel anak.
     */
    private function bangunUlangSqlite(bool $nullable): void
    {
        Schema::disableForeignKeyConstraints();

        try {
            Schema::create('download_logs_baru', function (Blueprint $table) use ($nullable) {
                $table->id();
                $table->foreignId('book_id')->constrained()->cascadeOnDelete();

                if ($nullable) {
                    $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                } else {
                    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                }

                $table->foreignId('prodi_id')->nullable()->constrained('prodi')->nullOnDelete();
                $table->string('mode', 20);
                $table->timestamps();

                // Indeks sengaja belum dibuat di sini: nama indeks bersifat
                // global di SQLite, sehingga bentrok dengan indeks tabel lama
                // yang masih hidup. Dipasang setelah penggantian nama.
            });

            $kolom = implode(', ', array_map(fn (string $nama): string => '"'.$nama.'"', self::KOLOM));

            DB::statement("INSERT INTO \"download_logs_baru\" ({$kolom}) SELECT {$kolom} FROM \"download_logs\"");

            Schema::drop('download_logs');

            DB::statement('PRAGMA legacy_alter_table = ON');
            Schema::rename('download_logs_baru', 'download_logs');
            DB::statement('PRAGMA legacy_alter_table = OFF');

            Schema::table('download_logs', function (Blueprint $table) {
                $table->index(['book_id', 'created_at']);
            });
        } finally {
            // Wajib menyala kembali walaupun ada kegagalan di tengah jalan.
            Schema::enableForeignKeyConstraints();
        }

        $menggantung = DB::select('PRAGMA foreign_key_check("download_logs")');

        if ($menggantung !== []) {
            throw new RuntimeException(
                'Tabel download_logs masih memuat kaitan menggantung setelah dibangun ulang: '
                .json_encode($menggantung)
            );
        }
    }
};