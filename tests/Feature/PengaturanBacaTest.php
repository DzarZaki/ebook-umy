<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sakelar kebijakan bacaan dari dashboard dosen.
 *
 * Dua sakelar ini menggantikan pengaturan .env lama; keduanya milik prodi
 * dan hanya bisa diubah dosen pengelola atau Super Admin.
 */
class PengaturanBacaTest extends TestCase
{
    use RefreshDatabase;

    public function test_dosen_mematikan_stempel_baca_prodinya(): void
    {
        $dosen = User::factory()->admin()->create();

        $this->actingAs($dosen)
            ->from(route('admin.dashboard'))
            ->patch(route('admin.pengaturan-baca.update'), [
                'sakelar' => 'baca_stempel',
                'nilai' => '0',
            ])
            ->assertRedirect()
            ->assertSessionHas('status', "Stempel identitas bacaan untuk {$dosen->prodi->name} telah dinonaktifkan.");

        $this->assertDatabaseHas('prodi', [
            'id' => $dosen->prodi_id,
            'baca_stempel' => false,
        ]);
    }

    public function test_dosen_menyalakan_batas_baca_mengikuti_rentang(): void
    {
        $dosen = User::factory()->admin()->create();

        $this->actingAs($dosen)
            ->patch(route('admin.pengaturan-baca.update'), [
                'sakelar' => 'baca_ikuti_rentang',
                'nilai' => '1',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('prodi', [
            'id' => $dosen->prodi_id,
            'baca_ikuti_rentang' => true,
        ]);
    }

    public function test_sakelar_tak_dikenal_ditolak(): void
    {
        $dosen = User::factory()->admin()->create();

        // Nama kolom dibatasi daftar; kalau tidak, permintaan liar bisa
        // menulis kolom prodi mana pun.
        $this->actingAs($dosen)
            ->patch(route('admin.pengaturan-baca.update'), [
                'sakelar' => 'download_enabled',
                'nilai' => '1',
            ])
            ->assertSessionHasErrors('sakelar');
    }

    public function test_mahasiswa_tidak_boleh_mengubah_kebijakan(): void
    {
        $mahasiswa = User::factory()->mahasiswa()->create();

        $this->actingAs($mahasiswa)
            ->patch(route('admin.pengaturan-baca.update'), [
                'sakelar' => 'baca_stempel',
                'nilai' => '0',
            ])
            ->assertForbidden();
    }
}
