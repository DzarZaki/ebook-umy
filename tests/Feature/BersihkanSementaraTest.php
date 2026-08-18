<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BersihkanSementaraTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        // Masa simpan dipatok eksplisit agar pengujian tidak bergantung
        // pada nilai .env di komputer siapa pun.
        config([
            'ebook.unduh.disk' => 'local',
            'ebook.unduh.folder' => 'unduhan-sementara',
            'ebook.unduh.ttl_menit' => 30,
            'ebook.baca.folder' => 'bacaan-sementara',
            'ebook.baca.ttl_menit' => 30,
        ]);
    }

    /** Menulis berkas lalu memundurkan waktu ubahnya. */
    private function tulisBerkas(string $jalur, int $menitLalu, int $ukuran = 2048): void
    {
        Storage::disk('local')->put($jalur, str_repeat('x', $ukuran));

        touch(
            Storage::disk('local')->path($jalur),
            now()->subMinutes($menitLalu)->getTimestamp()
        );
    }

    public function test_berkas_usang_di_kedua_folder_dihapus(): void
    {
        $this->tulisBerkas('unduhan-sementara/buku-lama.pdf', 180);
        $this->tulisBerkas('bacaan-sementara/bacaan-lama.pdf', 180);

        $this->artisan('ebook:bersihkan-unduhan')->assertSuccessful();

        Storage::disk('local')->assertMissing('unduhan-sementara/buku-lama.pdf');
        Storage::disk('local')->assertMissing('bacaan-sementara/bacaan-lama.pdf');
    }

    public function test_berkas_yang_masih_baru_tidak_ikut_terhapus(): void
    {
        $this->tulisBerkas('unduhan-sementara/baru.pdf', 1);
        $this->tulisBerkas('bacaan-sementara/baru.pdf', 1);

        $this->artisan('ebook:bersihkan-unduhan')->assertSuccessful();

        Storage::disk('local')->assertExists('unduhan-sementara/baru.pdf');
        Storage::disk('local')->assertExists('bacaan-sementara/baru.pdf');
    }

    /**
     * Bila laporan hanya mengukur folder unduhan, ruang yang dibebaskan
     * akan tertulis 0 B padahal 40 KB bacaan baru saja dilenyapkan.
     */
    public function test_laporan_menghitung_ruang_dari_folder_bacaan(): void
    {
        $this->tulisBerkas('bacaan-sementara/besar.pdf', 180, 40 * 1024);

        $this->artisan('ebook:bersihkan-unduhan')
            ->expectsOutputToContain('KB')
            ->assertSuccessful();
    }
}