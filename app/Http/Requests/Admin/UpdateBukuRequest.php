<?php

namespace App\Http\Requests\Admin;

use App\Rules\DeskripsiAman;
use App\Rules\PolaTeks;
use App\Support\PdfHelper;
use Illuminate\Http\UploadedFile;

/**
 * Validasi penyuntingan buku oleh dosen pemiliknya.
 */
class UpdateBukuRequest extends BukuRequest
{
    public function authorize(): bool
    {
        $buku = $this->route('buku');

        return ($this->user()?->isAdmin() ?? false) && $buku?->bolehDikelolaOleh($this->user());
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
            'berkas' => ['nullable', 'file', 'mimes:pdf', 'max:30720'],
            'sampul' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'access_mode' => ['required', 'in:full,partial,readonly'],
            'download_page_start' => ['nullable', 'integer', 'min:1'],
            'download_page_end' => ['nullable', 'integer', 'min:1'],
            'watermark_enabled' => ['nullable', 'boolean'],
            'is_published' => ['nullable', 'boolean'],
        ];
    }

    /** Memakai berkas baru bila ada, jika tidak memakai jumlah halaman tersimpan. */
    protected function jumlahHalamanBuku(): ?int
    {
        $berkas = $this->file('berkas');

        if ($berkas instanceof UploadedFile && $berkas->isValid()) {
            return PdfHelper::hitungHalaman($berkas->getPathname());
        }

        return $this->route('buku')?->page_count;
    }
}
