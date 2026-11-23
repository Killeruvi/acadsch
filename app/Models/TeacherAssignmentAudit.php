<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherAssignmentAudit extends Model
{
    use HasFactory;

    protected $fillable = [
        'admin_id',
        'action',
        'teacher_id',
        'class_id',
        'subject_id',
        'period_timetable_id',
        'teacher_name',
        'class_name',
        'subject_name',
        'description',
        'old_values',
        'new_values',
        'ip_address',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function academicClass(): BelongsTo
    {
        return $this->belongsTo(AcademicClass::class, 'class_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(AcademicSubject::class, 'subject_id');
    }

    public function timetableSlot(): BelongsTo
    {
        return $this->belongsTo(SchoolPeriodTimetable::class, 'period_timetable_id');
    }
}
