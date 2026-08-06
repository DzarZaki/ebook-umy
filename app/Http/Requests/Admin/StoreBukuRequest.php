<?php

namespace App\Http\Requests\Admin;

use App\Models\Book;
use App\Models\Category;
use App\Rules\DeskripsiAman;
use App\Rules\PolaTeks;
use App\Support\PdfHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Validator;

/**
 * Validasi unggahan buku baru oleh dosen.
 */
class StoreBukuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:3', 'max:200', PolaTeks::judul()],
            'author' => ['nullable', 'string', 'max:120', PolaTeks::namaOrang()],
            'description' => ['nullable', 'string', 'max:2000', new DeskripsiAman],
            'lingkup' => ['required', 'in:prodi,umum'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'berkas' => ['required', 'file', 'mimes:pdf', 'max:30720'],
            'sampul' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'access_mode' => ['required', 'in:full,partial,readonly'],
            'download_page_start' => ['nullable', 'integer', 'min:1'],
            'download_page_end' => ['nullable', 'integer', 'min:1'],
            'watermark_enabled' => ['nullable', 'boolean'],
            'is_published' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Pemeriksaan lanjutan: kecocokan lingkup kategori dan rentang halaman.
     */
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

    /** Membaca jumlah halaman dari berkas yang sedang diunggah. */
    protected function jumlahHalamanBuku(): ?int
    {
        $berkas = $this->file('berkas');

        if (! $berkas instanceof UploadedFile || ! $berkas->isValid()) {
            return null;
        }

        return PdfHelper::hitungHalaman($berkas->getPathname());
    }

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
