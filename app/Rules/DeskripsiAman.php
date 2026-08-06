<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** Menolak tag HTML dan tautan pada kolom deskripsi bebas. */
class DeskripsiAman implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        if (preg_match('/<[^>]*>/', $value) === 1) {
            $fail('Kolom :attribute tidak boleh mengandung tag HTML.');

            return;
        }

        if (preg_match('/(https?:\/\/|www\.)/i', $value) === 1) {
            $fail('Kolom :attribute tidak boleh mengandung tautan.');
        }
    }
}
