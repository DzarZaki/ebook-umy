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

    public function test_slug_kategori_unik_dalam_satu_prodi(): void
    {
        $prodi = Prodi::factory()->create();
        $dosen = User::factory()->admin($prodi)->create();

        // POST pertama — slug dasar
        $this->actingAs($dosen)
            ->post('/admin/kategori', ['name' => 'Pemrograman Web', 'lingkup' => 'prodi'])
            ->assertRedirect(route('admin.kategori.index'));

        // POST kedua — nama identik, prodi sama → harus dapat sufiks -2
        $this->actingAs($dosen)
            ->post('/admin/kategori', ['name' => 'Pemrograman Web', 'lingkup' => 'prodi'])
            ->assertRedirect(route('admin.kategori.index'));

        $this->assertDatabaseHas('categories', ['slug' => 'pemrograman-web', 'prodi_id' => $prodi->id]);
        $this->assertDatabaseHas('categories', ['slug' => 'pemrograman-web-2', 'prodi_id' => $prodi->id]);
        $this->assertSame(2, Category::where('prodi_id', $prodi->id)->count());
    }

    public function test_slug_sama_boleh_lintas_prodi(): void
    {
        $prodiA = Prodi::factory()->create();
        $prodiB = Prodi::factory()->create();
        $dosenA = User::factory()->admin($prodiA)->create();
        $dosenB = User::factory()->admin($prodiB)->create();

        $this->actingAs($dosenA)
            ->post('/admin/kategori', ['name' => 'Modul Ajar', 'lingkup' => 'prodi']);

        $this->actingAs($dosenB)
            ->post('/admin/kategori', ['name' => 'Modul Ajar', 'lingkup' => 'prodi']);

        // Unique index bersifat komposit (prodi_id, slug), bukan global.
        // Kedua prodi boleh punya slug yang sama.
        $this->assertDatabaseHas('categories', ['slug' => 'modul-ajar', 'prodi_id' => $prodiA->id]);
        $this->assertDatabaseHas('categories', ['slug' => 'modul-ajar', 'prodi_id' => $prodiB->id]);
    }

    public function test_slug_unik_saat_update(): void
    {
        $prodi = Prodi::factory()->create();
        $dosen = User::factory()->admin($prodi)->create();

        // Buat dua kategori berbeda
        $this->actingAs($dosen)
            ->post('/admin/kategori', ['name' => 'Artikel Ilmiah', 'lingkup' => 'prodi']);
        $this->actingAs($dosen)
            ->post('/admin/kategori', ['name' => 'Modul Praktikum', 'lingkup' => 'prodi']);

        $kategoriB = Category::where('slug', 'modul-praktikum')->first();

        // Rename kategori B menjadi nama yang sudah dipakai kategori A
        $this->actingAs($dosen)
            ->put(route('admin.kategori.update', $kategoriB), [
                'name' => 'Artikel Ilmiah',
            ])
            ->assertRedirect(route('admin.kategori.index'));

        // Slug harus jadi artikel-ilmiah-2, bukan error 500
        $this->assertDatabaseHas('categories', ['id' => $kategoriB->id, 'slug' => 'artikel-ilmiah-2']);
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
