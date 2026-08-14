<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\BerkasBukuService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

class BersihkanUnduhanSementara extends Command
{
    protected $signature = 'ebook:bersihkan-unduhan';

    protected $description = 'Menghapus berkas unduhan sementara yang sudah melewati masa simpan.';

    public function handle(BerkasBukuService $berkas): int
    {
        $sebelum = $this->ukuranFolder();

        try {
            $jumlah = $berkas->bersihkanBerkasKedaluwarsa();
        } catch (Throwable $galat) {
            // Pembersihan yang gagal tidak boleh diam-diam: kalau perintah ini
            // berjalan lewat penjadwal, log adalah satu-satunya saksi.
            report($galat);
            $this->error('Pembersihan gagal: '.$galat->getMessage());

            return self::FAILURE;
        }

        if ($jumlah === 0) {
            $this->info('Tidak ada berkas kedaluwarsa. Folder sementara sudah bersih.');

            return self::SUCCESS;
        }

        $lega = max(0, $sebelum - $this->ukuranFolder());

        $this->info(sprintf(
            '%d berkas dihapus, %s dibebaskan.',
            $jumlah,
            $this->ukuranTerbaca($lega),
        ));

        return self::SUCCESS;
    }

    /**
     * Total ukuran folder sementara, dalam byte.
     *
     * Dihitung sebelum dan sesudah pembersihan supaya laporannya menyebut
     * ruang yang nyata, bukan hanya jumlah berkas.
     */
    private function ukuranFolder(): int
    {
        $disk = Storage::disk((string) config('ebook.unduh.disk', 'local'));
        $folder = (string) config('ebook.unduh.folder', 'unduhan-sementara');

        $total = 0;

        foreach ($disk->allFiles($folder) as $namaBerkas) {
            try {
                $total += $disk->size($namaBerkas);
            } catch (Throwable) {
                // Berkas bisa lenyap di tengah penghitungan. Bukan masalah.
            }
        }

        return $total;
    }

    private function ukuranTerbaca(int $byte): string
    {
        if ($byte < 1024) {
            return $byte.' B';
        }

        $satuan = ['KB', 'MB', 'GB'];
        $nilai = $byte / 1024;
        $indeks = 0;

        while ($nilai >= 1024 && $indeks < count($satuan) - 1) {
            $nilai /= 1024;
            $indeks++;
        }

        return number_format($nilai, 1, ',', '.').' '.$satuan[$indeks];
    }
}