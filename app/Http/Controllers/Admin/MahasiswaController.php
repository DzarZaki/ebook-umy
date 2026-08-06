<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateMahasiswaRequest;
use App\Models\Prodi;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Pengelolaan akun mahasiswa oleh dosen program studi. */
class MahasiswaController extends Controller
{
    /** Menampilkan daftar mahasiswa pada prodi dosen, dengan pencarian sederhana. */
    public function index(Request $request): View
    {
        $dosen = $request->user();
        $cari = trim((string) $request->query('cari'));

        $daftarMahasiswa = User::query()
            ->where('role', User::ROLE_MAHASISWA)
            ->where('prodi_id', $dosen->prodi_id)
            ->when($cari !== '', function ($kueri) use ($cari) {
                $kueri->where(function ($bagian) use ($cari) {
                    $bagian->where('name', 'like', '%'.$cari.'%')
                        ->orWhere('email', 'like', '%'.$cari.'%');
                });
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.mahasiswa.index', [
            'daftarMahasiswa' => $daftarMahasiswa,
            'cari' => $cari,
        ]);
    }

    /** Menampilkan formulir penyuntingan seorang mahasiswa. */
    public function edit(Request $request, User $mahasiswa): View
    {
        $this->pastikanBoleh($request, $mahasiswa);

        return view('admin.mahasiswa.edit', [
            'mahasiswa' => $mahasiswa,
            'daftarProdi' => Prodi::orderBy('name')->get(),
        ]);
    }

    /** Menyimpan perubahan data mahasiswa, termasuk pemindahan program studi. */
    public function update(UpdateMahasiswaRequest $request, User $mahasiswa): RedirectResponse
    {
        $this->pastikanBoleh($request, $mahasiswa);

        $mahasiswa->update($request->validated());

        return redirect()
            ->route('admin.mahasiswa.index')
            ->with('status', 'Data mahasiswa berhasil diperbarui.');
    }

    /** Mengaktifkan atau menonaktifkan akun mahasiswa. */
    public function toggleStatus(Request $request, User $mahasiswa): RedirectResponse
    {
        $this->pastikanBoleh($request, $mahasiswa);

        $mahasiswa->update(['is_active' => ! $mahasiswa->is_active]);

        $pesan = $mahasiswa->is_active
            ? 'Akun mahasiswa berhasil diaktifkan.'
            : 'Akun mahasiswa berhasil dinonaktifkan.';

        return back()->with('status', $pesan);
    }

    /** Menghapus akun mahasiswa. */
    public function destroy(Request $request, User $mahasiswa): RedirectResponse
    {
        $this->pastikanBoleh($request, $mahasiswa);

        $mahasiswa->delete();

        return redirect()
            ->route('admin.mahasiswa.index')
            ->with('status', 'Akun mahasiswa berhasil dihapus.');
    }

    /** Dosen hanya boleh menyentuh akun mahasiswa di program studinya sendiri. */
    private function pastikanBoleh(Request $request, User $mahasiswa): void
    {
        $dosen = $request->user();

        abort_unless(
            $mahasiswa->isMahasiswa() && $mahasiswa->prodi_id === $dosen->prodi_id,
            403,
        );
    }
}
