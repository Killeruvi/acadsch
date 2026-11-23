<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseAssessmentSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'assessment_id',
        'student_id',
        'score',
        'percentage',
        'grade_letter',
        'feedback',
        'speaking_audio_url',
        'answers',
        'status',
        'teacher_id',
        'evaluated_at',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
            'percentage' => 'decimal:2',
            'answers' => 'array',
            'evaluated_at' => 'datetime',
        ];
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(CourseAssessment::class, 'assessment_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }
}
