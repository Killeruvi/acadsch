<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CurriculumVideo extends Model
{
    use HasFactory;

    protected $fillable = [
        'topic_id',
        'subject_id',
        'title',
        'video_url',
        'video_provider',
        'duration_seconds',
        'order_index',
        'description',
        'thumbnail_url',
        'lecture_notes_url',
        'is_free_preview',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'duration_seconds' => 'integer',
            'order_index' => 'integer',
            'is_free_preview' => 'boolean',
            'is_published' => 'boolean',
        ];
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(CurriculumTopic::class, 'topic_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(AcademicSubject::class, 'subject_id');
    }

    public function userProgress(): HasMany
    {
        return $this->hasMany(UserVideoProgress::class, 'video_id');
    }

    public function currentUserProgress(): HasOne
    {
        return $this->hasOne(UserVideoProgress::class, 'video_id')
            ->where('user_id', auth()->id());
    }

    public function isWatchedBy(?int $userId = null): bool
    {
        $uid = $userId ?? auth()->id();
        if (! $uid) {
            return false;
        }

        if ($this->relationLoaded('userProgress')) {
            return (bool) $this->userProgress->firstWhere('user_id', $uid)?->is_completed;
        }

        return $this->userProgress()->where('user_id', $uid)->where('is_completed', true)->exists();
    }

    /**
     * Formats duration in minutes / seconds.
     */
    public function getDurationFormattedAttribute(): string
    {
        $minutes = floor($this->duration_seconds / 60);
        $seconds = $this->duration_seconds % 60;

        if ($minutes >= 60) {
            $hours = floor($minutes / 60);
            $remainingMinutes = $minutes % 60;

            return "{$hours}h {$remainingMinutes}m";
        }

        return sprintf('%02d:%02d min', $minutes, $seconds);
    }

    /**
     * Extract embeddable YouTube URL.
     */
    public function getEmbedUrlAttribute(): string
    {
        $url = $this->video_url;

        // Check if already embed format
        if (str_contains($url, 'youtube.com/embed/')) {
            return $url;
        }

        // Standard YouTube URL: https://www.youtube.com/watch?v=VIDEO_ID
        if (preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/|v\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $url, $matches)) {
            return 'https://www.youtube-nocookie.com/embed/'.$matches[1].'?rel=0&modestbranding=1';
        }

        return $url;
    }
}
