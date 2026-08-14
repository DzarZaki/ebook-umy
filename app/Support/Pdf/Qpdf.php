<?php

declare(strict_types=1);

namespace App\Support\Pdf;

use Illuminate\Contracts\Process\ProcessResult;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use RuntimeException;
use Throwable;

/**
 * Pembungkus tipis untuk program luar `qpdf`.
 *
 * Alasan memakai program luar, bukan pustaka PHP murni: pustaka PHP gratis
 * umumnya hanya sanggup membaca PDF sampai versi 1.4, sedangkan buku hasil
 * ekspor Word/InDesign masa kini memakai versi 1.5+ dengan object stream.
 * qpdf membaca semuanya.
 *
 * Seluruh method di sini menerima dan mengembalikan **jalur absolut** pada
 * filesystem, bukan jalur relatif milik Storage. Pemanggil bertanggung jawab
 * mengubahnya lebih dulu, misalnya dengan Storage::disk('local')->path(...).
 */
class Qpdf
{
    /** Hasil pemeriksaan ketersediaan; disimpan agar tidak memanggil proses berulang. */
    private ?bool $tersedia = null;

    private ?string $versi = null;

    public function __construct(
        private readonly string $binary,
        private readonly int $timeout,
    ) {
    }

    /** Membuat instance memakai nilai dari config/ebook.php. */
    public static function dariKonfigurasi(): self
    {
        return new self(
            (string) config('ebook.qpdf.binary', 'qpdf'),
            max(5, (int) config('ebook.qpdf.timeout', 60)),
        );
    }

    /**
     * Apakah qpdf benar-benar bisa dijalankan di server ini?
     *
     * Dipakai untuk degradasi yang aman: bila qpdf hilang, fitur yang
     * membutuhkannya ditolak dengan pesan jelas, bukan menyalurkan berkas
     * mentah yang justru membocorkan isi buku.
     */
    public function tersedia(): bool
    {
        if ($this->tersedia !== null) {
            return $this->tersedia;
        }

        try {
            // Sengaja memakai batas waktu pendek: ini hanya pemeriksaan versi.
            $hasil = Process::timeout(10)->run([$this->binary, '--version']);
        } catch (Throwable $galat) {
            Log::warning('qpdf tidak dapat dijalankan.', [
                'binary' => $this->binary,
                'pesan' => $galat->getMessage(),
            ]);

            return $this->tersedia = false;
        }

        if (! $hasil->successful()) {
            Log::warning('qpdf menjawab dengan kode galat saat diperiksa.', [
                'binary' => $this->binary,
                'kode' => $hasil->exitCode(),
                'keluaran' => $hasil->errorOutput() ?: $hasil->output(),
            ]);

            return $this->tersedia = false;
        }

        $baris = preg_split('/\R/', trim($hasil->output())) ?: [];
        $this->versi = $baris[0] ?? null;

        return $this->tersedia = true;
    }

    /** Baris versi qpdf, atau null bila qpdf tidak tersedia. */
    public function versi(): ?string
    {
        $this->tersedia();

        return $this->versi;
    }

    /**
     * Menghitung jumlah halaman sebuah PDF.
     *
     * Jauh lebih dapat dipercaya daripada mencocokkan pola "/Type /Page"
     * dengan regex, karena qpdf benar-benar membaca struktur dokumen.
     * Mengembalikan null bila gagal, sehingga pemanggil bisa memilih
     * jalan cadangan tanpa terhenti.
     */
    public function jumlahHalaman(string $sumber): ?int
    {
        $this->pastikanBerkasAda($sumber);

        $hasil = $this->jalankan(
            ['--show-npages', $sumber],
            'menghitung jumlah halaman',
            wajibBerhasil: false,
        );

        if (! $hasil instanceof ProcessResult) {
            return null;
        }

        $angka = (int) trim($hasil->output());

        return $angka > 0 ? $angka : null;
    }

