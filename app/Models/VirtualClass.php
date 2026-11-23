<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VirtualClass extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'class_id',
        'batch_id',
        'instructor_id',
        'host',
        'start_time',
        'end_time',
        'duration',
        'meeting_id',
        'password',
        'join_url',
        'recording_url',
        'status',
        'capacity',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'duration' => 'integer',
        'capacity' => 'integer',
    ];

    public function academicClass(): BelongsTo
    {
        return $this->belongsTo(AcademicClass::class, 'class_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class, 'batch_id');
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    /**
     * Compute if the session is currently live.
     */
    public function isLiveNow(): bool
    {
        if ($this->status === 'live') {
            return true;
        }

        $now = now();
        $start = $this->start_time;
        $end = $this->end_time ?? $this->start_time->copy()->addMinutes($this->duration);

        return $now->between($start, $end);
    }

    /**
     * Get host display name.
     */
    public function getHostLabelAttribute(): string
    {
        return match ($this->host) {
            'zoom' => 'Zoom Meeting',
            'google_meet' => 'Google Meet',
            'jitsi' => 'Jitsi Live',
            'in_app' => 'In-App Live Class',
            default => 'Custom Live Session',
        };
    }

    /**
     * Get host icon class.
     */
    public function getHostIconAttribute(): string
    {
        return match ($this->host) {
            'zoom' => 'ti-video-camera',
            'google_meet' => 'ti-desktop',
            'jitsi' => 'ti-headphone-alt',
            'in_app' => 'ti-blackboard',
            default => 'ti-link',
        };
    }
}
