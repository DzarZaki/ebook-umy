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
use Tests\TestCase;

/**
 * Kebijakan bacaan kini milik program studi, bukan lagi milik .env.
 *
 * Aturan penentunya satu dan tidak boleh dobel standar: buku prodi
 * mengikuti prodinya sendiri, buku umum mengikuti prodi pembacanya.
 * Konfigurasi server hanya penengah bagi pembaca yang tidak terikat
 * prodi mana pun.
 */
class KebijakanBacaProdiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_stempel_baca_yang_dimatikan_menyajikan_berkas_asli(): void
    {
        [$buku, $mahasiswa] = $this->bukuDanPembaca(
            ['access_mode' => Book::AKSES_BACA_SAJA],
            Prodi::factory()->create(['baca_stempel' => false]),
        );

        $respons = $this->actingAs($mahasiswa)->get(route('katalog.berkas', $buku));
        $respons->assertOk();

        // Tanpa stempel dan tanpa rentang tidak ada alasan menyentuh qpdf:
        // berkas asli disalurkan apa adanya.
        $this->assertSame(
            Storage::disk('local')->get($buku->file_path),
            $respons->streamedContent(),
        );
    }

    public function test_ikuti_rentang_dinyalakan_lewat_prodi_bukan_konfigurasi(): void
    {
        $this->lewatiTanpaQpdf();

        // Konfigurasi sengaja dimatikan; yang menyala hanya kebijakan prodinya.
        config(['ebook.baca.ikuti_rentang' => false]);

        [$buku, $mahasiswa] = $this->bukuDanPembaca([
            'access_mode' => Book::AKSES_SEBAGIAN,
            'download_page_start' => 3,
            'download_page_end' => 5,
        ], Prodi::factory()->create(['baca_ikuti_rentang' => true]));

        $respons = $this->actingAs($mahasiswa)->get(route('katalog.berkas', $buku));
        $respons->assertOk();

        $this->assertSame(3, $this->jumlahHalaman($respons->streamedContent()));
    }

    public function test_kebijakan_prodi_yang_mati_menaklukkan_konfigurasi_yang_menyala(): void
    {
        $this->lewatiTanpaQpdf();

        config(['ebook.baca.ikuti_rentang' => true]);

        [$buku, $mahasiswa] = $this->bukuDanPembaca([
            'access_mode' => Book::AKSES_SEBAGIAN,
            'download_page_start' => 3,
            'download_page_end' => 5,
        ], Prodi::factory()->create(['baca_ikuti_rentang' => false]));

        $respons = $this->actingAs($mahasiswa)->get(route('katalog.berkas', $buku));
        $respons->assertOk();

        $this->assertSame(12, $this->jumlahHalaman($respons->streamedContent()));
    }

    public function test_buku_umum_mengikuti_kebijakan_prodi_pembacanya(): void
    {
        $this->lewatiTanpaQpdf();

        $prodiPembaca = Prodi::factory()->create(['baca_ikuti_rentang' => true]);
        $mahasiswa = User::factory()->mahasiswa($prodiPembaca)->create();
        $buku = $this->buatBuku([
            'access_mode' => Book::AKSES_SEBAGIAN,
            'download_page_start' => 2,
            'download_page_end' => 4,
        ], null);

        $respons = $this->actingAs($mahasiswa)->get(route('katalog.berkas', $buku));
        $respons->assertOk();

        $this->assertSame(3, $this->jumlahHalaman($respons->streamedContent()));
    }

    public function test_pembaca_tanpa_prodi_jatuh_ke_penengah_konfigurasi(): void
    {
        $this->lewatiTanpaQpdf();

        config(['ebook.baca.ikuti_rentang' => true]);

        $superadmin = User::factory()->superAdmin()->create();
        $buku = $this->buatBuku([
            'access_mode' => Book::AKSES_SEBAGIAN,
            'download_page_start' => 2,
            'download_page_end' => 4,
        ], null);

        $respons = $this->actingAs($superadmin)->get(route('katalog.berkas', $buku));
        $respons->assertOk();

        $this->assertSame(3, $this->jumlahHalaman($respons->streamedContent()));
    }

    /**
     * Buku berikut berkas contohnya di penyimpanan palsu.
     *
     * @param  array<string, mixed>  $atribut
     */
    private function buatBuku(array $atribut, ?Prodi $prodi): Book
    {
        $buku = Book::factory()->create(array_merge([
            'prodi_id' => $prodi?->id,
            'is_published' => true,
            'file_path' => 'books/'.Str::uuid()->toString().'.pdf',
            'page_count' => 12,
        ], $atribut));

        $pdf = new \FPDF('P', 'mm', 'A4');
        $pdf->SetFont('Helvetica', '', 24);

        for ($nomor = 1; $nomor <= 12; $nomor++) {
            $pdf->AddPage();
            $pdf->Cell(0, 20, "Halaman {$nomor}", 0, 1, 'C');
        }

        Storage::disk('local')->put($buku->file_path, $pdf->Output('S'));

        return $buku;
    }

    /**
     * @return array{0: Book, 1: User}
     */
    private function bukuDanPembaca(array $atribut, ?Prodi $prodi): array
    {
        $prodi ??= Prodi::factory()->create();

        return [$this->buatBuku($atribut, $prodi), User::factory()->mahasiswa($prodi)->create()];
    }

    /** qpdf hanya menerima jalur berkas, jadi isinya dititipkan sebentar. */
    private function jumlahHalaman(string $isi): ?int
    {
        $jalur = tempnam(sys_get_temp_dir(), 'kebijakan').'.pdf';
        file_put_contents($jalur, $isi);

        try {
            return app(Qpdf::class)->jumlahHalaman($jalur);
        } finally {
            @unlink($jalur);
        }
    }

    private function lewatiTanpaQpdf(): void
    {
        if (! app(Qpdf::class)->tersedia()) {
            $this->markTestSkipped('qpdf tidak tersedia di lingkungan ini.');
        }
    }
}
