<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\Pdf\Qpdf;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Memeriksa kesiapan peralatan pengolah PDF sebelum ada yang jadi korban.
 *
 * Tanpa perintah ini, satu-satunya cara mengetahui qpdf salah setel adalah
 * mengunggah buku, mengunduhnya, lalu memeriksa apakah stempelnya ada — dan
 * kalau tidak ada, tidak ada pesan apa pun yang menerangkan sebabnya.
 * Jalankan perintah ini setiap kali selesai deploy atau mengubah .env.
 *
 * Kode keluar 1 bila ada yang belum siap, sehingga bisa dipakai sebagai
 * pagar di skrip deploy.
 */
class PeriksaQpdf extends Command
{
    protected $signature = 'ebook:periksa-qpdf';

    protected $description = 'Memeriksa kesiapan peralatan pengolah PDF: qpdf, FPDF, dan folder sementara.';

    public function handle(Qpdf $qpdf): int
    {
        $this->line('Pemeriksaan peralatan pengolah PDF');
        $this->line('==================================');
        $this->line('');

        // Ditulis begini, bukan dengan &&, supaya ketiga pemeriksaan selalu
        // dijalankan. Orang yang men-deploy sebaiknya melihat seluruh daftar
        // masalah sekali jalan, bukan menemukannya satu per satu.
        $qpdfSiap = $this->periksaQpdf($qpdf);
        $fpdfSiap = $this->periksaFpdf();
        $folderSiap = $this->periksaFolderSementara();

        $this->line('');

        if ($qpdfSiap && $fpdfSiap && $folderSiap) {
            $this->info('Semua peralatan siap.');

            return self::SUCCESS;
        }

        $this->error('Ada peralatan yang belum siap.');
        $this->line('Selama ini belum dibereskan, unduhan berstempel dan mode "unduh sebagian" akan ditolak.');

        return self::FAILURE;
    }

    private function periksaQpdf(Qpdf $qpdf): bool
    {
        $diagnosa = $qpdf->diagnosa();
        $wajib = (bool) config('ebook.qpdf.wajib', true);

        $this->line('QPDF_BINARY   : '.($diagnosa['binary'] === '' ? '(kosong)' : $diagnosa['binary']));
        $this->line('QPDF_TIMEOUT  : '.$diagnosa['timeout'].' detik');
        $this->line('Wajib ada     : '.($wajib ? 'ya' : 'tidak (EBOOK_QPDF_WAJIB=false)'));

        if ($diagnosa['tersedia']) {
            $this->info('[ OK  ] qpdf berjalan — '.($diagnosa['versi'] ?? 'baris versi tidak terbaca'));

            return true;
        }

        $this->error('[GAGAL] qpdf tidak dapat dipakai.');
        $this->line('        Sebab: '.($diagnosa['alasan'] ?? 'tidak diketahui'));
        $this->line('        Perbaiki QPDF_BINARY di berkas .env, lalu jalankan: php artisan config:clear');
        $this->line('        Linux  : dnf install qpdf  (atau apt install qpdf), lalu QPDF_BINARY=qpdf');
        $this->line('        Windows: QPDF_BINARY="C:/Program Files/qpdf 12.3.2/bin/qpdf.exe"');

        return false;
    }

    private function periksaFpdf(): bool
    {
        if (class_exists(\FPDF::class)) {
            $this->info('[ OK  ] Pustaka FPDF terpasang (penulis lembar stempel).');

            return true;
        }

        $this->error('[GAGAL] Pustaka FPDF tidak ditemukan.');
        $this->line('        Jalankan: composer install');

        return false;
    }

    /**
     * Folder sementara harus benar-benar dapat ditulisi.
     *
     * Izin folder adalah penyebab kegagalan paling sering sesudah deploy,
     * dan gejalanya membingungkan: qpdf berjalan mulus, tetapi hasilnya
     * tidak pernah sampai ke pengguna.
     */
    private function periksaFolderSementara(): bool
    {
        $namaDisk = (string) config('ebook.unduh.disk', 'local');

        // Folder bacaan hanya ada bila batch 2 sudah dipasang; array_filter
        // membuat pemeriksaan ini tetap jalan tanpanya.
        $folderDiperiksa = array_filter(array_unique([
            (string) config('ebook.unduh.folder', 'unduhan-sementara'),
            (string) config('ebook.baca.folder', ''),
        ]));

        $semuaSiap = true;

        foreach ($folderDiperiksa as $folder) {
            $jalurUji = $folder.'/.periksa-'.bin2hex(random_bytes(4));

            try {
                $disk = Storage::disk($namaDisk);
                $disk->put($jalurUji, 'uji');
                $terbaca = $disk->get($jalurUji) === 'uji';
                $disk->delete($jalurUji);
            } catch (Throwable $galat) {
                $this->error("[GAGAL] Folder {$folder} pada disk {$namaDisk} tidak dapat ditulisi.");
                $this->line('        Sebab: '.$galat->getMessage());
                $semuaSiap = false;

                continue;
            }

            if (! $terbaca) {
                $this->error("[GAGAL] Folder {$folder} pada disk {$namaDisk} dapat ditulisi tetapi tidak dapat dibaca kembali.");
                $semuaSiap = false;

                continue;
            }

            $this->info("[ OK  ] Folder {$folder} pada disk {$namaDisk} dapat ditulis dan dibaca.");
        }

        return $semuaSiap;
    }
}