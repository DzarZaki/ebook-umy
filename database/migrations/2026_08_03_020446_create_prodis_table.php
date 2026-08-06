<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Membuat tabel `prodi` — daftar program studi (mis. PAI, Manajemen).
     * Dikelola oleh Super Admin. Kolom `download_enabled` adalah
     * saklar utama unduh untuk seluruh buku milik prodi tersebut.
     */
    public function up(): void
    {
        Schema::create('prodi', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();                    // Nama prodi
            $table->string('slug')->unique();                    // Slug untuk URL
            $table->boolean('download_enabled')->default(true);  // Saklar utama unduh
            $table->timestamps();
        });
    }

    /**
     * Mengembalikan perubahan: menghapus tabel `prodi`.
     */
    public function down(): void
    {
        Schema::dropIfExists('prodi');
    }
};
