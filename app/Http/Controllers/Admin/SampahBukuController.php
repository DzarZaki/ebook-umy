<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Book;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Throwable;

/**
 * Tempat sampah buku.
 *
 * Wewenang di berkas ini tidak lagi diputuskan sendiri, melainkan ditanyakan
 * kepada BookPolicy. Pesan penolakan pun datang dari sana, sehingga dosen
 * yang tertolak membaca alasan yang tepat, bukan sekadar 403 tanpa kata.
 */
class SampahBukuController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): View
    {
        $dosen = $request->user();
        $tenggang = max(1, (int) config('ebook.sampah.tenggang_hari', 30));

        $daftarBuku = Book::onlyTrashed()
            ->with(['category', 'prodi', 'pengunggah'])
            ->where(function ($kueri) use ($dosen) {
                $kueri->where('prodi_id', $dosen->prodi_id)
                    ->orWhere(function ($umum) use ($dosen) {
                        $umum->whereNull('prodi_id')
                            ->where('uploaded_by', $dosen->id);
                    });
            })
            ->orderByDesc('deleted_at')
            ->paginate(15)
            ->withQueryString();

        // Tenggat dihitung di sini, bukan di Blade, dan tidak disimpan ke
        // basis data — ia hanya turunan dari deleted_at dan masa tenggang.
        $daftarBuku->getCollection()->each(function (Book $buku) use ($tenggang): void {
            $batas = $buku->deleted_at?->copy()->addDays($tenggang);

            $buku->setAttribute('dilenyapkanPada', $batas);
            $buku->setAttribute(
                'sisaHari',
                $batas ? max(0, (int) ceil(now()->diffInDays($batas, false))) : null
            );
        });

        return view('admin.buku-sampah.index', [
            'daftarBuku' => $daftarBuku,
            'tenggangHari' => $tenggang,
        ]);
    }

    public function pulihkan(Request $request, Book $buku): RedirectResponse
    {
        $this->authorize('restore', $buku);

        // Tombol yang tertekan dua kali, atau dua dosen yang menekan
        // bersamaan, tidak perlu dijawab dengan galat.
        if (! $buku->trashed()) {
            return redirect()
                ->route('admin.buku-sampah.index')
                ->with('status', 'Buku itu sudah dipulihkan sebelumnya.');
        }

        $buku->restore();

        Log::info('SampahBuku: buku dipulihkan.', [
            'buku' => $buku->id,
            'judul' => $buku->title,
            'oleh' => $request->user()?->id,
        ]);

        return redirect()
            ->route('admin.buku.index')
            ->with('status', "Buku \"{$buku->title}\" telah dipulihkan.");
    }

    public function hapusPermanen(Request $request, Book $buku): RedirectResponse
    {
        $this->authorize('forceDelete', $buku);

        // Buku yang masih hidup harus melewati tempat sampah lebih dulu.
        abort_unless($buku->trashed(), 404);

        $judul = $buku->title;

        $berkasGagal = [];

        try {
            /*
             * Baris dulu, berkas belakangan.
             *
             * Bila urutannya dibalik dan forceDelete() gagal di tengah jalan,
             * tempat sampah memuat buku yang berkasnya sudah lenyap — tombol
             * Pulihkan akan menghidupkan buku kosong yang tak bisa dibaca.
             * Dengan urutan ini, kegagalan hanya menyisakan berkas yatim,
             * yang otomatis diburu perintah ebook:bersihkan-buku.
             */
            $buku->forceDelete();
        } catch (Throwable $e) {
            Log::error('SampahBuku: gagal melenyapkan buku.', [
                'buku' => $buku->id,
                'judul' => $judul,
                'oleh' => $request->user()?->id,
                'galat' => $e->getMessage(),
            ]);

            return redirect()
                ->route('admin.buku-sampah.index')
                ->with('error', "Buku \"{$judul}\" gagal dilenyapkan. Coba beberapa saat lagi.");
        }

        foreach ($buku->berkasnya() as [$disk, $jalur]) {
            if (blank($jalur)) {
                continue;
            }

            try {
                Storage::disk($disk)->delete($jalur);
            } catch (Throwable $e) {
                $berkasGagal[] = $jalur;

                Log::warning('SampahBuku: berkas gagal terhapus setelah baris dilenyapkan.', [
                    'disk' => $disk,
                    'jalur' => $jalur,
                    'galat' => $e->getMessage(),
                ]);
            }
        }

        Log::info('SampahBuku: buku dilenyapkan permanen.', [
            'judul' => $judul,
            'oleh' => $request->user()?->id,
            'berkas_tersisa' => $berkasGagal,
        ]);

        $pesan = $berkasGagal === []
            ? "Buku \"{$judul}\" telah dilenyapkan permanen beserta berkasnya."
            : "Buku \"{$judul}\" telah dilenyapkan permanen. Sebagian berkasnya gagal terhapus dan akan disapu pembersih terjadwal.";

        return redirect()
            ->route('admin.buku-sampah.index')
            ->with('status', $pesan);
    }
}
