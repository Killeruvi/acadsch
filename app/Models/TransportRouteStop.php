<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransportRouteStop extends Model
{
    use HasFactory;

    protected $fillable = [
        'route_id',
        'stop_name',
        'stop_sequence',
        'pickup_time',
        'drop_time',
        'fare',
        'latitude',
        'longitude',
        'landmark',
    ];

    protected function casts(): array
    {
        return [
            'stop_sequence' => 'integer',
            'fare' => 'decimal:2',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(TransportRoute::class, 'route_id');
    }

    public function studentAssignments(): HasMany
    {
        return $this->hasMany(StudentTransportAssignment::class, 'stop_id');
    }
}
