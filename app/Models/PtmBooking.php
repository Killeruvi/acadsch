<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PtmBooking extends Model
{
    use HasFactory;

    protected $table = 'ptm_bookings';

    protected $fillable = [
        'schedule_id',
        'parent_id',
        'student_id',
        'teacher_id',
        'slot_time',
        'status',
        'parent_notes',
        'teacher_feedback',
        'action_items',
    ];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(PtmSchedule::class, 'schedule_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function scopeBooked($query)
    {
        return $query->where('status', 'booked');
    }
}
