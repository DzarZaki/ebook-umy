<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\ReadingProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Beranda pengguna yang sudah masuk — rak pribadi, bukan katalog.
 *
 * Bagian-bagian disusun dari yang paling mendesak bagi mahasiswa:
 *
 *  1. Lanjutkan membaca — buku yang sedang dibaca, beserta posisinya
 *  2. Sedang ramai — koleksi terhangat prodi pekan ini (bukti sosial)
 *  3. Tersimpan — buku yang sengaja disimpan untuk nanti
 *  4. Baru ditambahkan — bacaan terbaru dari dosen
 *
 * Bagian yang kosong tidak digambar sama sekali oleh tampilannya, supaya
 * beranda mahasiswa baru tidak berisi tiga kotak hampa.
 */
class BerandaController extends Controller
{
    /** Jumlah kartu per bagian. Ganjil sengaja dihindari agar barisnya rapi. */
    private const BATAS_LANJUTKAN = 4;

    private const BATAS_TERSIMPAN = 4;

    private const BATAS_TERBARU = 8;

    private const BATAS_RAMAI = 6;

    public function __invoke(Request $permintaan): View
    {
        $pengguna = $permintaan->user();

        return view('beranda.index', [
            'lanjutkan' => $this->lanjutkanMembaca($pengguna->id, $pengguna->prodi_id, $pengguna),
            'ramai' => $this->sedangRamai($pengguna),
            'tersimpan' => $pengguna->bukuTersimpan()
                ->terbit()
                ->terlihatOleh($pengguna->prodi_id)
                ->with(['category', 'prodi'])
                ->take(self::BATAS_TERSIMPAN)
                ->get(),
            'terbaru' => Book::query()
                ->terbit()
                ->terlihatOleh($pengguna->prodi_id)
                ->denganStatusSimpan($pengguna)
                ->with(['category', 'prodi'])
                ->latest()
                ->take(self::BATAS_TERBARU)
                ->get(),
        ]);
    }

    /**
     * Buku yang sedang dibaca, terbaru lebih dulu.
     *
     * Dikerjakan dua kueri, dengan pola yang sama seperti daftar penanda di
     * KoleksiController: catatan kemajuan diambil sekali, bukunya dijemput
     * sekali, penggabungannya di dalam ingatan.
     *
     * Penyaringan terbit + terlihatOleh tetap dipasang. Kemajuan membaca
     * adalah jejak masa lalu, dan masa lalu tidak boleh menjadi jalan
     * memutar untuk melihat buku yang sekarang sudah tidak layak dilihat.
     *
     * @return Collection<int, array{buku: Book, halaman: int, total: ?int, persen: ?int}>
     */
    private function lanjutkanMembaca(int $penggunaId, ?int $prodiId, $pengguna): Collection
    {
        $kemajuan = ReadingProgress::query()
            ->where('user_id', $penggunaId)
            ->orderByDesc('updated_at')
            ->take(self::BATAS_LANJUTKAN * 2) // cadangan bila sebagian tersaring
            ->get(['book_id', 'last_page', 'total_pages'])
            ->keyBy('book_id');

        if ($kemajuan->isEmpty()) {
            return collect();
        }

        $buku = Book::query()
            ->whereIn('id', $kemajuan->keys())
            ->terbit()
            ->terlihatOleh($prodiId)
            ->denganStatusSimpan($pengguna)
            ->with(['category', 'prodi'])
            ->get()
            ->keyBy('id');

        // Urutan ditentukan oleh $kemajuan (terbaru dulu), bukan oleh urutan
        // kueri buku. whereIn tidak menjaga urutan apa pun.
        return $kemajuan->keys()
            ->filter(fn ($id) => $buku->has($id))
            ->take(self::BATAS_LANJUTKAN)
            ->map(function ($id) use ($buku, $kemajuan) {
                $catatan = $kemajuan[$id];
                $halaman = (int) $catatan->last_page;

                // total_pages dicatat penampil dan bisa kosong pada catatan
                // lama; page_count milik buku lebih dapat dipercaya.
                $total = $buku[$id]->page_count ?? $catatan->total_pages;
                $total = $total !== null ? (int) $total : null;

                return [
                    'buku' => $buku[$id],
                    'halaman' => $halaman,
                    'total' => $total,
                    'persen' => $total > 0
                        ? min(100, (int) round($halaman / $total * 100))
                        : null,
                ];
            })
            ->values();
    }

    /**
     * Buku yang sedang hangat dibicarakan pekan ini.
     *
     * Skornya sederhana dan jujur: gabungan unduhan dan penyimpanan tujuh
     * hari terakhir. Dua aksi itu menyatakan dua rasa minat yang berbeda —
     * mengambil milik sendiri, menandai untuk nanti — dan keduanya sama-sama
     * layak menghangatkan sebuah judul.
     *
     * Perangkingan dikerjakan di SQL lewat ORDER BY atas alias agregat,
     * lalu hanya segelintir baris teratas yang dihidrasi — bukan seluruh
     * katalog. Penyaringan kehangatan nol tetap di ingatan, karena HAVING
     * atas alias tidak portabel antara SQLite dan MySQL.
     *
     * @return Collection<int, Book>
     */
    private function sedangRamai($pengguna): Collection
    {
        $sejak = now()->subDays(7);

        return Book::query()
            ->terbit()
            ->terlihatOleh($pengguna->prodi_id)
            ->with(['category', 'prodi'])
            ->denganStatusSimpan($pengguna)
            ->withCount([
                'downloadLogs as unduhan_pekan' => fn ($q) => $q->where('created_at', '>=', $sejak),
                'tersimpanOleh as simpan_pekan' => fn ($q) => $q->where('book_saves.created_at', '>=', $sejak),
            ])
            ->orderByRaw('(unduhan_pekan + simpan_pekan) desc')
            // Jauh lebih banyak dari yang tampil: baris berkehangatan nol
            // menyusul di belakang dan disaring begitu sampai di ingatan.
            ->take(self::BATAS_RAMAI * 5)
            ->get()
            ->each(fn (Book $buku) => $buku->kehangatan = $buku->unduhan_pekan + $buku->simpan_pekan)
            ->filter(fn (Book $buku) => $buku->kehangatan > 0)
            ->take(self::BATAS_RAMAI)
            ->values();
    }
}
