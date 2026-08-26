<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Model LecturerProfile — Data profil & personal branding dosen untuk halaman muka.
 *
 * @property int $id
 * @property int $user_id
 * @property string|null $title_prefix
 * @property string|null $title_suffix
 * @property string|null $nidn
 * @property string|null $academic_position
 * @property string|null $expertise
 * @property string|null $bio
 * @property string|null $quote
 * @property string|null $photo_path
 * @property string|null $google_scholar_url
 * @property string|null $scopus_url
 * @property string|null $linkedin_url
 * @property string|null $website_url
 * @property bool $is_displayed
 */
class LecturerProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title_prefix',
        'title_suffix',
        'nidn',
        'academic_position',
        'expertise',
        'bio',
        'quote',
        'photo_path',
        'google_scholar_url',
        'scopus_url',
        'linkedin_url',
        'website_url',
        'is_displayed',
    ];

    protected function casts(): array
    {
        return [
            'is_displayed' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Nama lengkap dengan gelar depan dan belakang.
     */
    public function getNamaLengkapAttribute(): string
    {
        $nama = $this->user?->name ?? '';
        $depan = $this->title_prefix ? trim($this->title_prefix).' ' : '';
        $belakang = $this->title_suffix ? ', '.trim($this->title_suffix) : '';

        return trim($depan.$nama.$belakang);
    }

    /**
     * URL Foto Profil Dosen (jika ada).
     */
    public function photoUrl(): ?string
    {
        if (! $this->photo_path) {
            return null;
        }

        return Storage::disk('public')->url($this->photo_path);
    }

    /**
     * Daftar keahlian sebagai array.
     *
     * @return array<int, string>
     */
    public function getDaftarKeahlianAttribute(): array
    {
        if (! $this->expertise) {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $this->expertise))));
    }
}
