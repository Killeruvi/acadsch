<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChapterFlashcard extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_id',
        'subject_id',
        'topic_id',
        'chapter_name',
        'question',
        'answer',
        'hint',
        'difficulty',
    ];

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

    public function progress(): HasMany
    {
        return $this->hasMany(UserFlashcardProgress::class, 'flashcard_id');
    }
}
