<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\VerifikasiEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Alur verifikasi surel mahasiswa.
 *
 * Kode akses prodi menjaga siapa boleh mendaftar; verifikasi surel menjaga
 * agar alamat yang terdaftar benar-benar milik orangnya — sekaligus
 * menangkap salah ketik sebelum ia memutus reset kata sandi kelak.
 */
class VerifikasiEmailMahasiswaTest extends TestCase
{
    use RefreshDatabase;

    public function test_akun_tanpa_verifikasi_diarahkan_ke_halaman_verifikasi(): void
    {
        $mahasiswa = User::factory()->mahasiswa()->create(['email_verified_at' => null]);

        $this->actingAs($mahasiswa)
            ->get(route('katalog.index'))
            ->assertRedirect(route('verification.notice'));

        $this->actingAs($mahasiswa)
            ->get(route('beranda.saya'))
            ->assertRedirect(route('verification.notice'));

        $this->actingAs($mahasiswa)
            ->get(route('koleksi.index'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_mengklik_tautan_memverifikasi_dan_membuka_katalog(): void
    {
        $mahasiswa = User::factory()->mahasiswa()->create(['email_verified_at' => null]);

        $tautan = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $mahasiswa->id, 'hash' => sha1($mahasiswa->email)],
        );

        $this->actingAs($mahasiswa)
            ->get($tautan)
            ->assertRedirect(route('dashboard', absolute: false).'?verified=1');

        $this->assertNotNull($mahasiswa->refresh()->email_verified_at);

        $this->get(route('katalog.index'))->assertOk();
    }

    public function test_tautan_dengan_cetak_kering_salah_ditolak(): void
    {
        $mahasiswa = User::factory()->mahasiswa()->create(['email_verified_at' => null]);

        $tautanPalsu = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $mahasiswa->id, 'hash' => sha1('bukan-pemiliknya@example.com')],
        );

        $this->actingAs($mahasiswa)->get($tautanPalsu)->assertForbidden();

        $this->assertNull($mahasiswa->refresh()->email_verified_at);
    }

    public function test_tautan_lama_yang_kedaluwarsa_ditolak(): void
    {
        $mahasiswa = User::factory()->mahasiswa()->create(['email_verified_at' => null]);

        $tautanLama = URL::temporarySignedRoute(
            'verification.verify',
            now()->subMinutes(5),
            ['id' => $mahasiswa->id, 'hash' => sha1($mahasiswa->email)],
        );

        $this->actingAs($mahasiswa)->get($tautanLama)->assertForbidden();
    }

    public function test_pengguna_bisa_meminta_tautan_baru(): void
    {
        Notification::fake();

        $mahasiswa = User::factory()->mahasiswa()->create(['email_verified_at' => null]);

        $this->actingAs($mahasiswa)
            ->post(route('verification.send'))
            ->assertRedirect()
            ->assertSessionHas('status', 'verification-link-sent');

        Notification::assertSentTo($mahasiswa, VerifikasiEmail::class);
    }

    public function test_akun_terverifikasi_tidak_dikirim_ulang(): void
    {
        Notification::fake();

        $dosen = User::factory()->admin()->create();

        $this->actingAs($dosen)->post(route('verification.send'));

        Notification::assertNotSentTo($dosen, VerifikasiEmail::class);
    }
}
