<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Category;
use App\Models\User;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Katalog buku untuk mahasiswa: penelusuran, penyaringan, dan halaman detail.
 */
class KatalogController extends Controller
{
    /**
     * Menampilkan daftar buku yang boleh diakses pengguna,
     * lengkap dengan pencarian dan penyaringan.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $kueri = trim((string) $request->query('q', ''));
        $kategoriId = $request->query('kategori');
        $lingkup = $request->query('lingkup', 'semua');
        $urut = $request->query('urut', 'terbaru');

        // Kategori yang relevan bagi pengguna — dipakai untuk menu penyaring
        // sekaligus pagar penyaringan: id kategori di luar daftar ini
        // (milik prodi lain, misalnya) tidak pernah menyaring apa pun.
        $daftarKategori = $this->kategoriTersedia($user);
        $kategoriSah = is_numeric($kategoriId)
            ? $daftarKategori->firstWhere('id', (int) $kategoriId)
            : null;

        /*
         * Karakter wildcard LIKE dari pengguna dibuang, bukan di-escape.
         * Escape dengan backslash tidak portabel: MySQL memakainya secara
         * bawaan, SQLite justru menelan backslash sebagai karakter biasa
         * sehingga pencarian "a\b" berperilaku beda di dua basis data.
         * Membuang % dan _ hanya mengorbankan pencarian literal yang
         * nyaris tak pernah dimaksudkan siapa pun.
         */
        $istilah = str_replace(['%', '_'], '', $kueri);

        $daftarBuku = Book::query()
            ->with(['prodi', 'category'])
            ->terbit()
            // Super Admin melihat seluruh koleksi; peran lain disaring per prodi.
            ->when(! $user->isSuperAdmin(), fn (Builder $q) => $q->terlihatOleh($user->prodi_id))
            ->when($istilah !== '', fn (Builder $q) => $q->where(function (Builder $sub) use ($istilah) {
                $sub->where('title', 'like', "%{$istilah}%")
                    ->orWhere('author', 'like', "%{$istilah}%")
                    ->orWhere('description', 'like', "%{$istilah}%")
                    // Pencarian isi: judul buku tidak selalu menyebut topik
                    // yang diburu pembacanya.
                    ->orWhere('search_text', 'like', "%{$istilah}%");
            }))
            ->when($kategoriSah, fn (Builder $q) => $q->where('category_id', $kategoriSah->id))
            ->when($lingkup === 'prodi', fn (Builder $q) => $q->whereNotNull('prodi_id'))
            ->when($lingkup === 'umum', fn (Builder $q) => $q->whereNull('prodi_id'))
            ->when(
                $urut === 'judul',
                fn (Builder $q) => $q->orderBy('title'),
                fn (Builder $q) => $q->latest()
            )
            ->paginate(12)
            ->withQueryString();

        return view('katalog.index', [
            'daftarBuku' => $daftarBuku,
            'daftarKategori' => $daftarKategori,
            'kueri' => $kueri,
            'potonganIsi' => $this->potonganIsiCocok($daftarBuku, $kueri),
            'kategoriId' => $kategoriId,
            'lingkup' => $lingkup,
            'urut' => $urut,
        ]);
    }

    /**
     * Potongan isi untuk hasil yang cocok HANYA lewat isinya.
     *
     * Bila judul atau penulis sudah menjelaskan kecocokannya, potongan
     * hanyalah gangguan. Ia tampil tepat pada momen yang dibutuhkan:
     * ketika pembaca bertanya-tanya mengapa buku berjudul asing itu muncul
     * untuk kata kuncinya. Hanya halaman aktif yang diproses — dua belas
     * pemeriksaan stripos, bukan seluruh koleksi.
     *
     * @return array<int, string> dipetakan dari id buku
     */
    private function potonganIsiCocok(LengthAwarePaginator $daftarBuku, string $kueri): array
    {
        if ($kueri === '') {
            return [];
        }

        $istilah = str_replace(['%', '_'], '', $kueri);
        $potongan = [];

        foreach ($daftarBuku as $buku) {
            if ($buku->search_text === null) {
                continue;
            }

            $cocokMetadata = mb_stripos($buku->title, $istilah) !== false
                || ($buku->author !== null && mb_stripos($buku->author, $istilah) !== false)
                || ($buku->description !== null && mb_stripos($buku->description, $istilah) !== false);

            if ($cocokMetadata) {
                continue;
            }

            $isiPotongan = $buku->potonganCocok($istilah);

            if ($isiPotongan !== null) {
                $potongan[$buku->getKey()] = $isiPotongan;
            }
        }

        return $potongan;
    }

    /**
     * Menampilkan detail satu buku beserta aturan aksesnya.
     */
    public function show(Request $request, Book $buku): View
    {
        $this->pastikanTerlihat($request->user(), $buku);

        $buku->load(['prodi', 'category', 'pengunggah']);

        return view('katalog.show', [
            'buku' => $buku,
            'serupa' => Book::terbit()
                ->when(! $request->user()->isSuperAdmin(), fn (Builder $q) => $q->terlihatOleh($request->user()->prodi_id))
                ->where('id', '!=', $buku->id)
                ->when($buku->category_id, fn (Builder $q) => $q->where('category_id', $buku->category_id))
                ->latest()
                ->take(4)
                ->get(),
        ]);
    }

    /**
     * Kategori yang relevan bagi pengguna, untuk mengisi menu penyaring.
     *
     * @return Collection<int, Category>
     */
    private function kategoriTersedia(User $user)
    {
        return Category::query()
            ->when(! $user->isSuperAdmin(), fn (Builder $q) => $q->terlihatOleh($user->prodi_id))
            ->orderBy('name')
            ->get();
    }

    /**
     * Menolak akses bila buku belum terbit atau di luar prodi pengguna.
     */
    private function pastikanTerlihat(User $user, Book $buku): void
    {
        abort_unless($buku->bolehDilihatOleh($user), 404);
    }
}
