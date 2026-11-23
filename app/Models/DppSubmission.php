<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DppSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'dpp_id',
        'student_id',
        'answers',
        'score',
        'max_score',
        'percentage',
        'xp_earned',
        'time_taken_seconds',
        'is_passed',
        'rank',
        'percentile',
        'correct_count',
        'incorrect_count',
        'unattempted_count',
        'negative_deduction',
        'checked_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'answers' => 'array',
            'score' => 'float',
            'max_score' => 'integer',
            'percentage' => 'float',
            'xp_earned' => 'integer',
            'time_taken_seconds' => 'integer',
            'is_passed' => 'boolean',
            'rank' => 'integer',
            'percentile' => 'float',
            'correct_count' => 'integer',
            'incorrect_count' => 'integer',
            'unattempted_count' => 'integer',
            'negative_deduction' => 'float',
            'checked_at' => 'datetime',
        ];
    }

    public function dpp(): BelongsTo
    {
        return $this->belongsTo(DailyDpp::class, 'dpp_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
