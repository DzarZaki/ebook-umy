<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Langganan pemberitahuan buku baru.
 *
 * Opt-in, bukan bawaan nyala: surel yang tidak diminta adalah sampah,
 * sekecil apa pun niat baiknya. Penerimaannya juga menuntut akun aktif
 * dan surel terverifikasi — penjagaan itu ditegakkan di layanan
 * pengirimnya, bukan di kolom ini.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('notifikasi_buku_baru')
                ->default(false)
                ->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('notifikasi_buku_baru');
        });
    }
};
