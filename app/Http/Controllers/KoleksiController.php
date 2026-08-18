<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Bookmark;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Koleksi Saya — halaman pribadi mahasiswa.
 *
 * Dua tab dengan dua sumber data yang sengaja dipisah:
 *
 *  - Tersimpan: buku yang SENGAJA disimpan (tabel book_saves)
 *  - Penanda: halaman yang ditandai di dalam buku (tabel bookmarks)
 *
 * Keduanya tidak boleh saling memengaruhi. Mencabut penanda halaman
 * tidak mengeluarkan buku dari koleksi, dan melepas buku dari koleksi
 * tidak menghapus penanda halamannya.
 */
class KoleksiController extends Controller
{
    /** Batas jumlah buku berpenanda yang ditampilkan sekaligus. */
    private const BATAS_BUKU_PENANDA = 50;

    public function index(Request $permintaan): View
    {
        $pengguna = $permintaan->user();

        // Buku tersimpan. Penyaringan terbit + terlihatOleh tetap dipasang
        // karena isi koleksi bisa menjadi tidak layak dilihat setelah
        // disimpan: dosen dapat menarik buku dari peredaran, dan mahasiswa
        // dapat berpindah prodi. Buku di Tempat Sampah hilang sendiri
        // lewat SoftDeletes.
        $tersimpan = $pengguna->bukuTersimpan()
            ->terbit()
            ->terlihatOleh($pengguna->prodi_id)
            ->with(['category', 'prodi'])
            ->paginate(12)
            ->withQueryString();

        return view('koleksi.index', [
            'tab' => $permintaan->query('tab') === 'penanda' ? 'penanda' : 'tersimpan',
            'tersimpan' => $tersimpan,
            'bukuBerpenanda' => $this->bukuBerpenanda($pengguna->id, $pengguna->prodi_id, $pengguna),
        ]);
    }

    /** Menyimpan buku ke koleksi. */
    public function simpan(Request $permintaan, Book $buku): RedirectResponse
    {
        $this->pastikanBolehMelihat($buku);

        // syncWithoutDetaching, bukan attach: klik kedua pada tombol yang
        // sama harus berarti "sudah tersimpan", bukan galat 500. Aturan
        // unik di basis data tetap berjaga di belakangnya.
        $permintaan->user()->bukuTersimpan()->syncWithoutDetaching([$buku->getKey()]);

        return back()->with('status', 'Buku disimpan ke Koleksi Saya.');
    }

    /** Melepas buku dari koleksi. */
    public function lepas(Request $permintaan, Book $buku): RedirectResponse
    {
        $this->pastikanBolehMelihat($buku);

        $permintaan->user()->bukuTersimpan()->detach($buku->getKey());

        return back()->with('status', 'Buku dikeluarkan dari Koleksi Saya.');
    }

    /**
     * Buku yang punya penanda halaman, beserta daftar nomor halamannya.
     *
     * Dikerjakan dengan dua kueri, bukan satu kueri per buku: seluruh
     * penanda diambil sekali lalu dikelompokkan di dalam ingatan.
     *
     * @return \Illuminate\Support\Collection<int, array{buku: Book, halaman: array<int, int>}>
     */
    private function bukuBerpenanda(int $penggunaId, ?int $prodiId, ?\App\Models\User $pengguna)
    {
        $perBuku = Bookmark::query()
            ->where('user_id', $penggunaId)
            ->orderBy('page')
            ->get(['book_id', 'page'])
            ->groupBy('book_id');

        if ($perBuku->isEmpty()) {
            return collect();
        }

        return Book::query()
            ->whereIn('id', $perBuku->keys()->take(self::BATAS_BUKU_PENANDA))
            ->terbit()
            ->terlihatOleh($prodiId)
            ->denganStatusSimpan($pengguna)
            ->with(['category', 'prodi'])
            ->orderBy('title')
            ->get()
            ->map(fn (Book $buku) => [
                'buku' => $buku,
                'halaman' => $perBuku[$buku->getKey()]->pluck('page')->all(),
            ]);
    }

    /**
     * Buku yang tidak layak dilihat pengguna ini tidak boleh disimpan
     * maupun dilepas. Memakai 404 seperti BacaController, dengan alasan
     * yang sama: keberadaan buku prodi lain tidak perlu terungkap.
     */
    private function pastikanBolehMelihat(Book $buku): void
    {
        abort_if(Gate::denies('baca', $buku), 404);
    }
}