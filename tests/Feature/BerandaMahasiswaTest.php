<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Book;
use App\Models\DownloadLog;
use App\Models\Prodi;
use App\Models\ReadingProgress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Seksi-seksi baru beranda mahasiswa: Sedang ramai, strip Lanjutkan,
 * dan pencarian cepat di kepala halaman.
 */
class BerandaMahasiswaTest extends TestCase
{
    use RefreshDatabase;

    public function test_seksi_ramai_mengurutkan_buku_terhangat_pekan_ini(): void
    {
        $prodi = Prodi::factory()->create();
        $mahasiswa = User::factory()->mahasiswa($prodi)->create();

        $ramai = Book::factory()->create(['prodi_id' => $prodi->id, 'title' => 'Buku Paling Ramai']);
        $hangat = Book::factory()->create(['prodi_id' => $prodi->id, 'title' => 'Buku Cukup Hangat']);
        Book::factory()->create(['prodi_id' => $prodi->id, 'title' => 'Buku Sepi']);

        foreach (range(1, 3) as $i) {
            DownloadLog::create([
                'book_id' => $ramai->id,
                'user_id' => $mahasiswa->id,
                'prodi_id' => $prodi->id,
                'mode' => Book::AKSES_PENUH,
            ]);
        }

        $hangat->tersimpanOleh()->attach($mahasiswa->id);

        $respons = $this->actingAs($mahasiswa)->get(route('beranda.saya'));
        $respons->assertOk();

        // Urutan dan penyaringan diperiksa lewat data yang dikirim ke view —
        // judul yang sama bisa muncul lagi di rak "Baru ditambahkan", jadi
        // markup halaman bukan tempat yang tepat untuk menilai urutan.
        $seksiRamai = $respons->viewData('ramai');
        $this->assertSame(['Buku Paling Ramai', 'Buku Cukup Hangat'], $seksiRamai->pluck('title')->all());
        $this->assertSame(3, $seksiRamai[0]->kehangatan);
        $this->assertSame(1, $seksiRamai[1]->kehangatan);

        $respons->assertSee('Sedang ramai')
            ->assertSee('Rak 01')
            ->assertSee('Rak 02')
            ->assertSee('Rak 03');
    }

    public function test_strip_lanjutan_mendarat_di_halaman_terakhir(): void
    {
        $prodi = Prodi::factory()->create();
        $mahasiswa = User::factory()->mahasiswa($prodi)->create();

        $hero = Book::factory()->create(['prodi_id' => $prodi->id, 'page_count' => 40]);
        $sisa = Book::factory()->create(['prodi_id' => $prodi->id, 'page_count' => 50]);

        ReadingProgress::create([
            'user_id' => $mahasiswa->id,
            'book_id' => $hero->id,
            'last_page' => 10,
            'total_pages' => 40,
        ]);

        ReadingProgress::create([
            'user_id' => $mahasiswa->id,
            'book_id' => $sisa->id,
            'last_page' => 5,
            'total_pages' => 50,
        ]);

        // Kedua catatan lahir di detik yang sama; paksa si kedua lebih tua
        // agar posisi hero dan sisa strip-nya pasti.
        DB::table('reading_progress')
            ->where('book_id', $sisa->id)
            ->update(['updated_at' => now()->subMinutes(5)]);

        $respons = $this->actingAs($mahasiswa)->get(route('beranda.saya'));
        $respons->assertOk()
            ->assertSee('?halaman=5', false)
            ->assertSee('Terbaca 10 persen');
    }

    public function test_pencarian_cepat_tersedia_di_kepala_halaman(): void
    {
        $mahasiswa = User::factory()->mahasiswa()->create();

        $this->actingAs($mahasiswa)
            ->get(route('beranda.saya'))
            ->assertOk()
            ->assertSee('name="q"', false)
            ->assertSee('Cari judul, penulis, atau isinya');
    }
}
