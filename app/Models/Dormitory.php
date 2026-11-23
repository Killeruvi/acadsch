<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dormitory extends Model
{
    use HasFactory;

    protected $fillable = [
        'dormitory_name',
        'type',
        'address',
        'intake_capacity',
        'description',
        'warden_name',
        'warden_phone',
        'is_active',
    ];

    public function bedAssignments(): HasMany
    {
        return $this->hasManyThrough(DormitoryBedAssignment::class, DormitoryRoom::class, 'dormitory_id', 'dormitory_room_id');
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(DormitoryRoom::class, 'dormitory_id');
    }
}
