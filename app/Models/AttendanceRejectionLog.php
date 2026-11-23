<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRejectionLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'user_name',
        'user_role',
        'class_id',
        'action',
        'reason',
        'ip_address',
        'latitude',
        'longitude',
        'session_date',
        'attempted_at',
        'context',
    ];

    protected $casts = [
        'attempted_at' => 'datetime',
        'session_date' => 'date',
        'context' => 'array',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function academicClass(): BelongsTo
    {
        return $this->belongsTo(AcademicClass::class, 'class_id');
    }
}
