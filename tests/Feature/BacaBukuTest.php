<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Prodi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BacaBukuTest extends TestCase
{
    use RefreshDatabase;

    /** Menyiapkan buku dengan berkas PDF palsu di penyimpanan privat. */
    private function bukuDenganBerkas(array $ganti = []): Book
    {
        Storage::fake('local');
        $jalur = UploadedFile::fake()->create('isi.pdf', 100, 'application/pdf')->store('books', 'local');

        return Book::factory()->create(array_merge(['file_path' => $jalur], $ganti));
    }

    public function test_mahasiswa_dapat_membuka_halaman_baca(): void
    {
        $buku = $this->bukuDenganBerkas(['title' => 'Etika Profesi']);

        $this->actingAs(User::factory()->mahasiswa()->create())
            ->get(route('katalog.baca', $buku))
            ->assertOk()
            ->assertSee('Etika Profesi');
    }

    public function test_buku_prodi_lain_tidak_dapat_dibaca(): void
    {
        $prodiLain = Prodi::factory()->create();
        $buku = $this->bukuDenganBerkas(['prodi_id' => $prodiLain->id]);

        $this->actingAs(User::factory()->mahasiswa()->create())
            ->get(route('katalog.baca', $buku))
            ->assertNotFound();
    }

    public function test_berkas_hanya_disalurkan_kepada_yang_berhak(): void
    {
        $prodiLain = Prodi::factory()->create();
        $buku = $this->bukuDenganBerkas(['prodi_id' => $prodiLain->id]);

        $this->actingAs(User::factory()->mahasiswa()->create())
            ->get(route('katalog.berkas', $buku))
            ->assertNotFound();
    }

    public function test_berkas_dapat_dimuat_oleh_mahasiswa_yang_berhak(): void
    {
        $buku = $this->bukuDenganBerkas();

        $this->actingAs(User::factory()->mahasiswa()->create())
            ->get(route('katalog.berkas', $buku))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_buku_baca_saja_tidak_dapat_diunduh(): void
    {
        $buku = $this->bukuDenganBerkas(['access_mode' => Book::AKSES_BACA_SAJA]);

        $this->actingAs(User::factory()->mahasiswa()->create())
            ->post(route('katalog.catat', $buku))
            ->assertForbidden();

        $this->assertDatabaseCount('download_logs', 0);
    }

    public function test_unduhan_yang_diizinkan_tercatat(): void
    {
        $buku = $this->bukuDenganBerkas(['access_mode' => Book::AKSES_PENUH]);
        $mahasiswa = User::factory()->mahasiswa()->create();

        $this->actingAs($mahasiswa)
            ->post(route('katalog.catat', $buku))
            ->assertOk();

        $this->assertDatabaseHas('download_logs', [
            'book_id' => $buku->id,
            'user_id' => $mahasiswa->id,
            'mode' => Book::AKSES_PENUH,
        ]);
    }

    public function test_sakelar_prodi_yang_mati_memblokir_unduhan(): void
    {
        $prodi = Prodi::factory()->create(['download_enabled' => false]);
        $buku = $this->bukuDenganBerkas(['access_mode' => Book::AKSES_PENUH, 'prodi_id' => $prodi->id]);

        $this->actingAs(User::factory()->mahasiswa($prodi)->create())
            ->post(route('katalog.catat', $buku))
            ->assertForbidden();
    }

    public function test_mode_sebagian_mengembalikan_rentang_halaman(): void
    {
        $buku = $this->bukuDenganBerkas([
            'access_mode' => Book::AKSES_SEBAGIAN,
            'download_page_start' => 5,
            'download_page_end' => 20,
        ]);

        $aturan = $buku->aturanUnduhUntuk(User::factory()->mahasiswa()->create());

        $this->assertTrue($aturan['boleh']);
        $this->assertSame(5, $aturan['awal']);
        $this->assertSame(20, $aturan['akhir']);
    }

    public function test_dosen_dapat_mematikan_sakelar_unduhan_prodinya(): void
    {
        $prodi = Prodi::factory()->create(['download_enabled' => true]);
        $dosen = User::factory()->admin($prodi)->create();

        $this->actingAs($dosen)
            ->patch(route('admin.pengaturan-unduh.update'), ['download_enabled' => 0])
            ->assertRedirect();

        $this->assertFalse($prodi->fresh()->download_enabled);
    }

    public function test_mahasiswa_tidak_dapat_mengubah_sakelar_unduhan(): void
    {
        $this->actingAs(User::factory()->mahasiswa()->create())
            ->patch(route('admin.pengaturan-unduh.update'), ['download_enabled' => 0])
            ->assertForbidden();
    }
}
