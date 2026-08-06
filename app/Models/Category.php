<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model Category — kategori konten milik satu prodi, atau Umum bila prodi_id NULL.
 */
class Category extends Model
{
    use HasFactory;

    protected $fillable = ['prodi_id', 'name', 'slug', 'created_by'];

    /** Relasi: kategori milik satu prodi (NULL = Umum). */
    public function prodi(): BelongsTo
    {
        return $this->belongsTo(Prodi::class);
    }

    /** Relasi: dosen yang membuat kategori ini. */
    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Relasi: buku-buku dalam kategori ini. */
    public function books(): HasMany
    {
        return $this->hasMany(Book::class);
    }

    /** Apakah kategori ini bersifat umum (lintas prodi)? */
    public function isUmum(): bool
    {
        return $this->prodi_id === null;
    }

    /**
     * Scope: kategori yang terlihat oleh sebuah prodi,
     * yaitu kategori prodi tersebut ditambah seluruh kategori Umum.
     */
    public function scopeTerlihatOleh(Builder $query, ?int $prodiId): Builder
    {
        return $query->where(function (Builder $q) use ($prodiId) {
            $q->whereNull('prodi_id')->orWhere('prodi_id', $prodiId);
        });
    }

    /**
     * Apakah dosen tertentu boleh mengubah/menghapus kategori ini?
     * Dosen bebas mengelola kategori prodinya; untuk kategori Umum,
     * hanya pembuatnya sendiri yang boleh mengubah.
     */
    public function bolehDikelolaOleh(User $user): bool
    {
        if ($this->isUmum()) {
            return $this->created_by === $user->id;
        }

        return $this->prodi_id === $user->prodi_id;
    }
}
