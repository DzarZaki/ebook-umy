<?php

use App\Http\Controllers\Admin\BukuController;
use App\Http\Controllers\Admin\KategoriController;
use App\Http\Controllers\Admin\KodeAksesController;
use App\Http\Controllers\Admin\MahasiswaController;
use App\Http\Controllers\Admin\PengaturanUnduhController;
use App\Http\Controllers\Admin\SampahBukuController;
use App\Http\Controllers\Admin\StatistikController;
use App\Http\Controllers\BacaController;
use App\Http\Controllers\BerandaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KatalogController;
use App\Http\Controllers\KoleksiController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SuperAdmin\DashboardController as SuperAdminDashboardController;
use App\Http\Controllers\SuperAdmin\DosenController;
use App\Http\Controllers\SuperAdmin\ProdiController;
use App\Http\Controllers\UnduhController;
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
    /*
     * Beranda pribadi: lanjutkan membaca, buku tersimpan, bacaan terbaru.
     *
     * Namanya "beranda.saya", bukan "beranda", karena nama "beranda" sudah
     * dipakai halaman depan publik di bagian atas berkas ini. Dua halaman
     * berbeda dengan satu nama rute adalah sumber galat yang sulit dilacak:
     * yang terdaftar terakhir menang, tanpa peringatan apa pun.
     */
    Route::get('/beranda', BerandaController::class)->name('beranda.saya');

    Route::get('/katalog', [KatalogController::class, 'index'])->name('katalog.index');
    Route::get('/katalog/{buku}', [KatalogController::class, 'show'])->name('katalog.show');
    Route::get('/katalog/{buku}/baca', [BacaController::class, 'index'])->name('katalog.baca');

    // Penyaluran berkas untuk DIBACA di penampil. Dibatasi lajunya karena
    // satu berkas dapat diminta berulang kali oleh penampil PDF.
    Route::get('/katalog/{buku}/berkas', [BacaController::class, 'berkas'])
        ->name('katalog.berkas')
        ->middleware('throttle:baca-buku');

    // Gerbang UNDUH: satu-satunya pintu yang menyerahkan berkas ke tangan
    // pengguna. Di sinilah pemotongan halaman, stempel identitas, dan
    // pencatatan unduhan ditegakkan.
    Route::get('/katalog/{buku}/unduh', UnduhController::class)
        ->name('katalog.unduh')
        ->middleware('throttle:unduh-buku');

    // Kemajuan membaca dan penanda halaman, dipanggil dari penampil PDF.
    Route::get('/katalog/{buku}/data-baca', [BacaController::class, 'dataBaca'])->name('katalog.data-baca');
    Route::post('/katalog/{buku}/progres', [BacaController::class, 'simpanProgres'])->name('katalog.progres')->middleware('throttle:60,1');
    Route::post('/katalog/{buku}/penanda', [BacaController::class, 'ubahPenanda'])->name('katalog.penanda')->middleware('throttle:60,1');

    /*
     * Koleksi Saya — buku yang sengaja disimpan mahasiswa.
     *
     * Menyimpan dan melepas dipisah menjadi dua verba HTTP, bukan satu
     * rute pengalih (toggle). Dengan begitu satu permintaan selalu
     * berakhir pada keadaan yang sama, berapa kali pun terkirim —
     * penting saat koneksi lambat dan tombolnya ditekan berulang.
     */
    Route::get('/koleksi', [KoleksiController::class, 'index'])->name('koleksi.index');

    Route::post('/koleksi/{buku}', [KoleksiController::class, 'simpan'])
        ->name('koleksi.simpan')
        ->middleware('throttle:60,1');

    Route::delete('/koleksi/{buku}', [KoleksiController::class, 'lepas'])
        ->name('koleksi.lepas')
        ->middleware('throttle:60,1');
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

        /*
         * Tempat sampah buku.
         *
         * withTrashed() wajib di sini: tanpa itu, pengikatan model Laravel
         * menyembunyikan baris yang sudah dibuang dan setiap tautan di
         * halaman ini berakhir 404 — persis buku yang hendak dipulihkan.
         */
        Route::get('buku-sampah', [SampahBukuController::class, 'index'])
            ->name('buku-sampah.index');

        Route::patch('buku-sampah/{buku}/pulihkan', [SampahBukuController::class, 'pulihkan'])
            ->name('buku-sampah.pulihkan')
            ->withTrashed();

        Route::delete('buku-sampah/{buku}', [SampahBukuController::class, 'hapusPermanen'])
            ->name('buku-sampah.hapus')
            ->withTrashed();

        Route::patch('/pengaturan-unduh', [PengaturanUnduhController::class, 'update'])
            ->name('pengaturan-unduh.update');

        Route::get('/statistik', StatistikController::class)->name('statistik');

        // Kode akses pendaftaran mahasiswa
        Route::patch('kode-akses', [KodeAksesController::class, 'update'])
            ->name('kode-akses.update');

        // Pengelolaan akun mahasiswa
        Route::get('mahasiswa', [MahasiswaController::class, 'index'])->name('mahasiswa.index');
        Route::patch('mahasiswa/{mahasiswa}/status', [MahasiswaController::class, 'toggleStatus'])->name('mahasiswa.status');
        Route::delete('mahasiswa/{mahasiswa}', [MahasiswaController::class, 'destroy'])->name('mahasiswa.destroy');
    });

require __DIR__.'/auth.php';