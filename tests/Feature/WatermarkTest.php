<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Prodi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WatermarkTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    private function buatBuku(array $atribut = []): Book
    {
        $prodi = Prodi::factory()->create();
        Storage::disk('local')->put('books/contoh.pdf', '%PDF-1.4 dummy');

        return Book::factory()->create(array_merge([
            'prodi_id' => $prodi->id,
            'is_published' => true,
            'access_mode' => Book::AKSES_PENUH,
        ], $atribut));
    }

    public function test_watermark_kaki_tidak_muncul_saat_watermark_dinonaktifkan(): void
    {
        $buku = $this->buatBuku(['watermark_enabled' => false]);
        $mahasiswa = User::factory()->create(['prodi_id' => $buku->prodi_id]);

        $this->actingAs($mahasiswa)
            ->get(route('katalog.baca', $buku))
            ->assertOk()
            ->assertDontSee('data-watermark-kaki', false);
    }

    public function test_watermark_kaki_muncul_saat_watermark_diaktifkan(): void
    {
        $buku = $this->buatBuku(['watermark_enabled' => true]);
        $mahasiswa = User::factory()->create(['prodi_id' => $buku->prodi_id]);

        $this->actingAs($mahasiswa)
            ->get(route('katalog.baca', $buku))
            ->assertOk()
            ->assertSee('data-watermark-kaki', false);
    }
}
