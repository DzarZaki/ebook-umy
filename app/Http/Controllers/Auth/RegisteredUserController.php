<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Prodi;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/** Pendaftaran akun mahasiswa memakai kode akses program studi. */
class RegisteredUserController extends Controller
{
    /** Menampilkan formulir pendaftaran. */
    public function create(): View
    {
        return view('auth.register');
    }

    /** Menyimpan akun mahasiswa baru lalu mengirim tautan verifikasi surel. */
    public function store(RegisterRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Prodi ditentukan dari kode akses, bukan dipilih sendiri oleh mahasiswa.
        $prodi = Prodi::cariDenganKode($data['kode_akses']);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => User::ROLE_MAHASISWA,
            'prodi_id' => $prodi->id,
            'is_active' => true,
        ]);

        // Pendengar bawaan Registered mengirim tautan verifikasi karena
        // model User mengimplementasikan MustVerifyEmail.
        event(new Registered($user));

        Auth::login($user);

        // Sama seperti jalur masuk: ID sesi baru setelah otentikasi,
        // supaya perpindahan tamu → masuk tidak bisa dipatok pihak lain
        // (session fixation).
        $request->session()->regenerate();

        return redirect()
            ->route('verification.notice')
            ->with('status', 'Pendaftaran berhasil. Tautan verifikasi telah dikirim ke '.$user->email.'.');
    }
}
