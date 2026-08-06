<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan kolom peran & prodi pada tabel `users`:
     * - role      : superadmin | admin (dosen) | mahasiswa
     * - prodi_id  : prodi yang melekat pada user (null untuk superadmin)
     * - is_active : status aktif akun
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['superadmin', 'admin', 'mahasiswa'])
                ->default('mahasiswa')
                ->after('password');

            $table->foreignId('prodi_id')
                ->nullable()
                ->after('role')
                ->constrained('prodi')
                ->nullOnDelete();

            $table->boolean('is_active')
                ->default(true)
                ->after('prodi_id');
        });
    }

    /**
     * Mengembalikan perubahan: menghapus kolom yang ditambahkan.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['prodi_id']);
            $table->dropColumn(['role', 'prodi_id', 'is_active']);
        });
    }
};
