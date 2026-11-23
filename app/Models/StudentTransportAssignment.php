<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentTransportAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'route_id',
        'stop_id',
        'vehicle_id',
        'status',
        'start_date',
        'end_date',
        'monthly_fare',
        'waiver_percentage',
        'discount_amount',
        'opt_out_reason',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'monthly_fare' => 'decimal:2',
            'waiver_percentage' => 'decimal:2',
            'discount_amount' => 'decimal:2',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(TransportRoute::class, 'route_id');
    }

    public function stop(): BelongsTo
    {
        return $this->belongsTo(TransportRouteStop::class, 'stop_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(TransportVehicle::class, 'vehicle_id');
    }

    /**
     * Compute effective monthly fee after applying discount amount and waiver percentage.
     */
    public function getEffectiveFareAttribute(): float
    {
        $base = (float) $this->monthly_fare;
        if ($base <= 0 && $this->stop && $this->stop->fare !== null && (float) $this->stop->fare > 0) {
            $base = (float) $this->stop->fare;
        } elseif ($base <= 0 && $this->route) {
            $base = (float) $this->route->fare;
        }

        $waiver = $this->waiver_percentage > 0 ? ($base * ((float) $this->waiver_percentage / 100)) : 0.0;
        $discount = (float) $this->discount_amount;

        return max(0.0, round($base - $waiver - $discount, 2));
    }
}
