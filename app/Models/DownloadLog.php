<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model DownloadLog — satu baris untuk satu peristiwa unduhan.
 */
class DownloadLog extends Model
{
    protected $fillable = ['book_id', 'user_id', 'prodi_id', 'mode'];

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function prodi(): BelongsTo
    {
        return $this->belongsTo(Prodi::class);
    }
}
