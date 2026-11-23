<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolExamSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_term_id',
        'class_id',
        'subject_id',
        'exam_date',
        'start_time',
        'end_time',
        'room_number',
        'max_marks',
        'pass_marks',
    ];

    protected $casts = [
        'exam_date' => 'date',
        'max_marks' => 'decimal:2',
        'pass_marks' => 'decimal:2',
    ];

    public function examTerm(): BelongsTo
    {
        return $this->belongsTo(SchoolExamTerm::class, 'exam_term_id');
    }

    public function academicClass(): BelongsTo
    {
        return $this->belongsTo(AcademicClass::class, 'class_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(AcademicSubject::class, 'subject_id');
    }
}
