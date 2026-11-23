<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DailyDpp extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'lesson_id',
        'class_id',
        'subject_id',
        'topic_id',
        'teacher_id',
        'dpp_number',
        'type',
        'title',
        'assigned_date',
        'due_time',
        'total_marks',
        'difficulty',
        'duration_minutes',
        'is_timed',
        'pass_percentage',
        'negative_marks',
    ];

    protected function casts(): array
    {
        return [
            'assigned_date' => 'date',
            'total_marks' => 'integer',
            'duration_minutes' => 'integer',
            'is_timed' => 'boolean',
            'pass_percentage' => 'integer',
            'negative_marks' => 'float',
        ];
    }

    public function isTest(): bool
    {
        return $this->type === 'test';
    }

    public function isRapidFire(): bool
    {
        return $this->type === 'rapid_fire';
    }

    public function isTimed(): bool
    {
        return (bool) $this->is_timed && $this->duration_minutes > 0;
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(CourseLesson::class, 'lesson_id');
    }

    public function academicClass(): BelongsTo
    {
        return $this->belongsTo(AcademicClass::class, 'class_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(AcademicSubject::class, 'subject_id');
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(CurriculumTopic::class, 'topic_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(DppQuestion::class, 'dpp_id')->orderBy('question_number');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(DppSubmission::class, 'dpp_id');
    }

    public function studentSubmission(int $studentId): ?DppSubmission
    {
        return $this->submissions()->where('student_id', $studentId)->first();
    }
}
