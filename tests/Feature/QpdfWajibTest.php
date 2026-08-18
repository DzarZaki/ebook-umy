<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Prodi;
use App\Models\User;
use App\Support\Pdf\Qpdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;

/**
 * Perilaku aplikasi ketika qpdf salah setel atau hilang.
 *
 * Seluruh tes di sini memakai instance Qpdf SUNGGUHAN yang diarahkan ke
 * jalur yang tidak ada, bukan mock. Dengan begitu yang diuji adalah
 * perilaku kelas itu sendiri — termasuk pemeriksaan jalurnya — dan hasilnya
 * sama di komputer yang qpdf-nya terpasang maupun yang tidak.
 */
class QpdfWajibTest extends TestCase
{
    use RefreshDatabase;

    /** Jalur yang dijamin kosong, baik di Windows maupun Linux. */
    private const BINARY_PALSU = '/jalur/yang/pasti/tidak/ada/qpdf-palsu';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_unduhan_berstempel_ditahan_saat_qpdf_tidak_ada(): void
    {
        $this->tanpaQpdf();

        $buku = $this->buatBuku(['watermark_enabled' => true]);
        $mahasiswa = User::factory()->create(['prodi_id' => $buku->prodi_id]);

        // Dulu ini menjawab 200 dengan berkas asli tanpa stempel.
        $this->actingAs($mahasiswa)
            ->get(route('katalog.unduh', $buku))
            ->assertStatus(503);

        // Penjagaan berjalan sebelum pencatatan: statistik tidak boleh memuat
        // unduhan yang sebenarnya tidak pernah terjadi.
        $this->assertDatabaseCount('download_logs', 0);
    }

    public function test_unduhan_sebagian_ditahan_saat_qpdf_tidak_ada(): void
    {
        $this->tanpaQpdf();

        $buku = $this->buatBuku([
            'access_mode' => Book::AKSES_SEBAGIAN,
            'download_page_start' => 2,
            'download_page_end' => 5,
            'watermark_enabled' => false,
        ]);
        $mahasiswa = User::factory()->create(['prodi_id' => $buku->prodi_id]);

        $this->actingAs($mahasiswa)
            ->get(route('katalog.unduh', $buku))
            ->assertStatus(503);

        $this->assertDatabaseCount('download_logs', 0);
    }

    public function test_mode_kelonggaran_tetap_menolak_unduhan_sebagian(): void
    {
        $this->tanpaQpdf();

        // Kelonggaran hanya berlaku untuk stempel. Meneruskan buku "sebagian"
        // tanpa qpdf berarti menyerahkan seluruh isinya — itu kebocoran, bukan
        // kemunduran layanan yang bisa ditoleransi.
        config(['ebook.qpdf.wajib' => false]);

        $buku = $this->buatBuku([
            'access_mode' => Book::AKSES_SEBAGIAN,
            'download_page_start' => 2,
            'download_page_end' => 5,
        ]);
        $mahasiswa = User::factory()->create(['prodi_id' => $buku->prodi_id]);

        $this->actingAs($mahasiswa)
            ->get(route('katalog.unduh', $buku))
            ->assertStatus(503);
    }

    public function test_buku_tanpa_stempel_tetap_dapat_diunduh_tanpa_qpdf(): void
    {
        $this->tanpaQpdf();

        // Buku utuh tanpa stempel tidak pernah menyentuh qpdf, jadi
        // ketiadaannya tidak boleh menghalangi apa pun.
        $buku = $this->buatBuku(['watermark_enabled' => false]);
        $mahasiswa = User::factory()->create(['prodi_id' => $buku->prodi_id]);

        $respons = $this->actingAs($mahasiswa)->get(route('katalog.unduh', $buku));
        $respons->assertOk();

        $this->assertSame(
            Storage::disk('local')->get($buku->file_path),
            (string) file_get_contents($this->jalurBerkas($respons)),
        );

        $this->assertDatabaseCount('download_logs', 1);
    }

    public function test_mode_kelonggaran_meneruskan_unduhan_tanpa_stempel(): void
    {
        $this->tanpaQpdf();
        config(['ebook.qpdf.wajib' => false]);

        $buku = $this->buatBuku(['watermark_enabled' => true]);
        $mahasiswa = User::factory()->create(['prodi_id' => $buku->prodi_id]);

        $respons = $this->actingAs($mahasiswa)->get(route('katalog.unduh', $buku));
        $respons->assertOk();

        // Berkasnya memang tersalur apa adanya — itulah harga kelonggaran ini,
        // dan sebabnya sudah tercatat sebagai Log::error.
        $this->assertSame(
            Storage::disk('local')->get($buku->file_path),
            (string) file_get_contents($this->jalurBerkas($respons)),
        );
    }

    public function test_perintah_pemeriksa_menyebut_jalur_yang_salah(): void
    {
        $this->tanpaQpdf();

        // Nilai perintah ini terletak pada penyebutan jalurnya. "qpdf tidak
        // tersedia" membuat orang menebak; jalur yang salah langsung menunjuk
        // baris .env yang perlu diperbaiki.
        $this->artisan('ebook:periksa-qpdf')
            ->expectsOutputToContain(self::BINARY_PALSU)
            ->assertExitCode(1);
    }

    public function test_perintah_pemeriksa_lulus_saat_peralatan_lengkap(): void
    {
        if (! app(Qpdf::class)->tersedia()) {
            $this->markTestSkipped('qpdf tidak tersedia di lingkungan ini.');
        }

        $this->artisan('ebook:periksa-qpdf')->assertExitCode(0);
    }

    /** Mengganti qpdf dengan instance yang menunjuk jalur kosong. */
    private function tanpaQpdf(): void
    {
        $this->app->instance(Qpdf::class, new Qpdf(self::BINARY_PALSU, 10));
    }

    /**
     * Buku terbit beserta berkas di disk lokal.
     *
     * Isinya cukup byte biasa, bukan PDF sungguhan seperti di WatermarkTest:
     * pada seluruh tes di berkas ini qpdf memang tidak pernah dijalankan —
     * entah karena permintaannya ditolak lebih dulu, atau karena berkasnya
     * disalurkan apa adanya tanpa diolah.
     */
    private function buatBuku(array $atribut = []): Book
    {
        $prodi = Prodi::factory()->create();

        $buku = Book::factory()->create(array_merge([
            'prodi_id' => $prodi->id,
            'is_published' => true,
            'access_mode' => Book::AKSES_PENUH,
            'file_path' => 'books/'.Str::uuid()->toString().'.pdf',
            'page_count' => 8,
        ], $atribut));

        Storage::disk('local')->put($buku->file_path, "%PDF-1.4\nisi contoh\n%%EOF\n");

        return $buku;
    }

    private function jalurBerkas(TestResponse $respons): string
    {
        $this->assertInstanceOf(BinaryFileResponse::class, $respons->baseResponse);

        return $respons->baseResponse->getFile()->getPathname();
    }
}