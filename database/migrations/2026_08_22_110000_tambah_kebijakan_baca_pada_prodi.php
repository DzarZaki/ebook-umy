<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kebijakan bacaan tingkat program studi.
 *
 * Dua kolom ini memindahkan sakelar yang sebelumnya hanya bisa diubah
 * lewat .env (EBOOK_BACA_STEMPEL dan EBOOK_BACA_IKUTI_RENTANG) ke tangan
 * dosen pengelola. Nilai bawaannya disamakan dengan nilai .env lama agar
 * perilaku sistem tidak bergeser seketika setelah pemindahan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prodi', function (Blueprint $table) {
            $table->boolean('baca_stempel')->default(true)->after('download_enabled');
            $table->boolean('baca_ikuti_rentang')->default(false)->after('baca_stempel');
        });
    }

    public function down(): void
    {
        Schema::table('prodi', function (Blueprint $table) {
            $table->dropColumn(['baca_stempel', 'baca_ikuti_rentang']);
        });
    }
};
