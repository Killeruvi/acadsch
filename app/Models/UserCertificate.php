<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserCertificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'issuer_id',
        'certificate_number',
        'title',
        'category',
        'citation',
        'issued_date',
        'verification_hash',
        'revoked_at',
        'revoked_by',
        'revocation_reason',
    ];

    protected function casts(): array
    {
        return [
            'issued_date' => 'date',
            'revoked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function revoker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issuer_id');
    }
}
