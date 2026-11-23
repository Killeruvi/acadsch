<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransportTrip extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_date',
        'trip_type',
        'route_id',
        'vehicle_id',
        'driver_id',
        'status',
        'started_at',
        'ended_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'trip_date' => 'date',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(TransportRoute::class, 'route_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(TransportVehicle::class, 'vehicle_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(TransportStaff::class, 'driver_id');
    }

    public function passengers(): HasMany
    {
        return $this->hasMany(TransportTripPassenger::class, 'trip_id');
    }
}
