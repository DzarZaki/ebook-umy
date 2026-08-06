<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel `books` — koleksi e-book/artikel berformat PDF.
     *
     * access_mode:
     *  - full     : boleh diunduh seluruhnya
     *  - partial  : hanya rentang halaman tertentu yang boleh diunduh
     *  - readonly : hanya boleh dibaca, tidak boleh diunduh
     */
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('author')->nullable();
            $table->text('description')->nullable();

            $table->foreignId('prodi_id')->nullable()->constrained('prodi')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();

            $table->string('file_path');
            $table->unsignedBigInteger('file_size')->default(0);
            $table->unsignedInteger('page_count')->nullable();
            $table->string('cover_path')->nullable();

            $table->enum('access_mode', ['full', 'partial', 'readonly'])->default('readonly');
            $table->unsignedInteger('download_page_start')->nullable();
            $table->unsignedInteger('download_page_end')->nullable();
            $table->boolean('watermark_enabled')->default(true);
            $table->boolean('is_published')->default(true);

            $table->timestamps();

            $table->index(['prodi_id', 'is_published']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
