<?php

use App\Http\Controllers\Admin\BukuController;
use App\Http\Controllers\Admin\KategoriController;
use App\Http\Controllers\Admin\KodeAksesController;
use App\Http\Controllers\Admin\MahasiswaController;
use App\Http\Controllers\Admin\PengaturanUnduhController;
use App\Http\Controllers\Admin\StatistikController;
use App\Http\Controllers\BacaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KatalogController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SuperAdmin\DashboardController as SuperAdminDashboardController;
use App\Http\Controllers\SuperAdmin\DosenController;
use App\Http\Controllers\SuperAdmin\ProdiController;
use App\Models\Prodi;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome', [
        'daftarProdi' => Prodi::orderBy('name')->get(),
    ]);
})->name('beranda');

// Pintu masuk tunggal — dialihkan sesuai peran pengguna.
Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'active'])
    ->name('dashboard');

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| Area Super Admin
|--------------------------------------------------------------------------
| Hanya dapat diakses oleh pengguna dengan peran `superadmin`.
*/
Route::middleware(['auth', 'active', 'role:superadmin'])
    ->prefix('superadmin')
    ->name('superadmin.')
    ->group(function () {
        Route::get('/', SuperAdminDashboardController::class)->name('dashboard');

        Route::resource('prodi', ProdiController::class)->except(['show']);

        Route::resource('dosen', DosenController::class)
            ->except(['show'])
            ->parameters(['dosen' => 'user']);
    });

/*
|--------------------------------------------------------------------------
| Katalog (dapat diakses semua pengguna yang sudah masuk)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/katalog', [KatalogController::class, 'index'])->name('katalog.index');
    Route::get('/katalog/{buku}', [KatalogController::class, 'show'])->name('katalog.show');
    Route::get('/katalog/{buku}/baca', [BacaController::class, 'index'])->name('katalog.baca');
    Route::get('/katalog/{buku}/berkas', [BacaController::class, 'berkas'])->name('katalog.berkas');
    Route::post('/katalog/{buku}/catat-unduhan', [BacaController::class, 'catat'])->name('katalog.catat');
    // Kemajuan membaca dan penanda halaman, dipanggil dari penampil PDF.
    Route::get('/katalog/{buku}/data-baca', [BacaController::class, 'dataBaca'])->name('katalog.data-baca');
    Route::post('/katalog/{buku}/progres', [BacaController::class, 'simpanProgres'])->name('katalog.progres');
    Route::post('/katalog/{buku}/penanda', [BacaController::class, 'ubahPenanda'])->name('katalog.penanda');
});

/*
|--------------------------------------------------------------------------
| Area Dosen (Admin Prodi)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'active', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', App\Http\Controllers\Admin\DashboardController::class)->name('dashboard');

        Route::resource('kategori', KategoriController::class)
            ->except(['show'])
            ->parameters(['kategori' => 'kategori']);

        Route::resource('buku', BukuController::class)
            ->except(['show'])
            ->parameters(['buku' => 'buku']);

        Route::patch('/pengaturan-unduh', [PengaturanUnduhController::class, 'update'])
            ->name('pengaturan-unduh.update');

        Route::get('/statistik', StatistikController::class)->name('statistik');

        // Kode akses pendaftaran mahasiswa
        Route::patch('kode-akses', [KodeAksesController::class, 'update'])
            ->name('kode-akses.update');

        // Pengelolaan akun mahasiswa
        Route::get('mahasiswa', [MahasiswaController::class, 'index'])->name('mahasiswa.index');
        Route::get('mahasiswa/{mahasiswa}/ubah', [MahasiswaController::class, 'edit'])->name('mahasiswa.edit');
        Route::put('mahasiswa/{mahasiswa}', [MahasiswaController::class, 'update'])->name('mahasiswa.update');
        Route::patch('mahasiswa/{mahasiswa}/status', [MahasiswaController::class, 'toggleStatus'])->name('mahasiswa.status');
        Route::delete('mahasiswa/{mahasiswa}', [MahasiswaController::class, 'destroy'])->name('mahasiswa.destroy');
    });

require __DIR__.'/auth.php';
