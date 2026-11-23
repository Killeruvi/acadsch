<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransportTripPassenger extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_id',
        'student_id',
        'stop_id',
        'status',
        'confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'confirmed_at' => 'datetime',
        ];
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(TransportTrip::class, 'trip_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function stop(): BelongsTo
    {
        return $this->belongsTo(TransportRouteStop::class, 'stop_id');
    }
}
