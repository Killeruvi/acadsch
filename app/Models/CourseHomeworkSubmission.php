<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseHomeworkSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'lesson_id',
        'student_id',
        'submission_text',
        'audio_url',
        'status',
        'grade',
        'feedback',
        'evaluated_by',
        'evaluated_at',
    ];

    protected function casts(): array
    {
        return [
            'evaluated_at' => 'datetime',
        ];
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(CourseLesson::class, 'lesson_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluated_by');
    }
}
