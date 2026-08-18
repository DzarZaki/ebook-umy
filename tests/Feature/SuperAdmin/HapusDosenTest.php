<?php

declare(strict_types=1);

namespace Tests\Feature\SuperAdmin;

use App\Models\Book;
use App\Models\Prodi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Menjaga agar koleksi perpustakaan tidak bergantung pada masa kerja
 * seorang dosen.
 *
 * Sebelum perbaikan ini, books.uploaded_by memakai cascadeOnDelete: satu
 * penghapusan akun melenyapkan seluruh buku dosen itu di luar Eloquent,
 * beserta progres baca, penanda, koleksi tersimpan, dan riwayat unduhan
 * milik semua mahasiswa.
 */
class HapusDosenTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        return User::factory()->superAdmin()->create();
    }

    public function test_dosen_yang_masih_memiliki_buku_tidak_dapat_dihapus(): void
    {
        $prodi = Prodi::factory()->create();
        $dosen = User::factory()->admin($prodi)->create();

        $buku = Book::factory()->create([
            'prodi_id' => $prodi->id,
            'uploaded_by' => $dosen->id,
        ]);

        $this->actingAs($this->superAdmin())
            ->delete(route('superadmin.dosen.destroy', $dosen))
            ->assertSessionHasErrors('dosen');

        $this->assertDatabaseHas('users', ['id' => $dosen->id]);
        $this->assertDatabaseHas('books', ['id' => $buku->id, 'uploaded_by' => $dosen->id]);
    }

       public function test_buku_di_tempat_sampah_pun_ikut_menahan_penghapusan(): void
    {
        $prodi = Prodi::factory()->create();
        $dosen = User::factory()->admin($prodi)->create();

        $buku = Book::factory()->create([
            'prodi_id' => $prodi->id,
            'uploaded_by' => $dosen->id,
        ]);

        // Buku yang dibuang masih dapat dipulihkan selama masa tenggang,
        // jadi ia belum kehilangan hak atas pengunggahnya.
        $buku->delete();

        $this->assertSame(1, Book::withTrashed()->where('uploaded_by', $dosen->id)->count());
        $this->assertSame(0, Book::where('uploaded_by', $dosen->id)->count());

        $this->actingAs($this->superAdmin())
            ->delete(route('superadmin.dosen.destroy', $dosen))
            ->assertSessionHasErrors('dosen');

        $this->assertDatabaseHas('users', ['id' => $dosen->id]);
    }

    public function test_dosen_tanpa_buku_tetap_dapat_dihapus(): void
    {
        $dosen = User::factory()->admin()->create();

        $this->actingAs($this->superAdmin())
            ->delete(route('superadmin.dosen.destroy', $dosen))
            ->assertRedirect(route('superadmin.dosen.index'));

        $this->assertDatabaseMissing('users', ['id' => $dosen->id]);
    }

    public function test_buku_selamat_ketika_akun_pengunggahnya_terhapus_di_luar_controller(): void
    {
        // Penjagaan di controller dapat dilewati: tinker, seeder, atau kode
        // baru di kemudian hari. Yang diuji di sini adalah jaring pengaman
        // lapis database, bukan lapis aplikasi.
        $this->assertTrue(
            (bool) DB::selectOne('PRAGMA foreign_keys')->foreign_keys ?? false,
            'Penjagaan foreign key harus aktif, jika tidak pengujian ini tidak membuktikan apa pun.'
        );

        $prodi = Prodi::factory()->create();
        $dosen = User::factory()->admin($prodi)->create();

        $buku = Book::factory()->create([
            'prodi_id' => $prodi->id,
            'uploaded_by' => $dosen->id,
        ]);

        $dosen->delete();

        $this->assertDatabaseMissing('users', ['id' => $dosen->id]);

        // Inti perbaikannya: barisnya tetap ada, hanya pengunggahnya kosong.
        $this->assertDatabaseHas('books', ['id' => $buku->id, 'uploaded_by' => null]);
    }

    public function test_buku_umum_tanpa_pengunggah_tidak_dapat_diklaim_dosen_lain(): void
    {
        $prodi = Prodi::factory()->create();
        $dosen = User::factory()->admin($prodi)->create();

        // Buku umum: hak kelolanya ditentukan oleh uploaded_by, bukan prodi.
        $buku = Book::factory()->create([
            'prodi_id' => null,
            'uploaded_by' => User::factory()->admin()->create()->id,
        ]);

        $buku->update(['uploaded_by' => null]);

        $this->assertFalse(
            $buku->fresh()->bolehDikelolaOleh($dosen),
            'Buku umum yatim tidak boleh jatuh ke tangan dosen mana pun kecuali Super Admin.'
        );
    }
}