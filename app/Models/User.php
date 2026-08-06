<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
