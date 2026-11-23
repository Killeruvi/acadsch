<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CircularReadReceipt extends Model
{
    protected $fillable = [
        'notice_id',
        'user_id',
        'acknowledged',
        'read_at',
        'acknowledged_at',
    ];

    protected function casts(): array
    {
        return [
            'acknowledged' => 'boolean',
            'read_at' => 'datetime',
            'acknowledged_at' => 'datetime',
        ];
    }

    public function notice(): BelongsTo
    {
        return $this->belongsTo(AcademyNotice::class, 'notice_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
