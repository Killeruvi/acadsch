<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'quiz_id',
        'student_id',
        'score',
        'total_questions',
        'correct_answers',
        'xp_earned',
        'answers_payload',
    ];

    protected function casts(): array
    {
        return [
            'answers_payload' => 'array',
            'score' => 'integer',
            'total_questions' => 'integer',
            'correct_answers' => 'integer',
            'xp_earned' => 'integer',
        ];
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(LiveQuiz::class, 'quiz_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
