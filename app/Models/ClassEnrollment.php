<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassEnrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_id',
        'student_id',
        'roll_number',
        'admission_number',
        'academic_year',
        'status',
        'ended_at',
        'ended_by',
        'transition_reason',
    ];

    protected function casts(): array
    {
        return [
            'roll_number' => 'integer',
            'ended_at' => 'datetime',
        ];
    }

    public function academicClass(): BelongsTo
    {
        return $this->belongsTo(AcademicClass::class, 'class_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * Generate the next available conflict-free roll number for a class.
     * Finds the lowest available positive integer not currently occupied.
     */
    public static function generateNextRollNumber(int $classId, ?int $excludeStudentId = null, ?string $academicYear = null): int
    {
        $academicYear ??= AcademicClass::query()->whereKey($classId)->value('academic_year');
        $query = static::where('class_id', $classId);
        if ($excludeStudentId) {
            $query->where('student_id', '!=', $excludeStudentId);
        }
        if ($academicYear !== null) {
            $query->where('academic_year', $academicYear);
        }

        $taken = $query->whereNotNull('roll_number')
            ->pluck('roll_number')
            ->map(fn($v) => (int)$v)
            ->all();

        $takenSet = array_flip($taken);
        $candidate = 1;
        while (isset($takenSet[$candidate])) {
            $candidate++;
        }

        return $candidate;
    }

    /**
     * Check if a specific roll number is available in a given class.
     */
    public static function isRollNumberAvailable(int $classId, int $rollNumber, ?int $excludeStudentId = null, ?string $academicYear = null): bool
    {
        $academicYear ??= AcademicClass::query()->whereKey($classId)->value('academic_year');
        $query = static::where('class_id', $classId)
            ->where('roll_number', $rollNumber);

        if ($excludeStudentId) {
            $query->where('student_id', '!=', $excludeStudentId);
        }
        if ($academicYear !== null) {
            $query->where('academic_year', $academicYear);
        }

        return !$query->exists();
    }
}
