<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Book;
use App\Support\PdfHelper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Memeriksa ulang page_count seluruh buku terhadap isi PDF yang sebenarnya.
 *
 * Buku yang diunggah sebelum qpdf dipakai dihitung dengan cara lama yang bisa
 * meleset. Angka itu bukan sekadar hiasan: mode unduh sebagian bersandar
 * padanya, jadi angka yang salah berarti unduhan yang gagal di tangan mahasiswa.
 */
class PerbaikiJumlahHalaman extends Command
{
    protected $signature = 'ebook:perbaiki-halaman
                            {--terapkan : Simpan perbaikan. Tanpa opsi ini perintah hanya melaporkan.}';

    protected $description = 'Membandingkan jumlah halaman tersimpan dengan isi PDF, dan memperbaikinya bila diminta.';

    public function handle(): int
    {
        $terapkan = (bool) $this->option('terapkan');

        $diperiksa = 0;
        $cocok = 0;
        $beda = [];
        $bermasalah = [];
        $rentangRusak = [];

        Book::query()
            ->orderBy('id')
            ->chunkById(50, function ($daftarBuku) use (
                &$diperiksa, &$cocok, &$beda, &$bermasalah, &$rentangRusak, $terapkan
            ) {
                foreach ($daftarBuku as $buku) {
                    $diperiksa++;

                    $sebenarnya = $this->hitungHalaman($buku, $catatan);

                    if ($sebenarnya === null) {
                        $bermasalah[] = [$buku->id, $this->judul($buku), $buku->page_count ?? '—', $catatan];

                        continue;
                    }

                    // Rentang unduhan yang melampaui isi buku akan gagal saat
                    // qpdf memotongnya. Dilaporkan, tidak diubah sendiri.
                    if (
                        $buku->access_mode === Book::AKSES_SEBAGIAN
                        && $buku->download_page_end !== null
                        && $buku->download_page_end > $sebenarnya
                    ) {
                        $rentangRusak[] = [
                            $buku->id,
                            $this->judul($buku),
                            $buku->download_page_start.'–'.$buku->download_page_end,
                            $sebenarnya,
                        ];
                    }

                    if ($buku->page_count === $sebenarnya) {
                        $cocok++;

                        continue;
                    }

                    $beda[] = [$buku->id, $this->judul($buku), $buku->page_count ?? '—', $sebenarnya];

                    if ($terapkan) {
                        $this->simpanJumlah($buku, $sebenarnya);
                    }
                }
            });

        return $this->laporkan($diperiksa, $cocok, $beda, $bermasalah, $rentangRusak, $terapkan);
    }

    /** Jumlah halaman menurut isi berkas, atau null beserta alasannya. */
    private function hitungHalaman(Book $buku, ?string &$catatan): ?int
    {
        $catatan = null;

        if (! $buku->file_path) {
            $catatan = 'Tidak ada berkas terdaftar';

            return null;
        }

        $disk = Storage::disk('local');

        if (! $disk->exists($buku->file_path)) {
            $catatan = 'Berkas hilang dari penyimpanan';

            return null;
        }

        try {
            $jumlah = PdfHelper::hitungHalaman($disk->path($buku->file_path));
        } catch (Throwable $galat) {
            $catatan = Str::limit($galat->getMessage(), 60);

            return null;
        }

        if ($jumlah === null) {
            $catatan = 'PDF tidak terbaca (lihat laravel.log)';
        }

        return $jumlah;
    }

    /**
     * Menyimpan angka baru tanpa menyentuh updated_at.
     *
     * Ini perbaikan data, bukan penyuntingan oleh dosen. Kalau updated_at ikut
     * berubah, seluruh katalog akan tampak baru disunting serentak dan urutan
     * daftar buku di halaman admin jadi kacau.
     */
    private function simpanJumlah(Book $buku, int $jumlah): void
    {
        $buku->timestamps = false;
        $buku->page_count = $jumlah;
        $buku->saveQuietly();
        $buku->timestamps = true;
    }

    private function judul(Book $buku): string
    {
        return Str::limit((string) $buku->title, 40);
    }

    /**
     * @param  array<int, array<int, mixed>>  $beda
     * @param  array<int, array<int, mixed>>  $bermasalah
     * @param  array<int, array<int, mixed>>  $rentangRusak
     */
    private function laporkan(
        int $diperiksa,
        int $cocok,
        array $beda,
        array $bermasalah,
        array $rentangRusak,
        bool $terapkan,
    ): int {
        $this->newLine();
        $this->line("Diperiksa: {$diperiksa} buku · cocok: {$cocok}");

        if ($beda !== []) {
            $this->newLine();
            $this->line($terapkan ? 'Jumlah halaman diperbaiki:' : 'Jumlah halaman berbeda (belum diubah):');
            $this->table(['ID', 'Judul', 'Tersimpan', 'Sebenarnya'], $beda);
        }

        if ($bermasalah !== []) {
            $this->newLine();
            $this->warn('Tidak dapat diperiksa:');
            $this->table(['ID', 'Judul', 'Tersimpan', 'Sebab'], $bermasalah);
        }

        if ($rentangRusak !== []) {
            $this->newLine();
            $this->error('Rentang unduh sebagian melampaui isi buku.');
            $this->line('Unduhan buku berikut akan gagal. Perbaiki lewat form sunting buku:');
            $this->table(['ID', 'Judul', 'Rentang diatur', 'Halaman sebenarnya'], $rentangRusak);
        }

        $this->newLine();

        if ($beda === [] && $bermasalah === [] && $rentangRusak === []) {
            $this->info('Semua buku sehat. Tidak ada yang perlu diperbaiki.');

            return self::SUCCESS;
        }

        if (! $terapkan && $beda !== []) {
            $this->comment('Jalankan ulang dengan --terapkan untuk menyimpan perbaikan.');
        }

        return self::SUCCESS;
    }
}