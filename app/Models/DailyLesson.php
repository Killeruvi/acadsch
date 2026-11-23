<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DailyLesson extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_id',
        'subject_id',
        'topic_id',
        'teacher_id',
        'subtopic',
        'lesson_date',
        'distribution_mode',
        'target_level',
        'lesson_notes',
    ];

    protected function casts(): array
    {
        return [
            'lesson_date' => 'date',
            'target_level' => 'integer',
        ];
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

    public function assignments(): HasMany
    {
        return $this->hasMany(BloomAssignment::class, 'lesson_id')->orderBy('roll_number');
    }
}
