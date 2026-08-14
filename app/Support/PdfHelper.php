<?php

namespace App\Support;

use App\Support\Pdf\Qpdf;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Penghitung jumlah halaman PDF.
 *
 * qpdf membaca struktur PDF yang sebenarnya, jadi ia yang dipercaya lebih
 * dulu. Heuristik lama tetap dipertahankan sebagai cadangan untuk lingkungan
 * tanpa qpdf — tetapi kini setiap kali cadangan itu terpakai atau gagal,
 * kejadiannya dicatat, tidak lagi lewat tanpa suara.
 */
class PdfHelper
{
    private const CHUNK = 65536; // 64 KB

    /** Panjang sisa potongan yang disimpan untuk menjembatani batas chunk. */
    private const TUMPANG = 20;

    /** Menghitung jumlah halaman PDF tanpa memuat seluruh berkas ke memori. */
    public static function hitungHalaman(string $jalur): ?int
    {
        if ($jalur === '' || ! is_readable($jalur)) {
            Log::warning('PdfHelper: berkas tidak dapat dibaca.', ['jalur' => $jalur]);

            return null;
        }

        $pasti = self::dariQpdf($jalur);

        if ($pasti !== null) {
            return $pasti;
        }

        try {
            $tebakan = self::dariEkor($jalur) ?? self::dariChunk($jalur);
        } catch (Throwable $galat) {
            Log::warning('PdfHelper: penghitungan cadangan melempar galat.', [
                'jalur' => $jalur,
                'galat' => $galat->getMessage(),
            ]);

            return null;
        }

        if ($tebakan === null) {
            // Kemungkinan besar PDF dengan object stream terkompresi:
            // strukturnya tidak terlihat sebagai teks biasa.
            Log::warning('PdfHelper: jumlah halaman tidak dapat ditentukan.', ['jalur' => $jalur]);
        }

        return $tebakan;
    }

    /**
     * Angka dari qpdf — hasil pembacaan struktur PDF, bukan pencocokan pola.
     */
    private static function dariQpdf(string $jalur): ?int
    {
        try {
            $qpdf = app(Qpdf::class);

            if (! $qpdf->tersedia()) {
                Log::warning('PdfHelper: qpdf tidak tersedia, beralih ke penghitungan cadangan.', [
                    'jalur' => $jalur,
                ]);

                return null;
            }

            $jumlah = $qpdf->jumlahHalaman($jalur);

            return $jumlah > 0 ? $jumlah : null;
        } catch (Throwable $galat) {
            Log::warning('PdfHelper: qpdf gagal menghitung halaman.', [
                'jalur' => $jalur,
                'galat' => $galat->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Baca 64 KB terakhir dan ambil nilai /Count terbesar dari cross-reference.
     *
     * Perlu diingat: /Count juga dipakai pohon bookmark, sehingga angka ini
     * bisa kelebihan hitung. Karena itu ia hanya dipakai bila qpdf tidak ada.
     */
    private static function dariEkor(string $jalur): ?int
    {
        $ukuran = filesize($jalur);

        if ($ukuran === false || $ukuran === 0) {
            return null;
        }

        $fp = fopen($jalur, 'rb');

        if ($fp === false) {
            return null;
        }

        try {
            $offset = max(0, $ukuran - self::CHUNK);
            fseek($fp, $offset);
            $ekor = fread($fp, self::CHUNK);
        } finally {
            fclose($fp);
        }

        if ($ekor === false || $ekor === '') {
            return null;
        }

        if (preg_match_all('/\/Count\s+(\d+)/', $ekor, $cocok)) {
            $jumlah = (int) max($cocok[1]);

            return $jumlah > 0 ? $jumlah : null;
        }

        return null;
    }

    /**
     * Cadangan terakhir: pindai seluruh berkas per potongan dan hitung
     * kemunculan /Type /Page.
     *
     * Sisa 20 byte dari potongan sebelumnya disatukan agar pola yang terbelah
     * di batas potongan tetap terbaca. Konsekuensinya, wilayah tumpang-tindih
     * itu terpindai dua kali — jadi pola yang seluruhnya berada di dalamnya
     * harus dilewati, karena sudah dihitung pada putaran sebelumnya.
     */
    private static function dariChunk(string $jalur): ?int
    {
        $fp = fopen($jalur, 'rb');

        if ($fp === false) {
            return null;
        }

        $jumlah = 0;
        $sisa = '';

        try {
            while (! feof($fp)) {
                $potongan = fread($fp, self::CHUNK);

                if ($potongan === false) {
                    break;
                }

                $gabung = $sisa.$potongan;
                $panjangSisa = strlen($sisa);

                if (preg_match_all('/\/Type\s*\/Page[^s]/', $gabung, $cocok, PREG_OFFSET_CAPTURE)) {
                    foreach ($cocok[0] as [$teks, $posisi]) {
                        // Berakhir sebelum batas sisa berarti pola itu utuh di
                        // dalam potongan sebelumnya, dan sudah ikut dihitung.
                        if ($posisi + strlen($teks) <= $panjangSisa) {
                            continue;
                        }

                        $jumlah++;
                    }
                }

                $sisa = substr($gabung, -self::TUMPANG);
            }
        } finally {
            fclose($fp);
        }

        return $jumlah > 0 ? $jumlah : null;
    }
}