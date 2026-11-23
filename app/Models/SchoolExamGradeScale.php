<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolExamGradeScale extends Model
{
    protected $fillable = [
        'grade_code', 'minimum_percentage', 'label',
        'is_passing', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'minimum_percentage' => 'decimal:2',
        'is_passing' => 'boolean',
        'is_active' => 'boolean',
    ];
}
