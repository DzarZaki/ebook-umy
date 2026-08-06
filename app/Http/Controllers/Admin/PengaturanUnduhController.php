<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Sakelar utama unduhan milik masing-masing prodi.
 */
class PengaturanUnduhController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $prodi = $request->user()->prodi;
        abort_unless($prodi, 404);

        $aktif = $request->boolean('download_enabled');
        $prodi->update(['download_enabled' => $aktif]);

        return back()->with('status', $aktif
            ? "Unduhan untuk {$prodi->name} telah diaktifkan."
            : "Unduhan untuk {$prodi->name} telah dinonaktifkan.");
    }
}
