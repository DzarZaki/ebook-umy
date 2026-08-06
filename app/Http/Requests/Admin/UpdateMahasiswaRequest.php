<?php

namespace App\Http\Requests\Admin;

use App\Rules\NamaLengkap;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Validasi penyuntingan data mahasiswa oleh dosen. */
class UpdateMahasiswaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('name'))) {
            $this->merge([
                'name' => trim(preg_replace('/\s+/u', ' ', $this->input('name'))),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:5', 'max:120', new NamaLengkap],
            'email' => ['required', 'string', 'email', 'max:150', Rule::unique('users', 'email')->ignore($this->route('mahasiswa'))],
            'prodi_id' => ['required', 'integer', 'exists:prodi,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama lengkap',
            'email' => 'email',
            'prodi_id' => 'program studi',
        ];
    }
}
