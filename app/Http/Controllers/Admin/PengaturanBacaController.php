<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Kebijakan bacaan milik masing-masing prodi.
 *
 * Dua sakelar yang dikelola di sini menggantikan pengaturan .env lama:
 * apakah aliran baca diberi stempel identitas, dan apakah penampil ikut
 * dibatasi pada rentang halaman unduhan buku "sebagian".
 *
 * Nilai yang dikirim selalu keadaan TUJUAN, bukan perintah balik — satu
 * permintaan berulang kali terkirim pun berakhir pada keadaan yang sama.
 */
class PengaturanBacaController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $prodi = $request->user()->prodi;
        abort_unless($prodi, 404);

        $data = $request->validate([
            'sakelar' => ['required', Rule::in(['baca_stempel', 'baca_ikuti_rentang'])],
            'nilai' => ['required', 'boolean'],
        ]);

        $prodi->update([
            $data['sakelar'] => $data['nilai'],
        ]);

        $pesan = match ($data['sakelar']) {
            'baca_stempel' => $data['nilai']
                ? "Stempel identitas bacaan untuk {$prodi->name} telah diaktifkan."
                : "Stempel identitas bacaan untuk {$prodi->name} telah dinonaktifkan.",
            default => $data['nilai']
                ? "Penampil untuk {$prodi->name} kini mengikuti rentang halaman unduhan."
                : "Penampil untuk {$prodi->name} kini bebas membaca seluruh halaman.",
        };

        return back()->with('status', $pesan);
    }
}
