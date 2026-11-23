<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademyNotice extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'author_id',
        'approved_by',
        'approved_at',
        'circular_number',
        'title',
        'content',
        'priority',
        'target_audience',
        'is_pinned',
        'status',
        'published_at',
        'expires_at',
        'attachment_url',
        'requires_acknowledgement',
    ];

    protected function casts(): array
    {
        return [
            'is_pinned'                => 'boolean',
            'requires_acknowledgement' => 'boolean',
            'published_at'             => 'datetime',
            'approved_at'              => 'datetime',
            'expires_at'               => 'datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function readReceipts(): HasMany
    {
        return $this->hasMany(CircularReadReceipt::class, 'notice_id');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->where(function ($q) {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            });
    }

    public function scopeCirculars($query)
    {
        return $query->where('type', 'circular');
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isPendingApproval(): bool
    {
        return $this->status === 'pending_approval';
    }

    /**
     * Check if a given user has read this notice/circular.
     */
    public function isReadByUser(int $userId): bool
    {
        return $this->readReceipts()->where('user_id', $userId)->exists();
    }
}
