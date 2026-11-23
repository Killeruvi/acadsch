<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicClass;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\User;
use App\Services\AttendanceAccessControlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceApiController extends Controller
{
    /**
     * Get attendance summary and history.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->isStudent()) {
            $studentIds = [$user->id];
        } elseif ($user->isParent()) {
            $studentIds = User::childrenOf($user)->pluck('id')->all();
        } elseif ($user->isTeacher() || $user->isAdmin()) {
            $sessionQuery = AttendanceSession::query()->with(['teacher:id,name'])->withCount([
                'records as total_students',
                'records as present_students' => fn ($q) => $q->whereIn('status', ['present', 'late']),
            ]);

            if ($user->isTeacher()) {
                $sessionQuery->whereIn('class_id', AcademicClass::query()
                    ->where(function ($q) use ($user) {
                        $q->where('class_teacher_id', $user->id)
                            ->orWhereHas('subjects', fn ($sq) => $sq->where('teacher_id', $user->id))
                            ->orWhereHas('timetables', fn ($tq) => $tq->where('teacher_id', $user->id));
                    })->select('id'));
            }
            if ($request->filled('class_id')) {
                $sessionQuery->where('class_id', $request->integer('class_id'));
            }
            if ($request->filled('date')) {
                $request->validate(['date' => 'required|date_format:Y-m-d']);
                $sessionQuery->whereDate('session_date', $request->query('date'));
            }

            return response()->json($sessionQuery->latest('session_date')->paginate(20));
        } else {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $totalRecords = AttendanceRecord::whereIn('student_id', $studentIds)->count();
        $presentCount = AttendanceRecord::whereIn('student_id', $studentIds)->whereIn('status', ['present', 'late'])->count();
        $halfDayCount = AttendanceRecord::whereIn('student_id', $studentIds)->where('status', 'half_day')->count();
        $absentCount = AttendanceRecord::whereIn('student_id', $studentIds)->where('status', 'absent')->count();
        $excusedCount = AttendanceRecord::whereIn('student_id', $studentIds)->where('status', 'excused')->count();
        $eligibleCount = max(0, $totalRecords - $excusedCount);
        $records = AttendanceRecord::with('session.academicClass')
            ->whereIn('student_id', $studentIds)->latest()->paginate(20);

        return response()->json([
            'attendance_rate' => $eligibleCount > 0 ? round((($presentCount + ($halfDayCount * 0.5)) / $eligibleCount) * 100, 1) : null,
            'total_sessions' => $totalRecords,
            'present_count' => $presentCount,
            'absent_count' => $absentCount,
            'excused_count' => $excusedCount,
            'half_day_count' => $halfDayCount,
            'history' => $records,
        ]);
    }

    /**
     * Record or update session attendance (Faculty / Admin).
     */
    public function store(Request $request, AttendanceAccessControlService $accessControl): JsonResponse
    {
        $user = $request->user();
        if (! $user->isTeacher() && ! $user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized to record attendance.'], 403);
        }

        $validated = $request->validate([
            'class_id' => 'required|exists:academic_classes,id',
            'session_date' => 'required|date|before_or_equal:today',
            'title' => 'required|string|max:255',
            'notes' => 'nullable|string|max:500',
            'correction_reason' => 'nullable|string|max:500',
            'records' => 'required|array',
            'records.*.student_id' => 'required|exists:users,id',
            'records.*.status' => 'required|in:present,absent,late,half_day,excused',
            'records.*.remarks' => 'nullable|string|max:150',
            'period_number' => 'nullable|integer|min:1',
        ]);

        $class = AcademicClass::with('students')->findOrFail($validated['class_id']);
        if ($user->isTeacher()) {
            $assigned = (int) $class->class_teacher_id === (int) $user->id
                || $class->subjects()->where('teacher_id', $user->id)->exists()
                || $class->timetables()->where('teacher_id', $user->id)->exists();
            abort_unless($assigned, 403, 'You are not assigned to this class.');
        }
        $rosterIds = $class->students->pluck('id')->map(fn ($id) => (int) $id)->all();
        $submittedIds = collect($validated['records'])->pluck('student_id')->map(fn ($id) => (int) $id)->all();
        if (count($submittedIds) !== count(array_unique($submittedIds))) {
            return response()->json(['message' => 'Duplicate student records are not allowed.'], 422);
        }
        sort($rosterIds);
        sort($submittedIds);
        if ($rosterIds !== $submittedIds) {
            return response()->json(['message' => 'Attendance must include every currently enrolled student in this class and no other student.'], 422);
        }

        // Enforce Server-Side Access Control Rules: Premises location & assigned timetable period
        $validation = $accessControl->validateAttendanceAttempt(
            $request,
            $user,
            $class,
            $validated['session_date'],
            $user->isAdmin() && ! empty($validated['period_number']) ? (int) $validated['period_number'] : null,
            'api_store'
        );

        if (! $validation['allowed']) {
            return response()->json([
                'message' => $validation['reason'],
                'error' => $validation['reason'],
            ], 403);
        }

        $session = DB::transaction(function () use ($validated, $user, $validation) {
            $session = AttendanceSession::updateOrCreate(
                [
                    'class_id' => $validated['class_id'],
                    'session_date' => $validated['session_date'],
                ],
                [
                    'teacher_id' => $user->id,
                    'title' => $validated['title'],
                    'notes' => $validated['notes'] ?? null,
                    'period_number' => $validation['period']?->period_number ?? ($user->isAdmin() ? ($validated['period_number'] ?? null) : null),
                    'session_type' => 'daily_homeroom',
                ]
            );

            foreach ($validated['records'] as $recordData) {
                $record = AttendanceRecord::query()->where('session_id', $session->id)
                    ->where('student_id', $recordData['student_id'])->lockForUpdate()->first();
                $beforeStatus = $record?->status;
                if (! $record) {
                    $record = new AttendanceRecord(['session_id' => $session->id, 'student_id' => $recordData['student_id']]);
                }
                $record->status = $recordData['status'];
                $record->remarks = $recordData['remarks'] ?? null;
                $record->save();
                if ($beforeStatus !== null && $beforeStatus !== $recordData['status']) {
                    DB::table('attendance_record_revisions')->insert([
                        'attendance_record_id' => $record->id,
                        'attendance_session_id' => $session->id,
                        'student_id' => $recordData['student_id'],
                        'actor_id' => $user->id,
                        'before_status' => $beforeStatus,
                        'after_status' => $recordData['status'],
                        'source' => 'api',
                        'reason' => $validated['correction_reason'] ?? null,
                        'changed_at' => now(), 'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
            }

            return $session;
        });

        return response()->json([
            'message' => 'Attendance session recorded successfully.',
            'session' => $session->load('records'),
        ], 201);
    }
}
