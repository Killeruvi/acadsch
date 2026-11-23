<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolSection extends Model
{
    use HasFactory;

    protected $table = 'school_sections';

    protected $fillable = [
        'name',
        'name_key',
        'stream',
        'room_number',
        'capacity',
        'description',
        'is_active',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'is_active' => 'boolean',
    ];

    public function setNameAttribute($value): void
    {
        $normalized = mb_strtoupper(trim((string) $value));
        $this->attributes['name'] = $normalized;
        $this->attributes['name_key'] = $normalized;
    }

    /**
     * Associated academic classes using this reusable section.
     */
    public function classes(): HasMany
    {
        return $this->hasMany(AcademicClass::class, 'section_id');
    }

    /**
     * Active sections scope.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope by Stream.
     */
    public function scopeForStream($query, string $stream)
    {
        return $query->where('stream', $stream);
    }

    /**
     * Formatted display label (e.g. "Section A (General)").
     */
    public function getDisplayNameAttribute(): string
    {
        return "Section {$this->name} ({$this->stream})";
    }

    /**
     * Get grouped list of reusable sections indexed by stream.
     */
    public static function getGroupedByStream(): array
    {
        return static::where('is_active', true)
            ->orderBy('stream')
            ->orderBy('name')
            ->get()
            ->groupBy('stream')
            ->toArray();
    }
}
