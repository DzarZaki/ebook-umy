<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\Prodi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DosenCrudTest extends TestCase
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
            ->get('/superadmin/dosen')
            ->assertForbidden();
    }

    public function test_superadmin_dapat_membuat_akun_dosen(): void
    {
        $prodi = Prodi::factory()->create();

        $this->actingAs($this->superAdmin())
            ->post('/superadmin/dosen', [
                'name' => 'Dosen Baru',
                'email' => 'dosen.baru@umy.ac.id',
                'prodi_id' => $prodi->id,
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertRedirect(route('superadmin.dosen.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'dosen.baru@umy.ac.id',
            'role' => User::ROLE_ADMIN,
            'prodi_id' => $prodi->id,
            'is_active' => true,
        ]);
    }

    public function test_email_dosen_wajib_domain_umy(): void
    {
        $prodi = Prodi::factory()->create();

        $this->actingAs($this->superAdmin())
            ->post('/superadmin/dosen', [
                'name' => 'Dosen Baru',
                'email' => 'dosen@gmail.com',
                'prodi_id' => $prodi->id,
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_superadmin_dapat_mengubah_prodi_dosen(): void
    {
        $lama = Prodi::factory()->create();
        $baru = Prodi::factory()->create();
        $dosen = User::factory()->admin($lama)->create();

        $this->actingAs($this->superAdmin())
            ->put(route('superadmin.dosen.update', $dosen), [
                'name' => $dosen->name,
                'email' => $dosen->email,
                'prodi_id' => $baru->id,
                'is_active' => 1,
            ])
            ->assertRedirect(route('superadmin.dosen.index'));

        $this->assertDatabaseHas('users', ['id' => $dosen->id, 'prodi_id' => $baru->id]);
    }

    public function test_superadmin_dapat_menonaktifkan_dosen(): void
    {
        $dosen = User::factory()->admin()->create();

        $this->actingAs($this->superAdmin())
            ->put(route('superadmin.dosen.update', $dosen), [
                'name' => $dosen->name,
                'email' => $dosen->email,
                'prodi_id' => $dosen->prodi_id,
                'is_active' => 0,
            ]);

        $this->assertDatabaseHas('users', ['id' => $dosen->id, 'is_active' => false]);
    }

    public function test_superadmin_dapat_menghapus_dosen(): void
    {
        $dosen = User::factory()->admin()->create();

        $this->actingAs($this->superAdmin())
            ->delete(route('superadmin.dosen.destroy', $dosen));

        $this->assertDatabaseMissing('users', ['id' => $dosen->id]);
    }
}
