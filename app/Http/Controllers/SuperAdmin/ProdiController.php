<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\StoreProdiRequest;
use App\Http\Requests\SuperAdmin\UpdateProdiRequest;
use App\Models\Prodi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Pengelolaan data program studi oleh Super Admin.
 */
class ProdiController extends Controller
{
    /**
     * Menampilkan daftar prodi beserta jumlah penggunanya.
     */
    public function index(): View
    {
        return view('superadmin.prodi.index', [
            'daftarProdi' => Prodi::withCount('users')->orderBy('name')->paginate(10),
        ]);
    }

    /**
     * Menampilkan formulir penambahan prodi.
     */
    public function create(): View
    {
        return view('superadmin.prodi.create');
    }

    /**
     * Menyimpan prodi baru.
     */
    public function store(StoreProdiRequest $request): RedirectResponse
    {
        $nama = $request->validated('name');

        Prodi::create([
            'name' => $nama,
            'slug' => Str::slug($nama),
        ]);

        return redirect()
            ->route('superadmin.prodi.index')
            ->with('status', "Program studi \"{$nama}\" berhasil ditambahkan.");
    }

    /**
     * Menampilkan formulir penyuntingan prodi.
     */
    public function edit(Prodi $prodi): View
    {
        return view('superadmin.prodi.edit', ['prodi' => $prodi]);
    }

    /**
     * Memperbarui nama prodi (slug ikut disesuaikan).
     */
    public function update(UpdateProdiRequest $request, Prodi $prodi): RedirectResponse
    {
        $nama = $request->validated('name');

        $prodi->update([
            'name' => $nama,
            'slug' => Str::slug($nama),
        ]);

        return redirect()
            ->route('superadmin.prodi.index')
            ->with('status', "Program studi berhasil diperbarui menjadi \"{$nama}\".");
    }

    /**
     * Menghapus prodi. Ditolak bila masih memiliki pengguna terkait
     * agar data dosen/mahasiswa tidak menjadi yatim.
     */
    /** Menghapus prodi, hanya bila sudah tidak dipakai data lain. */
    /** Menghapus prodi, hanya bila sudah tidak dipakai data lain. */
    public function destroy(Prodi $prodi): RedirectResponse
    {
        $penghalang = [];

        if ($prodi->users()->exists()) {
            $penghalang[] = 'akun pengguna';
        }

        if ($prodi->books()->exists()) {
            $penghalang[] = 'buku';
        }

        if ($prodi->categories()->exists()) {
            $penghalang[] = 'kategori';
        }

        if ($penghalang) {
            $pesan = 'Program studi ini masih memiliki '.implode(', ', $penghalang).'. Pindahkan atau hapus data itu terlebih dahulu.';

            return back()
                ->withErrors(['prodi' => $pesan])
                ->with('gagal', $pesan);
        }

        $prodi->delete();

        return redirect()
            ->route('superadmin.prodi.index')
            ->with('status', 'Program studi berhasil dihapus.');
    }
}
