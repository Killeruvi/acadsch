<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolStream extends Model
{
    protected $fillable = ['name', 'name_key', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function setNameAttribute($value): void
    {
        $normalized = trim((string) $value);
        $this->attributes['name'] = $normalized;
        $this->attributes['name_key'] = mb_strtoupper($normalized);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(SchoolSection::class, 'stream', 'name');
    }
}
