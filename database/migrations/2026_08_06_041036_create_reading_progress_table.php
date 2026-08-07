<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Menyimpan halaman terakhir yang dibaca tiap mahasiswa pada tiap buku,
    // sehingga bacaan bisa dilanjutkan dari perangkat mana saja.
    public function up(): void
    {
        Schema::create('reading_progress', function (Blueprint $tabel) {
            $tabel->id();
            $tabel->foreignId('user_id')->constrained()->cascadeOnDelete();
            $tabel->foreignId('book_id')->constrained()->cascadeOnDelete();
            $tabel->unsignedInteger('last_page')->default(1);
            $tabel->unsignedInteger('total_pages')->nullable();
            $tabel->timestamps();

            // Satu baris saja untuk setiap pasangan pengguna dan buku.
            $tabel->unique(['user_id', 'book_id']);

            // Mempercepat penyusunan rak "Lanjutkan Membaca".
            $tabel->index(['user_id', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reading_progress');
    }
};
