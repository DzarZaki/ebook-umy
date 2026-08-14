<?php

namespace Tests\Feature\Admin;

use App\Models\Book;
use App\Models\Category;
use App\Models\Prodi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BukuCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Semua unggahan diarahkan ke penyimpanan palsu agar tidak mengotori disk asli.
        Storage::fake('local');
        Storage::fake('public');
    }

    /**
     * PDF minimal yang benar-benar sah, berisi satu halaman.
     * Dipakai agar pemeriksaan tipe berkas dan penghitung halaman ikut teruji.
     */
    private function isiPdfMinimal(): string
    {
        return "%PDF-1.4\n"
            ."1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n"
            ."2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n"
            ."3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 612 792]>>endobj\n"
            ."trailer<</Root 1 0 R>>\n"
            .'%%EOF';
    }

    /** Berkas unggahan PDF tiruan yang lolos validasi tipe berkas. */
    private function berkasPdf(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('buku.pdf', $this->isiPdfMinimal());
    }

    /** Gambar sampul tiruan yang benar-benar berupa JPEG. */
    private function berkasSampul(): UploadedFile
    {
        return UploadedFile::fake()->image('sampul.jpg', 400, 600);
    }

    /** Data unggahan buku yang sah, boleh ditimpa sebagian. */
    private function dataValid(array $ubah = []): array
    {
        return array_merge([
            'title' => 'Pengantar Manajemen',
            'author' => 'Budi Santoso',
            'description' => 'Buku pengantar untuk mata kuliah manajemen.',
            'lingkup' => 'prodi',
            'category_id' => null,
            'berkas' => $this->berkasPdf(),
            'access_mode' => Book::AKSES_PENUH,
            'watermark_enabled' => 1,
            'is_published' => 1,
        ], $ubah);
    }

    public function test_dosen_dapat_mengunggah_buku_untuk_prodinya(): void
    {
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

    public function test_jumlah_halaman_terdeteksi_otomatis(): void
    {
        $prodi = Prodi::factory()->create();
        $dosen = User::factory()->admin($prodi)->create();

        $this->actingAs($dosen)->post('/admin/buku', $this->dataValid());

        $this->assertSame(1, Book::first()->page_count);
    }

    public function test_dosen_dapat_mengunggah_buku_umum(): void
    {
        $prodi = Prodi::factory()->create();
        $dosen = User::factory()->admin($prodi)->create();

        $this->actingAs($dosen)
            ->post('/admin/buku', $this->dataValid(['lingkup' => 'umum']))
            ->assertRedirect(route('admin.buku.index'));

        $this->assertDatabaseHas('books', [
            'title' => 'Pengantar Manajemen',
            'prodi_id' => null,
        ]);
    }

    public function test_judul_dengan_karakter_terlarang_ditolak(): void
    {
        $prodi = Prodi::factory()->create();
        $dosen = User::factory()->admin($prodi)->create();

        $this->actingAs($dosen)
            ->post('/admin/buku', $this->dataValid(['title' => 'Manajemen @#$%']))
            ->assertSessionHasErrors('title');
    }

    public function test_mode_sebagian_wajib_menyertakan_rentang_halaman(): void
    {
        $prodi = Prodi::factory()->create();
        $dosen = User::factory()->admin($prodi)->create();

        $this->actingAs($dosen)
            ->post('/admin/buku', $this->dataValid(['access_mode' => Book::AKSES_SEBAGIAN]))
            ->assertSessionHasErrors('download_page_start');
    }

    public function test_rentang_halaman_tidak_boleh_melebihi_isi_pdf(): void
    {
        $prodi = Prodi::factory()->create();
        $dosen = User::factory()->admin($prodi)->create();

        $this->actingAs($dosen)
            ->post('/admin/buku', $this->dataValid([
                'access_mode' => Book::AKSES_SEBAGIAN,
                'download_page_start' => 1,
                'download_page_end' => 99,
            ]))
            ->assertSessionHasErrors('download_page_end');
    }

    public function test_kategori_prodi_lain_tidak_boleh_dipakai(): void
    {
        $prodiA = Prodi::factory()->create();
        $prodiB = Prodi::factory()->create();
        $dosen = User::factory()->admin($prodiA)->create();

        $kategoriLain = Category::factory()->create([
            'prodi_id' => $prodiB->id,
            'created_by' => $dosen->id,
        ]);

        $this->actingAs($dosen)
            ->post('/admin/buku', $this->dataValid([
                'lingkup' => 'umum',
                'category_id' => $kategoriLain->id,
            ]))
            ->assertSessionHasErrors('category_id');
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

    public function test_update_buku_tanpa_pdf_baru_mempertahankan_berkas_lama(): void
    {
        $prodi = Prodi::factory()->create();
        $dosen = User::factory()->admin($prodi)->create();

        // Unggah buku awal dengan PDF.
        $this->actingAs($dosen)->post('/admin/buku', $this->dataValid());
        $buku = Book::first();
        $this->assertNotNull($buku);

        $jalurAsli = $buku->file_path;
        $jumlahHalamanAsli = $buku->page_count;

        Storage::disk('local')->assertExists($jalurAsli);

        // Update judul saja, tanpa mengirim berkas baru.
        $this->actingAs($dosen)
            ->put(route('admin.buku.update', $buku), [
                'title' => 'Pengantar Manajemen Edisi Revisi',
                'author' => 'Budi Santoso',
                'lingkup' => 'prodi',
                'access_mode' => Book::AKSES_PENUH,
                'watermark_enabled' => 1,
                'is_published' => 1,
            ])
            ->assertRedirect(route('admin.buku.index'));

        $buku->refresh();

        // Judul berubah, berkas dan jumlah halaman tetap.
        $this->assertSame('Pengantar Manajemen Edisi Revisi', $buku->title);
        $this->assertSame($jalurAsli, $buku->file_path);
        $this->assertSame($jumlahHalamanAsli, $buku->page_count);
        Storage::disk('local')->assertExists($jalurAsli);
    }

    /**
     * Menghapus buku membuangnya ke tempat sampah, bukan melenyapkannya.
     *
     * Berkasnya sengaja dipertahankan. Kalau berkas ikut terhapus, memulihkan
     * buku hanya akan mengembalikan baris data yang menunjuk ke berkas hantu.
     */
    public function test_menghapus_buku_membuangnya_ke_tempat_sampah_tanpa_menghapus_berkas(): void
    {
        $prodi = Prodi::factory()->create();
        $dosen = User::factory()->admin($prodi)->create();

        $this->actingAs($dosen)->post('/admin/buku', $this->dataValid());

        $buku = Book::first();
        $this->assertNotNull($buku, 'Buku gagal tersimpan saat proses unggah.');

        Storage::disk('local')->assertExists($buku->file_path);

        $this->actingAs($dosen)
            ->delete(route('admin.buku.destroy', $buku))
            ->assertRedirect(route('admin.buku.index'));

        // Barisnya bertanda terbuang, bukan hilang.
        $this->assertSoftDeleted('books', ['id' => $buku->id]);

        // Dan lenyap dari seluruh kueri biasa di aplikasi.
        $this->assertSame(0, Book::count());
        $this->assertSame(1, Book::onlyTrashed()->count());

        // Inti dari seluruh perubahan ini: berkasnya masih di tempatnya.
        Storage::disk('local')->assertExists($buku->file_path);
    }

    /** Sampul pun ikut selamat saat buku dibuang ke tempat sampah. */
    public function test_menghapus_buku_tidak_menghapus_sampulnya(): void
    {
        $prodi = Prodi::factory()->create();
        $dosen = User::factory()->admin($prodi)->create();

        $this->actingAs($dosen)->post('/admin/buku', $this->dataValid([
            'sampul' => $this->berkasSampul(),
        ]));

        $buku = Book::first();
        $this->assertNotNull($buku->cover_path, 'Sampul gagal tersimpan saat proses unggah.');

        Storage::disk('public')->assertExists($buku->cover_path);

        $this->actingAs($dosen)->delete(route('admin.buku.destroy', $buku));

        $this->assertSoftDeleted('books', ['id' => $buku->id]);
        Storage::disk('public')->assertExists($buku->cover_path);
    }

    /** Buku yang terbuang dapat dipulihkan lengkap dengan berkasnya. */
    public function test_buku_di_tempat_sampah_dapat_dipulihkan(): void
    {
        $prodi = Prodi::factory()->create();
        $dosen = User::factory()->admin($prodi)->create();

        $this->actingAs($dosen)->post('/admin/buku', $this->dataValid());
        $buku = Book::first();

        $this->actingAs($dosen)->delete(route('admin.buku.destroy', $buku));

        $terbuang = Book::onlyTrashed()->firstOrFail();
        $terbuang->restore();

        $this->assertSame(1, Book::count());
        $this->assertDatabaseHas('books', [
            'id' => $buku->id,
            'deleted_at' => null,
        ]);

        // Berkasnya tidak pernah ke mana-mana, jadi bukunya langsung terbaca lagi.
        Storage::disk('local')->assertExists($buku->file_path);
    }

    /**
     * Buku di tempat sampah masih memegang slug-nya di database.
     * Mengunggah ulang judul yang sama tidak boleh menabrak indeks unik.
     */
    public function test_slug_tidak_bentrok_dengan_buku_di_tempat_sampah(): void
    {
        $prodi = Prodi::factory()->create();
        $dosen = User::factory()->admin($prodi)->create();

        $this->actingAs($dosen)->post('/admin/buku', $this->dataValid());
        $lama = Book::first();
        $this->assertSame('pengantar-manajemen', $lama->slug);

        $this->actingAs($dosen)->delete(route('admin.buku.destroy', $lama));

        // Judul yang persis sama, diunggah ulang setelah yang lama dibuang.
        $this->actingAs($dosen)
            ->post('/admin/buku', $this->dataValid())
            ->assertRedirect(route('admin.buku.index'));

        $baru = Book::firstOrFail();

        $this->assertNotSame($lama->id, $baru->id);
        $this->assertSame('pengantar-manajemen-2', $baru->slug);
        $this->assertSame(2, Book::withTrashed()->count());
    }
}