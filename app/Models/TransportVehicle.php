<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransportVehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_number',
        'vehicle_type',
        'capacity',
        'vehicle_model',
        'year_made',
        'insurance_renewal_date',
        'fitness_certificate_expiry',
        'driver_id',
        'driver_name',
        'driver_license',
        'driver_phone',
        'route_id',
        'status',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'insurance_renewal_date' => 'date',
            'fitness_certificate_expiry' => 'date',
        ];
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(TransportRoute::class, 'route_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(TransportStaff::class, 'driver_id');
    }

    public function studentAssignments(): HasMany
    {
        return $this->hasMany(StudentTransportAssignment::class, 'vehicle_id');
    }

    public function activeStudentAssignments(): HasMany
    {
        return $this->hasMany(StudentTransportAssignment::class, 'vehicle_id')->where('status', 'active');
    }

    public function trips(): HasMany
    {
        return $this->hasMany(TransportTrip::class, 'vehicle_id');
    }
}
