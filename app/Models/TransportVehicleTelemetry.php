<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransportVehicleTelemetry extends Model
{
    use HasFactory;

    protected $table = 'transport_vehicle_telemetry';

    protected $fillable = [
        'vehicle_id',
        'latitude',
        'longitude',
        'speed_kmh',
        'heading_degrees',
        'ignition',
        'battery_percentage',
        'recorded_at',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'speed_kmh' => 'decimal:2',
        'ignition' => 'boolean',
        'recorded_at' => 'datetime',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(TransportVehicle::class, 'vehicle_id');
    }
}
