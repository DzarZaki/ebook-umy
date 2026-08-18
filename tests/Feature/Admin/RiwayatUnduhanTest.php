<?php

namespace Tests\Feature\Admin;

use App\Models\Book;
use App\Models\DownloadLog;
use App\Models\Prodi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Riwayat unduhan adalah catatan peristiwa: ia harus bertahan lebih lama
 * daripada akun yang melakukannya, agar statistik tidak berubah surut dan
 * jejak penyebaran berkas masih dapat ditelusuri berbulan-bulan kemudian.
 */
class RiwayatUnduhanTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Menyiapkan satu prodi berisi satu dosen, satu mahasiswa, satu buku,
     * dan satu catatan unduhan.
     *
     * @return array{0: User, 1: User, 2: Book}
     */
    private function siapkanCatatan(): array
    {
        $prodi = Prodi::factory()->create();
        $dosen = User::factory()->admin($prodi)->create();
        $mahasiswa = User::factory()->mahasiswa($prodi)->create();

        $buku = Book::factory()->create([
            'title' => 'Dasar Statistika',
            'prodi_id' => $prodi->id,
        ]);

        DownloadLog::create([
            'book_id' => $buku->id,
            'user_id' => $mahasiswa->id,
            'prodi_id' => $prodi->id,
            'mode' => Book::AKSES_PENUH,
        ]);

        return [$dosen, $mahasiswa, $buku];
    }

    public function test_catatan_unduhan_bertahan_setelah_akun_pengunduh_dihapus(): void
    {
        [, $mahasiswa] = $this->siapkanCatatan();

        $mahasiswa->delete();

        $this->assertDatabaseCount('download_logs', 1);
        $this->assertNull(
            DownloadLog::first()->user_id,
            'Kaitan pengunduh seharusnya dikosongkan, bukan menghapus barisnya.'
        );
    }

    public function test_statistik_masih_menghitung_unduhan_dari_akun_yang_dihapus(): void
    {
        [$dosen, $mahasiswa] = $this->siapkanCatatan();

        $mahasiswa->delete();

        $this->actingAs($dosen)
            ->get(route('admin.statistik'))
            ->assertOk()
            ->assertSee('Dasar Statistika')
            ->assertSee('Pengguna dihapus');
    }

    /** Sebaliknya: catatan buku yang dihapus permanen memang harus ikut hilang. */
    public function test_catatan_unduhan_ikut_terhapus_saat_buku_dihapus_permanen(): void
    {
        [, , $buku] = $this->siapkanCatatan();

        $buku->forceDelete();

        $this->assertDatabaseCount('download_logs', 0);
    }
}