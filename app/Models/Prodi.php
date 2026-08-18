<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Model Prodi — merepresentasikan satu program studi.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $access_code
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
     * Kode akses selalu disimpan dalam huruf besar tanpa spasi tepi, dan
     * string kosong disimpan sebagai NULL.
     *
     * Penyeragaman diletakkan di model, bukan di FormRequest, karena kolom
     * ini bersifat unik secara peka huruf di basis data sementara
     * pencariannya tidak peka huruf. Bila `PAI-2026` dan `pai-2026` sampai
     * hidup berdampingan, satu kode akses akan cocok dengan dua prodi dan
     * mahasiswa terdaftar ke prodi yang ditentukan urutan baris — akses ke
     * koleksi prodi lain, tanpa satu pun galat yang terlihat.
     *
     * Dengan mutator ini, seeder, tinker, impor data, dan formulir apa pun
     * yang kelak menulis kolom ini ikut terjaga tanpa perlu mengingatnya.
     */
    protected function accessCode(): Attribute
    {
        return Attribute::make(
            set: fn (?string $nilai): ?string => self::seragamkanKode($nilai),
        );
    }

    /**
     * Bentuk baku sebuah kode akses. Dipakai bersama oleh mutator di atas,
     * pencarian di bawah, dan validasi di UpdateKodeAksesRequest, supaya
     * ketiganya tidak mungkin berbeda pendapat.
     */
    public static function seragamkanKode(?string $kode): ?string
    {
        $kode = trim((string) $kode);

        return $kode === '' ? null : Str::upper($kode);
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

    /**
     * Mencari prodi berdasarkan kode akses. Masukan pengguna diseragamkan
     * lebih dulu, jadi huruf kecil dan spasi tepi tetap diterima — tanpa
     * membungkus kolomnya dalam UPPER(), yang membuat indeks unik pada
     * `access_code` tidak terpakai.
     */
    public static function cariDenganKode(?string $kode): ?self
    {
        $kode = self::seragamkanKode($kode);

        if ($kode === null) {
            return null;
        }

        return static::query()
            ->where('access_code', $kode)
            ->first();
    }
}