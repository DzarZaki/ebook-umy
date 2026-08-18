<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Halaman masuk dan daftar adalah dua halaman yang paling tidak boleh
 * bergantung pada server di luar kampus: keduanya pintu masuk aplikasi.
 */
class AsetTanpaCdnTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_masuk_tidak_memuat_pustaka_dari_cdn(): void
    {
        $respons = $this->get('/login')->assertOk();

        $respons->assertDontSee('cdnjs.cloudflare.com');
        $respons->assertDontSee('three.min.js');
        $respons->assertDontSee('new THREE.', false);
    }

    public function test_halaman_daftar_juga_bebas_cdn(): void
    {
        $respons = $this->get('/register')->assertOk();

        $respons->assertDontSee('cdnjs.cloudflare.com');
        $respons->assertDontSee('three.min.js');
        $respons->assertDontSee('new THREE.', false);
    }

    public function test_panel_branding_tetap_utuh(): void
    {
        // Latar dekoratifnya berganti mesin, bukan hilang.
        $this->get('/login')
            ->assertOk()
            ->assertSee('particles-canvas')
            ->assertSee('Perpustakaan Digital');
    }
}