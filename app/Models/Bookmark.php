<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bookmark extends Model
{
    protected $fillable = [
        'user_id',
        'book_id',
        'page',
        'note',
    ];

    protected $casts = [
        'page' => 'integer',
    ];

    /** Pemilik penanda. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Buku tempat penanda dipasang. */
    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
}
