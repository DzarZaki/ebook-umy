<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\Prodi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProdiCrudTest extends TestCase
{
    use RefreshDatabase;

    /** Membuat akun Super Admin untuk pengujian. */
    private function superAdmin(): User
    {
        return User::factory()->superAdmin()->create();
    }

    public function test_pengguna_non_superadmin_ditolak(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/superadmin/prodi')
            ->assertForbidden();
    }

    public function test_superadmin_melihat_daftar_prodi(): void
    {
        Prodi::factory()->create(['name' => 'Teknik Informatika', 'slug' => 'teknik-informatika']);

        $this->actingAs($this->superAdmin())
            ->get('/superadmin/prodi')
            ->assertOk()
            ->assertSee('Teknik Informatika');
    }

    public function test_superadmin_dapat_menambah_prodi(): void
    {
        $this->actingAs($this->superAdmin())
            ->post('/superadmin/prodi', ['name' => 'Akuntansi'])
            ->assertRedirect(route('superadmin.prodi.index'));

        $this->assertDatabaseHas('prodi', ['name' => 'Akuntansi', 'slug' => 'akuntansi']);
    }

    public function test_nama_prodi_wajib_unik(): void
    {
        Prodi::factory()->create(['name' => 'Akuntansi', 'slug' => 'akuntansi']);

        $this->actingAs($this->superAdmin())
            ->post('/superadmin/prodi', ['name' => 'Akuntansi'])
            ->assertSessionHasErrors('name');
    }

    public function test_superadmin_dapat_mengubah_prodi(): void
    {
        $prodi = Prodi::factory()->create(['name' => 'Akuntansi', 'slug' => 'akuntansi']);

        $this->actingAs($this->superAdmin())
            ->put(route('superadmin.prodi.update', $prodi), ['name' => 'Akuntansi Syariah'])
            ->assertRedirect(route('superadmin.prodi.index'));

        $this->assertDatabaseHas('prodi', ['id' => $prodi->id, 'slug' => 'akuntansi-syariah']);
    }

    public function test_prodi_yang_masih_punya_pengguna_tidak_dapat_dihapus(): void
    {
        $prodi = Prodi::factory()->create();
        User::factory()->mahasiswa($prodi)->create();

        $this->actingAs($this->superAdmin())
            ->delete(route('superadmin.prodi.destroy', $prodi))
            ->assertSessionHasErrors('prodi');

        $this->assertDatabaseHas('prodi', ['id' => $prodi->id]);
    }

    public function test_prodi_kosong_dapat_dihapus(): void
    {
        $prodi = Prodi::factory()->create();

        $this->actingAs($this->superAdmin())
            ->delete(route('superadmin.prodi.destroy', $prodi));

        $this->assertDatabaseMissing('prodi', ['id' => $prodi->id]);
    }
}
