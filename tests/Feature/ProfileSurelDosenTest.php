<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Halaman profil tidak boleh menjadi jalan pintas yang melewati aturan
 * alamat Gmail, tetapi juga tidak boleh mengunci pengguna lama.
 */
class ProfileSurelDosenTest extends TestCase
{
    use RefreshDatabase;

    public function test_dosen_tidak_dapat_memindahkan_surelnya_ke_selain_gmail(): void
    {
        $dosen = User::factory()->admin()->create(['email' => 'ahmad.nugroho@gmail.com']);

        $this->actingAs($dosen)
            ->patch('/profile', [
                'name' => $dosen->name,
                'email' => 'ahmad.pribadi@yahoo.com',
            ])
            ->assertSessionHasErrors('email');

        $this->assertSame('ahmad.nugroho@gmail.com', $dosen->refresh()->email);
    }

    public function test_super_admin_juga_terikat_aturan_gmail(): void
    {
        $admin = User::factory()->superAdmin()->create(['email' => 'kepala.pustaka@gmail.com']);

        $this->actingAs($admin)
            ->patch('/profile', [
                'name' => $admin->name,
                'email' => 'kepala.pustaka@umy.ac.id',
            ])
            ->assertSessionHasErrors('email');

        $this->assertSame('kepala.pustaka@gmail.com', $admin->refresh()->email);
    }

    public function test_dosen_tetap_dapat_mengubah_surel_di_antara_alamat_gmail(): void
    {
        $dosen = User::factory()->admin()->create(['email' => 'ahmad.nugroho@gmail.com']);

        $this->actingAs($dosen)
            ->patch('/profile', [
                'name' => $dosen->name,
                'email' => 'a.nugroho@gmail.com',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertSame('a.nugroho@gmail.com', $dosen->refresh()->email);
    }

    /**
     * Data lama boleh berisi surel yang belum sesuai domain. Orang itu harus
     * tetap dapat memperbaiki namanya tanpa dipaksa mengganti surel lebih
     * dulu — aturan baru hanya berlaku pada surel yang benar-benar diubah.
     */
    public function test_dosen_bersurel_lama_di_luar_domain_tetap_dapat_menyunting_namanya(): void
    {
        $dosen = User::factory()->admin()->create([
            'name' => 'Ahmad Nugroho',
            'email' => 'ahmad.lama@gmail.com',
        ]);

        $this->actingAs($dosen)
            ->patch('/profile', [
                'name' => 'Ahmad Nugroho Hidayat',
                'email' => 'ahmad.lama@gmail.com',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertSame('Ahmad Nugroho Hidayat', $dosen->refresh()->name);
    }

    public function test_mahasiswa_tetap_bebas_memakai_surel_pribadi(): void
    {
        $mahasiswa = User::factory()->mahasiswa()->create();

        $this->actingAs($mahasiswa)
            ->patch('/profile', [
                'name' => $mahasiswa->name,
                'email' => 'mahasiswa.baru@gmail.com',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('mahasiswa.baru@gmail.com', $mahasiswa->refresh()->email);
    }

    /**
     * Mengganti surel membatalkan bukti kepemilikan alamat lamanya, jadi
     * akses katalog wajib menunggu verifikasi ulang. Yang dijaga di sini:
     * pengguna tidak boleh menabrak galat kasar — ia diarahkan sopan ke
     * halaman verifikasi yang rutenya sudah terdaftar.
     */
    public function test_mengubah_surel_minta_verifikasi_ulang_dengan_sopan(): void
    {
        $mahasiswa = User::factory()->mahasiswa()->create();

        $this->actingAs($mahasiswa)
            ->patch('/profile', [
                'name' => $mahasiswa->name,
                'email' => 'surel.pindah@gmail.com',
            ])
            ->assertSessionHasNoErrors();

        $this->assertNull($mahasiswa->refresh()->email_verified_at);

        $this->actingAs($mahasiswa)
            ->get(route('katalog.index'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_verifikasi_ulang_memulihkan_akses_katalog(): void
    {
        $mahasiswa = User::factory()->mahasiswa()->create();

        $this->actingAs($mahasiswa)
            ->patch('/profile', [
                'name' => $mahasiswa->name,
                'email' => 'surel.pindah@gmail.com',
            ])
            ->assertSessionHasNoErrors();

        $tautan = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $mahasiswa->id, 'hash' => sha1('surel.pindah@gmail.com')],
        );

        $this->actingAs($mahasiswa)->get($tautan)->assertRedirect();

        $this->assertNotNull($mahasiswa->refresh()->email_verified_at);

        $this->actingAs($mahasiswa)
            ->get(route('katalog.index'))
            ->assertOk();
    }
}
