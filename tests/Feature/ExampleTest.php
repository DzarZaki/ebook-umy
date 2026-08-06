<?php

namespace Tests\Feature;

use App\Models\Prodi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    // Halaman depan membaca daftar prodi, jadi skema database perlu disiapkan.
    use RefreshDatabase;

    public function test_halaman_depan_dapat_diakses(): void
    {
        $this->get('/')->assertStatus(200);
    }

    public function test_halaman_depan_menampilkan_daftar_prodi(): void
    {
        Prodi::factory()->create(['name' => 'Manajemen', 'slug' => 'manajemen']);

        $this->get('/')
            ->assertOk()
            ->assertSee('Manajemen');
    }

    public function test_halaman_depan_tetap_tampil_tanpa_prodi(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Belum ada program studi terdaftar.');
    }
}
