<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\AcademicClass;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Course;
use App\Models\StudentLeaveRequest;
use App\Models\User;
use App\Services\AttendanceAccessControlService;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SchoolAttendanceController extends Controller
{
    /**
     * Attendance Dashboard (Role-Tailored: Parents see Ward logs, Students see personal summary, Teachers/Admins manage Roll Call).
     */
    public function index(Request $request): View|RedirectResponse
    {
        if ($request->routeIs('attendance.index') || ($request->is('attendance') && ! $request->is('school/attendance*'))) {
            return $this->lmsIndex($request);
        }

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
                        ->orWhereHas('subjects', fn ($sq) => $sq->where('teacher_id', $teacherId))
                        ->orWhereHas('timetables', fn ($tq) => $tq->where('teacher_id', $teacherId));
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
        if (! $user->isAdmin() && ! $user->isTeacher()) {
            abort(403, 'Unauthorized. Parents and students are not permitted to take attendance.');
        }

        $class = AcademicClass::with([
            'classTeacher',
            'students' => function ($q) {
                $q->orderBy('class_enrollments.roll_number');
            },
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

        if (! $validation['allowed']) {
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
            if (! isset($existingRecords[$st->id]) && isset($approvedLeaves[$st->id])) {
                $lv = $approvedLeaves[$st->id];
                $existingRecords[$st->id] = [
                    'status' => 'excused',
                    'remarks' => 'Approved Leave: '.ucfirst($lv->type),
                ];
            }
        }

        return view('school.attendance.take', compact('class', 'date', 'session', 'existingRecords'));
    }

    /**
     * Store or update class attendance records.
     * Strictly restricted to Teachers (during assigned period) and Administrators.
     */
    public function store(Request $request, ?int $classId = null, ?AttendanceAccessControlService $accessControl = null): RedirectResponse
    {
        $accessControl = $accessControl ?? app(AttendanceAccessControlService::class);
        if ($classId === null) {
            return $this->storeLegacy($request, $accessControl);
        }
        $user = Auth::user();
        if (! $user->isAdmin() && ! $user->isTeacher()) {
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
            $user->isAdmin() && ! empty($validated['period_number']) ? (int) $validated['period_number'] : null,
            'store'
        );

        if (! $validation['allowed']) {
            return redirect()->back()->withInput()->with('error', $validation['reason']);
        }

        $matchedPeriod = $validation['period'] ?? null;
        DB::transaction(function () use ($class, $validated, $matchedPeriod, $user, $request) {
            $session = AttendanceSession::query()
                ->where('class_id', $class->id)
                ->whereDate('session_date', $validated['session_date'])
                ->lockForUpdate()
                ->first();

            if (! $session) {
                $session = new AttendanceSession;
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
                if (! $record) {
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

        if (! $user->isAdmin() && ! $user->isTeacher()) {
            abort(403, 'Unauthorized. Viewing class-wide attendance registers is reserved for faculty and administrators.');
        }

        $class = AcademicClass::with([
            'classTeacher',
            'students' => function ($q) {
                $q->orderBy('class_enrollments.roll_number');
            },
        ])->findOrFail($classId);

        if ($user->isTeacher()) {
            $assigned = $class->class_teacher_id === $user->id
                || $class->subjects()->where('teacher_id', $user->id)->exists()
                || $class->timetables()->where('teacher_id', $user->id)->exists();
            if (! $assigned) {
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
                            ->filter(fn ($l) => $l->start_date <= $currentDayDate && $l->end_date >= $currentDayDate)
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
                        ->filter(fn ($l) => $l->start_date <= $currentDayDate && $l->end_date >= $currentDayDate)
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

    /**
     * LMS Index View for student/teacher attendance overview.
     */
    public function lmsIndex(Request $request): View|RedirectResponse
    {
        $user = Auth::user();
        $user->recordDailyActivity('visit');

        if ($user->isParent()) {
            return redirect()->route('school.attendance.index');
        }

        if ($user->isStudent()) {
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

        if ($user->isTeacher()) {
            $teacherId = $user->id;
            $allClasses = AcademicClass::with('students')
                ->where(function ($q) use ($teacherId) {
                    $q->where('class_teacher_id', $teacherId)
                        ->orWhereHas('subjects', fn ($sq) => $sq->where('teacher_id', $teacherId))
                        ->orWhereHas('timetables', fn ($tq) => $tq->where('teacher_id', $teacherId));
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
        $totalSessionsConducted = AttendanceSession::when($user->isTeacher(), fn ($q) => $q->where('teacher_id', $user->id))->count();

        $defaulters = [];
        if ($selectedClass) {
            $studentsInClass = $selectedClass->students;
            foreach ($studentsInClass as $st) {
                $stTotal = AttendanceRecord::where('student_id', $st->id)->whereNotIn('status', ['excused'])->whereHas('session', fn ($q) => $q->where('class_id', $selectedClass->id))->count();
                if ($stTotal >= config('attendance.minimum_sessions_for_alert')) {
                    $stPresent = AttendanceRecord::where('student_id', $st->id)->whereHas('session', fn ($q) => $q->where('class_id', $selectedClass->id))->whereIn('status', ['present', 'late'])->count();
                    $stHalfDays = AttendanceRecord::where('student_id', $st->id)->whereHas('session', fn ($q) => $q->where('class_id', $selectedClass->id))->where('status', 'half_day')->count();
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
     * Store legacy attendance session.
     */
    public function storeLegacy(Request $request, AttendanceAccessControlService $accessControl): RedirectResponse
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

        $validation = $accessControl->validateAttendanceAttempt(
            $request,
            $user,
            $class,
            $validated['session_date'],
            $user->isAdmin() && ! empty($validated['period_number']) ? (int) $validated['period_number'] : null,
            'store'
        );

        if (! $validation['allowed']) {
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
            if (! $session) {
                $session = new AttendanceSession;
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
                if (! $record) {
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

        $presentUsers = User::whereIn('id', $presentStudentIds)->get();
        foreach ($presentUsers as $pu) {
            $pu->recordDailyActivity(15);
        }

        if (! empty($absentStudentIds)) {
            NotificationService::notifyUsers(
                $absentStudentIds,
                'attendance',
                "Absence Notice: {$session->title}",
                "You were marked absent for {$class->name} on ".date('M d, Y', strtotime($validated['session_date'])),
                route('attendance.index'),
                '⚠️'
            );
        }

        return redirect()->route('attendance.index', ['class_id' => $class->id])
            ->with('success', "Attendance recorded for {$class->name} ({$session->session_date->format('M d, Y')}). ".count($presentStudentIds).' students marked present.');
    }

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
            if (! $assigned) {
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

    public function edit(int $id): View
    {
        $session = AttendanceSession::with([
            'academicClass.students',
            'teacher:id,name',
            'records.student',
        ])->findOrFail($id);

        $user = Auth::user();
        if (! $user || (! $user->isAdmin() && (! $user->isTeacher() || $session->teacher_id !== $user->id))) {
            abort(403, 'Unauthorized to edit attendance session recorded by another faculty member.');
        }
        if ($user->isTeacher()) {
            $class = $session->academicClass;
            $assigned = $class && ($class->class_teacher_id === $user->id
                || $class->subjects()->where('teacher_id', $user->id)->exists()
                || $class->timetables()->where('teacher_id', $user->id)->exists());
            if (! $assigned) {
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

    public function update(Request $request, int $id): RedirectResponse
    {
        $session = AttendanceSession::with(['records', 'academicClass.students'])->findOrFail($id);
        $user = Auth::user();

        if (! $user || (! $user->isAdmin() && (! $user->isTeacher() || $session->teacher_id !== $user->id))) {
            abort(403, 'Unauthorized to edit attendance session recorded by another faculty member.');
        }
        if ($user->isTeacher()) {
            $class = $session->academicClass;
            $assigned = $class && ($class->class_teacher_id === $user->id
                || $class->subjects()->where('teacher_id', $user->id)->exists()
                || $class->timetables()->where('teacher_id', $user->id)->exists());
            if (! $assigned) {
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
                        if (in_array($status, ['present', 'late']) && ! in_array($oldStatus, ['present', 'late'])) {
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

        if (! empty($newlyPresentStudentIds)) {
            $presentUsers = User::whereIn('id', $newlyPresentStudentIds)->get();
            foreach ($presentUsers as $pu) {
                $pu->recordDailyActivity(15);
            }
        }

        if (! empty($newlyAbsentStudentIds)) {
            NotificationService::notifyUsers(
                $newlyAbsentStudentIds,
                'attendance',
                "Absence Update: {$session->title}",
                "Your attendance was updated to absent for {$session->academicClass->name} on ".date('M d, Y', strtotime($validated['session_date'])),
                route('attendance.show', $session->id),
                '⚠️'
            );
        }

        return redirect()->route('attendance.show', $session->id)
            ->with('success', "Attendance session \"{$session->title}\" updated successfully. Roster history preserved.");
    }

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
