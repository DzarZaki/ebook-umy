<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Mengarahkan pengguna ke halaman utama yang sesuai dengan perannya.
 *
 * Berkas ini sengaja tidak menggambar apa pun. Tugasnya hanya menentukan
 * tujuan, sehingga kueri berat setiap peran tinggal di controller-nya
 * masing-masing.
 */
class DashboardController extends Controller
{
    /**
     * Menentukan tujuan berdasarkan peran pengguna.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->isSuperAdmin()) {
            return redirect()->route('superadmin.dashboard');
        }

        // Mahasiswa mendarat di rak pribadinya: buku yang sedang dibaca,
        // yang tersimpan, lalu yang baru ditambahkan dosen. Katalog tetap
        // ada di menu sebagai tempat menjelajah.
        if ($user->isMahasiswa()) {
            return redirect()->route('beranda.saya');
        }

        // Admin/Dosen: perilaku lama dipertahankan apa adanya.
        return redirect()->route('katalog.index');
    }
}