<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubjectMaster extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'stream',
        'category',
        'default_periods',
        'min_grade',
        'max_grade',
        'default_teacher_id',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'default_periods' => 'integer',
            'min_grade' => 'integer',
            'max_grade' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Scope to active subjects.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to stream (includes 'All' stream subjects).
     */
    public function scopeForStream(Builder $query, ?string $stream = null): Builder
    {
        if (empty($stream) || strtolower($stream) === 'all') {
            return $query->where('is_active', true);
        }

        return $query->where('is_active', true)
            ->where(function ($q) use ($stream) {
                $q->where('stream', $stream)
                  ->orWhere('stream', 'All');
            });
    }

    /**
     * Get all active subjects grouped or filtered for a specific class.
     * Prevents duplicate subjects by marking or excluding already mapped ones.
     */
    public static function getAvailableForClass(int|AcademicClass $class, bool $excludeExisting = false): Collection
    {
        $classModel = is_numeric($class) ? AcademicClass::find($class) : $class;
        $stream = $classModel?->stream;
        $grade = $classModel?->grade_level;
        if (!$classModel || !$stream || !$grade) {
            return new Collection();
        }

        $existingSubjectCodes = AcademicSubject::where('class_id', $classModel->id)
            ->pluck('code')->map(fn ($code) => strtoupper(trim((string) $code)))->toArray();
        $existingSubjectNames = AcademicSubject::where('class_id', $classModel->id)
            ->pluck('name')->map(fn ($name) => strtoupper(trim((string) $name)))->toArray();
        $existingMasterIds = AcademicSubject::where('class_id', $classModel->id)
            ->whereNotNull('subject_master_id')->pluck('subject_master_id')->map(fn ($id) => (int) $id)->toArray();

        $mastersQuery = static::forStream($stream)->where('min_grade', '<=', (int) $grade)->where('max_grade', '>=', (int) $grade);
        $masters = $mastersQuery->orderBy('category')->orderBy('name')->get();

        return $masters->map(function ($master) use ($grade, $existingSubjectCodes, $existingSubjectNames, $existingMasterIds) {
            $formattedCode = $master->generateCodeForGrade($grade);
            $isAssigned = in_array((int) $master->id, $existingMasterIds, true)
                || in_array(strtoupper($formattedCode), $existingSubjectCodes, true)
                || in_array(strtoupper($master->code), $existingSubjectCodes, true)
                || in_array(strtoupper($master->name), $existingSubjectNames, true);

            $master->grade_code = $formattedCode;
            $master->is_already_assigned = $isAssigned;
            return $master;
        })->when($excludeExisting, function ($collection) {
            return $collection->where('is_already_assigned', false)->values();
        });

    }

    /**
     * Helper to format standardized class-level code, e.g. MTH-10.
     */
    public function generateCodeForGrade(int|string|null $grade = null): string
    {
        $baseCode = strtoupper($this->code);
        if (!$grade) {
            return $baseCode;
        }

        $paddedGrade = is_numeric($grade) ? sprintf('%02d', (int) $grade) : $grade;
        if (str_contains($baseCode, '-')) {
            return $baseCode;
        }

        return "{$baseCode}-{$paddedGrade}";
    }
}
