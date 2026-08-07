<?php

namespace App\Support;

class PdfHelper
{
    private const CHUNK = 65536; // 64 KB

    /** Menghitung jumlah halaman PDF tanpa memuat seluruh berkas ke memori. */
    public static function hitungHalaman(string $jalur): ?int
    {
        if (! is_readable($jalur)) {
            return null;
        }

        try {
            return self::dariEkor($jalur) ?? self::dariChunk($jalur);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Baca 64 KB terakhir dan ambil nilai /Count terbesar dari cross-reference.
     * Ini cukup untuk hampir semua PDF yang menyimpan trailer di akhir berkas.
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
     * Fallback: baca seluruh berkas per-chunk dan hitung kemunculan /Type /Page.
     * Lebih lambat tapi tetap hemat memori dibanding file_get_contents.
     */
    private static function dariChunk(string $jalur): ?int
    {
        $fp = fopen($jalur, 'rb');

        if ($fp === false) {
            return null;
        }

        $jumlah = 0;
        // Simpan sisa chunk sebelumnya untuk menangani pola yang terpotong di batas chunk.
        $sisa = '';

        try {
            while (! feof($fp)) {
                $potongan = fread($fp, self::CHUNK);

                if ($potongan === false) {
                    break;
                }

                $gabung = $sisa.$potongan;
                preg_match_all('/\/Type\s*\/Page[^s]/', $gabung, $cocok);
                $jumlah += count($cocok[0]);
                // Simpan 20 byte terakhir agar pola di batas chunk tidak terlewat.
                $sisa = substr($potongan, -20);
            }
        } finally {
            fclose($fp);
        }

        return $jumlah > 0 ? $jumlah : null;
    }
}
