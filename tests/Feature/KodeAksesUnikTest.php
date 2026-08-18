<?php

namespace Tests\Feature;

use App\Models\Prodi;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Kode akses menentukan prodi seorang mahasiswa, dan prodi menentukan buku
 * mana yang boleh ia baca. Karena itu bentuk penyimpanannya harus tunggal.
 */
class KodeAksesUnikTest extends TestCase
{
    use RefreshDatabase;

    public function test_kode_akses_disimpan_dalam_huruf_besar(): void
    {
        $prodi = Prodi::factory()->create(['access_code' => '  pai-2026 ']);

        $this->assertSame('PAI-2026', $prodi->refresh()->access_code);
    }

    public function test_kode_akses_kosong_disimpan_sebagai_null(): void
    {
        $prodi = Prodi::factory()->create(['access_code' => '   ']);

        $this->assertNull($prodi->refresh()->access_code);
    }

    public function test_pencarian_menerima_huruf_kecil_dan_spasi_berlebih(): void
    {
        $prodi = Prodi::factory()->create(['access_code' => 'MNJ-2026']);

        $this->assertSame($prodi->id, Prodi::cariDenganKode(' mnj-2026 ')?->id);
        $this->assertSame($prodi->id, Prodi::cariDenganKode('MNJ-2026')?->id);
    }

    /**
     * Inti perbaikan: dua prodi tidak boleh memakai kode yang hanya berbeda
     * huruf besar-kecil, karena satu kode akan cocok dengan dua prodi dan
     * mahasiswa terdaftar ke prodi yang ditentukan urutan baris.
     */
    public function test_kode_kembar_beda_huruf_ditolak_basis_data(): void
    {
        Prodi::factory()->create(['access_code' => 'PAI-2026']);

        $this->expectException(QueryException::class);

        Prodi::factory()->create(['access_code' => 'pai-2026']);
    }

    public function test_prodi_tanpa_kode_tidak_pernah_cocok(): void
    {
        Prodi::factory()->create(['access_code' => null]);
        Prodi::factory()->create(['access_code' => null]);

        $this->assertNull(Prodi::cariDenganKode('APA-SAJA'));
        $this->assertNull(Prodi::cariDenganKode(null));
        $this->assertNull(Prodi::cariDenganKode('   '));
    }
}