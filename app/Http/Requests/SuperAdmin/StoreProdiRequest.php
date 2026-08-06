<?php

namespace App\Http\Requests\SuperAdmin;

use App\Rules\PolaTeks;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Validasi penambahan program studi baru oleh Super Admin. */
class StoreProdiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'min:3',
                'max:100',
                PolaTeks::hurufSaja(),
                Rule::unique('prodi', 'name'),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['name' => 'nama program studi'];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['name.unique' => 'Nama program studi ini sudah terdaftar.'];
    }
}
