<?php

namespace App\Http\Controllers;

use App\Models\AcademicClass;
use App\Models\Assignment;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Batch;
use App\Models\BloomAssignment;
use App\Models\Course;
use App\Models\DailyDpp;
use App\Models\DppSubmission;
use App\Models\Enrollment;
use App\Models\HomeworkSubmission;
use App\Models\HomeworkTask;
use App\Models\Submission;
use App\Models\SchoolReportCard;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class WebAnalyticsController extends Controller
{
    public function index(): View
    {
        $viewer = Auth::user();
        abort_unless($viewer && in_array($viewer->role, ['admin', 'teacher'], true), 403);
        $isAdmin = $viewer->role === 'admin';

        // A teacher's reporting boundary is the union of their assigned classes,
        // subjects, timetable slots and classes for which they created learning work.
        $classIds = collect();
        if (!$isAdmin) {
            $classIds = AcademicClass::query()->where('class_teacher_id', $viewer->id)->pluck('id')
                ->merge(DB::table('academic_subjects')->where('teacher_id', $viewer->id)->pluck('class_id'))
                ->merge(DB::table('school_period_timetables')->where('teacher_id', $viewer->id)->pluck('class_id'))
                ->merge(DailyDpp::query()->where('teacher_id', $viewer->id)->pluck('class_id'))
                ->merge(HomeworkTask::query()->where('teacher_id', $viewer->id)->pluck('class_id'))
                ->filter()->unique()->values();
        }

        $studentQuery = User::query()->where('role', 'student');
        if (!$isAdmin) {
            $studentQuery->where(function ($query) use ($classIds) {
                if ($classIds->isNotEmpty()) {
                    $query->whereIn('current_class_id', $classIds)
                        ->orWhereIn('batch_id', Batch::query()->whereIn('class_id', $classIds)->select('id'))
                        ->orWhereIn('id', DB::table('class_enrollments')->whereIn('class_id', $classIds)
                            ->where('status', 'active')->select('student_id'));
                } else {
                    $query->whereRaw('1 = 0');
                }
            });
        }
        $studentIds = (clone $studentQuery)->select('users.id');
        $totalStudents = (clone $studentQuery)->count();
        $totalTeachers = $isAdmin ? User::where('role', 'teacher')->count() : 1;

        $dppQuery = DailyDpp::query();
        $dppSubmissionQuery = DppSubmission::query()->whereIn('student_id', $studentIds);
        $homeworkQuery = HomeworkTask::query();
        $homeworkSubmissionQuery = HomeworkSubmission::query()->whereIn('student_id', $studentIds);
        $attendanceSessionQuery = AttendanceSession::query();
        $attendanceRecordQuery = AttendanceRecord::query()->whereIn('student_id', $studentIds);
        $bloomQuery = BloomAssignment::query()->whereIn('student_id', $studentIds);

        if (!$isAdmin) {
            $dppQuery->where(function ($q) use ($viewer, $classIds) {
                $q->where('teacher_id', $viewer->id);
                if ($classIds->isNotEmpty()) $q->orWhereIn('class_id', $classIds);
            });
            $homeworkQuery->where(function ($q) use ($viewer, $classIds) {
                $q->where('teacher_id', $viewer->id);
                if ($classIds->isNotEmpty()) $q->orWhereIn('class_id', $classIds);
            });
            $attendanceSessionQuery->where(function ($q) use ($viewer, $classIds) {
                $q->where('teacher_id', $viewer->id);
                if ($classIds->isNotEmpty()) $q->orWhereIn('class_id', $classIds);
            });
            $attendanceRecordQuery->whereHas('session', function ($q) use ($viewer, $classIds) {
                $q->where('teacher_id', $viewer->id);
                if ($classIds->isNotEmpty()) $q->orWhereIn('class_id', $classIds);
            });
            $bloomQuery->whereHas('lesson', function ($q) use ($viewer, $classIds) {
                $q->where('teacher_id', $viewer->id);
                if ($classIds->isNotEmpty()) $q->orWhereIn('class_id', $classIds);
            });
        }

        $totalDpps = (clone $dppQuery)->count();
        $totalDppSubmissions = (clone $dppSubmissionQuery)->whereHas('dpp', function ($q) use ($dppQuery) {
            // Keep DPP submission metrics aligned to the visible DPP set.
            $q->whereIn('id', (clone $dppQuery)->select('id'));
        })->count();
        $avgDppScore = (clone $dppSubmissionQuery)->whereHas('dpp', function ($q) use ($dppQuery) {
            $q->whereIn('id', (clone $dppQuery)->select('id'));
        })->avg('percentage');
        $avgDppScore = $avgDppScore !== null ? round((float) $avgDppScore, 1) : 0.0;

        $totalHomeworkTasks = (clone $homeworkQuery)->count();
        $visibleHomeworkIds = (clone $homeworkQuery)->select('id');
        $homeworkSubmissionQuery->whereIn('homework_id', $visibleHomeworkIds);
        $totalHomeworkSubmissions = (clone $homeworkSubmissionQuery)->count();
        $homeworkCheckedCount = (clone $homeworkSubmissionQuery)->whereIn('status', ['checked', 'graded'])->count();

        $attendanceSessionIds = (clone $attendanceSessionQuery)->select('id');
        $attendanceRecordQuery->whereIn('session_id', $attendanceSessionIds);
        $totalAttendanceSessions = (clone $attendanceSessionQuery)->count();
        $attendanceCounts = (clone $attendanceRecordQuery)->selectRaw("COUNT(*) as total, SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present, SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late, SUM(CASE WHEN status = 'excused' THEN 1 ELSE 0 END) as excused, SUM(CASE WHEN status = 'half_day' THEN 1 ELSE 0 END) as half_day")->first();
        $attendanceTotal = (int) ($attendanceCounts->total ?? 0);
        $eligible = max(0, $attendanceTotal - (int) ($attendanceCounts->excused ?? 0));
        $attendanceNumerator = (int) ($attendanceCounts->present ?? 0) + (int) ($attendanceCounts->late ?? 0) + ((int) ($attendanceCounts->half_day ?? 0) * 0.5);
        $attendanceRate = $eligible > 0 ? round(min(100, ($attendanceNumerator / $eligible) * 100), 1) : 0.0;

        // Only published report cards contribute to student-facing performance analytics.
        $reportCardQuery = SchoolReportCard::query()->where('is_published', true);
        if (!$isAdmin) $reportCardQuery->whereIn('class_id', $classIds)->whereIn('student_id', $studentIds);
        $publishedReportCards = (clone $reportCardQuery)->count();
        $averageReportPercentage = (clone $reportCardQuery)->avg('percentage');
        $averageReportPercentage = $averageReportPercentage !== null ? round((float) $averageReportPercentage, 1) : null;

        $bloomLevels = [
            1 => ['name' => 'Remember', 'count' => 0, 'bg' => 'bg-sky-500', 'text' => 'text-sky-700'],
            2 => ['name' => 'Understand', 'count' => 0, 'bg' => 'bg-teal-500', 'text' => 'text-teal-700'],
            3 => ['name' => 'Apply', 'count' => 0, 'bg' => 'bg-emerald-500', 'text' => 'text-emerald-700'],
            4 => ['name' => 'Analyze', 'count' => 0, 'bg' => 'bg-amber-500', 'text' => 'text-amber-700'],
            5 => ['name' => 'Evaluate', 'count' => 0, 'bg' => 'bg-orange-500', 'text' => 'text-orange-700'],
            6 => ['name' => 'Create', 'count' => 0, 'bg' => 'bg-rose-500', 'text' => 'text-rose-700'],
        ];
        $bloomCounts = (clone $bloomQuery)->select('bloom_level', DB::raw('count(*) as count'))->groupBy('bloom_level')->pluck('count', 'bloom_level');
        $totalBloomAssignments = (clone $bloomQuery)->count();
        foreach ($bloomCounts as $level => $count) {
            if (isset($bloomLevels[$level])) $bloomLevels[$level]['count'] = (int) $count;
        }

        $topAchievers = (clone $studentQuery)->with('batch:id,name')->withCount('dppSubmissions')
            ->orderByDesc('xp_points')->orderByDesc('streak_count')->limit(5)->get();

        $batchQuery = Batch::query()->with('academicClass:id,name')->withCount(['students' => fn ($q) => $q->where('role', 'student')]);
        if (!$isAdmin) $batchQuery->whereIn('class_id', $classIds);
        $batches = $batchQuery->get();
        $attendanceByClass = DB::table('attendance_records')
            ->join('attendance_sessions', 'attendance_sessions.id', '=', 'attendance_records.session_id')
            ->whereIn('attendance_records.student_id', $studentIds)
            ->whereIn('attendance_sessions.class_id', $batches->pluck('class_id')->filter()->unique())
            ->select('attendance_sessions.class_id')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN attendance_records.status = 'present' THEN 1 ELSE 0 END) as present")
            ->selectRaw("SUM(CASE WHEN attendance_records.status = 'late' THEN 1 ELSE 0 END) as late")
            ->selectRaw("SUM(CASE WHEN attendance_records.status = 'excused' THEN 1 ELSE 0 END) as excused")
            ->selectRaw("SUM(CASE WHEN attendance_records.status = 'half_day' THEN 1 ELSE 0 END) as half_day")
            ->groupBy('attendance_sessions.class_id')->get()->keyBy('class_id');
        $batchPerformance = $batches->map(function ($batch) use ($attendanceByClass) {
            $counts = $attendanceByClass->get($batch->class_id);
            $total = (int) ($counts->total ?? 0);
            $eligibleCount = max(0, $total - (int) ($counts->excused ?? 0));
            $effective = (int) ($counts->present ?? 0) + (int) ($counts->late ?? 0) + ((int) ($counts->half_day ?? 0) * 0.5);
            $rate = $eligibleCount > 0 ? round(min(100, ($effective / $eligibleCount) * 100), 1) : 0.0;
            return [
                'id' => $batch->id, 'name' => $batch->name,
                'class_name' => $batch->academicClass?->name ?? 'Class',
                'shift' => $batch->shift, 'timing' => $batch->timing_slot,
                'students_count' => $batch->students_count, 'attendance_rate' => $rate,
                'rate' => $rate,
            ];
        });

        $courseQuery = Course::query();
        if (!$isAdmin) $courseQuery->where('teacher_id', $viewer->id);
        $totalCourses = (clone $courseQuery)->count();
        $courses = (clone $courseQuery)->withCount('enrollments')->withCount('assignments')->with('teacher:id,name')->get();
        $courseIds = (clone $courseQuery)->select('id');
        $totalEnrollments = Enrollment::whereIn('course_id', $courseIds)->count();
        $totalAssignments = Assignment::whereIn('course_id', $courseIds)->count();
        $visibleAssignmentIds = Assignment::whereIn('course_id', $courseIds)->select('id');
        $submissionQuery = Submission::whereIn('assignment_id', $visibleAssignmentIds);
        $totalSubmissions = (clone $submissionQuery)->count();
        $gradedSubmissions = (clone $submissionQuery)->where('status', 'graded')->count();
        $avgScore = DB::table('submissions')->join('assignments', 'submissions.assignment_id', '=', 'assignments.id')
            ->whereIn('assignments.id', $visibleAssignmentIds)->whereNotNull('submissions.grade')
            ->where('assignments.max_points', '>', 0)
            ->selectRaw('AVG((submissions.grade / assignments.max_points) * 100) as avg_score')->value('avg_score');
        $averageGrade = $avgScore !== null ? round((float) $avgScore, 1) : 0.0;
        $categories = (clone $courseQuery)->select('category', DB::raw('count(*) as count'))->groupBy('category')->get();
        $recentGraded = (clone $submissionQuery)->where('status', 'graded')
            ->with(['student:id,name', 'assignment:id,title,max_points', 'grader:id,name'])
            ->latest('graded_at')->limit(6)->get();

        $studentPerformanceRows = DB::table('submissions')
            ->join('assignments', 'assignments.id', '=', 'submissions.assignment_id')
            ->whereIn('assignments.id', $visibleAssignmentIds)
            ->whereIn('submissions.student_id', $studentIds)
            ->whereNotNull('submissions.grade')
            ->groupBy('submissions.student_id')
            ->select('submissions.student_id')
            ->selectRaw('COUNT(*) as graded_submissions_count, AVG(submissions.grade) as average_score')
            ->get()->keyBy('student_id');
        $studentPerformance = (clone $studentQuery)->withCount('enrollments')->orderBy('name')->limit(100)->get()
            ->map(function ($student) use ($studentPerformanceRows) {
                $stats = $studentPerformanceRows->get($student->id);
                return [
                    'id' => $student->id, 'name' => $student->name,
                    'enrollments_count' => $student->enrollments_count,
                    'submissions_count' => (int) ($stats->graded_submissions_count ?? 0),
                    'average_score' => isset($stats->average_score) ? round((float) $stats->average_score, 1) : null,
                ];
            });

        $totalBatches = $isAdmin ? Batch::count() : $batchPerformance->count();
        $totalClasses = $isAdmin ? AcademicClass::count() : $classIds->count();

        return view('analytics.index', compact(
            'totalStudents', 'totalTeachers', 'totalBatches', 'totalClasses',
            'totalDpps', 'totalDppSubmissions', 'avgDppScore',
            'totalHomeworkTasks', 'totalHomeworkSubmissions', 'homeworkCheckedCount',
            'totalAttendanceSessions', 'attendanceRate', 'publishedReportCards', 'averageReportPercentage',
            'bloomLevels', 'totalBloomAssignments', 'topAchievers', 'batchPerformance',
            'totalCourses', 'totalEnrollments', 'totalAssignments', 'totalSubmissions',
            'gradedSubmissions', 'averageGrade', 'courses', 'categories', 'recentGraded',
            'studentPerformance'
        ));
    }
}
