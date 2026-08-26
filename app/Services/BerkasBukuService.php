<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Book;
use App\Models\Prodi;
use App\Models\User;
use App\Support\Pdf\PembuatStempel;
use App\Support\Pdf\Qpdf;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Pusat penyaluran berkas buku.
 *
 * Dua jalur yang dibedakan tegas:
 *
 * 1. BACA  — menyalurkan berkas sebagai aliran privat, tanpa pernah punya
 *            alamat publik. Untuk buku yang BUKAN "unduh penuh", berkasnya
 *            distempel identitas pembaca lebih dulu, dan bila diminta, ikut
 *            dipotong sesuai rentang halaman.
 * 2. UNDUH — menghasilkan berkas turunan sesuai hak pengguna: dipotong bila
 *            mode buku "sebagian", dan diberi stempel identitas bila diminta.
 *
 * Prinsip kegagalan di kedua jalur sengaja berbeda, dan perbedaannya penting:
 *
 * - Pemotongan halaman tidak dapat ditawar. Bila ia gagal ditegakkan,
 *   permintaan DITOLAK — menyalurkan berkas utuh dalam keadaan itu sama
 *   dengan membocorkan seluruh buku.
 * - Stempel pada jalur BACA bersifat gagal-terbuka. Halaman yang dibaca
 *   memang berhak dibaca, jadi kegagalan menstempel dicatat di log, bukan
 *   dijadikan alasan menolak mahasiswa membaca.
 */
class BerkasBukuService
{
    public function __construct(
        private readonly Qpdf $qpdf,
        private readonly PembuatStempel $pembuatStempel,
    ) {}

    /**
     * Menyiapkan berkas buku untuk dibaca di penampil.
     *
     * Mengembalikan nama disk beserta jalur RELATIF, bukan jalur absolut,
     * supaya pemanggil memakai Storage::response() dan berkasnya tetap
     * disalurkan sebagai aliran privat.
     *
     * @return array{disk: string, jalur: string}
     *
     * @throws AuthorizationException bila pengguna tidak berhak membaca
     * @throws RuntimeException bila rentang halaman wajib ditegakkan tetapi gagal
     */
    public function siapkanBacaan(Book $buku, User $pembaca): array
    {
        if (! $buku->bolehDilihatOleh($pembaca)) {
            throw new AuthorizationException('Anda tidak berhak membuka buku ini.');
        }

        $relatif = $this->pastikanBerkasBukuAda($buku);
        $bawaan = ['disk' => 'local', 'jalur' => $relatif];

        // Buku "unduh penuh" tidak perlu diolah untuk dibaca: pembacanya
        // toh boleh mengambil seluruh berkas lewat pintu unduh. Mengolahnya
        // hanya memboroskan waktu dan ruang tanpa menambah satu pun jaminan.
        if ($buku->access_mode === Book::AKSES_PENUH) {
            return $bawaan;
        }

        // Dihitung di luar try: bila pengaturan rentangnya sendiri yang tidak
        // sah, itu galat konfigurasi buku, bukan kegagalan pengolahan.
        $rentang = $this->rentangBacaan($buku, $pembaca);
        $stempelAktif = $this->stempelBacaanAktif($buku, $pembaca);

        if ($rentang === null && ! $stempelAktif) {
            return $bawaan;
        }

        try {
            $siap = $this->bacaanTerolah($buku, $pembaca, $relatif, $rentang, $stempelAktif);

            if ($siap !== null) {
                return ['disk' => $this->namaDiskSementara(), 'jalur' => $siap];
            }
        } catch (Throwable $galat) {
            // Gagal-tertutup: rentang halaman wajib ditegakkan.
            if ($rentang !== null) {
                throw new RuntimeException(
                    'Buku ini belum dapat dibuka karena berkasnya sedang tidak dapat diolah. '
                    .'Silakan coba beberapa saat lagi.',
                    0,
                    $galat,
                );
            }

            // Gagal-terbuka: hanya stempelnya yang hilang, bukan batas halaman.
            Log::warning('Stempel bacaan dilewati karena berkas gagal diolah.', [
                'buku_id' => $buku->id,
                'pengguna_id' => $pembaca->id,
                'pesan' => $galat->getMessage(),
            ]);
        }

        return $bawaan;
    }

