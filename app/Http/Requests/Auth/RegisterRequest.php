<?php

namespace App\Http\Requests\Auth;

use App\Rules\KodeAkses;
use App\Rules\NamaLengkap;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/** Validasi pendaftaran akun mahasiswa. */
class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** Merapikan spasi ganda pada nama sebelum divalidasi. */
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
            'email' => ['required', 'string', 'email', 'max:150', Rule::unique('users', 'email')],
            'kode_akses' => ['required', 'string', 'max:30', new KodeAkses],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
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
            'kode_akses' => 'kode akses',
            'password' => 'kata sandi',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'Email ini sudah terdaftar. Silakan masuk memakai email tersebut.',
        ];
    }
}
