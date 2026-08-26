<?php

namespace App\Services;

use App\Models\Book;
use App\Models\User;
use App\Notifications\BukuBaru;
use Illuminate\Support\Facades\Log;

/**
 * Mengabarkan buku yang baru diterbitkan kepada para pelanggannya.
 *
 * Tiga syarat wajib bagi penerima, dan ketiganya diperiksa DI SINI
 * (bukan sekadar kolom langganan):
 *
 *  1. opt-in  — notifikasi_buku_baru bernilai true
 *  2. aktif   — akun yang dinonaktifkan tidak diganggu
 *  3. terverifikasi — surel yang belum dibuktikan kepemilikannya
 *     tidak layak menerima apa pun
 *
 * Cakupan mengikuti aturan rak: buku prodi hanya ke prodi itu sendiri,
 * buku umum ke seluruh pelanggan lintas prodi.
 *
 * Pengiriman berantre per 200 akun lewat chunkById agar memori tetap
 * datar sekalipun langganannya ribuan.
 */
class PemberiTahuBukuBaru
{
    public function kirim(Book $buku): void
    {
        $kueri = User::query()
            ->where('role', User::ROLE_MAHASISWA)
            ->where('is_active', true)
            ->whereNotNull('email_verified_at')
            ->where('notifikasi_buku_baru', true);

        if (! $buku->isUmum()) {
            $kueri->where('prodi_id', $buku->prodi_id);
        }

        $total = (clone $kueri)->count();

        if ($total === 0) {
            return;
        }

        $kueri->select(['id'])
            ->chunkById(200, function ($batch) use ($buku): void {
                $batch->each(fn (User $pengguna) => $pengguna->notify(new BukuBaru($buku)));
            });

        Log::info('Pemberitahuan buku baru dikirim.', [
            'buku_id' => $buku->getKey(),
            'penerima' => $total,
        ]);
    }
}
