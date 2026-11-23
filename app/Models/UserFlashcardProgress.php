<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserFlashcardProgress extends Model
{
    use HasFactory;

    protected $table = 'user_flashcard_progress';

    protected $fillable = [
        'user_id',
        'flashcard_id',
        'mastery_status',
        'review_count',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function flashcard(): BelongsTo
    {
        return $this->belongsTo(ChapterFlashcard::class, 'flashcard_id');
    }
}
