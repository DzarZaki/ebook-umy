<?php

namespace Tests\Feature\Admin;

use App\Models\Book;
use App\Models\Prodi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BukuCrudTest extends TestCase
{
    use RefreshDatabase;

    /** Data isian buku yang valid. */
    private function dataValid(array $ganti = []): array
    {
        return array_merge([
            'title' => 'Pengantar Manajemen',
            'author' => 'Dosen Pengampu',
            'lingkup' => 'prodi',
            'berkas' => UploadedFile::fake()->create('buku.pdf', 500, 'application/pdf'),
            'access_mode' => Book::AKSES_BACA_SAJA,
            'watermark_enabled' => 1,
            'is_published' => 1,
        ], $ganti);
    }

    public function test_dosen_dapat_mengunggah_buku(): void
    {
        Storage::fake('local');
        $prodi = Prodi::factory()->create();
        $dosen = User::factory()->admin($prodi)->create();

        $this->actingAs($dosen)
            ->post('/admin/buku', $this->dataValid())
            ->assertRedirect(route('admin.buku.index'));

        $this->assertDatabaseHas('books', [
            'title' => 'Pengantar Manajemen',
            'prodi_id' => $prodi->id,
            'uploaded_by' => $dosen->id,
        ]);
    }

    public function test_berkas_selain_pdf_ditolak(): void
    {
        Storage::fake('local');
        $dosen = User::factory()->admin()->create();

        $this->actingAs($dosen)
            ->post('/admin/buku', $this->dataValid([
                'berkas' => UploadedFile::fake()->create('gambar.jpg', 100, 'image/jpeg'),
            ]))
            ->assertSessionHasErrors('berkas');
    }

    public function test_mode_sebagian_wajib_mengisi_rentang_halaman(): void
    {
        Storage::fake('local');
        $dosen = User::factory()->admin()->create();

        $this->actingAs($dosen)
            ->post('/admin/buku', $this->dataValid(['access_mode' => Book::AKSES_SEBAGIAN]))
            ->assertSessionHasErrors('download_page_start');
    }

    public function test_halaman_akhir_tidak_boleh_lebih_kecil(): void
    {
        Storage::fake('local');
        $dosen = User::factory()->admin()->create();

        $this->actingAs($dosen)
            ->post('/admin/buku', $this->dataValid([
                'access_mode' => Book::AKSES_SEBAGIAN,
                'download_page_start' => 30,
                'download_page_end' => 10,
            ]))
            ->assertSessionHasErrors('download_page_end');
    }

    public function test_dosen_tidak_dapat_mengubah_buku_prodi_lain(): void
    {
        $prodiLain = Prodi::factory()->create();
        $buku = Book::factory()->create(['prodi_id' => $prodiLain->id]);
        $dosen = User::factory()->admin()->create();

        $this->actingAs($dosen)
            ->get(route('admin.buku.edit', $buku))
            ->assertForbidden();
    }

    public function test_menghapus_buku_juga_menghapus_berkasnya(): void
    {
        Storage::fake('local');
        $prodi = Prodi::factory()->create();
        $dosen = User::factory()->admin($prodi)->create();

        $this->actingAs($dosen)->post('/admin/buku', $this->dataValid());

        $buku = Book::first();
        Storage::disk('local')->assertExists($buku->file_path);

        $this->actingAs($dosen)->delete(route('admin.buku.destroy', $buku));

        Storage::disk('local')->assertMissing($buku->file_path);
        $this->assertDatabaseMissing('books', ['id' => $buku->id]);
    }
}
