<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'class_id',
        'batch_id',
        'delivery_mode', // 'offline' or 'online'
        'title',
        'day_of_week',
        'start_time',
        'end_time',
        'room',
        'is_cancelled',
        'cancellation_reason',
        'description',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function academicClass(): BelongsTo
    {
        return $this->belongsTo(AcademicClass::class, 'class_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class, 'batch_id');
    }

    public function isOffline(): bool
    {
        return ($this->delivery_mode ?? 'offline') === 'offline';
    }

    public function isOnline(): bool
    {
        return ($this->delivery_mode ?? '') === 'online';
    }

    /**
     * Display label for classroom/venue.
     */
    public function getVenueLabelAttribute(): string
    {
        if ($this->isOnline()) {
            return $this->room ?: 'Online Live Classroom';
        }

        return $this->room ?: 'Physical Classroom TBA';
    }

    /**
     * Associated subject or course title.
     */
    public function getSubjectTitleAttribute(): string
    {
        if ($this->course) {
            return $this->course->title;
        }

        if ($this->academicClass) {
            return $this->academicClass->name . ($this->batch ? ' - ' . $this->batch->name : '');
        }

        return $this->title;
    }
}
