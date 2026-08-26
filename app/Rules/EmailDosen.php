<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

/**
 * Akun dosen dan Super Admin wajib memakai alamat Gmail.
 *
 * Kebijakan klien berpindah dari domain kampus (@umy.ac.id) ke Gmail,
 * sekaligus tetap menahan akun berhak penuh atas satu prodi agar tidak
 * dipindahkan ke alamat sembarangan — jalur pemulihan kata sandi ikut
 * alamat ini.
 */
class EmailDosen implements ValidationRule
{
    public const DOMAIN = '@gmail.com';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! Str::endsWith(Str::lower($value), self::DOMAIN)) {
            $fail('Email dosen harus memakai domain '.self::DOMAIN.'.');
        }
    }
}
