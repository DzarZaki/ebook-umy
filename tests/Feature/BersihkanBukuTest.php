<?php

namespace Tests\Feature;

use App\Models\Book;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BersihkanBukuTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');
    }

    /** Buku lengkap dengan berkas nyata di penyimpanan palsu. */
    private function bukuBerkas(array $ubah = []): Book
    {
        $buku = Book::factory()->create(array_merge([
            'file_path' => 'books/'.uniqid().'.pdf',
            'cover_path' => 'covers/'.uniqid().'.jpg',
        ], $ubah));

        Storage::disk('local')->put($buku->file_path, 'isi pdf tiruan');
        Storage::disk('public')->put($buku->cover_path, 'isi gambar tiruan');

        return $buku;
    }

    /** Membuang buku ke tempat sampah dengan tanggal yang dimundurkan. */
    private function buangSejak(Book $buku, int $hari): void
    {
        $buku->delete();
        $buku->forceFill(['deleted_at' => now()->subDays($hari)])->saveQuietly();
    }

    /**
     * Memundurkan waktu ubah berkas.
     * Tanpa ini, setiap berkas uji berusia nol detik dan selalu terlindung
     * oleh pagar 24 jam, sehingga perburuan yatim tidak pernah benar-benar teruji.
     */
    private function tuakanBerkas(string $disk, string $jalur, int $hari): void
    {
        touch(Storage::disk($disk)->path($jalur), now()->subDays($hari)->getTimestamp());
        clearstatcache();
    }

    public function test_buku_yang_lewat_masa_tenggang_dilenyapkan_beserta_berkasnya(): void
    {
        $buku = $this->bukuBerkas();
        $this->buangSejak($buku, 40);

        $this->artisan('ebook:bersihkan-buku', ['--terapkan' => true])->assertSuccessful();

        $this->assertSame(0, Book::withTrashed()->count());
        Storage::disk('local')->assertMissing($buku->file_path);
        Storage::disk('public')->assertMissing($buku->cover_path);
    }

    public function test_buku_yang_masih_dalam_masa_tenggang_tidak_disentuh(): void
    {
        $buku = $this->bukuBerkas();
        $this->buangSejak($buku, 5);

        // Berkasnya sengaja dituakan agar hanya kepemilikanlah yang melindunginya.
        $this->tuakanBerkas('local', $buku->file_path, 5);
        $this->tuakanBerkas('public', $buku->cover_path, 5);

        $this->artisan('ebook:bersihkan-buku', ['--terapkan' => true])->assertSuccessful();

        $this->assertSoftDeleted('books', ['id' => $buku->id]);
        Storage::disk('local')->assertExists($buku->file_path);
        Storage::disk('public')->assertExists($buku->cover_path);
    }

    public function test_tanpa_terapkan_tidak_ada_yang_dihapus(): void
    {
        $buku = $this->bukuBerkas();
        $this->buangSejak($buku, 40);

        $this->artisan('ebook:bersihkan-buku')->assertSuccessful();

        $this->assertSoftDeleted('books', ['id' => $buku->id]);
        Storage::disk('local')->assertExists($buku->file_path);
        Storage::disk('public')->assertExists($buku->cover_path);
    }

    /**
     * Penjaga terpenting di berkas ini.
     *
     * Buku yang masih hidup, dengan berkas berumur dua bulan, harus tetap utuh.
     * Kalau tes ini merah, artinya perintah pembersih memakan koleksi yang aktif.
     */
    public function test_buku_yang_masih_hidup_tidak_pernah_disentuh(): void
    {
        $buku = $this->bukuBerkas();
        $this->tuakanBerkas('local', $buku->file_path, 60);
        $this->tuakanBerkas('public', $buku->cover_path, 60);

        $this->artisan('ebook:bersihkan-buku', ['--terapkan' => true])->assertSuccessful();

        $this->assertDatabaseHas('books', ['id' => $buku->id, 'deleted_at' => null]);
        Storage::disk('local')->assertExists($buku->file_path);
        Storage::disk('public')->assertExists($buku->cover_path);
    }

    public function test_berkas_yatim_yang_sudah_lama_dihapus(): void
    {
        // Tabel sengaja diisi agar rem pengaman tidak aktif.
        $this->bukuBerkas();

        Storage::disk('local')->put('books/sisa-unggahan-gagal.pdf', 'sisa');
        $this->tuakanBerkas('local', 'books/sisa-unggahan-gagal.pdf', 3);

        $this->artisan('ebook:bersihkan-buku', ['--terapkan' => true])->assertSuccessful();

        Storage::disk('local')->assertMissing('books/sisa-unggahan-gagal.pdf');
    }

    /**
     * Berkas yang baru saja ditulis bisa jadi milik unggahan yang sedang berjalan,
     * barisnya belum sempat tercatat. Pagar 24 jam melindunginya.
     */
    public function test_berkas_yatim_yang_baru_diunggah_tidak_disentuh(): void
    {
        $this->bukuBerkas();

        Storage::disk('local')->put('books/sedang-diproses.pdf', 'baru');

        $this->artisan('ebook:bersihkan-buku', ['--terapkan' => true])->assertSuccessful();

        Storage::disk('local')->assertExists('books/sedang-diproses.pdf');
    }

    /**
     * Tabel kosong hampir selalu berarti salah sambung database.
     * Dalam keadaan itu perintah harus menolak menyentuh penyimpanan.
     */
    public function test_perburuan_yatim_dilewati_saat_tabel_buku_kosong(): void
    {
        Storage::disk('local')->put('books/yatim.pdf', 'isi');
        $this->tuakanBerkas('local', 'books/yatim.pdf', 10);

        $this->artisan('ebook:bersihkan-buku', ['--terapkan' => true])->assertSuccessful();

        Storage::disk('local')->assertExists('books/yatim.pdf');
    }

    public function test_masa_tenggang_tidak_masuk_akal_ditolak(): void
    {
        $this->artisan('ebook:bersihkan-buku', ['--hari' => 0, '--terapkan' => true])->assertFailed();
    }
}