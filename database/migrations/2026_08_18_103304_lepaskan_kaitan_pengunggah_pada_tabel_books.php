<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Melepaskan buku dari nasib akun pengunggahnya.
 *
 * Sebelum migrasi ini, books.uploaded_by memakai cascadeOnDelete. Menghapus
 * satu akun dosen berarti database menghapus seluruh bukunya di luar Eloquent
 * — sehingga SoftDeletes tidak berlaku, tempat sampah dilewati, dan cascade
 * berlanjut melenyapkan progres baca, penanda, koleksi tersimpan, serta
 * riwayat unduhan milik semua mahasiswa. Berkas PDF-nya lalu disapu penjadwal
 * sebagai berkas yatim.
 *
 * Koleksi perpustakaan tidak boleh bergantung pada masa kerja seorang dosen.
 * Setelah migrasi ini, akun yang terhapus hanya menyisakan uploaded_by = NULL.
 */
return new class extends Migration
{
    /**
     * PRAGMA foreign_keys pada SQLite tidak berpengaruh di dalam transaksi,
     * sedangkan pembangunan ulang tabel di bawah wajib mematikannya sementara.
     * Karena itu migrasi ini sengaja berjalan tanpa transaksi.
     */
    public $withinTransaction = false;

    /**
     * Daftar kolom disebut tegas, bukan SELECT *, karena urutan fisik kolom
     * berbeda antar driver: deleted_at ditambahkan dengan after('is_published')
     * yang dihormati MySQL tetapi diabaikan SQLite.
     */
    private const KOLOM = [
        'id', 'title', 'slug', 'author', 'description',
        'prodi_id', 'category_id', 'uploaded_by',
        'file_path', 'file_size', 'page_count', 'cover_path',
        'access_mode', 'download_page_start', 'download_page_end',
        'watermark_enabled', 'is_published',
        'deleted_at', 'created_at', 'updated_at',
    ];

    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            $this->bangunUlangSqlite(bolehKosong: true);

            return;
        }

        Schema::table('books', function (Blueprint $tabel) {
            $tabel->dropForeign(['uploaded_by']);
        });

        Schema::table('books', function (Blueprint $tabel) {
            $tabel->unsignedBigInteger('uploaded_by')->nullable()->change();
            $tabel->foreign('uploaded_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        // Kembali ke cascade berarti kolomnya wajib NOT NULL lagi. Buku yang
        // sudah kehilangan pengunggah tidak bisa dipaksa punya pemilik, dan
        // menghapusnya diam-diam justru mengulang kerusakan yang hendak
        // dicegah migrasi ini. Jadi operator diminta memutuskan lebih dulu.
        $yatim = DB::table('books')->whereNull('uploaded_by')->count();

        if ($yatim > 0) {
            throw new RuntimeException(
                "Terdapat {$yatim} buku tanpa pengunggah. Tetapkan pengunggah baru "
                .'pada baris-baris itu sebelum migrasi ini dibatalkan.'
            );
        }

        if (DB::getDriverName() === 'sqlite') {
            $this->bangunUlangSqlite(bolehKosong: false);

            return;
        }

        Schema::table('books', function (Blueprint $tabel) {
            $tabel->dropForeign(['uploaded_by']);
        });

        Schema::table('books', function (Blueprint $tabel) {
            $tabel->unsignedBigInteger('uploaded_by')->nullable(false)->change();
            $tabel->foreign('uploaded_by')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    /**
     * Membangun ulang tabel books pada SQLite.
     *
     * SQLite tidak mengenal perintah untuk mengubah atau membuang foreign key,
     * jadi satu-satunya jalan adalah prosedur resminya: matikan penjagaan FK,
     * buat tabel baru, pindahkan datanya, buang tabel lama, ganti nama.
     *
     * Mematikan penjagaan FK di sini bukan kelonggaran, melainkan keharusan:
     * dengan FK aktif, DROP TABLE books akan memicu penghapusan berantai pada
     * reading_progress, bookmarks, book_saves, dan download_logs — persis
     * bencana yang sedang kita cegah.
     */
    private function bangunUlangSqlite(bool $bolehKosong): void
    {
        $kolom = implode(', ', array_map(
            static fn (string $nama): string => '"'.$nama.'"',
            self::KOLOM,
        ));

        Schema::disableForeignKeyConstraints();

        try {
            Schema::dropIfExists('books_bangun_ulang');

            Schema::create('books_bangun_ulang', function (Blueprint $tabel) use ($bolehKosong) {
                $tabel->id();
                $tabel->string('title');

                // Indeks belum dibuat di sini: nama indeks bersifat global di
                // SQLite, jadi ia akan bertabrakan dengan indeks tabel lama
                // yang masih hidup. Dibuat setelah penggantian nama.
                $tabel->string('slug');

                $tabel->string('author')->nullable();
                $tabel->text('description')->nullable();

                $tabel->foreignId('prodi_id')->nullable()->constrained('prodi')->nullOnDelete();
                $tabel->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();

                if ($bolehKosong) {
                    $tabel->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
                } else {
                    $tabel->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
                }

                $tabel->string('file_path');
                $tabel->unsignedBigInteger('file_size')->default(0);
                $tabel->unsignedInteger('page_count')->nullable();
                $tabel->string('cover_path')->nullable();

                $tabel->enum('access_mode', ['full', 'partial', 'readonly'])->default('readonly');
                $tabel->unsignedInteger('download_page_start')->nullable();
                $tabel->unsignedInteger('download_page_end')->nullable();
                $tabel->boolean('watermark_enabled')->default(true);
                $tabel->boolean('is_published')->default(true);

                $tabel->softDeletes();
                $tabel->timestamps();
            });

            DB::statement("INSERT INTO \"books_bangun_ulang\" ({$kolom}) SELECT {$kolom} FROM \"books\"");

            Schema::drop('books');

            // legacy_alter_table dimatikan sejenak supaya SQLite tidak
            // memeriksa ulang seluruh skema saat penggantian nama. Tanpa ini,
            // acuan ke tabel "books" yang sedang tiada — di reading_progress
            // dan kawan-kawan — dapat menggagalkan perintah.
            DB::statement('PRAGMA legacy_alter_table = ON');

            try {
                Schema::rename('books_bangun_ulang', 'books');
            } finally {
                DB::statement('PRAGMA legacy_alter_table = OFF');
            }

            Schema::table('books', function (Blueprint $tabel) {
                $tabel->unique('slug', 'books_slug_unique');
                $tabel->index(['prodi_id', 'is_published'], 'books_prodi_id_is_published_index');
            });
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        // Skema yang dibangun ulang wajib diperiksa, bukan diandaikan benar.
        $pelanggaran = DB::select('PRAGMA foreign_key_check');

        if ($pelanggaran !== []) {
            throw new RuntimeException(
                'Pembangunan ulang tabel books meninggalkan '.count($pelanggaran)
                .' acuan foreign key yang menggantung. Periksa basis data sebelum melanjutkan.'
            );
        }
    }
};