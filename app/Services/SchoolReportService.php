<?php

namespace App\Services;

use App\Models\AcademicClass;
use App\Models\AcademicSubject;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\ClassEnrollment;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\HomeworkTask;
use App\Models\SchoolExamMark;
use App\Models\SchoolPeriodTimetable;
use App\Models\SchoolReportCard;
use App\Models\StudentLeaveRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SchoolReportService
{
    /**
     * Categories definition.
     */
    public const CATEGORIES = [
        'academic' => [
            'id' => 'academic',
            'name' => 'Academic & Examinations',
            'icon' => 'ti-medall',
            'description' => 'Terminal exam results, merit lists, subject mark analytics, and at-risk students',
            'admin_only' => false,
        ],
        'attendance' => [
            'id' => 'attendance',
            'name' => 'Attendance & Registers',
            'icon' => 'ti-check-box',
            'description' => 'Daily roll call aggregates, chronic absenteeism warnings, and student leaves',
            'admin_only' => false,
        ],
        'finance' => [
            'id' => 'finance',
            'name' => 'Fees & Treasury',
            'icon' => 'ti-wallet',
            'description' => 'Fee collection ledger, defaulters list, head-wise revenue, and gateway reconciliation',
            'admin_only' => true,
        ],
        'students' => [
            'id' => 'students',
            'name' => 'Student Demographics',
            'icon' => 'ti-id-badge',
            'description' => 'Class capacities, admissions register, student directory dossier',
            'admin_only' => false,
        ],
        'faculty' => [
            'id' => 'faculty',
            'name' => 'Faculty & Operations',
            'icon' => 'ti-user',
            'description' => 'Teacher workload matrix, class teacher allocations, timetable distribution',
            'admin_only' => false,
        ],
    ];

    /**
     * Report types dictionary.
     */
    public const REPORTS = [
        // 1. Academic & Examinations
        'exam_merit_list' => [
            'id' => 'exam_merit_list',
            'category' => 'academic',
            'name' => 'Terminal Exam Merit List & Rankings',
            'description' => 'Comprehensive ranking of students, GPA, percentage, and pass/fail standing',
            'admin_only' => false,
        ],
        'subject_performance' => [
            'id' => 'subject_performance',
            'category' => 'academic',
            'name' => 'Subject Performance & Grading Matrix',
            'description' => 'Subject-wise class averages, pass rates, highest and lowest scores',
            'admin_only' => false,
        ],
        'at_risk_students' => [
            'id' => 'at_risk_students',
            'category' => 'academic',
            'name' => 'Academic Defaulters & At-Risk Students',
            'description' => 'Students scoring below minimum benchmark (< 40%) requiring faculty intervention',
            'admin_only' => false,
        ],
        'homework_completion' => [
            'id' => 'homework_completion',
            'category' => 'academic',
            'name' => 'Homework & Task Submission Tracking',
            'description' => 'Class and subject homework assignment completion and evaluation rate',
            'admin_only' => false,
        ],

        // 2. Attendance
        'attendance_summary' => [
            'id' => 'attendance_summary',
            'category' => 'attendance',
            'name' => 'Daily Class Attendance Summary',
            'description' => 'Session-wise attendance roll call totals, present/absent counts and %',
            'admin_only' => false,
        ],
        'chronic_absenteeism' => [
            'id' => 'chronic_absenteeism',
            'category' => 'attendance',
            'name' => 'Low Attendance Defaulters List',
            'description' => 'Students below mandatory institutional attendance threshold (e.g. 75%)',
            'admin_only' => false,
        ],
        'student_attendance_log' => [
            'id' => 'student_attendance_log',
            'category' => 'attendance',
            'name' => 'Student Attendance Dossier',
            'description' => 'Detailed historical daily attendance log with remarks for enrolled students',
            'admin_only' => false,
        ],
        'leave_summary' => [
            'id' => 'leave_summary',
            'category' => 'attendance',
            'name' => 'Student Leave Requests Audit',
            'description' => 'Breakdown of submitted leave requests, durations, approved/rejected status',
            'admin_only' => false,
        ],

        // 3. Finance & Treasury (Admin only)
        'fee_collection' => [
            'id' => 'fee_collection',
            'category' => 'finance',
            'name' => 'Fee Collection Register',
            'description' => 'Date range ledger of all payments received across gateways and cash',
            'admin_only' => true,
        ],
        'fee_defaulters' => [
            'id' => 'fee_defaulters',
            'category' => 'finance',
            'name' => 'Fee Defaulters & Overdue Aging',
            'description' => 'Outstanding fee invoices, unpaid amounts, overdue days, and guardian contacts',
            'admin_only' => true,
        ],
        'fee_head_breakdown' => [
            'id' => 'fee_head_breakdown',
            'category' => 'finance',
            'name' => 'Category-Wise Fee Revenue',
            'description' => 'Revenue collection vs billed totals by category (tuition, transport, lab, etc.)',
            'admin_only' => true,
        ],
        'gateway_reconciliation' => [
            'id' => 'gateway_reconciliation',
            'category' => 'finance',
            'name' => 'Payment Channel Reconciliation',
            'description' => 'Collections grouped by payment channel (Cash, UPI, QR Scan, Bank Transfer, Cheque)',
            'admin_only' => true,
        ],

        // 4. Student Demographics
        'class_strength' => [
            'id' => 'class_strength',
            'category' => 'students',
            'name' => 'Class Strength & Capacity Utilization',
            'description' => 'Total enrolled students per class/section versus maximum approved capacity',
            'admin_only' => false,
        ],
        'new_admissions' => [
            'id' => 'new_admissions',
            'category' => 'students',
            'name' => 'New Admissions Register',
            'description' => 'Students admitted within date range, academic tier, and previous school history',
            'admin_only' => false,
        ],
        'student_master_directory' => [
            'id' => 'student_master_directory',
            'category' => 'students',
            'name' => 'Student Master Dossier Directory',
            'description' => 'Master records containing roll no, admission no, guardian contacts, and blood group',
            'admin_only' => false,
        ],

        // 5. Faculty & Staff
        'teacher_workload' => [
            'id' => 'teacher_workload',
            'category' => 'faculty',
            'name' => 'Faculty Workload & Allocation Matrix',
            'description' => 'Classes, subjects assigned, and weekly scheduled lecture period counts',
            'admin_only' => false,
        ],
        'class_teachers_roster' => [
            'id' => 'class_teachers_roster',
            'category' => 'faculty',
            'name' => 'Class Teachers Official Roster',
            'description' => 'Directory of classes with appointed class teachers and room locations',
            'admin_only' => false,
        ],
    ];

    /**
     * Get accessible categories for a given user.
     */
    public function getCategories(User $user): array
    {
        return array_filter(self::CATEGORIES, function ($cat) use ($user) {
            return ! $cat['admin_only'] || $user->isAdmin() || ($user->isCashier() && $cat['id'] === 'finance');
        });
    }

    /**
     * Get accessible report definitions for a category.
     */
    public function getReportsForCategory(string $category, User $user): array
    {
        return array_filter(self::REPORTS, function ($rep) use ($category, $user) {
            if ($rep['category'] !== $category) {
                return false;
            }

            return ! $rep['admin_only'] || $user->isAdmin() || ($user->isCashier() && $rep['category'] === 'finance');
        });
    }

    /**
     * Generate structured report data.
     */
    public function generateReport(string $reportType, array $filters, User $user): array
    {
        if (! isset(self::REPORTS[$reportType])) {
            $reportType = 'exam_merit_list';
        }

        $meta = self::REPORTS[$reportType];

        // Access check
        $isAllowed = ! $meta['admin_only'] || $user->isAdmin() || ($user->isCashier() && $meta['category'] === 'finance');
        if (! $isAllowed) {
            abort(403, 'Unauthorized access to institutional financial reports.');
        }

        $method = 'generate'.str_replace(' ', '', ucwords(str_replace('_', ' ', $reportType)));

        if (method_exists($this, $method)) {
            $data = $this->$method($filters);
        } else {
            $data = $this->generateFallback($filters);
        }

        return array_merge([
            'id' => $reportType,
            'title' => $meta['name'],
            'category' => $meta['category'],
            'description' => $meta['description'],
            'generated_at' => now()->format('d M Y, h:i A'),
            'filters' => $filters,
        ], $data);
    }

    /* -------------------------------------------------------------------------
       1. ACADEMIC & EXAMINATION REPORTS
    ------------------------------------------------------------------------- */

    /**
     * 1.1 Exam Merit List & Rankings
     */
    protected function generateExamMeritList(array $filters): array
    {
        $classId = $filters['class_id'] ?? null;
        $termId = $filters['term_id'] ?? null;

        $query = SchoolReportCard::with(['student', 'academicClass', 'examTerm'])
            ->when($classId, fn ($q) => $q->where('class_id', $classId))
            ->when($termId, fn ($q) => $q->where('exam_term_id', $termId))
            ->orderBy('percentage', 'desc');

        $reportCards = $query->get();

        // If no compiled report cards exist yet, compile from exam marks or sample
        if ($reportCards->isEmpty()) {
            // Aggregate from SchoolExamMark
            $marksQuery = SchoolExamMark::with(['student.currentClass', 'subject', 'examTerm'])
                ->when($classId, fn ($q) => $q->where('class_id', $classId))
                ->when($termId, fn ($q) => $q->where('exam_term_id', $termId));

            $marks = $marksQuery->get();
            $grouped = $marks->groupBy('student_id');

            $rows = [];
            $rank = 1;
            foreach ($grouped as $studentId => $studentMarks) {
                $student = $studentMarks->first()->student;
                $totMax = $studentMarks->sum('max_marks');
                $totObt = $studentMarks->sum('marks_obtained');
                $pct = $totMax > 0 ? round(($totObt / $totMax) * 100, 2) : 0;
                $isPass = $studentMarks->every(fn ($m) => $m->marks_obtained >= $m->pass_marks);

                $rows[] = [
                    'rank' => $rank++,
                    'student_name' => $student->name ?? 'Student #'.$studentId,
                    'roll_number' => $student->roll_number ?? '—',
                    'admission_number' => $student->admission_number ?? '—',
                    'class_name' => $student->currentClass->name ?? 'Grade',
                    'max_marks' => $totMax,
                    'marks_obtained' => $totObt,
                    'percentage' => $pct.'%',
                    'grade' => $pct >= 85 ? 'A+' : ($pct >= 70 ? 'A' : ($pct >= 55 ? 'B' : ($pct >= 40 ? 'C' : 'F'))),
                    'status' => $isPass ? 'Passed' : 'Needs Support',
                ];
            }
        } else {
            $rows = [];
            $rank = 1;
            foreach ($reportCards as $rc) {
                $rows[] = [
                    'rank' => $rc->class_rank ?: $rank++,
                    'student_name' => $rc->student->name ?? 'Student #'.$rc->student_id,
                    'roll_number' => $rc->student->roll_number ?? '—',
                    'admission_number' => $rc->student->admission_number ?? '—',
                    'class_name' => $rc->academicClass->name ?? '—',
                    'max_marks' => (float) $rc->total_max_marks,
                    'marks_obtained' => (float) $rc->total_marks_obtained,
                    'percentage' => number_format((float) $rc->percentage, 1).'%',
                    'grade' => $rc->overall_grade ?: '—',
                    'status' => ucfirst($rc->result_status ?: 'passed'),
                ];
            }
        }

        $totalStudents = count($rows);
        $passCount = count(array_filter($rows, fn ($r) => str_contains(strtolower($r['status']), 'pass')));
        $passRate = $totalStudents > 0 ? round(($passCount / $totalStudents) * 100, 1) : 0;
        $avgScore = $totalStudents > 0 ? round(array_sum(array_map(fn ($r) => (float) rtrim($r['percentage'], '%'), $rows)) / $totalStudents, 1) : 0;
        $topScore = $totalStudents > 0 ? max(array_map(fn ($r) => (float) rtrim($r['percentage'], '%'), $rows)) : 0;

        return [
            'kpis' => [
                ['label' => 'Total Ranked Students', 'value' => $totalStudents, 'color' => 'indigo', 'subtext' => 'Enrolled & Examined'],
                ['label' => 'Overall Pass Rate', 'value' => $passRate.'%', 'color' => 'emerald', 'subtext' => $passCount.' cleared benchmarks'],
                ['label' => 'Class Average Score', 'value' => $avgScore.'%', 'color' => 'blue', 'subtext' => 'Across evaluated papers'],
                ['label' => 'Highest Achieved', 'value' => $topScore.'%', 'color' => 'amber', 'subtext' => 'Top Merit Standing'],
            ],
            'headers' => ['Rank', 'Student Name', 'Roll No', 'Admission No', 'Class', 'Max Marks', 'Marks Obtained', 'Percentage', 'Grade', 'Result Standing'],
            'rows' => $rows,
            'export_headers' => ['Rank', 'Student Name', 'Roll Number', 'Admission Number', 'Class', 'Max Marks', 'Marks Obtained', 'Percentage', 'Grade', 'Status'],
            'export_rows' => array_map(fn ($r) => array_values($r), $rows),
        ];
    }

    /**
     * 1.2 Subject Performance & Grading Matrix
     */
    protected function generateSubjectPerformance(array $filters): array
    {
        $classId = $filters['class_id'] ?? null;

        $subjects = AcademicSubject::with(['academicClass', 'teacher'])
            ->when($classId, fn ($q) => $q->where('class_id', $classId))
            ->get();

        $rows = [];
        $totalCandidates = 0;
        $totalPass = 0;

        foreach ($subjects as $subject) {
            $marks = SchoolExamMark::where('subject_id', $subject->id)->get();
            $candidates = $marks->count();
            $high = $marks->max('marks_obtained') ?? 0;
            $low = $candidates > 0 ? $marks->min('marks_obtained') : 0;
            $avg = $candidates > 0 ? round($marks->avg('marks_obtained'), 1) : 0;
            $passed = $marks->filter(fn ($m) => $m->marks_obtained >= $m->pass_marks)->count();
            $rate = $candidates > 0 ? round(($passed / $candidates) * 100, 1) : 100;

            $totalCandidates += $candidates;
            $totalPass += $passed;

            $rows[] = [
                'subject_code' => $subject->code ?: 'SUB-'.$subject->id,
                'subject_name' => $subject->name,
                'class_name' => $subject->academicClass->name ?? 'All',
                'teacher' => $subject->teacher->name ?? 'Unassigned',
                'candidates' => $candidates,
                'highest' => $high,
                'lowest' => $low,
                'average' => $avg,
                'pass_rate' => $rate.'%',
            ];
        }

        $overallRate = $totalCandidates > 0 ? round(($totalPass / $totalCandidates) * 100, 1) : 100;

        return [
            'kpis' => [
                ['label' => 'Total Subjects Analyzed', 'value' => count($rows), 'color' => 'indigo', 'subtext' => 'Active Curricula'],
                ['label' => 'Examined Submissions', 'value' => $totalCandidates, 'color' => 'blue', 'subtext' => 'Marks Recorded'],
                ['label' => 'Mean Subject Pass Rate', 'value' => $overallRate.'%', 'color' => 'emerald', 'subtext' => 'Institutional standard'],
            ],
            'headers' => ['Code', 'Subject Name', 'Class', 'Faculty Instructor', 'Total Candidates', 'Highest Score', 'Lowest Score', 'Mean Score', 'Pass Percentage'],
            'rows' => $rows,
            'export_headers' => ['Code', 'Subject Name', 'Class', 'Faculty Instructor', 'Candidates', 'Highest Score', 'Lowest Score', 'Mean Score', 'Pass Percentage'],
            'export_rows' => array_map(fn ($r) => array_values($r), $rows),
        ];
    }

    /**
     * 1.3 At-Risk Students & Academic Defaulters
     */
    protected function generateAtRiskStudents(array $filters): array
    {
        $classId = $filters['class_id'] ?? null;

        // Find students with marks < 40% or overall percentage < 40
        $students = User::where('role', 'student')
            ->when($classId, fn ($q) => $q->where('current_class_id', $classId))
            ->with(['currentClass', 'reportCards'])
            ->get();

        $rows = [];
        foreach ($students as $student) {
            $latestRc = $student->reportCards->sortByDesc('created_at')->first();
            $pct = $latestRc ? (float) $latestRc->percentage : null;

            // Also check marks
            $failingMarks = SchoolExamMark::where('student_id', $student->id)
                ->whereRaw('marks_obtained < pass_marks')
                ->count();

            if (($pct !== null && $pct < 45) || $failingMarks > 0) {
                $rows[] = [
                    'student_name' => $student->name,
                    'roll_number' => $student->roll_number ?? '—',
                    'admission_number' => $student->admission_number ?? '—',
                    'class_name' => $student->currentClass->name ?? '—',
                    'percentage' => $pct !== null ? $pct.'%' : 'N/A',
                    'failed_subjects' => $failingMarks > 0 ? $failingMarks.' Subject(s)' : 'Low Aggregate',
                    'guardian_name' => $student->guardian_name ?? 'Parent / Guardian',
                    'guardian_phone' => $student->guardian_phone ?? $student->phone ?? '—',
                    'status' => 'Intervention Required',
                ];
            }
        }

        return [
            'kpis' => [
                ['label' => 'Identified At-Risk Students', 'value' => count($rows), 'color' => 'rose', 'subtext' => 'Score < 45% or failed subjects'],
                ['label' => 'Parent Contact Readiness', 'value' => count(array_filter($rows, fn ($r) => $r['guardian_phone'] !== '—')), 'color' => 'amber', 'subtext' => 'Direct phone available'],
            ],
            'headers' => ['Student Name', 'Roll No', 'Admission No', 'Class', 'Aggregate %', 'Failing Papers', 'Guardian Name', 'Guardian Phone', 'Action Status'],
            'rows' => $rows,
            'export_headers' => ['Student Name', 'Roll Number', 'Admission Number', 'Class', 'Aggregate Percentage', 'Failing Papers', 'Guardian Name', 'Guardian Phone', 'Status'],
            'export_rows' => array_map(fn ($r) => array_values($r), $rows),
        ];
    }

    /**
     * 1.4 Homework & Task Submission Tracking
     */
    protected function generateHomeworkCompletion(array $filters): array
    {
        $classId = $filters['class_id'] ?? null;

        $tasks = HomeworkTask::with(['academicClass', 'teacher', 'submissions'])
            ->when($classId, fn ($q) => $q->where('class_id', $classId))
            ->orderBy('due_date', 'desc')
            ->take(50)
            ->get();

        $rows = [];
        $totalTasks = $tasks->count();
        $totalSubmissions = 0;

        foreach ($tasks as $task) {
            $class = $task->academicClass;
            $enrolledCount = $class ? ClassEnrollment::where('class_id', $class->id)->count() : 0;
            $enrolledCount = max($enrolledCount, 1);
            $subCount = $task->submissions->count();
            $checkedCount = $task->submissions->whereNotNull('checked_at')->count();
            $rate = round(($subCount / $enrolledCount) * 100, 1);
            $totalSubmissions += $subCount;

            $rows[] = [
                'task_title' => $task->title,
                'class_name' => $class->name ?? 'All',
                'teacher' => $task->teacher->name ?? 'Instructor',
                'due_date' => $task->due_date ? Carbon::parse($task->due_date)->format('d M Y') : '—',
                'expected' => $enrolledCount,
                'submitted' => $subCount,
                'checked' => $checkedCount,
                'completion_rate' => $rate.'%',
            ];
        }

        return [
            'kpis' => [
                ['label' => 'Assignments Evaluated', 'value' => $totalTasks, 'color' => 'indigo', 'subtext' => 'Issued to students'],
                ['label' => 'Total Student Submissions', 'value' => $totalSubmissions, 'color' => 'emerald', 'subtext' => 'Turned in on portal'],
            ],
            'headers' => ['Assignment Title', 'Class', 'Faculty', 'Due Date', 'Class Strength', 'Submissions', 'Graded Count', 'Completion Rate'],
            'rows' => $rows,
            'export_headers' => ['Assignment Title', 'Class', 'Faculty', 'Due Date', 'Class Strength', 'Submissions', 'Graded Count', 'Completion Rate'],
            'export_rows' => array_map(fn ($r) => array_values($r), $rows),
        ];
    }

    /* -------------------------------------------------------------------------
       2. ATTENDANCE & LEAVE REPORTS
    ------------------------------------------------------------------------- */

    /**
     * 2.1 Daily Class Attendance Summary
     */
    protected function generateAttendanceSummary(array $filters): array
    {
        $classId = $filters['class_id'] ?? null;
        $fromDate = $filters['from_date'] ?? null;
        $toDate = $filters['to_date'] ?? null;

        $sessionsQuery = AttendanceSession::with(['academicClass', 'records', 'teacher'])
            ->when($classId, fn ($q) => $q->where('class_id', $classId))
            ->when($fromDate, fn ($q) => $q->whereDate('session_date', '>=', $fromDate))
            ->when($toDate, fn ($q) => $q->whereDate('session_date', '<=', $toDate))
            ->orderBy('session_date', 'desc');

        $sessions = $sessionsQuery->take(100)->get();

        $rows = [];
        $totPresent = 0;
        $totRecords = 0;

        foreach ($sessions as $session) {
            $records = $session->records;
            $total = $records->count();
            $p = $records->where('status', 'present')->count();
            $a = $records->where('status', 'absent')->count();
            $l = $records->where('status', 'late')->count();
            $e = $records->where('status', 'excused')->count();
            $rate = $total > 0 ? round((($p + $l) / $total) * 100, 1) : 0;

            $totPresent += ($p + $l);
            $totRecords += $total;

            $rows[] = [
                'date' => $session->session_date ? Carbon::parse($session->session_date)->format('d M Y') : '—',
                'class_name' => $session->academicClass->name ?? 'All',
                'title' => $session->title ?: 'Daily Roll Call',
                'instructor' => $session->teacher->name ?? 'Class Faculty',
                'total_strength' => $total,
                'present' => $p,
                'absent' => $a,
                'late' => $l,
                'attendance_rate' => $rate.'%',
            ];
        }

        $overallRate = $totRecords > 0 ? round(($totPresent / $totRecords) * 100, 1) : 0;

        return [
            'kpis' => [
                ['label' => 'Total Roll Call Sessions', 'value' => count($rows), 'color' => 'indigo', 'subtext' => 'Sessions conducted'],
                ['label' => 'Average Daily Attendance', 'value' => $overallRate.'%', 'color' => 'emerald', 'subtext' => 'Across all recorded periods'],
                ['label' => 'Student-Days Recorded', 'value' => $totRecords, 'color' => 'blue', 'subtext' => 'Individual check-ins'],
            ],
            'headers' => ['Session Date', 'Class', 'Session Label', 'Faculty', 'Enrolled Strength', 'Present', 'Absent', 'Late', 'Attendance Rate'],
            'rows' => $rows,
            'export_headers' => ['Session Date', 'Class', 'Session Label', 'Faculty', 'Strength', 'Present', 'Absent', 'Late', 'Attendance Rate'],
            'export_rows' => array_map(fn ($r) => array_values($r), $rows),
        ];
    }

    /**
     * 2.2 Low Attendance Defaulters List
     */
    protected function generateChronicAbsenteeism(array $filters): array
    {
        $classId = $filters['class_id'] ?? null;
        $threshold = (float) ($filters['threshold'] ?? 75.0);

        $students = User::where('role', 'student')
            ->when($classId, fn ($q) => $q->where('current_class_id', $classId))
            ->with(['currentClass'])
            ->get();

        $rows = [];
        foreach ($students as $student) {
            $records = AttendanceRecord::where('student_id', $student->id)->get();
            $total = $records->count();
            if ($total === 0) {
                continue;
            }

            $present = $records->whereIn('status', ['present', 'late'])->count();
            $rate = round(($present / $total) * 100, 1);

            if ($rate < $threshold) {
                $rows[] = [
                    'student_name' => $student->name,
                    'roll_number' => $student->roll_number ?? '—',
                    'admission_number' => $student->admission_number ?? '—',
                    'class_name' => $student->currentClass->name ?? '—',
                    'total_sessions' => $total,
                    'days_present' => $present,
                    'days_absent' => ($total - $present),
                    'attendance_rate' => $rate.'%',
                    'guardian_phone' => $student->guardian_phone ?? $student->phone ?? '—',
                    'warning_level' => $rate < 60 ? 'Critical' : 'Warning',
                ];
            }
        }

        return [
            'kpis' => [
                ['label' => 'Threshold Criterion', 'value' => '< '.$threshold.'%', 'color' => 'amber', 'subtext' => 'Mandatory academic cutoff'],
                ['label' => 'Chronic Defaulters Count', 'value' => count($rows), 'color' => 'rose', 'subtext' => 'Students below threshold'],
            ],
            'headers' => ['Student Name', 'Roll No', 'Admission No', 'Class', 'Total Sessions', 'Days Present', 'Days Absent', 'Attendance Rate', 'Guardian Phone', 'Notice Severity'],
            'rows' => $rows,
            'export_headers' => ['Student Name', 'Roll Number', 'Admission Number', 'Class', 'Total Sessions', 'Days Present', 'Days Absent', 'Attendance Rate', 'Guardian Phone', 'Severity'],
            'export_rows' => array_map(fn ($r) => array_values($r), $rows),
        ];
    }

    /**
     * 2.3 Student Attendance Dossier
     */
    protected function generateStudentAttendanceLog(array $filters): array
    {
        $classId = $filters['class_id'] ?? null;
        $studentId = $filters['student_id'] ?? null;

        $query = AttendanceRecord::with(['session.academicClass', 'student'])
            ->when($studentId, fn ($q) => $q->where('student_id', $studentId))
            ->when($classId, function ($q) use ($classId) {
                $q->whereHas('session', fn ($sq) => $sq->where('class_id', $classId));
            })
            ->orderBy('id', 'desc');

        $records = $query->take(150)->get();

        $rows = [];
        foreach ($records as $rec) {
            $session = $rec->session;
            $rows[] = [
                'date' => $session && $session->session_date ? Carbon::parse($session->session_date)->format('d M Y') : '—',
                'student_name' => $rec->student->name ?? 'Student #'.$rec->student_id,
                'class_name' => $session->academicClass->name ?? '—',
                'session_title' => $session->title ?? 'Roll Call',
                'status' => ucfirst($rec->status),
                'remarks' => $rec->remarks ?: 'None recorded',
            ];
        }

        return [
            'kpis' => [
                ['label' => 'Total Logged Entries', 'value' => count($rows), 'color' => 'indigo', 'subtext' => 'Historical daily attendance logs'],
            ],
            'headers' => ['Log Date', 'Student Name', 'Class', 'Session Description', 'Status', 'Faculty Remarks'],
            'rows' => $rows,
            'export_headers' => ['Log Date', 'Student Name', 'Class', 'Session Description', 'Status', 'Remarks'],
            'export_rows' => array_map(fn ($r) => array_values($r), $rows),
        ];
    }

    /**
     * 2.4 Student Leave Requests Audit
     */
    protected function generateLeaveSummary(array $filters): array
    {
        $leaves = StudentLeaveRequest::with('student.currentClass')
            ->orderBy('created_at', 'desc')
            ->get();

        $rows = [];
        foreach ($leaves as $leave) {
            $start = $leave->start_date ? Carbon::parse($leave->start_date) : null;
            $end = $leave->end_date ? Carbon::parse($leave->end_date) : null;
            $days = ($start && $end) ? $start->diffInDays($end) + 1 : 1;

            $rows[] = [
                'student_name' => $leave->student->name ?? 'Student #'.$leave->student_id,
                'class_name' => $leave->student->currentClass->name ?? '—',
                'leave_type' => ucfirst($leave->type ?: 'medical'),
                'start_date' => $start ? $start->format('d M Y') : '—',
                'end_date' => $end ? $end->format('d M Y') : '—',
                'duration' => $days.' Day(s)',
                'reason' => $leave->reason ?: 'Personal',
                'status' => ucfirst($leave->status ?: 'pending'),
            ];
        }

        return [
            'kpis' => [
                ['label' => 'Total Applications', 'value' => count($rows), 'color' => 'indigo', 'subtext' => 'Leaves filed'],
                ['label' => 'Approved', 'value' => count(array_filter($rows, fn ($r) => strtolower($r['status']) === 'approved')), 'color' => 'emerald', 'subtext' => 'Authorized by faculty'],
                ['label' => 'Pending Review', 'value' => count(array_filter($rows, fn ($r) => strtolower($r['status']) === 'pending')), 'color' => 'amber', 'subtext' => 'Awaiting approval'],
            ],
            'headers' => ['Student Name', 'Class', 'Leave Category', 'From Date', 'To Date', 'Duration', 'Reason', 'Approval Status'],
            'rows' => $rows,
            'export_headers' => ['Student Name', 'Class', 'Category', 'From Date', 'To Date', 'Duration', 'Reason', 'Status'],
            'export_rows' => array_map(fn ($r) => array_values($r), $rows),
        ];
    }

    /* -------------------------------------------------------------------------
       3. FINANCIAL & FEE TREASURY REPORTS (ADMIN ONLY)
    ------------------------------------------------------------------------- */

    /**
     * 3.1 Fee Collection Register
     */
    protected function generateFeeCollection(array $filters): array
    {
        $fromDate = $filters['from_date'] ?? null;
        $toDate = $filters['to_date'] ?? null;
        $gateway = $filters['gateway'] ?? null;

        $paymentsQuery = FeePayment::with(['invoice.student.currentClass'])
            ->when($fromDate, fn ($q) => $q->whereDate('paid_at', '>=', $fromDate))
            ->when($toDate, fn ($q) => $q->whereDate('paid_at', '<=', $toDate))
            ->when($gateway, fn ($q) => $q->where('payment_gateway', $gateway))
            ->orderBy('paid_at', 'desc');

        $payments = $paymentsQuery->get();

        $rows = [];
        $totalCollected = 0;

        foreach ($payments as $pay) {
            $inv = $pay->invoice;
            $stu = $inv ? $inv->student : null;
            $amt = (float) $pay->amount;
            $totalCollected += $amt;

            $rows[] = [
                'receipt_number' => $pay->receipt_number ?: 'REC-'.str_pad($pay->id, 5, '0', STR_PAD_LEFT),
                'paid_date' => $pay->paid_at ? Carbon::parse($pay->paid_at)->format('d M Y, h:i A') : '—',
                'invoice_number' => $inv->invoice_number ?? '—',
                'student_name' => $stu->name ?? '—',
                'admission_number' => $stu->admission_number ?? '—',
                'class_name' => $stu->currentClass->name ?? '—',
                'category' => ucfirst($inv->category ?? 'tuition'),
                'payment_method' => strtoupper($pay->payment_method ?: 'UPI'),
                'gateway' => ucfirst($pay->payment_gateway ?: 'bharat_qr'),
                'amount' => '₹ '.number_format($amt, 2),
            ];
        }

        return [
            'kpis' => [
                ['label' => 'Total Fee Realized', 'value' => '₹ '.number_format($totalCollected, 2), 'color' => 'emerald', 'subtext' => 'Gross collections in window'],
                ['label' => 'Receipts Issued', 'value' => count($rows), 'color' => 'indigo', 'subtext' => 'Successful settlements'],
                ['label' => 'Mean Transaction', 'value' => count($rows) > 0 ? '₹ '.number_format($totalCollected / count($rows), 2) : '₹ 0.00', 'color' => 'blue', 'subtext' => 'Average receipt size'],
            ],
            'headers' => ['Receipt #', 'Payment Date & Time', 'Invoice #', 'Student Name', 'Admission No', 'Class', 'Fee Head', 'Method', 'Gateway / Mode', 'Collected Amount'],
            'rows' => $rows,
            'export_headers' => ['Receipt Number', 'Payment Date', 'Invoice Number', 'Student Name', 'Admission Number', 'Class', 'Fee Head', 'Method', 'Gateway', 'Amount'],
            'export_rows' => array_map(fn ($r) => array_values($r), $rows),
        ];
    }

    /**
     * 3.2 Fee Defaulters & Overdue Aging
     */
    protected function generateFeeDefaulters(array $filters): array
    {
        $classId = $filters['class_id'] ?? null;

        $invoicesQuery = FeeInvoice::with(['student.currentClass'])
            ->where('status', '!=', 'paid')
            ->when($classId, function ($q) use ($classId) {
                $q->whereHas('student', fn ($sq) => $sq->where('current_class_id', $classId));
            })
            ->orderBy('due_date', 'asc');

        $invoices = $invoicesQuery->get();

        $rows = [];
        $totalOutstanding = 0;

        foreach ($invoices as $inv) {
            $due = (float) $inv->total_due;
            $paid = (float) $inv->paid_amount;
            $balance = max(0, $due - $paid);
            $totalOutstanding += $balance;

            $dueDate = $inv->due_date ? Carbon::parse($inv->due_date) : null;
            $isOverdue = $dueDate && $dueDate->isPast();
            $daysOverdue = ($isOverdue && $dueDate) ? $dueDate->diffInDays(now()) : 0;

            $stu = $inv->student;

            $rows[] = [
                'invoice_number' => $inv->invoice_number,
                'student_name' => $stu->name ?? '—',
                'roll_number' => $stu->roll_number ?? '—',
                'admission_number' => $stu->admission_number ?? '—',
                'class_name' => $stu->currentClass->name ?? '—',
                'category' => ucfirst($inv->category ?? 'tuition'),
                'total_billed' => '₹ '.number_format($due, 2),
                'paid_so_far' => '₹ '.number_format($paid, 2),
                'outstanding' => '₹ '.number_format($balance, 2),
                'due_date' => $dueDate ? $dueDate->format('d M Y') : '—',
                'days_overdue' => $isOverdue ? $daysOverdue.' Days' : 'Current',
                'guardian_phone' => $stu->guardian_phone ?? $stu->phone ?? '—',
            ];
        }

        return [
            'kpis' => [
                ['label' => 'Total Outstanding Arrears', 'value' => '₹ '.number_format($totalOutstanding, 2), 'color' => 'rose', 'subtext' => 'Unpaid fee balance'],
                ['label' => 'Defaulter Invoices', 'value' => count($rows), 'color' => 'amber', 'subtext' => 'Pending clearance'],
            ],
            'headers' => ['Invoice #', 'Student Name', 'Roll No', 'Admission No', 'Class', 'Category', 'Total Billed', 'Paid So Far', 'Pending Balance', 'Due Date', 'Overdue Aging', 'Parent Contact Phone'],
            'rows' => $rows,
            'export_headers' => ['Invoice Number', 'Student Name', 'Roll Number', 'Admission Number', 'Class', 'Category', 'Total Billed', 'Paid So Far', 'Pending Balance', 'Due Date', 'Aging', 'Guardian Phone'],
            'export_rows' => array_map(fn ($r) => array_values($r), $rows),
        ];
    }

    /**
     * 3.3 Category-Wise Fee Revenue
     */
    protected function generateFeeHeadBreakdown(array $filters): array
    {
        $categories = ['tuition', 'admission', 'examination', 'transportation', 'laboratory', 'library', 'sports', 'miscellaneous'];
        $rows = [];
        $grandBilled = 0;
        $grandCollected = 0;

        foreach ($categories as $cat) {
            $invoices = FeeInvoice::where('category', $cat)->get();
            $count = $invoices->count();
            if ($count === 0 && ! in_array($cat, ['tuition', 'admission', 'examination'])) {
                continue;
            }

            $billed = (float) $invoices->sum('total_due');
            $collected = (float) $invoices->sum('paid_amount');
            $balance = max(0, $billed - $collected);
            $eff = $billed > 0 ? round(($collected / $billed) * 100, 1) : 0;

            $grandBilled += $billed;
            $grandCollected += $collected;

            $rows[] = [
                'category' => ucfirst($cat).' Fee',
                'invoices_count' => $count,
                'total_billed' => '₹ '.number_format($billed, 2),
                'total_collected' => '₹ '.number_format($collected, 2),
                'outstanding' => '₹ '.number_format($balance, 2),
                'recovery_rate' => $eff.'%',
            ];
        }

        $overallEff = $grandBilled > 0 ? round(($grandCollected / $grandBilled) * 100, 1) : 0;

        return [
            'kpis' => [
                ['label' => 'Gross Invoiced Value', 'value' => '₹ '.number_format($grandBilled, 2), 'color' => 'indigo', 'subtext' => 'Across all institutional heads'],
                ['label' => 'Total Recovered', 'value' => '₹ '.number_format($grandCollected, 2), 'color' => 'emerald', 'subtext' => 'Collected in institutional treasury'],
                ['label' => 'Treasury Recovery Rate', 'value' => $overallEff.'%', 'color' => 'blue', 'subtext' => 'Collection efficiency'],
            ],
            'headers' => ['Fee Head / Category', 'Invoices Generated', 'Total Billed Amount', 'Recovered Revenue', 'Outstanding Arrears', 'Recovery Efficiency'],
            'rows' => $rows,
            'export_headers' => ['Fee Head', 'Invoices Count', 'Billed Amount', 'Recovered Revenue', 'Outstanding Arrears', 'Recovery Efficiency'],
            'export_rows' => array_map(fn ($r) => array_values($r), $rows),
        ];
    }

    /**
     * 3.4 Payment Gateway Reconciliation
     */
    protected function generateGatewayReconciliation(array $filters): array
    {
        $payments = FeePayment::all();
        $channels = [
            'cash' => 'Cash Counter',
            'upi' => 'UPI Transfer',
            'qr' => 'QR Code Scan',
            'bank' => 'Bank Transfer / NEFT / RTGS',
            'cheque' => 'Cheque',
            'manual' => 'Manual (Other)',
        ];

        $rows = [];
        $totalVol = 0;

        foreach ($channels as $chKey => $chTitle) {
            $chPayments = $payments->filter(function ($p) use ($chKey) {
                $mode = strtolower((string) ($p->payment_mode ?? $p->payment_method ?? $p->payment_gateway ?? ''));

                return $mode === $chKey;
            });

            $count = $chPayments->count();
            $vol = (float) $chPayments->sum('amount');
            $totalVol += $vol;

            $rows[] = [
                'channel' => $chTitle,
                'channel_type' => in_array($chKey, ['cash', 'cheque']) ? 'Counter / Offline' : 'Digital / Electronic',
                'transactions' => $count,
                'gross_volume' => '₹ '.number_format($vol, 2),
                'status' => 'Reconciled',
            ];
        }

        return [
            'kpis' => [
                ['label' => 'Total Collections', 'value' => '₹ '.number_format($totalVol, 2), 'color' => 'emerald', 'subtext' => 'All channels aggregated'],
                ['label' => 'Payment Channels', 'value' => count($channels), 'color' => 'indigo', 'subtext' => 'Active collection modes'],
            ],
            'headers' => ['Payment Channel', 'Channel Type', 'Transactions', 'Gross Volume', 'Status'],
            'rows' => $rows,
            'export_headers' => ['Channel', 'Type', 'Transactions', 'Gross Volume', 'Status'],
            'export_rows' => array_map(fn ($r) => array_values($r), $rows),
        ];
    }

    /* -------------------------------------------------------------------------
       4. STUDENT DEMOGRAPHICS & ADMISSIONS REPORTS
    ------------------------------------------------------------------------- */

    /**
     * 4.1 Class Strength & Capacity Utilization
     */
    protected function generateClassStrength(array $filters): array
    {
        $classes = AcademicClass::with(['classTeacher', 'schoolSection', 'students'])
            ->orderBy('grade_level')
            ->orderBy('section')
            ->get();

        $rows = [];
        $totalEnrolled = 0;
        $totalCap = 0;

        foreach ($classes as $cls) {
            $enrolled = $cls->students->count();
            $cap = (int) ($cls->capacity ?: 40);
            $util = $cap > 0 ? round(($enrolled / $cap) * 100, 1) : 0;

            $totalEnrolled += $enrolled;
            $totalCap += $cap;

            $rows[] = [
                'class_name' => $cls->name,
                'section' => $cls->section ?: 'A',
                'stream' => $cls->stream ?: 'General',
                'room_number' => $cls->room_number ?: 'TBA',
                'class_teacher' => $cls->classTeacher->name ?? 'Unassigned',
                'enrolled' => $enrolled,
                'capacity' => $cap,
                'utilization' => $util.'%',
                'status' => $enrolled >= $cap ? 'Full' : 'Seats Open',
            ];
        }

        $overallUtil = $totalCap > 0 ? round(($totalEnrolled / $totalCap) * 100, 1) : 0;

        return [
            'kpis' => [
                ['label' => 'Total Enrolled Students', 'value' => $totalEnrolled, 'color' => 'indigo', 'subtext' => 'Active student body'],
                ['label' => 'Approved School Capacity', 'value' => $totalCap, 'color' => 'blue', 'subtext' => 'Physical classroom seats'],
                ['label' => 'Campus Seat Utilization', 'value' => $overallUtil.'%', 'color' => 'emerald', 'subtext' => 'Occupancy efficiency'],
            ],
            'headers' => ['Class Name', 'Section', 'Academic Stream', 'Room #', 'Appointed Class Teacher', 'Enrolled Strength', 'Class Capacity', 'Seat Utilization', 'Admission Status'],
            'rows' => $rows,
            'export_headers' => ['Class Name', 'Section', 'Stream', 'Room Number', 'Class Teacher', 'Enrolled', 'Capacity', 'Utilization', 'Status'],
            'export_rows' => array_map(fn ($r) => array_values($r), $rows),
        ];
    }

    /**
     * 4.2 New Admissions Register
     */
    protected function generateNewAdmissions(array $filters): array
    {
        $fromDate = $filters['from_date'] ?? null;
        $toDate = $filters['to_date'] ?? null;
        $classId = $filters['class_id'] ?? null;

        $studentsQuery = User::where('role', 'student')
            ->when($classId, fn ($q) => $q->where('current_class_id', $classId))
            ->when($fromDate, fn ($q) => $q->whereDate('created_at', '>=', $fromDate))
            ->when($toDate, fn ($q) => $q->whereDate('created_at', '<=', $toDate))
            ->with(['currentClass'])
            ->orderBy('created_at', 'desc');

        $students = $studentsQuery->get();

        $rows = [];
        foreach ($students as $stu) {
            $rows[] = [
                'admission_number' => $stu->admission_number ?: 'ADM-'.$stu->id,
                'student_name' => $stu->name,
                'class_name' => $stu->currentClass->name ?? 'Class '.($stu->current_class_id ?: 'TBA'),
                'roll_number' => $stu->roll_number ?? '—',
                'admitted_on' => $stu->created_at ? $stu->created_at->format('d M Y') : '—',
                'academic_tier' => $stu->academic_tier ?: 'Standard',
                'guardian_name' => $stu->guardian_name ?: '—',
                'guardian_phone' => $stu->guardian_phone ?: ($stu->phone ?: '—'),
                'previous_school' => $stu->previous_school_name ?: 'First Admission',
            ];
        }

        return [
            'kpis' => [
                ['label' => 'New Student Intakes', 'value' => count($rows), 'color' => 'emerald', 'subtext' => 'In selected registration period'],
            ],
            'headers' => ['Admission #', 'Student Full Name', 'Assigned Class', 'Roll No', 'Registration Date', 'Academic Tier', 'Guardian Name', 'Guardian Phone', 'Previous School Transfer'],
            'rows' => $rows,
            'export_headers' => ['Admission Number', 'Student Name', 'Class', 'Roll Number', 'Registration Date', 'Academic Tier', 'Guardian Name', 'Guardian Phone', 'Previous School'],
            'export_rows' => array_map(fn ($r) => array_values($r), $rows),
        ];
    }

    /**
     * 4.3 Student Master Dossier Directory
     */
    protected function generateStudentMasterDirectory(array $filters): array
    {
        $classId = $filters['class_id'] ?? null;

        $students = User::where('role', 'student')
            ->when($classId, fn ($q) => $q->where('current_class_id', $classId))
            ->with(['currentClass'])
            ->orderBy('current_class_id')
            ->orderBy('roll_number')
            ->get();

        $rows = [];
        foreach ($students as $stu) {
            $rows[] = [
                'admission_number' => $stu->admission_number ?: 'ADM-'.$stu->id,
                'roll_number' => $stu->roll_number ?? '—',
                'student_name' => $stu->name,
                'class_name' => $stu->currentClass->name ?? 'Class '.$stu->current_class_id,
                'email' => $stu->email,
                'blood_group' => $stu->blood_group ?: '—',
                'guardian_name' => $stu->guardian_name ?: '—',
                'guardian_phone' => $stu->guardian_phone ?: ($stu->phone ?: '—'),
                'enrollment_status' => $stu->is_approved ? 'Active & Approved' : 'Pending',
            ];
        }

        return [
            'kpis' => [
                ['label' => 'Directory Headcount', 'value' => count($rows), 'color' => 'indigo', 'subtext' => 'Active student records'],
            ],
            'headers' => ['Admission #', 'Roll No', 'Student Name', 'Class', 'Institutional Email', 'Blood Group', 'Guardian Name', 'Parent Contact Phone', 'Dossier Status'],
            'rows' => $rows,
            'export_headers' => ['Admission Number', 'Roll Number', 'Student Name', 'Class', 'Email', 'Blood Group', 'Guardian Name', 'Guardian Phone', 'Status'],
            'export_rows' => array_map(fn ($r) => array_values($r), $rows),
        ];
    }

    /* -------------------------------------------------------------------------
       5. FACULTY & STAFF REPORTS
    ------------------------------------------------------------------------- */

    /**
     * 5.1 Faculty Workload & Allocation Matrix
     */
    protected function generateTeacherWorkload(array $filters): array
    {
        $teachers = User::where('role', 'teacher')
            ->with(['teachingSubjects.academicClass', 'homeroomClass'])
            ->orderBy('name')
            ->get();

        $rows = [];
        $totalLectures = 0;

        foreach ($teachers as $teacher) {
            $subjects = $teacher->teachingSubjects;
            $classesCount = $subjects->pluck('class_id')->unique()->count();
            $weeklyPeriods = SchoolPeriodTimetable::where('teacher_id', $teacher->id)->count();

            // Fallback to sum of subject weekly periods if timetable not scheduled
            if ($weeklyPeriods === 0) {
                $weeklyPeriods = (int) $subjects->sum('weekly_periods');
            }

            $totalLectures += $weeklyPeriods;
            $classTeacherFor = $teacher->homeroomClass ? $teacher->homeroomClass->name : 'None';

            $rows[] = [
                'teacher_name' => $teacher->name,
                'faculty_id' => $teacher->student_id_number ?: 'FAC-'.$teacher->id,
                'email' => $teacher->email,
                'phone' => $teacher->phone ?: '—',
                'class_teacher_of' => $classTeacherFor,
                'classes_taught' => $classesCount,
                'subjects_count' => $subjects->count(),
                'weekly_periods' => $weeklyPeriods,
                'workload_status' => $weeklyPeriods > 24 ? 'Heavy' : ($weeklyPeriods < 12 ? 'Light' : 'Optimal'),
            ];
        }

        return [
            'kpis' => [
                ['label' => 'Active Faculty Staff', 'value' => count($rows), 'color' => 'indigo', 'subtext' => 'Teaching personnel'],
                ['label' => 'Weekly Scheduled Periods', 'value' => $totalLectures, 'color' => 'blue', 'subtext' => 'Campus-wide timetable slots'],
                ['label' => 'Mean Workload', 'value' => count($rows) > 0 ? round($totalLectures / count($rows), 1).' periods/wk' : '0', 'color' => 'emerald', 'subtext' => 'Per faculty member'],
            ],
            'headers' => ['Faculty Member', 'Staff Code', 'Official Email', 'Phone', 'Class Teacher Duty', 'Classes Handled', 'Subjects Taught', 'Weekly Periods', 'Workload Distribution'],
            'rows' => $rows,
            'export_headers' => ['Teacher Name', 'Staff Code', 'Email', 'Phone', 'Class Teacher Of', 'Classes Handled', 'Subjects Taught', 'Weekly Periods', 'Workload Status'],
            'export_rows' => array_map(fn ($r) => array_values($r), $rows),
        ];
    }

    /**
     * 5.2 Class Teachers Official Roster
     */
    protected function generateClassTeachersRoster(array $filters): array
    {
        $classes = AcademicClass::with(['classTeacher', 'students'])
            ->orderBy('grade_level')
            ->orderBy('section')
            ->get();

        $rows = [];
        foreach ($classes as $cls) {
            $t = $cls->classTeacher;
            $rows[] = [
                'class_name' => $cls->name,
                'section' => $cls->section ?: 'A',
                'grade_level' => 'Grade '.$cls->grade_level,
                'room_number' => $cls->room_number ?: 'TBA',
                'class_teacher' => $t ? $t->name : 'Unassigned',
                'teacher_email' => $t ? $t->email : '—',
                'teacher_phone' => $t ? ($t->phone ?: '—') : '—',
                'enrolled_students' => $cls->students->count(),
            ];
        }

        return [
            'kpis' => [
                ['label' => 'Total Academic Classes', 'value' => count($rows), 'color' => 'indigo', 'subtext' => 'Form sections'],
                ['label' => 'Assigned Class Teachers', 'value' => count(array_filter($rows, fn ($r) => $r['class_teacher'] !== 'Unassigned')), 'color' => 'emerald', 'subtext' => 'Homerooms active'],
            ],
            'headers' => ['Class Name', 'Section', 'Grade Level', 'Room #', 'Appointed Class Teacher', 'Faculty Email', 'Contact Phone', 'Enrolled Headcount'],
            'rows' => $rows,
            'export_headers' => ['Class Name', 'Section', 'Grade Level', 'Room Number', 'Class Teacher', 'Email', 'Phone', 'Enrolled Students'],
            'export_rows' => array_map(fn ($r) => array_values($r), $rows),
        ];
    }

    /**
     * Fallback report
     */
    protected function generateFallback(array $filters): array
    {
        return [
            'kpis' => [
                ['label' => 'Status', 'value' => 'Initialized', 'color' => 'blue', 'subtext' => 'System Ready'],
            ],
            'headers' => ['Information', 'Details'],
            'rows' => [
                ['Report Status', 'This report is ready and active for processing.'],
            ],
            'export_headers' => ['Field', 'Value'],
            'export_rows' => [
                ['Report Status', 'Ready'],
            ],
        ];
    }

    /**
     * Stream CSV file directly to response.
     */
    public function exportCsv(string $reportType, array $filters, User $user): StreamedResponse
    {
        $reportData = $this->generateReport($reportType, $filters, $user);
        $filename = 'report_'.$reportType.'_'.now()->format('Ymd_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($reportData) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM for Excel compatibility
            fwrite($handle, "\xEF\xBB\xBF");

            // School & Report Header Metadata
            fputcsv($handle, ['--- '.strtoupper($reportData['title']).' ---']);
            fputcsv($handle, ['Generated At:', $reportData['generated_at']]);
            fputcsv($handle, ['Description:', $reportData['description']]);
            fputcsv($handle, []);

            // Summary KPIs in CSV
            if (! empty($reportData['kpis'])) {
                fputcsv($handle, ['--- EXECUTIVE SUMMARY METRICS ---']);
                foreach ($reportData['kpis'] as $kpi) {
                    fputcsv($handle, [$kpi['label'], $kpi['value'], $kpi['subtext'] ?? '']);
                }
                fputcsv($handle, []);
            }

            // Data Table Headers
            fputcsv($handle, $reportData['export_headers']);

            // Data Table Rows
            foreach ($reportData['export_rows'] as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, 200, $headers);
    }
}
