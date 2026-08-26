<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LecturerProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProfilDosenController extends Controller
{
    /** Ekstensi foto yang diizinkan, dipetakan dari jenis gambar sesungguhnya. */
    private const EKSTENSI_GAMBAR = [
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG => 'png',
        IMAGETYPE_WEBP => 'webp',
    ];

    /**
     * Menampilkan form penyuntingan profil dan branding dosen.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();
        $profil = $user->lecturerProfile ?? new LecturerProfile(['user_id' => $user->id]);

        return view('admin.profil.edit', [
            'user' => $user,
            'profil' => $profil,
        ]);
    }

    /**
     * Memperbarui profil dan branding dosen.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'title_prefix' => ['nullable', 'string', 'max:50'],
            'title_suffix' => ['nullable', 'string', 'max:50'],
            'nidn' => ['nullable', 'string', 'max:50'],
            'academic_position' => ['nullable', 'string', 'max:150'],
            'expertise' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:3000'],
            'quote' => ['nullable', 'string', 'max:500'],
            'google_scholar_url' => ['nullable', 'url', 'max:255'],
            'scopus_url' => ['nullable', 'url', 'max:255'],
            'linkedin_url' => ['nullable', 'url', 'max:255'],
            'website_url' => ['nullable', 'url', 'max:255'],
            'is_displayed' => ['nullable', 'boolean'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ]);

        // Perbarui nama di tabel users
        $user->update(['name' => $data['name']]);

        $profil = $user->lecturerProfile ?? new LecturerProfile(['user_id' => $user->id]);

        // Penanganan upload foto profil baru
        if ($request->hasFile('photo')) {
            // Hapus foto lama jika ada
            if ($profil->photo_path && Storage::disk('public')->exists($profil->photo_path)) {
                Storage::disk('public')->delete($profil->photo_path);
            }

            $profil->photo_path = $this->simpanFoto($request->file('photo'));
        }

        $profil->fill([
            'title_prefix' => $data['title_prefix'] ?? null,
            'title_suffix' => $data['title_suffix'] ?? null,
            'nidn' => $data['nidn'] ?? null,
            'academic_position' => $data['academic_position'] ?? null,
            'expertise' => $data['expertise'] ?? null,
            'bio' => $data['bio'] ?? null,
            'quote' => $data['quote'] ?? null,
            'google_scholar_url' => $data['google_scholar_url'] ?? null,
            'scopus_url' => $data['scopus_url'] ?? null,
            'linkedin_url' => $data['linkedin_url'] ?? null,
            'website_url' => $data['website_url'] ?? null,
            // Tanpa nilai bawaan: checkbox yang tidak dicentang TIDAK ikut
            // terkirim, sehingga key-nya absen dari request. Bawaan `true`
            // di sini membuat profil mustahil disembunyikan — kotak kosong
            // pun tetap terbaca "tampilkan".
            'is_displayed' => $request->boolean('is_displayed'),
        ]);

        $profil->save();

        // Bersihkan cache beranda agar perubahan profil dosen langsung tampil di landing page.
        // Kunci lamanya ("beranda.profil-dosen") sisa daftar tunggal; yang benar kini jamak.
        cache()->forget('beranda.daftar-profil-dosen');
        cache()->forget('beranda.profil-dosen');

        return redirect()->route('admin.profil-dosen.edit')
            ->with('status', 'Profil dan branding dosen berhasil diperbarui.');
    }

    /**
     * Menyimpan foto profil dengan nama dan ekstensi yang kita tentukan.
     *
     * Sama seperti sampul buku: ekstensi diturunkan dari ISI berkas lewat
     * getimagesize(), bukan dari nama kiriman pengguna. Foto mendarat di
     * disk publik, dan JPEG mana pun boleh memuat teks apa pun di dalam
     * metadatanya — sehingga "x.html" yang menyamar tidak boleh ikut
     * menentukan identitas berkasnya.
     */
    private function simpanFoto(UploadedFile $foto): string
    {
        $keterangan = @getimagesize($foto->getPathname());
        $ekstensi = self::EKSTENSI_GAMBAR[$keterangan[2] ?? null] ?? null;

        if ($ekstensi === null) {
            throw ValidationException::withMessages([
                'photo' => 'Foto harus berupa JPG, PNG, atau WEBP yang sah.',
            ]);
        }

        $aliran = @fopen($foto->getPathname(), 'rb');

        if ($aliran === false) {
            throw ValidationException::withMessages([
                'photo' => 'Berkas foto tidak dapat dibaca oleh server. Coba unggah ulang.',
            ]);
        }

        $tujuan = 'lecturers/'.Str::uuid()->toString().'.'.$ekstensi;

        Storage::disk('public')->put($tujuan, $aliran);

        if (is_resource($aliran)) {
            fclose($aliran);
        }

        return $tujuan;
    }

    /**
     * Menghapus foto profil dosen.
     */
    public function hapusFoto(Request $request): RedirectResponse
    {
        $user = $request->user();
        $profil = $user->lecturerProfile;

        if ($profil && $profil->photo_path) {
            if (Storage::disk('public')->exists($profil->photo_path)) {
                Storage::disk('public')->delete($profil->photo_path);
            }
            $profil->update(['photo_path' => null]);
            // Kunci yang sama dengan update(): foto tampil di beranda publik.
            cache()->forget('beranda.daftar-profil-dosen');
            cache()->forget('beranda.profil-dosen');
        }

        return redirect()->route('admin.profil-dosen.edit')
            ->with('status', 'Foto profil berhasil dihapus.');
    }
}
