<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BiometricLog extends Model
{
    use HasFactory;

    protected $table = 'biometric_logs';

    protected $fillable = [
        'device_id',
        'biometric_pin',
        'user_id',
        'punch_time',
        'punch_type',
        'status',
        'error_message',
    ];

    protected $casts = [
        'punch_time' => 'datetime',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(BiometricDevice::class, 'device_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
