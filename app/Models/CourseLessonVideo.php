<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseLessonVideo extends Model
{
    use HasFactory;

    protected $fillable = [
        'lesson_id',
        'course_id',
        'title',
        'video_url',
        'duration_minutes',
        'order_index',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'duration_minutes' => 'integer',
            'order_index' => 'integer',
        ];
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(CourseLesson::class, 'lesson_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    /**
     * Convert YouTube/Vimeo URLs to embeddable player links.
     */
    public function getEmbedUrlAttribute(): string
    {
        $url = $this->video_url;

        if (empty($url)) {
            return '';
        }

        if (str_contains($url, 'youtube.com/embed/')) {
            return $url;
        }

        if (preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/|v\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $url, $matches)) {
            return 'https://www.youtube-nocookie.com/embed/' . $matches[1] . '?rel=0&modestbranding=1';
        }

        if (preg_match('/vimeo\.com\/(?:video\/)?([0-9]+)/', $url, $matches)) {
            return 'https://player.vimeo.com/video/' . $matches[1];
        }

        return $url;
    }
}
