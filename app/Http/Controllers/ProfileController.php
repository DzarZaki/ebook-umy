<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Menampilkan formulir profil pengguna.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Memperbarui informasi profil pengguna.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        /*
         * Catatan penting tentang `email_verified_at`.
         *
         * Aplikasi ini TIDAK memiliki alur verifikasi surel: tidak ada rute
         * `verification.notice`/`verification.verify`, model User tidak
         * mengimplementasikan MustVerifyEmail, dan MAIL_MAILER masih `log`.
         * Kolom ini diisi `now()` begitu saja saat akun dibuat (pendaftaran
         * mahasiswa, pembuatan dosen oleh Super Admin, seeder), jadi ia tidak
         * pernah menjadi bukti kepemilikan kotak surat.
         *
         * Barisnya dipertahankan karena inilah satu-satunya saat kolom itu
         * mengatakan hal yang benar: surel baru ini memang belum dikonfirmasi
         * siapa pun. Tetapi ia BUKAN penjagaan.
         *
         * Karena itu, JANGAN menambahkan middleware `verified` pada rute mana
         * pun sebelum keempat bagian alur verifikasi benar-benar dipasang.
         * Bila ditambahkan sekarang, setiap pengguna yang pernah mengganti
         * surelnya sendiri akan langsung kehilangan akses dan diarahkan ke
         * rute bernama `verification.notice` yang tidak terdaftar — 500 yang
         * hanya menimpa sebagian pengguna. Pengujian
         * ProfileSurelDosenTest::test_mengubah_surel_tidak_mengunci_pengguna_dari_katalog
         * dipasang untuk menangkap perubahan itu lebih dulu.
         */
        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Menghapus akun pengguna sendiri.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}