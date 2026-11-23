<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Submission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SubmissionController extends Controller
{
    /**
     * List submissions for a specific assignment (teacher/admin).
     */
    public function index(Request $request, int $assignmentId): JsonResponse
    {
        $assignment = Assignment::findOrFail($assignmentId);
        abort_unless($request->user() && $request->user()->can('grade', $assignment), 403);

        $query = $assignment->submissions()
            ->with(['student:id,name,email', 'grader:id,name'])
            ->latest('submitted_at');

        $perPage = min(100, max(1, $request->integer('per_page', 20)));
        $submissions = $query->paginate($perPage);

        return response()->json([
            'assignment' => $assignment->only(['id', 'title', 'max_points', 'due_date']),
            'submissions' => $submissions,
        ]);
    }

    /**
     * Store or update student's submission.
     */
    public function store(Request $request, int $assignmentId): JsonResponse
    {
        $assignment = Assignment::findOrFail($assignmentId);
        abort_unless($request->user() && $request->user()->can('submit', $assignment), 403);
        $studentId = $request->user()->id;

        // Verify student is enrolled in this assignment's course
        $isEnrolled = $request->user()->enrolledCourses()->where('courses.id', $assignment->course_id)->exists();
        if (! $isEnrolled && $assignment->target_type === 'all_enrolled') {
            throw ValidationException::withMessages([
                'assignment' => ['You must be enrolled in the course to submit this assignment.'],
            ]);
        }

        $validated = $request->validate([
            'content' => 'required|string|max:50000',
            'attachment_url' => ['nullable', 'url', 'max:2048', 'regex:/^https:\/\//i'],
        ]);

        $isLate = now()->isAfter($assignment->due_date);

        $submission = Submission::updateOrCreate(
            [
                'assignment_id' => $assignment->id,
                'student_id' => $studentId,
            ],
            [
                'content' => $validated['content'],
                'attachment_url' => $validated['attachment_url'] ?? null,
                'submitted_at' => now(),
                'status' => $isLate ? 'late' : 'submitted',
                'grade' => null,
                'feedback' => null,
                'graded_by' => null,
                'graded_at' => null,
            ]
        );

        return response()->json([
            'message' => 'Assignment submitted successfully',
            'submission' => $submission->load(['student:id,name,email']),
        ], 201);
    }

    /**
     * Grade a student submission (teacher or admin).
     */
    public function grade(Request $request, int $id): JsonResponse
    {
        $submission = Submission::with('assignment')->findOrFail($id);
        abort_unless($request->user() && $request->user()->can('grade', $submission->assignment), 403);

        $validated = $request->validate([
            'grade' => 'required|numeric|min:0|max:'.$submission->assignment->max_points,
            'feedback' => 'nullable|string',
        ]);

        $submission->update([
            'grade' => $validated['grade'],
            'feedback' => $validated['feedback'] ?? null,
            'status' => 'graded',
            'graded_by' => $request->user()->id,
            'graded_at' => now(),
        ]);

        return response()->json([
            'message' => 'Submission graded successfully',
            'submission' => $submission->fresh()->load(['student:id,name,email', 'grader:id,name']),
        ]);
    }
}
