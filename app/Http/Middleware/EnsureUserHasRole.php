<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware yang memastikan user memiliki salah satu peran yang diizinkan.
 *
 * Contoh pemakaian pada route:
 *   ->middleware('role:superadmin')
 *   ->middleware('role:admin,superadmin')
 */
class EnsureUserHasRole
{
    /**
     * Menangani request masuk.
     *
     * @param  Closure(Request): Response  $next
     * @param  string  ...$roles  Daftar peran yang diizinkan.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        // Belum login → arahkan ke halaman login.
        if (! $user) {
            return redirect()->route('login');
        }

        // Peran tidak termasuk yang diizinkan → tolak akses.
        if (! in_array($user->role, $roles, true)) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        return $next($request);
    }
}
