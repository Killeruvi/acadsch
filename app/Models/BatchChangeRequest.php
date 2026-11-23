<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BatchChangeRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'current_batch_id',
        'requested_batch_id',
        'reason',
        'status',
        'reviewed_by',
        'admin_notes',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function currentBatch(): BelongsTo
    {
        return $this->belongsTo(Batch::class, 'current_batch_id');
    }

    public function requestedBatch(): BelongsTo
    {
        return $this->belongsTo(Batch::class, 'requested_batch_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
