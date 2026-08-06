<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBukuRequest;
use App\Http\Requests\Admin\UpdateBukuRequest;
use App\Models\Book;
use App\Models\Category;
use App\Support\PdfHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Pengelolaan koleksi buku oleh dosen (admin prodi).
 */
class BukuController extends Controller
{
    /** Menampilkan daftar buku milik prodi dosen ditambah buku umum miliknya. */
    public function index(Request $request): View
    {
        $dosen = $request->user();

        $daftarBuku = Book::query()
            ->with(['category', 'prodi'])
            ->where(function ($kueri) use ($dosen) {
                $kueri->where('prodi_id', $dosen->prodi_id)
                    ->orWhereNull('prodi_id');
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
        $data = $request->validated();
        $dosen = $request->user();
        $berkas = $request->file('berkas');

        // Jumlah halaman dibaca lebih dulu, selagi berkas sementara masih utuh.
        $jumlahHalaman = PdfHelper::hitungHalaman($berkas->getPathname());

        Book::create([
            'title' => $data['title'],
            'slug' => $this->buatSlug($data['title']),
            'author' => $data['author'] ?? null,
            'description' => $data['description'] ?? null,
            'prodi_id' => $data['lingkup'] === 'umum' ? null : $dosen->prodi_id,
            'category_id' => $data['category_id'] ?? null,
            'uploaded_by' => $dosen->id,
            'file_path' => $this->simpanPdf($berkas),
            'file_size' => $berkas->getSize(),
            'page_count' => $jumlahHalaman,
            'cover_path' => $this->simpanSampul($request->file('sampul')),
            'access_mode' => $data['access_mode'],
            'download_page_start' => $data['access_mode'] === Book::AKSES_SEBAGIAN ? $data['download_page_start'] : null,
            'download_page_end' => $data['access_mode'] === Book::AKSES_SEBAGIAN ? $data['download_page_end'] : null,
            'watermark_enabled' => $request->boolean('watermark_enabled'),
            'is_published' => $request->boolean('is_published'),
        ]);

        return redirect()
            ->route('admin.buku.index')
            ->with('status', 'Buku berhasil diunggah.');
    }

    /** Menampilkan formulir penyuntingan buku. */
    public function edit(Request $request, Book $buku): View
    {
        abort_unless($buku->bolehDikelolaOleh($request->user()), 403);

        $daftarKategori = $this->kategoriTersedia($request);

        return view('admin.buku.edit', [
            'buku' => $buku,
            'daftarKategori' => $daftarKategori,
            'kategori' => $daftarKategori,
        ]);
    }

    /** Memperbarui buku; berkas lama dipertahankan bila tidak ada unggahan baru. */
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

        if ($berkas) {
            Storage::disk('local')->delete($buku->file_path);

            $perubahan['page_count'] = PdfHelper::hitungHalaman($berkas->getPathname());
            $perubahan['file_path'] = $this->simpanPdf($berkas);
            $perubahan['file_size'] = $berkas->getSize();
        }

        if ($sampul) {
            if ($buku->cover_path) {
                Storage::disk('public')->delete($buku->cover_path);
            }

            $perubahan['cover_path'] = $this->simpanSampul($sampul);
        }

        $buku->update($perubahan);

        return redirect()
            ->route('admin.buku.index')
            ->with('status', 'Buku berhasil diperbarui.');
    }

    /** Menghapus buku beserta seluruh berkas yang menyertainya. */
    public function destroy(Request $request, Book $buku): RedirectResponse
    {
        abort_unless($buku->bolehDikelolaOleh($request->user()), 403);

        Storage::disk('local')->delete($buku->file_path);

        if ($buku->cover_path) {
            Storage::disk('public')->delete($buku->cover_path);
        }

        $buku->delete();

        return redirect()
            ->route('admin.buku.index')
            ->with('status', 'Buku berhasil dihapus.');
    }

    /**
     * Membuat slug dari judul buku dan menjamin keunikannya.
     * Bila slug sudah dipakai, ditambahkan angka di belakangnya.
     */
    private function buatSlug(string $judul, ?int $abaikanId = null): string
    {
        $dasar = Str::slug($judul) ?: 'buku';
        $slug = $dasar;
        $urutan = 2;

        while (
            Book::query()
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

        return $this->salinBerkas($sampul, 'public', 'covers', $sampul->getClientOriginalExtension() ?: 'jpg');
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
