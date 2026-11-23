<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    /** Return academy-wide metrics to admins and teacher-owned metrics to teachers. */
    public function overview(): JsonResponse
    {
        $viewer = Auth::user();
        abort_unless($viewer && in_array($viewer->role, ['admin', 'teacher'], true), 403);
        $isAdmin = $viewer->role === 'admin';

        $courseQuery = Course::query();
        if (!$isAdmin) $courseQuery->where('teacher_id', $viewer->id);
        $courseIds = (clone $courseQuery)->select('id');
        $assignmentIds = Assignment::query()->whereIn('course_id', $courseIds)->select('id');
        $submissionQuery = Submission::query()->whereIn('assignment_id', $assignmentIds);

        $studentQuery = User::query()->where('role', 'student');
        if (!$isAdmin) {
            $studentQuery->whereIn('id', Enrollment::query()->whereIn('course_id', $courseIds)->select('student_id'));
        }

        $totalStudents = (clone $studentQuery)->count();
        $totalTeachers = $isAdmin ? User::where('role', 'teacher')->count() : 1;
        $totalCourses = (clone $courseQuery)->count();
        $activeCourses = (clone $courseQuery)->where('status', 'active')->count();
        $totalEnrollments = Enrollment::whereIn('course_id', $courseIds)->count();
        $totalAssignments = Assignment::whereIn('id', $assignmentIds)->count();
        $totalSubmissions = (clone $submissionQuery)->count();
        $gradedSubmissions = (clone $submissionQuery)->where('status', 'graded')->count();

        $avgScore = DB::table('submissions')->join('assignments', 'submissions.assignment_id', '=', 'assignments.id')
            ->whereIn('assignments.id', $assignmentIds)->whereNotNull('submissions.grade')
            ->where('assignments.max_points', '>', 0)
            ->selectRaw('AVG((submissions.grade / assignments.max_points) * 100) as avg_score')->value('avg_score');

        $courseEnrollmentStats = (clone $courseQuery)->withCount('enrollments')->with('teacher:id,name')
            ->orderByDesc('enrollments_count')->limit(6)
            ->get(['id', 'title', 'code', 'category', 'capacity', 'teacher_id']);
        $categoryBreakdown = (clone $courseQuery)->select('category', DB::raw('count(*) as count'))
            ->groupBy('category')->get();
        $recentSubmissions = (clone $submissionQuery)->with([
            'student:id,name', 'assignment:id,title,course_id', 'assignment.course:id,code',
        ])->latest('submitted_at')->limit(5)->get();

        return response()->json([
            'summary' => [
                'total_students' => $totalStudents,
                'total_teachers' => $totalTeachers,
                'total_courses' => $totalCourses,
                'active_courses' => $activeCourses,
                'total_enrollments' => $totalEnrollments,
                'total_assignments' => $totalAssignments,
                'total_submissions' => $totalSubmissions,
                'graded_submissions' => $gradedSubmissions,
                'average_grade_percentage' => $avgScore !== null ? round((float) $avgScore, 1) : 0.0,
            ],
            'courses_by_category' => $categoryBreakdown,
            'top_courses_by_enrollment' => $courseEnrollmentStats,
            'recent_submissions' => $recentSubmissions,
        ]);
    }
}
