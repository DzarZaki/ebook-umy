<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Bookmark;
use App\Models\Prodi;
use App\Models\ReadingProgress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BacaBukuTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    /**
     * Buku beserta berkas tiruan di disk lokal.
     *
     * Berkasnya bukan PDF yang sah — hanya sekumpulan byte berukuran 100 KB.
     * Cukup untuk menguji izin dan penyaluran, tetapi tidak dapat diolah qpdf.
     * Karena itu tes yang mengharapkan 200 selalu mematikan watermark.
     */
    private function bukuDenganBerkas(array $ganti = []): Book
    {
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

        // Penolakan terjadi di gerbang unduhan, sebelum berkas sempat disentuh.
        $this->actingAs(User::factory()->mahasiswa()->create())
            ->get(route('katalog.unduh', $buku))
            ->assertForbidden();

        $this->assertDatabaseCount('download_logs', 0);
    }

    public function test_unduhan_yang_diizinkan_tercatat(): void
    {
        $buku = $this->bukuDenganBerkas([
            'access_mode' => Book::AKSES_PENUH,
            'watermark_enabled' => false,
        ]);
        $mahasiswa = User::factory()->mahasiswa()->create();

        // Satu permintaan ini sekaligus membuktikan tiga hal: izin diberikan,
        // berkas disalurkan, dan unduhannya tercatat.
        $respons = $this->actingAs($mahasiswa)->get(route('katalog.unduh', $buku));

        $respons->assertOk();
        $this->assertStringContainsString(
            'attachment;',
            (string) $respons->headers->get('content-disposition'),
        );

        $this->assertDatabaseHas('download_logs', [
            'book_id' => $buku->id,
            'user_id' => $mahasiswa->id,
            'mode' => Book::AKSES_PENUH,
        ]);
    }

    public function test_sakelar_prodi_yang_mati_memblokir_unduhan(): void
    {
        $prodi = Prodi::factory()->create(['download_enabled' => false]);
        $buku = $this->bukuDenganBerkas([
            'access_mode' => Book::AKSES_PENUH,
            'prodi_id' => $prodi->id,
        ]);

        $this->actingAs(User::factory()->mahasiswa($prodi)->create())
            ->get(route('katalog.unduh', $buku))
            ->assertForbidden();

        $this->assertDatabaseCount('download_logs', 0);
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

    public function test_penanda_di_luar_jumlah_halaman_ditolak(): void
    {
        $buku = $this->bukuDenganBerkas(['page_count' => 242]);

        $this->actingAs(User::factory()->mahasiswa()->create())
            ->postJson(route('katalog.penanda', $buku), ['halaman' => 9999])
            ->assertStatus(422)
            ->assertJsonPath('errors.halaman.0', 'Buku ini hanya memiliki 242 halaman.');

        // Penolakan harus terjadi sebelum basis data disentuh.
        $this->assertSame(0, Bookmark::count());
    }

    public function test_progres_di_luar_jumlah_halaman_ditolak(): void
    {
        $buku = $this->bukuDenganBerkas(['page_count' => 242]);

        $this->actingAs(User::factory()->mahasiswa()->create())
            ->postJson(route('katalog.progres', $buku), ['halaman' => 9999])
            ->assertStatus(422)
            ->assertJsonPath('errors.halaman.0', 'Buku ini hanya memiliki 242 halaman.');

        $this->assertSame(0, ReadingProgress::count());
    }

    /**
     * Buku yang jumlah halamannya belum terbaca tidak boleh mengunci
     * mahasiswanya. Kelalaian ada di sisi server, bukan di sisi pembaca.
     */
    public function test_buku_tanpa_jumlah_halaman_tetap_menerima_penanda(): void
    {
        $buku = $this->bukuDenganBerkas(['page_count' => null]);
        $mahasiswa = User::factory()->mahasiswa()->create();

        $this->actingAs($mahasiswa)
            ->postJson(route('katalog.penanda', $buku), ['halaman' => 500])
            ->assertOk()
            ->assertJsonPath('penanda', [500]);

        $this->assertDatabaseHas('bookmarks', [
            'user_id' => $mahasiswa->id,
            'book_id' => $buku->id,
            'page' => 500,
        ]);
    }

    /**
     * Jumlah halaman adalah fakta milik server. Angka yang dikirim penampil
     * hanya boleh dipakai bila server memang belum mengetahuinya — kalau
     * tidak, siapa pun bisa membuat kemajuan bacanya tampak tuntas.
     */
    public function test_total_halaman_kiriman_penampil_diabaikan(): void
    {
        $buku = $this->bukuDenganBerkas(['page_count' => 242]);
        $mahasiswa = User::factory()->mahasiswa()->create();

        $this->actingAs($mahasiswa)
            ->postJson(route('katalog.progres', $buku), ['halaman' => 10, 'total' => 12])
            ->assertOk();

        $progres = ReadingProgress::where('user_id', $mahasiswa->id)
            ->where('book_id', $buku->id)
            ->firstOrFail();

        $this->assertSame(10, (int) $progres->last_page);
        $this->assertSame(242, (int) $progres->total_pages);
    }
}