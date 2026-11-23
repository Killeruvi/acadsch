<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LiveQuiz extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_id',
        'subject_id',
        'creator_id',
        'title',
        'description',
        'access_code',
        'time_per_question',
        'xp_reward',
        'status',
    ];

    public function academicClass(): BelongsTo
    {
        return $this->belongsTo(AcademicClass::class, 'class_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(AcademicSubject::class, 'subject_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(LiveQuizQuestion::class, 'quiz_id');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class, 'quiz_id');
    }
}
