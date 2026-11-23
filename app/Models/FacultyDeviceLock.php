<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacultyDeviceLock extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_id',
        'device_fingerprint',
        'ip_address',
        'user_id',
        'faculty_code_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function code(): BelongsTo
    {
        return $this->belongsTo(FacultyAccessCode::class, 'faculty_code_id');
    }

    /**
     * Check if a device has already registered a teacher account.
     */
    public static function isDeviceLocked(?string $deviceId, ?string $fingerprint): bool
    {
        if (empty($deviceId) && empty($fingerprint)) {
            return false;
        }

        return self::query()
            ->where(function ($query) use ($deviceId, $fingerprint) {
                if (!empty($deviceId)) {
                    $query->where('device_id', $deviceId);
                }
                if (!empty($fingerprint)) {
                    $query->orWhere('device_fingerprint', $fingerprint);
                }
            })
            ->exists();
    }
}
