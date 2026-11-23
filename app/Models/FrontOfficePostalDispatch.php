<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FrontOfficePostalDispatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'from_title',
        'reference_no',
        'address',
        'to_title',
        'date',
        'type',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }
}
