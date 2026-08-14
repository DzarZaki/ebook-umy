<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Category;
use App\Models\Prodi;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Mengisi katalog dengan 18 buku contoh untuk menguji tata letak.
 *
 * Sengaja TIDAK dipanggil dari DatabaseSeeder: buku contoh tidak boleh
 * ikut masuk saat orang lain memasang proyek ini, dan tidak boleh ikut
 * masuk ke basis data pengujian. Jalankan sendiri bila diperlukan:
 *
 *     php artisan db:seed --class=BukuContohSeeder
 *
 * Aman dijalankan berulang: buku yang slug-nya sudah ada dilewati.
 *
 * PERINGATAN PENTING soal berkas. Bila di basis data sudah ada buku
 * sungguhan, seluruh buku contoh akan MENUNJUK BERKAS PDF YANG SAMA agar
 * dapat benar-benar dibuka di pembaca. Berkasnya tidak diduplikasi.
 * Akibatnya: jangan pernah menghapus permanen salah satu buku contoh
 * lewat Tempat Sampah atau `ebook:bersihkan-buku`, karena penghapus
 * berkas akan melenyapkan PDF yang masih dipakai buku lain — termasuk
 * buku asli Anda. Cara membuang buku contoh ada di komentar paling bawah.
 */
class BukuContohSeeder extends Seeder
{
    /** Semua slug buku contoh diberi awalan ini agar mudah dikenali. */
    private const AWALAN_SLUG = 'contoh-';

    public function run(): void
    {
        $sumber = Book::withTrashed()
            ->whereNotNull('file_path')
            ->orderBy('id')
            ->first();

        $pengunggahCadangan = User::whereIn('role', [User::ROLE_ADMIN, User::ROLE_SUPERADMIN])
            ->orderBy('id')
            ->first();

        if (! $pengunggahCadangan) {
            $this->command->error('Tidak ada akun dosen/superadmin. Jalankan seeder utama lebih dulu.');

            return;
        }

        $kategori = Category::all();

        if ($kategori->isEmpty()) {
            $this->command->warn('Belum ada kategori. Buku contoh dibuat tanpa kategori.');
        }

        $prodi = Prodi::all()->keyBy('name');
        $dosenProdi = User::where('role', User::ROLE_ADMIN)->get()->keyBy('prodi_id');

        if ($sumber) {
            $this->command->info("Buku contoh akan menumpang berkas: {$sumber->file_path}");
        } else {
            $this->command->warn('Tidak ada buku berisi berkas. Buku contoh dibuat dengan jalur palsu dan TIDAK dapat dibaca.');
        }

        $dibuat = 0;
        $dilewati = 0;

        foreach ($this->daftarBuku() as $data) {
            $slug = self::AWALAN_SLUG.Str::slug($data['judul']);

            // withTrashed: buku contoh yang sedang di Tempat Sampah tetap
            // dianggap ada, supaya seeder tidak membuat slug kembar yang
            // ditolak basis data.
            if (Book::withTrashed()->where('slug', $slug)->exists()) {
                $dilewati++;

                continue;
            }

            $prodiId = $data['prodi'] !== null
                ? ($prodi[$data['prodi']]->id ?? null)
                : null;

            // Kategori dipilih dari kategori milik prodi yang sama bila ada.
            // Mengakses prodi_id tetap aman meskipun tabel kategori tidak
            // memiliki kolom itu — Eloquent mengembalikan null, bukan galat.
            $kategoriCocok = $kategori->filter(fn ($k) => $k->prodi_id === $prodiId);
            $kategoriTerpilih = $kategoriCocok->isNotEmpty()
                ? $kategoriCocok->random()
                : $kategori->first();

            $halaman = $sumber?->page_count ?? $data['halaman'];

            $buku = Book::create([
                'title' => $data['judul'],
                'slug' => $slug,
                'author' => $data['penulis'],
                'description' => $data['ringkasan'],
                'prodi_id' => $prodiId,
                'category_id' => $kategoriTerpilih?->id,
                'uploaded_by' => ($dosenProdi[$prodiId] ?? $pengunggahCadangan)->id,
                'file_path' => $sumber?->file_path ?? 'books/contoh-'.Str::uuid().'.pdf',
                'file_size' => $sumber
                    ? (Storage::disk('local')->exists($sumber->file_path)
                        ? Storage::disk('local')->size($sumber->file_path)
                        : (int) $sumber->file_size)
                    : random_int(400_000, 20_000_000),
                'page_count' => $halaman,

                // Separuh buku sengaja dibiarkan tanpa sampul, supaya kotak
                // inisial ikut teruji. Rak sungguhan pun tidak pernah rapi.
                'cover_path' => $data['bersampul'] ? $sumber?->cover_path : null,

                'access_mode' => $data['akses'],
                'download_page_start' => $data['akses'] === Book::AKSES_SEBAGIAN ? 1 : null,
                'download_page_end' => $data['akses'] === Book::AKSES_SEBAGIAN
                    ? (int) max(1, min($halaman ?? 40, (int) round(($halaman ?? 40) * 0.2)))
                    : null,
                'watermark_enabled' => $data['watermark'],
                'is_published' => $data['terbit'],
            ]);

            // Umur buku disebar agar urutan "Baru ditambahkan" bermakna dan
            // lencana "Baru" hanya menempel pada yang benar-benar baru.
            // created_at tidak termasuk kolom yang boleh diisi massal.
            $waktu = now()->subDays($data['umurHari'])->subHours(random_int(0, 23));
            $buku->forceFill(['created_at' => $waktu, 'updated_at' => $waktu])->saveQuietly();

            $dibuat++;
        }

        $this->isiKoleksiContoh();

        $this->command->info("Buku contoh dibuat: {$dibuat} · dilewati (sudah ada): {$dilewati}");
    }

