<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolFeeStructure extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_id',
        'fee_title',
        'fee_type',
        'amount',
        'due_frequency',
        'academic_year',
        'description',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function academicClass(): BelongsTo
    {
        return $this->belongsTo(AcademicClass::class, 'class_id');
    }
}
