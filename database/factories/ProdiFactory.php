<?php

namespace Database\Factories;

use App\Models\Prodi;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Prodi>
 */
class ProdiFactory extends Factory
{
    protected $model = Prodi::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $nama = fake()->unique()->randomElement([
            'Akuntansi', 'Hukum', 'Psikologi', 'Ilmu Komunikasi', 'Teknik Sipil',
            'Farmasi', 'Agroteknologi', 'Hubungan Internasional', 'Ekonomi Syariah',
            'Teknologi Informasi', 'Pendidikan Bahasa Arab', 'Ilmu Pemerintahan',
        ]);

        return [
            'name' => $nama,
            'slug' => Str::slug($nama),
            'access_code' => strtoupper(fake()->unique()->bothify('??##-####')),
            'download_enabled' => true,
        ];
    }

    /** Prodi yang mematikan izin unduh untuk seluruh bukunya. */
    public function unduhMati(): static
    {
        return $this->state(fn (array $atribut) => [
            'download_enabled' => false,
        ]);
    }
}
