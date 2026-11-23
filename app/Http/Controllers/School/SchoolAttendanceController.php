<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\AcademicClass;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\StudentLeaveRequest;
use App\Models\User;
use App\Services\AttendanceAccessControlService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class SchoolAttendanceController extends Controller
{
    /**
     * Attendance Dashboard (Role-Tailored: Parents see Ward logs, Students see personal summary, Teachers/Admins manage Roll Call).
     */
    public function index(Request $request): View
    {
        $user = Auth::user();

        // 1. Parent View: Ward Attendance & Presence Log (Zero roll-call buttons)
        if ($user->isParent()) {
            $children = User::childrenOf($user)
                ->with(['currentClass.classTeacher'])
                ->get();

            $selectedChildId = $request->input('child_id', session('parent_active_student_id', $children->first()?->id));
            if ($selectedChildId && $children->contains('id', (int) $selectedChildId)) {
                session(['parent_active_student_id' => (int) $selectedChildId]);
            } else {
                $selectedChildId = $children->first()?->id;
                session(['parent_active_student_id' => $selectedChildId]);
            }
            $activeChild = $children->firstWhere('id', $selectedChildId) ?? $children->first();

            $records = collect();
            $totalSessions = 0;
            $presentCount = 0;
            $absentCount = 0;
            $lateCount = 0;
            $attendancePercentage = 0.0;
            $excusedCount = 0;
            $halfDayCount = 0;

            if ($activeChild) {
                $records = AttendanceRecord::where('student_id', $activeChild->id)
                    ->with(['session.academicClass', 'session.teacher'])
                    ->latest('id')
                    ->paginate(15)
                    ->withQueryString();

                $totalSessions = AttendanceRecord::where('student_id', $activeChild->id)->count();
                $presentCount = AttendanceRecord::where('student_id', $activeChild->id)->where('status', 'present')->count();
                $absentCount = AttendanceRecord::where('student_id', $activeChild->id)->where('status', 'absent')->count();
                $lateCount = AttendanceRecord::where('student_id', $activeChild->id)->where('status', 'late')->count();
                $halfDayCount = AttendanceRecord::where('student_id', $activeChild->id)->where('status', 'half_day')->count();
                $excusedCount = AttendanceRecord::where('student_id', $activeChild->id)->where('status', 'excused')->count();
                $attendancePercentage = AttendanceRecord::calculatePercentage($presentCount, $totalSessions, $lateCount, $excusedCount, $halfDayCount);
            }

            return view('school.attendance.parent', compact(
                'children',
                'activeChild',
                'records',
                'totalSessions',
                'presentCount',
                'absentCount',
                'lateCount',
                'excusedCount',
                'halfDayCount',
                'attendancePercentage'
            ));
        }

        // 2. Student View: Personal Session History & Stat Cards (Zero roll-call buttons)
        if ($user->isStudent()) {
            $records = AttendanceRecord::where('student_id', $user->id)
                ->with(['session.academicClass', 'session.course', 'session.teacher'])
                ->latest('id')
                ->paginate(15)
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
                'excusedCount',
                'halfDayCount',
                'attendancePercentage'
            ));
        }

        // 3. Faculty & Administrator View: Class Roll Call & Register
        $request->validate(['date' => 'nullable|date_format:Y-m-d']);
        $date = $request->input('date', now()->toDateString());

        $classesQuery = AcademicClass::with(['classTeacher', 'students'])
            ->orderBy('grade_level')
            ->orderBy('section');

        if ($user->isTeacher()) {
            // Scope strictly to classes the teacher is assigned to (class teacher, subject teacher, or timetable period)
            $teacherId = $user->id;
            $classes = (clone $classesQuery)
                ->where(function ($q) use ($teacherId) {
                    $q->where('class_teacher_id', $teacherId)
                      ->orWhereHas('subjects', fn($sq) => $sq->where('teacher_id', $teacherId))
                      ->orWhereHas('timetables', fn($tq) => $tq->where('teacher_id', $teacherId));
                })
                ->get();
        } else {
            $classes = $classesQuery->get();
        }

        // Check roll-call status for each class for selected date
        $classStatus = [];
        $totalStudents = 0;
        $totalPresent = 0;
        $totalAbsent = 0;
        $totalLate = 0;

        foreach ($classes as $class) {
            $session = AttendanceSession::where('class_id', $class->id)
                ->whereDate('session_date', $date)
                ->with('records')
                ->first();

            $studentCount = $class->students->count();
            $totalStudents += $studentCount;

            if ($session) {
                $pCount = $session->records->whereIn('status', ['present'])->count();
                $aCount = $session->records->whereIn('status', ['absent'])->count();
                $lCount = $session->records->whereIn('status', ['late', 'half_day'])->count();

                $totalPresent += $pCount;
                $totalAbsent += $aCount;
                $totalLate += $lCount;

                $classStatus[$class->id] = [
                    'session_id' => $session->id,
                    'status' => 'marked',
                    'present' => $pCount,
                    'absent' => $aCount,
                    'late' => $lCount,
                    'rate' => $studentCount > 0 ? round(($pCount / $studentCount) * 100, 1) : 0,
                ];
            } else {
                $classStatus[$class->id] = [
                    'session_id' => null,
                    'status' => 'pending',
                    'present' => 0,
                    'absent' => 0,
                    'late' => 0,
                    'rate' => 0,
                ];
            }
        }

        $overallRate = $totalStudents > 0 ? round(($totalPresent / $totalStudents) * 100, 1) : 0;

        return view('school.attendance.index', compact(
            'classes',
            'date',
            'classStatus',
            'totalStudents',
            'totalPresent',
            'totalAbsent',
            'totalLate',
            'overallRate'
        ));
    }

    /**
     * Morning Roll Call screen for a specific class & section.
     * Strictly restricted to Teachers (during assigned period) and Administrators.
     */
    public function take(Request $request, int $classId, AttendanceAccessControlService $accessControl): View|RedirectResponse
    {
        $user = Auth::user();
        if (!$user->isAdmin() && !$user->isTeacher()) {
            abort(403, 'Unauthorized. Parents and students are not permitted to take attendance.');
        }

        $class = AcademicClass::with([
            'classTeacher',
            'students' => function ($q) {
                $q->orderBy('class_enrollments.roll_number');
            }
        ])->findOrFail($classId);

        $request->validate(['date' => 'nullable|date_format:Y-m-d|before_or_equal:today']);
        $date = $request->input('date', now()->toDateString());

        // Validate Access Control: Location, Admin rights, Teacher timetable period
        $validation = $accessControl->validateAttendanceAttempt(
            $request,
            $user,
            $class,
            $date,
            $user->isAdmin() && $request->filled('period_number') ? (int) $request->input('period_number') : null,
            'take'
        );

        if (!$validation['allowed']) {
            return redirect()->route('school.attendance.index', ['date' => $date])
                ->with('error', $validation['reason']);
        }

        // Check if session already exists
        $session = AttendanceSession::where('class_id', $class->id)
            ->whereDate('session_date', $date)
            ->with('records')
            ->first();

        $existingRecords = [];
        if ($session) {
            foreach ($session->records as $r) {
                $existingRecords[$r->student_id] = [
                    'status' => $r->status,
                    'remarks' => $r->remarks,
                ];
            }
        }

        // Pre-populate approved leaves as 'excused' for students without an existing session record
        $approvedLeaves = StudentLeaveRequest::where('status', 'approved')
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->whereIn('student_id', $class->students->pluck('id'))
            ->get()
            ->keyBy('student_id');

        foreach ($class->students as $st) {
            if (!isset($existingRecords[$st->id]) && isset($approvedLeaves[$st->id])) {
                $lv = $approvedLeaves[$st->id];
                $existingRecords[$st->id] = [
                    'status' => 'excused',
                    'remarks' => "Approved Leave: " . ucfirst($lv->type),
                ];
            }
        }

        return view('school.attendance.take', compact('class', 'date', 'session', 'existingRecords'));
    }

    /**
     * Store or update class attendance records.
     * Strictly restricted to Teachers (during assigned period) and Administrators.
     */
    public function store(Request $request, int $classId, AttendanceAccessControlService $accessControl): RedirectResponse
    {
        $user = Auth::user();
        if (!$user->isAdmin() && !$user->isTeacher()) {
            abort(403, 'Unauthorized. Parents and students are not permitted to record attendance.');
        }

        $class = AcademicClass::findOrFail($classId);

        $validated = $request->validate([
            'session_date' => 'required|date|before_or_equal:today',
            'title' => 'nullable|string|max:150',
            'notes' => 'nullable|string|max:500',
            'correction_reason' => 'nullable|string|max:500',
            'records' => 'required|array|min:1',
            'records.*.status' => 'required|in:present,absent,late,half_day,excused',
            'records.*.remarks' => 'nullable|string|max:150',
            'period_number' => 'nullable|integer|min:1',
        ]);

        $class->load('students');
        $rosterIds = $class->students->pluck('id')->map(fn ($id) => (int) $id)->all();
        $submittedIds = array_map('intval', array_keys($validated['records']));
        sort($rosterIds);
        sort($submittedIds);
        if ($rosterIds !== $submittedIds) {
            return redirect()->back()->withInput()->with('error', 'Attendance must include every currently enrolled student in this class and no other student.');
        }

        // Enforce Server-Side Access Control Rules: Timetable period & admin rights
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

        $matchedPeriod = $validation['period'] ?? null;
        DB::transaction(function () use ($class, $validated, $matchedPeriod, $user, $request) {
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

            $session->teacher_id = Auth::id();
            $session->title = $validated['title'] ?? "Daily Roll Call - {$class->display_name}";
            $session->notes = $validated['notes'] ?? null;
            $session->period_number = $matchedPeriod?->period_number ?? ($user->isAdmin() ? ($validated['period_number'] ?? null) : null);
            $session->session_type = 'daily_homeroom';
            $session->save();

            foreach ($validated['records'] as $studentId => $data) {
                $record = AttendanceRecord::query()->where('session_id', $session->id)
                    ->where('student_id', (int) $studentId)->lockForUpdate()->first();
                $beforeStatus = $record?->status;
                if (!$record) {
                    $record = new AttendanceRecord(['session_id' => $session->id, 'student_id' => (int) $studentId]);
                }
                $record->status = $data['status'];
                $record->remarks = $data['remarks'] ?? null;
                $record->save();
                if ($beforeStatus !== null && $beforeStatus !== $data['status']) {
                    DB::table('attendance_record_revisions')->insert([
                        'attendance_record_id' => $record->id,
                        'attendance_session_id' => $session->id,
                        'student_id' => (int) $studentId,
                        'actor_id' => Auth::id(),
                        'before_status' => $beforeStatus,
                        'after_status' => $data['status'],
                        'source' => 'school_web',
                        'reason' => $request->input('correction_reason'),
                        'changed_at' => now(), 'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
            }
        });

        return redirect()->route('school.attendance.index', ['date' => $validated['session_date']])
            ->with('success', "Attendance for {$class->display_name} on {$validated['session_date']} recorded successfully!");
    }

    /**
     * Monthly Class Attendance Register (Student vs Days 1..31 grid).
     * Strictly restricted to Teachers and Administrators; Parents redirected to ward attendance.
     */
    public function register(Request $request, int $classId): View|RedirectResponse
    {
        $user = Auth::user();

        // Parents clicking attendance register see their child's attendance records
        if ($user->isParent()) {
            return redirect()->route('school.attendance.index');
        }

        if (!$user->isAdmin() && !$user->isTeacher()) {
            abort(403, 'Unauthorized. Viewing class-wide attendance registers is reserved for faculty and administrators.');
        }

        $class = AcademicClass::with([
            'classTeacher',
            'students' => function ($q) {
                $q->orderBy('class_enrollments.roll_number');
            }
        ])->findOrFail($classId);

        if ($user->isTeacher()) {
            $assigned = $class->class_teacher_id === $user->id
                || $class->subjects()->where('teacher_id', $user->id)->exists()
                || $class->timetables()->where('teacher_id', $user->id)->exists();
            if (!$assigned) {
                abort(403, 'You are not assigned to this class.');
            }
        }

        $request->validate(['month' => 'nullable|date_format:Y-m']);
        $month = $request->input('month', now()->format('Y-m'));
        $carbonMonth = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $daysInMonth = $carbonMonth->daysInMonth;

        // Fetch all sessions for this class in this month
        $sessions = AttendanceSession::where('class_id', $class->id)
            ->whereYear('session_date', $carbonMonth->year)
            ->whereMonth('session_date', $carbonMonth->month)
            ->with('records')
            ->get()
            ->keyBy(function ($s) {
                return Carbon::parse($s->session_date)->day;
            });

        // Pre-fetch approved student leaves for this class in this month (Rule 28)
        $monthStart = $carbonMonth->copy()->startOfMonth()->toDateString();
        $monthEnd = $carbonMonth->copy()->endOfMonth()->toDateString();
        $approvedLeaves = StudentLeaveRequest::where('status', 'approved')
            ->whereIn('student_id', $class->students->pluck('id'))
            ->where(function ($q) use ($monthStart, $monthEnd) {
                $q->whereBetween('start_date', [$monthStart, $monthEnd])
                  ->orWhereBetween('end_date', [$monthStart, $monthEnd])
                  ->orWhere(function ($sub) use ($monthStart, $monthEnd) {
                      $sub->where('start_date', '<=', $monthStart)
                          ->where('end_date', '>=', $monthEnd);
                  });
            })
            ->get();

        // Build attendance matrix [student_id][day] = status
        $matrix = [];
        $studentTotals = [];
        $dayTotals = [];

        for ($d = 1; $d <= $daysInMonth; $d++) {
            $dayTotals[$d] = ['present' => 0, 'absent' => 0, 'excused' => 0, 'late' => 0, 'total' => 0];
        }

        foreach ($class->students as $student) {
            $studentTotals[$student->id] = ['present' => 0, 'absent' => 0, 'late' => 0, 'excused' => 0, 'total' => 0];
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $currentDayDate = $carbonMonth->copy()->day($day)->toDateString();

                if (isset($sessions[$day])) {
                    $rec = $sessions[$day]->records->firstWhere('student_id', $student->id);
                    $status = $rec ? $rec->status : 'absent';

                    // Check if approved leave applies to this day
                    if ($status === 'absent') {
                        $hasLeave = $approvedLeaves->where('student_id', $student->id)
                            ->filter(fn($l) => $l->start_date <= $currentDayDate && $l->end_date >= $currentDayDate)
                            ->isNotEmpty();
                        if ($hasLeave) {
                            $status = 'excused';
                        }
                    }

                    $matrix[$student->id][$day] = $status;
                    if ($status === 'present') {
                        $studentTotals[$student->id]['present']++;
                        $dayTotals[$day]['present']++;
                    } elseif ($status === 'absent') {
                        $studentTotals[$student->id]['absent']++;
                        $dayTotals[$day]['absent']++;
                    } elseif ($status === 'excused') {
                        $studentTotals[$student->id]['excused']++;
                        $dayTotals[$day]['excused']++;
                    } else {
                        $studentTotals[$student->id]['late']++;
                        $dayTotals[$day]['late']++;
                    }
                    $studentTotals[$student->id]['total']++;
                    $dayTotals[$day]['total']++;
                } else {
                    // Check if approved leave on a day without session
                    $hasLeave = $approvedLeaves->where('student_id', $student->id)
                        ->filter(fn($l) => $l->start_date <= $currentDayDate && $l->end_date >= $currentDayDate)
                        ->isNotEmpty();
                    $matrix[$student->id][$day] = $hasLeave ? 'excused' : null;
                }
            }
        }

        $allClassesQuery = AcademicClass::orderBy('grade_level')->orderBy('section');
        if ($user->isTeacher()) {
            $allClassesQuery->where(function ($q) use ($user) {
                $q->where('class_teacher_id', $user->id)
                    ->orWhereHas('subjects', fn ($sq) => $sq->where('teacher_id', $user->id))
                    ->orWhereHas('timetables', fn ($tq) => $tq->where('teacher_id', $user->id));
            });
        }
        $allClasses = $allClassesQuery->get();

        return view('school.attendance.register', compact(
            'class',
            'allClasses',
            'month',
            'carbonMonth',
            'daysInMonth',
            'sessions',
            'matrix',
            'studentTotals',
            'dayTotals'
        ));
    }
}