    /**
     * Jalur relatif berkas buku untuk disalurkan ke penampil baca.
     *
     * @deprecated Dipertahankan hanya untuk pemanggil lama. Gunakan
     *             siapkanBacaan(), yang ikut menegakkan stempel dan rentang.
     */
    public function jalurBacaan(Book $buku, User $pembaca): string
    {
        if (! $buku->bolehDilihatOleh($pembaca)) {
            throw new AuthorizationException('Anda tidak berhak membuka buku ini.');
        }

        return $this->pastikanBerkasBukuAda($buku);
    }

    /**
     * Menyiapkan berkas unduhan sesuai hak pengguna.
     *
     * @return array{jalur: string, namaBerkas: string, sementara: bool}
     *                                                                   jalur      : jalur absolut berkas yang siap dikirim
     *                                                                   namaBerkas : nama berkas yang dilihat pengguna
     *                                                                   sementara  : true bila berkas wajib dihapus setelah terkirim
     *
     * @throws AuthorizationException bila pengguna tidak berhak mengunduh
     * @throws RuntimeException bila berkas gagal diolah
     */
    public function siapkanUnduhan(Book $buku, User $pengguna): array
    {
        $aturan = $buku->aturanUnduhUntuk($pengguna);

        if (! ($aturan['boleh'] ?? false)) {
            throw new AuthorizationException(
                (string) ($aturan['alasan'] ?? 'Buku ini tidak tersedia untuk diunduh.')
            );
        }

        $relatif = $this->pastikanBerkasBukuAda($buku);
        $asli = $this->diskBuku()->path($relatif);

        $rentang = $this->rentangHalaman($buku, $aturan, $asli);
        $perluStempel = (bool) $buku->watermark_enabled;

        // Jalur tercepat: seluruh buku, tanpa stempel. Tidak ada yang perlu
        // diolah, jadi berkas asli dikirim langsung tanpa menyalin apa pun.
        if ($rentang === null && ! $perluStempel) {
            return $this->hasil($asli, $buku, null, sementara: false);
        }

        if (! $this->qpdf->tersedia()) {
            // Pemotongan tidak bisa ditawar: tolak.
            if ($rentang !== null) {
                throw new RuntimeException(
                    'Layanan pengolahan berkas sedang tidak tersedia, sehingga unduhan '
                    .'sebagian tidak dapat disiapkan. Silakan coba beberapa saat lagi.'
                );
            }

            // Stempel hilang memang mengurangi jejak, tetapi tidak membocorkan
            // halaman yang seharusnya tertutup. Dicatat, lalu diteruskan.
            Log::warning('Stempel watermark dilewati karena qpdf tidak tersedia.', [
                'buku_id' => $buku->id,
                'pengguna_id' => $pengguna->id,
            ]);

            return $this->hasil($asli, $buku, null, sementara: false);
        }

        $this->bersihkanBerkasKedaluwarsa();

        $penanda = Str::uuid()->toString();
        $antara = [];
        $kerja = $asli;

        try {
            if ($rentang !== null) {
                [$awal, $akhir] = $rentang;

                $potong = $this->jalurSementara($penanda, 'potong');
                $this->qpdf->potongHalaman($kerja, $awal, $akhir, $potong);

                $antara[] = $potong;
                $kerja = $potong;
            }

            if ($perluStempel) {
                $stempel = $this->jalurSementara($penanda, 'stempel');
                $this->pembuatStempel->untukPengguna($pengguna, $stempel);
                $antara[] = $stempel;

                $bertanda = $this->jalurSementara($penanda, 'hasil');
                $this->qpdf->tempelStempel($kerja, $stempel, $bertanda);

                $antara[] = $bertanda;
                $kerja = $bertanda;
            }
        } catch (Throwable $galat) {
            // Jangan tinggalkan sampah setengah jadi di folder sementara.
            $this->hapus($antara);

            throw $galat;
        }

        // Sisakan hanya berkas yang akan dikirim.
        $this->hapus(array_filter($antara, static fn (string $jalur): bool => $jalur !== $kerja));

        return $this->hasil($kerja, $buku, $rentang, sementara: $kerja !== $asli);
    }

