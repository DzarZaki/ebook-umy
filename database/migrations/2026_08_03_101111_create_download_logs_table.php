<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel `download_logs` — mencatat setiap unduhan untuk keperluan
     * statistik dan penelusuran bila berkas tersebar tanpa izin.
     */
    public function up(): void
    {
        Schema::create('download_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('prodi_id')->nullable()->constrained('prodi')->nullOnDelete();
            $table->string('mode', 20);
            $table->timestamps();

            $table->index(['book_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('download_logs');
    }
};
