<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Bookmark;
use App\Models\DownloadLog;
use App\Models\ReadingProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Halaman baca PDF, penyaluran berkas, dan pencatatan unduhan.
 */
class BacaController extends Controller
{
    /**
     * Menampilkan penampil PDF beserta aturan unduh yang berlaku.
     */
    public function index(Request $request, Book $buku): View
    {
        $user = $request->user();
        abort_unless($buku->bolehDilihatOleh($user), 404);

        return view('katalog.baca', [
            'buku' => $buku,
            'aturan' => $buku->aturanUnduhUntuk($user),
        ]);
    }

    /**
     * Menyalurkan berkas PDF secara langsung dari penyimpanan privat.
     * Berkas tidak pernah memiliki alamat publik, jadi hanya pengguna
     * yang berhak dan sedang masuk yang dapat memuatnya.
     */
    public function berkas(Request $request, Book $buku): StreamedResponse
    {
        abort_unless($buku->bolehDilihatOleh($request->user()), 404);
        abort_unless(Storage::disk('local')->exists($buku->file_path), 404);

        return Storage::disk('local')->response($buku->file_path, $buku->slug.'.pdf', [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$buku->slug.'.pdf"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=0, no-store',
        ]);
    }

    /**
     * Mencatat unduhan yang baru saja dilakukan mahasiswa.
     * Aturan tetap diperiksa ulang di sisi server agar tidak bisa dilewati.
     */
    public function catat(Request $request, Book $buku): JsonResponse
    {
        $user = $request->user();
        abort_unless($buku->bolehDilihatOleh($user), 404);

        $aturan = $buku->aturanUnduhUntuk($user);
        abort_unless($aturan['boleh'], 403, $aturan['alasan']);

        DownloadLog::create([
            'book_id' => $buku->id,
            'user_id' => $user->id,
            'prodi_id' => $user->prodi_id,
            'mode' => $buku->access_mode,
        ]);

        return response()->json(['status' => 'tercatat']);
    }

    /** Mengirim kemajuan membaca dan daftar penanda milik pengguna untuk buku ini. */
    public function dataBaca(Request $permintaan, Book $buku): JsonResponse
    {
        $pengguna = $permintaan->user();

        abort_unless($buku->bolehDilihatOleh($pengguna), 404);

        $progres = ReadingProgress::where('user_id', $pengguna->id)
            ->where('book_id', $buku->id)
            ->first();

        $limit = $this->resolusiLimit($permintaan);
        $penanda = $this->daftarPenanda($pengguna->id, $buku->id, $limit);

        return response()->json([
            'halamanTerakhir' => $progres?->last_page ?? 1,
            'penanda' => $penanda,
            'penanda_total' => count($penanda),
        ]);
    }

    /** Menyimpan halaman terakhir yang dibaca agar bisa dilanjutkan di perangkat lain. */
    public function simpanProgres(Request $permintaan, Book $buku): JsonResponse
    {
        $pengguna = $permintaan->user();

        abort_unless($buku->bolehDilihatOleh($pengguna), 404);

        $data = $permintaan->validate([
            'halaman' => ['required', 'integer', 'min:1', 'max:100000'],
            'total' => ['nullable', 'integer', 'min:1', 'max:100000'],
        ]);

        // updateOrCreate menjaga hanya ada satu baris per pengguna per buku.
        ReadingProgress::updateOrCreate(
            [
                'user_id' => $pengguna->id,
                'book_id' => $buku->id,
            ],
            [
                'last_page' => $data['halaman'],
                'total_pages' => $data['total'] ?? $buku->page_count,
            ],
        );

        return response()->json(['status' => 'tersimpan']);
    }

    /** Menyalakan atau mencabut penanda pada sebuah halaman, lalu mengembalikan daftar terbaru. */
    public function ubahPenanda(Request $permintaan, Book $buku): JsonResponse
    {
        $pengguna = $permintaan->user();

        abort_unless($buku->bolehDilihatOleh($pengguna), 404);

        $data = $permintaan->validate([
            'halaman' => ['required', 'integer', 'min:1', 'max:100000'],
        ]);

        $penanda = Bookmark::where('user_id', $pengguna->id)
            ->where('book_id', $buku->id)
            ->where('page', $data['halaman'])
            ->first();

        // Menekan tombol pada halaman yang sudah ditandai berarti mencabut penandanya.
        if ($penanda) {
            $penanda->delete();
        } else {
            Bookmark::create([
                'user_id' => $pengguna->id,
                'book_id' => $buku->id,
                'page' => $data['halaman'],
            ]);
        }

        $limit = $this->resolusiLimit($permintaan);
        $daftar = $this->daftarPenanda($pengguna->id, $buku->id, $limit);

        return response()->json([
            'penanda' => $daftar,
            'penanda_total' => count($daftar),
        ]);
    }

    /** Daftar nomor halaman yang ditandai, selalu urut dari kecil ke besar, dibatasi $limit item. */
    private function daftarPenanda(int $penggunaId, int $bukuId, int $limit = 100): array
    {
        return Bookmark::where('user_id', $penggunaId)
            ->where('book_id', $bukuId)
            ->orderBy('page')
            ->limit($limit)
            ->pluck('page')
            ->all();
    }

    /** Membaca parameter ?limit= dari request; default 100, maksimum 500. */
    private function resolusiLimit(Request $permintaan): int
    {
        $limit = (int) $permintaan->query('limit', 100);

        return max(1, min($limit, 500));
    }
}
