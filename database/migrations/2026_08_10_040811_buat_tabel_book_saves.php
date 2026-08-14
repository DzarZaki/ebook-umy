<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Buku yang sengaja disimpan mahasiswa untuk dibaca nanti.
 *
 * Tabel ini TIDAK sama dengan `bookmarks`. `bookmarks` menyimpan penanda
 * halaman di dalam sebuah buku; tabel ini menyimpan bukunya secara utuh.
 * Dua niat yang berbeda, sehingga sengaja tidak digabungkan: mencabut
 * penanda halaman tidak boleh diam-diam mengeluarkan buku dari koleksi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_saves', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();

            $table->timestamps();

            // Satu buku hanya boleh tersimpan sekali oleh orang yang sama.
            // Aturan ini ditegakkan basis data, bukan sekadar dijaga kode —
            // dua klik cepat pada tombol simpan tidak akan pernah membuat
            // baris ganda.
            $table->unique(['user_id', 'book_id']);

            // Halaman Koleksi Saya selalu membaca dengan pola yang sama:
            // milik satu pengguna, diurutkan dari yang terbaru.
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_saves');
    }
};