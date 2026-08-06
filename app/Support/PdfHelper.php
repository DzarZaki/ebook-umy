<?php

namespace App\Support;

class PdfHelper
{
    /** Menghitung jumlah halaman PDF langsung dari isi berkasnya. */
    public static function hitungHalaman(string $jalur): ?int
    {
        if (! is_readable($jalur)) {
            return null;
        }

        $isi = file_get_contents($jalur);

        if ($isi === false) {
            return null;
        }

        // Cara utama: menghitung objek bertipe /Page (bukan /Pages).
        preg_match_all('/\/Type\s*\/Page[^s]/', $isi, $cocok);
        $jumlah = count($cocok[0]);

        // Cadangan: mengambil nilai /Count terbesar pada pohon halaman.
        if ($jumlah === 0 && preg_match_all('/\/Count\s+(\d+)/', $isi, $cocokCount)) {
            $jumlah = (int) max($cocokCount[1]);
        }

        return $jumlah > 0 ? $jumlah : null;
    }
}
