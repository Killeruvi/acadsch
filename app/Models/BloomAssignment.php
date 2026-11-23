<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class BloomAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'lesson_id',
        'student_id',
        'roll_number',
        'bloom_level',
        'level_name',
        'activity_prompt',
        'due_date',
        'rubric_guide',
        'submission_content',
        'submission_attachment_path',
        'submission_attachment_name',
        'submission_attachment_type',
        'submission_attachment_size',
        'teacher_attachment_path',
        'teacher_attachment_name',
        'teacher_attachment_type',
        'teacher_attachment_size',
        'submitted_at',
        'status',
        'teacher_feedback',
        'score',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'bloom_level' => 'integer',
            'roll_number' => 'integer',
            'due_date' => 'date',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'score' => 'decimal:2',
            'submission_attachment_size' => 'integer',
            'teacher_attachment_size' => 'integer',
        ];
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(DailyLesson::class, 'lesson_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function getMetadataAttribute(): array
    {
        return TopicBloomActivity::getLevelMetadata($this->bloom_level);
    }

    /**
     * Determine if student uploaded an attachment (PDF, PPT, DOC, etc.).
     */
    public function hasSubmissionAttachment(): bool
    {
        return !empty($this->submission_attachment_path);
    }

    /**
     * Determine if teacher attached reference guidelines or presentation slides.
     */
    public function hasTeacherAttachment(): bool
    {
        return !empty($this->teacher_attachment_path);
    }

    /**
     * Get URL for student attachment.
     */
    public function getSubmissionAttachmentUrlAttribute(): ?string
    {
        if (!$this->hasSubmissionAttachment()) {
            return null;
        }

        return null; // Private attachments are served only through the authorized download route.
    }

    /**
     * Get URL for teacher attachment.
     */
    public function getTeacherAttachmentUrlAttribute(): ?string
    {
        if (!$this->hasTeacherAttachment()) {
            return null;
        }

        return null; // Private attachments are served only through the authorized download route.
    }

    /**
     * Return visual emoji icon based on file type.
     */
    public static function getFileIconByExtension(?string $ext): string
    {
        $ext = strtolower(trim((string)$ext));

        return match ($ext) {
            'pdf' => '📄',
            'ppt', 'pptx', 'pps', 'ppsx', 'odp' => '📊',
            'doc', 'docx', 'odt', 'rtf', 'txt' => '📝',
            'xls', 'xlsx', 'csv', 'ods' => '📈',
            'png', 'jpg', 'jpeg', 'webp', 'gif', 'svg' => '🖼️',
            'zip', 'rar', '7z', 'tar', 'gz' => '📦',
            default => '📎',
        };
    }

    public function getSubmissionAttachmentIconAttribute(): string
    {
        return self::getFileIconByExtension($this->submission_attachment_type);
    }

    public function getTeacherAttachmentIconAttribute(): string
    {
        return self::getFileIconByExtension($this->teacher_attachment_type);
    }

    /**
     * Format byte size into human readable string (KB / MB).
     */
    public static function formatBytes(?int $bytes): string
    {
        if (!$bytes || $bytes <= 0) {
            return '—';
        }

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1) . ' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 0) . ' KB';
        }

        return $bytes . ' B';
    }

    public function getFormattedSubmissionAttachmentSizeAttribute(): string
    {
        return self::formatBytes($this->submission_attachment_size);
    }

    public function getFormattedTeacherAttachmentSizeAttribute(): string
    {
        return self::formatBytes($this->teacher_attachment_size);
    }
}
