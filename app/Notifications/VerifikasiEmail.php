<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Surel verifikasi berbahasa aplikasi ini.
 *
 * Bawaan kerangka menulis dalam bahasa Inggris dan tidak punya berkas
 * terjemahan Indonesia, jadi pesannya ditulis ulang di sini. Mekanisme
 * tautannya — bertanda tangan, kedaluwarsa sesuai auth.verification —
 * tetap warisan bawaan agar tidak ada dua aturan umur tautan.
 */
class VerifikasiEmail extends VerifyEmail
{
    protected function buildMailMessage($url): MailMessage
    {
        return (new MailMessage)
            ->subject('Verifikasi Alamat Email')
            ->greeting('Halo!')
            ->line('Terima kasih sudah mendaftar. Klik tombol di bawah untuk memverifikasi alamat email Anda.')
            ->action('Verifikasi Alamat Email', $url)
            ->line('Bila Anda tidak merasa mendaftar, abaikan surel ini.');
    }
}
