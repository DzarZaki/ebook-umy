<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Model Book — satu berkas e-book/artikel PDF beserta aturan aksesnya.
 *
 * Buku dihapus secara lunak. Menghapusnya berarti ikut membuang riwayat baca,
 * progres halaman, dan penanda milik mahasiswa, jadi penghapusan diberi masa
 * tenggang sebelum perintah pembersih membuatnya permanen.
 */
class Book extends Model
{
    use HasFactory, SoftDeletes;

    /** Mode akses unduh. */
    public const AKSES_PENUH = 'full';

    public const AKSES_SEBAGIAN = 'partial';

    public const AKSES_BACA_SAJA = 'readonly';

    protected $fillable = [
        'title', 'slug', 'author', 'description',
        'prodi_id', 'category_id', 'uploaded_by',
        'file_path', 'file_size', 'page_count', 'cover_path',
        'access_mode', 'download_page_start', 'download_page_end',
        'watermark_enabled', 'is_published',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'watermark_enabled' => 'boolean',
            'is_published' => 'boolean',
            'file_size' => 'integer',
        ];
    }

    public function prodi(): BelongsTo
    {
        return $this->belongsTo(Prodi::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function pengunggah(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /** Relasi: seluruh catatan unduhan buku ini. */
    public function downloadLogs(): HasMany
    {
        return $this->hasMany(DownloadLog::class);
    }

    /**
     * Relasi: pengguna yang menyimpan buku ini ke koleksinya.
     *
     * Kebalikan dari User::bukuTersimpan(). Berguna untuk menghitung
     * berapa banyak mahasiswa yang menyimpan sebuah buku — angka yang
     * kelak berarti bagi dosen di halaman statistik.
     */
    public function tersimpanOleh(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'book_saves')
            ->withTimestamps();
    }

    /** Gunakan slug pada route model binding. */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Pasangan [disk, jalur] seluruh berkas milik buku ini.
     *
     * Dipakai bersama oleh controller dan perintah pembersih, supaya
     * pengetahuan tentang disk mana menyimpan apa hanya tinggal di satu tempat.
     *
     * @return array<int, array{0: string, 1: string|null}>
     */
    public function berkasnya(): array
    {
        return [
            ['local', $this->file_path],
            ['public', $this->cover_path],
        ];
    }

    /** Apakah buku ini bersifat umum (lintas prodi)? */
    public function isUmum(): bool
    {
        return $this->prodi_id === null;
    }

    /** Label mode akses dalam bahasa Indonesia. */
    public function labelAkses(): string
    {
        return match ($this->access_mode) {
            self::AKSES_PENUH => 'Unduh penuh',
            self::AKSES_SEBAGIAN => "Unduh hal. {$this->download_page_start}–{$this->download_page_end}",
            default => 'Baca saja',
        };
    }

    /** Ukuran berkas dalam MB, dibulatkan 1 desimal. */
    public function ukuranMb(): float
    {
        return round($this->file_size / 1048576, 1);
    }

    /** Alamat gambar sampul, atau null bila buku tidak punya sampul. */
    public function coverUrl(): ?string
    {
        return $this->cover_path
            ? Storage::disk('public')->url($this->cover_path)
            : null;
    }

    /** Dua huruf awal judul, dipakai sebagai sampul cadangan. */
    public function inisial(): string
    {
        return Str::upper(Str::substr($this->title, 0, 2));
    }

    /**
     * Scope: buku yang boleh dilihat mahasiswa sebuah prodi
     * (buku prodinya sendiri + seluruh buku Umum).
     */
    public function scopeTerlihatOleh(Builder $query, ?int $prodiId): Builder
    {
        return $query->where(function (Builder $q) use ($prodiId) {
            $q->whereNull('prodi_id')->orWhere('prodi_id', $prodiId);
        });
    }

    /** Scope: hanya buku yang sudah dipublikasikan. */
    public function scopeTerbit(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /**
     * Scope: menyertakan status "sudah saya simpan" untuk seluruh baris
     * dalam SATU kueri tambahan, bukan satu kueri per kartu buku.
     *
     * Tanpa ini, dua puluh kartu di halaman katalog berarti dua puluh
     * perjalanan ke basis data — jenis kelambatan yang tidak terasa saat
     * ada satu buku, dan menyiksa saat ada dua ratus.
     *
     * Hasilnya terbaca lewat sudahDisimpan() pada tiap buku.
     */
    public function scopeDenganStatusSimpan(Builder $query, ?User $pengguna): Builder
    {
        if (! $pengguna) {
            return $query;
        }

        return $query->withExists([
            'tersimpanOleh as tersimpan' => function ($q) use ($pengguna) {
                $q->where('users.id', $pengguna->getKey());
            },
        ]);
    }

    /**
     * Apakah buku ini ada di koleksi pengguna yang sedang melihat?
     *
     * Hanya bermakna bila kuerinya memakai scope denganStatusSimpan().
     * Bila tidak, jawabannya selalu false — sengaja begitu, supaya
     * halaman yang lupa memakai scope-nya menampilkan tombol simpan
     * yang tidak aktif, bukan diam-diam menembakkan ratusan kueri.
     */
    public function sudahDisimpan(): bool
    {
        return (bool) ($this->attributes['tersimpan'] ?? false);
    }

    /**
     * Apakah pengguna boleh melihat/membaca buku ini?
     */
    public function bolehDilihatOleh(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! $this->is_published) {
            return false;
        }

        return $this->isUmum() || $this->prodi_id === $user->prodi_id;
    }

    /**
     * Menghitung aturan unduh yang berlaku bagi seorang pengguna.
     *
     * Tiga hal diperiksa berurutan:
     *  1. Mode akses buku yang ditetapkan dosen
     *  2. Sakelar unduh milik prodi (dosen dapat mematikannya sewaktu-waktu)
     *  3. Rentang halaman bila mode-nya "sebagian"
     *
     * @return array{boleh: bool, alasan: string, awal: ?int, akhir: ?int}
     */
    public function aturanUnduhUntuk(User $user): array
    {
        if ($this->access_mode === self::AKSES_BACA_SAJA) {
            return [
                'boleh' => false,
                'alasan' => 'Dosen menetapkan buku ini hanya untuk dibaca di halaman ini.',
                'awal' => null,
                'akhir' => null,
            ];
        }

        // Buku prodi memakai sakelar prodinya; buku umum memakai sakelar prodi pembacanya.
        $prodiPenentu = $this->prodi ?? $user->prodi;

        if (! $user->isSuperAdmin() && $prodiPenentu && ! $prodiPenentu->download_enabled) {
            return [
                'boleh' => false,
                'alasan' => 'Unduhan sedang dinonaktifkan oleh dosen pengelola program studi.',
                'awal' => null,
                'akhir' => null,
            ];
        }

        if ($this->access_mode === self::AKSES_SEBAGIAN) {
            return [
                'boleh' => true,
                'alasan' => "Hanya halaman {$this->download_page_start}–{$this->download_page_end} yang dapat diunduh.",
                'awal' => $this->download_page_start,
                'akhir' => $this->download_page_end,
            ];
        }

        return [
            'boleh' => true,
            'alasan' => 'Seluruh isi buku dapat diunduh.',
            'awal' => null,
            'akhir' => null,
        ];
    }

    /**
     * Apakah dosen tertentu boleh mengubah/menghapus buku ini?
     * Aturan sama seperti kategori.
     */
    public function bolehDikelolaOleh(User $user): bool
    {
        if ($this->isUmum()) {
            return $this->uploaded_by === $user->id;
        }

        return $this->prodi_id === $user->prodi_id;
    }
}