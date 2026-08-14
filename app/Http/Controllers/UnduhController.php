<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\DownloadLog;
use App\Models\User;
use App\Services\BerkasBukuService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

/**
 * Gerbang unduhan berkas buku.
 *
 * Satu-satunya pintu yang menyerahkan berkas ke tangan pengguna. Karena
 * itu seluruh penegakan aturan berkumpul di sini: wewenang diperiksa,
 * halaman dipotong sesuai mode buku, stempel identitas ditempelkan,
 * unduhan dicatat, lalu berkas sementara dibersihkan.
 */
class UnduhController extends Controller
{
    public function __construct(
        private readonly BerkasBukuService $berkasBuku,
    ) {
    }

    public function __invoke(Request $permintaan, Book $buku): BinaryFileResponse
    {
        // Diperiksa dua tahap, dan urutannya bukan kebetulan.
        //
        // Tahap pertama: bolehkah orang ini tahu buku ini ada? Bila tidak,
        // jawabannya 404 — sama seperti di halaman baca — supaya keberadaan
        // buku milik program studi lain tidak terungkap.
        abort_if(Gate::denies('baca', $buku), 404);

        // Tahap kedua: bolehkah ia mengunduhnya? Di sini 403 justru tepat,
        // karena pengguna memang berhak tahu alasan penolakannya, misalnya
        // "unduhan dinonaktifkan oleh program studi".
        $periksa = Gate::inspect('unduh', $buku);

        if ($periksa->denied()) {
            abort(403, $periksa->message() ?: 'Buku ini tidak tersedia untuk diunduh.');
        }

        $pengguna = $permintaan->user();
        abort_unless($pengguna instanceof User, 403);

        try {
            $berkas = $this->berkasBuku->siapkanUnduhan($buku, $pengguna);
        } catch (AuthorizationException $galat) {
            // Pemeriksaan berlapis: service menolak berdasarkan aturan model,
            // walau policy tadi meloloskan. Yang lebih ketat yang menang.
            abort(403, $galat->getMessage());
        } catch (RuntimeException $galat) {
            Log::error('Unduhan buku gagal disiapkan.', [
                'buku_id' => $buku->id,
                'pengguna_id' => $pengguna->id,
                'pesan' => $galat->getMessage(),
            ]);

            // 503, bukan 500: ini gangguan sementara pada pengolahan berkas,
            // bukan permintaan yang salah dari pengguna.
            abort(503, $galat->getMessage());
        }

        $this->catatUnduhan($buku, $pengguna);

        return response()
            ->download($berkas['jalur'], $berkas['namaBerkas'], [
                'Content-Type' => 'application/pdf',
                'X-Content-Type-Options' => 'nosniff',
                'Referrer-Policy' => 'no-referrer',
                'Cache-Control' => 'private, max-age=0, no-store',
            ])
            // PENTING. Bendera ini hanya boleh menyala untuk berkas hasil
            // olahan di folder sementara. Ketika buku disalurkan apa adanya,
            // jalurnya menunjuk ke berkas induk di penyimpanan — menghapusnya
            // setelah terkirim berarti melenyapkan buku itu dari sistem.
            // Karena itu nilainya diambil dari service, tidak ditulis tetap.
            ->deleteFileAfterSend($berkas['sementara']);
    }

    /**
     * Mencatat unduhan untuk keperluan statistik dan audit.
     *
     * Kegagalan pencatatan tidak dijadikan alasan membatalkan unduhan yang
     * sudah sah, tetapi tetap ditulis ke log agar tidak lewat tanpa jejak.
     */
    private function catatUnduhan(Book $buku, User $pengguna): void
    {
        try {
            DownloadLog::create([
                'book_id' => $buku->id,
                'user_id' => $pengguna->id,
                // Pengelola tidak terikat program studi, sehingga unduhannya
                // dicatat atas program studi pemilik buku agar statistik per
                // prodi tetap utuh.
                'prodi_id' => $pengguna->prodi_id ?? $buku->prodi_id,
                'mode' => $buku->access_mode,
            ]);
        } catch (Throwable $galat) {
            Log::error('Gagal mencatat unduhan buku.', [
                'buku_id' => $buku->id,
                'pengguna_id' => $pengguna->id,
                'pesan' => $galat->getMessage(),
            ]);
        }
    }
}