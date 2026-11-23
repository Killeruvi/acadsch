<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicHoliday extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'holiday_date',
        'end_date',
        'type',
        'badge_icon',
        'description',
        'is_instructional_off',
        'academic_year',
    ];

    protected function casts(): array
    {
        return [
            'holiday_date' => 'date',
            'end_date' => 'date',
            'is_instructional_off' => 'boolean',
        ];
    }

    public function lessonPlans(): HasMany
    {
        return $this->hasMany(LessonPlan::class, 'holiday_id');
    }

    /**
     * Check if a given date string or Carbon instance falls on this holiday.
     */
    public function coversDate($date): bool
    {
        $d = Carbon::parse($date)->format('Y-m-d');
        $start = $this->holiday_date->format('Y-m-d');
        $end = $this->end_date ? $this->end_date->format('Y-m-d') : $start;

        return $d >= $start && $d <= $end;
    }

    /**
     * Helper to check if a given date is any holiday in the system.
     */
    public static function findHolidayForDate($date): ?self
    {
        $d = Carbon::parse($date)->format('Y-m-d');

        return static::query()
            ->where('is_instructional_off', true)
            ->whereDate('holiday_date', '<=', $d)
            ->where(function ($query) use ($d) {
                $query->whereDate('end_date', '>=', $d)
                    ->orWhere(function ($singleDay) use ($d) {
                        $singleDay->whereNull('end_date')->whereDate('holiday_date', $d);
                    });
            })
            ->orderBy('holiday_date')
            ->first();
    }
}
