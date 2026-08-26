<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Prodi;
use App\Models\User;
use App\Support\Pdf\PengekstrakTeks;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Pencarian full-text isi buku.
 *
 * Mahasiswa yang mencari "fotosintesis" tidak peduli apakah ada dosen
 * yang menuliskannya pada judul — yang penting bukunya membahasnya.
 */
class PencarianIsiBukuTest extends TestCase
{
    use RefreshDatabase;

    public function test_ekstraktor_membaca_teks_dari_pdf(): void
    {
        $jalur = $this->tulisPdfSementara('Kata kunci fotosintesis terdapat di paragraf pembuka.');

        try {
            $teks = app(PengekstrakTeks::class)->ekstrak($jalur);

            $this->assertNotNull($teks);
            $this->assertStringContainsString('fotosintesis', mb_strtolower((string) $teks));
        } finally {
            @unlink($jalur);
        }
    }

    public function test_buku_ditemukan_lewat_isinya_meski_judulnya_asing(): void
    {
        $prodi = Prodi::factory()->create();
        $mahasiswa = User::factory()->mahasiswa($prodi)->create();

        Book::factory()->create([
            'prodi_id' => $prodi->id,
            'title' => 'Panduan Praktikum Biologi',
            'search_text' => 'Bab tiga membahas fotosintesis pada tumbuhan C3 dan C4 beserta perbedaan lajunya.',
        ]);

        $this->actingAs($mahasiswa)
            ->get(route('katalog.index', ['q' => 'fotosintesis']))
            ->assertOk()
            ->assertSee('Panduan Praktikum Biologi')
            ->assertSee('cocok di isi buku')
            ->assertSee('fotosintesis');
    }

    public function test_kecocokan_pada_judul_tidak_memunculkan_potongan_isi(): void
    {
        $prodi = Prodi::factory()->create();
        $mahasiswa = User::factory()->mahasiswa($prodi)->create();

        Book::factory()->create([
            'prodi_id' => $prodi->id,
            'title' => 'Fotosintesis Lanjut',
            'description' => 'Buku pegangan kuliah.',
            'search_text' => 'Isi bab satu tentang reaksi terang.',
        ]);

        $this->actingAs($mahasiswa)
            ->get(route('katalog.index', ['q' => 'fotosintesis']))
            ->assertOk()
            ->assertDontSee('cocok di isi buku');
    }

    public function test_wildcard_dari_pengguna_tidak_mengacaukan_pencarian(): void
    {
        $prodi = Prodi::factory()->create();
        $mahasiswa = User::factory()->mahasiswa($prodi)->create();

        Book::factory()->create([
            'prodi_id' => $prodi->id,
            'title' => 'Statistika Dasar',
            'search_text' => 'Tabel distribusi normal tersedia pada lampiran akhir buku.',
        ]);

        // Tanpa penanganan, %%% diproses sebagai wildcard dan memaksa
        // pencocokan sembarangan; dengan pembersihan ia hanya mencari teks
        // polos dan tetap menemukan buku lewat isinya.
        $this->actingAs($mahasiswa)
            ->get(route('katalog.index', ['q' => '%lampiran%']))
            ->assertOk()
            ->assertSee('Statistika Dasar');
    }

    public function test_perintah_indeks_mengejar_buku_yang_belum_terindeks(): void
    {
        Storage::fake('local');

        $buku = Book::factory()->create([
            'file_path' => 'books/'.Str::uuid()->toString().'.pdf',
            'search_text' => null,
        ]);

        Storage::disk('local')->put(
            $buku->file_path,
            $this->pdfBerisi('Unggas pelikan bermigrasi melewati selat setiap musim gugur.'),
        );

        $this->artisan('ebook:indeks-teks')->assertSuccessful();

        $buku->refresh();
        $this->assertNotNull($buku->search_text);
        $this->assertStringContainsString('pelikan', mb_strtolower((string) $buku->search_text));
    }

    /** PDF sederhana berisi satu frasa, ditulis ke berkas sementara. */
    private function tulisPdfSementara(string $isi): string
    {
        $jalur = tempnam(sys_get_temp_dir(), 'indeks').'.pdf';
        file_put_contents($jalur, $this->pdfBerisi($isi));

        return $jalur;
    }

    private function pdfBerisi(string $isi): string
    {
        $pdf = new \FPDF('P', 'mm', 'A4');
        $pdf->AddPage();
        $pdf->SetFont('Helvetica', '', 12);
        $pdf->Cell(0, 10, $isi);

        return $pdf->Output('S');
    }
}
