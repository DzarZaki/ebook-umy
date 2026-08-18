<?php

declare(strict_types=1);

namespace App\Support\Pdf;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Membuat berkas PDF stempel satu halaman berisi identitas pengunduh.
 *
 * Berkas hasil kelas ini tidak untuk dikirim ke pengguna. Ia hanyalah
 * lembar transparan yang nanti ditumpangkan qpdf ke setiap halaman buku,
 * sehingga jejak identitas menyatu ke dalam berkas PDF dan tetap ada
 * walau berkasnya disimpan, dikirim ulang, atau dicetak.
 *
 * Catatan tentang FPDF: kelas FPDF berada di namespace global, karena itu
 * ditulis `new \FPDF(...)` dengan garis miring di depan.
 */
class PembuatStempel
{
    /** @param  array<string, mixed>  $pengaturan */
    public function __construct(private readonly array $pengaturan)
    {
    }

    /** Membuat instance memakai nilai dari config/ebook.php. */
    public static function dariKonfigurasi(): self
    {
        return new self((array) config('ebook.watermark', []));
    }

        /**
     * Menyusun kalimat stempel untuk seorang pengguna.
     *
     * Dipisah sebagai method sendiri agar susunan kalimatnya bisa diuji
     * tanpa perlu benar-benar membuat berkas PDF.
     *
     * $awalan dapat diganti karena satu kelas ini melayani dua peristiwa
     * yang berbeda: berkas yang diunduh dan berkas yang dibaca di penampil.
     * Stempel yang berbunyi "Diunduh oleh" pada berkas yang tidak pernah
     * diunduh hanya akan menyesatkan penelusuran kelak.
     */
    public function teksUntuk(User $pengguna, ?Carbon $waktu = null, string $awalan = 'Diunduh oleh'): string
    {
        return sprintf(
            '%s %s (%s) pada %s',
            $awalan,
            (string) $pengguna->name,
            (string) $pengguna->email,
            ($waktu ?? Carbon::now())->format('d/m/Y'),
        );
    }

        /**
     * Membuat berkas stempel untuk seorang pengguna.
     *
     * @return string Jalur absolut berkas stempel yang dihasilkan.
     */
    public function untukPengguna(
        User $pengguna,
        string $jalurTujuan,
        ?Carbon $waktu = null,
        string $awalan = 'Diunduh oleh',
    ): string {
        return $this->tulis($this->teksUntuk($pengguna, $waktu, $awalan), $jalurTujuan);
    }

    /**
     * Menggambar teks stempel ke kaki halaman sebuah PDF baru.
     *
     * @param  string  $jalurTujuan  Jalur absolut pada filesystem.
     * @return string Jalur berkas yang dihasilkan.
     */
    public function tulis(string $teks, string $jalurTujuan): string
    {
        if (! class_exists(\FPDF::class)) {
            throw new RuntimeException(
                'Pustaka FPDF belum terpasang. Jalankan: composer require setasign/fpdf'
            );
        }

        $lebar = $this->angka('lebar_halaman', 210.0);
        $tinggi = $this->angka('tinggi_halaman', 297.0);
        $jarakKiri = $this->angka('jarak_kiri', 12.0);
        $jarakBawah = $this->angka('jarak_bawah', 7.0);
        $ukuranFont = $this->angka('ukuran_font', 7.5);
        $font = (string) ($this->pengaturan['font'] ?? 'Helvetica');

        $this->siapkanFolder($jalurTujuan);

        $teksAman = $this->amankan($teks);

        $pdf = new \FPDF('P', 'mm', [$lebar, $tinggi]);

        // Margin nol dan pemutus halaman dimatikan: halaman ini bukan
        // dokumen biasa, melainkan lembar tempel yang posisinya harus persis.
        $pdf->SetMargins(0.0, 0.0, 0.0);
        $pdf->SetAutoPageBreak(false);
        $pdf->AddPage();

        $pdf->SetFont($font, '', $ukuranFont);

        // Bila nama atau email panjang, kecilkan huruf secukupnya supaya
        // teks tidak terpotong di tepi halaman.
        $lebarTersedia = max(20.0, $lebar - ($jarakKiri * 2));

        while ($ukuranFont > 5.0 && $pdf->GetStringWidth($teksAman) > $lebarTersedia) {
            $ukuranFont -= 0.5;
            $pdf->SetFont($font, '', $ukuranFont);
        }

        [$merah, $hijau, $biru] = $this->warna();
        $pdf->SetTextColor($merah, $hijau, $biru);

        $pdf->SetXY($jarakKiri, $tinggi - $jarakBawah);
        $pdf->Cell(0.0, 4.0, $teksAman, 0, 0, 'L');

        // 'F' berarti tulis ke berkas, bukan kirim ke browser.
        $pdf->Output('F', $jalurTujuan);

        if (! is_file($jalurTujuan)) {
            throw new RuntimeException("Berkas stempel gagal ditulis: {$jalurTujuan}");
        }

        return $jalurTujuan;
    }

