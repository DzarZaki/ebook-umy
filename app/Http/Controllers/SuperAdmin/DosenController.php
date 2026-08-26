<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\StoreDosenRequest;
use App\Http\Requests\SuperAdmin\UpdateDosenRequest;
use App\Models\Book;
use App\Models\Prodi;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/**
 * Pengelolaan akun Dosen (Admin prodi) oleh Super Admin.
 */
class DosenController extends Controller
{
    /**
     * Menampilkan daftar akun dosen.
     */
    public function index(): View
    {
        return view('superadmin.dosen.index', [
            // withCount mengisi kolom "Buku" dan menonaktifkan tombol Hapus
            // untuk dosen yang masih menjadi pengunggah — tanpa ini angkanya
            // selalu nol dan penjagaan hanya terasa setelah DELETE ditolak.
            'daftarDosen' => User::with('prodi')
                ->withCount('bukuDiunggah')
                ->where('role', User::ROLE_ADMIN)
                ->orderBy('name')
                ->paginate(10),
        ]);
    }

    /**
     * Menampilkan formulir penambahan dosen.
     */
    public function create(): View
    {
        return view('superadmin.dosen.create', [
            'daftarProdi' => Prodi::orderBy('name')->get(),
        ]);
    }

    /**
     * Menyimpan akun dosen baru (langsung terverifikasi & aktif).
     */
    public function store(StoreDosenRequest $request): RedirectResponse
    {
        $data = $request->validated();

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'prodi_id' => $data['prodi_id'],
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        return redirect()
            ->route('superadmin.dosen.index')
            ->with('status', "Akun dosen \"{$data['name']}\" berhasil dibuat.");
    }

    /**
     * Menampilkan formulir penyuntingan dosen.
     */
    public function edit(User $user): View
    {
        $this->pastikanAkunDosen($user);

        return view('superadmin.dosen.edit', [
            'dosen' => $user,
            'daftarProdi' => Prodi::orderBy('name')->get(),
        ]);
    }

    /** Memperbarui data dosen, termasuk pemindahan prodi dan status akun. */
    public function update(UpdateDosenRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        // Super Admin tidak boleh menonaktifkan akunnya sendiri.
        if ($user->id === $request->user()->id && ! $data['is_active']) {
            return back()
                ->withErrors(['is_active' => 'Anda tidak dapat menonaktifkan akun Anda sendiri.'])
                ->with('gagal', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
        }

        $this->pastikanAkunDosen($user);

        $perubahan = [
            'name' => $data['name'],
            'email' => $data['email'],
            'prodi_id' => $data['prodi_id'],
            'is_active' => $data['is_active'],
        ];

        if (! empty($data['password'])) {
            $perubahan['password'] = Hash::make($data['password']);
        }

        $user->update($perubahan);

        /*
         * Surel baru belum terbukti kepemilikannya, apa pun perannya —
         * aturan yang sama dengan ProfileController::update(). Bukti lama
         * dibatalkan dan tautan verifikasi dikirim ulang; akun dosen yang
         * belum terverifikasi ikut tertahan middleware `verified` pada
         * jalur katalog, sama seperti mahasiswa.
         */
        if ($user->wasChanged('email')) {
            $user->forceFill(['email_verified_at' => null])->save();
            $user->sendEmailVerificationNotification();
        }

        return redirect()
            ->route('superadmin.dosen.index')
            ->with('status', 'Data dosen berhasil diperbarui.');
    }

    /** Menghapus akun dosen dengan sejumlah penjagaan. */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()
                ->withErrors(['dosen' => 'Anda tidak dapat menghapus akun Anda sendiri.'])
                ->with('gagal', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        // Dihitung lewat helper model, jadi aman meski nilai peran berubah.
        $jumlahSuperAdmin = User::where('role', User::ROLE_SUPERADMIN)->count();

        if ($user->isSuperAdmin() && $jumlahSuperAdmin <= 1) {
            return back()
                ->withErrors(['dosen' => 'Harus tersisa minimal satu Super Admin.'])
                ->with('gagal', 'Harus tersisa minimal satu Super Admin.');
        }

        $this->pastikanAkunDosen($user);

        /*
         * Akun yang masih tercatat sebagai pengunggah tidak dihapus.
         *
         * Sejak books.uploaded_by memakai nullOnDelete, penghapusan akun tidak
         * lagi melenyapkan bukunya — itu jaring pengaman di lapis database.
         * Penjagaan di sini menjawab kerugian yang tetap terjadi: jejak siapa
         * pengunggah sebuah buku hilang selamanya, dan buku umum (tanpa prodi)
         * berubah menjadi yatim yang tak dapat dikelola dosen mana pun, karena
         * hak atas buku umum ditentukan oleh kolom uploaded_by itu sendiri.
         *
         * withTrashed() disertakan dengan sengaja: buku di tempat sampah masih
         * dapat dipulihkan selama masa tenggang, jadi ia belum kehilangan hak
         * atas pengunggahnya.
         */
        $jumlahBuku = Book::withTrashed()->where('uploaded_by', $user->id)->count();

        if ($jumlahBuku > 0) {
            $pesan = "Akun \"{$user->name}\" masih tercatat sebagai pengunggah {$jumlahBuku} buku, "
                .'sehingga tidak dapat dihapus. Nonaktifkan akunnya lewat tombol Ubah — '
                .'akun nonaktif tidak dapat masuk, tetapi bukunya tetap terurus.';

            return back()
                ->withErrors(['dosen' => $pesan])
                ->with('gagal', $pesan);
        }

        $user->delete();

        return redirect()
            ->route('superadmin.dosen.index')
            ->with('status', 'Akun dosen berhasil dihapus.');
    }

    /**
     * Halaman ini hanya mengelola akun Dosen, yaitu pengguna berperan `admin`.
     *
     * Rute `superadmin/dosen/{user}` menerima id pengguna apa pun, sehingga
     * tanpa penjagaan ini satu permintaan DELETE ke id seorang mahasiswa akan
     * menghapus akun itu beserta seluruh kemajuan bacaan, penanda, dan koleksi
     * yang terkait — semuanya lewat halaman yang tidak pernah menampilkan
     * mahasiswa. Akun Super Admin lain juga ditolak: pemindahan peran itu
     * urusan yang lebih besar daripada formulir dosen.
     *
     * Dipakai 404, bukan 403, agar sama dengan perilaku `edit()` dan agar
     * keberadaan sebuah id tidak bocor dari perbedaan kode jawaban.
     */
    private function pastikanAkunDosen(User $user): void
    {
        abort_unless($user->isAdmin(), 404);
    }
}
