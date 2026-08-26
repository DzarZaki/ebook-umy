<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('lecturer_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title_prefix')->nullable(); // e.g. "Dr.", "Prof."
            $table->string('title_suffix')->nullable(); // e.g. "M.Kom.", "Ph.D."
            $table->string('nidn')->nullable();
            $table->string('academic_position')->nullable(); // e.g. "Lektor Kepala / Dosen Pengampu"
            $table->string('expertise')->nullable(); // e.g. "Kecerdasan Buatan, Rekayasa Web, Sistem Basis Data"
            $table->text('bio')->nullable(); // Sambutan / Biografi Dosen
            $table->string('quote')->nullable(); // Quote / Motto Dosen
            $table->string('photo_path')->nullable(); // Foto profil dosen
            $table->string('google_scholar_url')->nullable();
            $table->string('scopus_url')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->string('website_url')->nullable();
            $table->boolean('is_displayed')->default(true); // Tampilkan di halaman muka
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lecturer_profiles');
    }
};
