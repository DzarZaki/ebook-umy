<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReadingProgress extends Model
{
    // Nama tabel ditulis tegas karena bentuk jamak otomatis Laravel tidak sesuai.
    protected $table = 'reading_progress';

    protected $fillable = [
        'user_id',
        'book_id',
        'last_page',
        'total_pages',
    ];

    protected $casts = [
        'last_page' => 'integer',
        'total_pages' => 'integer',
    ];

    /** Pemilik kemajuan membaca ini. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Buku yang sedang dibaca. */
    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    /** Persentase kemajuan membaca untuk ditampilkan sebagai bilah. */
    public function persen(): int
    {
        if (! $this->total_pages) {
            return 0;
        }

        return (int) min(100, round($this->last_page / $this->total_pages * 100));
    }
}
