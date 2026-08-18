<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Menyeragamkan data `prodi.access_code` yang sudah ada ke bentuk baku
     * (huruf besar, tanpa spasi tepi, kosong menjadi NULL), supaya pencarian
     * berbasis kolom apa adanya di Prodi::cariDenganKode() menemukan baris
     * yang sama seperti sebelumnya.
     */
    public function up(): void
    {
        // Diperiksa lebih dulu: bila dua prodi ternyata memakai kode yang
        // hanya berbeda huruf besar-kecil, penyeragaman akan menabrak indeks
        // unik. Lebih baik migrasi berhenti dengan pesan yang menyebut
        // kodenya daripada gagal di tengah jalan dengan galat basis data.
        $bentrok = DB::table('prodi')
            ->selectRaw('UPPER(TRIM(access_code)) as kode_baku, COUNT(*) as jumlah_prodi')
            ->whereNotNull('access_code')
            ->groupByRaw('UPPER(TRIM(access_code))')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('kode_baku')
            ->all();

        if ($bentrok !== []) {
            throw new RuntimeException(
                'Ada kode akses yang kembar bila huruf besar-kecil diabaikan: '
                .implode(', ', $bentrok)
                .'. Perbaiki dulu lewat halaman Kode Akses masing-masing dosen, lalu jalankan ulang migrasi ini.'
            );
        }

        // Kode kosong tidak boleh ikut diseragamkan menjadi string kosong:
        // kolom ini unik, sehingga dua prodi bertanda '' akan saling menolak.
        DB::table('prodi')
            ->whereNotNull('access_code')
            ->whereRaw("TRIM(access_code) = ''")
            ->update(['access_code' => null]);

        DB::table('prodi')
            ->whereNotNull('access_code')
            ->update(['access_code' => DB::raw('UPPER(TRIM(access_code))')]);
    }

    /**
     * Tidak dapat dibalik: bentuk huruf yang asli tidak disimpan di mana pun.
     * Membiarkannya kosong lebih jujur daripada menebak.
     */
    public function down(): void
    {
        //
    }
};