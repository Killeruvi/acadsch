<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookReservation extends Model
{
    protected $fillable = ['book_id', 'member_id', 'status', 'reserved_at', 'fulfilled_at', 'cancelled_at'];

    protected function casts(): array
    {
        return ['reserved_at' => 'datetime', 'fulfilled_at' => 'datetime', 'cancelled_at' => 'datetime'];
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(LibraryBook::class, 'book_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(User::class, 'member_id');
    }
}
