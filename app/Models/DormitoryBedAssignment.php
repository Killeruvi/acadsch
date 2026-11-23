<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DormitoryBedAssignment extends Model
{
    protected $fillable = [
        'dormitory_room_id', 'student_id', 'bed_number', 'start_date', 'end_date',
        'status', 'monthly_fee', 'checked_in_at', 'checked_out_at', 'created_by', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date', 'end_date' => 'date',
            'checked_in_at' => 'datetime', 'checked_out_at' => 'datetime',
            'monthly_fee' => 'decimal:2',
        ];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(DormitoryRoom::class, 'dormitory_room_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
