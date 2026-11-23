<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicClass extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'grade_level',
        'stream',
        'section',
        'section_id',
        'academic_year',
        'class_teacher_id',
        'room_number',
        'capacity',
    ];

    public function schoolSection(): BelongsTo
    {
        return $this->belongsTo(SchoolSection::class, 'section_id');
    }

    public function classTeacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'class_teacher_id');
    }

    public function subjects(): HasMany
    {
        return $this->hasMany(AcademicSubject::class, 'class_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(ClassEnrollment::class, 'class_id')->orderBy('roll_number');
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'class_enrollments', 'class_id', 'student_id')
            ->withPivot(['id', 'roll_number', 'admission_number', 'academic_year', 'status'])
            ->wherePivot('status', 'active')
            ->where('users.role', 'student')
            ->where('users.is_approved', true)
            ->withTimestamps()
            ->orderBy('class_enrollments.roll_number');
    }

    public function timetables(): HasMany
    {
        return $this->hasMany(SchoolPeriodTimetable::class, 'class_id')
            ->orderBy('period_number');
    }

    public function feeStructures(): HasMany
    {
        return $this->hasMany(SchoolFeeStructure::class, 'class_id');
    }

    public function examSchedules(): HasMany
    {
        return $this->hasMany(SchoolExamSchedule::class, 'class_id');
    }

    public function reportCards(): HasMany
    {
        return $this->hasMany(SchoolReportCard::class, 'class_id');
    }

    public function attendanceSessions(): HasMany
    {
        return $this->hasMany(AttendanceSession::class, 'class_id')->latest('session_date');
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(DailyLesson::class, 'class_id')->latest('lesson_date');
    }

    public function batches(): HasMany
    {
        return $this->hasMany(Batch::class, 'class_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class, 'class_id')->orderBy('day_of_week')->orderBy('start_time');
    }

    public function lessonPlans(): HasMany
    {
        return $this->hasMany(LessonPlan::class, 'class_id')->orderBy('planned_date')->orderBy('order_index');
    }

    public static function getDeduplicatedClasses()
    {
        return static::orderBy('grade_level')
            ->orderBy('stream')
            ->orderBy('section')
            ->get()
            ->unique(function ($class) {
                return $class->name.'-'.($class->stream ?? $class->section);
            })
            ->values();
    }

    public function getStreamNameAttribute(): ?string
    {
        return $this->stream ?: (in_array(strtolower($this->section ?? ''), ['science', 'commerce', 'arts', 'humanities']) ? ucfirst($this->section) : null);
    }

    public function getNameWithStreamAttribute(): string
    {
        return $this->display_name;
    }

    public function getDisplayNameAttribute(): string
    {
        $stream = $this->stream ?: $this->stream_name;

        if ($stream && ! in_array(strtolower($stream), ['general', 'default', 'core'])) {
            $hasDistinctSection = $this->section && ! in_array(strtolower($this->section), [strtolower($stream), 'default', '']);

            return "{$this->name} – {$stream}".($hasDistinctSection ? " (Sec {$this->section})" : '');
        }

        if ($this->section) {
            return "{$this->name} - Section {$this->section}";
        }

        return $this->name;
    }
}
