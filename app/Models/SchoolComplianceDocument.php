<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolComplianceDocument extends Model
{
    protected $fillable = [
        'category', 'title', 'document_number', 'issuing_authority',
        'issued_on', 'expires_on', 'file_path', 'original_filename', 'notes', 'created_by',
    ];

    protected $casts = [
        'issued_on' => 'date',
        'expires_on' => 'date',
    ];

    public function getExpiryStatusAttribute(): string
    {
        if (! $this->expires_on) {
            return 'no_expiry';
        }
        $expiry = $this->expires_on->copy()->endOfDay();
        if ($expiry->lt(now())) {
            return 'expired';
        }
        if ($expiry->lte(now()->copy()->addDays(60)->endOfDay())) {
            return 'expiring';
        }

        return 'valid';
    }
}
