<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseModule extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'week_number',
        'title',
        'theme',
        'description',
        'learning_goals',
        'order_index',
    ];

    protected function casts(): array
    {
        return [
            'week_number' => 'integer',
            'order_index' => 'integer',
            'learning_goals' => 'array',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(CourseLesson::class, 'module_id')->orderBy('lesson_number');
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(CourseAssessment::class, 'module_id');
    }
}
