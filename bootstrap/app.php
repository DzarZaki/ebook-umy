<?php

use App\Http\Middleware\AddSecurityHeaders;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        /*
         * Proksi tepercaya dibaca dari TRUSTED_PROXIES supaya bisa
         * diketatkan per penempatan server.
         *
         * Bawaan '*' (mis. di belakang Cloudflare Tunnel) membuat alamat
         * klien mengikuti header X-Forwarded-For kiriman siapa pun —
         * pembatas laju berbasis IP (pendaftaran, lupa sandi, login)
         * jadi bisa dihindari cukup dengan memutar-putar header itu.
         * Bila topologi jaringan sudah pasti, isi TRUSTED_PROXIES dengan
         * CIDR prodi/proksi yang sebenarnya (mis. "173.245.48.0/20").
         */
        $middleware->trustProxies(
            at: array_filter(array_map('trim', explode(',', (string) env('TRUSTED_PROXIES', '*')))),
        );

        // Alias middleware kustom agar mudah dipakai di route.
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
            'active' => EnsureUserIsActive::class,
        ]);

        // Header keamanan dasar untuk seluruh respons, termasuk halaman galat.
        $middleware->append(AddSecurityHeaders::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
