<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AkunNonaktifTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_aktif_dapat_mengakses_halaman_terlindungi(): void
    {
        $user = User::factory()->create();

        // Mahasiswa aktif dialihkan dari /dashboard menuju katalog,
        // yang menandakan middleware `active` meloloskannya.
       $this->actingAs($user)->get('/dashboard')->assertRedirect(route('beranda.saya'));

        // Katalognya sendiri harus dapat dibuka penuh.
        $this->actingAs($user)->get(route('katalog.index'))->assertOk();
    }

    public function test_user_nonaktif_dikeluarkan_dari_sesi(): void
    {
        $user = User::factory()->nonaktif()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
