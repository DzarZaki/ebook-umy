<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Book;
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
 * 1. BACA  — selalu menyalurkan dokumen asli sebagai aliran, tanpa pernah
 *            menaruhnya di lokasi yang bisa dialamati publik.
 * 2. UNDUH — menghasilkan berkas turunan sesuai hak pengguna: dipotong bila
 *            mode buku "sebagian", dan diberi stempel identitas bila diminta.
 *
 * Prinsip gagal-tertutup: bila sebuah aturan tidak dapat ditegakkan
 * (misalnya qpdf tidak tersedia padahal buku harus dipotong), permintaan
 * DITOLAK. Menyalurkan berkas utuh dalam keadaan itu sama dengan
 * membocorkan seluruh buku.
 */
class BerkasBukuService
{
    public function __construct(
        private readonly Qpdf $qpdf,
        private readonly PembuatStempel $pembuatStempel,
    ) {
    }

    /**
     * Jalur relatif berkas buku untuk disalurkan ke penampil baca.
     *
     * Sengaja mengembalikan jalur relatif, bukan absolut, supaya pemanggil
     * memakai Storage::response() dan berkas tetap disalurkan sebagai
     * aliran privat.
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
     *         jalur      : jalur absolut berkas yang siap dikirim
     *         namaBerkas : nama berkas yang dilihat pengguna
     *         sementara  : true bila berkas wajib dihapus setelah terkirim
     *
     * @throws AuthorizationException bila pengguna tidak berhak mengunduh
     * @throws RuntimeException       bila berkas gagal diolah
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
     * @return int Jumlah berkas yang terhapus.
     */
    public function bersihkanBerkasKedaluwarsa(): int
    {
        $disk = $this->diskSementara();
        $folder = $this->folderSementara();

        $batas = Carbon::now()->subMinutes(
            max(1, (int) config('ebook.unduh.ttl_menit', 30))
        );

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
        $disk = Storage::disk((string) config('ebook.unduh.disk', 'local'));

        return $disk;
    }

    private function folderSementara(): string
    {
        return trim((string) config('ebook.unduh.folder', 'unduhan-sementara'), '/');
    }
}