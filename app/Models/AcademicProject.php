<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicProject extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_id',
        'subject_id',
        'teacher_id',
        'title',
        'type', // 'project', 'case_study'
        'group_mode', // 'individual', 'grouped'
        'max_team_size',
        'description',
        'problem_statement',
        'evaluation_rubric',
        'due_date',
        'max_marks',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'max_marks' => 'integer',
            'max_team_size' => 'integer',
        ];
    }

    public function isCaseStudy(): bool
    {
        return $this->type === 'case_study';
    }

    public function isGrouped(): bool
    {
        return $this->group_mode === 'grouped';
    }

    public function academicClass(): BelongsTo
    {
        return $this->belongsTo(AcademicClass::class, 'class_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(AcademicSubject::class, 'subject_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(ProjectSubmission::class, 'project_id');
    }
}
