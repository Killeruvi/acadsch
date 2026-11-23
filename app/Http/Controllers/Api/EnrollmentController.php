<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EnrollmentController extends Controller
{
    /**
     * Display a listing of enrollments.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Enrollment::with([
            'course:id,title,code,credits,category',
            'student:id,name,email',
        ]);

        $user = $request->user();
        if ($user->isStudent()) {
            $query->where('student_id', $user->id);
        } elseif ($user->isParent()) {
            $childIds = \App\Models\User::childrenOf($user)->pluck('id');
            $query->whereIn('student_id', $childIds);
        } elseif ($user->isTeacher()) {
            $query->whereHas('course', function ($courseQuery) use ($user) {
                $courseQuery->where('teacher_id', $user->id);
            });
        } elseif (! $user->isAdmin()) {
            abort(403, 'You are not authorized to view enrollments.');
        }

        if ($request->filled('course_id')) {
            $query->where('course_id', $request->query('course_id'));
        }

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->query('student_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $perPage = min(100, max(1, $request->integer('per_page', 20)));
        $enrollments = $query->latest('enrolled_at')->paginate($perPage);

        return response()->json($enrollments);
    }

    /**
     * Enroll a student in a course.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'student_id' => 'nullable|exists:users,id',
        ]);

        $user = $request->user();
        $course = Course::findOrFail($validated['course_id']);
        $requestedStudentId = $validated['student_id'] ?? null;

        if ($user->isStudent()) {
            abort_unless(! $requestedStudentId || (int) $requestedStudentId === (int) $user->id, 403,
                'Students may only enroll themselves.');
            abort_unless($course->status === 'active', 403, 'This course is not open for enrollment.');
            $studentId = $user->id;
        } elseif ($user->isTeacher()) {
            abort_unless((int) $course->teacher_id === (int) $user->id, 403,
                'Teachers may only enroll students in courses they teach.');
            abort_unless($requestedStudentId, 422, 'A student_id is required when a teacher enrolls a student.');
            $student = \App\Models\User::whereKey($requestedStudentId)->where('role', 'student')->firstOrFail();
            $studentId = $student->id;
        } elseif ($user->isAdmin()) {
            abort_unless($requestedStudentId, 422, 'A student_id is required for administrator enrollment.');
            $student = \App\Models\User::whereKey($requestedStudentId)->where('role', 'student')->firstOrFail();
            $studentId = $student->id;
        } else {
            abort(403, 'You are not authorized to enroll students.');
        }

        $enrollment = DB::transaction(function () use ($course, $studentId) {
            // Serialize capacity checks to reduce over-enrollment during concurrent requests.
            $lockedCourse = Course::whereKey($course->id)->lockForUpdate()->firstOrFail();
            $existing = Enrollment::where('course_id', $lockedCourse->id)
                ->where('student_id', $studentId)->first();
            if ($existing) {
                return $existing;
            }

            $occupied = $lockedCourse->enrollments()->where('status', 'enrolled')->count();
            if ($occupied >= $lockedCourse->capacity) {
                throw ValidationException::withMessages([
                    'course_id' => ['This course has reached its maximum enrollment capacity.'],
                ]);
            }

            return Enrollment::create([
                'course_id' => $lockedCourse->id,
                'student_id' => $studentId,
                'status' => 'enrolled',
                'enrolled_at' => now(),
            ]);
        });

        if ($enrollment->wasRecentlyCreated === false) {
            return response()->json([
                'message' => 'Student is already enrolled in this course.',
                'enrollment' => $enrollment->load(['course', 'student:id,name,email']),
            ], 200);
        }

        return response()->json([
            'message' => 'Successfully enrolled in course',
            'enrollment' => $enrollment->load(['course', 'student:id,name,email']),
        ], 201);
    }

    /**
     * Update enrollment status or final grade.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        $enrollment = Enrollment::findOrFail($id);

        $validated = $request->validate([
            'status' => 'sometimes|required|in:enrolled,completed,dropped',
            'grade_letter' => 'nullable|string|max:5',
            'final_score' => 'nullable|numeric|min:0|max:100',
        ]);

        $enrollment->update($validated);

        return response()->json([
            'message' => 'Enrollment updated successfully',
            'enrollment' => $enrollment->fresh()->load(['course', 'student:id,name,email']),
        ]);
    }

    /**
     * Cancel / delete enrollment.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        $enrollment = Enrollment::findOrFail($id);
        $enrollment->delete();

        return response()->json(['message' => 'Enrollment removed successfully']);
    }
}
