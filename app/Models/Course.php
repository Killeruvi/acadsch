<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'code',
        'description',
        'category',
        'credits',
        'capacity',
        'teacher_id',
        'status',
        'start_date',
        'end_date',
    ];

    protected function casts(): array
    {
        return [
            'credits' => 'integer',
            'capacity' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'course_id');
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'enrollments', 'course_id', 'student_id')
                    ->withPivot(['id', 'status', 'enrolled_at', 'grade_letter', 'final_score'])
                    ->withTimestamps();
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class, 'course_id')->orderBy('day_of_week')->orderBy('start_time');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class, 'course_id')->orderBy('due_date');
    }

    public function modules(): HasMany
    {
        return $this->hasMany(CourseModule::class, 'course_id')->orderBy('week_number');
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(CourseLesson::class, 'course_id')->orderBy('lesson_number');
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(CourseAssessment::class, 'course_id')->orderBy('week_number');
    }

    public function dpps(): HasMany
    {
        return $this->hasMany(DailyDpp::class, 'course_id')->orderBy('dpp_number');
    }
}
