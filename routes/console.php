<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * Berkas unduhan sementara dibuat setiap kali sebuah buku dipotong atau
 * distempel. deleteFileAfterSend hanya membereskan yang unduhannya selesai
 * normal; yang dibatalkan di tengah jalan atau terputus akan tertinggal.
 * Pembersih ini yang menyapu sisanya.
 */
Schedule::command('ebook:bersihkan-unduhan')
    ->hourly()
    ->withoutOverlapping(10)
    ->appendOutputTo(storage_path('logs/pembersih-unduhan.log'));

/*
 * Buku yang dibuang ke tempat sampah dilenyapkan permanen setelah masa
 * tenggang (config ebook.sampah.tenggang_hari) terlampaui, berikut PDF dan
 * sampulnya. Sekalian menyapu berkas yatim yang tidak dimiliki baris mana pun.
 *
 * Dijalankan dini hari karena melibatkan penghapusan berkas besar, dan
 * withoutOverlapping diberi 30 menit sebab koleksi yang menumpuk bisa
 * memakan waktu jauh lebih lama daripada pembersih unduhan.
 *
 * CATATAN: --terapkan membuatnya benar-benar menghapus. Kalau Anda ingin
 * mengamati dulu selama beberapa hari, hapus baris parameter itu; perintah
 * akan menulis laporan ke log tanpa menyentuh apa pun.
 */
Schedule::command('ebook:bersihkan-buku', ['--terapkan'])
    ->dailyAt('03:15')
    ->timezone('Asia/Jakarta')
    ->withoutOverlapping(30)
    ->appendOutputTo(storage_path('logs/pembersih-buku.log'));