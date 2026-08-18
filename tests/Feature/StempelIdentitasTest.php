<?php

namespace Tests\Feature;

use App\Support\Pdf\PembuatStempel;
use Tests\TestCase;

/**
 * Susunan huruf pada stempel harus sama di semua sistem operasi, dan tidak
 * boleh menghilangkan penanda identitas pengunduh.
 */
class StempelIdentitasTest extends TestCase
{
    private function stempel(): PembuatStempel
    {
        if (! function_exists('iconv')) {
            $this->markTestSkipped('Ekstensi iconv tidak tersedia di lingkungan ini.');
        }

        return PembuatStempel::dariKonfigurasi();
    }

    public function test_nama_beraksen_latin_dipertahankan_utuh(): void
    {
        $asal = 'Diunduh oleh Aímé Nüñez (aime.nunez@umy.ac.id) pada 18/08/2026';

        $hasil = $this->stempel()->amankan($asal);

        // Windows-1252 memuat huruf-huruf ini, jadi tidak ada yang perlu
        // dialihkan: hasilnya harus kembali persis sama saat dibalik.
        $this->assertSame($asal, iconv('Windows-1252', 'UTF-8', $hasil));
    }

    public function test_nama_non_latin_dialihkan_tanpa_meninggalkan_tanda_tanya(): void
    {
        $hasil = $this->stempel()->amankan(
            'Diunduh oleh Nguyễn Văn Tú (nguyen.tu@umy.ac.id) pada 18/08/2026'
        );

        $this->assertStringContainsString('nguyen.tu@umy.ac.id', $hasil);
        $this->assertStringContainsString('Nguy', $hasil);
        $this->assertStringNotContainsString('?', $hasil);
    }

    public function test_nama_huruf_han_tidak_menghapus_penanda_surel(): void
    {
        $hasil = $this->stempel()->amankan(
            'Diunduh oleh 李明 (li.ming@umy.ac.id) pada 18/08/2026'
        );

        $this->assertStringContainsString('li.ming@umy.ac.id', $hasil);
        $this->assertStringNotContainsString('?', $hasil);
        $this->assertNotSame('', trim($hasil));
    }

    public function test_spasi_berlebih_dirapikan(): void
    {
        $hasil = $this->stempel()->amankan("Diunduh  oleh\nDzar\t Zaki  (dzar@umy.ac.id)");

        $this->assertSame('Diunduh oleh Dzar Zaki (dzar@umy.ac.id)', $hasil);
    }

    public function test_hasil_selalu_dapat_dicetak_font_inti(): void
    {
        $stempel = $this->stempel();

        $contoh = [
            'Diunduh oleh Dzar Zaki (dzar@umy.ac.id) pada 18/08/2026',
            'Diunduh oleh Aímé Nüñez (aime@umy.ac.id) pada 18/08/2026',
            'Diunduh oleh Nguyễn Văn Tú (tu@umy.ac.id) pada 18/08/2026',
            'Diunduh oleh 李明 (li@umy.ac.id) pada 18/08/2026',
            'Diunduh oleh أحمد يوسف (ahmad@umy.ac.id) pada 18/08/2026',
        ];

        foreach ($contoh as $teks) {
            $hasil = $stempel->amankan($teks);

            $this->assertIsString(
                @iconv('Windows-1252', 'UTF-8', $hasil),
                "Hasil bukan Windows-1252 yang sah untuk: {$teks}"
            );

            $this->assertSame(
                0,
                preg_match('/[\x00-\x1F\x7F]/', $hasil),
                "Hasil memuat karakter kendali untuk: {$teks}"
            );
        }
    }
}