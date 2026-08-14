<?php

namespace Tests\Feature\Admin;

use App\Models\Book;
use App\Models\Prodi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SampahBukuTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');
    }

    /** Buku yang sudah dibuang ke tempat sampah, lengkap dengan berkas nyata. */
    private function bukuTerbuang(array $ubah = []): Book
    {
        $buku = Book::factory()->create(array_merge([
            'file_path' => 'books/'.uniqid().'.pdf',
            'cover_path' => 'covers/'.uniqid().'.jpg',
        ], $ubah));

        Storage::disk('local')->put($buku->file_path, 'isi pdf tiruan');
        Storage::disk('public')->put($buku->cover_path, 'isi sampul tiruan');

        $buku->delete();

        return $buku;
    }

    public function test_dosen_melihat_buku_terbuang_prodinya_saja(): void
    {
        $prodi = Prodi::factory()->create();
        $dosen = User::factory()->admin($prodi)->create();

        $this->bukuTerbuang([
            'prodi_id' => $prodi->id,
            'uploaded_by' => $dosen->id,
            'title' => 'Statistika Dasar',
        ]);

        $this->bukuTerbuang([
            'prodi_id' => Prodi::factory()->create()->id,
            'title' => 'Anatomi Manusia',
        ]);

        $this->actingAs($dosen)
            ->get(route('admin.buku-sampah.index'))
            ->assertOk()
            ->assertSee('Statistika Dasar')
            ->assertDontSee('Anatomi Manusia');
    }

    /**
     * Buku umum boleh dilihat semua dosen di halaman koleksi, tetapi hanya
     * pengunggahnya yang boleh mengelolanya. Tempat sampah mengikuti aturan
     * pengelolaan, bukan aturan tampilan.
     */
    public function test_buku_umum_milik_dosen_lain_tidak_tampil_di_tempat_sampah(): void
    {
        $prodi = Prodi::factory()->create();
        $dosen = User::factory()->admin($prodi)->create();

        $this->bukuTerbuang([
            'prodi_id' => null,
            'uploaded_by' => $dosen->id,
            'title' => 'Metodologi Penelitian',
        ]);

        $this->bukuTerbuang([
            'prodi_id' => null,
            'title' => 'Kalkulus Lanjut',
        ]);

        $this->actingAs($dosen)
            ->get(route('admin.buku-sampah.index'))
            ->assertOk()
            ->assertSee('Metodologi Penelitian')
            ->assertDontSee('Kalkulus Lanjut');
    }

    public function test_dosen_dapat_memulihkan_bukunya(): void
    {
        $prodi = Prodi::factory()->create();
        $dosen = User::factory()->admin($prodi)->create();

        $buku = $this->bukuTerbuang(['prodi_id' => $prodi->id, 'uploaded_by' => $dosen->id]);

        $this->actingAs($dosen)
            ->patch(route('admin.buku-sampah.pulihkan', $buku))
            ->assertRedirect(route('admin.buku.index'));

        $this->assertDatabaseHas('books', ['id' => $buku->id, 'deleted_at' => null]);
        Storage::disk('local')->assertExists($buku->file_path);
    }

    public function test_memulihkan_dua_kali_tidak_menimbulkan_galat(): void
    {
        $prodi = Prodi::factory()->create();
        $dosen = User::factory()->admin($prodi)->create();

        $buku = $this->bukuTerbuang(['prodi_id' => $prodi->id, 'uploaded_by' => $dosen->id]);

        $this->actingAs($dosen)->patch(route('admin.buku-sampah.pulihkan', $buku));

        // Tombol yang tertekan dua kali cukup dijawab dengan pesan, bukan galat.
        $this->actingAs($dosen)
            ->patch(route('admin.buku-sampah.pulihkan', $buku))
            ->assertRedirect(route('admin.buku-sampah.index'));

        $this->assertDatabaseHas('books', ['id' => $buku->id, 'deleted_at' => null]);
    }

    public function test_dosen_tidak_dapat_memulihkan_buku_prodi_lain(): void
    {
        $dosen = User::factory()->admin(Prodi::factory()->create())->create();

        $buku = $this->bukuTerbuang(['prodi_id' => Prodi::factory()->create()->id]);

        $this->actingAs($dosen)
            ->patch(route('admin.buku-sampah.pulihkan', $buku))
            ->assertForbidden();

        $this->assertSoftDeleted('books', ['id' => $buku->id]);
    }

    public function test_hapus_permanen_melenyapkan_baris_beserta_berkasnya(): void
    {
        $prodi = Prodi::factory()->create();
        $dosen = User::factory()->admin($prodi)->create();

        $buku = $this->bukuTerbuang(['prodi_id' => $prodi->id, 'uploaded_by' => $dosen->id]);

        $this->actingAs($dosen)
            ->delete(route('admin.buku-sampah.hapus', $buku))
            ->assertRedirect(route('admin.buku-sampah.index'));

        $this->assertDatabaseMissing('books', ['id' => $buku->id]);
        Storage::disk('local')->assertMissing($buku->file_path);
        Storage::disk('public')->assertMissing($buku->cover_path);
    }

    /**
     * Penjaga terpenting di berkas ini: alamat tebakan tidak boleh menjadi
     * jalan pintas untuk melenyapkan koleksi prodi lain.
     */
    public function test_dosen_tidak_dapat_melenyapkan_buku_prodi_lain(): void
    {
        $dosen = User::factory()->admin(Prodi::factory()->create())->create();

        $buku = $this->bukuTerbuang(['prodi_id' => Prodi::factory()->create()->id]);

        $this->actingAs($dosen)
            ->delete(route('admin.buku-sampah.hapus', $buku))
            ->assertForbidden();

        $this->assertSoftDeleted('books', ['id' => $buku->id]);
        Storage::disk('local')->assertExists($buku->file_path);
        Storage::disk('public')->assertExists($buku->cover_path);
    }

    /** Buku yang masih hidup tidak boleh dilenyapkan lewat pintu tempat sampah. */
    public function test_buku_yang_masih_hidup_tidak_dapat_dilenyapkan(): void
    {
        $prodi = Prodi::factory()->create();
        $dosen = User::factory()->admin($prodi)->create();

        $buku = Book::factory()->create([
            'prodi_id' => $prodi->id,
            'uploaded_by' => $dosen->id,
            'file_path' => 'books/masih-hidup.pdf',
        ]);

        Storage::disk('local')->put($buku->file_path, 'isi pdf tiruan');

        $this->actingAs($dosen)
            ->delete(route('admin.buku-sampah.hapus', $buku))
            ->assertNotFound();

        $this->assertDatabaseHas('books', ['id' => $buku->id, 'deleted_at' => null]);
        Storage::disk('local')->assertExists($buku->file_path);
    }

    public function test_mahasiswa_tidak_dapat_membuka_tempat_sampah(): void
    {
        $prodi = Prodi::factory()->create();
        $mahasiswa = User::factory()->mahasiswa($prodi)->create();

        $this->actingAs($mahasiswa)
            ->get(route('admin.buku-sampah.index'))
            ->assertForbidden();
    }
}