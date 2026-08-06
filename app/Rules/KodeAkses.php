<?php

namespace App\Rules;

use App\Models\Prodi;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** Memastikan kode akses yang dimasukkan benar-benar milik sebuah prodi. */
class KodeAkses implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! Prodi::cariDenganKode($value)) {
            $fail('Kode akses tidak dikenali. Mintalah kode yang benar kepada dosen program studi Anda.');
        }
    }
}
