<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\BerkasBukuService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Menyapu berkas sementara yang sudah melewati masa simpan.
 *
 * Sejak fitur baca-berwatermark ada, folder sementara berjumlah DUA:
 *
 *   - ebook.unduh.folder  → hasil potong/stempel untuk diunduh
 *   - ebook.baca.folder   → hasil stempel untuk dibaca di pembaca
 *
 * Folder bacaan yang paling cepat menumpuk, sebab setiap pembukaan buku
 * berwatermark menghasilkan satu berkas baru, sedangkan unduhan hanya
 * terjadi sesekali. Karena perintah ini berjalan lewat penjadwal, isi
 * laporannya adalah satu-satunya pengawasan yang Anda punya — jadi
 * keduanya harus ikut terukur.
 */
class BersihkanUnduhanSementara extends Command
{
    protected $signature = 'ebook:bersihkan-unduhan';

    protected $description = 'Menghapus berkas unduhan dan bacaan sementara yang sudah melewati masa simpan.';

    public function handle(BerkasBukuService $berkas): int
    {
        $sebelum = $this->ukuranSetiapFolder();

        try {
            $jumlah = $berkas->bersihkanBerkasKedaluwarsa();
        } catch (Throwable $galat) {
            // Pembersihan yang gagal tidak boleh diam-diam: kalau perintah ini
            // berjalan lewat penjadwal, log adalah satu-satunya saksi.
            report($galat);
            $this->error('Pembersihan gagal: '.$galat->getMessage());

            return self::FAILURE;
        }

        $sesudah = $this->ukuranSetiapFolder();

        $lega = [];
        $legaTotal = 0;

        foreach ($sebelum as $nama => $byte) {
            $selisih = max(0, $byte - ($sesudah[$nama] ?? 0));
            $lega[$nama] = $selisih;
            $legaTotal += $selisih;
        }

        /*
         * Dua ukuran diperiksa berdua, bukan salah satu: jumlah berkas
         * datang dari service, ruang yang dibebaskan diukur di sini. Bila
         * keduanya pernah tidak sepakat — misalnya service menyapu sebuah
         * folder tetapi tidak menghitungnya — yang muncul di log adalah
         * ketidaksepakatan itu, bukan kalimat "sudah bersih" yang keliru.
         */
        if ($jumlah === 0 && $legaTotal === 0) {
            $this->info('Tidak ada berkas kedaluwarsa. Semua folder sementara sudah bersih.');

            return self::SUCCESS;
        }

        $this->info(sprintf(
            '%d berkas dihapus, %s dibebaskan.',
            $jumlah,
            $this->ukuranTerbaca($legaTotal),
        ));

        foreach ($lega as $nama => $byte) {
            $this->line(sprintf('  - %s: %s', $nama, $this->ukuranTerbaca($byte)));
        }

        return self::SUCCESS;
    }

    /**
     * Folder sementara yang diawasi, beserta disknya.
     *
     * @return array<string, array{disk: string, folder: string}>
     */
    private function folderDiawasi(): array
    {
        $diskUnduh = (string) config('ebook.unduh.disk', 'local');

        $daftar = [
            'unduhan sementara' => [
                'disk' => $diskUnduh,
                'folder' => trim((string) config('ebook.unduh.folder', 'unduhan-sementara'), '/'),
            ],
            'bacaan sementara' => [
                // Folder bacaan belum punya setelan disk sendiri; selama
                // belum ada, ia menumpang disk unduhan.
                'disk' => (string) config('ebook.baca.disk', $diskUnduh),
                'folder' => trim((string) config('ebook.baca.folder', 'bacaan-sementara'), '/'),
            ],
        ];

        // Bila kelak kedua setelan menunjuk tempat yang sama, cukup dihitung
        // sekali supaya ruangnya tidak dilaporkan dua kali.
        return collect($daftar)
            ->unique(fn (array $satu): string => $satu['disk'].'|'.$satu['folder'])
            ->all();
    }

    /**
     * Ukuran tiap folder sementara, dalam byte.
     *
     * @return array<string, int>
     */
    private function ukuranSetiapFolder(): array
    {
        $hasil = [];

        foreach ($this->folderDiawasi() as $nama => $tempat) {
            $hasil[$nama] = $this->ukuranFolder($tempat['disk'], $tempat['folder']);
        }

        return $hasil;
    }

    /**
     * Total ukuran satu folder, dalam byte.
     *
     * Dihitung sebelum dan sesudah pembersihan supaya laporannya menyebut
     * ruang yang nyata, bukan hanya jumlah berkas.
     */
    private function ukuranFolder(string $namaDisk, string $folder): int
    {
        if ($folder === '') {
            return 0;
        }

        try {
            $disk = Storage::disk($namaDisk);
            $daftarBerkas = $disk->allFiles($folder);
        } catch (Throwable) {
            // Disk yang salah tulis di config, atau folder yang belum pernah
            // dibuat, tidak boleh menggagalkan pembersihan — hanya laporannya
            // yang kehilangan satu angka.
            return 0;
        }

        $total = 0;

        foreach ($daftarBerkas as $namaBerkas) {
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