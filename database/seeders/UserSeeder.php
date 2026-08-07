<?php

namespace Database\Seeders;

use App\Models\Prodi;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Mengisi akun awal: 1 Super Admin dan 2 Dosen (PAI & Manajemen).
 */
class UserSeeder extends Seeder
{
    /**
     * Menjalankan seeder.
     *
     * // HANYA UNTUK DEVELOPMENT — jangan dipakai di production.
     */
    public function run(): void
    {
        // Guard: tolak eksekusi di production kecuali ada flag eksplisit.
        if (app()->environment('production') && ! app()->runningUnitTests()) {
            $this->command?->error('UserSeeder ditolak di environment production. Gunakan --force bila benar-benar disengaja.');

            return;
        }

        // HANYA UNTUK DEVELOPMENT — jangan dipakai di production.
        $password = env('SEED_PASSWORD', 'password');

        $pai = Prodi::where('slug', 'pai')->first();
        $manajemen = Prodi::where('slug', 'manajemen')->first();

        $akun = [
            [
                'name' => 'Super Admin',
                'email' => 'superadmin@umy.ac.id',
                'role' => User::ROLE_SUPERADMIN,
                'prodi_id' => null,
            ],
            [
                'name' => 'Dosen PAI',
                'email' => 'dosen.pai@umy.ac.id',
                'role' => User::ROLE_ADMIN,
                'prodi_id' => $pai?->id,
            ],
            [
                'name' => 'Dosen Manajemen',
                'email' => 'dosen.manajemen@umy.ac.id',
                'role' => User::ROLE_ADMIN,
                'prodi_id' => $manajemen?->id,
            ],
        ];

        foreach ($akun as $data) {
            User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => $password, // otomatis di-hash oleh cast 'hashed'
                    'role' => $data['role'],
                    'prodi_id' => $data['prodi_id'],
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );
        }
    }
}
