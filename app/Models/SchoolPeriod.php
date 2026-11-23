<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SchoolPeriod extends Model
{
    protected $fillable = ['period_number', 'name', 'start_time', 'end_time', 'is_break', 'is_active', 'sort_order'];

    protected $casts = [
        'period_number' => 'integer',
        'is_break' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }
}
