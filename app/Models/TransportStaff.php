<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransportStaff extends Model
{
    use HasFactory;

    protected $table = 'transport_staff';

    protected $fillable = [
        'name',
        'role',
        'contact_number',
        'license_number',
        'license_expiry',
        'assigned_vehicle_id',
        'assigned_route_id',
        'status',
        'address',
    ];

    protected function casts(): array
    {
        return [
            'license_expiry' => 'date',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(TransportVehicle::class, 'assigned_vehicle_id');
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(TransportRoute::class, 'assigned_route_id');
    }

    public function trips(): HasMany
    {
        return $this->hasMany(TransportTrip::class, 'driver_id');
    }
}
