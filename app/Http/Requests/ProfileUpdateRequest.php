<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Rules\NamaLengkap;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('name'))) {
            $this->merge([
                'name' => trim(preg_replace('/\s+/u', ' ', $this->input('name'))),
            ]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $namaBerubah = $this->input('name') !== $this->user()->name;

        return [
            'name' => [
                'required',
                'string',
                'max:120',
                // min:5 dan NamaLengkap hanya berlaku bila nama benar-benar diubah,
                // agar user dengan nama lama yang tidak sesuai format tetap bisa
                // memperbarui field lain (mis. email) tanpa diblokir.
                Rule::when($namaBerubah, ['min:5', new NamaLengkap]),
            ],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:150',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'name' => 'nama lengkap',
            'email' => 'email',
        ];
    }
}
