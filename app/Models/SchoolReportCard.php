<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolReportCard extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_term_id',
        'class_id',
        'student_id',
        'total_max_marks',
        'total_marks_obtained',
        'percentage',
        'gpa',
        'overall_grade',
        'class_rank',
        'total_working_days',
        'days_present',
        'attendance_percentage',
        'result_status',
        'teacher_remarks',
        'principal_remarks',
        'is_published',
        'generated_date',
        'attendance_data_available',
        'gpa_configured',
    ];

    protected $casts = [
        'total_max_marks' => 'decimal:2',
        'total_marks_obtained' => 'decimal:2',
        'percentage' => 'decimal:2',
        'gpa' => 'decimal:2',
        'attendance_percentage' => 'decimal:2',
        'is_published' => 'boolean',
        'attendance_data_available' => 'boolean',
        'gpa_configured' => 'boolean',
        'generated_date' => 'date',
    ];

    public function examTerm(): BelongsTo
    {
        return $this->belongsTo(SchoolExamTerm::class, 'exam_term_id');
    }

    public function academicClass(): BelongsTo
    {
        return $this->belongsTo(AcademicClass::class, 'class_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * Get all exam marks for this student and exam term
     */
    public function getMarksAttribute()
    {
        return SchoolExamMark::where('exam_term_id', $this->exam_term_id)
            ->where('student_id', $this->student_id)
            ->with('subject')
            ->get();
    }
}
