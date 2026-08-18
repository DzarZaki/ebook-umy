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

        /*
         | Haruskah ketiadaan qpdf menghentikan unduhan?
         |
         | true (bawaan) — unduhan yang membutuhkan qpdf ditolak dengan 503.
         |   Ini pilihan yang benar untuk keadaan normal. Menyalurkan buku
         |   tanpa stempel identitas berarti dosen mengira bukunya bertanda
         |   padahal tidak; kepercayaan yang salah lebih merugikan daripada
         |   unduhan yang jelas-jelas gagal dan karenanya segera dilaporkan.
         |
         | false — unduhan diteruskan tanpa stempel, kejadiannya dicatat
         |   sebagai Log::error. Hanya untuk masa darurat: qpdf baru rusak
         |   di server dan Anda memilih layanan tetap jalan sambil dibereskan.
         |   Mode ini tidak melonggarkan pemotongan halaman — buku "unduh
         |   sebagian" tetap ditolak, karena meneruskannya berarti menyerahkan
         |   seluruh isi buku yang seharusnya tertutup.
         |
         | Periksa keadaan sebenarnya dengan: php artisan ebook:periksa-qpdf
         */
        'wajib' => (bool) env('EBOOK_QPDF_WAJIB', true),
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
    | Berbeda dengan unduhan, aliran baca tidak boleh gagal hanya karena
    | pengolahan berkas bermasalah — mahasiswa berhak membaca. Karena itu
    | stempel di sini bersifat gagal-terbuka: bila qpdf tidak tersedia,
    | berkas asli tetap disalurkan dan kejadiannya dicatat di log.
    |
    | Pengecualiannya rentang halaman: bila penegakannya dinyalakan tetapi
    | gagal dijalankan, permintaan DITOLAK. Menyalurkan berkas utuh dalam
    | keadaan itu sama dengan membatalkan aturan dosen tanpa sepengetahuannya.
    */
    'baca' => [
        // Batas permintaan berkas untuk dibaca di penampil, per menit.
        'maks_per_menit' => (int) env('EBOOK_READ_PER_MINUTE', 30),

        /*
         * Stempel identitas pada aliran baca untuk buku yang BUKAN
         * "unduh penuh". Berbeda dari kolom watermark_enabled milik buku,
         * yang mengatur stempel pada berkas UNDUHAN.
         *
         * Buku "unduh penuh" tidak distempel di jalur baca: pembacanya
         * memang boleh mengambil seluruh berkas lewat pintu unduh.
         */
        'stempel' => (bool) env('EBOOK_BACA_STEMPEL', true),

        /*
         * Haruskah penampil ikut dibatasi pada rentang halaman unduhan?
         *
         * Default mati, karena "unduh sebagian" pada umumnya berarti
         * "baca semuanya di sini, ambil sebagiannya saja". Nyalakan bila
         * di kampus Anda rentang itu dimaksudkan membatasi bacaan juga.
         */
        'ikuti_rentang' => (bool) env('EBOOK_BACA_IKUTI_RENTANG', false),

        // Folder cache hasil stempel bacaan, pada disk ebook.unduh.disk.
        'folder' => 'bacaan-sementara',

        /*
         * Umur cache bacaan (menit). Lebih panjang daripada TTL unduhan
         * karena berkas ini dipakai berulang selama satu sesi membaca:
         * pdf.js meminta berkas yang sama setiap kali penampil dibuka.
         */
        'ttl_menit' => (int) env('EBOOK_BACA_TTL', 120),
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