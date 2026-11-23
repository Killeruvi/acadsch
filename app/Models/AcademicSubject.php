<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicSubject extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_id',
        'subject_master_id',
        'name',
        'code',
        'code_key',
        'teacher_id',
        'weekly_periods',
    ];

    public function setCodeAttribute($value): void
    {
        $normalized = strtoupper(trim((string) $value));
        $this->attributes['code'] = $normalized;
        $this->attributes['code_key'] = $normalized;
    }

    public function subjectMaster(): BelongsTo
    {
        return $this->belongsTo(SubjectMaster::class, 'subject_master_id');
    }

    public function academicClass(): BelongsTo
    {
        return $this->belongsTo(AcademicClass::class, 'class_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function timetables(): HasMany
    {
        return $this->hasMany(SchoolPeriodTimetable::class, 'subject_id');
    }

    public function examMarks(): HasMany
    {
        return $this->hasMany(SchoolExamMark::class, 'subject_id');
    }

    public function examSchedules(): HasMany
    {
        return $this->hasMany(SchoolExamSchedule::class, 'subject_id');
    }

    public function topics(): HasMany
    {
        return $this->hasMany(CurriculumTopic::class, 'subject_id')->orderBy('chapter_number');
    }

    public function videos(): HasMany
    {
        return $this->hasMany(CurriculumVideo::class, 'subject_id')->orderBy('order_index');
    }

    public function dpps(): HasMany
    {
        return $this->hasMany(DailyDpp::class, 'subject_id')->orderBy('assigned_date', 'desc');
    }

    public function homeworkTasks(): HasMany
    {
        return $this->hasMany(HomeworkTask::class, 'subject_id')->orderBy('assigned_date', 'desc');
    }

    public function lessonPlans(): HasMany
    {
        return $this->hasMany(LessonPlan::class, 'subject_id')->orderBy('planned_date')->orderBy('order_index');
    }
}
