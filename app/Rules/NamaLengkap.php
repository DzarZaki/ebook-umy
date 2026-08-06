<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Memastikan pengguna menuliskan nama asli yang wajar,
 * yaitu minimal dua kata dan tanpa angka atau simbol.
 */
class NamaLengkap implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('Kolom nama lengkap tidak sah.');

            return;
        }

        $nama = trim(preg_replace('/\s+/u', ' ', $value));

        if (preg_match("/^[\p{L} .'\-]+$/u", $nama) !== 1) {
            $fail('Nama lengkap hanya boleh berisi huruf, spasi, titik, apostrof, dan tanda hubung.');

            return;
        }

        $kata = explode(' ', $nama);

        if (count($kata) < 2) {
            $fail('Tuliskan nama lengkap Anda, minimal dua kata.');

            return;
        }

        foreach ($kata as $sepotong) {
            if (mb_strlen(str_replace(['.', "'", '-'], '', $sepotong)) < 2) {
                $fail('Setiap bagian nama minimal terdiri dari dua huruf.');

                return;
            }
        }
    }
}
