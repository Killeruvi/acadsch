<?php

namespace App\Services;

use App\Models\AcademicClass;
use App\Models\AcademicSubject;
use App\Models\SubjectMaster;
use App\Models\User;
use Illuminate\Http\Request;

class SubjectCurriculumInheritanceService
{
    public function inheritForClass(AcademicClass $class, ?User $actor = null, ?Request $request = null): array
    {
        $activeSession = \App\Models\AcademySetting::get('academic_session');
        if (!$activeSession || ($class->academic_year !== $activeSession && $this->normalizeSession($class->academic_year) !== $this->normalizeSession($activeSession))) {
            return ['created' => 0, 'linked' => 0, 'existing' => 0, 'skipped' => ['Automatic curriculum inheritance is limited to the configured active academic session.']];
        }
        if (!$class->stream || !$class->grade_level) {
            return ['created' => 0, 'linked' => 0, 'existing' => 0, 'skipped' => ['Class has no configured stream or grade.']];
        }

        $masters = SubjectMaster::forStream($class->stream)
            ->where('min_grade', '<=', $class->grade_level)
            ->where('max_grade', '>=', $class->grade_level)
            ->orderBy('category')->orderBy('name')->get();

        $result = ['created' => 0, 'linked' => 0, 'existing' => 0, 'skipped' => []];
        foreach ($masters as $master) {
            $outcome = $this->inheritMasterToClass($master, $class, $actor, $request);
            if ($outcome['status'] === 'skipped') {
                if ($outcome['reason']) $result['skipped'][] = $outcome['reason'];
            } else {
                $result[$outcome['status']]++;
            }
        }

        return $result;
    }

    public function inheritMasterAcrossClasses(SubjectMaster $master, ?string $academicYear = null, ?User $actor = null, ?Request $request = null): array
    {
        if (!$master->is_active) {
            return ['created' => 0, 'linked' => 0, 'existing' => 0, 'skipped' => ['Inactive subject masters are not inherited into new class sections.']];
        }
        $academicYear = $academicYear ?: \App\Models\AcademySetting::get('academic_session');
        $activeSession = \App\Models\AcademySetting::get('academic_session');
        if (!$academicYear || !$activeSession) {
            return ['created' => 0, 'linked' => 0, 'existing' => 0, 'skipped' => ['No active academic session is configured.']];
        }
        if ($this->normalizeSession($academicYear) !== $this->normalizeSession($activeSession)) {
            return ['created' => 0, 'linked' => 0, 'existing' => 0, 'skipped' => ['Automatic curriculum inheritance is limited to the configured active academic session.']];
        }

        $classes = AcademicClass::whereIn('academic_year', array_unique(array_filter([$academicYear, $activeSession, $this->normalizeSession($academicYear)])))
            ->where('grade_level', '>=', $master->min_grade)
            ->where('grade_level', '<=', $master->max_grade)
            ->when($master->stream !== 'All', fn ($query) => $query->where('stream', $master->stream))
            ->orderBy('grade_level')->orderBy('section')->get();

        $result = ['created' => 0, 'linked' => 0, 'existing' => 0, 'skipped' => []];
        foreach ($classes as $class) {
            $outcome = $this->inheritMasterToClass($master, $class, $actor, $request);
            if ($outcome['status'] === 'skipped') {
                if ($outcome['reason']) $result['skipped'][] = $outcome['reason'];
            } else {
                $result[$outcome['status']]++;
            }
        }

        return $result;
    }

