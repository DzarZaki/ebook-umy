<?php

namespace App\Http\Requests\Admin;

use App\Models\Book;
use App\Rules\BerkasPdfAman;
use App\Rules\DeskripsiAman;
use App\Rules\PolaTeks;
use App\Support\PdfHelper;
use Illuminate\Http\UploadedFile;

/**
 * Validasi unggahan buku baru oleh dosen.
 */
class StoreBukuRequest extends BukuRequest
{
    /**
     * Wewenang diserahkan kepada BookPolicy::create(), sama seperti
     * pemeriksaan di BukuController::store(). Keduanya bertanya, satu
     * tempat yang menjawab.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Book::class) ?? false;
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
            'berkas' => ['required', 'file', 'mimes:pdf', 'max:30720', new BerkasPdfAman],
            'sampul' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'access_mode' => ['required', 'in:full,partial,readonly'],
            'download_page_start' => ['nullable', 'integer', 'min:1'],
            'download_page_end' => ['nullable', 'integer', 'min:1'],
            'watermark_enabled' => ['nullable', 'boolean'],
            'is_published' => ['nullable', 'boolean'],
        ];
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
}