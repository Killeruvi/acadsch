<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class FacultyAccessCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'generated_by',
        'notes',
        'is_used',
        'used_by',
        'used_at',
        'device_fingerprint',
    ];

    protected function casts(): array
    {
        return [
            'is_used' => 'boolean',
            'used_at' => 'datetime',
        ];
    }

    /**
     * Admin who generated this authorization code.
     */
    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    /**
     * Teacher who redeemed this authorization code.
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'used_by');
    }

    /**
     * Generate a unique, formatted institutional authorization code.
     */
    public static function createUniqueCode(int $adminId, ?string $notes = null): self
    {
        do {
            $code = 'FAC-' . strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(4));
        } while (self::where('code', $code)->exists());

        return self::create([
            'code' => $code,
            'generated_by' => $adminId,
            'notes' => $notes,
            'is_used' => false,
        ]);
    }
}
