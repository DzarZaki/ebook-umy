<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indeks teks isi buku untuk pencarian full-text.
 *
 * Teksnya diekstrak dari berkas PDF saat unggah (dan saat berkas diganti),
 * lalu disimpan sebagai satu kolom polos. Pencariannya memakai LIKE —
 * untuk skala koleksi per program studi itu cukup cepat, dan ia bekerja
 * sama baik di SQLite maupun MySQL tanpa dua set sintaks FULLTEXT.
 *
 * longText, bukan text: TEXT MySQL hanya muat 64 KB, sementara isi buku
 * pelajaran bisa jauh melewatinya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->longText('search_text')->nullable()->after('page_count');
        });
    }

    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->dropColumn('search_text');
        });
    }
};
