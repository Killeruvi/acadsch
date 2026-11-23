<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\AcademyNotice;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\FeeInvoice;
use App\Models\SchoolPeriodTimetable;
use App\Models\SchoolReportCard;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SchoolParentController extends Controller
{
    /**
     * Parent Portal Dashboard.
     */
    public function dashboard(Request $request): View
    {
        $parent = Auth::user();

        // Fetch children linked to this parent
        $children = User::where('role', 'student')
            ->where(function ($q) use ($parent) {
                $q->where('parent_id', $parent->id);
                // Legacy records may predate parent_id linkage. Only accept an
                // exact normalized email match for an authenticated parent account.
                if ($parent->email) {
                    $q->orWhere(function ($legacy) use ($parent) {
                        $legacy->whereNull('parent_id')
                            ->whereRaw('LOWER(guardian_email) = ?', [mb_strtolower(trim($parent->email))]);
                    });
                }
            })
            ->with(['currentClass.classTeacher', 'currentClass.subjects'])
            ->orderBy('name')
            ->get();

        $selectedChildId = $request->input('child_id', session('parent_active_student_id', $children->first()?->id));
        if ($selectedChildId && $children->contains('id', (int) $selectedChildId)) {
            session(['parent_active_student_id' => (int) $selectedChildId]);
        } else {
            $selectedChildId = $children->first()?->id;
            session(['parent_active_student_id' => $selectedChildId]);
        }

        $activeChild = $children->firstWhere('id', $selectedChildId) ?? $children->first();

        // Fetch child data
        $todayAttendance = null;
        $recentReportCards = collect();
        $todayTimetable = collect();
        $pendingFees = collect();
        $attendanceStats = ['total' => 0, 'present' => 0, 'absent' => 0, 'late' => 0, 'half_day' => 0, 'excused' => 0, 'percentage' => null];
        $allAttendanceRecords = collect();
        $allLeaves = collect();
        $pendingLeavesCount = 0;
        $approvedLeavesCount = 0;
        $weeklyTimetable = collect();
        $allInvoices = collect();
        $feeStats = ['total_billed' => 0, 'total_paid' => 0, 'total_due' => 0];

        if ($activeChild) {
            // Today's attendance
            $today = now()->toDateString();
            $session = AttendanceSession::where('class_id', $activeChild->current_class_id)
                ->whereDate('session_date', $today)
                ->first();

            if ($session) {
                $rec = AttendanceRecord::where('session_id', $session->id)
                    ->where('student_id', $activeChild->id)
                    ->first();
                $todayAttendance = $rec ? $rec->status : 'pending';
            }

            // Attendance aggregate
            $attendanceCounts = AttendanceRecord::where('student_id', $activeChild->id)
                ->selectRaw('status, COUNT(*) as aggregate')
                ->groupBy('status')
                ->pluck('aggregate', 'status');
            $presentDays = (int) ($attendanceCounts['present'] ?? 0);
            $lateDays = (int) ($attendanceCounts['late'] ?? 0);
            $halfDays = (int) ($attendanceCounts['half_day'] ?? 0);
            $absentDays = (int) ($attendanceCounts['absent'] ?? 0);
            $excusedDays = (int) ($attendanceCounts['excused'] ?? 0);
            $eligibleDays = $presentDays + $lateDays + $halfDays + $absentDays;
            $creditedDays = $presentDays + $lateDays + ($halfDays * 0.5);

            $attendanceStats = [
                'total' => $eligibleDays,
                'present' => $presentDays + $lateDays,
                'absent' => $absentDays,
                'late' => $lateDays,
                'half_day' => $halfDays,
                'excused' => $excusedDays,
                'percentage' => $eligibleDays > 0 ? round(($creditedDays / $eligibleDays) * 100, 1) : null,
            ];

            // Full Attendance Log for Attendance Tab
            $allAttendanceRecords = AttendanceRecord::where('student_id', $activeChild->id)
                ->with(['session.academicClass', 'session.teacher'])
                ->latest('id')
                ->take(50)
                ->get();

            // Report cards
            $recentReportCards = SchoolReportCard::where('student_id', $activeChild->id)
                ->with(['examTerm', 'academicClass'])
                ->latest()
                ->get();

            // Weekly Routine (all days)
            $weeklyTimetable = SchoolPeriodTimetable::where('class_id', $activeChild->current_class_id)
                ->with(['subject', 'teacher'])
                ->orderBy('period_number')
                ->get()
                ->groupBy('day_of_week');

            // Today's Timetable
            $dayOfWeek = now()->format('l');
            $todayTimetable = $weeklyTimetable->get($dayOfWeek, collect());

            // Fee Invoices
            $allInvoices = FeeInvoice::where('student_id', $activeChild->id)
                ->with(['payments'])
                ->latest()
                ->get();

            $pendingFees = $allInvoices->whereIn('status', ['unpaid', 'partial']);
            $feeStats = [
                'total_billed' => (float) $allInvoices->sum('total_due'),
                'total_paid' => (float) $allInvoices->sum('paid_amount'),
                'total_due' => (float) ($allInvoices->sum('total_due') - $allInvoices->sum('paid_amount')),
            ];

            // Leaves Portal Data
            $allLeaves = \App\Models\StudentLeaveRequest::where('student_id', $activeChild->id)
                ->with('reviewer:id,name')
                ->latest()
                ->get();
            $pendingLeavesCount = $allLeaves->where('status', 'pending')->count();
            $approvedLeavesCount = $allLeaves->where('status', 'approved')->count();

            $recentLeaves = $allLeaves->take(3);
            $recentAttendance = $allAttendanceRecords->take(5);
        } else {
            $recentLeaves = collect();
            $recentAttendance = collect();
        }

        // Recent school circulars (scoped to parents)
        $notices = \App\Models\AcademyNotice::whereNotNull('published_at')
            ->whereIn('target_audience', ['all', 'parents'])
            ->latest('published_at')
            ->take(5)
            ->get();

        return view('school.parent.dashboard', compact(
            'children',
            'activeChild',
            'todayAttendance',
            'recentReportCards',
            'todayTimetable',
            'weeklyTimetable',
            'pendingFees',
            'allInvoices',
            'feeStats',
            'attendanceStats',
            'allAttendanceRecords',
            'allLeaves',
            'pendingLeavesCount',
            'approvedLeavesCount',
            'recentLeaves',
            'recentAttendance',
            'notices'
        ));
    }
}
