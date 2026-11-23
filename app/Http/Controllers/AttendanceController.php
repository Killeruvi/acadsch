<?php

namespace App\Http\Controllers;

use App\Models\AcademicClass;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Course;
use App\Models\User;
use App\Services\AttendanceAccessControlService;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    /**
     * Main Attendance Dashboard (Role Tailored).
     */
    public function index(Request $request): View|RedirectResponse
    {
        $user = Auth::user();
        $user->recordDailyActivity('visit');

        if ($user->isParent()) {
            // Parents only view their child's attendance records, never the faculty roll-call interface
            return redirect()->route('school.attendance.index');
        }

        if ($user->isStudent()) {
            // Student View: Attendance summary & personal logs
            $records = AttendanceRecord::where('student_id', $user->id)
                ->with(['session.academicClass', 'session.course', 'session.teacher'])
                ->latest('created_at')
                ->paginate(12)
                ->withQueryString();

            $totalSessions = AttendanceRecord::where('student_id', $user->id)->count();
            $presentCount = AttendanceRecord::where('student_id', $user->id)->where('status', 'present')->count();
            $absentCount = AttendanceRecord::where('student_id', $user->id)->where('status', 'absent')->count();
            $excusedCount = AttendanceRecord::where('student_id', $user->id)->where('status', 'excused')->count();
            $lateCount = AttendanceRecord::where('student_id', $user->id)->where('status', 'late')->count();
            $halfDayCount = AttendanceRecord::where('student_id', $user->id)->where('status', 'half_day')->count();
            $attendancePercentage = AttendanceRecord::calculatePercentage($presentCount, $totalSessions, $lateCount, $excusedCount, $halfDayCount);

            return view('attendance.student', compact(
                'records',
                'totalSessions',
                'presentCount',
                'absentCount',
                'attendancePercentage'
            ));
        }

        // Teacher & Admin View: Attendance Management
        if ($user->isTeacher()) {
            $teacherId = $user->id;
            $allClasses = AcademicClass::with('students')
                ->where(function ($q) use ($teacherId) {
                    $q->where('class_teacher_id', $teacherId)
                      ->orWhereHas('subjects', fn($sq) => $sq->where('teacher_id', $teacherId))
                      ->orWhereHas('timetables', fn($tq) => $tq->where('teacher_id', $teacherId));
                })
                ->orderBy('grade_level')
                ->get();
        } else {
            $allClasses = AcademicClass::with('students')->orderBy('grade_level')->get();
        }
        $allCourses = Course::where('status', 'active')->orderBy('title')->get();

        $selectedClassId = $request->query('class_id', $allClasses->first()?->id);
        $selectedClass = $allClasses->firstWhere('id', $selectedClassId) ?? $allClasses->first();

        $query = AttendanceSession::with(['academicClass', 'course', 'teacher', 'records']);

        if ($user->isTeacher()) {
            $query->whereIn('class_id', AcademicClass::query()->where(function ($q) use ($user) {
                $q->where('class_teacher_id', $user->id)
                    ->orWhereHas('subjects', fn ($sq) => $sq->where('teacher_id', $user->id))
                    ->orWhereHas('timetables', fn ($tq) => $tq->where('teacher_id', $user->id));
            })->select('id'));
        }

        if ($selectedClassId) {
            $query->where('class_id', $selectedClassId);
        }

        $sessions = $query->latest('session_date')->paginate(10)->withQueryString();

        // Stats for teacher
        $totalSessionsConducted = AttendanceSession::when($user->isTeacher(), fn($q) => $q->where('teacher_id', $user->id))->count();

        // Attendance defaulters in selected class (< 75% attendance)
        $defaulters = [];
        if ($selectedClass) {
            $studentsInClass = $selectedClass->students;
            foreach ($studentsInClass as $st) {
                $stTotal = AttendanceRecord::where('student_id', $st->id)->whereNotIn('status', ['excused'])->whereHas('session', fn($q) => $q->where('class_id', $selectedClass->id))->count();
                if ($stTotal >= config('attendance.minimum_sessions_for_alert')) {
                    $stPresent = AttendanceRecord::where('student_id', $st->id)->whereHas('session', fn($q) => $q->where('class_id', $selectedClass->id))->whereIn('status', ['present', 'late'])->count();
                    $stHalfDays = AttendanceRecord::where('student_id', $st->id)->whereHas('session', fn($q) => $q->where('class_id', $selectedClass->id))->where('status', 'half_day')->count();
                    $stRate = round((($stPresent + ($stHalfDays * 0.5)) / $stTotal) * 100, 1);
                    if ($stRate < config('attendance.minimum_percentage')) {
                        $defaulters[] = [
                            'student' => $st,
                            'total' => $stTotal,
                            'present' => $stPresent,
                            'rate' => $stRate,
                        ];
                    }
                }
            }
        }

        return view('attendance.index', compact(
            'allClasses',
            'allCourses',
            'selectedClass',
            'selectedClassId',
            'sessions',
            'totalSessionsConducted',
            'defaulters'
        ));
    }

    /**
     * Record new attendance session and mark student entries.
     */
    public function store(Request $request, AttendanceAccessControlService $accessControl): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'class_id' => 'required|exists:academic_classes,id',
            'session_date' => 'required|date|before_or_equal:today',
            'title' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'attendance' => 'required|array|min:1',
            'attendance.*' => 'required|in:present,absent,late,half_day,excused',
            'correction_reason' => 'nullable|string|max:500',
            'period_number' => 'nullable|integer|min:1',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        $class = AcademicClass::with('students')->findOrFail($validated['class_id']);
        $rosterIds = $class->students->pluck('id')->map(fn ($id) => (int) $id)->all();
        $submittedIds = array_map('intval', array_keys($validated['attendance'] ?? []));
        sort($rosterIds);
        sort($submittedIds);
        if ($rosterIds !== $submittedIds || $submittedIds === []) {
            return redirect()->back()->withInput()->with('error', 'Attendance must include every currently enrolled student in this class and no other student.');
        }

        // Enforce Server-Side Access Control Rules: Premises location & assigned timetable period
        $validation = $accessControl->validateAttendanceAttempt(
            $request,
            $user,
            $class,
            $validated['session_date'],
            $user->isAdmin() && !empty($validated['period_number']) ? (int) $validated['period_number'] : null,
            'store'
        );

        if (!$validation['allowed']) {
            return redirect()->back()->withInput()->with('error', $validation['reason']);
        }

        $teacherId = Auth::id();
        $presentStudentIds = [];
        $absentStudentIds = [];
        $matchedPeriod = $validation['period'] ?? null;

        $session = DB::transaction(function () use ($class, $validated, $teacherId, $matchedPeriod, $user, &$presentStudentIds, &$absentStudentIds) {
            $session = AttendanceSession::query()
                ->where('class_id', $class->id)
                ->whereDate('session_date', $validated['session_date'])
                ->lockForUpdate()
                ->first();
            if (!$session) {
                $session = new AttendanceSession();
                $session->class_id = $class->id;
                $session->session_date = $validated['session_date'];
            }
            $session->teacher_id = $teacherId;
            $session->title = $validated['title'];
            $session->notes = $validated['notes'] ?? null;
            $session->period_number = $matchedPeriod?->period_number ?? ($user->isAdmin() ? ($validated['period_number'] ?? null) : null);
            $session->session_type = 'daily_homeroom';
            $session->save();

            foreach ($validated['attendance'] as $studentId => $status) {
                $record = AttendanceRecord::query()->where('session_id', $session->id)
                    ->where('student_id', (int) $studentId)->lockForUpdate()->first();
                $beforeStatus = $record?->status;
                if (!$record) {
                    $record = new AttendanceRecord(['session_id' => $session->id, 'student_id' => (int) $studentId]);
                }
                $record->status = $status;
                $record->save();
                if ($beforeStatus !== null && $beforeStatus !== $status) {
                    DB::table('attendance_record_revisions')->insert([
                        'attendance_record_id' => $record->id,
                        'attendance_session_id' => $session->id,
                        'student_id' => (int) $studentId,
                        'actor_id' => $user->id,
                        'before_status' => $beforeStatus,
                        'after_status' => $status,
                        'source' => 'legacy_web',
                        'reason' => $validated['correction_reason'] ?? null,
                        'changed_at' => now(), 'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
                if (in_array($status, ['present', 'late'], true)) {
                    $presentStudentIds[] = (int) $studentId;
                } elseif ($status === 'absent') {
                    $absentStudentIds[] = (int) $studentId;
                }
            }
            return $session;
        });

        // Award attendance XP (+15 XP) and record streak for present students
        $presentUsers = User::whereIn('id', $presentStudentIds)->get();
        foreach ($presentUsers as $pu) {
            $pu->recordDailyActivity(15);
        }

        // Send attendance notifications
        if (!empty($absentStudentIds)) {
            NotificationService::notifyUsers(
                $absentStudentIds,
                'attendance',
                "Absence Notice: {$session->title}",
                "You were marked absent for {$class->name} on " . date('M d, Y', strtotime($validated['session_date'])),
                route('attendance.index'),
                '⚠️'
            );
        }

        return redirect()->route('attendance.index', ['class_id' => $class->id])
            ->with('success', "Attendance recorded for {$class->name} ({$session->session_date->format('M d, Y')}). " . count($presentStudentIds) . " students marked present.");
    }

    /**
     * View detailed attendance session report.
     */
    public function show(int $id): View|RedirectResponse
    {
        $user = Auth::user();

        if ($user->isParent()) {
            return redirect()->route('school.attendance.index');
        }

        if ($user->isStudent()) {
            abort(403, 'Unauthorized. Full class attendance session records are reserved for faculty.');
        }

        $session = AttendanceSession::with([
            'academicClass.students',
            'teacher:id,name',
            'records.student',
        ])->findOrFail($id);

        if ($user->isTeacher()) {
            $class = $session->academicClass;
            $assigned = $class && ($class->class_teacher_id === $user->id
                || $class->subjects()->where('teacher_id', $user->id)->exists()
                || $class->timetables()->where('teacher_id', $user->id)->exists());
            if (!$assigned) {
                abort(403, 'You are not assigned to this class.');
            }
        }

        $presentCount = $session->records->whereIn('status', ['present', 'late'])->count();
        $halfDayCount = $session->records->where('status', 'half_day')->count();
        $absentCount = $session->records->where('status', 'absent')->count();
        $totalCount = $session->records->where('status', '!=', 'excused')->count();
        $rate = $totalCount > 0 ? round((($presentCount + ($halfDayCount * 0.5)) / $totalCount) * 100, 1) : 0;

        return view('attendance.show', compact('session', 'presentCount', 'absentCount', 'rate'));
    }

    /**
     * Show form to edit existing attendance session.
     */
    public function edit(int $id): View
    {
        $session = AttendanceSession::with([
            'academicClass.students',
            'teacher:id,name',
            'records.student',
        ])->findOrFail($id);

        $user = Auth::user();
        if (!$user || (!$user->isAdmin() && (!$user->isTeacher() || $session->teacher_id !== $user->id))) {
            abort(403, 'Unauthorized to edit attendance session recorded by another faculty member.');
        }
        if ($user->isTeacher()) {
            $class = $session->academicClass;
            $assigned = $class && ($class->class_teacher_id === $user->id
                || $class->subjects()->where('teacher_id', $user->id)->exists()
                || $class->timetables()->where('teacher_id', $user->id)->exists());
            if (!$assigned) {
                abort(403, 'Your current class assignment does not permit editing this attendance session.');
            }
        }

        $presentCount = $session->records->whereIn('status', ['present', 'late'])->count();
        $halfDayCount = $session->records->where('status', 'half_day')->count();
        $absentCount = $session->records->where('status', 'absent')->count();
        $totalCount = $session->records->where('status', '!=', 'excused')->count();
        $rate = $totalCount > 0 ? round((($presentCount + ($halfDayCount * 0.5)) / $totalCount) * 100, 1) : 0;

        return view('attendance.show', compact('session', 'presentCount', 'absentCount', 'rate'))->with('isEditing', true);
    }

    /**
     * Update and save attendance session even after submission/publication.
     * Retains accurate history and adjusts records safely within a database transaction.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $session = AttendanceSession::with(['records', 'academicClass.students'])->findOrFail($id);
        $user = Auth::user();

        if (!$user || (!$user->isAdmin() && (!$user->isTeacher() || $session->teacher_id !== $user->id))) {
            abort(403, 'Unauthorized to edit attendance session recorded by another faculty member.');
        }
        if ($user->isTeacher()) {
            $class = $session->academicClass;
            $assigned = $class && ($class->class_teacher_id === $user->id
                || $class->subjects()->where('teacher_id', $user->id)->exists()
                || $class->timetables()->where('teacher_id', $user->id)->exists());
            if (!$assigned) {
                abort(403, 'Your class assignment no longer permits attendance corrections for this class.');
            }
        }

        $validated = $request->validate([
            'session_date' => 'required|date|before_or_equal:today',
            'title' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'attendance' => 'required|array',
            'attendance.*' => 'required|in:present,absent,late,half_day,excused',
            'correction_reason' => 'nullable|string|max:500',
        ]);

        $rosterIds = $session->academicClass?->students?->pluck('id')->map(fn ($id) => (int) $id)->all() ?? [];
        $submittedIds = array_map('intval', array_keys($validated['attendance']));
        sort($rosterIds);
        sort($submittedIds);
        if ($rosterIds !== $submittedIds) {
            return redirect()->back()->withInput()->with('error', 'Attendance updates must include the complete current class roster only.');
        }

        if ($validated['session_date'] !== $session->session_date->toDateString()) {
            return redirect()->back()->withInput()->with('error', 'The original attendance date is immutable. Create an authorized correction record instead.');
        }

        $newlyPresentStudentIds = [];
        $newlyAbsentStudentIds = [];

        DB::transaction(function () use ($session, $validated, $user, &$newlyPresentStudentIds, &$newlyAbsentStudentIds) {
            $session->update([
                'session_date' => $validated['session_date'],
                'title' => $validated['title'],
                'notes' => $validated['notes'] ?? null,
            ]);

            $existingRecords = $session->records->keyBy('student_id');

            foreach ($validated['attendance'] as $studentId => $status) {
                $studentId = (int) $studentId;
                $existing = $existingRecords->get($studentId);

                if ($existing) {
                    $oldStatus = $existing->status;
                    if ($oldStatus !== $status) {
                        DB::table('attendance_record_revisions')->insert([
                            'attendance_record_id' => $existing->id,
                            'attendance_session_id' => $session->id,
                            'student_id' => $studentId,
                            'actor_id' => $user->id,
                            'before_status' => $oldStatus,
                            'after_status' => $status,
                            'source' => 'legacy_web',
                            'reason' => $validated['correction_reason'] ?? null,
                            'changed_at' => now(), 'created_at' => now(), 'updated_at' => now(),
                        ]);
                        $existing->update(['status' => $status]);
                        if (in_array($status, ['present', 'late']) && !in_array($oldStatus, ['present', 'late'])) {
                            $newlyPresentStudentIds[] = $studentId;
                        } elseif ($status === 'absent' && $oldStatus !== 'absent') {
                            $newlyAbsentStudentIds[] = $studentId;
                        }
                    }
                } else {
                    AttendanceRecord::create([
                        'session_id' => $session->id,
                        'student_id' => $studentId,
                        'status' => $status,
                    ]);
                    if (in_array($status, ['present', 'late'])) {
                        $newlyPresentStudentIds[] = $studentId;
                    } elseif ($status === 'absent') {
                        $newlyAbsentStudentIds[] = $studentId;
                    }
                }
            }
        });

        // Award XP to students who were newly marked present
        if (!empty($newlyPresentStudentIds)) {
            $presentUsers = User::whereIn('id', $newlyPresentStudentIds)->get();
            foreach ($presentUsers as $pu) {
                $pu->recordDailyActivity(15);
            }
        }

        // Notify students newly marked absent
        if (!empty($newlyAbsentStudentIds)) {
            NotificationService::notifyUsers(
                $newlyAbsentStudentIds,
                'attendance',
                "Absence Update: {$session->title}",
                "Your attendance was updated to absent for {$session->academicClass->name} on " . date('M d, Y', strtotime($validated['session_date'])),
                route('attendance.show', $session->id),
                '⚠️'
            );
        }

        return redirect()->route('attendance.show', $session->id)
            ->with('success', "Attendance session \"{$session->title}\" updated successfully. Roster history preserved.");
    }

    /**
     * Delete an attendance session and its child records.
     */
    public function destroy(Request $request, int $id): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user && $user->isAdmin(), 403, 'Only administrators can remove attendance sessions.');

        $session = AttendanceSession::findOrFail($id);
        $session->records()->delete();
        $session->delete();

        return redirect()->route('attendance.index')->with('success', 'Attendance session deleted successfully.');
    }
}
