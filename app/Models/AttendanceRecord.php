<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'student_id',
        'status',
        'remarks',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(AttendanceSession::class, 'session_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * Attendance rate: (present + late + half-day weight) / eligible sessions.
     * Approved excused sessions are excluded; no eligible sessions returns 0 and must be displayed as no data.
     */
    public static function calculatePercentage(int $presentCount, int $totalSessions, int $lateCount = 0, int $excusedCount = 0, int $halfDayCount = 0): float
    {
        $eligibleSessions = max(0, $totalSessions - $excusedCount);
        if ($eligibleSessions <= 0) {
            return 0.0;
        }

        $effectivePresence = min($eligibleSessions, $presentCount + $lateCount + ($halfDayCount * 0.5));

        return min(100.0, max(0.0, round(($effectivePresence / $eligibleSessions) * 100, 1)));
    }
}
