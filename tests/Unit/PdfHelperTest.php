<?php

namespace Tests\Unit;

use App\Support\PdfHelper;
use Tests\TestCase;

class PdfHelperTest extends TestCase
{
    /** Membangun PDF minimal yang valid dengan $n halaman. */
    private function buatPdf(int $n): string
    {
        // Bangun objek halaman satu per satu.
        $objek = [];

        // Objek 1: catalog
        $objek[1] = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";

        // Objek 2: pages (pohon halaman)
        $kids = implode(' ', array_map(fn ($i) => ($i + 2).' 0 R', range(0, $n - 1)));
        $objek[2] = "2 0 obj\n<< /Type /Pages /Kids [{$kids}] /Count {$n} >>\nendobj\n";

        // Objek 3..n+2: setiap halaman
        for ($i = 0; $i < $n; $i++) {
            $no = $i + 3;
            $objek[$no] = "{$no} 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] >>\nendobj\n";
        }

        $isi = "%PDF-1.4\n";
        $offset = [];

        foreach ($objek as $no => $blok) {
            $offset[$no] = strlen($isi);
            $isi .= $blok;
        }

        $xrefPos = strlen($isi);
        $total = count($objek) + 1;
        $isi .= "xref\n0 {$total}\n0000000000 65535 f \n";

        foreach ($offset as $off) {
            $isi .= str_pad($off, 10, '0', STR_PAD_LEFT)." 00000 n \n";
        }

        $isi .= "trailer\n<< /Size {$total} /Root 1 0 R >>\nstartxref\n{$xrefPos}\n%%EOF\n";

        return $isi;
    }

    private function tulisTemp(string $isi): string
    {
        $jalur = tempnam(sys_get_temp_dir(), 'pdf_test_');
        file_put_contents($jalur, $isi);

        return $jalur;
    }

    public function test_hitung_halaman_pdf_satu_halaman(): void
    {
        $jalur = $this->tulisTemp($this->buatPdf(1));

        try {
            $this->assertSame(1, PdfHelper::hitungHalaman($jalur));
        } finally {
            unlink($jalur);
        }
    }

    public function test_hitung_halaman_pdf_tiga_halaman(): void
    {
        $jalur = $this->tulisTemp($this->buatPdf(3));

        try {
            $this->assertSame(3, PdfHelper::hitungHalaman($jalur));
        } finally {
            unlink($jalur);
        }
    }

    public function test_jalur_tidak_ada_mengembalikan_null(): void
    {
        $this->assertNull(PdfHelper::hitungHalaman('/tmp/tidak_ada_sama_sekali.pdf'));
    }

    public function test_berkas_bukan_pdf_mengembalikan_null(): void
    {
        $jalur = $this->tulisTemp('ini bukan PDF sama sekali');

        try {
            $this->assertNull(PdfHelper::hitungHalaman($jalur));
        } finally {
            unlink($jalur);
        }
    }
}