    /**
     * Menyalin hanya rentang halaman tertentu ke berkas baru.
     * Inilah penegak mode akses "unduh sebagian": mahasiswa menerima
     * berkas yang memang hanya berisi halaman yang diizinkan dosen.
     */
    public function potongHalaman(string $sumber, int $awal, int $akhir, string $tujuan): void
    {
        $this->pastikanBerkasAda($sumber);

        if ($awal < 1 || $akhir < $awal) {
            throw new RuntimeException("Rentang halaman tidak masuk akal: {$awal}-{$akhir}.");
        }

        $this->siapkanFolder($tujuan);

        $this->jalankan([
            '--empty',
            '--warning-exit-0',
            '--pages', $sumber, "{$awal}-{$akhir}",
            '--',
            $tujuan,
        ], "memotong halaman {$awal}-{$akhir}");
    }

    /**
     * Menempelkan berkas stempel satu halaman ke SETIAP halaman dokumen.
     *
     * Berbeda dengan watermark berbentuk elemen HTML di penampil, hasil
     * di sini menyatu ke dalam berkas PDF, sehingga tetap ada ketika
     * berkas disimpan, dikirim ulang, atau dicetak.
     */
    public function tempelStempel(string $sumber, string $stempel, string $tujuan): void
    {
        $this->pastikanBerkasAda($sumber);
        $this->pastikanBerkasAda($stempel);
        $this->siapkanFolder($tujuan);

        $this->jalankan([
            $sumber,
            '--warning-exit-0',
            '--overlay', $stempel,
            '--from=1',   // pakai halaman 1 dari berkas stempel
            '--repeat=1', // lalu ulangi halaman itu sampai dokumen habis
            '--',
            $tujuan,
        ], 'menempelkan stempel watermark');
    }

    /**
     * Menjalankan qpdf dengan argumen berbentuk array.
     *
     * Bentuk array dipilih agar jalur berisi spasi — misalnya
     * "D:/qpdf 12.3.2/bin/qpdf.exe" — tidak perlu diberi tanda kutip
     * manual dan tidak bisa disalahgunakan sebagai injeksi perintah.
     *
     * @param  array<int, string>  $argumen
     */
    private function jalankan(array $argumen, string $konteks, bool $wajibBerhasil = true): ?ProcessResult
    {
        if (! $this->tersedia()) {
            if ($wajibBerhasil) {
                throw new RuntimeException(
                    'Program qpdf tidak tersedia di server ini, sehingga berkas tidak dapat diolah.'
                );
            }

            return null;
        }

        try {
            $hasil = Process::timeout($this->timeout)->run(
                array_merge([$this->binary], $argumen)
            );
        } catch (Throwable $galat) {
            Log::error("qpdf gagal saat {$konteks}.", ['pesan' => $galat->getMessage()]);

            if ($wajibBerhasil) {
                throw new RuntimeException("Gagal {$konteks}: {$galat->getMessage()}", 0, $galat);
            }

            return null;
        }

        // Catatan: qpdf memakai kode keluar 3 untuk peringatan ringan, yang
        // lumrah pada PDF hasil pemindaian. Argumen --warning-exit-0 pada
        // pemanggil membuat peringatan tetap dianggap berhasil.
        if (! $hasil->successful()) {
            Log::error("qpdf mengembalikan galat saat {$konteks}.", [
                'kode' => $hasil->exitCode(),
                'keluaran' => $hasil->errorOutput() ?: $hasil->output(),
            ]);

            if ($wajibBerhasil) {
                throw new RuntimeException("Gagal {$konteks}. Periksa log aplikasi untuk keterangan qpdf.");
            }

            return null;
        }

        return $hasil;
    }

    private function pastikanBerkasAda(string $jalur): void
    {
        if (! is_file($jalur)) {
            throw new RuntimeException("Berkas PDF tidak ditemukan: {$jalur}");
        }
    }

    private function siapkanFolder(string $jalurTujuan): void
    {
        $folder = \dirname($jalurTujuan);

        if (! is_dir($folder) && ! @mkdir($folder, 0775, true) && ! is_dir($folder)) {
            throw new RuntimeException("Folder tujuan tidak dapat dibuat: {$folder}");
        }
    }
}