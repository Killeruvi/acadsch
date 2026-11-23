<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcademyEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'coordinator_id',
        'title',
        'category', // 'debate', 'talent_show', 'science_exhibition', 'hackathon', 'literary'
        'event_format', // 'individual', 'parliamentary_team', 'group'
        'description',
        'rules_and_rubric',
        'event_datetime',
        'venue',
        'status', // 'upcoming', 'live', 'completed'
        'registered_participants',
        'podium_winners',
    ];

    protected function casts(): array
    {
        return [
            'event_datetime' => 'datetime',
            'registered_participants' => 'array',
            'podium_winners' => 'array',
        ];
    }

    public function coordinator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coordinator_id');
    }

    public function isDebate(): bool
    {
        return $this->category === 'debate';
    }

    public function isTalentShow(): bool
    {
        return $this->category === 'talent_show';
    }
}
