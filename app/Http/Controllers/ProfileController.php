<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Book;
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
         * Mengganti surel berarti membatalkan bukti kepemilikan lamanya:
         * alamat baru belum dikonfirmasi siapa pun, jadi kolom ini sengaja
         * dikosongkan. Middleware `verified` pada jalur mahasiswa kemudian
         * mengarahkan pengguna itu ke halaman verifikasi — sopan, bukan
         * galat — sampai surel barunya dikonfirmasi lewat tautan.
         *
         * Dosen ikut terdampak bila mengganti surel mereka; itu disengaja.
         * Alamat baru harus dibuktikan kepemilikannya, apa pun perannya.
         */
        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        /*
         * Kotak centang yang tidak dikirim berarti "matikan". fill() saja
         * akan membiarkan nilai lama bertahan ketika kolomnya absen dari
         * kiriman, sehingga pelanggan tidak pernah bisa berhenti berlangganan
         * — kebalikan dari yang ia minta.
         */
        $request->user()->notifikasi_buku_baru = $request->boolean('notifikasi_buku_baru');

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

        /*
         * Penjagaan yang sama dengan panel Super Admin berlaku di sini.
         * Tanpa ini, pintu hapus-akun-sendiri menjadi jalan tikus yang
         * melewati dua penghalang: Super Admin terakhir dapat melenyapkan
         * dirinya dan mematikan seluruh akses kelola, dan dosen pengunggah
         * buku dapat mengosongkan uploaded_by sehingga buku umumnya menjadi
         * yatim yang tak bisa diklaim siapa pun.
         */
        if ($user->isSuperAdmin()) {
            return back()->withErrors(
                ['akun' => 'Akun Super Admin tidak dapat menghapus dirinya sendiri.'],
                'userDeletion',
            );
        }

        $jumlahBuku = Book::withTrashed()->where('uploaded_by', $user->id)->count();

        if ($jumlahBuku > 0) {
            $pesan = "Anda masih tercatat sebagai pengunggah {$jumlahBuku} buku, "
                .'sehingga akun tidak dapat dihapus. Minta Super Admin menonaktifkan akun Anda.';

            return back()->withErrors(['akun' => $pesan], 'userDeletion');
        }

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
