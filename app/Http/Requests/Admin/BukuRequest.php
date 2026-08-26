<?php

namespace App\Http\Requests\Admin;

use App\Models\Book;
use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Validator;

/**
 * Base class bersama untuk StoreBukuRequest dan UpdateBukuRequest.
 * Berisi logika validasi lanjutan yang identik di keduanya.
 */
abstract class BukuRequest extends FormRequest
{
    private ?int $halamanTerbaca = null;

    private bool $halamanSudahDihitung = false;

    /** Ditandai bila berkasnya sendiri sudah ditolak, agar galat tidak berganda. */
    private bool $berkasDitolak = false;

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $this->periksaBerkasTerbaca($validator);
            $this->periksaLingkupKategori($validator);
            $this->periksaRentangHalaman($validator);
        });
    }

    /**
     * Jumlah halaman berkas terkait, dihitung paling banyak sekali.
     *
     * Menghitung halaman kini berarti menjalankan qpdf sebagai proses terpisah
     * atas berkas yang bisa mencapai 30 MB. Validator dan controller sama-sama
     * memerlukan angka ini, jadi hasilnya diingat lalu dipakai bersama —
     * termasuk hasil `null`, supaya kegagalan pun tidak diulang percuma.
     */
    public function jumlahHalamanTerbaca(): ?int
    {
        if (! $this->halamanSudahDihitung) {
            $this->halamanTerbaca = $this->jumlahHalamanBuku();
            $this->halamanSudahDihitung = true;
        }

        return $this->halamanTerbaca;
    }

    /**
     * Berkas baru wajib bisa dibaca oleh qpdf, apa pun mode aksesnya.
     *
     * PDF yang tidak terbaca di sini akan gagal di semua tempat lain: pembaca
     * tidak menampilkannya, stempel tidak menempel, pemotongan halaman ambruk.
     * Menolaknya sekarang berarti dosennya tahu selagi masih memegang berkasnya
     * — bukan mahasiswa yang menemukannya berbulan-bulan kemudian.
     */
    protected function periksaBerkasTerbaca(Validator $validator): void
    {
        $berkas = $this->file('berkas');

        // Tanpa unggahan baru tidak ada yang perlu diperiksa di sini.
        if (! $berkas instanceof UploadedFile || ! $berkas->isValid()) {
            return;
        }

        // Berkas yang sudah gugur di aturan lain tidak perlu diadili dua kali.
        if ($validator->errors()->has('berkas')) {
            $this->berkasDitolak = true;

            return;
        }

        if ($this->jumlahHalamanTerbaca() !== null) {
            return;
        }

        $this->berkasDitolak = true;

        $validator->errors()->add(
            'berkas',
            'Isi berkas PDF ini tidak dapat dibaca oleh sistem, sehingga tidak akan bisa ditampilkan kepada mahasiswa. Coba buka berkasnya di komputer Anda, simpan ulang sebagai PDF baru, lalu unggah kembali.',
        );
    }

    /** Kategori prodi tidak boleh dipasang pada buku umum, dan sebaliknya. */
    protected function periksaLingkupKategori(Validator $validator): void
    {
        $kategoriId = $this->input('category_id');

        if (! $kategoriId) {
            return;
        }

        $kategori = Category::find($kategoriId);

        if (! $kategori) {
            return;
        }

        $bukuUmum = $this->input('lingkup') === 'umum';

        if ($bukuUmum && ! $kategori->isUmum()) {
            $validator->errors()->add('category_id', 'Buku berlingkup Umum hanya boleh memakai kategori Umum.');
        }

        if (! $bukuUmum && $kategori->isUmum()) {
            $validator->errors()->add('category_id', 'Buku berlingkup program studi hanya boleh memakai kategori program studi.');
        }

        /*
         * Buku prodi wajib memakai kategori milik prodinya sendiri.
         * Aturan kompatibilitas di atas masih menerima kategori prodi
         * MANA PUN; menu pilihan memang sudah disaring, tetapi kiriman
         * yang dipoles tetap bisa menyelundupkan kategori prodi lain.
         */
        if (! $bukuUmum && ! $kategori->isUmum()
            && (int) $kategori->prodi_id !== (int) $this->user()?->prodi_id) {
            $validator->errors()->add('category_id', 'Kategori yang dipilih bukan milik program studi Anda.');
        }
    }

    /** Rentang halaman wajib masuk akal dan tidak melebihi isi PDF. */
    protected function periksaRentangHalaman(Validator $validator): void
    {
        if ($this->input('access_mode') !== Book::AKSES_SEBAGIAN) {
            return;
        }

        $awal = $this->integer('download_page_start');
        $akhir = $this->integer('download_page_end');

        if (! $awal || ! $akhir) {
            $validator->errors()->add('download_page_start', 'Rentang halaman wajib diisi untuk mode unduh sebagian.');

            return;
        }

        if ($awal > $akhir) {
            $validator->errors()->add('download_page_end', 'Halaman akhir tidak boleh lebih kecil daripada halaman awal.');

            return;
        }

        // Berkasnya sudah ditolak utuh; menambah keluhan soal rentang halaman
        // hanya akan membingungkan dosennya.
        if ($this->berkasDitolak) {
            return;
        }

        $jumlahHalaman = $this->jumlahHalamanTerbaca();

        if ($jumlahHalaman === null) {
            // Gagal-tertutup. Bila jumlah halaman tidak terbaca, pemotongan
            // halaman saat diunduh pasti gagal juga.
            $validator->errors()->add(
                'download_page_end',
                'Jumlah halaman buku ini tidak diketahui, sehingga mode unduh sebagian tidak dapat dipakai. Unggah ulang berkas PDF-nya, atau pilih mode Unduh penuh atau Baca saja.',
            );

            return;
        }

        if ($akhir > $jumlahHalaman) {
            $validator->errors()->add(
                'download_page_end',
                "Berkas PDF ini hanya memiliki {$jumlahHalaman} halaman, jadi halaman akhir tidak boleh melebihi angka itu.",
            );
        }
    }

    /** Dikembalikan oleh subclass sesuai konteks (unggah baru vs perbarui). */
    abstract protected function jumlahHalamanBuku(): ?int;

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'judul buku',
            'author' => 'penulis',
            'description' => 'deskripsi',
            'berkas' => 'berkas PDF',
            'sampul' => 'gambar sampul',
            'lingkup' => 'lingkup buku',
            'category_id' => 'kategori',
        ];
    }
}
