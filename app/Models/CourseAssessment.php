<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CourseAssessment extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'module_id',
        'type',
        'title',
        'week_number',
        'description',
        'total_points',
        'passing_score',
        'time_limit_minutes',
        'rubric',
        'marking_scheme',
        'questions',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'week_number' => 'integer',
            'total_points' => 'integer',
            'passing_score' => 'integer',
            'time_limit_minutes' => 'integer',
            'is_published' => 'boolean',
            'rubric' => 'array',
            'marking_scheme' => 'array',
            'questions' => 'array',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(CourseModule::class, 'module_id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(CourseAssessmentSubmission::class, 'assessment_id');
    }

    public function studentSubmission(int $studentId): HasOne
    {
        return $this->hasOne(CourseAssessmentSubmission::class, 'assessment_id')->where('student_id', $studentId);
    }
}
