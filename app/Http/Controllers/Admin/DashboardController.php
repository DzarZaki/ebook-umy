<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Dashboard ringkasan untuk Dosen (admin prodi).
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $prodiId = $user->prodi_id;

        return view('admin.dashboard', [
            'jumlahBukuProdi' => Book::where('prodi_id', $prodiId)->count(),
            'jumlahBukuUmum' => Book::whereNull('prodi_id')->count(),
            'jumlahKategori' => Category::terlihatOleh($prodiId)->count(),
            'bukuTerbaru' => Book::terlihatOleh($prodiId)->latest()->take(5)->get(),
        ]);
    }
}
