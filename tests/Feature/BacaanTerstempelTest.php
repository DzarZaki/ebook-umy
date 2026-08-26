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
 * Menjaga agar penampil baca tidak lagi menjadi pintu samping.
 *
 * Sebelum perbaikan ini, /katalog/{buku}/berkas menyalurkan dokumen asli
 * utuh untuk SEMUA mode akses — sehingga "baca saja" dan "unduh sebagian"
 * dapat dilewati hanya dengan menyimpan berkas dari penampil.
 */
class BacaanTerstempelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_buku_baca_saja_disalurkan_dalam_keadaan_terstempel(): void
    {
        $this->lewatiTanpaQpdf();

        [$buku, $mahasiswa] = $this->bukuDanPembaca([
            'access_mode' => Book::AKSES_BACA_SAJA,
            'watermark_enabled' => false,
        ]);

        $respons = $this->actingAs($mahasiswa)->get(route('katalog.berkas', $buku));
        $respons->assertOk();

        $terkirim = $respons->streamedContent();

        // Buktinya di sini: berkas yang diterima penampil bukan lagi berkas
        // asli. Kolom watermark_enabled sengaja dimatikan, karena kolom itu
        // mengatur berkas UNDUHAN, bukan aliran baca.
        $this->assertNotSame(
            Storage::disk('local')->get($buku->file_path),
            $terkirim,
            'Aliran baca masih identik dengan berkas asli, berarti stempelnya tidak menempel.',
        );

        // Menstempel tidak boleh menambah atau menghilangkan halaman.
        $this->assertSame(12, $this->jumlahHalaman($terkirim));
    }

    public function test_buku_unduh_penuh_disalurkan_apa_adanya(): void
    {
        [$buku, $mahasiswa] = $this->bukuDanPembaca([
            'access_mode' => Book::AKSES_PENUH,
            'watermark_enabled' => false,
        ]);

        $respons = $this->actingAs($mahasiswa)->get(route('katalog.berkas', $buku));
        $respons->assertOk();

        // Buku yang boleh diunduh seluruhnya tidak perlu diolah untuk dibaca.
        $this->assertSame(
            Storage::disk('local')->get($buku->file_path),
            $respons->streamedContent(),
        );
    }

    public function test_penampil_ikut_dibatasi_saat_prodi_menyalakan_ikuti_rentang(): void
    {
        $this->lewatiTanpaQpdf();

        // Sakelarnya kini milik program studi; konfigurasi tidak lagi menentukan.
        [$buku, $mahasiswa] = $this->bukuDanPembaca([
            'access_mode' => Book::AKSES_SEBAGIAN,
            'download_page_start' => 3,
            'download_page_end' => 5,
            'watermark_enabled' => false,
        ], 12, Prodi::factory()->create(['baca_ikuti_rentang' => true]));

        $respons = $this->actingAs($mahasiswa)->get(route('katalog.berkas', $buku));
        $respons->assertOk();

        $this->assertSame(3, $this->jumlahHalaman($respons->streamedContent()));
    }

    public function test_penampil_tetap_menerima_seluruh_halaman_tanpa_kebijakan_rentang(): void
    {
        $this->lewatiTanpaQpdf();

        [$buku, $mahasiswa] = $this->bukuDanPembaca([
            'access_mode' => Book::AKSES_SEBAGIAN,
            'download_page_start' => 3,
            'download_page_end' => 5,
            'watermark_enabled' => false,
        ]);

        $respons = $this->actingAs($mahasiswa)->get(route('katalog.berkas', $buku));
        $respons->assertOk();

        // Bawaannya: membaca seluruh isi tetap boleh, hanya jejaknya yang
        // ditambahkan. Membatasi bacaan adalah keputusan yang harus disengaja.
        $this->assertSame(12, $this->jumlahHalaman($respons->streamedContent()));
    }

    /**
     * @return array{0: Book, 1: User}
     */
    private function bukuDanPembaca(array $atribut, int $halaman = 12, ?Prodi $prodi = null): array
    {
        $prodi ??= Prodi::factory()->create();
        $mahasiswa = User::factory()->mahasiswa($prodi)->create();

        $buku = Book::factory()->create(array_merge([
            'prodi_id' => $prodi->id,
            'is_published' => true,
            'file_path' => 'books/'.Str::uuid()->toString().'.pdf',
            'page_count' => $halaman,
        ], $atribut));

        $pdf = new \FPDF('P', 'mm', 'A4');
        $pdf->SetFont('Helvetica', '', 24);

        for ($nomor = 1; $nomor <= $halaman; $nomor++) {
            $pdf->AddPage();
            $pdf->Cell(0, 20, "Halaman {$nomor}", 0, 1, 'C');
        }

        Storage::disk('local')->put($buku->file_path, $pdf->Output('S'));

        return [$buku, $mahasiswa];
    }

    /** qpdf hanya menerima jalur berkas, jadi isinya dititipkan sebentar. */
    private function jumlahHalaman(string $isi): ?int
    {
        $jalur = tempnam(sys_get_temp_dir(), 'baca').'.pdf';
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
