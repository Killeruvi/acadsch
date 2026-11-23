<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'teacher_id',
        'title',
        'description',
        'due_date',
        'max_points',
        'target_type',
        'student_id',
        'attachment_path',
        'attachment_name',
        'attachment_type',
        'attachment_size',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'datetime',
            'max_points' => 'integer',
            'attachment_size' => 'integer',
        ];
    }

    public function hasAttachment(): bool
    {
        return ! empty($this->attachment_path);
    }

    public function getAttachmentUrlAttribute(): ?string
    {
        if (empty($this->attachment_path)) {
            return null;
        }
        if (str_starts_with($this->attachment_path, 'https://')) {
            return $this->attachment_path;
        }

        return route('assignments.attachment', $this->id);
    }

    public function getFormattedAttachmentSizeAttribute(): string
    {
        $bytes = (int) ($this->attachment_size ?? 0);
        if ($bytes <= 0) {
            return '';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1).' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 0).' KB';
        }

        return $bytes.' B';
    }

    public function getIsPdfAttachmentAttribute(): bool
    {
        return str_ends_with(strtolower($this->attachment_name ?? $this->attachment_path ?? ''), '.pdf')
            || str_contains(strtolower($this->attachment_type ?? ''), 'pdf');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class, 'assignment_id');
    }

    public function getAssignedToLabelAttribute(): string
    {
        if ($this->target_type === 'specific_student' && $this->student) {
            return '👤 '.$this->student->name;
        }
        if ($this->target_type === 'all_students') {
            return '🏫 All Scholars (Campus)';
        }

        return '👥 All Enrolled Scholars ('.($this->course->code ?? 'Course').')';
    }
}
