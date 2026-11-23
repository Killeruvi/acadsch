<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HomeworkTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_id',
        'subject_id',
        'topic_id',
        'teacher_id',
        'title',
        'instructions',
        'assigned_date',
        'due_date',
        'attachment_path',
        'attachment_name',
        'attachment_type',
        'attachment_size',
    ];

    protected function casts(): array
    {
        return [
            'assigned_date' => 'date',
            'due_date' => 'date',
            'attachment_size' => 'integer',
        ];
    }

    public function hasAttachment(): bool
    {
        return ! empty($this->attachment_path);
    }

    public function getAttachmentUrlAttribute(): ?string
    {
        return $this->attachment_path ? route('homework.download-attachment', ['id' => $this->id, 'type' => 'task']) : null;
    }

    public function getAttachmentIconAttribute(): string
    {
        return self::getFileIconByExtension($this->attachment_type);
    }

    public function getFormattedAttachmentSizeAttribute(): string
    {
        return self::formatBytes($this->attachment_size);
    }

    public static function getFileIconByExtension(?string $ext): string
    {
        $ext = strtolower((string) $ext);

        return match ($ext) {
            'pdf' => '📄',
            'ppt', 'pptx', 'pps', 'ppsx' => '📊',
            'doc', 'docx', 'odt', 'rtf', 'txt' => '📝',
            'png', 'jpg', 'jpeg', 'webp', 'gif', 'svg' => '🖼️',
            'zip', 'rar', '7z', 'tar', 'gz' => '📦',
            default => '📎',
        };
    }

    public static function formatBytes(?int $bytes, int $precision = 1): string
    {
        if (! $bytes || $bytes <= 0) {
            return '0 B';
        }
        $units = ['B', 'KB', 'MB', 'GB'];
        $power = floor(log($bytes, 1024));
        $power = min($power, count($units) - 1);
        $val = $bytes / pow(1024, $power);

        return round($val, $precision).' '.$units[$power];
    }

    public function academicClass(): BelongsTo
    {
        return $this->belongsTo(AcademicClass::class, 'class_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(AcademicSubject::class, 'subject_id');
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(CurriculumTopic::class, 'topic_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(HomeworkSubmission::class, 'homework_id');
    }

    public function studentSubmission(int $studentId): ?HomeworkSubmission
    {
        return $this->submissions()->where('student_id', $studentId)->first();
    }
}
