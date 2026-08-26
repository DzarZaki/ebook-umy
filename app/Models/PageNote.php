<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model PageNote — Catatan belajar pribadi mahasiswa per halaman pada sebuah buku.
 *
 * @property int $id
 * @property int $user_id
 * @property int $book_id
 * @property int $page
 * @property string $content
 */
class PageNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'book_id',
        'page',
        'content',
    ];

    protected function casts(): array
    {
        return [
            'page' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
}
