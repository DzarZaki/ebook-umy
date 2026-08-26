<?php

namespace App\Notifications;

use App\Models\Book;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Surel pemberitahuan buku baru yang baru saja diterbitkan.
 *
 * Dikirim lewat antrean: satu unggahan bisa menyasar ratusan pelanggan,
 * dan HTTP request tidak boleh menunggu ratusan SMTP selesai.
 */
class BukuBaru extends Notification implements ShouldQueue
{
    use Queueable;

    /** Jeda ulang otomatis ketika server surat sedang tersendat. */
    public int $tries = 3;

    public function __construct(public readonly Book $buku) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Buku baru: '.$this->buku->title)
            ->greeting('Halo, '.$notifiable->name.'!')
            ->line('Dosen pengampu baru saja menerbitkan sebuah buku di rak Anda.')
            ->line(
                '“'.$this->buku->title.'”'
                .($this->buku->author ? ' — '.$this->buku->author : '')
            )
            ->action('Buka sekarang', route('katalog.show', $this->buku))
            ->line('Anda menerima surel ini karena mengaktifkan pemberitahuan buku baru pada halaman profil. Bila tidak berkenan, matikan saja di sana.');
    }
}
