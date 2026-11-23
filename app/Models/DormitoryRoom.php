<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DormitoryRoom extends Model
{
    use HasFactory;

    protected $fillable = [
        'dormitory_id',
        'room_number',
        'room_type',
        'number_of_beds',
        'cost_per_bed',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'number_of_beds' => 'integer',
            'cost_per_bed' => 'decimal:2',
        ];
    }

    public function bedAssignments(): HasMany
    {
        return $this->hasMany(DormitoryBedAssignment::class, 'dormitory_room_id');
    }

    public function dormitory(): BelongsTo
    {
        return $this->belongsTo(Dormitory::class, 'dormitory_id');
    }
}
