<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentLeaveRequest extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

    public const TYPES = ['medical', 'family', 'academic', 'other'];
    use HasFactory;

    protected $fillable = [
        'student_id',
        'start_date',
        'end_date',
        'type',
        'reason',
        'status',
        'reviewed_by',
        'teacher_remarks',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeOverlapping($query, string $startDate, string $endDate)
    {
        return $query->whereDate('start_date', '<=', $endDate)
            ->whereDate('end_date', '>=', $startDate);
    }
}
