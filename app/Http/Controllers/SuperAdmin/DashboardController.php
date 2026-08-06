<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Prodi;
use App\Models\User;
use Illuminate\View\View;

/**
 * Dashboard ringkasan untuk Super Admin.
 */
class DashboardController extends Controller
{
    /**
     * Menampilkan statistik ringkas jumlah prodi dan pengguna.
     */
    public function __invoke(): View
    {
        return view('superadmin.dashboard', [
            'jumlahProdi' => Prodi::count(),
            'jumlahDosen' => User::where('role', User::ROLE_ADMIN)->count(),
            'jumlahMahasiswa' => User::where('role', User::ROLE_MAHASISWA)->count(),
        ]);
    }
}
