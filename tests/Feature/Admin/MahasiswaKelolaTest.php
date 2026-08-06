<?php

namespace Tests\Feature\Admin;

use App\Models\Prodi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MahasiswaKelolaTest extends TestCase
{
    use RefreshDatabase;

    public function test_dosen_hanya_melihat_mahasiswa_prodinya(): void
    {
        $prodiA = Prodi::factory()->create();
        $prodiB = Prodi::factory()->create();

        $dosen = User::factory()->admin($prodiA)->create();
        $milikKita = User::factory()->mahasiswa($prodiA)->create(['name' => 'Intan Kusuma']);
        $prodiLain = User::factory()->mahasiswa($prodiB)->create(['name' => 'Fajar Nugroho']);

        $this->actingAs($dosen)
            ->get(route('admin.mahasiswa.index'))
            ->assertOk()
            ->assertSee($milikKita->name)
            ->assertDontSee($prodiLain->name);
    }

    public function test_dosen_tidak_dapat_mengubah_mahasiswa_prodi_lain(): void
    {
        $prodiA = Prodi::factory()->create();
        $prodiB = Prodi::factory()->create();

        $dosen = User::factory()->admin($prodiA)->create();
        $mahasiswa = User::factory()->mahasiswa($prodiB)->create();

        $this->actingAs($dosen)
            ->get(route('admin.mahasiswa.edit', $mahasiswa))
            ->assertForbidden();
    }

    public function test_dosen_dapat_memindahkan_mahasiswa_ke_prodi_lain(): void
    {
        $prodiA = Prodi::factory()->create();
        $prodiB = Prodi::factory()->create();

        $dosen = User::factory()->admin($prodiA)->create();
        $mahasiswa = User::factory()->mahasiswa($prodiA)->create();

        $this->actingAs($dosen)
            ->put(route('admin.mahasiswa.update', $mahasiswa), [
                'name' => 'Nurul Safitri',
                'email' => 'nurul.safitri@gmail.com',
                'prodi_id' => $prodiB->id,
            ])
            ->assertRedirect(route('admin.mahasiswa.index'));

        $this->assertDatabaseHas('users', [
            'id' => $mahasiswa->id,
            'prodi_id' => $prodiB->id,
        ]);
    }

    public function test_dosen_dapat_menonaktifkan_mahasiswa(): void
    {
        $prodi = Prodi::factory()->create();
        $dosen = User::factory()->admin($prodi)->create();
        $mahasiswa = User::factory()->mahasiswa($prodi)->create();

        $this->actingAs($dosen)
            ->patch(route('admin.mahasiswa.status', $mahasiswa))
            ->assertRedirect();

        $this->assertFalse($mahasiswa->fresh()->is_active);
    }

    public function test_dosen_dapat_menghapus_mahasiswa(): void
    {
        $prodi = Prodi::factory()->create();
        $dosen = User::factory()->admin($prodi)->create();
        $mahasiswa = User::factory()->mahasiswa($prodi)->create();

        $this->actingAs($dosen)
            ->delete(route('admin.mahasiswa.destroy', $mahasiswa))
            ->assertRedirect(route('admin.mahasiswa.index'));

        $this->assertDatabaseMissing('users', ['id' => $mahasiswa->id]);
    }

    public function test_dosen_dapat_memperbarui_kode_akses_prodinya(): void
    {
        $prodi = Prodi::factory()->create(['access_code' => 'LAMA-2025']);
        $dosen = User::factory()->admin($prodi)->create();

        $this->actingAs($dosen)
            ->patch(route('admin.kode-akses.update'), ['access_code' => 'baru-2026'])
            ->assertRedirect();

        $this->assertSame('BARU-2026', $prodi->fresh()->access_code);
    }

    public function test_kode_akses_dengan_simbol_ditolak(): void
    {
        $prodi = Prodi::factory()->create();
        $dosen = User::factory()->admin($prodi)->create();

        $this->actingAs($dosen)
            ->patch(route('admin.kode-akses.update'), ['access_code' => 'PAI@2026!'])
            ->assertSessionHasErrors('access_code');
    }
}
