<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Book;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Aturan wewenang untuk buku.
 *
 * Policy ini tidak menyalin ulang logika izin, melainkan meneruskannya ke
 * model Book. Dengan begitu aturan tetap punya satu sumber kebenaran,
 * sementara controller dan Blade cukup memakai authorize() dan @can.
 *
 * Pada Laravel 12 policy ditemukan otomatis dari namanya
 * (App\Models\Book -> App\Policies\BookPolicy), jadi tidak perlu
 * didaftarkan di service provider.
 */
class BookPolicy
{
    /**
     * Pemeriksaan yang berlaku sebelum seluruh kemampuan lain.
     *
     * Mengembalikan null berarti "belum diputuskan", sehingga penilaian
     * diteruskan ke method yang sesuai.
     */
    public function before(User $pengguna, string $kemampuan): ?bool
    {
        // Akun yang dinonaktifkan tidak berhak atas apa pun, termasuk
        // sekadar melihat. Middleware sudah menjaga ini, tetapi wewenang
        // sebaiknya tidak bergantung pada satu lapisan saja.
        if (! $pengguna->is_active) {
            return false;
        }

        if ($pengguna->isSuperAdmin()) {
            return true;
        }

        return null;
    }

    /** Melihat daftar katalog. */
    public function viewAny(User $pengguna): bool
    {
        return true;
    }

    /** Membuka halaman rincian sebuah buku. */
    public function view(User $pengguna, Book $buku): Response
    {
        return $buku->bolehDilihatOleh($pengguna)
            ? Response::allow()
            : Response::deny('Buku ini tidak tersedia untuk Anda.');
    }

    /**
     * Membaca isi buku di penampil.
     *
     * Dipisahkan dari `view` walau aturannya kini sama, karena keduanya
     * berbeda taraf: `view` hanya membuka sampul dan keterangan, sedangkan
     * `baca` menyalurkan isi berkas. Bila kelak ada buku yang boleh
     * dilihat tetapi tidak boleh dibuka, perubahannya cukup di sini.
     */
    public function baca(User $pengguna, Book $buku): Response
    {
        return $this->view($pengguna, $buku);
    }

    /**
     * Mengunduh berkas buku.
     *
     * Alasan penolakan dari model diteruskan apa adanya, supaya pengguna
     * membaca keterangan yang tepat — misalnya "unduhan dinonaktifkan oleh
     * program studi" — bukan sekadar halaman 403 yang membingungkan.
     */
    public function unduh(User $pengguna, Book $buku): Response
    {
        if (! $buku->bolehDilihatOleh($pengguna)) {
            return Response::deny('Buku ini tidak tersedia untuk Anda.');
        }

        $aturan = $buku->aturanUnduhUntuk($pengguna);

        if ($aturan['boleh'] ?? false) {
            return Response::allow();
        }

        return Response::deny(
            (string) ($aturan['alasan'] ?? 'Buku ini tidak tersedia untuk diunduh.')
        );
    }

    /** Menambah buku baru. */
    public function create(User $pengguna): bool
    {
        return $pengguna->role !== User::ROLE_MAHASISWA;
    }

    /** Menyunting buku yang sudah ada. */
    public function update(User $pengguna, Book $buku): Response
    {
        return $buku->bolehDikelolaOleh($pengguna)
            ? Response::allow()
            : Response::deny('Anda tidak berhak mengubah buku ini.');
    }

    /** Membuang buku ke tempat sampah. Masih dapat dibatalkan. */
    public function delete(User $pengguna, Book $buku): Response
    {
        return $buku->bolehDikelolaOleh($pengguna)
            ? Response::allow()
            : Response::deny('Anda tidak berhak menghapus buku ini.');
    }

    /**
     * Memulihkan buku dari tempat sampah.
     *
     * Nama `restore` dan `forceDelete` bukan pilihan bebas: Laravel
     * memetakan authorize('restore', ...) ke method bernama persis itu.
     */
    public function restore(User $pengguna, Book $buku): Response
    {
        return $buku->bolehDikelolaOleh($pengguna)
            ? Response::allow()
            : Response::deny('Anda tidak berhak memulihkan buku ini.');
    }

    /**
     * Melenyapkan buku beserta berkasnya, tanpa jalan kembali.
     *
     * Aturannya kini sama dengan `delete`, tetapi sengaja berdiri sendiri.
     * Inilah satu-satunya tindakan di aplikasi ini yang tidak dapat
     * dibatalkan, sehingga bila kelak ia perlu dibatasi — misalnya hanya
     * kepala prodi, atau hanya super admin — perubahannya cukup di method
     * ini, tanpa menyentuh controller mana pun.
     */
    public function forceDelete(User $pengguna, Book $buku): Response
    {
        return $buku->bolehDikelolaOleh($pengguna)
            ? Response::allow()
            : Response::deny('Anda tidak berhak melenyapkan buku ini.');
    }

    /** Menerbitkan atau menarik terbit sebuah buku. */
    public function terbitkan(User $pengguna, Book $buku): Response
    {
        return $this->update($pengguna, $buku);
    }
}