    /**
     * Menghapus berkas sementara yang sudah melewati umur pakainya.
     *
     * Dua folder disapu sekaligus: hasil olahan unduhan dan cache bacaan.
     * Umurnya berbeda karena sifatnya berbeda — berkas unduhan sekali kirim
     * lalu selesai, sedangkan cache bacaan dipakai berulang selama satu sesi.
     *
     * @return int Jumlah berkas yang terhapus.
     */
    public function bersihkanBerkasKedaluwarsa(): int
    {
        return $this->sapuFolder(
            $this->folderSementara(),
            (int) config('ebook.unduh.ttl_menit', 30),
        ) + $this->sapuFolder(
            $this->folderBacaan(),
            (int) config('ebook.baca.ttl_menit', 120),
        );
    }

    /**
     * Menyiapkan berkas bacaan yang sudah distempel (dan dipotong bila perlu).
     *
     * Hasilnya disimpan sebagai cache, bukan berkas sekali pakai. Alasannya
     * praktis: pdf.js meminta berkas yang sama setiap kali penampil dibuka,
     * dan memanggil qpdf pada setiap permintaan akan melumpuhkan server.
     *
     * @param  array{int, int}|null  $rentang
     * @param  bool  $stempelAktif  keputusan kebijakan prodi; ikut masuk kunci
     *                              cache agar pergantian sakelar tidak pernah
     *                              menyajikan berkas sisa keadaan lama
     * @return string|null Jalur relatif pada disk sementara, atau null bila
     *                     tidak ada yang bisa diolah dan berkas asli boleh dipakai.
     */
    private function bacaanTerolah(Book $buku, User $pembaca, string $relatifAsli, ?array $rentang, bool $stempelAktif): ?string
    {
        $disk = $this->diskSementara();
        $folder = $this->folderBacaan();
        $asli = $this->diskBuku()->path($relatifAsli);

        $kunci = $this->kunciBacaan($buku, $pembaca, $asli, $rentang, $stempelAktif);
        $tujuan = "{$folder}/{$kunci}.pdf";

        // Masih hangat di cache: tidak perlu memanggil qpdf sama sekali.
        if ($disk->exists($tujuan)) {
            return $tujuan;
        }

        if (! $this->qpdf->tersedia()) {
            if ($rentang !== null) {
                throw new RuntimeException(
                    'Program pengolah berkas tidak tersedia, sehingga batas halaman '
                    .'pada buku ini tidak dapat ditegakkan.'
                );
            }

            Log::warning('Stempel bacaan dilewati karena qpdf tidak tersedia.', [
                'buku_id' => $buku->id,
                'pengguna_id' => $pembaca->id,
            ]);

            return null;
        }

        $this->bersihkanBerkasKedaluwarsa();

        if (! $disk->exists($folder)) {
            $disk->makeDirectory($folder);
        }

        $penanda = Str::uuid()->toString();
        $antara = [];
        $kerja = $asli;

        try {
            if ($rentang !== null) {
                [$awal, $akhir] = $rentang;

                $potong = $disk->path("{$folder}/{$kunci}-{$penanda}-potong.pdf");
                $this->qpdf->potongHalaman($kerja, $awal, $akhir, $potong);

                $antara[] = $potong;
                $kerja = $potong;
            }

            if ($stempelAktif) {
                $stempel = $disk->path("{$folder}/{$kunci}-{$penanda}-stempel.pdf");
                $this->pembuatStempel->untukPengguna($pembaca, $stempel, null, 'Dibaca oleh');
                $antara[] = $stempel;

                $hasil = $disk->path("{$folder}/{$kunci}-{$penanda}-hasil.pdf");
                $this->qpdf->tempelStempel($kerja, $stempel, $hasil);
                $antara[] = $hasil;
                $kerja = $hasil;
            }

            // Dipindahkan lewat rename, bukan disalin: berkas cache baru
            // muncul dalam keadaan lengkap, sehingga permintaan lain yang
            // datang bersamaan tidak pernah menemukan berkas setengah jadi.
            if (! @rename($kerja, $disk->path($tujuan))) {
                throw new RuntimeException('Berkas bacaan gagal dipindahkan ke folder simpanan.');
            }

            $this->hapus(array_filter($antara, static fn (string $jalur): bool => $jalur !== $kerja));

            return $tujuan;
        } catch (Throwable $galat) {
            $this->hapus($antara);

            throw $galat;
        }
    }

