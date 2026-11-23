<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolPeriodSubstitute extends Model
{
    use HasFactory;

    protected $fillable = [
        'timetable_id',
        'original_teacher_id',
        'substitute_teacher_id',
        'date',
        'reason',
        'status',
        'assigned_by',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
    ];

    public function setDateAttribute($value): void
    {
        $this->attributes['date'] = \Carbon\Carbon::parse($value)->toDateString();
    }

    public function timetable(): BelongsTo
    {
        return $this->belongsTo(SchoolPeriodTimetable::class, 'timetable_id');
    }

    public function originalTeacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'original_teacher_id');
    }

    public function substituteTeacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'substitute_teacher_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
