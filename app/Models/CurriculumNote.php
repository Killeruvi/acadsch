<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CurriculumNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'topic_id',
        'title',
        'note_type',
        'content',
        'download_url',
        'reading_minutes',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'reading_minutes' => 'integer',
        ];
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(CurriculumTopic::class, 'topic_id');
    }
}
