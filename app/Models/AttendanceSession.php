<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_id',
        'course_id',
        'teacher_id',
        'session_date',
        'title',
        'notes',
        'period_number',
        'session_type',
    ];

    protected function casts(): array
    {
        return [
            'session_date' => 'date',
        ];
    }

    public function setSessionDateAttribute($value): void
    {
        $this->attributes['session_date'] = \Carbon\Carbon::parse($value)->format('Y-m-d');
    }

    public function academicClass(): BelongsTo
    {
        return $this->belongsTo(AcademicClass::class, 'class_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function records(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class, 'session_id');
    }
}
