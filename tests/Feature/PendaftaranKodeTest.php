<?php

namespace Tests\Feature;

use App\Models\Prodi;
use App\Models\User;
use App\Notifications\VerifikasiEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PendaftaranKodeTest extends TestCase
{
    use RefreshDatabase;

    /** Data pendaftaran yang sah, boleh ditimpa sebagian. */
    private function dataValid(Prodi $prodi, array $ubah = []): array
    {
        return array_merge([
            'name' => 'Dzar Fadhlurrahman',
            'email' => 'dzar.mahasiswa@gmail.com',
            'kode_akses' => $prodi->access_code,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ], $ubah);
    }

    public function test_halaman_pendaftaran_dapat_dibuka(): void
    {
        $this->get('/register')->assertOk();
    }

    public function test_mahasiswa_terdaftar_ke_prodi_sesuai_kode_akses(): void
    {
        $prodi = Prodi::factory()->create(['access_code' => 'PAI-2026']);

        $this->post('/register', $this->dataValid($prodi))
            ->assertRedirect(route('verification.notice'));

        $this->assertAuthenticated();

        $this->assertDatabaseHas('users', [
            'email' => 'dzar.mahasiswa@gmail.com',
            'role' => User::ROLE_MAHASISWA,
            'prodi_id' => $prodi->id,
            'is_active' => true,
            'email_verified_at' => null,
        ]);
    }

    public function test_kode_akses_tidak_peka_huruf_besar_kecil(): void
    {
        $prodi = Prodi::factory()->create(['access_code' => 'MNJ-2026']);

        $this->post('/register', $this->dataValid($prodi, ['kode_akses' => 'mnj-2026']));

        $this->assertDatabaseHas('users', [
            'email' => 'dzar.mahasiswa@gmail.com',
            'prodi_id' => $prodi->id,
        ]);
    }

    public function test_kode_akses_salah_ditolak(): void
    {
        $prodi = Prodi::factory()->create(['access_code' => 'PAI-2026']);

        $this->post('/register', $this->dataValid($prodi, ['kode_akses' => 'SALAH-999']))
            ->assertSessionHasErrors('kode_akses');

        $this->assertGuest();
    }

    public function test_nama_satu_kata_ditolak(): void
    {
        $prodi = Prodi::factory()->create();

        $this->post('/register', $this->dataValid($prodi, ['name' => 'Dzar']))
            ->assertSessionHasErrors('name');

        $this->assertGuest();
    }

    public function test_nama_dengan_angka_atau_simbol_ditolak(): void
    {
        $prodi = Prodi::factory()->create();

        $this->post('/register', $this->dataValid($prodi, ['name' => 'Dzar F4z @#']))
            ->assertSessionHasErrors('name');

        $this->assertGuest();
    }

    public function test_email_ganda_ditolak(): void
    {
        $prodi = Prodi::factory()->create();
        User::factory()->mahasiswa($prodi)->create(['email' => 'dzar.mahasiswa@gmail.com']);

        $this->post('/register', $this->dataValid($prodi))
            ->assertSessionHasErrors('email');
    }

    public function test_mahasiswa_baru_diarahkan_ke_halaman_verifikasi_dan_belum_bisa_buka_katalog(): void
    {
        $prodi = Prodi::factory()->create();

        $this->post('/register', $this->dataValid($prodi))
            ->assertRedirect(route('verification.notice'));

        // Katalog tetap terkunci sampai surelnya dikonfirmasi — diarahkan
        // sopan ke halaman verifikasi, bukan ditolak dengan galat.
        $this->get(route('katalog.index'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_pendaftaran_mengirim_tautan_verifikasi(): void
    {
        Notification::fake();

        $prodi = Prodi::factory()->create();
        $this->post('/register', $this->dataValid($prodi));

        $pengguna = User::where('email', 'dzar.mahasiswa@gmail.com')->firstOrFail();

        Notification::assertSentTo($pengguna, VerifikasiEmail::class);
    }
}
