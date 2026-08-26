<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\BerkasBukuService;
use App\Support\Pdf\PembuatStempel;
use App\Support\Pdf\Qpdf;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Mendaftarkan layanan ke container.
     */
    public function register(): void
    {
        // Dijadikan singleton karena Qpdf menyimpan hasil pemeriksaan
        // ketersediaan program. Tanpa ini, setiap pemanggilan akan
        // menjalankan proses `qpdf --version` berulang kali dalam satu
        // permintaan HTTP.
        $this->app->singleton(Qpdf::class, static fn (): Qpdf => Qpdf::dariKonfigurasi());

        $this->app->singleton(
            PembuatStempel::class,
            static fn (): PembuatStempel => PembuatStempel::dariKonfigurasi(),
        );

        // Dengan pendaftaran ini, controller cukup menuliskan
        // BerkasBukuService pada parameter constructor; Laravel yang
        // merakit seluruh ketergantungannya.
        $this->app->singleton(BerkasBukuService::class, static fn ($app): BerkasBukuService => new BerkasBukuService(
            $app->make(Qpdf::class),
            $app->make(PembuatStempel::class),
        ));
    }

    /**
     * Menjalankan penyiapan setelah seluruh layanan terdaftar.
     */
    public function boot(): void
    {
        $this->daftarkanPembatasLaju();
        $this->daftarkanPembatasLajuTamu();
    }

    /**
     * Pembatas laju untuk penyaluran berkas buku.
     *
     * Angkanya diambil dari config/ebook.php supaya bisa disetel per
     * lingkungan tanpa menyentuh kode.
     *
     * Pembatasan memakai id akun, bukan hanya alamat IP: satu kampus
     * umumnya berbagi sedikit alamat IP keluar, sehingga pembatasan per IP
     * akan menghukum seangkatan hanya karena ulah satu orang.
     */
    private function daftarkanPembatasLaju(): void
    {
        RateLimiter::for('unduh-buku', function (Request $permintaan): Limit {
            $penanda = $permintaan->user()?->id ?? $permintaan->ip();

            return Limit::perHour((int) config('ebook.unduh.maks_per_jam', 20))
                ->by('unduh-buku:'.$penanda)
                ->response(static fn () => response(
                    'Anda sudah cukup banyak mengunduh dalam satu jam terakhir. '
                    .'Silakan coba lagi nanti.',
                    429,
                ));
        });

        RateLimiter::for('baca-buku', function (Request $permintaan): Limit {
            $penanda = $permintaan->user()?->id ?? $permintaan->ip();

            return Limit::perMinute((int) config('ebook.baca.maks_per_menit', 30))
                ->by('baca-buku:'.$penanda);
        });
    }

    /**
     * Pembatas laju untuk pintu otentikasi yang terbuka bagi tamu.
     *
     * Kode akses prodi adalah satu-satunya gerbang pendaftaran, sehingga
     * tanpa pembatas laju kode itu bisa ditebak paksa dari internet. Berbeda
     * dengan unduh/baca di atas, penandaannya wajib per alamat IP karena tamu
     * belum punya identitas akun; ambangnya dipilih cukup longgar agar
     * manusia biasa tidak pernah merasakannya — hanya mesin yang menebak
     * yang tertahan. Endpoint lupa sandi dibatasi serupa untuk mencegah
     * pembanjiran surel dan sensus massal alamat email.
     */
    private function daftarkanPembatasLajuTamu(): void
    {
        RateLimiter::for('pendaftaran', function (Request $permintaan): Limit {
            return Limit::perMinute(5)
                ->by('pendaftaran:'.$permintaan->ip())
                ->response(static fn () => response(
                    'Terlalu banyak percobaan pendaftaran dari jaringan Anda. '
                    .'Silakan coba lagi beberapa saat lagi.',
                    429,
                ));
        });

        RateLimiter::for('lupa-sandi', function (Request $permintaan): Limit {
            return Limit::perMinute(5)
                ->by('lupa-sandi:'.$permintaan->ip())
                ->response(static fn () => response(
                    'Terlalu banyak permintaan tautan reset kata sandi dari '
                    .'jaringan Anda. Silakan coba lagi beberapa saat lagi.',
                    429,
                ));
        });
    }
}
