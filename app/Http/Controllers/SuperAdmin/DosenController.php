<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\StoreDosenRequest;
use App\Http\Requests\SuperAdmin\UpdateDosenRequest;
use App\Models\Prodi;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/**
 * Pengelolaan akun Dosen (Admin prodi) oleh Super Admin.
 */
class DosenController extends Controller
{
    /**
     * Menampilkan daftar akun dosen.
     */
    public function index(): View
    {
        return view('superadmin.dosen.index', [
            'daftarDosen' => User::with('prodi')
                ->where('role', User::ROLE_ADMIN)
                ->orderBy('name')
                ->paginate(10),
        ]);
    }

    /**
     * Menampilkan formulir penambahan dosen.
     */
    public function create(): View
    {
        return view('superadmin.dosen.create', [
            'daftarProdi' => Prodi::orderBy('name')->get(),
        ]);
    }

    /**
     * Menyimpan akun dosen baru (langsung terverifikasi & aktif).
     */
    public function store(StoreDosenRequest $request): RedirectResponse
    {
        $data = $request->validated();

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'prodi_id' => $data['prodi_id'],
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        return redirect()
            ->route('superadmin.dosen.index')
            ->with('status', "Akun dosen \"{$data['name']}\" berhasil dibuat.");
    }

    /**
     * Menampilkan formulir penyuntingan dosen.
     */
    public function edit(User $user): View
    {
        abort_unless($user->isAdmin(), 404);

        return view('superadmin.dosen.edit', [
            'dosen' => $user,
            'daftarProdi' => Prodi::orderBy('name')->get(),
        ]);
    }

    /**
     * Memperbarui data dosen. Password hanya diganti bila diisi.
     */
    /** Memperbarui data dosen, termasuk pemindahan prodi dan status akun. */
    public function update(UpdateDosenRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        // Super Admin tidak boleh menonaktifkan akunnya sendiri.
        if ($user->id === $request->user()->id && ! $data['is_active']) {
            return back()
                ->withErrors(['is_active' => 'Anda tidak dapat menonaktifkan akun Anda sendiri.'])
                ->with('gagal', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
        }

        $perubahan = [
            'name' => $data['name'],
            'email' => $data['email'],
            'prodi_id' => $data['prodi_id'],
            'is_active' => $data['is_active'],
        ];

        if (! empty($data['password'])) {
            $perubahan['password'] = Hash::make($data['password']);
        }

        $user->update($perubahan);

        return redirect()
            ->route('superadmin.dosen.index')
            ->with('status', 'Data dosen berhasil diperbarui.');
    }

    /**
     * Menghapus akun dosen.
     */
    /** Menghapus akun dosen dengan sejumlah penjagaan. */
    /** Menghapus akun dosen dengan sejumlah penjagaan. */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()
                ->withErrors(['dosen' => 'Anda tidak dapat menghapus akun Anda sendiri.'])
                ->with('gagal', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        // Dihitung lewat helper model, jadi aman meski nilai peran berubah.
        $jumlahSuperAdmin = User::all()->filter(fn (User $akun) => $akun->isSuperAdmin())->count();

        if ($user->isSuperAdmin() && $jumlahSuperAdmin <= 1) {
            return back()
                ->withErrors(['dosen' => 'Harus tersisa minimal satu Super Admin.'])
                ->with('gagal', 'Harus tersisa minimal satu Super Admin.');
        }

        $user->delete();

        return redirect()
            ->route('superadmin.dosen.index')
            ->with('status', 'Akun dosen berhasil dihapus.');
    }
}
