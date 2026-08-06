<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Mengarahkan pengguna ke dashboard yang sesuai dengan perannya.
 */
class DashboardController extends Controller
{
    /**
     * Menentukan tujuan dashboard berdasarkan peran pengguna.
     */
    public function __invoke(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->isSuperAdmin()) {
            return redirect()->route('superadmin.dashboard');
        }

        // Admin/Dosen & Mahasiswa masih memakai dashboard umum
        // Mahasiswa langsung diarahkan ke katalog sebagai halaman utamanya.
        return redirect()->route('katalog.index');
    }
}
