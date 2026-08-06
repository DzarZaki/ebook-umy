<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Bookmark;
use App\Models\Category;
use App\Models\Prodi;
use App\Models\ReadingProgress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KemajuanBacaTest extends TestCase
{
    use RefreshDatabase;

    /** Menyiapkan satu mahasiswa dan satu buku terbit pada prodi yang sama. */
    private function siapkan(): array
    {
        $prodi = Prodi::factory()->create();
        $dosen = User::factory()->admin()->create(['prodi_id' => $prodi->id]);
        $mahasiswa = User::factory()->mahasiswa()->create(['prodi_id' => $prodi->id]);

        $kategori = Category::factory()->create([
            'prodi_id' => $prodi->id,
            'created_by' => $dosen->id,
        ]);

        $buku = Book::factory()->create([
            'prodi_id' => $prodi->id,
            'category_id' => $kategori->id,
            'uploaded_by' => $dosen->id,
            'is_published' => true,
            'page_count' => 50,
        ]);

        return [$mahasiswa, $buku];
    }

    public function test_mahasiswa_dapat_menyimpan_kemajuan_membaca(): void
    {
        [$mahasiswa, $buku] = $this->siapkan();

        $this->actingAs($mahasiswa)
            ->postJson(route('katalog.progres', $buku), ['halaman' => 12, 'total' => 50])
            ->assertOk();

        $this->assertDatabaseHas('reading_progress', [
            'user_id' => $mahasiswa->id,
            'book_id' => $buku->id,
            'last_page' => 12,
        ]);
    }

    public function test_kemajuan_membaca_diperbarui_bukan_digandakan(): void
    {
        [$mahasiswa, $buku] = $this->siapkan();

        $this->actingAs($mahasiswa)->postJson(route('katalog.progres', $buku), ['halaman' => 5]);
        $this->actingAs($mahasiswa)->postJson(route('katalog.progres', $buku), ['halaman' => 30]);

        $this->assertSame(1, ReadingProgress::count());
        $this->assertSame(30, ReadingProgress::first()->last_page);
    }

    public function test_data_baca_mengembalikan_halaman_terakhir_dan_penanda(): void
    {
        [$mahasiswa, $buku] = $this->siapkan();

        ReadingProgress::create([
            'user_id' => $mahasiswa->id,
            'book_id' => $buku->id,
            'last_page' => 21,
            'total_pages' => 50,
        ]);

        Bookmark::create(['user_id' => $mahasiswa->id, 'book_id' => $buku->id, 'page' => 8]);

        $this->actingAs($mahasiswa)
            ->getJson(route('katalog.data-baca', $buku))
            ->assertOk()
            ->assertJson([
                'halamanTerakhir' => 21,
                'penanda' => [8],
            ]);
    }

    public function test_penanda_dapat_dinyalakan_dan_dicabut(): void
    {
        [$mahasiswa, $buku] = $this->siapkan();

        // Penekanan pertama memasang penanda.
        $this->actingAs($mahasiswa)
            ->postJson(route('katalog.penanda', $buku), ['halaman' => 9])
            ->assertOk()
            ->assertJson(['penanda' => [9]]);

        // Penekanan kedua pada halaman yang sama mencabutnya kembali.
        $this->actingAs($mahasiswa)
            ->postJson(route('katalog.penanda', $buku), ['halaman' => 9])
            ->assertOk()
            ->assertJson(['penanda' => []]);

        $this->assertSame(0, Bookmark::count());
    }

    public function test_nomor_halaman_tidak_wajar_ditolak(): void
    {
        [$mahasiswa, $buku] = $this->siapkan();

        $this->actingAs($mahasiswa)
            ->postJson(route('katalog.progres', $buku), ['halaman' => 0])
            ->assertStatus(422);
    }

    public function test_tamu_tidak_dapat_menyimpan_kemajuan(): void
    {
        [, $buku] = $this->siapkan();

        $this->postJson(route('katalog.progres', $buku), ['halaman' => 3])
            ->assertUnauthorized();
    }
}
