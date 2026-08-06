<?php

namespace App\Http\Requests\SuperAdmin;

use App\Rules\EmailDosen;
use App\Rules\PolaTeks;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/** Validasi penyuntingan akun dosen oleh Super Admin. */
class UpdateDosenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    /** Nilai centang diseragamkan agar selalu berupa boolean. */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:120', PolaTeks::namaOrang()],
            'email' => ['required', 'email', 'max:150', new EmailDosen, Rule::unique('users', 'email')->ignore($this->route('user'))],
            'prodi_id' => ['required', 'integer', 'exists:prodi,id'],
            'is_active' => ['required', 'boolean'],
            'password' => ['nullable', 'confirmed', Password::min(8)->letters()->numbers()],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama dosen',
            'email' => 'email dosen',
            'prodi_id' => 'program studi',
            'is_active' => 'status akun',
            'password' => 'kata sandi',
        ];
    }
}