    /**
     * Menyesuaikan teks dengan keterbatasan font bawaan FPDF.
     *
     * Font inti PDF memakai pengkodean Windows-1252, bukan UTF-8. Tanpa
     * penyesuaian ini, nama ber-aksen atau tanda kutip melengkung akan
     * tampil sebagai karakter acak pada stempel.
     */
        /**
     * Menyesuaikan teks dengan keterbatasan font bawaan FPDF.
     *
     * Font inti PDF memakai pengkodean Windows-1252, bukan UTF-8, sehingga
     * teks harus dipetakan ke sana sebelum digambar.
     *
     * `//TRANSLIT` sengaja TIDAK dipakai: perilakunya ditentukan pustaka
     * iconv sistem operasi, sehingga stempel yang sama bisa berbunyi "李明"
     * → "??" di satu server dan menjadi kosong di server lain. Urutan di
     * bawah ini memberi hasil yang sama di mana pun:
     *
     *   1. Konversi ketat. Nama ber-aksen Latin (é, ü, ñ) lolos utuh, karena
     *      Windows-1252 memang memuatnya — sebelumnya justru diubah paksa
     *      menjadi e, u, n oleh //TRANSLIT.
     *   2. Bila ada huruf di luar Windows-1252, seluruh teks dialihkan ke
     *      ASCII memakai tabel transliterasi Laravel (deterministik).
     *   3. Bila masih gagal, sisakan hanya ASCII yang dapat dicetak.
     *
     * Bersifat public agar susunan hurufnya dapat diuji tanpa membuat berkas
     * PDF, sama seperti teksUntuk().
     */
    public function amankan(string $teks): string
    {
        $teks = trim((string) preg_replace('/\s+/u', ' ', $teks));

        if ($teks === '') {
            return '';
        }

        $hasil = @iconv('UTF-8', 'Windows-1252', $teks);

        if (is_string($hasil)) {
            return $hasil;
        }

        $alih = Str::ascii($teks);

        $hasil = @iconv('UTF-8', 'Windows-1252', $alih);

        if (! is_string($hasil)) {
            $hasil = (string) preg_replace('/[^\x20-\x7E]/', '', $alih);
        }

        /*
         * Dicatat supaya kegagalan penelusuran tidak berlangsung diam-diam:
         * bila nama seorang mahasiswa tidak dapat dicetak, alamat surelnya
         * yang tetap utuh adalah penanda yang tersisa, dan pengelola perlu
         * tahu bahwa hal itu terjadi.
         *
         * Nama dan surel sengaja TIDAK dimasukkan ke log — panjang teks sudah
         * cukup untuk menandai peristiwanya tanpa menumpuk data pribadi di
         * storage/logs. Kedua angka ini bersifat indikatif, bukan setara
         * karakter demi karakter.
         */
        Log::warning('Stempel: sebagian huruf identitas tidak dapat dicetak oleh font inti PDF.', [
            'panjang_asal' => mb_strlen($teks),
            'panjang_hasil' => strlen($hasil),
            'petunjuk' => 'Huruf non-Latin dialihkan ke ASCII; alamat surel tetap utuh sebagai penanda.',
        ]);

        return $hasil;
    }

    /** @return array{int, int, int} */
    private function warna(): array
    {
        $warna = $this->pengaturan['warna'] ?? [110, 110, 110];

        if (! is_array($warna) || count($warna) < 3) {
            $warna = [110, 110, 110];
        }

        return [
            $this->kanal($warna[0]),
            $this->kanal($warna[1]),
            $this->kanal($warna[2]),
        ];
    }

    private function kanal(mixed $nilai): int
    {
        return max(0, min(255, (int) $nilai));
    }

    private function angka(string $kunci, float $bawaan): float
    {
        $nilai = $this->pengaturan[$kunci] ?? null;

        return is_numeric($nilai) ? (float) $nilai : $bawaan;
    }

    private function siapkanFolder(string $jalurTujuan): void
    {
        $folder = \dirname($jalurTujuan);

        if (! is_dir($folder) && ! @mkdir($folder, 0775, true) && ! is_dir($folder)) {
            throw new RuntimeException("Folder tujuan tidak dapat dibuat: {$folder}");
        }
    }
}