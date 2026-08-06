<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateKodeAksesRequest;
use Illuminate\Http\RedirectResponse;

/** Pengaturan kode akses pendaftaran untuk program studi dosen. */
class KodeAksesController extends Controller
{
    /** Menyimpan kode akses baru bagi prodi dosen yang sedang masuk. */
    public function update(UpdateKodeAksesRequest $request): RedirectResponse
    {
        $prodi = $request->user()->prodi;

        $prodi->update([
            'access_code' => $request->validated()['access_code'],
        ]);

        return back()->with('status', 'Kode akses program studi berhasil diperbarui.');
    }
}
