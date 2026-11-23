<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolExamMark extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_term_id',
        'class_id',
        'subject_id',
        'student_id',
        'marks_obtained',
        'max_marks',
        'pass_marks',
        'letter_grade',
        'teacher_remarks',
    ];

    protected $casts = [
        'marks_obtained' => 'decimal:2',
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

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * Compute letter grade from marks and max marks
     */
    public static function calculateGrade(float $obtained, float $max): string
    {
        if ($max <= 0 || $obtained < 0 || $obtained > $max) {
            throw new \InvalidArgumentException('Marks must be between zero and the maximum marks.');
        }

        $percentage = ($obtained / $max) * 100;
        $grade = SchoolExamGradeScale::query()
            ->where('is_active', true)
            ->where('minimum_percentage', '<=', $percentage)
            ->orderByDesc('minimum_percentage')
            ->first();

        if (! $grade) {
            throw new \RuntimeException('No active grading scale covers this percentage. Configure the grading master data.');
        }

        return $grade->grade_code;
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(SchoolExamMarkRevision::class, 'exam_mark_id')->latest();
    }
}
