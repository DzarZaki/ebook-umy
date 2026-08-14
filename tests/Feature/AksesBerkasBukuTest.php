<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Book;
use App\Models\DownloadLog;
use App\Models\Prodi;
use App\Models\User;
use App\Support\Pdf\Qpdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;

/**
 * Menjaga agar batasan akses berkas buku tidak dapat ditembus.
 *
 * Tes ini memakai berkas PDF sungguhan dan qpdf sungguhan, karena inti yang
 * hendak dibuktikan justru ada di sana: bahwa halaman benar-benar dipotong
 * di server dan bukan sekadar disembunyikan dari tampilan.
 */
class AksesBerkasBukuTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Disk buku dan disk berkas sementara keduanya 'local', sehingga satu
        // pemalsuan cukup untuk mengisolasi seluruh berkas selama pengujian.
        Storage::fake('local');
    }

    public function test_tamu_tidak_dapat_mengambil_berkas_maupun_mengunduh(): void
    {
        $buku = $this->buatBuku(['access_mode' => Book::AKSES_PENUH]);

        $this->get(route('katalog.berkas', $buku))->assertRedirect(route('login'));
        $this->get(route('katalog.unduh', $buku))->assertRedirect(route('login'));
    }

    public function test_buku_baca_saja_tidak_dapat_diunduh(): void
    {
        $prodi = Prodi::factory()->create();
        $mahasiswa = User::factory()->mahasiswa($prodi)->create();
        $buku = $this->buatBuku([
            'prodi_id' => $prodi->id,
            'access_mode' => Book::AKSES_BACA_SAJA,
        ]);

        $this->actingAs($mahasiswa)
            ->get(route('katalog.unduh', $buku))
            ->assertForbidden();

        // Penolakan tidak boleh meninggalkan catatan unduhan palsu.
        $this->assertSame(0, DownloadLog::count());
    }

    public function test_buku_baca_saja_tetap_boleh_dibaca_di_penampil(): void
    {
        $prodi = Prodi::factory()->create();
        $mahasiswa = User::factory()->mahasiswa($prodi)->create();
        $buku = $this->buatBuku([
            'prodi_id' => $prodi->id,
            'access_mode' => Book::AKSES_BACA_SAJA,
            'watermark_enabled' => false,
        ]);

        $this->actingAs($mahasiswa)
            ->get(route('katalog.berkas', $buku))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_buku_akses_penuh_dapat_diunduh_dan_tercatat(): void
    {
        $prodi = Prodi::factory()->create();
        $mahasiswa = User::factory()->mahasiswa($prodi)->create();
        $buku = $this->buatBuku([
            'prodi_id' => $prodi->id,
            'access_mode' => Book::AKSES_PENUH,
            'watermark_enabled' => false,
        ]);

        $respons = $this->actingAs($mahasiswa)->get(route('katalog.unduh', $buku));

        $respons->assertOk();
        $this->assertStringContainsString('attachment;', (string) $respons->headers->get('content-disposition'));
        $this->assertStringContainsString(Str::slug($buku->title).'.pdf', (string) $respons->headers->get('content-disposition'));

        // Tanpa pemotongan dan tanpa stempel, berkas disalurkan apa adanya.
        $this->assertSame(
            Storage::disk('local')->get($buku->file_path),
            file_get_contents($this->jalurBerkas($respons)),
        );

        $this->assertSame(1, DownloadLog::count());
        $catatan = DownloadLog::first();
        $this->assertSame($buku->id, $catatan->book_id);
        $this->assertSame($mahasiswa->id, $catatan->user_id);
        $this->assertSame(Book::AKSES_PENUH, $catatan->mode);
    }

    public function test_buku_akses_sebagian_hanya_mengirim_halaman_yang_diizinkan(): void
    {
        $this->lewatiTanpaQpdf();

        $prodi = Prodi::factory()->create();
        $mahasiswa = User::factory()->mahasiswa($prodi)->create();
        $buku = $this->buatBuku([
            'prodi_id' => $prodi->id,
            'access_mode' => Book::AKSES_SEBAGIAN,
            'download_page_start' => 3,
            'download_page_end' => 5,
            'watermark_enabled' => false,
        ], halaman: 12);

        $respons = $this->actingAs($mahasiswa)->get(route('katalog.unduh', $buku));

        $respons->assertOk();
        $this->assertStringContainsString(
            Str::slug($buku->title).'-hal-3-5.pdf',
            (string) $respons->headers->get('content-disposition'),
        );

        // Inti seluruh perbaikan: berkas yang terkirim memang hanya 3 halaman,
        // bukan 12 halaman yang halamannya disembunyikan.
        $this->assertSame(3, app(Qpdf::class)->jumlahHalaman($this->jalurBerkas($respons)));
    }

    public function test_stempel_identitas_menempel_pada_berkas_unduhan(): void
    {
        $this->lewatiTanpaQpdf();

        $prodi = Prodi::factory()->create();
        $mahasiswa = User::factory()->mahasiswa($prodi)->create();
        $buku = $this->buatBuku([
            'prodi_id' => $prodi->id,
            'access_mode' => Book::AKSES_PENUH,
            'watermark_enabled' => true,
        ], halaman: 6);

        $respons = $this->actingAs($mahasiswa)->get(route('katalog.unduh', $buku));
        $respons->assertOk();

        $jalurHasil = $this->jalurBerkas($respons);

        // Isinya berubah karena distempel, tetapi jumlah halamannya tetap utuh.
        $this->assertNotSame(
            Storage::disk('local')->get($buku->file_path),
            file_get_contents($jalurHasil),
        );
        $this->assertSame(6, app(Qpdf::class)->jumlahHalaman($jalurHasil));
    }

    public function test_unduhan_ditolak_bila_prodi_mematikan_izin_unduh(): void
    {
        $prodi = Prodi::factory()->unduhMati()->create();
        $mahasiswa = User::factory()->mahasiswa($prodi)->create();
        $buku = $this->buatBuku([
            'prodi_id' => $prodi->id,
            'access_mode' => Book::AKSES_PENUH,
        ]);

        $this->actingAs($mahasiswa)
            ->get(route('katalog.unduh', $buku))
            ->assertForbidden();

        $this->assertSame(0, DownloadLog::count());
    }

    public function test_superadmin_tetap_dapat_mengunduh_meski_prodi_mematikan_izin(): void
    {
        $prodi = Prodi::factory()->unduhMati()->create();
        $superAdmin = User::factory()->superAdmin()->create();
        $buku = $this->buatBuku([
            'prodi_id' => $prodi->id,
            'access_mode' => Book::AKSES_PENUH,
            'watermark_enabled' => false,
        ]);

        $this->actingAs($superAdmin)
            ->get(route('katalog.unduh', $buku))
            ->assertOk();
    }

    public function test_buku_prodi_lain_menjawab_404_bukan_403(): void
    {
        $prodiSendiri = Prodi::factory()->create();
        $prodiLain = Prodi::factory()->create();
        $mahasiswa = User::factory()->mahasiswa($prodiSendiri)->create();
        $buku = $this->buatBuku([
            'prodi_id' => $prodiLain->id,
            'access_mode' => Book::AKSES_PENUH,
        ]);

        // 404, bukan 403: keberadaan buku milik prodi lain tidak diungkap.
        $this->actingAs($mahasiswa)
            ->get(route('katalog.unduh', $buku))
            ->assertNotFound();

        $this->actingAs($mahasiswa)
            ->get(route('katalog.berkas', $buku))
            ->assertNotFound();
    }

    public function test_buku_belum_terbit_tidak_dapat_diakses_mahasiswa(): void
    {
        $prodi = Prodi::factory()->create();
        $mahasiswa = User::factory()->mahasiswa($prodi)->create();
        $buku = $this->buatBuku([
            'prodi_id' => $prodi->id,
            'access_mode' => Book::AKSES_PENUH,
            'is_published' => false,
        ]);

        $this->actingAs($mahasiswa)
            ->get(route('katalog.berkas', $buku))
            ->assertNotFound();

        $this->actingAs($mahasiswa)
            ->get(route('katalog.unduh', $buku))
            ->assertNotFound();
    }

    public function test_pengguna_nonaktif_ditolak_meski_bukunya_umum(): void
    {
        $nonaktif = User::factory()->mahasiswa()->nonaktif()->create();
        $buku = $this->buatBuku([
            'prodi_id' => null,
            'access_mode' => Book::AKSES_PENUH,
        ]);

        // Diperiksa di tingkat Gate, karena middleware `active` sudah menahannya
        // lebih dulu di tingkat HTTP. Keduanya harus menolak, bukan salah satu.
        $this->assertTrue(Gate::forUser($nonaktif)->denies('baca', $buku));
        $this->assertTrue(Gate::forUser($nonaktif)->denies('unduh', $buku));
    }

    public function test_buku_umum_dapat_diunduh_mahasiswa_prodi_mana_pun(): void
    {
        $prodi = Prodi::factory()->create();
        $mahasiswa = User::factory()->mahasiswa($prodi)->create();
        $buku = $this->buatBuku([
            'prodi_id' => null,
            'access_mode' => Book::AKSES_PENUH,
            'watermark_enabled' => false,
        ]);

        $this->actingAs($mahasiswa)
            ->get(route('katalog.unduh', $buku))
            ->assertOk();

        $this->assertSame(1, DownloadLog::count());
    }

    /**
     * Membuat buku beserta berkas PDF sungguhan di disk palsu.
     */
    private function buatBuku(array $atribut = [], int $halaman = 12): Book
    {
        $buku = Book::factory()->create(array_merge([
            // Nama berkas dibuat unik agar beberapa buku dalam satu tes tidak
            // saling menimpa berkasnya.
            'file_path' => 'books/'.Str::uuid()->toString().'.pdf',
            'page_count' => $halaman,
        ], $atribut));

        $this->tulisPdf($buku->file_path, $halaman);

        return $buku;
    }

    /**
     * Menulis PDF sungguhan berisi sejumlah halaman bernomor.
     */
    private function tulisPdf(string $jalurRelatif, int $halaman): void
    {
        $pdf = new \FPDF('P', 'mm', 'A4');

        for ($nomor = 1; $nomor <= $halaman; $nomor++) {
            $pdf->AddPage();
            $pdf->SetFont('Helvetica', '', 24);
            $pdf->Cell(0, 20, "Halaman {$nomor}", 0, 1, 'C');
        }

        Storage::disk('local')->put($jalurRelatif, $pdf->Output('S'));
    }

    /**
     * Mengambil jalur berkas nyata dari respons unduhan.
     */
    private function jalurBerkas(TestResponse $respons): string
    {
        $dasar = $respons->baseResponse;

        $this->assertInstanceOf(
            BinaryFileResponse::class,
            $dasar,
            'Respons unduhan seharusnya mengirim berkas, bukan isi biasa.',
        );

        return $dasar->getFile()->getPathname();
    }

    /**
     * Melewati tes yang memang membutuhkan qpdf bila peralatannya belum ada.
     */
    private function lewatiTanpaQpdf(): void
    {
        if (! app(Qpdf::class)->tersedia()) {
            $this->markTestSkipped('qpdf tidak tersedia di lingkungan ini.');
        }
    }
}