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
use Throwable;

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
  * Soal berkas. Bila di basis data sudah ada buku sungguhan, PDF dan
 * sampulnya DISALIN untuk tiap buku contoh, bukan dipakai bersama, supaya
 * setiap buku contoh memiliki berkasnya sendiri. Dengan begitu membuang
 * buku contoh lewat Tempat Sampah maupun `ebook:bersihkan-buku` menjadi
 * aman: yang terhapus hanya salinan.
 *
 * Ongkosnya ruang disk — 18 × ukuran PDF sumber, jadi PDF 10 MB berarti
 * sekitar 180 MB. Itu sebabnya seeder ini hanya untuk mesin pengembangan.
 *
 * Bila penyalinan gagal (disk penuh, berkas sumber hilang), buku contoh
 * dibuat dengan jalur palsu dan tidak dapat dibaca — sengaja demikian.
 * Menumpang berkas asli akan mengembalikan risiko penghapusan salah
 * sasaran, dan buku contoh yang tak terbaca jauh lebih ringan akibatnya.
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
            $this->command->info("Berkas contoh akan disalin dari: {$sumber->file_path}");
            $this->command->warn('Setiap buku contoh menyalin PDF-nya sendiri, jadi siapkan ruang disk sekitar 18 kali ukuran berkas di atas.');
        } else {
            $this->command->warn('Tidak ada buku berisi berkas. Buku contoh dibuat dengan jalur palsu dan TIDAK dapat dibaca.');
        }

        $dibuat = 0;
        $dilewati = 0;
        $gagalSalin = 0;

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

            // Berkas disalin, tidak dipakai bersama: buku contoh harus dapat
            // dihapus permanen tanpa menyentuh PDF milik buku sungguhan.
            $jalurBerkas = null;

            if ($sumber?->file_path) {
                $jalurBerkas = $this->salin(
                    'local',
                    (string) $sumber->file_path,
                    'books/'.self::AWALAN_SLUG.Str::uuid().'.pdf',
                );

                if ($jalurBerkas === null) {
                    $gagalSalin++;
                }
            }

            $jalurSampul = null;

            if ($data['bersampul'] && $sumber?->cover_path) {
                $akhiran = pathinfo((string) $sumber->cover_path, PATHINFO_EXTENSION) ?: 'jpg';

                $jalurSampul = $this->salin(
                    'public',
                    (string) $sumber->cover_path,
                    'covers/'.self::AWALAN_SLUG.Str::uuid().'.'.$akhiran,
                );
            }

            $buku = Book::create([
                'title' => $data['judul'],
                'slug' => $slug,
                'author' => $data['penulis'],
                'description' => $data['ringkasan'],
                'prodi_id' => $prodiId,
                'category_id' => $kategoriTerpilih?->id,
                'uploaded_by' => ($dosenProdi[$prodiId] ?? $pengunggahCadangan)->id,

                // Penyalinan yang gagal berujung pada jalur palsu, BUKAN pada
                // jalur berkas asli. Buku contoh yang tak terbaca jauh lebih
                // ringan akibatnya daripada penghapusan yang salah sasaran.
                'file_path' => $jalurBerkas ?? 'books/'.self::AWALAN_SLUG.Str::uuid().'.pdf',
                'file_size' => $jalurBerkas !== null
                    ? Storage::disk('local')->size($jalurBerkas)
                    : random_int(400_000, 20_000_000),
                'page_count' => $halaman,

                // Separuh buku sengaja dibiarkan tanpa sampul, supaya kotak
                // inisial ikut teruji. Rak sungguhan pun tidak pernah rapi.
                'cover_path' => $jalurSampul,

                // Separuh buku sengaja dibiarkan tanpa sampul, supaya kotak
                // inisial ikut teruji. Rak sungguhan pun tidak pernah rapi.
                'cover_path' => $jalurSampul,

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

        if ($gagalSalin > 0) {
            $this->command->warn("{$gagalSalin} berkas gagal disalin; buku contoh terkait tidak dapat dibuka di pembaca.");
        }
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
     * Menyalin satu berkas di dalam sebuah disk.
     *
     * Mengembalikan jalur tujuan bila berhasil, atau null bila gagal —
     * termasuk saat berkas sumbernya tidak ada. Pemanggil yang memutuskan
     * apa artinya kegagalan itu; di sini tidak ada yang dilempar, sebab
     * satu berkas yang gagal disalin tidak boleh menghentikan seeder.
     */
    private function salin(string $namaDisk, string $sumber, string $tujuan): ?string
    {
        try {
            $disk = Storage::disk($namaDisk);

            if (! $disk->exists($sumber)) {
                return null;
            }

            return $disk->copy($sumber, $tujuan) ? $tujuan : null;
        } catch (Throwable) {
            return null;
        }
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
            ['judul' => 'Menggambar Teknik dengan AutoCAD', 'penulis' => 'Rina Kusumawati', 'prodi' => 'Teknik', 'akses' => Book::AKSES_SEBAGIAN, 'terbit' => true, 'watermark' => true, 'bersampul' => false, 'halaman' => 168, 'umurHari' => 5, 'ringkasan' => 'Panduan praktis penyusunan gambar kerja beserta standar penulisannya.'],
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
         * Membuang buku contoh sekarang boleh lewat jalan biasa: pilih
         * semuanya di Tempat Sampah, atau tunggu `ebook:bersihkan-buku`
         * melenyapkannya setelah masa tenggang. Karena setiap buku contoh
         * memiliki salinan berkasnya sendiri, tidak ada PDF buku asli yang
         * ikut terhapus.
         *
         * Bila ingin cepat lewat tinker:
         *
         *   DB::table('books')->where('slug', 'like', 'contoh-%')->delete();
         *
         * Cara ini melewati logika penghapusan berkas, jadi salinan PDF dan
         * sampulnya tertinggal sebagai berkas yatim. Itu tidak menghapus
         * apa pun milik buku asli, dan `ebook:bersihkan-buku` akan menyapu
         * berkas yatim tersebut pada jadwal berikutnya.
         */
    }
}