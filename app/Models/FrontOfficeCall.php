<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FrontOfficeCall extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'date',
        'description',
        'next_follow_up_date',
        'call_duration',
        'call_type',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'next_follow_up_date' => 'date',
        ];
    }
}
