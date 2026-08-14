<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Prodi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Penjaga untuk fitur Koleksi Saya.
 *
 * Sampai berkas ini ada, KoleksiController, tabel book_saves, dan komponen
 * pita simpan tidak pernah dijalankan satu pun pengujian — sehingga suite
 * yang hijau tetap hijau meskipun controllernya berada di folder yang salah.
 */
class KoleksiTest extends TestCase
{
    use RefreshDatabase;

    public function test_tamu_diarahkan_ke_halaman_masuk(): void
    {
        $this->get('/koleksi')->assertRedirect('/login');
    }

    public function test_mahasiswa_dapat_menyimpan_buku(): void
    {
        $mahasiswa = User::factory()->mahasiswa()->create();
        $buku = Book::factory()->create(['title' => 'Etika Profesi']);

        $this->actingAs($mahasiswa)
            ->from(route('koleksi.index'))
            ->post(route('koleksi.simpan', $buku))
            ->assertRedirect(route('koleksi.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('book_saves', [
            'user_id' => $mahasiswa->id,
            'book_id' => $buku->id,
        ]);
    }

    public function test_menyimpan_dua_kali_tidak_menggandakan_baris(): void
    {
        $mahasiswa = User::factory()->mahasiswa()->create();
        $buku = Book::factory()->create();

        // Klik ganda, atau penekanan tombol kembali lalu simpan lagi. Tanpa
        // penjagaan, kunci unik pada book_saves akan melempar 500 ke muka
        // mahasiswa alih-alih mengabaikan permintaan kedua.
        $this->actingAs($mahasiswa)->post(route('koleksi.simpan', $buku));
        $this->actingAs($mahasiswa)->post(route('koleksi.simpan', $buku))->assertRedirect();

        $this->assertDatabaseCount('book_saves', 1);
    }

    public function test_mahasiswa_dapat_melepas_buku(): void
    {
        $mahasiswa = User::factory()->mahasiswa()->create();
        $buku = Book::factory()->create();
        $mahasiswa->bukuTersimpan()->attach($buku->id);

        $this->actingAs($mahasiswa)
            ->from(route('koleksi.index'))
            ->delete(route('koleksi.lepas', $buku))
            ->assertRedirect(route('koleksi.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseCount('book_saves', 0);
    }

    public function test_halaman_koleksi_menampilkan_buku_tersimpan(): void
    {
        $mahasiswa = User::factory()->mahasiswa()->create();
        $tersimpan = Book::factory()->create(['title' => 'Metodologi Penelitian']);
        Book::factory()->create(['title' => 'Buku Yang Tidak Disimpan']);

        $mahasiswa->bukuTersimpan()->attach($tersimpan->id);

        $this->actingAs($mahasiswa)
            ->get(route('koleksi.index'))
            ->assertOk()
            ->assertSee('Metodologi Penelitian')
            ->assertDontSee('Buku Yang Tidak Disimpan');
    }

    public function test_koleksi_tidak_menampilkan_simpanan_pengguna_lain(): void
    {
        $prodi = Prodi::factory()->create();
        $saya = User::factory()->mahasiswa($prodi)->create();
        $oranglain = User::factory()->mahasiswa($prodi)->create();

        $buku = Book::factory()->create(['title' => 'Rahasia Orang Lain']);
        $oranglain->bukuTersimpan()->attach($buku->id);

        $this->actingAs($saya)
            ->get(route('koleksi.index'))
            ->assertOk()
            ->assertDontSee('Rahasia Orang Lain');
    }

    public function test_buku_prodi_lain_tidak_dapat_disimpan(): void
    {
        $prodiLain = Prodi::factory()->create();
        $mahasiswa = User::factory()->mahasiswa()->create();
        $buku = Book::factory()->create(['prodi_id' => $prodiLain->id]);

        // Alamatnya dapat ditebak siapa saja; penjagaan harus ada di server,
        // bukan sekadar pada tombol yang tidak digambar.
        $this->actingAs($mahasiswa)
            ->post(route('koleksi.simpan', $buku))
            ->assertNotFound();

        $this->assertDatabaseCount('book_saves', 0);
    }

    public function test_buku_draf_tidak_dapat_disimpan(): void
    {
        $mahasiswa = User::factory()->mahasiswa()->create();
        $buku = Book::factory()->create(['is_published' => false]);

        $this->actingAs($mahasiswa)
            ->post(route('koleksi.simpan', $buku))
            ->assertNotFound();

        $this->assertDatabaseCount('book_saves', 0);
    }

    public function test_tamu_tidak_dapat_menyimpan(): void
    {
        $buku = Book::factory()->create();

        $this->post(route('koleksi.simpan', $buku))->assertRedirect('/login');

        $this->assertDatabaseCount('book_saves', 0);
    }

    public function test_pita_berubah_menjadi_tombol_lepas_setelah_disimpan(): void
    {
        $mahasiswa = User::factory()->mahasiswa()->create();
        $buku = Book::factory()->create(['title' => 'Literasi Digital']);

        // Sebelum disimpan, beranda hanya boleh menawarkan penyimpanan.
        $this->actingAs($mahasiswa)
            ->get(route('beranda.saya'))
            ->assertOk()
            ->assertSee('Literasi Digital')
            ->assertDontSee('name="_method" value="DELETE"', false);

        $mahasiswa->bukuTersimpan()->attach($buku->id);

        // Sesudahnya, pita yang sama harus berubah menjadi pelepas. Uji ini
        // sekaligus membuktikan scope denganStatusSimpan benar-benar terpasang
        // di controller: tanpa scope, sudahDisimpan() selalu mengembalikan false
        // dan halamannya tetap tampak wajar.
        $this->actingAs($mahasiswa)
            ->get(route('beranda.saya'))
            ->assertOk()
            ->assertSee('name="_method" value="DELETE"', false);
    }

    public function test_penanda_halaman_tampil_di_koleksi(): void
    {
        $mahasiswa = User::factory()->mahasiswa()->create();
        $buku = Book::factory()->create(['title' => 'Panduan Skripsi']);

        DB::table('bookmarks')->insert([
            'user_id' => $mahasiswa->id,
            'book_id' => $buku->id,
            'page' => 7,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($mahasiswa)
            ->get(route('koleksi.index', ['tab' => 'penanda']))
            ->assertOk()
            ->assertSee('Panduan Skripsi');
    }
}