    /**
     * Rentang halaman yang boleh DIBACA, atau null bila seluruh buku boleh
     * dibaca apa adanya.
     *
     * Bawaannya null walau bukunya bermode "sebagian", karena rentang pada
     * kolom download_page_* menyatakan batas UNDUH. Prodi yang memaknainya
     * sebagai batas baca dapat menyalakan sakelar "ikuti rentang" miliknya;
     * konfigurasi ebook.baca.ikuti_rentang tinggal penengah untuk pembaca
     * yang tidak terikat prodi mana pun.
     *
     * @return array{int, int}|null
     */
    private function rentangBacaan(Book $buku, User $pembaca): ?array
    {
        if ($buku->access_mode !== Book::AKSES_SEBAGIAN) {
            return null;
        }

        $prodi = $this->prodiPenentuKebijakan($buku, $pembaca);

        $ikutiRentang = $prodi !== null
            ? $prodi->baca_ikuti_rentang
            : (bool) config('ebook.baca.ikuti_rentang', false);

        if (! $ikutiRentang) {
            return null;
        }

        $total = (int) ($buku->page_count ?? 0);
        $awal = max(1, (int) ($buku->download_page_start ?? 1));
        $akhir = (int) ($buku->download_page_end ?? 0);

        if ($akhir < 1) {
            $akhir = $total > 0 ? $total : $awal;
        }

        if ($total > 0) {
            $awal = min($awal, $total);
            $akhir = min($akhir, $total);
        }

        if ($akhir < $awal) {
            throw new RuntimeException(
                'Pengaturan rentang halaman pada buku ini tidak sah. '
                .'Hubungi pengelola buku untuk memperbaikinya.'
            );
        }

        // Rentang yang mencakup seluruh buku tidak perlu dipotong.
        if ($total > 0 && $awal === 1 && $akhir === $total) {
            return null;
        }

        return [$awal, $akhir];
    }

    /**
     * Prodi yang kebijakannya berlaku atas pasangan buku–pembaca ini.
     *
     * Buku prodi mengikuti aturan prodinya sendiri; buku umum mengikuti
     * prodi pembacanya — sama persis dengan penentu aturan unduh di
     * Book::aturanUnduhUntuk(), supaya satu pertanyaan tidak punya dua
     * jawaban tergantung jalurnya lewat mana.
     */
    private function prodiPenentuKebijakan(Book $buku, User $pembaca): ?Prodi
    {
        return $buku->prodi ?? $pembaca->prodi;
    }

    private function stempelBacaanAktif(Book $buku, User $pembaca): bool
    {
        $prodi = $this->prodiPenentuKebijakan($buku, $pembaca);

        return $prodi !== null
            ? $prodi->baca_stempel
            : (bool) config('ebook.baca.stempel', true);
    }

    /**
     * Kunci cache bacaan.
     *
     * Ikut memasukkan waktu ubah berkas induk dan tanggal hari ini: yang
     * pertama supaya buku yang diganti berkasnya tidak menyajikan cache
     * lama, yang kedua supaya tanggal pada stempel tetap jujur.
     *
     * @param  array{int, int}|null  $rentang
     * @param  bool  $stempelAktif  keadaan sakelar saat berkas diolah
     */
    private function kunciBacaan(Book $buku, User $pembaca, string $jalurAsli, ?array $rentang, bool $stempelAktif): string
    {
        return sha1(implode('|', [
            (string) $buku->getKey(),
            (string) $buku->file_path,
            (string) (@filemtime($jalurAsli) ?: 0),
            (string) $pembaca->getKey(),
            $rentang === null ? 'utuh' : "{$rentang[0]}-{$rentang[1]}",
            $stempelAktif ? 'berstempel' : 'polos',
            Carbon::now()->toDateString(),
        ]));
    }

