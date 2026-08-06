<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware yang memblokir user dengan akun nonaktif (is_active = false).
 * User yang dinonaktifkan admin akan langsung dikeluarkan dari sesi.
 */
class EnsureUserIsActive
{
    /**
     * Menangani request masuk.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Akun Anda dinonaktifkan. Silakan hubungi admin prodi Anda.',
            ]);
        }

        return $next($request);
    }
}
