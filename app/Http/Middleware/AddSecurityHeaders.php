<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menempelkan header keamanan dasar pada setiap respons HTTP.
 *
 * - X-Content-Type-Options: melarang browser menebak tipe berkas.
 * - X-Frame-Options dan CSP frame-ancestors: halaman tidak boleh
 *   dibingkai oleh situs lain (anti clickjacking). Tidak ada satu pun
 *   halaman aplikasi ini yang sengaja dibingkai, bahkan oleh dirinya.
 * - Referrer-Policy: alamat internal tidak bocor lewat header Referer.
 * - Permissions-Policy: menutup kamera/mikrofon/lokasi yang tidak dipakai.
 *
 * HSTS hanya dikirim bila koneksi sudah HTTPS supaya pengembangan lokal
 * ber-HTTP tidak terganggu, dan tanpa includeSubDomains karena subdomain
 * lain milik kampus belum tentu siap HTTPS.
 */
class AddSecurityHeaders
{
    /**
     * Menangani request masuk.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $respons = $next($request);

        $respons->headers->set('X-Content-Type-Options', 'nosniff');
        $respons->headers->set('X-Frame-Options', 'DENY');
        $respons->headers->set('Content-Security-Policy', "frame-ancestors 'none'");
        $respons->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $respons->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        if ($request->isSecure()) {
            $respons->headers->set('Strict-Transport-Security', 'max-age=31536000');
        }

        return $respons;
    }
}
