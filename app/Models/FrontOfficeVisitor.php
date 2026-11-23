<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FrontOfficeVisitor extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'id_proof',
        'purpose',
        'person_to_meet',
        'in_time',
        'out_time',
        'date',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }
}
