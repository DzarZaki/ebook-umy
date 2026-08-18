<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Halaman profil tidak boleh menjadi jalan pintas yang melewati aturan
 * domain surel kampus, tetapi juga tidak boleh mengunci pengguna lama.
 */
class ProfileSurelDosenTest extends TestCase
{
    use RefreshDatabase;

    public function test_dosen_tidak_dapat_memindahkan_surelnya_ke_domain_luar(): void
    {
        $dosen = User::factory()->admin()->create(['email' => 'ahmad.nugroho@umy.ac.id']);

        $this->actingAs($dosen)
            ->patch('/profile', [
                'name' => $dosen->name,
                'email' => 'ahmad.pribadi@gmail.com',
            ])
            ->assertSessionHasErrors('email');

        $this->assertSame('ahmad.nugroho@umy.ac.id', $dosen->refresh()->email);
    }

    public function test_super_admin_juga_terikat_domain_kampus(): void
    {
        $admin = User::factory()->superAdmin()->create(['email' => 'kepala.pustaka@umy.ac.id']);

        $this->actingAs($admin)
            ->patch('/profile', [
                'name' => $admin->name,
                'email' => 'kepala.pustaka@gmail.com',
            ])
            ->assertSessionHasErrors('email');

        $this->assertSame('kepala.pustaka@umy.ac.id', $admin->refresh()->email);
    }

    public function test_dosen_tetap_dapat_mengubah_surel_di_dalam_domain_kampus(): void
    {
        $dosen = User::factory()->admin()->create(['email' => 'ahmad.nugroho@umy.ac.id']);

        $this->actingAs($dosen)
            ->patch('/profile', [
                'name' => $dosen->name,
                'email' => 'a.nugroho@umy.ac.id',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertSame('a.nugroho@umy.ac.id', $dosen->refresh()->email);
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
     * Mengosongkan `email_verified_at` tidak boleh berakibat apa pun selain
     * mengosongkan kolom itu. Bila pengujian ini gagal, kemungkinan besar
     * middleware `verified` baru dipasang pada suatu rute — sementara alur
     * verifikasinya belum ada, sehingga pengguna terkunci dan diarahkan ke
     * rute `verification.notice` yang tidak terdaftar.
     */
    public function test_mengubah_surel_tidak_mengunci_pengguna_dari_katalog(): void
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
            ->assertOk();
    }
}