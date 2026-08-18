<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Rules\EmailDosen;
use App\Rules\NamaLengkap;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
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
        $pengguna = $this->user();

        $namaBerubah = $this->input('name') !== $pengguna->name;

        // Dibandingkan dalam huruf kecil karena aturan `lowercase` di bawah
        // baru dijalankan setelah perbandingan ini dibuat.
        $surelBerubah = Str::lower((string) $this->input('email')) !== Str::lower($pengguna->email);

        /*
         * Akun dosen dan Super Admin wajib memakai surel kampus — aturan yang
         * sama sudah ditegakkan pada formulir Super Admin (StoreDosenRequest
         * dan UpdateDosenRequest). Tanpa baris ini, halaman profil menjadi
         * jalan pintas yang melewati aturan itu: akun berhak penuh atas satu
         * prodi bisa dipindahkan ke kotak surat pribadi, sekaligus memindahkan
         * jalur pemulihan kata sandi ke luar kendali kampus.
         *
         * Hanya berlaku bila surelnya benar-benar berubah, mengikuti pola yang
         * sama dengan aturan nama di bawah: pengguna lama yang surelnya belum
         * sesuai domain tetap dapat memperbarui kolom lain tanpa terkunci.
         */
        $wajibSurelKampus = $surelBerubah && ($pengguna->isAdmin() || $pengguna->isSuperAdmin());

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
                Rule::unique(User::class)->ignore($pengguna->id),
                Rule::when($wajibSurelKampus, [new EmailDosen]),
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