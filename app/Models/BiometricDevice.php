<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BiometricDevice extends Model
{
    use HasFactory;

    protected $table = 'biometric_devices';

    protected $fillable = [
        'device_name',
        'device_serial',
        'device_model',
        'device_ip',
        'device_location',
        'auth_token',
        'last_heartbeat',
        'status',
    ];

    protected $casts = [
        'last_heartbeat' => 'datetime',
    ];

    public function logs(): HasMany
    {
        return $this->hasMany(BiometricLog::class, 'device_id');
    }

    public function isOnline(): bool
    {
        if ($this->status === 'disabled') {
            return false;
        }

        return $this->last_heartbeat && $this->last_heartbeat->diffInMinutes(now()) <= 10;
    }
}
