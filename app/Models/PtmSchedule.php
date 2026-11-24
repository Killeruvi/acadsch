<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PtmSchedule extends Model
{
    use HasFactory;

    protected $table = 'ptm_schedules';

    protected $fillable = [
        'title',
        'academic_year',
        'class_id',
        'meeting_date',
        'start_time',
        'end_time',
        'slot_duration_minutes',
        'mode',
        'meeting_venue',
        'meeting_link',
        'status',
        'description',
        'created_by',
    ];

    protected $casts = [
        'meeting_date' => 'date',
        'slot_duration_minutes' => 'integer',
    ];

    public function academicClass(): BelongsTo
    {
        return $this->belongsTo(AcademicClass::class, 'class_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(PtmBooking::class, 'schedule_id');
    }

    /**
     * Generate list of time slots based on start_time, end_time, and slot_duration_minutes.
     *
     * @return array<string>
     */
    public function generateTimeSlots(): array
    {
        $slots = [];
        $duration = max(5, (int) $this->slot_duration_minutes);
        $dateStr = $this->meeting_date->toDateString();

        $start = Carbon::parse("{$dateStr} {$this->start_time}");
        $end = Carbon::parse("{$dateStr} {$this->end_time}");

        $current = $start->copy();
        while ($current->copy()->addMinutes($duration)->lte($end)) {
            $slots[] = $current->format('H:i');
            $current->addMinutes($duration);
        }

        return $slots;
    }

    /**
     * Scope for active/upcoming PTMs.
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['scheduled', 'in_progress']);
    }
}
