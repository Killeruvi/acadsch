<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Batch extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_id',
        'name',
        'code',
        'shift',
        'timing_slot',
        'start_time',
        'end_time',
        'academic_year',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function academicClass(): BelongsTo
    {
        return $this->belongsTo(AcademicClass::class, 'class_id');
    }

    public function students(): HasMany
    {
        return $this->hasMany(User::class, 'batch_id');
    }

    public function subjects(): HasManyThrough
    {
        return $this->hasManyThrough(
            AcademicSubject::class,
            AcademicClass::class,
            'id',          // Foreign key on academic_classes table (academic_classes.id)
            'class_id',    // Foreign key on academic_subjects table (academic_subjects.class_id)
            'class_id',    // Local key on batches table (batches.class_id)
            'id'           // Local key on academic_classes table (academic_classes.id)
        );
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class, 'batch_id')->orderBy('day_of_week')->orderBy('start_time');
    }
}
