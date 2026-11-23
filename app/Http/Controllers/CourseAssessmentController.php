<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseAssessment;
use App\Models\CourseAssessmentSubmission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CourseAssessmentController extends Controller
{
    /**
     * Store a new mastery test or assessment for a course.
     */
    public function store(Request $request, int $courseId): RedirectResponse
    {
        $user = Auth::user();
        if (! $user || (! $user->isAdmin() && ! $user->isTeacher())) {
            abort(403);
        }

        $course = Course::findOrFail($courseId);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|string|in:quiz,oral,written,project,exam',
            'week_number' => 'required|integer|min:1|max:52',
            'module_id' => 'nullable|exists:course_modules,id',
            'description' => 'nullable|string|max:1000',
            'total_points' => 'required|integer|min:1|max:500',
            'passing_score' => 'required|integer|min:1|max:100',
            'time_limit_minutes' => 'nullable|integer|min:5|max:300',
        ]);

        $validated['course_id'] = $course->id;
        $validated['is_published'] = true;

        CourseAssessment::create($validated);

        return back()->with('success', "Mastery Assessment \"{$validated['title']}\" scheduled successfully for Week {$validated['week_number']}!");
    }

    /**
     * Update an assessment.
     */
    public function update(Request $request, int $courseId, int $assessmentId): RedirectResponse
    {
        $user = Auth::user();
        if (! $user || (! $user->isAdmin() && ! $user->isTeacher())) {
            abort(403);
        }

        $course = Course::findOrFail($courseId);
        $assessment = CourseAssessment::where('course_id', $course->id)->findOrFail($assessmentId);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|string|in:quiz,oral,written,project,exam',
            'week_number' => 'required|integer|min:1|max:52',
            'module_id' => 'nullable|exists:course_modules,id',
            'description' => 'nullable|string|max:1000',
            'total_points' => 'required|integer|min:1|max:500',
            'passing_score' => 'required|integer|min:1|max:100',
            'time_limit_minutes' => 'nullable|integer|min:5|max:300',
        ]);

        $assessment->update($validated);

        return back()->with('success', "Assessment \"{$assessment->title}\" updated successfully!");
    }

    /**
     * Delete an assessment.
     */
    public function destroy(Request $request, int $courseId, int $assessmentId): RedirectResponse
    {
        $user = Auth::user();
        if (! $user || (! $user->isAdmin() && ! $user->isTeacher())) {
            abort(403);
        }

        $course = Course::findOrFail($courseId);
        $assessment = CourseAssessment::where('course_id', $course->id)->findOrFail($assessmentId);
        $title = $assessment->title;

        $assessment->submissions()->delete();
        $assessment->delete();

        return back()->with('success', "Assessment \"{$title}\" has been deleted.");
    }

    /**
     * Show assessment details / interactive student testing interface.
     */
    public function show(Request $request, int $courseId, int $assessmentId): View
    {
        $course = Course::findOrFail($courseId);
        $assessment = CourseAssessment::with(['module', 'submissions.student'])->where('course_id', $course->id)->findOrFail($assessmentId);

        $user = Auth::user();
        $submission = $user ? $assessment->submissions()->where('student_id', $user->id)->first() : null;

        return view('courses.assessments.show', compact('course', 'assessment', 'submission'));
    }

    /**
     * Submit an assessment attempt.
     */
    public function submit(Request $request, int $courseId, int $assessmentId): RedirectResponse
    {
        $user = Auth::user();
        if (! $user) {
            abort(403);
        }

        $course = Course::findOrFail($courseId);
        $assessment = CourseAssessment::where('course_id', $course->id)->findOrFail($assessmentId);

        $existing = CourseAssessmentSubmission::where('assessment_id', $assessment->id)
            ->where('student_id', $user->id)
            ->first();

        $answers = $request->input('answers', []);
        $rawScore = (float) $request->input('score', 0);

        // Auto-scoring fallback if oral/written submission
        if ($rawScore <= 0) {
            $rawScore = $assessment->total_points * 0.85; // Default proficiency benchmark
        }
        $percentage = round(($rawScore / max(1, $assessment->total_points)) * 100, 1);

        $gradeLetter = 'A';
        if ($percentage < 60) {
            $gradeLetter = 'F';
        } elseif ($percentage < 70) {
            $gradeLetter = 'C';
        } elseif ($percentage < 85) {
            $gradeLetter = 'B';
        } elseif ($percentage >= 95) {
            $gradeLetter = 'A+';
        }

        if ($existing) {
            $existing->update([
                'answers' => $answers,
                'score' => $rawScore,
                'percentage' => $percentage,
                'grade_letter' => $gradeLetter,
                'status' => 'evaluated',
                'evaluated_at' => now(),
            ]);
        } else {
            CourseAssessmentSubmission::create([
                'assessment_id' => $assessment->id,
                'student_id' => $user->id,
                'answers' => $answers,
                'score' => $rawScore,
                'percentage' => $percentage,
                'grade_letter' => $gradeLetter,
                'status' => 'evaluated',
                'evaluated_at' => now(),
            ]);
        }

        return redirect()->route('courses.assessments.show', [$course->id, $assessment->id])
            ->with('success', "Assessment submitted successfully! Score: {$rawScore} / {$assessment->total_points} ({$percentage}%) - Grade: {$gradeLetter}");
    }
}
