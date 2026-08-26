<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Prodi;
use App\Models\User;
use App\Support\Pdf\PembuatStempel;
use App\Support\Pdf\PengekstrakTeks;
use App\Support\Pdf\Qpdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;

class WatermarkTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_halaman_baca_tidak_lagi_membawa_watermark_kaki(): void
    {
        $buku = $this->buatBuku(['watermark_enabled' => true]);
        $mahasiswa = User::factory()->create(['prodi_id' => $buku->prodi_id]);

        // Watermark kaki dulu ditempel oleh browser, sehingga bisa dimatikan
        // siapa pun lewat DevTools. Kehadiran atribut ini kembali berarti
        // cara lama yang palsu itu dihidupkan lagi. Pemeriksaan kedua memakai
        // tanda "=" agar tidak salah menangkap atribut baru milik fitur
        // catatan (data-url-catatan-*), yang kebetulan berawalan sama.
        $this->actingAs($mahasiswa)
            ->get(route('katalog.baca', $buku))
            ->assertOk()
            ->assertDontSee('data-watermark-kaki', false)
            ->assertDontSee('data-url-catat=', false);
    }

    public function test_halaman_baca_tanpa_watermark_juga_tidak_membawa_watermark_kaki(): void
    {
        $buku = $this->buatBuku(['watermark_enabled' => false]);
        $mahasiswa = User::factory()->create(['prodi_id' => $buku->prodi_id]);

        $this->actingAs($mahasiswa)
            ->get(route('katalog.baca', $buku))
            ->assertOk()
            ->assertDontSee('data-watermark-kaki', false);
    }

    public function test_cap_layar_membawa_identitas_pembaca(): void
    {
        $buku = $this->buatBuku(['watermark_enabled' => true]);
        $mahasiswa = User::factory()->create(['prodi_id' => $buku->prodi_id]);

        // Cap miring di layar memang hanya penghambat pemotretan layar,
        // bukan pengaman. Yang diuji: identitasnya benar-benar terpasang.
        $this->actingAs($mahasiswa)
            ->get(route('katalog.baca', $buku))
            ->assertOk()
            ->assertSee(
                'data-watermark="'.e($mahasiswa->name.' — '.$mahasiswa->email).'"',
                false,
            );
    }

    public function test_stempel_tertanam_di_berkas_unduhan_saat_diaktifkan(): void
    {
        $this->lewatiTanpaQpdf();

        $buku = $this->buatBuku(['watermark_enabled' => true], halaman: 5);
        $mahasiswa = User::factory()->create(['prodi_id' => $buku->prodi_id]);

        $respons = $this->actingAs($mahasiswa)->get(route('katalog.unduh', $buku));
        $respons->assertOk();

        $jalurUnduhan = $this->jalurBerkas($respons);
        $asli = Storage::disk('local')->get($buku->file_path);

        // Berkas yang diterima harus sudah berbeda dari yang tersimpan:
        // di situlah bukti stempelnya menempel di dalam PDF, bukan di layar.
        $this->assertNotSame(
            $asli,
            (string) file_get_contents($jalurUnduhan),
            'Berkas unduhan identik dengan berkas asli, berarti stempel tidak menempel.',
        );

        // Menstempel tidak boleh menambah, menghapus, atau merusak halaman.
        $this->assertSame(5, app(Qpdf::class)->jumlahHalaman($jalurUnduhan));
    }

    public function test_berkas_unduhan_tetap_asli_saat_watermark_dinonaktifkan(): void
    {
        $buku = $this->buatBuku(['watermark_enabled' => false], halaman: 5);
        $mahasiswa = User::factory()->create(['prodi_id' => $buku->prodi_id]);

        $respons = $this->actingAs($mahasiswa)->get(route('katalog.unduh', $buku));
        $respons->assertOk();

        // Menstempel buku yang tidak diminta distempel sama saja dengan cacat.
        $this->assertSame(
            Storage::disk('local')->get($buku->file_path),
            (string) file_get_contents($this->jalurBerkas($respons)),
            'Berkas ikut distempel padahal watermark dimatikan.',
        );
    }

    public function test_teks_stempel_memuat_nama_email_dan_tanggal(): void
    {
        $mahasiswa = User::factory()->create([
            'name' => 'Ahmad Nugroho',
            'email' => 'ahmad.nugroho@umy.ac.id',
        ]);

        $teks = app(PembuatStempel::class)->teksUntuk($mahasiswa, Carbon::parse('2026-08-08'));

        $this->assertStringContainsString('Ahmad Nugroho', $teks);
        $this->assertStringContainsString('ahmad.nugroho@umy.ac.id', $teks);
        $this->assertStringContainsString('08/08/2026', $teks);
    }

    public function test_stempel_muncul_di_kepala_dan_kaki_setiap_halaman(): void
    {
        $this->lewatiTanpaQpdf();

        $buku = $this->buatBuku(['watermark_enabled' => true], halaman: 3);
        $mahasiswa = User::factory()->create(['prodi_id' => $buku->prodi_id]);

        $respons = $this->actingAs($mahasiswa)->get(route('katalog.unduh', $buku));
        $respons->assertOk();

        // Dua baris per halaman × tiga halaman. Satu baris tunggal mudah
        // lenyap oleh pemangkasan; keberadaan pasangannya yang dijaga di sini.
        $jumlah = substr_count(
            (string) app(PengekstrakTeks::class)->ekstrak(
                $this->jalurBerkas($respons),
            ),
            'Diunduh oleh',
        );

        $this->assertSame(6, $jumlah);
    }

    /**
     * Buku terbit beserta PDF sungguhan di disk lokal.
     */
    private function buatBuku(array $atribut = [], int $halaman = 4): Book
    {
        $prodi = Prodi::factory()->create();

        $buku = Book::factory()->create(array_merge([
            'prodi_id' => $prodi->id,
            'is_published' => true,
            'access_mode' => Book::AKSES_PENUH,
            'file_path' => 'books/'.Str::uuid()->toString().'.pdf',
            'page_count' => $halaman,
        ], $atribut));

        $this->tulisPdf($buku->file_path, $halaman);

        return $buku;
    }

    /**
     * PDF sungguhan, karena qpdf menolak berkas tiruan.
     */
    private function tulisPdf(string $jalurRelatif, int $halaman): void
    {
        $pdf = new \FPDF('P', 'mm', 'A4');
        $pdf->SetFont('Helvetica', '', 24);

        for ($nomor = 1; $nomor <= $halaman; $nomor++) {
            $pdf->AddPage();
            $pdf->Cell(0, 20, "Halaman {$nomor}", 0, 1, 'C');
        }

        Storage::disk('local')->put($jalurRelatif, $pdf->Output('S'));
    }

    /**
     * Jalur berkas nyata di balik respons unduhan.
     *
     * deleteFileAfterSend tidak berjalan pada feature test, jadi berkasnya
     * masih dapat dibaca setelah respons diterima.
     */
    private function jalurBerkas(TestResponse $respons): string
    {
        $this->assertInstanceOf(BinaryFileResponse::class, $respons->baseResponse);

        return $respons->baseResponse->getFile()->getPathname();
    }

    private function lewatiTanpaQpdf(): void
    {
        if (! app(Qpdf::class)->tersedia()) {
            $this->markTestSkipped('qpdf tidak tersedia di lingkungan ini.');
        }
    }
}
