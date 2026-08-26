<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\Prodi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Rute `superadmin/dosen/{user}` menerima id pengguna apa pun, jadi setiap
 * metode yang mengubah data harus menolak akun yang bukan berperan `admin`.
 */
class AkunBukanDosenTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        return User::factory()->superAdmin()->create();
    }

    public function test_akun_mahasiswa_tidak_dapat_dibuka_lewat_formulir_dosen(): void
    {
        $mahasiswa = User::factory()->mahasiswa()->create();

        $this->actingAs($this->superAdmin())
            ->get(route('superadmin.dosen.edit', $mahasiswa))
            ->assertNotFound();
    }

    public function test_akun_mahasiswa_tidak_dapat_diubah_lewat_rute_dosen(): void
    {
        $prodiLain = Prodi::factory()->create();
        $mahasiswa = User::factory()->mahasiswa()->create();

        $this->actingAs($this->superAdmin())
            ->put(route('superadmin.dosen.update', $mahasiswa), [
                'name' => 'Nama Disusupi',
                'email' => 'penyusup@gmail.com',
                'prodi_id' => $prodiLain->id,
                'is_active' => 0,
            ])
            ->assertNotFound();

        $this->assertDatabaseHas('users', [
            'id' => $mahasiswa->id,
            'name' => $mahasiswa->name,
            'email' => $mahasiswa->email,
            'role' => User::ROLE_MAHASISWA,
            'is_active' => true,
        ]);
    }

    public function test_akun_mahasiswa_tidak_dapat_dihapus_lewat_rute_dosen(): void
    {
        $mahasiswa = User::factory()->mahasiswa()->create();

        $this->actingAs($this->superAdmin())
            ->delete(route('superadmin.dosen.destroy', $mahasiswa))
            ->assertNotFound();

        $this->assertDatabaseHas('users', ['id' => $mahasiswa->id]);
    }

    public function test_akun_super_admin_lain_tidak_dapat_dihapus_lewat_rute_dosen(): void
    {
        $rekan = User::factory()->superAdmin()->create();

        $this->actingAs($this->superAdmin())
            ->delete(route('superadmin.dosen.destroy', $rekan))
            ->assertNotFound();

        $this->assertDatabaseHas('users', ['id' => $rekan->id]);
    }

    /** Penjagaan baru tidak boleh ikut menghalangi pekerjaan yang sah. */
    public function test_akun_dosen_tetap_dapat_dihapus(): void
    {
        $dosen = User::factory()->admin()->create();

        $this->actingAs($this->superAdmin())
            ->delete(route('superadmin.dosen.destroy', $dosen))
            ->assertRedirect(route('superadmin.dosen.index'));

        $this->assertDatabaseMissing('users', ['id' => $dosen->id]);
    }
}
