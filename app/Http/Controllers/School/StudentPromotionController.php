<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\AcademicClass;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\ClassEnrollment;
use App\Models\FeeInvoice;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StudentPromotionController extends Controller
{
    private function authorizeAdmin(Request $request): User
    {
        $actor = $request->user();
        abort_unless($actor && $actor->isAdmin(), 403, 'Administrative access is required.');
        return $actor;
    }

    public function index(Request $request): View
    {
        $this->authorizeAdmin($request);

        $classes = AcademicClass::query()->orderBy('grade_level')->orderBy('section')->orderBy('academic_year')->get();
        $sourceClassId = $request->integer('source_class_id') ?: null;
        $students = collect();
        $sourceClass = $sourceClassId ? AcademicClass::findOrFail($sourceClassId) : null;
        $minimumAttendance = (float) config('school.promotion.minimum_attendance_percentage', 75);

        if ($sourceClass) {
            $classSessionCount = AttendanceSession::query()
                ->where('class_id', $sourceClass->id)
                ->count();

            $students = ClassEnrollment::query()
                ->with('student')
                ->where('class_id', $sourceClass->id)
                ->where('academic_year', $sourceClass->academic_year)
                ->where('status', 'active')
                ->orderBy('roll_number')
                ->get()
                ->filter(fn (ClassEnrollment $enrollment) => $enrollment->student?->role === 'student')
                ->map(function (ClassEnrollment $enrollment) use ($classSessionCount, $minimumAttendance) {
                    $student = $enrollment->student;
                    $presentAndLate = AttendanceRecord::query()
                        ->where('student_id', $student->id)
                        ->whereHas('session', fn ($query) => $query->where('class_id', $enrollment->class_id))
                        ->whereIn('status', ['present', 'late'])
                        ->count();

                    $attendancePct = $classSessionCount > 0
                        ? round(min(100, ($presentAndLate / $classSessionCount) * 100), 1)
                        : null;

                    $dues = FeeInvoice::query()
                        ->where('student_id', $student->id)
                        ->whereColumn('total_due', '>', 'paid_amount')
                        ->sum(DB::raw('total_due - paid_amount'));

                    $student->enrollment_record_id = $enrollment->id;
                    $student->effective_roll_number = $enrollment->roll_number;
                    $student->total_pending_dues = round((float) $dues, 2);
                    $student->attendance_percentage = $attendancePct;
                    $student->is_fee_cleared = (float) $dues <= 0;
                    $student->is_attendance_eligible = $attendancePct !== null && $attendancePct >= $minimumAttendance;
                    $student->is_promotion_eligible = (bool) $student->is_approved;
                    return $student;
                });
        }

        $withdrawnStudents = User::query()
            ->where('role', 'student')
            ->whereExists(function ($query) {
                $query->selectRaw('1')->from('student_withdrawal_records as swr')
                    ->whereColumn('swr.student_id', 'users.id')
                    ->whereNull('swr.re_admitted_at');
            })
            ->orderBy('name')->get(['id', 'name', 'admission_number']);

        return view('school.promotion.index', compact(
            'classes', 'sourceClassId', 'sourceClass', 'students',
            'withdrawnStudents', 'minimumAttendance'
        ));
    }

    public function promote(Request $request): RedirectResponse
    {
        $actor = $this->authorizeAdmin($request);
        $validated = $request->validate([
            'source_class_id' => ['required', 'integer', 'exists:academic_classes,id'],
            'target_class_id' => ['required', 'integer', 'different:source_class_id', 'exists:academic_classes,id'],
            'student_ids' => ['required', 'array', 'min:1', 'max:200'],
            'student_ids.*' => ['required', 'integer', 'distinct', 'exists:users,id'],
        ]);

        $count = DB::transaction(function () use ($validated, $actor) {
            $source = AcademicClass::query()->lockForUpdate()->findOrFail($validated['source_class_id']);
            $target = AcademicClass::query()->lockForUpdate()->findOrFail($validated['target_class_id']);
            $targetYear = trim((string) $target->academic_year);
            if ($targetYear === '' || $targetYear === (string) $source->academic_year) {
                throw ValidationException::withMessages(['target_class_id' => 'Choose a target class configured for a different academic session.']);
            }
            if ((int) $target->grade_level !== (int) $source->grade_level + 1) {
                throw ValidationException::withMessages(['target_class_id' => 'Promotion must move students to the next grade. Use transfer for same-session class changes and the Grade 12 leaving-certificate process for graduates.']);
            }

            $studentIds = collect($validated['student_ids'])->map(fn ($id) => (int) $id)->unique()->values();
            $policyChecker = app(\App\Http\Controllers\School\AcademicProgressionController::class);
            foreach ($studentIds as $studentId) {
                $assessment = $policyChecker->assess($source, $studentId);
                if (!$assessment['eligible']) {
                    throw ValidationException::withMessages(['student_ids' => 'Promotion blocked by examination policy for student ID ' . $studentId . ': ' . $assessment['reason'] . '.']);
                }
            }
            $enrollments = ClassEnrollment::query()
                ->where('class_id', $source->id)
                ->where('academic_year', $source->academic_year)
                ->where('status', 'active')
                ->whereIn('student_id', $studentIds)
                ->lockForUpdate()->get()->keyBy('student_id');
            if ($enrollments->count() !== $studentIds->count()) {
                throw ValidationException::withMessages(['student_ids' => 'One or more selected students are not actively enrolled in the submitted source class and session.']);
            }

            $capacity = (int) ($target->capacity ?? 0);
            $existingCount = ClassEnrollment::query()->where('class_id', $target->id)
                ->where('academic_year', $targetYear)->where('status', 'active')->count();
            if ($capacity > 0 && ($existingCount + $studentIds->count()) > $capacity) {
                throw ValidationException::withMessages(['target_class_id' => 'The target class does not have enough remaining capacity.']);
            }

            $minimumAttendance = (float) config('school.promotion.minimum_attendance_percentage', 75);
            foreach ($studentIds as $studentId) {
                $student = User::query()->lockForUpdate()->where('role', 'student')->find($studentId);
                $oldEnrollment = $enrollments->get($studentId);
                if (!$student || !$student->is_approved || (int) $student->current_class_id !== (int) $source->id || !$oldEnrollment) {
                    throw ValidationException::withMessages(['student_ids' => 'A selected student is no longer active in the source class. Refresh and try again.']);
                }
                $otherActiveEnrollment = ClassEnrollment::query()->where('student_id', $studentId)
                    ->where('academic_year', $source->academic_year)->where('status', 'active')
                    ->where('id', '!=', $oldEnrollment->id)->exists();
                if ($otherActiveEnrollment) {
                    throw ValidationException::withMessages(['student_ids' => 'A selected student already has another active enrollment in this academic session.']);
                }

                $hasOutstandingFees = FeeInvoice::query()
                    ->where('student_id', $studentId)
                    ->whereColumn('total_due', '>', 'paid_amount')
                    ->exists();
                if ($hasOutstandingFees) {
                    throw ValidationException::withMessages([
                        'student_ids' => 'Student has an outstanding fee balance and cannot be promoted.',
                    ]);
                }

                $classSessionCount = AttendanceSession::query()->where('class_id', $source->id)->count();
                if ($classSessionCount === 0) {
                    throw ValidationException::withMessages([
                        'student_ids' => 'Source class has no attendance sessions recorded.',
                    ]);
                }
                $presentAndLate = AttendanceRecord::query()
                    ->where('student_id', $studentId)
                    ->whereHas('session', fn ($query) => $query->where('class_id', $source->id))
                    ->whereIn('status', ['present', 'late'])
                    ->count();
                $attendancePct = round(min(100, ($presentAndLate / $classSessionCount) * 100), 1);
                if ($attendancePct < $minimumAttendance) {
                    throw ValidationException::withMessages([
                        'student_ids' => 'Student attendance (' . $attendancePct . '%) does not meet the minimum requirement of ' . $minimumAttendance . '%.',
                    ]);
                }

                $roll = $this->nextRollNumber($target->id, $targetYear);
                $oldEnrollment->update([
                    'status' => 'promoted', 'ended_at' => now(), 'ended_by' => $actor->id,
                    'transition_reason' => 'Promoted to ' . $target->display_name . ' for academic session ' . $targetYear,
                ]);
                ClassEnrollment::create([
                    'class_id' => $target->id,
                    'student_id' => $studentId,
                    'roll_number' => $roll,
                    'admission_number' => $student->admission_number,
                    'academic_year' => $targetYear,
                    'status' => 'active',
                ]);
                $student->forceFill([
                    'current_class_id' => $target->id,
                    'roll_number' => $roll,
                    'academic_tier' => $target->display_name,
                ])->save();
                DB::table('school_promotion_audits')->insert([
                    'student_id' => $studentId, 'source_class_id' => $source->id, 'target_class_id' => $target->id,
                    'source_year' => $source->academic_year, 'target_year' => $targetYear, 'decision' => 'promoted',
                    'reason' => 'Met configured examination promotion policy', 'actor_id' => $actor->id,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }

            Log::notice('Students promoted to a new academic session.', [
                'actor_id' => $actor->id, 'source_class_id' => $source->id,
                'target_class_id' => $target->id, 'academic_year' => $targetYear,
                'student_ids' => $studentIds->all(),
                'review_note' => 'Attendance and fee balances are informational and do not automatically block academic promotion.',
            ]);
            return $studentIds->count();
        });

        return redirect()->route('school.promotion.index', ['source_class_id' => $validated['target_class_id']])
            ->with('success', "Successfully promoted {$count} student(s). Previous enrollment history has been preserved.");
    }

    public function transfer(Request $request): RedirectResponse
    {
        $actor = $this->authorizeAdmin($request);
        $validated = $request->validate([
            'source_class_id' => ['required', 'integer', 'exists:academic_classes,id'],
            'target_class_id' => ['required', 'integer', 'different:source_class_id', 'exists:academic_classes,id'],
            'student_ids' => ['required', 'array', 'min:1', 'max:200'],
            'student_ids.*' => ['required', 'integer', 'distinct', 'exists:users,id'],
        ]);

        DB::transaction(function () use ($validated, $actor) {
            $source = AcademicClass::query()->lockForUpdate()->findOrFail($validated['source_class_id']);
            $target = AcademicClass::query()->lockForUpdate()->findOrFail($validated['target_class_id']);
            if ((string) $source->academic_year !== (string) $target->academic_year) {
                throw ValidationException::withMessages(['target_class_id' => 'A class transfer must remain within the same academic session. Use promotion for a new session.']);
            }
            $ids = collect($validated['student_ids'])->map(fn ($id) => (int) $id)->unique()->values();
            $enrollments = ClassEnrollment::query()->where('class_id', $source->id)
                ->where('academic_year', $source->academic_year)->where('status', 'active')
                ->whereIn('student_id', $ids)->lockForUpdate()->get()->keyBy('student_id');
            if ($enrollments->count() !== $ids->count()) {
                throw ValidationException::withMessages(['student_ids' => 'One or more selected students are not actively enrolled in the source class.']);
            }
            $targetCount = ClassEnrollment::query()->where('class_id', $target->id)
                ->where('academic_year', $target->academic_year)->where('status', 'active')->count();
            if ((int) $target->capacity > 0 && $targetCount + $ids->count() > (int) $target->capacity) {
                throw ValidationException::withMessages(['target_class_id' => 'The target class does not have enough remaining capacity.']);
            }
            foreach ($ids as $id) {
                $student = User::query()->lockForUpdate()->where('role', 'student')->find($id);
                if (!$student || !$student->is_approved || (int) $student->current_class_id !== (int) $source->id) {
                    throw ValidationException::withMessages(['student_ids' => 'A selected student is no longer active in the source class.']);
                }
                $otherActiveEnrollment = ClassEnrollment::query()->where('student_id', $id)
                    ->where('academic_year', $source->academic_year)->where('status', 'active')
                    ->where('id', '!=', $enrollments->get($id)->id)->exists();
                if ($otherActiveEnrollment) {
                    throw ValidationException::withMessages(['student_ids' => 'A selected student already has another active enrollment in this academic session.']);
                }
                $roll = $this->nextRollNumber($target->id, (string) $target->academic_year);
                $enrollments->get($id)->update([
                    'status' => 'transferred', 'ended_at' => now(), 'ended_by' => $actor->id,
                    'transition_reason' => 'Transferred to ' . $target->display_name . ' within academic session ' . $target->academic_year,
                ]);
                ClassEnrollment::create([
                    'class_id' => $target->id, 'student_id' => $id, 'roll_number' => $roll,
                    'admission_number' => $student->admission_number,
                    'academic_year' => $target->academic_year, 'status' => 'active',
                ]);
                $student->forceFill(['current_class_id' => $target->id, 'roll_number' => $roll, 'academic_tier' => $target->display_name])->save();
            }
            Log::notice('Students transferred between classes in the same session.', [
                'actor_id' => $actor->id, 'source_class_id' => $source->id,
                'target_class_id' => $target->id, 'student_ids' => $ids->all(),
            ]);
        });

        return redirect()->route('school.promotion.index', ['source_class_id' => $validated['target_class_id']])
            ->with('success', 'Selected students transferred. Their previous class enrollments remain in history.');
    }

    public function readmit(Request $request): RedirectResponse
    {
        $actor = $this->authorizeAdmin($request);
        $validated = $request->validate([
            'student_id' => ['required', 'integer', 'exists:users,id'],
            'target_class_id' => ['required', 'integer', 'exists:academic_classes,id'],
        ]);

        DB::transaction(function () use ($validated, $actor) {
            $student = User::query()->where('role', 'student')->lockForUpdate()->findOrFail($validated['student_id']);
            $target = AcademicClass::query()->lockForUpdate()->findOrFail($validated['target_class_id']);
            $year = trim((string) $target->academic_year);
            if ($year === '') {
                throw ValidationException::withMessages(['target_class_id' => 'The target class has no configured academic session.']);
            }
            $withdrawal = DB::table('student_withdrawal_records')->where('student_id', $student->id)
                ->whereNull('re_admitted_at')->orderByDesc('effective_date')->lockForUpdate()->first();
            if (!$withdrawal || $student->is_approved || $student->current_class_id !== null) {
                throw ValidationException::withMessages(['student_id' => 'This student does not have an open withdrawal record or is already active.']);
            }
            if (ClassEnrollment::query()->where('student_id', $student->id)->where('status', 'active')->exists()) {
                throw ValidationException::withMessages(['student_id' => 'This withdrawn student still has an active enrollment record. Resolve the inconsistent enrollment before re-admission.']);
            }
            if ((int) $target->capacity > 0) {
                $activeCount = ClassEnrollment::query()->where('class_id', $target->id)->where('academic_year', $year)->where('status', 'active')->count();
                if ($activeCount >= (int) $target->capacity) {
                    throw ValidationException::withMessages(['target_class_id' => 'The selected class is at capacity.']);
                }
            }
            $roll = $this->nextRollNumber($target->id, $year);
            ClassEnrollment::create([
                'class_id' => $target->id, 'student_id' => $student->id, 'roll_number' => $roll,
                'admission_number' => $student->admission_number, 'academic_year' => $year, 'status' => 'active',
            ]);
            DB::table('student_withdrawal_records')->where('id', $withdrawal->id)->update([
                're_admitted_at' => now(), 're_admitted_by' => $actor->id, 'updated_at' => now(),
            ]);
            $student->forceFill([
                'is_approved' => true, 'approved_by' => $actor->id, 'approved_at' => now(),
                'current_class_id' => $target->id, 'roll_number' => $roll,
                'academic_tier' => $target->display_name, 'remember_token' => null,
            ])->save();
            Log::notice('Withdrawn student re-admitted.', [
                'actor_id' => $actor->id, 'student_id' => $student->id,
                'withdrawal_record_id' => $withdrawal->id, 'class_id' => $target->id, 'academic_year' => $year,
            ]);
        });

        return redirect()->route('school.students.index')->with('success', 'Student re-admitted and a new enrollment history record was created.');
    }

    private function nextRollNumber(int $classId, string $academicYear): int
    {
        $used = ClassEnrollment::query()->where('class_id', $classId)
            ->where('academic_year', $academicYear)->whereNotNull('roll_number')
            ->lockForUpdate()->pluck('roll_number')->map(fn ($value) => (int) $value)->all();
        $taken = array_flip($used);
        $roll = 1;
        while (isset($taken[$roll])) {
            $roll++;
        }
        return $roll;
    }
}
