<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBukuRequest;
use App\Http\Requests\Admin\UpdateBukuRequest;
use App\Models\Book;
use App\Models\Category;
use App\Services\PemberiTahuBukuBaru;
use App\Support\Pdf\PengekstrakTeks;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

/**
 * Pengelolaan koleksi buku oleh dosen (admin prodi).
 *
 * Wewenang diputuskan oleh BookPolicy, bukan oleh controller ini. Yang
 * tersisa di sini hanyalah pertanyaan — authorize() — sedangkan jawabannya
 * tinggal di satu tempat bersama seluruh aturan kepemilikan buku.
 */
class BukuController extends Controller
{
    use AuthorizesRequests;

    /** Ekstensi sampul yang diizinkan, dipetakan dari jenis gambar sesungguhnya. */
    private const EKSTENSI_GAMBAR = [
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG => 'png',
        IMAGETYPE_WEBP => 'webp',
    ];

    /** Menampilkan daftar buku milik prodi dosen ditambah buku umum miliknya. */
    public function index(Request $request): View
    {
        $dosen = $request->user();

        $daftarBuku = Book::query()
            ->with(['category', 'prodi'])
            // Cakupan disamakan dengan Tempat Sampah: buku prodi sendiri, plus
            // buku umum hanya bila diunggah dosen ini. Tanpa ini daftar memuat
            // buku yang pasti ditolak BookPolicy saat diklik.
            ->where(function ($kueri) use ($dosen) {
                $kueri->where('prodi_id', $dosen->prodi_id)
                    ->orWhere(function ($umum) use ($dosen) {
                        $umum->whereNull('prodi_id')
                            ->where('uploaded_by', $dosen->id);
                    });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        // Dua nama variabel dikirim agar cocok dengan penamaan di view.
        return view('admin.buku.index', [
            'daftarBuku' => $daftarBuku,
            'bukus' => $daftarBuku,
        ]);
    }

    /** Menampilkan formulir unggah buku baru. */
    public function create(Request $request): View
    {
        $this->authorize('create', Book::class);

        $daftarKategori = $this->kategoriTersedia($request);

        return view('admin.buku.create', [
            'daftarKategori' => $daftarKategori,
            'kategori' => $daftarKategori,
            'buku' => new Book,
        ]);
    }

    /** Menyimpan buku baru beserta berkas PDF-nya. */
    public function store(StoreBukuRequest $request): RedirectResponse
    {
        $this->authorize('create', Book::class);

        $data = $request->validated();
        $dosen = $request->user();
        $berkas = $request->file('berkas');

        // Halaman sudah dihitung saat validasi berjalan. Angkanya diambil dari
        // sana supaya qpdf tidak dijalankan dua kali atas berkas yang sama.
        $jumlahHalaman = $request->jumlahHalamanTerbaca();

        $jalurPdf = $this->simpanPdf($berkas);
        $jalurSampul = $this->simpanSampul($request->file('sampul'));
        $teksIsi = $this->indeksTeksIsi($jalurPdf);

        try {
            $buku = Book::create([
                'title' => $data['title'],
                'slug' => $this->buatSlug($data['title']),
                'author' => $data['author'] ?? null,
                'description' => $data['description'] ?? null,
                'prodi_id' => $data['lingkup'] === 'umum' ? null : $dosen->prodi_id,
                'category_id' => $data['category_id'] ?? null,
                'uploaded_by' => $dosen->id,
                'file_path' => $jalurPdf,
                'file_size' => $berkas->getSize(),
                'page_count' => $jumlahHalaman,
                'cover_path' => $jalurSampul,
                'access_mode' => $data['access_mode'],
                'download_page_start' => $data['access_mode'] === Book::AKSES_SEBAGIAN ? $data['download_page_start'] : null,
                'download_page_end' => $data['access_mode'] === Book::AKSES_SEBAGIAN ? $data['download_page_end'] : null,
                'watermark_enabled' => $request->boolean('watermark_enabled'),
                'is_published' => $request->boolean('is_published'),
                'search_text' => $teksIsi,
            ]);
        } catch (Throwable $galat) {
            // Barisnya gagal dibuat, jadi berkas yang terlanjur tersalin tidak
            // punya pemilik. Tanpa pembersihan ini ia akan menghuni disk
            // selamanya tanpa seorang pun tahu ia ada.
            $this->hapusBerkas([['local', $jalurPdf], ['public', $jalurSampul]]);

            throw $galat;
        }

        // Kabari para pelanggan pemberitahuan — hanya bila buku langsung
        // terbit; draf yang diterbitkan belakangan kabarnya lewat terbitkan().
        if ($buku->is_published) {
            app(PemberiTahuBukuBaru::class)->kirim($buku);
        }

        return redirect()
            ->route('admin.buku.index')
            ->with('status', 'Buku berhasil diunggah.');
    }

    /** Menampilkan formulir penyuntingan buku. */
    public function edit(Request $request, Book $buku): View
    {
        $this->authorize('update', $buku);

        $daftarKategori = $this->kategoriTersedia($request);

        return view('admin.buku.edit', [
            'buku' => $buku,
            'daftarKategori' => $daftarKategori,
            'kategori' => $daftarKategori,
        ]);
    }

    /**
     * Memperbarui buku; berkas lama dipertahankan bila tidak ada unggahan baru.
     *
     * Berkas baru selalu disimpan lebih dulu dan yang lama dihapus paling
     * akhir. Bila urutannya dibalik, satu kegagalan di tengah jalan akan
     * meninggalkan buku yang datanya menunjuk ke berkas yang sudah lenyap.
     *
     * Wewenang di sini dijaga UpdateBukuRequest::authorize(), yang juga
     * bertanya kepada BookPolicy::update().
     */
    public function update(UpdateBukuRequest $request, Book $buku): RedirectResponse
    {
        $data = $request->validated();
        $dosen = $request->user();
        $berkas = $request->file('berkas');
        $sampul = $request->file('sampul');

        $perubahan = [
            'title' => $data['title'],
            'slug' => $this->buatSlug($data['title'], $buku->id),
            'author' => $data['author'] ?? null,
            'description' => $data['description'] ?? null,
            'prodi_id' => $data['lingkup'] === 'umum' ? null : $dosen->prodi_id,
            'category_id' => $data['category_id'] ?? null,
            'access_mode' => $data['access_mode'],
            'download_page_start' => $data['access_mode'] === Book::AKSES_SEBAGIAN ? $data['download_page_start'] : null,
            'download_page_end' => $data['access_mode'] === Book::AKSES_SEBAGIAN ? $data['download_page_end'] : null,
            'watermark_enabled' => $request->boolean('watermark_enabled'),
            'is_published' => $request->boolean('is_published'),
        ];

        $pdfBaru = null;
        $pdfLama = null;
        $sampulBaru = null;
        $sampulLama = null;

        if ($berkas) {
            $pdfBaru = $this->simpanPdf($berkas);
            $pdfLama = $buku->file_path;

            $perubahan['page_count'] = $request->jumlahHalamanTerbaca();
            $perubahan['file_path'] = $pdfBaru;
            $perubahan['file_size'] = $berkas->getSize();

            // Berkasnya berganti, isi indeks pencariannya juga wajib ikut.
            // Bila ekstraksi gagal, indeks lama justru menyesatkan: ia
            // menggambarkan buku yang sudah tidak ada.
            $perubahan['search_text'] = $this->indeksTeksIsi($pdfBaru);
        }

        if ($sampul) {
            $sampulBaru = $this->simpanSampul($sampul);
            $sampulLama = $buku->cover_path;

            $perubahan['cover_path'] = $sampulBaru;
        }

        try {
            $buku->update($perubahan);
        } catch (Throwable $galat) {
            // Data gagal berubah, jadi berkas lama masih yang berlaku.
            // Yang baru justru harus dibuang.
            $this->hapusBerkas([['local', $pdfBaru], ['public', $sampulBaru]]);

            throw $galat;
        }

        // Baru sekarang aman: data sudah menunjuk ke berkas yang baru.
        $this->hapusBerkas([['local', $pdfLama], ['public', $sampulLama]]);

        return redirect()
            ->route('admin.buku.index')
            ->with('status', 'Buku berhasil diperbarui.');
    }

    /**
     * Menerbitkan atau menarik terbit sebuah buku.
     *
     * Nilai yang diterima adalah keadaan TUJUAN, bukan perintah balik: satu
     * permintaan selalu berakhir pada keadaan yang sama berapa kali pun
     * terkirim — penting saat jaringan lambat dan tombolnya tertekan ulang.
     */
    public function terbitkan(Request $request, Book $buku): RedirectResponse
    {
        $this->authorize('terbitkan', $buku);

        $data = $request->validate([
            'is_published' => ['required', 'boolean'],
        ]);

        // Kabar hanya untuk transisi draf → terbit. Menarik-ulang terbitan
        // atau menariknya kembali bukan peristiwa yang layak dikabarkan.
        $tadinyaTerbit = $buku->is_published;

        $buku->update(['is_published' => $data['is_published']]);

        if (! $tadinyaTerbit && $data['is_published']) {
            app(PemberiTahuBukuBaru::class)->kirim($buku);
        }

        return back()->with('status', $data['is_published']
            ? "Buku “{$buku->title}” telah diterbitkan."
            : "Buku “{$buku->title}” ditarik dari terbitan.");
    }

    /**
     * Membuang buku ke tempat sampah.
     *
     * Berkasnya sengaja tidak disentuh. Buku membawa serta riwayat baca,
     * progres, dan penanda milik mahasiswa — hal yang tidak bisa diunggah
     * ulang — jadi penghapusan diberi masa tenggang. Perintah
     * `ebook:bersihkan-buku` yang nanti melenyapkannya berikut berkasnya.
     */
    public function destroy(Request $request, Book $buku): RedirectResponse
    {
        $this->authorize('delete', $buku);

        $buku->delete();

        return redirect()
            ->route('admin.buku.index')
            ->with('status', 'Buku dipindahkan ke tempat sampah. Berkasnya masih tersimpan selama masa tenggang.');
    }

    /**
     * Menghapus sekumpulan berkas dari disk masing-masing.
     *
     * @param  array<int, array{0: string, 1: string|null}>  $daftar  pasangan [disk, jalur]
     */
    private function hapusBerkas(array $daftar): void
    {
        foreach ($daftar as [$disk, $jalur]) {
            if ($jalur) {
                Storage::disk($disk)->delete($jalur);
            }
        }
    }

    /**
     * Membuat slug dari judul buku dan menjamin keunikannya.
     * Bila slug sudah dipakai, ditambahkan angka di belakangnya.
     *
     * Buku di tempat sampah ikut diperiksa: barisnya masih ada di database dan
     * masih memegang slug-nya. Tanpa withTrashed(), mengunggah ulang buku yang
     * baru saja dihapus akan menabrak indeks unik dengan galat database mentah.
     */
    private function buatSlug(string $judul, ?int $abaikanId = null): string
    {
        $dasar = Str::slug($judul) ?: 'buku';
        $slug = $dasar;
        $urutan = 2;

        while (
            Book::withTrashed()
                ->where('slug', $slug)
                ->when($abaikanId, fn ($kueri) => $kueri->whereKeyNot($abaikanId))
                ->exists()
        ) {
            $slug = $dasar.'-'.$urutan;
            $urutan++;
        }

        return $slug;
    }

    /** Kategori yang boleh dipakai dosen: kategori prodinya sendiri dan kategori umum. */
    private function kategoriTersedia(Request $request)
    {
        $dosen = $request->user();

        return Category::query()
            ->where(function ($kueri) use ($dosen) {
                $kueri->where('prodi_id', $dosen->prodi_id)
                    ->orWhereNull('prodi_id');
            })
            ->orderBy('name')
            ->get();
    }

    /**
     * Menyalin PDF ke penyimpanan privat memakai aliran data.
     * Cara ini tidak bergantung pada realpath() yang bisa kosong di Windows.
     */
    private function simpanPdf(UploadedFile $berkas): string
    {
        return $this->salinBerkas($berkas, 'local', 'books', 'pdf');
    }

    /** Menyalin gambar sampul ke penyimpanan publik. Boleh kosong. */
    private function simpanSampul(?UploadedFile $sampul): ?string
    {
        if (! $sampul) {
            return null;
        }

        return $this->salinBerkas($sampul, 'public', 'covers', $this->ekstensiGambar($sampul));
    }

    /**
     * Mengekstrak isi teks berkas PDF untuk indeks pencarian.
     *
     * Kegagalan tidak boleh menggagalkan unggah — buku hasil pindai tanpa
     * lapisan teks tetap sah sebagai koleksi; ia hanya tak dapat dicari
     * lewat isinya. Service mencatat sebabnya di log.
     */
    private function indeksTeksIsi(string $jalurRelatif): ?string
    {
        return app(PengekstrakTeks::class)->ekstrak(
            Storage::disk('local')->path($jalurRelatif),
        );
    }

    /**
     * Menentukan ekstensi sampul dari isi berkas, bukan dari nama kiriman.
     *
     * Nama berkas sepenuhnya dikendalikan pengunggah. Sampul tersimpan di disk
     * publik, jadi ekstensi seperti .html akan membuat server menyajikannya
     * sebagai halaman — dan JPEG boleh memuat teks apa pun di metadatanya.
     * Hanya tiga jenis gambar yang boleh mendarat di sana, dengan nama yang
     * kita tentukan sendiri.
     */
    private function ekstensiGambar(UploadedFile $sampul): string
    {
        $keterangan = @getimagesize($sampul->getPathname());
        $jenis = $keterangan[2] ?? null;

        if (! isset(self::EKSTENSI_GAMBAR[$jenis])) {
            throw ValidationException::withMessages([
                'sampul' => 'Gambar sampul harus berupa JPG, PNG, atau WEBP yang sah.',
            ]);
        }

        return self::EKSTENSI_GAMBAR[$jenis];
    }

    /** Penyalin berkas serbaguna dengan penjagaan dan pesan galat yang ramah. */
    private function salinBerkas(UploadedFile $berkas, string $disk, string $folder, string $ekstensi): string
    {
        if (! $berkas->isValid()) {
            throw ValidationException::withMessages([
                'berkas' => 'Berkas gagal diunggah. Coba ulangi, atau periksa ukuran berkasnya.',
            ]);
        }

        $aliran = @fopen($berkas->getPathname(), 'rb');

        if ($aliran === false) {
            throw ValidationException::withMessages([
                'berkas' => 'Berkas sementara tidak dapat dibaca oleh server. Coba unggah ulang.',
            ]);
        }

        $tujuan = $folder.'/'.Str::uuid()->toString().'.'.Str::lower($ekstensi);

        Storage::disk($disk)->put($tujuan, $aliran);

        if (is_resource($aliran)) {
            fclose($aliran);
        }

        return $tujuan;
    }
}
