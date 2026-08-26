<?php

namespace Database\Factories;

use App\Models\Prodi;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $sandiBawaan = null;

    /**
     * Bawaan factory adalah akun mahasiswa dengan email umum.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->namaWajar(),
            'email' => fake()->unique()->userName().'@gmail.com',
            'email_verified_at' => now(),
            'password' => static::$sandiBawaan ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => User::ROLE_MAHASISWA,
            'prodi_id' => null,
            'is_active' => true,
        ];
    }

    /** Nama acak yang hanya berisi huruf, agar lolos aturan PolaTeks::namaOrang(). */
    private function namaWajar(): string
    {
        $depan = ['Ahmad', 'Siti', 'Budi', 'Dewi', 'Rizky', 'Nurul', 'Fajar', 'Intan', 'Hendra', 'Laila'];
        $belakang = ['Santoso', 'Hidayat', 'Wijaya', 'Pratama', 'Maulana', 'Kusuma', 'Nugroho', 'Safitri'];

        return fake()->randomElement($depan).' '.fake()->randomElement($belakang);
    }

    /** Akun yang belum memverifikasi email. */
    public function unverified(): static
    {
        return $this->state(fn (array $atribut) => [
            'email_verified_at' => null,
        ]);
    }

    /** Akun Super Admin, memakai alamat Gmail sesuai kebijakan klien. */
    public function superAdmin(): static
    {
        return $this->state(fn (array $atribut) => [
            'role' => User::ROLE_SUPERADMIN,
            'email' => fake()->unique()->userName().'@gmail.com',
            'prodi_id' => null,
        ]);
    }

    /** Akun dosen (admin prodi), memakai alamat Gmail sesuai kebijakan klien. */
    public function admin(?Prodi $prodi = null): static
    {
        return $this->state(fn (array $atribut) => [
            'role' => User::ROLE_ADMIN,
            'email' => fake()->unique()->userName().'@gmail.com',
            'prodi_id' => $prodi?->id ?? Prodi::factory(),
        ]);
    }

    /** Akun mahasiswa, memakai email umum seperti Gmail. */
    public function mahasiswa(?Prodi $prodi = null): static
    {
        return $this->state(fn (array $atribut) => [
            'role' => User::ROLE_MAHASISWA,
            'email' => fake()->unique()->userName().'@gmail.com',
            'prodi_id' => $prodi?->id ?? Prodi::factory(),
        ]);
    }

    /** Akun yang dinonaktifkan admin. */
    public function nonaktif(): static
    {
        return $this->state(fn (array $atribut) => [
            'is_active' => false,
        ]);
    }
}
