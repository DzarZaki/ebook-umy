<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Prodi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Buku contoh tidak boleh berbagi berkas dengan buku sungguhan: satu
 * penghapusan permanen yang wajar akan melenyapkan PDF milik dosen.
 */
class BukuContohSeederTest extends TestCase
{
    use RefreshDatabase;

    /** Menyiapkan satu buku sungguhan berisi berkas, sebagai sumber salinan. */
    private function bukuSumber(): Book
    {
        Storage::fake('local');
        Storage::fake('public');

        $prodi = Prodi::factory()->create(['name' => 'Teknik', 'slug' => 'teknik']);
        $dosen = User::factory()->admin($prodi)->create();

        Storage::disk('local')->put('books/asli.pdf', str_repeat('a', 4096));
        Storage::disk('public')->put('covers/asli.jpg', str_repeat('b', 512));

        return Book::factory()->create([
            'uploaded_by' => $dosen->id,
            'prodi_id' => $prodi->id,
            'file_path' => 'books/asli.pdf',
            'cover_path' => 'covers/asli.jpg',
            'page_count' => 120,
        ]);
    }

    private function jalankanSeeder(): void
    {
        $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\BukuContohSeeder'])
            ->assertSuccessful();
    }

    public function test_setiap_buku_contoh_memiliki_berkasnya_sendiri(): void
    {
        $sumber = $this->bukuSumber();

        $this->jalankanSeeder();

        $contoh = Book::where('slug', 'like', 'contoh-%')->get();

        $this->assertCount(18, $contoh);

        $jalur = $contoh->pluck('file_path');

        $this->assertNotContains(
            $sumber->file_path,
            $jalur->all(),
            'Buku contoh masih menumpang berkas buku asli.'
        );

        $this->assertSame(
            $jalur->count(),
            $jalur->unique()->count(),
            'Ada buku contoh yang berbagi berkas dengan buku contoh lain.'
        );

        foreach ($jalur as $satu) {
            Storage::disk('local')->assertExists($satu);
        }

        // Berkas sumber tentu harus tetap ada dan tidak tersentuh.
        Storage::disk('local')->assertExists($sumber->file_path);
    }

    public function test_sampul_buku_contoh_juga_disalin(): void
    {
        $sumber = $this->bukuSumber();

        $this->jalankanSeeder();

        $sampul = Book::where('slug', 'like', 'contoh-%')
            ->whereNotNull('cover_path')
            ->pluck('cover_path');

        $this->assertTrue($sampul->isNotEmpty(), 'Tidak ada buku contoh bersampul.');

        $this->assertNotContains($sumber->cover_path, $sampul->all());
        $this->assertSame($sampul->count(), $sampul->unique()->count());

        foreach ($sampul as $satu) {
            Storage::disk('public')->assertExists($satu);
        }
    }
}