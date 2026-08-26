<?php

namespace Tests\Feature;

use App\Models\Prodi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PembatasLajuAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_pendaftaran_dibatasi_lima_kali_per_menit(): void
    {
        $prodi = Prodi::factory()->create(['access_code' => 'PAI-2026']);

        // Lima percobaan pertama masih mendapat jawaban validasi biasa.
        for ($i = 1; $i <= 5; $i++) {
            $this->post('/register', [
                'name' => 'Dzar Fadhlurrahman',
                'email' => "dzar{$i}@example.com",
                'kode_akses' => 'SALAH-999',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])->assertStatus(302);
        }

        // Percobaan keenam ditolak mentah, meskipun datanya sah.
        $this->post('/register', [
            'name' => 'Dzar Fadhlurrahman',
            'email' => 'dzar.sah@example.com',
            'kode_akses' => $prodi->access_code,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])
            ->assertStatus(429)
            ->assertSee('Terlalu banyak percobaan pendaftaran');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'dzar.sah@example.com']);
    }

    public function test_permintaan_reset_sandi_dibatasi_lima_kali_per_menit(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->post('/forgot-password', ['email' => 'dzar@example.com'])
                ->assertStatus(302);
        }

        $this->post('/forgot-password', ['email' => 'lainnya@example.com'])
            ->assertStatus(429)
            ->assertSee('Terlalu banyak permintaan tautan reset');
    }

    public function test_pengunjung_lain_tidak_kebagian_jatah_ip_orang(): void
    {
        Prodi::factory()->create(['access_code' => 'PAI-2026']);

        for ($i = 1; $i <= 5; $i++) {
            $this->post('/register', [
                'name' => 'Dzar Fadhlurrahman',
                'email' => "dzar{$i}@example.com",
                'kode_akses' => 'SALAH-999',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);
        }

        // Pengunjung dengan alamat IP berbeda tetap dilayani walau satu
        // jaringan sudah mencapai batas.
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.77'])
            ->post('/register', [
                'name' => 'Dzar Fadhlurrahman',
                'email' => 'dzar.ip-lain@example.com',
                'kode_akses' => 'PAI-2026',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertRedirect(route('verification.notice'));
    }
}
