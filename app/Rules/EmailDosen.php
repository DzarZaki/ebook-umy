<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

/** Akun dosen dan Super Admin wajib memakai email resmi kampus. */
class EmailDosen implements ValidationRule
{
    public const DOMAIN = '@umy.ac.id';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! Str::endsWith(Str::lower($value), self::DOMAIN)) {
            $fail('Email dosen harus memakai domain '.self::DOMAIN.'.');
        }
    }
}
