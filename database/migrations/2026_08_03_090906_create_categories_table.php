<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel `categories` — pengelompokan konten (mis. Pelajaran, Artikel).
     * `prodi_id` bernilai NULL berarti kategori Umum yang berlaku lintas prodi.
     */
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prodi_id')->nullable()->constrained('prodi')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['prodi_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
