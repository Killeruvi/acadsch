<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HomeworkSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'homework_id',
        'student_id',
        'student_notes',
        'attachment_path',
        'attachment_name',
        'attachment_type',
        'attachment_size',
        'status',
        'teacher_feedback',
        'is_late',
        'resubmission_count',
        'checked_at',
    ];

    protected function casts(): array
    {
        return [
            'is_late' => 'boolean',
            'resubmission_count' => 'integer',
            'checked_at' => 'datetime',
            'attachment_size' => 'integer',
        ];
    }

    public function hasAttachment(): bool
    {
        return ! empty($this->attachment_path);
    }

    public function getAttachmentUrlAttribute(): ?string
    {
        return $this->attachment_path ? route('homework.download-attachment', ['id' => $this->id, 'type' => 'submission']) : null;
    }

    public function getAttachmentIconAttribute(): string
    {
        return HomeworkTask::getFileIconByExtension($this->attachment_type);
    }

    public function getFormattedAttachmentSizeAttribute(): string
    {
        return HomeworkTask::formatBytes($this->attachment_size);
    }

    public function homework(): BelongsTo
    {
        return $this->belongsTo(HomeworkTask::class, 'homework_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
