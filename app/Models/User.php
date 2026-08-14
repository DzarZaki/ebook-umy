<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Model User — mewakili Super Admin, Admin/Dosen, dan Mahasiswa.
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $role
 * @property int|null $prodi_id
 * @property bool $is_active
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /** Konstanta peran agar tidak salah ketik di seluruh aplikasi. */
    public const ROLE_SUPERADMIN = 'superadmin';

    public const ROLE_ADMIN = 'admin';

    public const ROLE_MAHASISWA = 'mahasiswa';

    /**
     * Kolom yang boleh diisi massal.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'prodi_id',
        'is_active',
    ];

    /**
     * Kolom yang disembunyikan saat serialisasi.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Konversi tipe data kolom.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Relasi: user milik satu prodi (null untuk Super Admin).
     */
    public function prodi(): BelongsTo
    {
        return $this->belongsTo(Prodi::class);
    }

    /**
     * Buku yang sengaja disimpan pengguna untuk dibaca nanti.
     *
     * Urutannya dari yang paling baru disimpan, karena itulah urutan yang
     * dibutuhkan halaman Koleksi Saya dan bagian "Tersimpan" di beranda.
     *
     * Buku yang sedang berada di Tempat Sampah otomatis tidak ikut muncul —
     * SoftDeletes pada model Book yang mengurusnya. Barisnya tetap ada di
     * tabel, sehingga buku yang dipulihkan dosen kembali ke koleksi
     * mahasiswa tanpa ada yang perlu menyimpannya ulang.
     */
    public function bukuTersimpan(): BelongsToMany
    {
        return $this->belongsToMany(Book::class, 'book_saves')
            ->withTimestamps()
            ->orderByDesc('book_saves.created_at');
    }

    /** Kemajuan membaca pengguna ini di seluruh buku. */
    public function kemajuanBaca(): HasMany
    {
        return $this->hasMany(ReadingProgress::class);
    }

    /** Seluruh penanda halaman milik pengguna ini. */
    public function penanda(): HasMany
    {
        return $this->hasMany(Bookmark::class);
    }

    /**
     * Apakah buku ini sudah ada di koleksi pengguna?
     *
     * Sengaja bertanya langsung ke basis data alih-alih memuat seluruh
     * koleksi, supaya pemeriksaan pada satu kartu buku tidak menyeret
     * ratusan baris. Untuk daftar panjang, muat kumpulan id-nya sekali
     * di controller — lihat KoleksiController pada langkah berikutnya.
     */
    public function telahMenyimpan(Book $buku): bool
    {
        return $this->bukuTersimpan()
            ->where('books.id', $buku->getKey())
            ->exists();
    }

    /**
     * Apakah user ini Super Admin?
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPERADMIN;
    }

    /**
     * Apakah user ini Admin/Dosen?
     */
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /**
     * Apakah user ini Mahasiswa?
     */
    public function isMahasiswa(): bool
    {
        return $this->role === self::ROLE_MAHASISWA;
    }
}