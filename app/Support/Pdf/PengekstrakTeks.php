<?php

declare(strict_types=1);

namespace App\Support\Pdf;

use Illuminate\Support\Facades\Log;
use Smalot\PdfParser\Parser;
use Throwable;

/**
 * Pengekstrak teks isi berkas PDF untuk indeks pencarian.
 *
 * Memakai pustaka murni PHP (smalot/pdfparser) alih-alih biner tambahan:
 * qpdf sudah wajib terpasang, tetapi menuntut satu program lagi demi
 * fitur pelengkap akan mempersulit pemasangan ulang di server baru.
 *
 * Kegagalan ekstraksi BUKAN kegagalan unggah. PDF hasil pindai tanpa
 * lapisan teks, dokumen rusak, atau halaman aneh hanya membuat bukunya
 * tidak dapat dicari lewat isinya — judul dan penulisnya tetap bekerja.
 * Karena itu setiap kegagalan dicatat lalu dikembalikan sebagai null.
 */
class PengekstrakTeks
{
    /**
     * Batas halaman yang diekstrak. Buku raksasa seribu halaman jarang
     * menambah kualitas pencarian sepadan dengan waktu tunggu unggahnya.
     */
    private const BATAS_HALAMAN = 300;

    /**
     * Batas panjang teks tersimpan (± 400 ribu karakter). Kolom longText
     * sanggup lebih, tetapi LIKE atas teks yang jauh lebih panjang mulai
     * terasa pada koleksi besar.
     */
    private const BATAS_KARAKTER = 400_000;

    /** Ekstrak seluruh teks isi berkas; null bila tidak berhasil. */
    public function ekstrak(string $jalurAbsolut): ?string
    {
        try {
            $parser = new Parser;
            $dokumen = $parser->parseFile($jalurAbsolut);

            $teks = '';
            $nomor = 0;

            foreach ($dokumen->getPages() as $halaman) {
                if ($nomor >= self::BATAS_HALAMAN || strlen($teks) >= self::BATAS_KARAKTER) {
                    break;
                }

                $teks .= $halaman->getText()."\n";
                $nomor++;
            }
        } catch (Throwable $galat) {
            Log::warning('Ekstraksi teks isi gagal.', [
                'berkas' => $jalurAbsolut,
                'pesan' => $galat->getMessage(),
            ]);

            return null;
        }

        // Rapatkan spasi agar pencarian frasa tidak tersandung ganda baris
        // yang tidak disengaja oleh pemisah halaman.
        $teks = trim((string) preg_replace('/\s+/u', ' ', $teks));

        if ($teks === '') {
            return null;
        }

        if (strlen($teks) > self::BATAS_KARAKTER) {
            /*
             * mb_strcut memotong pada batas byte TANPA membelah karakter
             * UTF-8. substr() mentah bisa menyisakan karakter terbelah di
             * ujung, membuat pola /u di bawah gagal mengenali seluruh teks —
             * indeks pencarian pun kosong tanpa jejak.
             */
            $teks = mb_strcut($teks, 0, self::BATAS_KARAKTER, 'UTF-8');

            // Potong pada batas kata supaya istilah terakhir tidak terbelah.
            // Bila perapian gagal, teks terpangkas tetap dipakai apa adanya.
            $rapi = preg_replace('/\s+\S*$/u', '', $teks);
            $teks = ($rapi === null || trim($rapi) === '') ? rtrim($teks) : rtrim($rapi);
        }

        return $teks;
    }
}
