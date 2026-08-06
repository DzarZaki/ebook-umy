<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seeder utama — memanggil seluruh seeder aplikasi secara berurutan.
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Menjalankan seluruh seeder.
     */
    public function run(): void
    {
        $this->call([
            ProdiSeeder::class,
            UserSeeder::class,
        ]);
    }
}
