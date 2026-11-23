<?php

namespace App\Http\Controllers;

use App\Models\AcademicClass;
use App\Models\AcademicHoliday;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\SchoolExamSchedule;
use App\Models\StudentLeaveRequest;
use App\Models\User;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class LeaveRequestController extends Controller
{
    /**
     * View Attendance Leave Applications Dashboard.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $user->recordDailyActivity('visit');

        if ($user->isParent()) {
            $children = User::childrenOf($user)
                ->with('currentClass')
                ->get();

            $childIds = $children->pluck('id')->toArray();

            $selectedChildId = $request->input('child_id', $request->input('student_id', session('parent_active_student_id', $children->first()?->id)));
            if ($selectedChildId && $children->contains('id', (int) $selectedChildId)) {
                session(['parent_active_student_id' => (int) $selectedChildId]);
            } else {
                $selectedChildId = $children->first()?->id;
                session(['parent_active_student_id' => $selectedChildId]);
            }
            $activeChild = $children->firstWhere('id', $selectedChildId) ?? $children->first();

            $query = StudentLeaveRequest::whereIn('student_id', $childIds)
                ->with(['student.currentClass', 'reviewer:id,name'])
                ->latest('created_at');

            if ($request->filled('status') && $request->query('status') !== 'all') {
                $request->validate(['status' => ['in:pending,approved,rejected,cancelled']]);
                $query->where('status', $request->query('status'));
            }

            $leaveRequests = $query->paginate(15)->withQueryString();
            $pendingCount = StudentLeaveRequest::whereIn('student_id', $childIds)->where('status', 'pending')->count();
            $approvedCount = StudentLeaveRequest::whereIn('student_id', $childIds)->where('status', 'approved')->count();

            return view('attendance.leaves', compact('leaveRequests', 'pendingCount', 'approvedCount', 'children', 'activeChild'));
        }

        if ($user->isStudent()) {
            $studentQuery = StudentLeaveRequest::where('student_id', $user->id)
                ->with('reviewer:id,name')->latest('created_at');
            if ($request->filled('status') && $request->query('status') !== 'all') {
                $request->validate(['status' => ['in:pending,approved,rejected,cancelled']]);
                $studentQuery->where('status', $request->query('status'));
            }
            $leaveRequests = $studentQuery->paginate(10)->withQueryString();

            $pendingCount = StudentLeaveRequest::where('student_id', $user->id)->where('status', 'pending')->count();
            $approvedCount = StudentLeaveRequest::where('student_id', $user->id)->where('status', 'approved')->count();

            return view('attendance.leaves', compact('leaveRequests', 'pendingCount', 'approvedCount'));
        }

        // Teacher / Admin: Review Student Leave Applications
        $query = StudentLeaveRequest::with(['student.currentClass', 'reviewer:id,name'])->latest('created_at');

        if ($user->isTeacher()) {
            $assignedClassIds = AcademicClass::where('class_teacher_id', $user->id)->pluck('id');
            $query->whereHas('student', fn ($sq) => $sq->whereIn('current_class_id', $assignedClassIds));
        }

        if ($request->filled('status') && $request->query('status') !== 'all') {
            $request->validate(['status' => ['in:pending,approved,rejected,cancelled']]);
            $query->where('status', $request->query('status'));
        }

        $leaveRequests = $query->paginate(12)->withQueryString();
        $pendingCount = (clone $query)->where('status', 'pending')->count();
        $approvedCount = (clone $query)->where('status', 'approved')->count();

        return view('attendance.leaves', compact('leaveRequests', 'pendingCount', 'approvedCount'));
    }

    /**
     * Submit leave application (Student or Parent for Student).
     */
    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if ($user->isParent()) {
            $children = User::where('role', 'student')
                ->where(fn ($q) => $q->where('parent_id', $user->id)->orWhere(fn ($legacy) => $legacy->whereNull('parent_id')->whereRaw('LOWER(TRIM(guardian_email)) = ?', [mb_strtolower(trim((string) $user->email))])))
                ->pluck('id')->all();
            if (! $children) {
                return back()->with('error', 'No enrolled child is linked to this parent account.');
            }
            $validated = $request->validate([
                'student_id' => ['required', 'integer', 'in:'.implode(',', $children)],
                'start_date' => ['required', 'date_format:Y-m-d'],
                'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
                'type' => ['required', 'in:medical,family,academic,other'],
                'reason' => ['required', 'string', 'min:5', 'max:5000'],
            ]);
            $studentId = (int) $validated['student_id'];
        } elseif ($user->isStudent()) {
            $validated = $request->validate([
                'start_date' => ['required', 'date_format:Y-m-d'],
                'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
                'type' => ['required', 'in:medical,family,academic,other'],
                'reason' => ['required', 'string', 'min:5', 'max:5000'],
            ]);
            $studentId = (int) $user->id;
        } else {
            abort(403, 'Only students and their linked parents can submit leave applications.');
        }

        $start = Carbon::createFromFormat('Y-m-d', $validated['start_date'])->startOfDay();
        $end = Carbon::createFromFormat('Y-m-d', $validated['end_date'])->startOfDay();
        $maxDays = (int) config('leave.max_days', 30);
        if ($start->diffInDays($end) + 1 > $maxDays) {
            return back()->withInput()->withErrors(['end_date' => "A leave request cannot exceed {$maxDays} calendar days."]);
        }

        $instructionalDays = 0;
        $weekends = config('attendance.weekend_days', ['Sunday']);
        for ($curr = $start->copy(); $curr->lte($end); $curr->addDay()) {
            $isWeekend = in_array($curr->format('l'), $weekends, true);
            $isHoliday = AcademicHoliday::findHolidayForDate($curr);
            if (! $isWeekend && ! $isHoliday) {
                $instructionalDays++;
            }
        }
        if ($instructionalDays === 0) {
            return back()->withInput()->with('error', 'The requested dates fall entirely on official school holidays or non-instructional weekend days.');
        }

        try {
            $leave = DB::transaction(function () use ($studentId, $validated) {
                // Serialize requests per student so simultaneous submissions cannot bypass overlap checks.
                $student = User::query()->whereKey($studentId)->lockForUpdate()->firstOrFail();
                if ($student->role !== 'student') {
                    abort(422, 'Leave applications can only be created for enrolled students.');
                }
                $overlapping = StudentLeaveRequest::query()->where('student_id', $studentId)
                    ->whereIn('status', ['pending', 'approved'])
                    ->whereDate('start_date', '<=', $validated['end_date'])
                    ->whereDate('end_date', '>=', $validated['start_date'])
                    ->exists();
                if ($overlapping) {
                    throw new \DomainException('A pending or approved leave request already overlaps these dates.');
                }
                $examCount = 0;
                if ($student->current_class_id) {
                    $examCount = SchoolExamSchedule::query()
                        ->where('class_id', $student->current_class_id)
                        ->whereBetween('exam_date', [$validated['start_date'], $validated['end_date']])
                        ->count();
                }
                $finalReason = trim($validated['reason']);
                if ($examCount > 0) {
                    $finalReason = "[Notice: {$examCount} exam(s) scheduled during this leave period] ".$finalReason;
                }

                return StudentLeaveRequest::create([
                    'student_id' => $studentId,
                    'start_date' => $validated['start_date'],
                    'end_date' => $validated['end_date'],
                    'type' => $validated['type'],
                    'reason' => $finalReason,
                    'status' => 'pending',
                ]);
            });
        } catch (\DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('leaves.index', ['tab' => 'view'])
            ->with('success', 'Leave application submitted successfully for review by institutional faculty.');
    }

    /**
     * Teacher / Admin reviews and approves or rejects leave.
     * Automatically reflects approved leave as 'excused' in Attendance records.
     */
    public function review(Request $request, int $id): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user->isAdmin() || $user->isTeacher(), 403);

        $validated = $request->validate([
            'status' => ['required', 'in:approved,rejected'],
            'teacher_remarks' => ['nullable', 'string', 'max:2000'],
        ]);

        $result = DB::transaction(function () use ($id, $user, $validated) {
            $leave = StudentLeaveRequest::query()->with('student.currentClass')->lockForUpdate()->findOrFail($id);
            $studentClass = $leave->student?->currentClass;
            $isAuthorizedReviewer = $user->isAdmin()
                || ($user->isTeacher() && $studentClass && (int) $studentClass->class_teacher_id === (int) $user->id);
            abort_unless($isAuthorizedReviewer, 403, 'Only the class teacher or an administrator can review this request.');
            if ($leave->status !== 'pending') {
                abort(409, 'This leave request has already been reviewed or cancelled.');
            }

            $leave->update([
                'status' => $validated['status'],
                'teacher_remarks' => isset($validated['teacher_remarks']) ? trim($validated['teacher_remarks']) : null,
                'reviewed_by' => $user->id,
            ]);

            // Approved leave may excuse only absences; never overwrite a recorded present/late/half-day decision.
            if ($validated['status'] === 'approved' && $leave->student?->current_class_id) {
                $classId = $leave->student->current_class_id;
                $start = Carbon::parse($leave->start_date)->startOfDay();
                $end = Carbon::parse($leave->end_date)->startOfDay();
                $today = Carbon::today();
                $weekends = config('attendance.weekend_days', ['Sunday']);
                for ($date = $start->copy(); $date->lte($end) && $date->lte($today); $date->addDay()) {
                    if (in_array($date->format('l'), $weekends, true) || AcademicHoliday::findHolidayForDate($date)) {
                        continue;
                    }
                    $session = AttendanceSession::query()->where('class_id', $classId)
                        ->whereDate('session_date', $date->toDateString())->lockForUpdate()->first();
                    if (! $session) {
                        continue;
                    }
                    $record = AttendanceRecord::query()
                        ->where('session_id', $session->id)->where('student_id', $leave->student_id)
                        ->lockForUpdate()->first();
                    if (! $record) {
                        AttendanceRecord::create([
                            'session_id' => $session->id, 'student_id' => $leave->student_id,
                            'status' => 'excused', 'remarks' => 'Approved leave (reviewer-synced)',
                        ]);
                    } elseif ($record->status === 'absent') {
                        DB::table('attendance_record_revisions')->insert([
                            'attendance_record_id' => $record->id, 'attendance_session_id' => $session->id,
                            'student_id' => $leave->student_id, 'actor_id' => $user->id,
                            'before_status' => 'absent', 'after_status' => 'excused',
                            'source' => 'approved_leave_sync', 'reason' => 'Approved leave synchronized to attendance.',
                            'changed_at' => now(), 'created_at' => now(), 'updated_at' => now(),
                        ]);
                        $record->update(['status' => 'excused', 'remarks' => 'Approved leave (reviewer-synced)']);
                    }
                }
            }

            return $leave->load('student:id,name');
        });

        try {
            NotificationService::notifyUser(
                $result->student_id, 'attendance', 'Leave Application '.ucfirst($result->status),
                'Your leave request from '.$result->start_date->format('M d').' to '.$result->end_date->format('M d, Y').' has been '.$result->status.'.',
                route('leaves.index'), $result->status === 'approved' ? 'check-circle' : 'x-circle'
            );
        } catch (\Throwable $e) {
            Log::warning('Leave review notification failed.', ['leave_id' => $result->id, 'error' => $e->getMessage()]);
        }

        return redirect()->route('leaves.index')->with('success', 'Leave request for '.$result->student->name.' marked as '.ucfirst($result->status).'.');
    }

    /**
     * Delete / cancel a leave application (supports cancellation of pending and approved leaves).
     */
    public function destroy(Request $request, int $id): RedirectResponse
    {
        $user = Auth::user();
        $leave = StudentLeaveRequest::query()->with('student.currentClass')->findOrFail($id);
        $ownsRequest = $user->isStudent() && (int) $leave->student_id === (int) $user->id;
        $isLinkedParent = $user->isParent() && User::query()->whereKey($leave->student_id)
            ->where(fn ($q) => $q->where('parent_id', $user->id)->orWhere(fn ($legacy) => $legacy->whereNull('parent_id')->whereRaw('LOWER(TRIM(guardian_email)) = ?', [mb_strtolower(trim((string) $user->email))])))->exists();
        $isAdmin = $user->isAdmin();
        abort_unless($ownsRequest || $isLinkedParent || $isAdmin, 403, 'Only the applicant, linked parent, or administrator can cancel this request.');

        if (! in_array($leave->status, ['pending', 'approved'], true)) {
            return back()->with('error', 'Only pending or approved leave requests can be cancelled.');
        }

        DB::transaction(function () use ($leave, $user) {
            $locked = StudentLeaveRequest::query()->lockForUpdate()->findOrFail($leave->id);
            if (! in_array($locked->status, ['pending', 'approved'], true)) {
                abort(409, 'Only pending or approved leave requests can be cancelled.');
            }

            $previousStatus = $locked->status;
            $locked->update([
                'status' => 'cancelled',
                'teacher_remarks' => ($locked->teacher_remarks ? $locked->teacher_remarks.' | ' : '').'Cancelled by '.$user->name.' on '.now()->format('M d, Y H:i'),
            ]);

            // If an approved leave is cancelled, sync any attendance records back if needed
            if ($previousStatus === 'approved' && $locked->student?->current_class_id) {
                $classId = $locked->student->current_class_id;
                $start = Carbon::parse($locked->start_date)->startOfDay();
                $end = Carbon::parse($locked->end_date)->startOfDay();
                $today = Carbon::today();

                for ($date = $start->copy(); $date->lte($end) && $date->lte($today); $date->addDay()) {
                    $session = AttendanceSession::query()->where('class_id', $classId)
                        ->whereDate('session_date', $date->toDateString())->lockForUpdate()->first();
                    if (! $session) {
                        continue;
                    }
                    $record = AttendanceRecord::query()
                        ->where('session_id', $session->id)->where('student_id', $locked->student_id)
                        ->where('status', 'excused')
                        ->where('remarks', 'like', '%Approved leave%')
                        ->lockForUpdate()->first();
                    if ($record) {
                        DB::table('attendance_record_revisions')->insert([
                            'attendance_record_id' => $record->id,
                            'attendance_session_id' => $session->id,
                            'student_id' => $locked->student_id,
                            'actor_id' => $user->id,
                            'before_status' => 'excused',
                            'after_status' => 'absent',
                            'source' => 'cancelled_leave_reversion',
                            'reason' => 'Approved leave was cancelled.',
                            'changed_at' => now(), 'created_at' => now(), 'updated_at' => now(),
                        ]);
                        $record->update(['status' => 'absent', 'remarks' => 'Leave cancelled; reverted to absent.']);
                    }
                }
            }
        });

        return redirect()->route('leaves.index', ['tab' => 'view'])->with('success', 'Leave request cancelled successfully. Attendance records have been synchronized.');
    }
}
