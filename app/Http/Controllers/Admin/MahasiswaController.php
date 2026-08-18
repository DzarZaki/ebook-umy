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

    /**
     * Menampilkan formulir penyuntingan data mahasiswa.
     *
     * Penjagaannya sengaja 403, bukan 404: akun mahasiswa bukan rahasia
     * lintas prodi (jumlahnya bisa ditebak dari daftar publik prodi),
     * sehingga menyembunyikan keberadaannya tidak menambah keamanan —
     * sedangkan 403 memberi pesan jujur bahwa wewenangnya yang kurang.
     */
    public function edit(Request $request, User $mahasiswa): View
    {
        $this->pastikanBoleh($request, $mahasiswa);

        return view('admin.mahasiswa.edit', [
            'mahasiswa' => $mahasiswa,
            'daftarProdi' => Prodi::orderBy('name')->get(),
        ]);
    }

    /**
     * Memperbarui nama, email, dan program studi mahasiswa.
     *
     * Hanya tiga kolom itu yang disentuh. `role`, `is_active`, `password`, dan
     * `email_verified_at` sengaja tidak ikut di-mass-assign di sini: status akun
     * punya rutenya sendiri (mahasiswa.status), dan kata sandi adalah milik
     * pengguna — dosen tidak berhak menimpanya dari halaman ini.
     */
    public function update(UpdateMahasiswaRequest $request, User $mahasiswa): RedirectResponse
    {
        $this->pastikanBoleh($request, $mahasiswa);

        $data = $request->validated();

        // Dihitung sebelum update, karena setelahnya nilai lamanya sudah hilang.
        $pindahProdi = (int) $data['prodi_id'] !== (int) $mahasiswa->prodi_id;

        $mahasiswa->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'prodi_id' => (int) $data['prodi_id'],
        ]);

        $pesan = $pindahProdi
            ? "Data {$mahasiswa->name} berhasil diperbarui. Akun ini kini berada di program studi lain, sehingga tidak lagi muncul pada daftar Anda."
            : "Data {$mahasiswa->name} berhasil diperbarui.";

        return redirect()
            ->route('admin.mahasiswa.index')
            ->with('status', $pesan);
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

    /**
     * Dosen hanya boleh menyentuh akun mahasiswa di program studinya sendiri.
     *
     * Dua syarat, bukan satu: perannya harus mahasiswa (agar rute ini tidak
     * bisa dipakai menyunting akun dosen atau super admin yang kebetulan
     * ber-prodi sama), dan prodinya harus persis prodi dosen tersebut.
     */
    private function pastikanBoleh(Request $request, User $mahasiswa): void
    {
        $dosen = $request->user();

        abort_unless(
            $mahasiswa->isMahasiswa()
                && $dosen->prodi_id !== null
                && $mahasiswa->prodi_id === $dosen->prodi_id,
            403,
        );
    }
}