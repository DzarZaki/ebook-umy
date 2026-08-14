<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Pengaturan E-Book UMY
|--------------------------------------------------------------------------
| Seluruh angka dan jalur yang berkaitan dengan penyaluran berkas buku
| dikumpulkan di sini supaya tidak tersebar sebagai angka ajaib di
| controller maupun service.
*/

return [

    /*
    |----------------------------------------------------------------------
    | qpdf
    |----------------------------------------------------------------------
    | qpdf dipakai untuk dua hal: memotong rentang halaman ketika buku
    | bermode "unduh sebagian", dan menempelkan stempel watermark ke
    | setiap halaman. Berbeda dengan pustaka PHP murni, qpdf sanggup
    | membaca PDF versi 1.5 ke atas yang memakai object stream.
    */
    'qpdf' => [
        'binary' => env('QPDF_BINARY', 'qpdf'),

        // Batas waktu setiap pemanggilan qpdf, dalam detik.
        'timeout' => (int) env('QPDF_TIMEOUT', 60),
    ],

    /*
    |----------------------------------------------------------------------
    | Berkas unduhan
    |----------------------------------------------------------------------
    | Berkas hasil potong/stempel ditulis ke folder sementara, dikirim ke
    | pengguna, lalu dibersihkan. Folder ini WAJIB berada di disk privat
    | supaya hasil olahan tidak pernah punya alamat publik.
    */
    'unduh' => [
        'disk' => env('EBOOK_TEMP_DISK', 'local'),
        'folder' => 'unduhan-sementara',

        // Umur berkas sementara sebelum layak dibersihkan (menit).
        'ttl_menit' => (int) env('EBOOK_TEMP_TTL', 30),

        // Batas unduhan per pengguna per jam. Menahan pengambilan massal.
        'maks_per_jam' => (int) env('EBOOK_DOWNLOAD_PER_HOUR', 20),
    ],

    /*
    |----------------------------------------------------------------------
    | Streaming baca
    |----------------------------------------------------------------------
    | Batas permintaan berkas untuk dibaca di penampil, per menit.
    */
    'baca' => [
        'maks_per_menit' => (int) env('EBOOK_READ_PER_MINUTE', 30),
    ],

    /*
    |----------------------------------------------------------------------
    | Tempat sampah
    |----------------------------------------------------------------------
    | Buku yang dihapus tidak langsung lenyap. Barisnya ditandai terbuang
    | dan berkasnya tetap tersimpan selama masa tenggang di bawah ini,
    | supaya penghapusan yang keliru masih bisa dibatalkan.
    |
    | Setelah tenggang lewat, perintah ebook:bersihkan-buku melenyapkannya
    | secara permanen. Beri angka yang cukup panjang untuk menutupi masa
    | libur; kesalahan hapus jarang disadari pada hari yang sama.
    */
    'sampah' => [
        'tenggang_hari' => (int) env('EBOOK_TRASH_DAYS', 30),
    ],

    /*
    |----------------------------------------------------------------------
    | Watermark
    |----------------------------------------------------------------------
    | Stempel ditulis pada kaki halaman. Ukuran dalam milimeter agar
    | cocok dengan satuan bawaan FPDF.
    */
    'watermark' => [
        'font' => 'Helvetica',
        'ukuran_font' => 7.5,

        // Warna teks stempel dalam RGB.
        'warna' => [110, 110, 110],

        // Jarak teks dari tepi bawah dan tepi kiri halaman (mm).
        'jarak_bawah' => 7.0,
        'jarak_kiri' => 12.0,

        // Ukuran halaman stempel (mm). A4 tegak sudah mencakup mayoritas
        // buku; qpdf akan menyelaraskan stempel ke tiap halaman.
        'lebar_halaman' => 210.0,
        'tinggi_halaman' => 297.0,
    ],

];