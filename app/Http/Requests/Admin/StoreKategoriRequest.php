<?php

namespace App\Http\Requests\Admin;

use App\Rules\PolaTeks;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi penambahan kategori oleh dosen.
 */
class StoreKategoriRequest extends FormRequest
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
            'name' => ['required', 'string', 'min:3', 'max:100', PolaTeks::namaKategori()],
            'lingkup' => ['required', 'in:prodi,umum'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['name' => 'nama kategori', 'lingkup' => 'lingkup kategori'];
    }
}