    /**
     * Menentukan rentang halaman yang boleh diunduh, atau null bila
     * seluruh buku boleh diambil apa adanya.
     *
     * @param  array<string, mixed>  $aturan
     * @return array{int, int}|null
     */
    private function rentangHalaman(Book $buku, array $aturan, string $jalurAsli): ?array
    {
        if ($buku->access_mode !== Book::AKSES_SEBAGIAN) {
            return null;
        }

        // Jumlah halaman dari database dipakai lebih dulu; bila kosong atau
        // tidak masuk akal, qpdf yang menghitung ulang.
        $total = (int) ($buku->page_count ?? 0);

        if ($total < 1) {
            $total = (int) ($this->qpdf->jumlahHalaman($jalurAsli) ?? 0);
        }

        $awal = (int) ($aturan['awal'] ?? $buku->download_page_start ?? 1);
        $akhir = (int) ($aturan['akhir'] ?? $buku->download_page_end ?? 0);

        $awal = max(1, $awal);

        if ($akhir < 1) {
            $akhir = $total > 0 ? $total : $awal;
        }

        // Jepit ke jumlah halaman sebenarnya supaya qpdf tidak diminta
        // mengambil halaman yang tidak ada.
        if ($total > 0) {
            $awal = min($awal, $total);
            $akhir = min($akhir, $total);
        }

        if ($akhir < $awal) {
            throw new RuntimeException(
                'Pengaturan rentang halaman unduhan pada buku ini tidak sah. '
                .'Hubungi pengelola buku untuk memperbaikinya.'
            );
        }

        // Bila rentangnya ternyata mencakup seluruh buku, memotong hanya
        // memboroskan waktu dan ruang.
        if ($total > 0 && $awal === 1 && $akhir === $total) {
            return null;
        }

        return [$awal, $akhir];
    }

    /**
     * @param  array{int, int}|null  $rentang
     * @return array{jalur: string, namaBerkas: string, sementara: bool}
     */
    private function hasil(string $jalur, Book $buku, ?array $rentang, bool $sementara): array
    {
        return [
            'jalur' => $jalur,
            'namaBerkas' => $this->namaBerkas($buku, $rentang),
            'sementara' => $sementara,
        ];
    }

    /** @param  array{int, int}|null  $rentang */
    private function namaBerkas(Book $buku, ?array $rentang): string
    {
        $dasar = Str::slug((string) $buku->title) ?: (string) ($buku->slug ?: 'buku');

        if ($rentang !== null) {
            $dasar .= "-hal-{$rentang[0]}-{$rentang[1]}";
        }

        return Str::limit($dasar, 100, '').'.pdf';
    }

    private function pastikanBerkasBukuAda(Book $buku): string
    {
        $relatif = trim((string) $buku->file_path);

        if ($relatif === '' || ! $this->diskBuku()->exists($relatif)) {
            throw new RuntimeException(
                "Berkas buku tidak ditemukan pada penyimpanan: {$relatif}"
            );
        }

        return $relatif;
    }

    /**
     * Menyapu satu folder sementara berdasarkan umur berkasnya.
     *
     * @return int Jumlah berkas yang terhapus.
     */
    private function sapuFolder(string $folder, int $ttlMenit): int
    {
        $disk = $this->diskSementara();
        $batas = Carbon::now()->subMinutes(max(1, $ttlMenit));
        $jumlah = 0;

        try {
            $daftar = $disk->files($folder);
        } catch (Throwable) {
            return 0;
        }

        foreach ($daftar as $berkas) {
            try {
                $diubah = Carbon::createFromTimestamp($disk->lastModified($berkas));

                if ($diubah->lt($batas)) {
                    $disk->delete($berkas);
                    $jumlah++;
                }
            } catch (Throwable) {
                // Berkas mungkin sedang dikirim ke pengguna lain. Lewati saja.
                continue;
            }
        }

        return $jumlah;
    }

    private function jalurSementara(string $penanda, string $tahap): string
    {
        $disk = $this->diskSementara();
        $folder = $this->folderSementara();

        if (! $disk->exists($folder)) {
            $disk->makeDirectory($folder);
        }

        return $disk->path("{$folder}/{$penanda}-{$tahap}.pdf");
    }

    /** @param  array<int, string>  $jalur */
    private function hapus(array $jalur): void
    {
        foreach ($jalur as $satu) {
            if (is_file($satu)) {
                @unlink($satu);
            }
        }
    }

    private function diskBuku(): FilesystemAdapter
    {
        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('local');

        return $disk;
    }

    private function diskSementara(): FilesystemAdapter
    {
        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk($this->namaDiskSementara());

        return $disk;
    }

    private function namaDiskSementara(): string
    {
        return (string) config('ebook.unduh.disk', 'local');
    }

    private function folderSementara(): string
    {
        return trim((string) config('ebook.unduh.folder', 'unduhan-sementara'), '/');
    }

    private function folderBacaan(): string
    {
        return trim((string) config('ebook.baca.folder', 'bacaan-sementara'), '/');
    }
}