    /**
     * Mengisi Koleksi Saya beberapa mahasiswa, supaya bagian "Tersimpan"
     * di beranda tidak kosong saat tata letaknya diperiksa.
     */
    private function isiKoleksiContoh(): void
    {
        $mahasiswa = User::where('role', User::ROLE_MAHASISWA)->get();

        foreach ($mahasiswa as $orang) {
            $bukuTerlihat = Book::query()
                ->terbit()
                ->terlihatOleh($orang->prodi_id)
                ->inRandomOrder()
                ->take(3)
                ->pluck('id');

            if ($bukuTerlihat->isNotEmpty()) {
                $orang->bukuTersimpan()->syncWithoutDetaching($bukuTerlihat->all());
            }
        }

        $this->command->info("Koleksi contoh diisi untuk {$mahasiswa->count()} mahasiswa.");
    }

    /**
     * Delapan belas judul: tiga prodi, sebagian umum, dua draf, tiga mode
     * akses, dengan dan tanpa sampul, umur berbeda-beda.
     *
     * @return array<int, array<string, mixed>>
     */
    private function daftarBuku(): array
    {
        return [
            ['judul' => 'Dasar-Dasar Mekanika Teknik', 'penulis' => 'Ir. Bambang Sutrisno', 'prodi' => 'Teknik', 'akses' => Book::AKSES_PENUH, 'terbit' => true, 'watermark' => false, 'bersampul' => true, 'halaman' => 214, 'umurHari' => 2, 'ringkasan' => 'Pengantar statika dan dinamika untuk mahasiswa semester awal.'],
            ['judul' => 'Menggambar Teknik dengan AutoCAD', 'penulis' => 'Rina Kusumawati', 'prodi' => 'Teknik', 'akses' => Book::AKSES_SEBAGIAN, 'terbit' => true, 'watermark' => true, 'bersampul' => false, 'halaman' => 168, 'umverHari' => 5, 'umurHari' => 5, 'ringkasan' => 'Panduan praktis penyusunan gambar kerja beserta standar penulisannya.'],
            ['judul' => 'Material Konstruksi Ramah Lingkungan', 'penulis' => 'Dr. Agus Priyono', 'prodi' => 'Teknik', 'akses' => Book::AKSES_BACA_SAJA, 'terbit' => true, 'watermark' => true, 'bersampul' => true, 'halaman' => 132, 'umurHari' => 9, 'ringkasan' => 'Kajian bahan bangunan berkelanjutan dan penerapannya di Indonesia.'],
            ['judul' => 'Praktikum Fisika Dasar', 'penulis' => 'Tim Laboratorium Teknik', 'prodi' => 'Teknik', 'akses' => Book::AKSES_PENUH, 'terbit' => true, 'watermark' => false, 'bersampul' => false, 'halaman' => 96, 'umurHari' => 16, 'ringkasan' => 'Dua belas modul praktikum beserta lembar pengamatannya.'],
            ['judul' => 'Perancangan Sistem Drainase Perkotaan', 'penulis' => 'Ir. Nurul Hidayah', 'prodi' => 'Teknik', 'akses' => Book::AKSES_SEBAGIAN, 'terbit' => true, 'watermark' => false, 'bersampul' => true, 'halaman' => 240, 'umurHari' => 28, 'ringkasan' => 'Metode perhitungan debit dan tata letak saluran di kawasan padat.'],
            ['judul' => 'Catatan Kuliah Termodinamika (Draf)', 'penulis' => 'Dzar Zaki', 'prodi' => 'Teknik', 'akses' => Book::AKSES_BACA_SAJA, 'terbit' => false, 'watermark' => false, 'bersampul' => false, 'halaman' => 88, 'umurHari' => 1, 'ringkasan' => 'Berkas kerja yang belum siap dibagikan kepada mahasiswa.'],

            ['judul' => 'Manajemen Keuangan Syariah', 'penulis' => 'Dr. Siti Aminah', 'prodi' => 'Manajemen', 'akses' => Book::AKSES_PENUH, 'terbit' => true, 'watermark' => true, 'bersampul' => true, 'halaman' => 276, 'umurHari' => 4, 'ringkasan' => 'Prinsip pengelolaan dana berbasis akad dan penerapannya di UMKM.'],
            ['judul' => 'Perilaku Organisasi', 'penulis' => 'Prof. Hendra Wijaya', 'prodi' => 'Manajemen', 'akses' => Book::AKSES_SEBAGIAN, 'terbit' => true, 'watermark' => false, 'bersampul' => false, 'halaman' => 310, 'umurHari' => 12, 'ringkasan' => 'Dinamika kelompok kerja, motivasi, dan kepemimpinan.'],
            ['judul' => 'Studi Kasus Pemasaran Digital', 'penulis' => 'Lia Rahmawati', 'prodi' => 'Manajemen', 'akses' => Book::AKSES_PENUH, 'terbit' => true, 'watermark' => false, 'bersampul' => true, 'halaman' => 154, 'umurHari' => 21, 'ringkasan' => 'Dua belas kasus nyata pemasaran usaha kecil di media sosial.'],
            ['judul' => 'Statistika untuk Penelitian Bisnis', 'penulis' => 'Dr. Eko Nugroho', 'prodi' => 'Manajemen', 'akses' => Book::AKSES_BACA_SAJA, 'terbit' => true, 'watermark' => true, 'bersampul' => false, 'halaman' => 198, 'umurHari' => 34, 'ringkasan' => 'Uji hipotesis dan regresi dengan contoh data usaha.'],

            ['judul' => 'Metodologi Studi Islam', 'penulis' => 'Dr. Ahmad Fauzi', 'prodi' => 'Pendidikan Agama Islam', 'akses' => Book::AKSES_PENUH, 'terbit' => true, 'watermark' => false, 'bersampul' => true, 'halaman' => 186, 'umurHari' => 6, 'ringkasan' => 'Pendekatan keilmuan dalam kajian teks dan sejarah Islam.'],
            ['judul' => 'Pembelajaran Akidah Akhlak di Sekolah', 'penulis' => 'Nur Laila', 'prodi' => 'Pendidikan Agama Islam', 'akses' => Book::AKSES_SEBAGIAN, 'terbit' => true, 'watermark' => true, 'bersampul' => false, 'halaman' => 142, 'umurHari' => 14, 'ringkasan' => 'Rancangan pembelajaran beserta contoh penilaian sikap.'],
            ['judul' => 'Sejarah Peradaban Islam Nusantara', 'penulis' => 'Prof. Zainal Abidin', 'prodi' => 'Pendidikan Agama Islam', 'akses' => Book::AKSES_BACA_SAJA, 'terbit' => true, 'watermark' => false, 'bersampul' => true, 'halaman' => 264, 'umurHari' => 40, 'ringkasan' => 'Jejak penyebaran Islam dari pesisir sampai pedalaman.'],
            ['judul' => 'Draf Modul Fikih Ibadah', 'penulis' => 'Dosen PAI', 'prodi' => 'Pendidikan Agama Islam', 'akses' => Book::AKSES_BACA_SAJA, 'terbit' => false, 'watermark' => false, 'bersampul' => false, 'halaman' => 74, 'umurHari' => 3, 'ringkasan' => 'Modul yang masih ditinjau sebelum diterbitkan.'],

            ['judul' => 'Panduan Penulisan Skripsi', 'penulis' => 'Tim Akademik', 'prodi' => null, 'akses' => Book::AKSES_PENUH, 'terbit' => true, 'watermark' => false, 'bersampul' => true, 'halaman' => 64, 'umurHari' => 7, 'ringkasan' => 'Tata cara penulisan, sitasi, dan tenggat pengumpulan.'],
            ['judul' => 'Etika Akademik dan Anti Plagiarisme', 'penulis' => 'Lembaga Penjaminan Mutu', 'prodi' => null, 'akses' => Book::AKSES_BACA_SAJA, 'terbit' => true, 'watermark' => true, 'bersampul' => false, 'halaman' => 48, 'umurHari' => 11, 'ringkasan' => 'Batas kutipan, parafrasa, dan sanksi pelanggaran.'],
            ['judul' => 'Bahasa Inggris untuk Perguruan Tinggi', 'penulis' => 'Pusat Bahasa', 'prodi' => null, 'akses' => Book::AKSES_SEBAGIAN, 'terbit' => true, 'watermark' => false, 'bersampul' => true, 'halaman' => 220, 'umurHari' => 24, 'ringkasan' => 'Keterampilan membaca teks akademik dan menulis ringkasan.'],
            ['judul' => 'Literasi Digital dan Keamanan Data', 'penulis' => 'Pusat Teknologi Informasi', 'prodi' => null, 'akses' => Book::AKSES_PENUH, 'terbit' => true, 'watermark' => false, 'bersampul' => false, 'halaman' => 112, 'umurHari' => 52, 'ringkasan' => 'Kata sandi, pengelabuan, dan penjagaan data pribadi.'],
        ];

        /*
         * Cara membuang seluruh buku contoh dengan aman — lewat tinker:
         *
         *   DB::table('books')->where('slug', 'like', 'contoh-%')->delete();
         *
         * Sengaja memakai DB, bukan model: dengan begitu tidak ada logika
         * penghapusan berkas yang tersentuh, sehingga PDF milik buku asli
         * tetap utuh. Baris di book_saves ikut terbawa oleh cascade.
         */
    }
}