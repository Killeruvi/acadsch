<?php

namespace App\Http\Controllers;

use App\Models\AcademicClass;
use App\Models\AcademicHoliday;
use App\Models\AcademicSubject;
use App\Models\AcademyEvent;
use App\Models\AcademyNotice;
use App\Models\Assignment;
use App\Models\AttendanceRecord;
use App\Models\AttendanceRejectionLog;
use App\Models\AttendanceSession;
use App\Models\Batch;
use App\Models\BloomAssignment;
use App\Models\BookIssue;
use App\Models\ClassEnrollment;
use App\Models\Course;
use App\Models\CurriculumNote;
use App\Models\CurriculumTopic;
use App\Models\CurriculumVideo;
use App\Models\DailyDpp;
use App\Models\Dormitory;
use App\Models\DormitoryRoom;
use App\Models\DppSubmission;
use App\Models\Enrollment;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\FrontOfficeVisitor;
use App\Models\HomeworkSubmission;
use App\Models\HomeworkTask;
use App\Models\LibraryBook;
use App\Models\Schedule;
use App\Models\SchoolExamSchedule;
use App\Models\SchoolExamTerm;
use App\Models\SchoolExpense;
use App\Models\SchoolIncome;
use App\Models\StudentLeaveRequest;
use App\Models\Submission;
use App\Models\TeacherAssignmentAudit;
use App\Models\TransportRoute;
use App\Models\TransportVehicle;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View|\Illuminate\Http\RedirectResponse
    {
        $user = Auth::user();

        if ($user->isParent()) {
            return redirect()->route('school.parent.dashboard');
        }

        if ($user->isCashier()) {
            return redirect()->route('school.fees.index');
        }

        $user->recordDailyActivity('dashboard');
        $birthdayCelebration = \App\Services\BirthdayService::checkAndCelebrate($user);

        $data = [
            'user' => $user,
            'dailyMotivation' => \App\Services\MotivationService::getRandomQuote(),
            'dailyFact' => \App\Services\MotivationService::getRandomFact(),
            'fireStatus' => \App\Services\MotivationService::getFireStatus($user->streak_count ?? 1),
            'birthdayCelebration' => $birthdayCelebration,
        ];

        if ($user->isAdmin()) {
            $today = now()->toDateString();
            $startOfWeek = now()->startOfWeek();
            $startOfMonth = now()->startOfMonth();
            $startOfTerm = now()->subMonths(3);

            // 1. TODAY'S SNAPSHOT (Top Priority)
            $todaySessions = AttendanceSession::whereDate('session_date', $today)
                ->with(['records', 'academicClass'])
                ->get();

            $todayClassIdsWithAttendance = $todaySessions->pluck('class_id')->filter()->unique()->toArray();
            $allActiveClasses = AcademicClass::withCount('students')->with('classTeacher')->get();
            $totalEnrolledStudents = ClassEnrollment::where('status', 'active')->count() ?: User::where('role', 'student')->count();

            $todayPresentCount = 0;
            $todayAbsentCount = 0;
            $todayExcusedCount = 0;
            $todayTotalRecords = 0;
            $abnormalClasses = [];

            foreach ($todaySessions as $session) {
                $present = $session->records->whereIn('status', ['present', 'late'])->count();
                $halfDays = $session->records->where('status', 'half_day')->count();
                $absent = $session->records->where('status', 'absent')->count();
                $excused = $session->records->where('status', 'excused')->count();
                $totalInSession = $session->records->where('status', '!=', 'excused')->count();

                $todayPresentCount += $present;
                $todayAbsentCount += $absent;
                $todayExcusedCount += $excused;
                $todayTotalRecords += $totalInSession;

                $pct = $totalInSession > 0 ? round((($present + ($halfDays * 0.5)) / $totalInSession) * 100, 1) : 0.0;
                if ($pct < config('attendance.minimum_percentage') && $totalInSession > 0) {
                    $abnormalClasses[] = [
                        'id' => $session->class_id,
                        'name' => $session->academicClass?->display_name ?? ('Class #' . $session->class_id),
                        'present' => $present,
                        'absent' => $absent,
                        'percentage' => $pct,
                    ];
                }
            }

            $todayAttendanceRate = $todayTotalRecords > 0 
                ? round(($todayPresentCount / $todayTotalRecords) * 100, 1) 
                : 0.0;

            // Teachers on leave today
            $activeStaffLeaves = StudentLeaveRequest::whereHas('student', fn($q) => $q->where('role', 'teacher'))
                ->where('status', 'approved')
                ->whereDate('start_date', '<=', $today)
                ->whereDate('end_date', '>=', $today)
                ->with('student:id,name')
                ->get();

            // Pending leaves (students + staff)
            $pendingStudentLeaves = StudentLeaveRequest::whereHas('student', fn($q) => $q->where('role', 'student'))
                ->where('status', 'pending')
                ->count();
            $pendingStaffLeaves = StudentLeaveRequest::whereHas('student', fn($q) => $q->where('role', 'teacher'))
                ->where('status', 'pending')
                ->count();

            // Today's key events / exams / meetings
            $todayEvents = AcademyEvent::whereDate('event_datetime', $today)->take(3)->get();
            $todayExams = SchoolExamSchedule::whereDate('exam_date', $today)->with(['subject', 'academicClass'])->take(3)->get();
            $todayHoliday = AcademicHoliday::whereDate('holiday_date', $today)->first();

            // 2. ADMISSIONS PIPELINE
            $admissionsThisWeek = User::where('role', 'student')->where('created_at', '>=', $startOfWeek)->count();
            $admissionsThisMonth = User::where('role', 'student')->where('created_at', '>=', $startOfMonth)->count();
            $pendingReviews = User::where('role', 'student')->where('is_approved', false)->count();
            $approvedStudents = User::where('role', 'student')->where('is_approved', true)->count();
            $stuckApplications = User::where('role', 'student')->where('is_approved', false)->where('created_at', '<=', now()->subDays(7))->count();
            $missingClassStudents = User::where('role', 'student')->where('is_approved', true)->whereNull('current_class_id')->count();
            $totalStudentSignups = User::where('role', 'student')->count();
            $conversionRate = $totalStudentSignups > 0 ? round(($approvedStudents / $totalStudentSignups) * 100, 1) : 0.0;

            // 3. FEES & FINANCE
            $feesCollectedToday = (float) FeePayment::whereDate('paid_at', $today)->sum('amount');
            $feesCollectedThisMonth = (float) FeePayment::where('paid_at', '>=', $startOfMonth)->sum('amount');
            $feesCollectedThisTerm = (float) FeePayment::where('paid_at', '>=', $startOfTerm)->sum('amount');

            $lastMonthStart = now()->subMonth()->startOfMonth();
            $lastMonthEnd = now()->subMonth()->endOfMonth();
            $feesCollectedLastMonth = (float) FeePayment::whereBetween('paid_at', [$lastMonthStart, $lastMonthEnd])->sum('amount');
            $feeTrendPct = $feesCollectedLastMonth > 0 
                ? round((($feesCollectedThisMonth - $feesCollectedLastMonth) / $feesCollectedLastMonth) * 100, 1) 
                : 0.0;

            $outstandingInvoices = FeeInvoice::whereIn('status', ['unpaid', 'partial', 'partially_paid']);
            $totalOutstandingAmount = (float) ((clone $outstandingInvoices)->selectRaw('SUM(total_due - paid_amount) as outstanding')->value('outstanding') ?? 0);
            $outstandingStudentCount = (clone $outstandingInvoices)->distinct('student_id')->count('student_id');

            $defaultersCount = FeeInvoice::whereIn('status', ['unpaid', 'partial', 'partially_paid'])
                ->where('due_date', '<', $today)
                ->distinct('student_id')
                ->count('student_id');

            $studentsWithoutInvoice = User::where('role', 'student')
                ->where('is_approved', true)
                ->whereNotNull('current_class_id')
                ->whereDoesntHave('feeInvoices', fn($q) => $q->where('created_at', '>=', $startOfTerm))
                ->count();

            // 4. ACADEMIC OPERATIONS
            $classesWithoutAttendanceToday = $allActiveClasses->filter(function ($c) use ($todayClassIdsWithAttendance) {
                return !in_array($c->id, $todayClassIdsWithAttendance);
            });

            $unassignedClassTeachers = AcademicClass::whereNull('class_teacher_id')->count();
            $unassignedSubjects = AcademicSubject::whereNull('teacher_id')->count();

            $upcomingExams = SchoolExamTerm::where('status', 'scheduled')
                ->orWhere('end_date', '>=', $today)
                ->withCount('examSchedules')
                ->orderBy('start_date')
                ->take(3)
                ->get();

            $totalCurriculumTopics = CurriculumTopic::count();
            $completedCurriculumTopics = CurriculumTopic::where('status', 'completed')->count();
            $syllabusProgressRate = $totalCurriculumTopics > 0 
                ? round(($completedCurriculumTopics / $totalCurriculumTopics) * 100, 1) 
                : 0.0;

            // 5. INFIX CORE ENTITY SUMMARY (Students, Teachers, Parents, Staff)
            $totalStudents = User::where('role', 'student')->count();
            $totalTeachers = User::where('role', 'teacher')->count();
            $totalParents = User::where('role', 'parent')->count();
            $totalStaff = User::whereIn('role', ['admin', 'staff', 'super_admin'])->count();

            // Infix Operational Modules
            $totalBooks = (int) (LibraryBook::sum('quantity') ?: LibraryBook::count());
            $totalIssuedBooks = BookIssue::where('status', 'issued')->count();
            $totalVehicles = TransportVehicle::where('is_active', true)->count();
            $totalRoutes = TransportRoute::where('is_active', true)->count();
            $totalDormitories = Dormitory::count();
            $totalDormitoryRooms = DormitoryRoom::count();
            $totalBedCapacity = (int) DormitoryRoom::sum('number_of_beds');

            // Infix Treasury & Accounts
            $monthlyIncome = (float) SchoolIncome::where('date', '>=', $startOfMonth)->sum('amount');
            $monthlyExpense = (float) SchoolExpense::where('date', '>=', $startOfMonth)->sum('amount');
            $monthlyNetCashflow = $monthlyIncome - $monthlyExpense;
            $todayIncome = (float) SchoolIncome::whereDate('date', $today)->sum('amount');
            $todayExpense = (float) SchoolExpense::whereDate('date', $today)->sum('amount');
            $todayVisitorsCount = FrontOfficeVisitor::whereDate('date', $today)->count();

            // Infix Calendar Events & Holidays
            $upcomingHolidays = AcademicHoliday::where('holiday_date', '>=', $today)
                ->orderBy('holiday_date')
                ->take(5)
                ->get();
            $upcomingEvents = AcademyEvent::where('event_datetime', '>=', now())
                ->orderBy('event_datetime')
                ->take(5)
                ->get();

            $newAdmissionsTerm = User::where('role', 'student')->where('created_at', '>=', $startOfTerm)->count();
            $withdrawnCount = ClassEnrollment::whereIn('status', ['withdrawn', 'transferred'])->count();
            $newTeachersMonth = User::where('role', 'teacher')->where('created_at', '>=', $startOfMonth)->count();
            $unapprovedStaff = User::where('role', 'teacher')->where('is_approved', false)->count();

            $classesSummary = AcademicClass::withCount('students')
                ->orderBy('grade_level')
                ->orderBy('stream')
                ->orderBy('section')
                ->take(6)
                ->get();

            // 6. LEAVE & ATTENDANCE TRENDS
            $approvedLeavesThisWeek = StudentLeaveRequest::where('status', 'approved')
                ->where('updated_at', '>=', $startOfWeek)
                ->count();

            $lastWeekStart = now()->subWeek()->startOfWeek();
            $lastWeekEnd = now()->subWeek()->endOfWeek();
            $lastWeekSessions = AttendanceSession::whereBetween('session_date', [$lastWeekStart, $lastWeekEnd])->with('records')->get();
            $lastWeekPresent = 0;
            $lastWeekTotal = 0;
            foreach ($lastWeekSessions as $lws) {
                $lastWeekPresent += $lws->records->whereIn('status', ['present', 'late'])->count();
                $lastWeekTotal += $lws->records->count();
            }
            $lastWeekAttendanceRate = $lastWeekTotal > 0 ? round(($lastWeekPresent / $lastWeekTotal) * 100, 1) : 0.0;

            $chronicAbsenteesCount = DB::table('attendance_records')
                ->select('student_id')
                ->groupBy('student_id')
                ->havingRaw('COUNT(CASE WHEN status = "absent" THEN 1 END) >= 3')
                ->count();

            // 7. CIRCULARS & COMMUNICATION
            $recentCirculars = AcademyNotice::latest('published_at')->take(4)->get();
            $draftCircularsCount = AcademyNotice::whereNull('published_at')->count();
            $parentAccountsLinked = User::where('role', 'student')->whereNotNull('parent_id')->count();
            $parentEngagementRate = $totalStudents > 0 ? round(($parentAccountsLinked / $totalStudents) * 100, 1) : 0.0;

            // 8. ALERTS & ANOMALIES
            $recentRejections = AttendanceRejectionLog::latest('attempted_at')->take(4)->get();
            $rejectionsTodayCount = AttendanceRejectionLog::whereDate('attempted_at', $today)->count();

            // 9. RECENT ACTIVITY FEED
            $recentFeePayments = FeePayment::with(['student.currentClass', 'feeInvoice'])->latest('paid_at')->take(6)->get();
            $recentAudits = TeacherAssignmentAudit::latest()->take(4)->get();
            $recentLeaveActions = StudentLeaveRequest::with('student:id,name')
                ->where('status', '!=', 'pending')
                ->latest('updated_at')
                ->take(4)
                ->get();

            // 10. VISUAL ANALYTICS GRAPH DATASETS
            $trendDays = collect(range(6, 0))->map(fn($offset) => now()->subDays($offset)->toDateString());
            $attendanceTrendChart = [
                'labels' => $trendDays->map(fn($d) => \Carbon\Carbon::parse($d)->format('D, d M'))->toArray(),
                'present' => [],
                'absent' => [],
                'excused' => [],
                'rate' => [],
            ];

            foreach ($trendDays as $dayStr) {
                $daySessions = AttendanceSession::whereDate('session_date', $dayStr)->with('records')->get();
                $pres = 0; $abs = 0; $exc = 0;
                foreach ($daySessions as $ds) {
                    $pres += $ds->records->whereIn('status', ['present', 'late'])->count();
                    $abs += $ds->records->where('status', 'absent')->count();
                    $exc += $ds->records->where('status', 'excused')->count();
                }
                $tot = $pres + $abs + $exc;
                $attendanceTrendChart['present'][] = $pres;
                $attendanceTrendChart['absent'][] = $abs;
                $attendanceTrendChart['excused'][] = $exc;
                $attendanceTrendChart['rate'][] = $tot > 0 ? round(($pres / $tot) * 100, 1) : 0;
            }

            $financialMonthLabels = [];
            $financialIncomeData = [];
            $financialExpenseData = [];
            $financialFeesData = [];

            for ($offset = 5; $offset >= 0; $offset--) {
                $mStart = now()->subMonths($offset)->startOfMonth();
                $mEnd = now()->subMonths($offset)->endOfMonth();
                $financialMonthLabels[] = $mStart->format('M');
                $financialIncomeData[] = round((float) SchoolIncome::whereBetween('date', [$mStart->toDateString(), $mEnd->toDateString()])->sum('amount'), 2);
                $financialExpenseData[] = round((float) SchoolExpense::whereBetween('date', [$mStart->toDateString(), $mEnd->toDateString()])->sum('amount'), 2);
                $financialFeesData[] = round((float) FeePayment::whereBetween('paid_at', [$mStart, $mEnd])->sum('amount'), 2);
            }

            $financialTrendChart = [
                'labels' => $financialMonthLabels,
                'income' => $financialIncomeData,
                'expenses' => $financialExpenseData,
                'fees' => $financialFeesData,
            ];

            $defaultersAmount = (float) FeeInvoice::where(function ($q) use ($today) {
                $q->where('status', 'overdue')
                  ->orWhere(function ($sub) use ($today) {
                      $sub->whereIn('status', ['unpaid', 'partial', 'partially_paid'])
                          ->where('due_date', '<', $today);
                  });
            })->sum('total_due') - (float) FeeInvoice::where(function ($q) use ($today) {
                $q->where('status', 'overdue')
                  ->orWhere(function ($sub) use ($today) {
                      $sub->whereIn('status', ['unpaid', 'partial', 'partially_paid'])
                          ->where('due_date', '<', $today);
                  });
            })->sum('paid_amount');

            $feeDistributionChart = [
                'labels' => ['Collected (Mo)', 'Outstanding Balance', 'Defaulters Overdue'],
                'data' => [
                    round($feesCollectedThisMonth, 2),
                    round(max(0, $totalOutstandingAmount - $defaultersAmount), 2),
                    round(max(0, $defaultersAmount), 2),
                ],
            ];

            $admissionsChart = [
                'labels' => ['Monthly Inflow', 'Approved Scholars', 'Stuck (>7d)', 'Pending Review'],
                'data' => [$admissionsThisMonth, $approvedStudents, $stuckApplications, $pendingReviews],
            ];

            $classCapacityChart = [
                'labels' => $classesSummary->pluck('display_name')->toArray(),
                'enrolled' => $classesSummary->pluck('students_count')->toArray(),
                'capacity' => $classesSummary->pluck('capacity')->toArray(),
            ];

            // Legacy keys for complete backward-compatibility
            $data['totalStudents'] = $totalStudents;
            $data['totalTeachers'] = $totalTeachers;
            $data['totalParents'] = $totalParents;
            $data['totalStaff'] = $totalStaff;
            $data['totalBooks'] = $totalBooks;
            $data['totalCourses'] = Course::count();
            $data['activeCourses'] = Course::where('status', 'active')->count();
            $data['totalEnrollments'] = Enrollment::count();
            $data['totalBatches'] = Batch::count();
            $data['totalClasses'] = AcademicClass::count();
            $data['totalDpps'] = DailyDpp::count();
            $data['totalDppAttempts'] = DppSubmission::count();
            $data['totalHomeworkTasks'] = HomeworkTask::count();
            $data['totalAttendanceSessions'] = AttendanceSession::count();
            $data['todayAttendanceSessions'] = $todaySessions->count();
            $data['recentEnrollments'] = Enrollment::with(['student:id,name,email', 'course:id,title,code'])->latest('enrolled_at')->take(6)->get();
            $data['courses'] = Course::with(['teacher:id,name'])->withCount('enrollments')->take(6)->get();
            $data['recentSubmissions'] = Submission::with(['student:id,name', 'assignment:id,title,course_id', 'assignment.course:id,code'])->latest('submitted_at')->take(5)->get();
            $data['avgGrade'] = 0;

            // Merge all structured metrics
            $data = array_merge($data, compact(
                'allActiveClasses', 'todaySessions', 'todayPresentCount', 'todayAbsentCount', 'todayExcusedCount',
                'todayAttendanceRate', 'abnormalClasses', 'activeStaffLeaves', 'pendingStudentLeaves', 'pendingStaffLeaves',
                'todayEvents', 'todayExams', 'todayHoliday', 'admissionsThisWeek', 'admissionsThisMonth', 'pendingReviews',
                'approvedStudents', 'stuckApplications', 'missingClassStudents', 'conversionRate', 'feesCollectedToday',
                'feesCollectedThisMonth', 'feesCollectedThisTerm', 'feeTrendPct', 'totalOutstandingAmount', 'outstandingStudentCount',
                'defaultersCount', 'studentsWithoutInvoice', 'classesWithoutAttendanceToday', 'unassignedClassTeachers',
                'unassignedSubjects', 'upcomingExams', 'syllabusProgressRate', 'totalCurriculumTopics', 'completedCurriculumTopics', 'newAdmissionsTerm', 'withdrawnCount',
                'newTeachersMonth', 'unapprovedStaff', 'classesSummary', 'approvedLeavesThisWeek', 'lastWeekAttendanceRate',
                'chronicAbsenteesCount', 'recentCirculars', 'draftCircularsCount', 'parentAccountsLinked', 'parentEngagementRate',
                'recentRejections', 'rejectionsTodayCount', 'recentFeePayments', 'recentAudits', 'recentLeaveActions',
                'attendanceTrendChart', 'feeDistributionChart', 'admissionsChart', 'classCapacityChart',
                'totalParents', 'totalStaff', 'totalBooks', 'totalIssuedBooks', 'totalVehicles', 'totalRoutes',
                'totalDormitories', 'totalDormitoryRooms', 'totalBedCapacity', 'monthlyIncome', 'monthlyExpense',
                'monthlyNetCashflow', 'todayIncome', 'todayExpense', 'todayVisitorsCount', 'upcomingHolidays',
                'upcomingEvents', 'financialTrendChart'
            ));
        } elseif ($user->isTeacher()) {
            $taughtCourses = Course::where('teacher_id', $user->id)
                ->withCount('enrollments')
                ->with('schedules')
                ->get();
            $courseIds = $taughtCourses->pluck('id');

            $data['taughtCourses'] = $taughtCourses;
            $data['totalStudentsTaught'] = Enrollment::whereIn('course_id', $courseIds)->distinct('student_id')->count();

            // Coaching Academy Faculty Metrics
            $data['totalBatches'] = Batch::where('is_active', true)->count();
            $data['totalDppsCreated'] = DailyDpp::where('teacher_id', $user->id)->count();
            $data['pendingHomeworkChecks'] = HomeworkSubmission::where('status', 'submitted')->count();
            $data['todayAttendanceCount'] = AttendanceSession::where('teacher_id', $user->id)->whereDate('session_date', now()->toDateString())->count();
            $data['pendingBloomReviews'] = BloomAssignment::where('status', 'submitted')->count();

            $data['schedules'] = Schedule::whereIn('course_id', $courseIds)
                ->with('course:id,title,code')
                ->orderBy('start_time')
                ->get();

            $data['pendingSubmissions'] = Submission::whereHas('assignment', function ($q) use ($user) {
                    $q->where('teacher_id', $user->id);
                })
                ->where('status', 'submitted')
                ->with(['student:id,name', 'assignment:id,title,max_points,course_id', 'assignment.course:id,code'])
                ->latest('submitted_at')
                ->get();

            $data['recentGraded'] = Submission::where('graded_by', $user->id)
                ->with(['student:id,name', 'assignment:id,title'])
                ->latest('graded_at')
                ->take(5)
                ->get();
        } else {
            // Student
            $user->load('batch.academicClass');
            $classId = $user->batch?->class_id ?? $user->classEnrollments()->first()?->class_id;

            // Coaching DPP metrics
            $dppQuery = DailyDpp::query();
            if ($classId) {
                $dppQuery->where('class_id', $classId);
            }
            $data['totalDppsCount'] = (clone $dppQuery)->count();
            $data['todayDpps'] = (clone $dppQuery)->whereDate('assigned_date', now()->toDateString())->withCount('questions')->get();
            $data['completedDppsCount'] = DppSubmission::where('student_id', $user->id)->count();
            $data['totalTestsCount'] = (clone $dppQuery)->where('type', 'test')->count();

            // Notes and Videos resource counts
            if ($classId) {
                $data['totalNotesCount'] = CurriculumNote::whereHas('topic.subject', fn($q) => $q->where('class_id', $classId))->where('is_published', true)->count();
                $data['totalVideosCount'] = \App\Models\CurriculumVideo::whereHas('topic.subject', fn($q) => $q->where('class_id', $classId))->count();
            } else {
                $data['totalNotesCount'] = CurriculumNote::where('is_published', true)->count();
                $data['totalVideosCount'] = \App\Models\CurriculumVideo::count();
            }

            // Coaching Homework metrics
            $homeworkQuery = HomeworkTask::query();
            if ($classId) {
                $homeworkQuery->where('class_id', $classId);
            }
            $data['totalHomeworkCount'] = (clone $homeworkQuery)->count();
            $submittedHwIds = HomeworkSubmission::where('student_id', $user->id)->pluck('homework_id')->toArray();
            $data['completedHomeworkCount'] = count($submittedHwIds);
            $data['pendingHomework'] = (clone $homeworkQuery)
                ->whereNotIn('id', $submittedHwIds)
                ->with(['academicClass', 'subject'])
                ->latest('assigned_date')
                ->take(4)
                ->get();

            // Attendance metrics
            $attendanceCounts = AttendanceRecord::where('student_id', $user->id)
                ->selectRaw('status, COUNT(*) as aggregate')
                ->groupBy('status')
                ->pluck('aggregate', 'status');
            $presentAttendance = (int) ($attendanceCounts['present'] ?? 0);
            $lateAttendance = (int) ($attendanceCounts['late'] ?? 0);
            $halfDayAttendance = (int) ($attendanceCounts['half_day'] ?? 0);
            $absentAttendance = (int) ($attendanceCounts['absent'] ?? 0);
            $eligibleAttendance = $presentAttendance + $lateAttendance + $halfDayAttendance + $absentAttendance;
            $data['attendanceRate'] = $eligibleAttendance > 0
                ? round((($presentAttendance + $lateAttendance + ($halfDayAttendance * 0.5)) / $eligibleAttendance) * 100, 1)
                : null;
            $data['attendanceSessionsCount'] = $eligibleAttendance;
            $data['excusedAttendanceCount'] = (int) ($attendanceCounts['excused'] ?? 0);

            // Bloom's Cognitive Activities
            $data['bloomAssignedCount'] = BloomAssignment::where('student_id', $user->id)->count();
            $data['bloomCompletedCount'] = BloomAssignment::where('student_id', $user->id)->whereIn('status', ['submitted', 'reviewed'])->count();

            // Syllabus Coverage
            if ($classId) {
                $classTopicCount = CurriculumTopic::whereHas('subject', fn($q) => $q->where('class_id', $classId))->count();
                $completedTopicCount = CurriculumTopic::whereHas('subject', fn($q) => $q->where('class_id', $classId))->where('status', 'completed')->count();
                $data['syllabusProgressRate'] = $classTopicCount > 0 ? round(($completedTopicCount / $classTopicCount) * 100, 1) : 0.0;
            } else {
                $data['syllabusProgressRate'] = 0.0;
            }

            // Legacy Course Enrollments & Schedules (Preserved for compatibility)
            $enrollments = Enrollment::where('student_id', $user->id)
                ->with(['course.teacher:id,name', 'course.schedules'])
                ->get();
            $enrolledCourseIds = $enrollments->pluck('course_id');

            $data['enrollments'] = $enrollments;
            
            // Student schedules
            $data['mySchedules'] = Schedule::whereIn('course_id', $enrolledCourseIds)
                ->with('course:id,title,code')
                ->orderBy('day_of_week')
                ->orderBy('start_time')
                ->get();

            // Assignments for enrolled courses
            $assignments = Assignment::whereIn('course_id', $enrolledCourseIds)
                ->with(['course:id,title,code', 'teacher:id,name'])
                ->orderBy('due_date')
                ->get();

            // Student's submissions
            $mySubmissions = Submission::where('student_id', $user->id)
                ->get()
                ->keyBy('assignment_id');

            $data['assignments'] = $assignments;
            $data['mySubmissions'] = $mySubmissions;

            $grades = $mySubmissions->whereNotNull('grade');
            $data['completedSubmissionsCount'] = $grades->count();
            $data['avgStudentGrade'] = $grades->count() > 0 ? round($grades->avg('grade'), 1) : 0;
        }

        return view('dashboard', $data);
    }
}
