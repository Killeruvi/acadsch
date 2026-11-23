<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransportRoute extends Model
{
    use HasFactory;

    protected $fillable = [
        'route_title',
        'fare',
        'start_point',
        'end_point',
        'distance_km',
        'morning_timing',
        'evening_timing',
        'description',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'fare' => 'decimal:2',
            'distance_km' => 'decimal:2',
        ];
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(TransportVehicle::class, 'route_id');
    }

    public function stops(): HasMany
    {
        return $this->hasMany(TransportRouteStop::class, 'route_id')->orderBy('stop_sequence');
    }

    public function staff(): HasMany
    {
        return $this->hasMany(TransportStaff::class, 'assigned_route_id');
    }

    public function studentAssignments(): HasMany
    {
        return $this->hasMany(StudentTransportAssignment::class, 'route_id');
    }

    public function activeStudentAssignments(): HasMany
    {
        return $this->hasMany(StudentTransportAssignment::class, 'route_id')->where('status', 'active');
    }

    public function trips(): HasMany
    {
        return $this->hasMany(TransportTrip::class, 'route_id');
    }
}
