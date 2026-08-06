<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model Prodi — merepresentasikan satu program studi.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property bool $download_enabled
 */
class Prodi extends Model
{
    use HasFactory;

    /**
     * Nama tabel dibuat eksplisit karena kata "prodi" tidak berbentuk jamak.
     */
    protected $table = 'prodi';

    /**
     * Kolom yang boleh diisi massal.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'download_enabled',
        'access_code',
    ];

    /**
     * Konversi tipe data kolom.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'download_enabled' => 'boolean',
        ];
    }

    /**
     * Relasi: satu prodi memiliki banyak user (dosen & mahasiswa).
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Gunakan slug sebagai kunci pada route model binding.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** Relasi: semua buku milik program studi ini. */
    public function books(): HasMany
    {
        return $this->hasMany(Book::class);
    }

    /** Relasi: semua kategori milik program studi ini. */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    /** Mencari prodi berdasarkan kode akses, tanpa peduli huruf besar-kecil. */
    public static function cariDenganKode(?string $kode): ?self
    {
        $kode = trim((string) $kode);

        if ($kode === '') {
            return null;
        }

        return static::query()
            ->whereRaw('UPPER(access_code) = ?', [strtoupper($kode)])
            ->first();
    }
}
