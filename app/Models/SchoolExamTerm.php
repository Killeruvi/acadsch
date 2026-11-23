<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolExamTerm extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'academic_year',
        'start_date',
        'end_date',
        'status',
        'description',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function schedules(): HasMany
    {
        return $this->hasMany(SchoolExamSchedule::class, 'exam_term_id');
    }

    public function examSchedules(): HasMany
    {
        return $this->schedules();
    }

    public function marks(): HasMany
    {
        return $this->hasMany(SchoolExamMark::class, 'exam_term_id');
    }

    public function reportCards(): HasMany
    {
        return $this->hasMany(SchoolReportCard::class, 'exam_term_id');
    }
}