    private function inheritMasterToClass(SubjectMaster $master, AcademicClass $class, ?User $actor, ?Request $request): array
    {
        $code = $master->generateCodeForGrade((int) $class->grade_level);
        $normalizedCode = strtoupper(trim((string) $code));
        $existing = AcademicSubject::where('class_id', $class->id)
            ->where(function ($query) use ($master, $normalizedCode) {
                $query->where('subject_master_id', $master->id)
                    ->orWhere('code_key', $normalizedCode)
                    ->orWhereRaw('UPPER(code) = ?', [$normalizedCode])
                    ->orWhereRaw('UPPER(name) = ?', [strtoupper($master->name)]);
            })->first();

        if ($existing) {
            if ($existing->subject_master_id && (int) $existing->subject_master_id !== (int) $master->id) {
                return ['status' => 'skipped', 'reason' => "{$class->display_name}: {$existing->name} is already linked to a different subject master."];
            }
            if (strtoupper($existing->code_key ?? $existing->code) === $normalizedCode && strtoupper($existing->name) !== strtoupper($master->name)) {
                return ['status' => 'skipped', 'reason' => "{$class->display_name}: Subject code {$code} is already used by {$existing->name}."];
            }
            if (!$existing->weekly_periods && (!$master->default_periods || (int) $master->default_periods < 1)) {
                return ['status' => 'skipped', 'reason' => "{$class->display_name}: {$master->name} has no configured weekly period load."];
            }
            $oldValues = ['subject_master_id' => $existing->subject_master_id, 'teacher_id' => $existing->teacher_id, 'weekly_periods' => $existing->weekly_periods];
            $existing->subject_master_id = $master->id;
            $sessionTeacherId = $class->academic_year === \App\Models\AcademySetting::get('academic_session') ? $master->default_teacher_id : null;
            $existing->teacher_id = $existing->teacher_id ?? $sessionTeacherId;
            $existing->weekly_periods = $existing->weekly_periods ?: $master->default_periods;
            $changed = $oldValues['subject_master_id'] !== $existing->subject_master_id
                || $oldValues['teacher_id'] !== $existing->teacher_id
                || $oldValues['weekly_periods'] !== $existing->weekly_periods;
            if ($changed) {
                $existing->save();
                if ($actor) {
                    app(SchoolAcademicAuditService::class)->log($actor, 'linked_class_subject_master', "Linked {$existing->name} to its subject master in {$class->display_name}.", $oldValues, ['subject_master_id' => $existing->subject_master_id, 'teacher_id' => $existing->teacher_id, 'weekly_periods' => $existing->weekly_periods], $request, $class->id, $existing->id);
                }
                return ['status' => 'linked', 'reason' => null];
            }
            return ['status' => 'existing', 'reason' => null];
        }

        if (!$master->default_periods || (int) $master->default_periods < 1) {
            return ['status' => 'skipped', 'reason' => "{$class->display_name}: {$master->name} has no configured default weekly period load."];
        }

        $conflict = AcademicSubject::where('class_id', $class->id)
            ->where(function ($q) use ($normalizedCode) {
                $q->where('code_key', $normalizedCode)
                    ->orWhereRaw('UPPER(code) = ?', [$normalizedCode]);
            })->first();

        if ($conflict) {
            return ['status' => 'skipped', 'reason' => "{$class->display_name}: Subject code {$code} is already used by {$conflict->name}."];
        }

        $subject = AcademicSubject::create([
            'class_id' => $class->id,
            'subject_master_id' => $master->id,
            'name' => $master->name,
            'code' => $code,
            'teacher_id' => $class->academic_year === \App\Models\AcademySetting::get('academic_session') ? $master->default_teacher_id : null,
            'weekly_periods' => $master->default_periods,
        ]);

        if ($actor) {
            app(SchoolAcademicAuditService::class)->log($actor, 'inherited_class_subject', "Inherited {$master->name} into {$class->display_name} from the subject master.", null, ['subject_id' => $subject->id, 'subject_master_id' => $master->id, 'teacher_id' => $subject->teacher_id, 'weekly_periods' => $subject->weekly_periods], $request, $class->id, $subject->id);
        }

        return ['status' => 'created', 'reason' => null];
    }

    private function normalizeSession(?string $session): ?string
    {
        $session = trim((string) $session);
        if (preg_match('/^(\d{4})-(\d{2})$/', $session, $matches)) {
            return $matches[1] . '-' . ((int) $matches[1] + 1);
        }
        if (preg_match('/^\d{4}-\d{4}$/', $session)) {
            return $session;
        }
        return $session !== '' ? $session : null;
    }
}
