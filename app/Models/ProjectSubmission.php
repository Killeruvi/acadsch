<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'student_id',
        'team_name',
        'team_members',
        'solution_summary',
        'deliverable_url',
        'score',
        'teacher_feedback',
        'status',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'team_members' => 'array',
            'submitted_at' => 'datetime',
            'score' => 'integer',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(AcademicProject::class, 'project_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
