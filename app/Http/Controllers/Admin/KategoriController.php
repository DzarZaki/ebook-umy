<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreKategoriRequest;
use App\Http\Requests\Admin\UpdateKategoriRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Pengelolaan kategori konten oleh dosen.
 */
class KategoriController extends Controller
{
    /**
     * Menampilkan kategori prodi dosen ditambah kategori Umum.
     */
    public function index(): View
    {
        $prodiId = auth()->user()->prodi_id;

        return view('admin.kategori.index', [
            'daftarKategori' => Category::with('prodi')
                ->withCount('books')
                ->terlihatOleh($prodiId)
                ->orderBy('prodi_id')
                ->orderBy('name')
                ->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('admin.kategori.create');
    }

    /**
     * Menyimpan kategori baru sesuai lingkup yang dipilih.
     */
    public function store(StoreKategoriRequest $request): RedirectResponse
    {
        $user = $request->user();
        $nama = $request->validated('name');

        Category::create([
            'name' => $nama,
            'slug' => Str::slug($nama),
            'prodi_id' => $request->validated('lingkup') === 'umum' ? null : $user->prodi_id,
            'created_by' => $user->id,
        ]);

        return redirect()
            ->route('admin.kategori.index')
            ->with('status', "Kategori \"{$nama}\" berhasil ditambahkan.");
    }

    public function edit(Category $kategori): View
    {
        $this->pastikanBoleh($kategori);

        return view('admin.kategori.edit', ['kategori' => $kategori]);
    }

    public function update(UpdateKategoriRequest $request, Category $kategori): RedirectResponse
    {
        $this->pastikanBoleh($kategori);

        $nama = $request->validated('name');

        $kategori->update([
            'name' => $nama,
            'slug' => Str::slug($nama),
        ]);

        return redirect()
            ->route('admin.kategori.index')
            ->with('status', "Kategori berhasil diperbarui menjadi \"{$nama}\".");
    }

    /**
     * Menghapus kategori. Ditolak bila masih dipakai oleh buku.
     */
    /** Menghapus kategori, hanya bila belum dipakai buku mana pun. */
    /** Menghapus kategori, hanya bila belum dipakai buku mana pun. */
    public function destroy(Request $request, Category $kategori): RedirectResponse
    {
        abort_unless($kategori->bolehDikelolaOleh($request->user()), 403);

        if ($kategori->books()->exists()) {
            $pesan = 'Kategori ini masih dipakai oleh buku. Pindahkan buku-buku itu ke kategori lain terlebih dahulu.';

            return back()
                ->withErrors(['kategori' => $pesan])
                ->with('gagal', $pesan);
        }

        $kategori->delete();

        return redirect()
            ->route('admin.kategori.index')
            ->with('status', 'Kategori berhasil dihapus.');
    }

    /**
     * Menolak akses bila dosen tidak berhak atas kategori tersebut.
     */
    private function pastikanBoleh(Category $kategori): void
    {
        abort_unless($kategori->bolehDikelolaOleh(auth()->user()), 403);
    }
}
