<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Bookmark;
use App\Models\ReadingProgress;
use App\Services\BerkasBukuService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Penampil PDF: halaman baca, penyaluran berkas untuk dibaca, kemajuan
 * membaca, dan penanda halaman.
 *
 * Controller ini sengaja TIDAK menangani unduhan. Membaca dan mengunduh
 * adalah dua wewenang yang berbeda, sehingga dipisahkan ke pintu masing
 * masing; lihat UnduhController.
 */
class BacaController extends Controller
{
    /**
     * Batas halaman untuk buku yang jumlah halamannya belum terbaca.
     *
     * Angka ini bukan aturan, melainkan pagar terakhir agar kolom integer
     * tidak dijejali bilangan raksasa. Buku yang sehat selalu memakai
     * page_count-nya sendiri.
     */
    private const BATAS_HALAMAN_TAK_DIKETAHUI = 100000;

    public function __construct(
        private readonly BerkasBukuService $berkasBuku,
    ) {
    }

    /**
     * Menampilkan penampil PDF beserta aturan unduh yang berlaku.
     */
    public function index(Request $permintaan, Book $buku): View
    {
        $this->pastikanBolehMembaca($buku);

        return view('katalog.baca', [
            'buku' => $buku,
            'aturan' => $buku->aturanUnduhUntuk($permintaan->user()),
        ]);
    }

    /**
     * Menyalurkan berkas PDF untuk dibaca di penampil.
     *
     * Berkas dialirkan langsung dari penyimpanan privat dan tidak pernah
     * memiliki alamat publik, sehingga hanya pengguna yang sedang masuk
     * dan berhak yang dapat memuatnya.
     *
     * Perlu ditegaskan: yang keluar dari sini adalah dokumen asli tanpa
     * potongan halaman. Itu memang wajar untuk membaca — penampil hanya
     * menampilkan, tidak menyerahkan berkas. Penyerahan berkas ke tangan
     * pengguna ditangani UnduhController, yang menegakkan pemotongan
     * halaman dan stempel identitas.
     */
    public function berkas(Request $permintaan, Book $buku): StreamedResponse
    {
        $this->pastikanBolehMembaca($buku);

        try {
            $relatif = $this->berkasBuku->jalurBacaan($buku, $permintaan->user());
        } catch (AuthorizationException|RuntimeException) {
            abort(404);
        }

        return Storage::disk('local')->response($relatif, $buku->slug.'.pdf', [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$buku->slug.'.pdf"',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'no-referrer',
            'Cache-Control' => 'private, max-age=0, no-store',
        ]);
    }

    /** Mengirim kemajuan membaca dan daftar penanda milik pengguna untuk buku ini. */
    public function dataBaca(Request $permintaan, Book $buku): JsonResponse
    {
        $this->pastikanBolehMembaca($buku);

        $pengguna = $permintaan->user();

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
        $this->pastikanBolehMembaca($buku);

        $pengguna = $permintaan->user();
        $batas = $this->batasHalaman($buku);

        $data = $permintaan->validate([
            'halaman' => ['required', 'integer', 'min:1', 'max:'.$batas],
            'total' => ['nullable', 'integer', 'min:1', 'max:'.$batas],
        ], [
            'halaman.max' => "Buku ini hanya memiliki {$batas} halaman.",
            'total.max' => "Buku ini hanya memiliki {$batas} halaman.",
        ]);

        // updateOrCreate menjaga hanya ada satu baris per pengguna per buku.
        ReadingProgress::updateOrCreate(
            [
                'user_id' => $pengguna->id,
                'book_id' => $buku->id,
            ],
            [
                'last_page' => $data['halaman'],
                // Jumlah halaman diambil dari catatan server lebih dulu.
                // Angka kiriman penampil hanya dipakai bila buku ini memang
                // belum pernah terbaca jumlah halamannya.
                'total_pages' => $buku->page_count ?? ($data['total'] ?? null),
            ],
        );

        return response()->json(['status' => 'tersimpan']);
    }

    /** Menyalakan atau mencabut penanda pada sebuah halaman, lalu mengembalikan daftar terbaru. */
    public function ubahPenanda(Request $permintaan, Book $buku): JsonResponse
    {
        $this->pastikanBolehMembaca($buku);

        $pengguna = $permintaan->user();
        $batas = $this->batasHalaman($buku);

        $data = $permintaan->validate([
            'halaman' => ['required', 'integer', 'min:1', 'max:'.$batas],
        ], [
            'halaman.max' => "Buku ini hanya memiliki {$batas} halaman.",
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

    /**
     * Gerbang izin tunggal untuk seluruh method di controller ini.
     *
     * Memakai 404, bukan 403, dengan sengaja: keberadaan buku milik
     * program studi lain sebaiknya tidak terungkap kepada yang tidak
     * berhak membukanya.
     */
    private function pastikanBolehMembaca(Book $buku): void
    {
        abort_if(Gate::denies('baca', $buku), 404);
    }

    /**
     * Nomor halaman tertinggi yang masuk akal untuk buku ini.
     *
     * Buku yang page_count-nya belum terbaca — misalnya PDF yang gagal
     * dihitung qpdf — jatuh ke pagar umum, supaya mahasiswa tidak
     * terkunci dari penanda hanya karena catatan servernya belum lengkap.
     */
    private function batasHalaman(Book $buku): int
    {
        $jumlah = (int) ($buku->page_count ?? 0);

        return $jumlah > 0 ? $jumlah : self::BATAS_HALAMAN_TAK_DIKETAHUI;
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