<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Book;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Membersihkan buku yang sudah lama dibuang, berikut berkasnya,
 * lalu memburu berkas yatim yang tidak dimiliki baris mana pun.
 *
 * Tanpa --terapkan, perintah ini hanya melapor dan tidak menghapus apa pun.
 */
class BersihkanBuku extends Command
{
    protected $signature = 'ebook:bersihkan-buku
                            {--terapkan : Benar-benar menghapus. Tanpa ini perintah hanya melapor.}
                            {--hari= : Masa tenggang dalam hari, menimpa nilai konfigurasi.}
                            {--lewati-yatim : Lewati perburuan berkas yatim.}';

    protected $description = 'Melenyapkan buku yang lewat masa tenggang di tempat sampah, dan berkas yatim di penyimpanan.';

    /** Berkas yang lebih muda dari ini tidak pernah disentuh. */
    private const USIA_AMAN_JAM = 24;

    /** Folder yang diawasi, dipetakan ke disk tempatnya berada. */
    private const FOLDER_DIAWASI = [
        ['disk' => 'local', 'folder' => 'books', 'kolom' => 'file_path'],
        ['disk' => 'public', 'folder' => 'covers', 'kolom' => 'cover_path'],
    ];

    public function handle(): int
    {
        $hari = $this->tenggangHari();

        if ($hari === null) {
            return self::FAILURE;
        }

        $terapkan = (bool) $this->option('terapkan');
        $batas = now()->subDays($hari);

        $this->info(sprintf(
            'Masa tenggang %d hari. Buku yang dibuang sebelum %s akan dilenyapkan.',
            $hari,
            $batas->format('d/m/Y H:i')
        ));

        if (! $terapkan) {
            $this->warn('Mode laporan. Tidak ada yang dihapus. Tambahkan --terapkan untuk menjalankannya.');
        }

        $this->newLine();

        $hasilBuku = $this->lenyapkanBuku($batas, $terapkan);

        $hasilYatim = ['jumlah' => 0, 'ukuran' => 0];

        if (! $this->option('lewati-yatim')) {
            $this->newLine();
            $hasilYatim = $this->buruBerkasYatim($terapkan);
        }

        $this->newLine();
        $this->ringkas($hasilBuku, $hasilYatim, $terapkan);

        return self::SUCCESS;
    }

    /** Membaca masa tenggang dari opsi atau konfigurasi, menolak nilai tak masuk akal. */
    private function tenggangHari(): ?int
    {
        $dariOpsi = $this->option('hari');

        if ($dariOpsi === null) {
            return max(1, (int) config('ebook.sampah.tenggang_hari', 30));
        }

        if (! is_numeric($dariOpsi) || (int) $dariOpsi < 1) {
            $this->error('Nilai --hari harus berupa angka minimal 1.');

            return null;
        }

        return (int) $dariOpsi;
    }

    /**
     * Melenyapkan buku yang sudah lewat masa tenggang.
     *
     * Berkas dihapus lebih dulu. Kalau ada yang gagal dihapus, barisnya sengaja
     * dibiarkan hidup di tempat sampah supaya percobaan berikutnya bisa mengulanginya.
     * Baris yang hilang sementara berkasnya tertinggal adalah keadaan yang paling
     * sulit dibereskan, jadi urutan ini dipilih dengan sengaja.
     */
    private function lenyapkanBuku(\DateTimeInterface $batas, bool $terapkan): array
    {
        $baris = [];
        $gagal = [];
        $ukuranTotal = 0;

        Book::onlyTrashed()
            ->where('deleted_at', '<=', $batas)
            ->orderBy('id')
            ->chunkById(50, function (Collection $kumpulan) use (&$baris, &$gagal, &$ukuranTotal, $terapkan): void {
                foreach ($kumpulan as $buku) {
                    $berkas = $this->berkasNyata($buku);
                    $ukuran = array_sum(array_column($berkas, 'ukuran'));

                    if (! $terapkan) {
                        $baris[] = [
                            $buku->id,
                            $this->potong($buku->title),
                            $buku->deleted_at?->format('d/m/Y') ?? '-',
                            count($berkas),
                            $this->ukuranTerbaca($ukuran),
                        ];
                        $ukuranTotal += $ukuran;

                        continue;
                    }

                    try {
                        foreach ($berkas as $item) {
                            Storage::disk($item['disk'])->delete($item['jalur']);
                        }

                        $buku->forceDelete();

                        Log::info('BersihkanBuku: buku dilenyapkan.', [
                            'id' => $buku->id,
                            'judul' => $buku->title,
                            'berkas' => array_column($berkas, 'jalur'),
                        ]);

                        $baris[] = [
                            $buku->id,
                            $this->potong($buku->title),
                            $buku->deleted_at?->format('d/m/Y') ?? '-',
                            count($berkas),
                            $this->ukuranTerbaca($ukuran),
                        ];
                        $ukuranTotal += $ukuran;
                    } catch (Throwable $e) {
                        Log::error('BersihkanBuku: gagal melenyapkan buku.', [
                            'id' => $buku->id,
                            'pesan' => $e->getMessage(),
                        ]);

                        $gagal[] = [$buku->id, $this->potong($buku->title), $this->potong($e->getMessage(), 60)];
                    }
                }
            });

        if ($baris === [] && $gagal === []) {
            $this->line('Tempat sampah bersih. Tidak ada buku yang melewati masa tenggang.');

            return ['jumlah' => 0, 'ukuran' => 0, 'gagal' => 0];
        }

        if ($baris !== []) {
            $this->line($terapkan ? 'Buku yang dilenyapkan:' : 'Buku yang akan dilenyapkan:');
            $this->table(['ID', 'Judul', 'Dibuang', 'Berkas', 'Ukuran'], $baris);
        }

        if ($gagal !== []) {
            $this->newLine();
            $this->error('Buku berikut gagal dibersihkan dan masih tertinggal di tempat sampah:');
            $this->table(['ID', 'Judul', 'Sebab'], $gagal);
        }

        return ['jumlah' => count($baris), 'ukuran' => $ukuranTotal, 'gagal' => count($gagal)];
    }

