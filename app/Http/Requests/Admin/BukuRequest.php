<?php

namespace App\Http\Requests\Admin;

use App\Models\Book;
use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Base class bersama untuk StoreBukuRequest dan UpdateBukuRequest.
 * Berisi logika validasi lanjutan yang identik di keduanya.
 */
abstract class BukuRequest extends FormRequest
{
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $this->periksaLingkupKategori($validator);
            $this->periksaRentangHalaman($validator);
        });
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

        $jumlahHalaman = $this->jumlahHalamanBuku();

        if ($jumlahHalaman !== null && $akhir > $jumlahHalaman) {
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
