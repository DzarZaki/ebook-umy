<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\DownloadLog;
use App\Models\User;
use App\Services\BerkasBukuService;
use App\Support\Pdf\Qpdf;
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
 * kesiapan peralatan dipastikan, halaman dipotong sesuai mode buku,
 * stempel identitas ditempelkan, unduhan dicatat, lalu berkas sementara
 * dibersihkan.
 */
class UnduhController extends Controller
{
    public function __construct(
        private readonly BerkasBukuService $berkasBuku,
        private readonly Qpdf $qpdf,
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

        // Tahap ketiga: sanggupkah server ini menyalurkannya dengan benar?
        $this->pastikanPengolahBerkasSiap($buku, $pengguna);

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
     * Menahan unduhan yang tidak dapat disalurkan sebagaimana mestinya.
     *
     * Dulu ketiadaan qpdf hanya menghasilkan satu baris Log::warning di dalam
     * service, lalu berkas asli diteruskan TANPA stempel dengan status 200.
     * Akibatnya buruk justru karena tidak kelihatan: dosen tetap yakin
     * bukunya bertanda identitas, mahasiswa menerima PDF bersih, dan tidak
     * seorang pun tahu sampai berkasnya beredar di luar dan tidak ada jejak
     * yang bisa dilacak. Sekarang kejadian itu berhenti di sini, tercatat
     * sebagai Log::error yang menyebut sebabnya.
     *
     * Diletakkan setelah pemeriksaan wewenang, dan itu disengaja: orang yang
     * memang tidak berhak mengunduh tidak perlu diberi tahu keadaan internal
     * server. Diletakkan sebelum pencatatan unduhan, juga disengaja: statistik
     * tidak boleh memuat unduhan yang sebenarnya tidak pernah terjadi.
     */
    private function pastikanPengolahBerkasSiap(Book $buku, User $pengguna): void
    {
        $perluPotong = $buku->access_mode === Book::AKSES_SEBAGIAN;
        $perluStempel = (bool) $buku->watermark_enabled;

        // Buku utuh tanpa stempel disalurkan apa adanya; qpdf tidak dilibatkan
        // sama sekali, jadi ketiadaannya tidak boleh menghalangi apa pun.
        if (! $perluPotong && ! $perluStempel) {
            return;
        }

        if ($this->qpdf->tersedia()) {
            return;
        }

        $wajib = (bool) config('ebook.qpdf.wajib', true);

        Log::error('qpdf tidak tersedia saat buku diminta untuk diunduh.', [
            'buku_id' => $buku->id,
            'pengguna_id' => $pengguna->id,
            'perlu_potong' => $perluPotong,
            'perlu_stempel' => $perluStempel,
            'binary' => $this->qpdf->binary(),
            'sebab' => $this->qpdf->alasanTidakTersedia(),
            'ditahan' => $wajib || $perluPotong,
            'petunjuk' => 'Jalankan: php artisan ebook:periksa-qpdf',
        ]);

        // Pemotongan halaman tidak bisa ditawar, apa pun setelan kelonggaran:
        // meneruskannya berarti menyerahkan seluruh buku, bukan bagian yang
        // diizinkan dosen. Penolakan ini juga ditegakkan lagi di dalam
        // service — dua lapis, karena kebocorannya tidak bisa dibatalkan.
        if ($perluPotong) {
            abort(503, 'Layanan pengolahan berkas sedang tidak tersedia, sehingga unduhan '
                .'sebagian tidak dapat disiapkan. Silakan coba beberapa saat lagi.');
        }

        // Mode kelonggaran: pengelola sadar-sadar memilih layanan tetap jalan
        // tanpa stempel sambil qpdf dibereskan. Kejadiannya sudah tercatat
        // sebagai galat di atas, jadi tidak lagi lewat tanpa jejak.
        if (! $wajib) {
            return;
        }

        abort(503, 'Buku ini belum dapat diunduh karena layanan penanda berkas sedang '
            .'tidak tersedia. Pengelola sistem sudah dicatatkan pemberitahuannya — '
            .'silakan coba beberapa saat lagi.');
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