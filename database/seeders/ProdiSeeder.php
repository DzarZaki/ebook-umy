<?php

namespace Database\Seeders;

use App\Models\Prodi;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProdiSeeder extends Seeder
{
    /** Menyiapkan dua program studi beserta kode akses pendaftarannya. */
    public function run(): void
    {
        $daftar = [
            ['name' => 'Pendidikan Agama Islam', 'access_code' => 'PAI-2026'],
            ['name' => 'Manajemen', 'access_code' => 'MNJ-2026'],
        ];

        foreach ($daftar as $data) {
            Prodi::updateOrCreate(
                ['slug' => Str::slug($data['name'])],
                [
                    'name' => $data['name'],
                    'access_code' => $data['access_code'],
                    'download_enabled' => true,
                ],
            );
        }
    }
}
