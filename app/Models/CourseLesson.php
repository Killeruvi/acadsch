<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CourseLesson extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'module_id',
        'lesson_number',
        'week_number',
        'day_of_week',
        'title',
        'duration_minutes',
        'level',
        'topic_summary',
        'learning_objectives',
        'expected_outcomes',
        'vocabulary',
        'concepts',
        'teaching_materials',
        'time_allocation',
        'procedure_steps',
        'speaking_activity',
        'listening_activity',
        'reading_activity',
        'writing_activity',
        'real_life_scenario',
        'common_mistakes',
        'homework_task',
        'evaluation_criteria',
        'teacher_script',
        'worksheet',
        'answer_key',
        'class_notes',
        'pdf_url',
        'video_url',
        'order_index',
    ];

    protected function casts(): array
    {
        return [
            'lesson_number' => 'integer',
            'week_number' => 'integer',
            'day_of_week' => 'integer',
            'duration_minutes' => 'integer',
            'order_index' => 'integer',
            'learning_objectives' => 'array',
            'vocabulary' => 'array',
            'concepts' => 'array',
            'teaching_materials' => 'array',
            'time_allocation' => 'array',
            'procedure_steps' => 'array',
            'speaking_activity' => 'array',
            'listening_activity' => 'array',
            'reading_activity' => 'array',
            'writing_activity' => 'array',
            'common_mistakes' => 'array',
            'teacher_script' => 'array',
            'worksheet' => 'array',
            'answer_key' => 'array',
            'class_notes' => 'array',
        ];
    }

    public function getClassNotesAttribute(): array
    {
        if (!empty($this->attributes['class_notes'])) {
            $val = json_decode($this->attributes['class_notes'], true);
            if (is_array($val)) {
                return $val;
            }
        }

        return [];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(CourseModule::class, 'module_id');
    }

    public function progress(): HasMany
    {
        return $this->hasMany(CourseLessonProgress::class, 'lesson_id');
    }

    public function studentProgress(int $studentId): HasOne
    {
        return $this->hasOne(CourseLessonProgress::class, 'lesson_id')->where('student_id', $studentId);
    }

    public function homeworkSubmissions(): HasMany
    {
        return $this->hasMany(CourseHomeworkSubmission::class, 'lesson_id');
    }

    public function dpp(): HasOne
    {
        return $this->hasOne(DailyDpp::class, 'lesson_id');
    }

    public function videos(): HasMany
    {
        return $this->hasMany(CourseLessonVideo::class, 'lesson_id')->orderBy('order_index');
    }

    /**
     * Retrieve all videos for this lesson (relational videos or legacy video_url fallback).
     */
    public function allVideos(): \Illuminate\Support\Collection
    {
        if ($this->relationLoaded('videos') && $this->videos->isNotEmpty()) {
            return $this->videos;
        }

        $relVideos = $this->videos()->get();
        if ($relVideos->isNotEmpty()) {
            return $relVideos;
        }

        if (!empty($this->video_url)) {
            $synthesized = new CourseLessonVideo([
                'id' => 0,
                'lesson_id' => $this->id,
                'course_id' => $this->course_id,
                'title' => $this->title,
                'video_url' => $this->video_url,
                'duration_minutes' => $this->duration_minutes ?? 30,
                'order_index' => 1,
            ]);
            $synthesized->id = 0;
            return collect([$synthesized]);
        }

        return collect();
    }
}
