<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Course;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssignmentController extends Controller
{
    /**
     * Display a listing of assignments.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Assignment::with(['course:id,title,code', 'teacher:id,name'])->withCount('submissions');
        if ($user->isAdmin()) {
            if ($request->filled('course_id')) {
                $query->where('course_id', $request->integer('course_id'));
            }
            if ($request->filled('teacher_id')) {
                $query->where('teacher_id', $request->integer('teacher_id'));
            }
        } elseif ($user->isTeacher()) {
            $query->where('teacher_id', $user->id);
        } elseif ($user->isStudent()) {
            $courseIds = $user->enrolledCourses()->pluck('courses.id');
            $query->where(function ($q) use ($user, $courseIds) {
                $q->where(function ($q) use ($courseIds) {
                    $q->where('target_type', 'all_enrolled')->whereIn('course_id', $courseIds);
                })
                    ->orWhere('target_type', 'all_students')
                    ->orWhere(function ($q) use ($user) {
                        $q->where('target_type', 'specific_student')->where('student_id', $user->id);
                    });
            });
        } else {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $perPage = min(100, max(1, $request->integer('per_page', 20)));

        return response()->json($query->orderBy('due_date')->paginate($perPage));
    }

    /**
     * Store a newly created assignment.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'required|date|after:now',
            'max_points' => 'required|integer|min:1|max:1000',
        ]);

        $user = $request->user();
        abort_unless($user && $user->can('create', Assignment::class), 403);
        if ($user->isTeacher() && ! Course::whereKey($validated['course_id'])->where('teacher_id', $user->id)->exists()) {
            abort(403, 'You may only create assignments for courses you teach.');
        }
        $validated['teacher_id'] = $user->id;

        $assignment = Assignment::create($validated);

        return response()->json([
            'message' => 'Assignment created successfully',
            'assignment' => $assignment->load(['course:id,title,code', 'teacher:id,name']),
        ], 201);
    }

    /**
     * Display the specified assignment.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $assignment = Assignment::with([
            'course:id,title,code',
            'teacher:id,name,email',
        ])->withCount('submissions')->findOrFail($id);

        $currentUser = $request->user();
        abort_unless($currentUser && $currentUser->can('view', $assignment), 403);
        $studentSubmission = null;

        if ($currentUser && $currentUser->isStudent()) {
            $studentSubmission = $assignment->submissions()
                ->where('student_id', $currentUser->id)
                ->first();
        }

        return response()->json([
            'assignment' => $assignment,
            'my_submission' => $studentSubmission,
        ]);
    }

    /**
     * Update the specified assignment.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $assignment = Assignment::findOrFail($id);
        $user = $request->user();

        if (! $user->can('update', $assignment)) {
            return response()->json(['message' => 'Unauthorized. You may only edit your own assignments.'], 403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'sometimes|required|date',
            'max_points' => 'sometimes|required|integer|min:1|max:1000',
        ]);

        $assignment->update($validated);

        return response()->json([
            'message' => 'Assignment updated successfully',
            'assignment' => $assignment->fresh()->load(['course:id,title,code', 'teacher:id,name']),
        ]);
    }

    /**
     * Remove the specified assignment.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $assignment = Assignment::findOrFail($id);
        $user = $request->user();

        if (! $user->can('delete', $assignment)) {
            return response()->json(['message' => 'Unauthorized. You may only delete your own assignments.'], 403);
        }

        $assignment->delete();

        return response()->json(['message' => 'Assignment deleted successfully']);
    }
}
