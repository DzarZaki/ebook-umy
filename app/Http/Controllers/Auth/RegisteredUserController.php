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

    /** Menyimpan akun mahasiswa baru lalu langsung memasukkannya ke aplikasi. */
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
            'email_verified_at' => now(),
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect()
            ->route('katalog.index')
            ->with('status', 'Pendaftaran berhasil. Selamat membaca, '.$user->name.'.');
    }
}
