<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'student_id',
        'status',
        'enrolled_at',
        'grade_letter',
        'final_score',
    ];

    protected function casts(): array
    {
        return [
            'enrolled_at' => 'datetime',
            'final_score' => 'decimal:2',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
