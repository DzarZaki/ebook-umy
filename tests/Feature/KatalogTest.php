<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Category;
use App\Models\Prodi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_tamu_diarahkan_ke_halaman_masuk(): void
    {
        $this->get('/katalog')->assertRedirect('/login');
    }

    public function test_mahasiswa_hanya_melihat_buku_prodinya_dan_umum(): void
    {
        $prodiA = Prodi::factory()->create();
        $prodiB = Prodi::factory()->create();
        $mahasiswa = User::factory()->mahasiswa($prodiA)->create();

        Book::factory()->create(['title' => 'Buku Prodi A', 'prodi_id' => $prodiA->id]);
        Book::factory()->create(['title' => 'Buku Prodi B', 'prodi_id' => $prodiB->id]);
        Book::factory()->create(['title' => 'Buku Umum']);

        $this->actingAs($mahasiswa)
            ->get('/katalog')
            ->assertOk()
            ->assertSee('Buku Prodi A')
            ->assertSee('Buku Umum')
            ->assertDontSee('Buku Prodi B');
    }

    public function test_buku_draf_tidak_tampil_di_katalog(): void
    {
        $mahasiswa = User::factory()->mahasiswa()->create();
        Book::factory()->create(['title' => 'Naskah Belum Selesai', 'is_published' => false]);

        $this->actingAs($mahasiswa)
            ->get('/katalog')
            ->assertDontSee('Naskah Belum Selesai');
    }

    public function test_pencarian_menyaring_berdasarkan_judul(): void
    {
        $mahasiswa = User::factory()->mahasiswa()->create();
        Book::factory()->create(['title' => 'Manajemen Operasional']);
        Book::factory()->create(['title' => 'Sejarah Peradaban']);

        $this->actingAs($mahasiswa)
            ->get('/katalog?q=operasional')
            ->assertSee('Manajemen Operasional')
            ->assertDontSee('Sejarah Peradaban');
    }

    public function test_penyaring_kategori_bekerja(): void
    {
        $mahasiswa = User::factory()->mahasiswa()->create();
        $kategori = Category::factory()->create(['name' => 'Modul']);

        Book::factory()->create(['title' => 'Buku Bermodul', 'category_id' => $kategori->id]);
        Book::factory()->create(['title' => 'Buku Tanpa Kategori']);

        $this->actingAs($mahasiswa)
            ->get('/katalog?kategori='.$kategori->id)
            ->assertSee('Buku Bermodul')
            ->assertDontSee('Buku Tanpa Kategori');
    }

    public function test_mahasiswa_dapat_membuka_detail_buku_umum(): void
    {
        $mahasiswa = User::factory()->mahasiswa()->create();
        $buku = Book::factory()->create(['title' => 'Etika Profesi']);

        $this->actingAs($mahasiswa)
            ->get(route('katalog.show', $buku))
            ->assertOk()
            ->assertSee('Etika Profesi');
    }

    public function test_detail_buku_prodi_lain_menghasilkan_404(): void
    {
        $prodiLain = Prodi::factory()->create();
        $mahasiswa = User::factory()->mahasiswa()->create();
        $buku = Book::factory()->create(['prodi_id' => $prodiLain->id]);

        $this->actingAs($mahasiswa)
            ->get(route('katalog.show', $buku))
            ->assertNotFound();
    }

    public function test_super_admin_melihat_seluruh_koleksi(): void
    {
        $prodiA = Prodi::factory()->create();
        $prodiB = Prodi::factory()->create();
        $superAdmin = User::factory()->superAdmin()->create();

        Book::factory()->create(['title' => 'Buku Prodi A', 'prodi_id' => $prodiA->id]);
        Book::factory()->create(['title' => 'Buku Prodi B', 'prodi_id' => $prodiB->id]);

        $this->actingAs($superAdmin)
            ->get('/katalog')
            ->assertSee('Buku Prodi A')
            ->assertSee('Buku Prodi B');
    }

    public function test_mahasiswa_diarahkan_dari_dashboard_ke_katalog(): void
    {
        $this->actingAs(User::factory()->mahasiswa()->create())
            ->get('/dashboard')
            ->assertRedirect(route('katalog.index'));
    }
}
