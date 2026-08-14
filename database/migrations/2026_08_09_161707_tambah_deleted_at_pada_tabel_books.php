<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menambahkan penanda penghapusan lunak pada buku.
 *
 * Menghapus buku berarti ikut membuang riwayat baca, progres halaman, dan
 * penanda milik mahasiswa — hal-hal yang tidak bisa diunggah ulang. Kolom ini
 * memberi masa tenggang sebelum penghapusan menjadi permanen.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('books', 'deleted_at')) {
            return;
        }

        Schema::table('books', function (Blueprint $tabel) {
            $tabel->softDeletes()->after('is_published');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('books', 'deleted_at')) {
            return;
        }

        Schema::table('books', function (Blueprint $tabel) {
            $tabel->dropSoftDeletes();
        });
    }
};