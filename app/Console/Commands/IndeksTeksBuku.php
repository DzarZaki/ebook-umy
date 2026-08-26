<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Book;
use App\Support\Pdf\PengekstrakTeks;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Mengisi indeks teks isi untuk buku-buku yang belum memilikinya.
 *
 * Buku yang diunggah sejak fitur pencarian isi diperkenalkan sudah
 * terindeks otomatis saat unggah; perintah ini mengejar keterlambatan
 * untuk koleksi lama, atau mengulang seluruhnya bila algoritma
 * ekstraksinya kelak berubah.
 */
class IndeksTeksBuku extends Command
{
    protected $signature = 'ebook:indeks-teks
        {--ulang : Ekstrak ulang seluruh buku, termasuk yang sudah punya indeks}';

    protected $description = 'Mengisi indeks teks isi buku untuk pencarian full-text.';

    public function handle(PengekstrakTeks $pengekstrak): int
    {
        $kueri = Book::query()->orderBy('id');

        if (! $this->option('ulang')) {
            $kueri->whereNull('search_text');
        }

        $daftarBuku = $kueri->get();

        if ($daftarBuku->isEmpty()) {
            $this->info('Tidak ada buku yang perlu diindeks.');

            return self::SUCCESS;
        }

        $this->info("Mengindeks {$daftarBuku->count()} buku…");

        $bilahKemajuan = $this->output->createProgressBar($daftarBuku->count());
        $terindeks = 0;
        $dilewati = 0;

        foreach ($daftarBuku as $buku) {
            $jalur = Storage::disk('local')->path($buku->file_path);

            $teks = is_file($jalur) ? $pengekstrak->ekstrak($jalur) : null;

            if ($teks === null) {
                $dilewati++;
                $bilahKemajuan->advance();

                continue;
            }

            // Memperbarui indeks bukan perubahan koleksi yang layak
            // menggeser urutan "terbaru" pada daftar dosen.
            $buku->timestamps = false;
            $buku->search_text = $teks;
            $buku->save();
            $buku->timestamps = true;

            $terindeks++;
            $bilahKemajuan->advance();
        }

        $bilahKemajuan->finish();
        $this->newLine(2);
        $this->info("Selesai: {$terindeks} terindeks, {$dilewati} dilewati.");
        $this->line('Yang dilewati: berkasnya hilang dari penyimpanan, atau PDF-nya tidak memiliki lapisan teks (hasil pindai).');

        return self::SUCCESS;
    }
}
