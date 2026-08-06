<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Menyimpan halaman-halaman yang ditandai mahasiswa pada sebuah buku.
    public function up(): void
    {
        Schema::create('bookmarks', function (Blueprint $tabel) {
            $tabel->id();
            $tabel->foreignId('user_id')->constrained()->cascadeOnDelete();
            $tabel->foreignId('book_id')->constrained()->cascadeOnDelete();
            $tabel->unsignedInteger('page');
            $tabel->string('note', 200)->nullable();
            $tabel->timestamps();

            // Satu halaman hanya boleh ditandai sekali oleh pengguna yang sama.
            $tabel->unique(['user_id', 'book_id', 'page']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookmarks');
    }
};
