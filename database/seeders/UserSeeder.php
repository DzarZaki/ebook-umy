<?php

namespace Database\Seeders;

use App\Models\Prodi;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Mengisi akun awal: 1 Super Admin dan 1 Dosen (Manajemen).
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

        /*
         * Dicari lewat nama, bukan slug: slug diturunkan dari Str::slug(nama)
         * oleh ProdiSeeder, sehingga "Manajemen" menghasilkan slug
         * "manajemen". Mencari slug yang tidak pernah dibuat membuat akun
         * dosen lahir tanpa prodi.
         */
        $manajemen = Prodi::where('name', 'Manajemen')->first();

        $akun = [
            [
                'name' => 'Super Admin',
                'email' => 'dzarfaz@gmail.com',
                'role' => User::ROLE_SUPERADMIN,
                'prodi_id' => null,
            ],
            [
                'name' => 'Dosen Manajemen',
                'email' => 'dosen.manajemen@gmail.com',
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
