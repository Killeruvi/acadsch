<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolExamMarkRevision extends Model
{
    protected $fillable = [
        'exam_mark_id', 'old_marks', 'new_marks', 'reason', 'changed_by',
    ];

    protected $casts = [
        'old_marks' => 'decimal:2',
        'new_marks' => 'decimal:2',
    ];

    public function examMark(): BelongsTo
    {
        return $this->belongsTo(SchoolExamMark::class, 'exam_mark_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
