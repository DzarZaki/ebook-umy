<?php

namespace Tests\Feature\Admin;

use App\Models\Book;
use App\Models\DownloadLog;
use App\Models\Prodi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatistikTest extends TestCase
{
    use RefreshDatabase;

    public function test_mahasiswa_tidak_boleh_membuka_statistik(): void
    {
        $this->actingAs(User::factory()->mahasiswa()->create())
            ->get(route('admin.statistik'))
            ->assertForbidden();
    }

    public function test_dosen_melihat_jumlah_unduhan_bukunya(): void
    {
        $prodi = Prodi::factory()->create();
        $dosen = User::factory()->admin($prodi)->create();
        $mahasiswa = User::factory()->mahasiswa($prodi)->create();

        $buku = Book::factory()->create(['title' => 'Dasar Akuntansi', 'prodi_id' => $prodi->id]);

        DownloadLog::create([
            'book_id' => $buku->id, 'user_id' => $mahasiswa->id,
            'prodi_id' => $prodi->id, 'mode' => Book::AKSES_PENUH,
        ]);

        $this->actingAs($dosen)
            ->get(route('admin.statistik'))
            ->assertOk()
            ->assertSee('Dasar Akuntansi')
            ->assertSee($mahasiswa->name);
    }

    public function test_statistik_prodi_lain_tidak_ikut_terhitung(): void
    {
        $prodiA = Prodi::factory()->create();
        $prodiB = Prodi::factory()->create();
        $dosenA = User::factory()->admin($prodiA)->create();
        $mahasiswaB = User::factory()->mahasiswa($prodiB)->create();

        $bukuB = Book::factory()->create(['title' => 'Rahasia Prodi B', 'prodi_id' => $prodiB->id]);

        DownloadLog::create([
            'book_id' => $bukuB->id, 'user_id' => $mahasiswaB->id,
            'prodi_id' => $prodiB->id, 'mode' => Book::AKSES_PENUH,
        ]);

        $this->actingAs($dosenA)
            ->get(route('admin.statistik'))
            ->assertOk()
            ->assertDontSee('Rahasia Prodi B');
    }
}
