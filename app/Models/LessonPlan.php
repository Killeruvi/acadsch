<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_id',
        'subject_id',
        'topic_id',
        'teacher_id',
        'lesson_number',
        'title',
        'subtopics',
        'learning_objectives',
        'pedagogy',
        'activity_type',
        'planned_date',
        'period_slot',
        'bloom_focus',
        'resources_needed',
        'practical_lab',
        'homework_assignment',
        'status',
        'holiday_id',
        'holiday_note',
        'term',
        'order_index',
    ];

    protected function casts(): array
    {
        return [
            'planned_date' => 'date',
            'lesson_number' => 'integer',
            'order_index' => 'integer',
        ];
    }

    public function isCompetition(): bool
    {
        return $this->activity_type === 'competition';
    }

    public function isProject(): bool
    {
        return $this->activity_type === 'project';
    }

    public function isDebate(): bool
    {
        return $this->activity_type === 'debate';
    }

    public function isSymposium(): bool
    {
        return $this->activity_type === 'symposium';
    }

    public function isTest(): bool
    {
        return $this->activity_type === 'test';
    }

    public function isRevision(): bool
    {
        return $this->activity_type === 'revision';
    }

    public function isRegular(): bool
    {
        return empty($this->activity_type) || $this->activity_type === 'regular';
    }

    public function getActivityIconAttribute(): string
    {
        return match ($this->activity_type) {
            'competition' => '🏆',
            'project' => '🚀',
            'debate' => '🗣️',
            'symposium' => '🔬',
            'test' => '📝',
            'revision' => '🔄',
            default => '📖',
        };
    }

    public function getActivityBadgeClassAttribute(): string
    {
        return match ($this->activity_type) {
            'competition' => 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 border-amber-300',
            'project' => 'bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-300 border-purple-300',
            'debate' => 'bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300 border-sky-300',
            'symposium' => 'bg-teal-100 text-teal-800 dark:bg-teal-950 dark:text-teal-300 border-teal-300',
            'test' => 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300 border-rose-300',
            'revision' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border-emerald-300',
            default => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border-slate-200',
        };
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

    public function holiday(): BelongsTo
    {
        return $this->belongsTo(AcademicHoliday::class, 'holiday_id');
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isHolidayShifted(): bool
    {
        return $this->status === 'holiday_shift' || !empty($this->holiday_id);
    }
}
