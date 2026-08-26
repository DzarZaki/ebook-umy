<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Prodi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tombol terbit/tarik di daftar buku.
 *
 * Draf yang belum diterbitkan tidak boleh terlihat oleh mahasiswa mana
 * pun, dan hanya pengelola sahnya yang dapat mengubah keadaan terbit.
 */
class TerbitBukuTest extends TestCase
{
    use RefreshDatabase;

    public function test_dosen_menerbitkan_buku_draf_prodinya(): void
    {
        [$dosen, $buku] = $this->bukuDraf();

        $this->actingAs($dosen)
            ->patch(route('admin.buku.terbit', $buku), ['is_published' => '1'])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('books', ['id' => $buku->id, 'is_published' => true]);
    }

    public function test_draf_tersembunyi_dari_mahasiswa_hingga_diterbitkan(): void
    {
        $prodi = Prodi::factory()->create();
        $dosen = User::factory()->admin($prodi)->create();
        $mahasiswa = User::factory()->mahasiswa($prodi)->create();
        $buku = Book::factory()->create([
            'prodi_id' => $prodi->id,
            'uploaded_by' => $dosen->id,
            'is_published' => false,
        ]);

        $this->actingAs($mahasiswa)
            ->get(route('katalog.show', $buku))
            ->assertNotFound();

        $this->actingAs($dosen)
            ->patch(route('admin.buku.terbit', $buku), ['is_published' => '1']);

        $this->actingAs($mahasiswa)
            ->get(route('katalog.show', $buku))
            ->assertOk();
    }

    public function test_menarik_terbit_menyembunyikan_buku_dari_mahasiswa(): void
    {
        $prodi = Prodi::factory()->create();
        $dosen = User::factory()->admin($prodi)->create();
        $mahasiswa = User::factory()->mahasiswa($prodi)->create();
        $buku = Book::factory()->create([
            'prodi_id' => $prodi->id,
            'uploaded_by' => $dosen->id,
            'is_published' => true,
        ]);

        $this->actingAs($dosen)
            ->patch(route('admin.buku.terbit', $buku), ['is_published' => '0'])
            ->assertRedirect()
            ->assertSessionHas('status', "Buku “{$buku->title}” ditarik dari terbitan.");

        $this->assertDatabaseHas('books', ['id' => $buku->id, 'is_published' => false]);

        $this->actingAs($mahasiswa)
            ->get(route('katalog.show', $buku))
            ->assertNotFound();
    }

    public function test_dosen_prodi_lain_tidak_berwenang_menerbitkan(): void
    {
        [$dosen, $buku] = $this->bukuDraf();
        $dosenLain = User::factory()->admin()->create();

        $this->actingAs($dosenLain)
            ->patch(route('admin.buku.terbit', $buku), ['is_published' => '1'])
            ->assertForbidden();

        $this->assertDatabaseHas('books', ['id' => $buku->id, 'is_published' => false]);
    }

    public function test_nilai_yang_tidak_jelas_ditolak_validasi(): void
    {
        [$dosen, $buku] = $this->bukuDraf();

        $this->actingAs($dosen)
            ->from(route('admin.dashboard'))
            ->patch(route('admin.buku.terbit', $buku), ['is_published' => 'mungkin'])
            ->assertSessionHasErrors('is_published');
    }

    /**
     * Buku draf milik dosen pembuatnya.
     *
     * @return array{0: User, 1: Book}
     */
    private function bukuDraf(): array
    {
        $dosen = User::factory()->admin()->create();
        $buku = Book::factory()->create([
            'prodi_id' => $dosen->prodi_id,
            'uploaded_by' => $dosen->id,
            'is_published' => false,
        ]);

        return [$dosen, $buku];
    }
}
