<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Validasi pembaruan kode akses prodi oleh dosen. */
class UpdateKodeAksesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ($this->user()?->isAdmin() ?? false) && $this->user()->prodi_id !== null;
    }

    /** Kode akses selalu disimpan dalam huruf besar. */
    protected function prepareForValidation(): void
    {
        $this->merge([
                        // Satu sumber kebenaran dengan mutator di App\Models\Prodi, agar
            // nilai yang divalidasi Rule::unique persis sama dengan yang
            // nanti tersimpan.
            'access_code' => \App\Models\Prodi::seragamkanKode($this->input('access_code')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'access_code' => [
                'required',
                'string',
                'min:4',
                'max:30',
                'regex:/^[A-Za-z0-9\-]+$/',
                Rule::unique('prodi', 'access_code')->ignore($this->user()->prodi_id),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['access_code' => 'kode akses'];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'access_code.regex' => 'Kode akses hanya boleh berisi huruf, angka, dan tanda hubung.',
            'access_code.unique' => 'Kode akses ini sudah dipakai program studi lain.',
        ];
    }
}
