<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Menambahkan kode akses pendaftaran pada tiap program studi. */
    public function up(): void
    {
        Schema::table('prodi', function (Blueprint $table) {
            $table->string('access_code', 30)->nullable()->unique()->after('slug');
        });
    }

    public function down(): void
    {
        Schema::table('prodi', function (Blueprint $table) {
            $table->dropUnique(['access_code']);
            $table->dropColumn('access_code');
        });
    }
};
