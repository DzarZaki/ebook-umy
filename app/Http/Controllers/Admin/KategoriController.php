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
        $prodiId = $request->validated('lingkup') === 'umum' ? null : $user->prodi_id;

        Category::create([
            'name' => $nama,
            'slug' => $this->slugUnik($nama, $prodiId),
            'prodi_id' => $prodiId,
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
            'slug' => $this->slugUnik($nama, $kategori->prodi_id, $kategori->id),
        ]);

        return redirect()
            ->route('admin.kategori.index')
            ->with('status', "Kategori berhasil diperbarui menjadi \"{$nama}\".");
    }

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
     * Menghasilkan slug unik dalam lingkup prodi yang sama.
     * Menambah sufiks -2, -3, dst. bila slug dasar sudah dipakai.
     */
    private function slugUnik(string $nama, ?int $prodiId, ?int $abaikanId = null): string
    {
        $dasar = Str::slug($nama);
        $kandidat = $dasar;
        $n = 2;

        while (
            Category::where('prodi_id', $prodiId)
                ->where('slug', $kandidat)
                ->when($abaikanId, fn ($q) => $q->where('id', '!=', $abaikanId))
                ->exists()
        ) {
            $kandidat = "{$dasar}-{$n}";
            $n++;
        }

        return $kandidat;
    }

    /**
     * Menolak akses bila dosen tidak berhak atas kategori tersebut.
     */
    private function pastikanBoleh(Category $kategori): void
    {
        abort_unless($kategori->bolehDikelolaOleh(auth()->user()), 403);
    }
}