    /** Mengumpulkan berkas milik buku yang benar-benar ada di penyimpanan. */
    private function berkasNyata(Book $buku): array
    {
        $hasil = [];

        foreach ($buku->berkasnya() as [$disk, $jalur]) {
            if (blank($jalur)) {
                continue;
            }

            $penyimpanan = Storage::disk($disk);

            if (! $penyimpanan->exists($jalur)) {
                continue;
            }

            $hasil[] = [
                'disk' => $disk,
                'jalur' => $jalur,
                'ukuran' => (int) $penyimpanan->size($jalur),
            ];
        }

        return $hasil;
    }

    /**
     * Memburu berkas di penyimpanan yang tidak dimiliki baris mana pun,
     * termasuk baris yang sedang berada di tempat sampah.
     */
    private function buruBerkasYatim(bool $terapkan): array
    {
        // Rem pengaman: tabel kosong hampir selalu berarti salah sambung database,
        // bukan berarti seluruh isi penyimpanan benar-benar yatim.
        if (Book::withTrashed()->count() === 0) {
            $this->warn('Tabel buku kosong. Perburuan berkas yatim dilewati demi keamanan.');

            return ['jumlah' => 0, 'ukuran' => 0];
        }

        $ambangUsia = now()->subHours(self::USIA_AMAN_JAM)->getTimestamp();
        $baris = [];
        $ukuranTotal = 0;

        foreach (self::FOLDER_DIAWASI as $awasan) {
            $penyimpanan = Storage::disk($awasan['disk']);

            $dimiliki = Book::withTrashed()
                ->whereNotNull($awasan['kolom'])
                ->pluck($awasan['kolom'])
                ->flip();

            foreach ($penyimpanan->files($awasan['folder']) as $jalur) {
                if ($dimiliki->has($jalur)) {
                    continue;
                }

                // Berkas yang baru saja diunggah bisa jadi sedang dalam proses
                // penyimpanan, barisnya belum sempat tercatat. Jangan disentuh.
                if ($penyimpanan->lastModified($jalur) > $ambangUsia) {
                    continue;
                }

                $ukuran = (int) $penyimpanan->size($jalur);

                if ($terapkan) {
                    $penyimpanan->delete($jalur);

                    Log::info('BersihkanBuku: berkas yatim dihapus.', [
                        'disk' => $awasan['disk'],
                        'jalur' => $jalur,
                    ]);
                }

                $baris[] = [$awasan['disk'], $this->potong($jalur, 60), $this->ukuranTerbaca($ukuran)];
                $ukuranTotal += $ukuran;
            }
        }

        if ($baris === []) {
            $this->line('Tidak ada berkas yatim di penyimpanan.');

            return ['jumlah' => 0, 'ukuran' => 0];
        }

        $this->line($terapkan ? 'Berkas yatim yang dihapus:' : 'Berkas yatim yang ditemukan:');
        $this->table(['Disk', 'Jalur', 'Ukuran'], $baris);

        return ['jumlah' => count($baris), 'ukuran' => $ukuranTotal];
    }

    private function ringkas(array $buku, array $yatim, bool $terapkan): void
    {
        $ruang = $this->ukuranTerbaca($buku['ukuran'] + $yatim['ukuran']);

        $pesan = sprintf(
            '%s %d buku · %d berkas yatim · %s',
            $terapkan ? 'Dibersihkan:' : 'Akan dibersihkan:',
            $buku['jumlah'],
            $yatim['jumlah'],
            $ruang
        );

        ($buku['gagal'] ?? 0) > 0
            ? $this->warn($pesan.sprintf(' · %d gagal', $buku['gagal']))
            : $this->info($pesan);
    }

    private function ukuranTerbaca(int $bita): string
    {
        if ($bita < 1024) {
            return $bita.' B';
        }

        if ($bita < 1048576) {
            return round($bita / 1024, 1).' KB';
        }

        return round($bita / 1048576, 1).' MB';
    }

    private function potong(?string $teks, int $panjang = 40): string
    {
        $teks = (string) $teks;

        return mb_strlen($teks) > $panjang
            ? mb_substr($teks, 0, $panjang - 1).'…'
            : $teks;
    }
}