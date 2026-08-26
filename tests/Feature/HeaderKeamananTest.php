<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HeaderKeamananTest extends TestCase
{
    // Halaman depan membaca daftar prodi, jadi skema database perlu disiapkan.
    use RefreshDatabase;

    public function test_setiap_halaman_membawa_header_keamanan_dasar(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Content-Security-Policy', "frame-ancestors 'none'")
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    }

    public function test_hsts_hanya_dikirim_pada_koneksi_https(): void
    {
        // Status koneksi ditentukan skema URI — Symfony mendahuluikan skema
        // URI daripada variabel server HTTPS. Keduanya memakai URL absolut
        // supaya tidak terpengaruh akar URL dari permintaan sebelumnya.
        $this->get('http://localhost/')
            ->assertHeaderMissing('Strict-Transport-Security');

        $this->get(config('app.url'))
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000');
    }
}
