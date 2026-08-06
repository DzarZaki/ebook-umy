<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Membatasi karakter yang boleh dipakai pada sebuah kolom teks.
 * Dipakai lewat konstruktor statis agar mudah dibaca di Form Request.
 */
class PolaTeks implements ValidationRule
{
    private function __construct(
        private string $pola,
        private string $pesan,
    ) {}

    /** Hanya huruf dan spasi. Untuk nama program studi. */
    public static function hurufSaja(): self
    {
        return new self(
            '/^[\p{L} ]+$/u',
            'Kolom :attribute hanya boleh berisi huruf dan spasi.',
        );
    }

    /** Huruf, spasi, titik, apostrof, dan tanda hubung. Untuk nama orang. */
    public static function namaOrang(): self
    {
        return new self(
            "/^[\p{L} .'\-]+$/u",
            'Kolom :attribute hanya boleh berisi huruf, spasi, titik, apostrof, dan tanda hubung.',
        );
    }

    /** Huruf, spasi, dan tanda hubung. Untuk nama kategori. */
    public static function namaKategori(): self
    {
        return new self(
            '/^[\p{L} \-]+$/u',
            'Kolom :attribute hanya boleh berisi huruf, spasi, dan tanda hubung.',
        );
    }

    /** Huruf, angka, spasi, dan tanda baca umum. Untuk judul buku. */
    public static function judul(): self
    {
        return new self(
            '/^[\p{L}\p{N} .,:;\-–\'"()?!]+$/u',
            'Kolom :attribute hanya boleh berisi huruf, angka, spasi, dan tanda baca umum seperti titik, koma, dan tanda kurung.',
        );
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || preg_match($this->pola, $value) !== 1) {
            $fail(str_replace(':attribute', $attribute, $this->pesan));
        }
    }
}
