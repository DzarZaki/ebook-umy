<?php

namespace Tests\Feature\Admin;

use App\Models\Book;
use App\Models\Category;
use App\Models\Prodi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KategoriCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_mahasiswa_tidak_boleh_masuk_area_dosen(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin/kategori')
            ->assertForbidden();
    }

    public function test_dosen_dapat_menambah_kategori_prodi(): void
    {
        $prodi = Prodi::factory()->create();
        $dosen = User::factory()->admin($prodi)->create();

        $this->actingAs($dosen)
            ->post('/admin/kategori', ['name' => 'Modul Pelajaran', 'lingkup' => 'prodi'])
            ->assertRedirect(route('admin.kategori.index'));

        $this->assertDatabaseHas('categories', [
            'name' => 'Modul Pelajaran',
            'prodi_id' => $prodi->id,
            'created_by' => $dosen->id,
        ]);
    }

    public function test_dosen_dapat_menambah_kategori_umum(): void
    {
        $dosen = User::factory()->admin()->create();

        $this->actingAs($dosen)
            ->post('/admin/kategori', ['name' => 'Artikel Lepas', 'lingkup' => 'umum']);

        $this->assertDatabaseHas('categories', ['name' => 'Artikel Lepas', 'prodi_id' => null]);
    }

    public function test_dosen_hanya_melihat_kategori_prodinya_dan_umum(): void
    {
        $prodiA = Prodi::factory()->create();
        $prodiB = Prodi::factory()->create();
        $dosen = User::factory()->admin($prodiA)->create();

        Category::factory()->create(['name' => 'Kategori Prodi A', 'prodi_id' => $prodiA->id]);
        Category::factory()->create(['name' => 'Kategori Prodi B', 'prodi_id' => $prodiB->id]);
        Category::factory()->create(['name' => 'Kategori Umum']);

        $this->actingAs($dosen)
            ->get('/admin/kategori')
            ->assertSee('Kategori Prodi A')
            ->assertSee('Kategori Umum')
            ->assertDontSee('Kategori Prodi B');
    }

    public function test_dosen_tidak_dapat_mengubah_kategori_prodi_lain(): void
    {
        $prodiLain = Prodi::factory()->create();
        $kategori = Category::factory()->create(['prodi_id' => $prodiLain->id]);
        $dosen = User::factory()->admin()->create();

        $this->actingAs($dosen)
            ->get(route('admin.kategori.edit', $kategori))
            ->assertForbidden();
    }

    public function test_kategori_yang_dipakai_buku_tidak_dapat_dihapus(): void
    {
        $prodi = Prodi::factory()->create();
        $dosen = User::factory()->admin($prodi)->create();
        $kategori = Category::factory()->create(['prodi_id' => $prodi->id]);
        Book::factory()->create(['category_id' => $kategori->id, 'prodi_id' => $prodi->id]);

        $this->actingAs($dosen)
            ->delete(route('admin.kategori.destroy', $kategori))
            ->assertSessionHasErrors('kategori');
    }
}
