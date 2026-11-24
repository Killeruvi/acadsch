<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoScholasticAssessment extends Model
{
    use HasFactory;

    protected $table = 'co_scholastic_assessments';

    protected $fillable = [
        'student_id',
        'class_id',
        'exam_term_id',
        'academic_year',
        'work_education',
        'art_education',
        'health_physical_education',
        'discipline',
        'height_cm',
        'weight_kg',
        'self_assessment_notes',
        'peer_feedback_notes',
        'teacher_holistic_remarks',
        'assessed_by',
    ];

    protected $casts = [
        'height_cm' => 'decimal:2',
        'weight_kg' => 'decimal:2',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function academicClass(): BelongsTo
    {
        return $this->belongsTo(AcademicClass::class, 'class_id');
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(SchoolExamTerm::class, 'exam_term_id');
    }

    public function assessor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessed_by');
    }

    /**
     * Compute Body Mass Index (BMI).
     */
    public function getBmiAttribute(): ?float
    {
        if ($this->height_cm > 0 && $this->weight_kg > 0) {
            $heightMeters = (float) $this->height_cm / 100;

            return round((float) $this->weight_kg / ($heightMeters * $heightMeters), 1);
        }

        return null;
    }
}
