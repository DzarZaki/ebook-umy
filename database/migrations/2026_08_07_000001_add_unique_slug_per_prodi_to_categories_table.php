<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            // MySQL tidak mengizinkan drop index yang dipakai FK.
            // Urutan: drop FK → drop index lama → buat unique → restore FK.
            $table->dropForeign(['prodi_id']);
            $table->dropIndex(['prodi_id', 'slug']);
            $table->unique(['prodi_id', 'slug'], 'categories_prodi_id_slug_unique');
            $table->foreign('prodi_id')->references('id')->on('prodi')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropForeign(['prodi_id']);
            $table->dropUnique('categories_prodi_id_slug_unique');
            $table->index(['prodi_id', 'slug']);
            $table->foreign('prodi_id')->references('id')->on('prodi')->cascadeOnDelete();
        });
    }
};
