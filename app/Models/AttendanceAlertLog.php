<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceAlertLog extends Model
{
    use HasFactory;

    protected $table = 'attendance_alert_logs';

    protected $fillable = [
        'student_id',
        'session_id',
        'attendance_date',
        'channel',
        'recipient',
        'status',
        'message_payload',
    ];

    protected $casts = [
        'attendance_date' => 'date',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(AttendanceSession::class, 'session_id');
    }
}
