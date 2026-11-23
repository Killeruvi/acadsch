<?php

namespace App\Http\Controllers;

use App\Models\AcademicClass;
use App\Models\AttendanceRecord;
use App\Models\Batch;
use App\Models\ClassEnrollment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentDirectoryController extends Controller
{
    protected function checkFacultyAccess(): void
    {
        $user = Auth::user();
        if (!$user || (!$user->isTeacher() && !$user->isAdmin())) {
            abort(403, 'Unauthorized. Student directory access is restricted to verified faculty and administrative officers.');
        }
    }

    /** Resolve all classes a teacher is currently assigned to. */
    protected function assignedClassIds(User $teacher): \Illuminate\Support\Collection
    {
        return AcademicClass::query()
            ->where(function ($query) use ($teacher) {
                $query->where('class_teacher_id', $teacher->id)
                    ->orWhereHas('subjects', fn ($subjectQuery) => $subjectQuery->where('teacher_id', $teacher->id))
                    ->orWhereHas('timetables', fn ($timetableQuery) => $timetableQuery->where('teacher_id', $teacher->id));
            })
            ->pluck('id');
    }

    /** Prevent faculty from reaching or editing records outside their assigned classes. */
    protected function authorizeStudentScope(User $student, User $actor): void
    {
        abort_unless($student->isStudent(), 404);
        if ($actor->isAdmin()) {
            return;
        }

        $classIds = $this->assignedClassIds($actor);
        $canAccess = ($student->current_class_id && $classIds->contains((int) $student->current_class_id))
            || $student->classEnrollments()->where('status', 'active')->whereIn('class_id', $classIds)->exists()
            || $student->batch()->whereIn('class_id', $classIds)->exists();

        abort_unless($canAccess, 403, 'You may only access students in your assigned classes.');
    }

    /**
     * Display paginated student roster with class-wise filtering, search, and full student details.
     */
    public function index(Request $request): View
    {
        $this->checkFacultyAccess();
        $allClasses = AcademicClass::orderBy('grade_level')->get();
        $allBatches = Batch::where('is_active', true)->orderBy('name')->get();

        $selectedClassId = $request->filled('class_id') ? (int) $request->query('class_id') : null;
        $selectedBatchId = $request->filled('batch_id') ? (int) $request->query('batch_id') : null;
        $searchTerm = trim($request->query('search', ''));
        $statusFilter = $request->query('status', 'all');
        $tierFilter = $request->query('tier', 'all');
        $perPage = in_array((int) $request->query('per_page', 15), [10, 15, 25, 50, 100], true)
            ? (int) $request->query('per_page', 15)
            : 15;
        $sortBy = $request->query('sort_by', 'name');
        $sortOrder = strtolower($request->query('order', 'asc')) === 'desc' ? 'desc' : 'asc';

        $query = User::where('role', 'student')
            ->with([
                'batch.academicClass',
                'classEnrollments.academicClass',
                'feeInvoices',
                'attendanceRecords',
            ]);

        // Class-wise filter
        if ($selectedClassId) {
            $query->where(function ($q) use ($selectedClassId) {
                $q->whereHas('classEnrollments', function ($sub) use ($selectedClassId) {
                    $sub->where('class_id', $selectedClassId);
                })->orWhereHas('batch', function ($sub) use ($selectedClassId) {
                    $sub->where('class_id', $selectedClassId);
                });
            });
        }

        // Batch filter
        if ($selectedBatchId) {
            $query->where('batch_id', $selectedBatchId);
        }

        // Search filter (Name, Email, Phone, Student ID, Guardian Name/Phone, Roll Number)
        if (!empty($searchTerm)) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                  ->orWhere('email', 'like', "%{$searchTerm}%")
                  ->orWhere('phone', 'like', "%{$searchTerm}%")
                  ->orWhere('student_id_number', 'like', "%{$searchTerm}%")
                  ->orWhere('guardian_name', 'like', "%{$searchTerm}%")
                  ->orWhere('guardian_phone', 'like', "%{$searchTerm}%")
                  ->orWhere('address', 'like', "%{$searchTerm}%")
                  ->orWhereHas('classEnrollments', function ($sub) use ($searchTerm) {
                      $sub->where('roll_number', 'like', "%{$searchTerm}%");
                  });
            });
        }

        // Status filter
        if ($statusFilter === 'active') {
            $query->where('is_approved', true);
        } elseif ($statusFilter === 'pending') {
            $query->where('is_approved', false);
        }

        // Academic Tier filter
        if ($tierFilter !== 'all' && !empty($tierFilter)) {
            $query->where('academic_tier', $tierFilter);
        }

        // Sorting
        if ($sortBy === 'roll_number') {
            $query->leftJoin('class_enrollments', 'users.id', '=', 'class_enrollments.student_id')
                  ->orderBy('class_enrollments.roll_number', $sortOrder)
                  ->select('users.*');
        } elseif (in_array($sortBy, ['name', 'email', 'xp_points', 'streak_count', 'created_at'], true)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('name', 'asc');
        }

        $students = $query->paginate($perPage)->withQueryString();

        // Calculate student counts per class for tabs
        $classCounts = [];
        $totalStudentsCount = User::where('role', 'student')->count();
        foreach ($allClasses as $c) {
            $classCounts[$c->id] = User::where('role', 'student')
                ->where(function ($q) use ($c) {
                    $q->whereHas('classEnrollments', fn ($sub) => $sub->where('class_id', $c->id))
                      ->orWhereHas('batch', fn ($sub) => $sub->where('class_id', $c->id));
                })->count();
        }

        return view('students.index', compact(
            'students',
            'allClasses',
            'allBatches',
            'selectedClassId',
            'selectedBatchId',
            'searchTerm',
            'statusFilter',
            'tierFilter',
            'perPage',
            'sortBy',
            'sortOrder',
            'classCounts',
            'totalStudentsCount'
        ));
    }

    /**
     * Export all matching students info to CSV for download.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $this->checkFacultyAccess();
        $selectedClassId = $request->filled('class_id') ? (int) $request->query('class_id') : null;
        $selectedBatchId = $request->filled('batch_id') ? (int) $request->query('batch_id') : null;
        $searchTerm = trim($request->query('search', ''));

        $query = User::where('role', 'student')
            ->with([
                'batch.academicClass',
                'classEnrollments.academicClass',
                'feeInvoices',
            ])
            ->orderBy('name', 'asc');

        if ($selectedClassId) {
            $query->where(function ($q) use ($selectedClassId) {
                $q->whereHas('classEnrollments', fn ($sub) => $sub->where('class_id', $selectedClassId))
                  ->orWhereHas('batch', fn ($sub) => $sub->where('class_id', $selectedClassId));
            });
        }

        if ($selectedBatchId) {
            $query->where('batch_id', $selectedBatchId);
        }

        if (!empty($searchTerm)) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                  ->orWhere('email', 'like', "%{$searchTerm}%")
                  ->orWhere('phone', 'like', "%{$searchTerm}%")
                  ->orWhere('student_id_number', 'like', "%{$searchTerm}%")
                  ->orWhere('guardian_name', 'like', "%{$searchTerm}%");
            });
        }

        $students = $query->get();
        $filename = 'primearc_students_roster_' . now()->format('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($students) {
            $handle = fopen('php://output', 'w');
            // Write UTF-8 BOM for Excel
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'Student ID',
                'Full Name',
                'Academic Class',
                'Section',
                'Roll Number',
                'Batch Name',
                'Batch Shift',
                'Batch Timings',
                'Phone Number',
                'Email Address',
                'Date of Birth',
                'Blood Group',
                'Guardian Name',
                'Guardian Phone',
                'Guardian Email',
                'Residential Address',
                'Academic Tier',
                'XP Points',
                'Streak Count',
                'Approval Status',
                'Card Valid Until',
            ]);

            foreach ($students as $s) {
                $enrollment = $s->classEnrollments->first();
                $academicClass = $enrollment?->academicClass ?? $s->batch?->academicClass;
                $rollNumber = $enrollment?->roll_number ?? 'N/A';

                fputcsv($handle, [
                    $s->student_id_number,
                    $s->name,
                    $academicClass?->name ?? 'Unassigned',
                    $academicClass?->section ?? 'A',
                    $rollNumber,
                    $s->batch?->name ?? 'Unassigned Batch',
                    $s->batch?->shift ?? 'N/A',
                    $s->batch?->timing_slot ?? 'N/A',
                    $s->phone ?? 'N/A',
                    $s->email,
                    $s->date_of_birth ? $s->date_of_birth->format('Y-m-d') : 'N/A',
                    $s->blood_group ?? 'N/A',
                    $s->guardian_name ?? 'N/A',
                    $s->guardian_phone ?? 'N/A',
                    $s->guardian_email ?? 'N/A',
                    $s->address ?? 'N/A',
                    $s->academic_tier ?? 'Junior Scholar',
                    $s->xp_points ?? 0,
                    $s->streak_count ?? 0,
                    $s->is_approved ? 'Active / Approved' : 'Pending Verification',
                    $s->card_valid_until ? $s->card_valid_until->format('Y-m-d') : 'N/A',
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Grab all matching student info as JSON for 1-click clipboard / data export.
     */
    public function grabJson(Request $request): JsonResponse
    {
        $this->checkFacultyAccess();
        $selectedClassId = $request->filled('class_id') ? (int) $request->query('class_id') : null;
        $selectedBatchId = $request->filled('batch_id') ? (int) $request->query('batch_id') : null;
        $searchTerm = trim($request->query('search', ''));

        $query = User::where('role', 'student')
            ->with([
                'batch.academicClass',
                'classEnrollments.academicClass',
            ])
            ->orderBy('name', 'asc');

        if ($selectedClassId) {
            $query->where(function ($q) use ($selectedClassId) {
                $q->whereHas('classEnrollments', fn ($sub) => $sub->where('class_id', $selectedClassId))
                  ->orWhereHas('batch', fn ($sub) => $sub->where('class_id', $selectedClassId));
            });
        }

        if ($selectedBatchId) {
            $query->where('batch_id', $selectedBatchId);
        }

        if (!empty($searchTerm)) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                  ->orWhere('email', 'like', "%{$searchTerm}%")
                  ->orWhere('phone', 'like', "%{$searchTerm}%")
                  ->orWhere('student_id_number', 'like', "%{$searchTerm}%");
            });
        }

        $students = $query->get()->map(function ($s) {
            $enrollment = $s->classEnrollments->first();
            $academicClass = $enrollment?->academicClass ?? $s->batch?->academicClass;

            return [
                'id' => $s->id,
                'student_id_number' => $s->student_id_number,
                'name' => $s->name,
                'class' => $academicClass?->name ?? 'Unassigned',
                'section' => $academicClass?->section ?? 'A',
                'roll_number' => $enrollment?->roll_number ?? null,
                'batch_name' => $s->batch?->name ?? null,
                'batch_shift' => $s->batch?->shift ?? null,
                'batch_timings' => $s->batch?->timing_slot ?? null,
                'phone' => $s->phone,
                'email' => $s->email,
                'guardian_name' => $s->guardian_name,
                'guardian_phone' => $s->guardian_phone,
                'guardian_email' => $s->guardian_email,
                'address' => $s->address,
                'blood_group' => $s->blood_group,
                'date_of_birth' => $s->date_of_birth?->format('Y-m-d'),
                'academic_tier' => $s->academic_tier,
                'xp_points' => $s->xp_points,
                'streak_count' => $s->streak_count,
                'is_approved' => (bool) $s->is_approved,
            ];
        });

        return response()->json([
            'status' => 'success',
            'count' => $students->count(),
            'students' => $students,
        ]);
    }

    /**
     * Clean, printable student roster view for physical attendance or record keeping.
     */
    public function rosterPrint(Request $request): View
    {
        $this->checkFacultyAccess();
        $selectedClassId = $request->filled('class_id') ? (int) $request->query('class_id') : null;
        $class = $selectedClassId ? AcademicClass::find($selectedClassId) : null;

        $query = User::where('role', 'student')
            ->with([
                'batch.academicClass',
                'classEnrollments.academicClass',
            ])
            ->orderBy('name', 'asc');

        if ($selectedClassId) {
            $query->where(function ($q) use ($selectedClassId) {
                $q->whereHas('classEnrollments', fn ($sub) => $sub->where('class_id', $selectedClassId))
                  ->orWhereHas('batch', fn ($sub) => $sub->where('class_id', $selectedClassId));
            });
        }

        $students = $query->get();

        return view('students.roster-print', compact('students', 'class', 'selectedClassId'));
    }

    /**
     * Deep analysis and profile dossier view for a single student.
     */
    public function show(User $student): View
    {
        $this->checkFacultyAccess();
        abort_unless($student->isStudent(), 404);

        $actor = Auth::user();
        $this->authorizeStudentScope($student, $actor);

        $student->load([
            'batch.academicClass',
            'classEnrollments.academicClass',
            'quizAttempts.quiz',
            'attendanceRecords.session.academicClass',
        ]);

        // Use only attendance records belonging to this student. Excused records
        // are excluded from the denominator and an empty history is not 100%.
        $attendanceStats = AttendanceRecord::query()
            ->where('student_id', $student->id)
            ->selectRaw("COUNT(CASE WHEN status <> 'excused' THEN 1 END) as total_sessions")
            ->selectRaw("COUNT(CASE WHEN status IN ('present', 'late', 'half_day') THEN 1 END) as present_count")
            ->selectRaw("COUNT(CASE WHEN status = 'absent' THEN 1 END) as absent_count")
            ->selectRaw("COUNT(CASE WHEN status = 'excused' THEN 1 END) as excused_count")
            ->first();

        $totalAttendanceSessions = (int) ($attendanceStats->total_sessions ?? 0);
        $presentAttendanceCount = (int) ($attendanceStats->present_count ?? 0);
        $absentAttendanceCount = (int) ($attendanceStats->absent_count ?? 0);
        $excusedAttendanceCount = (int) ($attendanceStats->excused_count ?? 0);
        $attendanceRate = $totalAttendanceSessions > 0
            ? round(($presentAttendanceCount / $totalAttendanceSessions) * 100, 1)
            : null;

        $quizAttempts = $student->quizAttempts()->with('quiz')->latest()->take(10)->get();
        $averageQuizScore = $quizAttempts->isNotEmpty() ? round($quizAttempts->avg('score'), 1) : null;

        $invoices = $student->feeInvoices()->with('payments')->latest()->limit(20)->get();
        $feeSummary = $student->feeInvoices()
            ->selectRaw('COALESCE(SUM(total_due), 0) as total_invoiced')
            ->selectRaw('COALESCE(SUM(paid_amount), 0) as total_paid')
            ->first();
        $totalInvoiced = (float) ($feeSummary->total_invoiced ?? 0);
        $totalPaid = (float) ($feeSummary->total_paid ?? 0);
        $totalOutstanding = max(0, $totalInvoiced - $totalPaid);

        $activeEnrollment = $student->classEnrollments()->where('status', 'active')->latest()->first();
        $lastEnrollment = $student->classEnrollments()->latest()->first();

        return view('students.show', compact(
            'student',
            'activeEnrollment',
            'lastEnrollment',
            'totalAttendanceSessions',
            'presentAttendanceCount',
            'absentAttendanceCount',
            'excusedAttendanceCount',
            'attendanceRate',
            'quizAttempts',
            'averageQuizScore',
            'invoices',
            'totalInvoiced',
            'totalPaid',
            'totalOutstanding',
        ));
    }

    /**
     * Form to edit student profile and contact information. Class, batch, and
     * admission identity are displayed read-only and changed only through their
     * dedicated administrative workflows.
     */
    public function edit(User $student): View
    {
        $this->checkFacultyAccess();
        $this->authorizeStudentScope($student, Auth::user());
        $student->load(['currentClass', 'batch']);

        return view('students.edit', compact('student'));
    }

    /** Update profile fields without implicitly transferring or re-identifying a student. */
    public function update(Request $request, User $student): RedirectResponse
    {
        $this->checkFacultyAccess();
        $actor = Auth::user();
        $this->authorizeStudentScope($student, $actor);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'class_id' => ['nullable', 'exists:academic_classes,id'],
            'roll_number' => ['nullable', 'string', 'max:50'],
            'guardian_name' => ['nullable', 'string', 'max:255'],
            'guardian_phone' => ['nullable', 'string', 'max:30'],
            'guardian_email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'blood_group' => ['nullable', \Illuminate\Validation\Rule::in(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])],
            'is_approved' => ['sometimes', 'boolean'],
        ]);

        // Login email changes require a separate verified account-security workflow.
        if ($actor->isTeacher()) {
            unset($validated['is_approved']);
        }

        if ($actor->isAdmin() && !empty($validated['class_id']) && (int)$validated['class_id'] !== (int)$student->current_class_id) {
            $newClassId = (int)$validated['class_id'];
            $newRoll = $validated['roll_number'] ?? $student->roll_number;

            \App\Models\ClassEnrollment::where('student_id', $student->id)
                ->where('status', 'active')
                ->update(['status' => 'transferred']);

            \App\Models\ClassEnrollment::create([
                'student_id' => $student->id,
                'class_id' => $newClassId,
                'roll_number' => $newRoll,
                'status' => 'active',
                'academic_year' => \App\Models\AcademySetting::get('academic_session', '2026-2027'),
            ]);

            $student->current_class_id = $newClassId;
            if ($newRoll) {
                $student->roll_number = $newRoll;
            }
        } elseif (!empty($validated['roll_number'])) {
            $student->roll_number = $validated['roll_number'];
            \App\Models\ClassEnrollment::where('student_id', $student->id)
                ->where('class_id', $student->current_class_id)
                ->where('status', 'active')
                ->update(['roll_number' => $validated['roll_number']]);
        }

        $student->fill([
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
            'guardian_name' => $validated['guardian_name'] ?? null,
            'guardian_phone' => $validated['guardian_phone'] ?? null,
            'guardian_email' => $validated['guardian_email'] ?? null,
            'address' => $validated['address'] ?? null,
            'blood_group' => $validated['blood_group'] ?? null,
        ]);

        if ($actor->isAdmin() && array_key_exists('is_approved', $validated)) {
            $student->is_approved = (bool) $validated['is_approved'];
        }

        $changedFields = array_keys($student->getDirty());
        $student->save();

        \Illuminate\Support\Facades\Log::info('Legacy student profile updated', [
            'student_id' => $student->id,
            'actor_id' => $actor->id,
            'actor_role' => $actor->role,
            'changed_fields' => $changedFields,
        ]);

        return redirect()->route('students.show', $student->id)
            ->with('success', "Student profile for {$student->name} updated. Login email, academic placement, and admission identity were not changed.");
    }

    /**
     * Delete student permanently with confirmation caution and password / captcha protection.
     */
    public function destroy(Request $request, User $student): RedirectResponse
    {
        // Keep the legacy endpoint safe: it delegates to the canonical withdrawal
        // workflow and can no longer hard-delete academic or financial history.
        return app(\App\Http\Controllers\School\SchoolStudentController::class)
            ->destroy($request, $student->id);
    }
}
