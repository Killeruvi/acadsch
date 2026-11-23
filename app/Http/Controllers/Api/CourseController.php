<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    /**
     * Display a listing of courses.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Course::with(['teacher:id,name,email'])->withCount('enrollments');
        if ($user->isTeacher()) {
            $query->where('teacher_id', $user->id);
        } elseif ($user->isStudent()) {
            $query->where('status', 'active');
        } elseif (! $user->isAdmin()) {
            $query->whereRaw('1 = 0');
        }

        if ($request->filled('category')) {
            $query->where('category', $request->query('category'));
        }

        if ($request->filled('status') && $user->isAdmin()) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('teacher_id') && $user->isAdmin()) {
            $query->where('teacher_id', $request->query('teacher_id'));
        }

        if ($request->filled('search')) {
            $search = strtolower($request->query('search'));
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(title) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(code) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(description) LIKE ?', ["%{$search}%"]);
            });
        }

        $perPage = min(100, max(1, $request->integer('per_page', 15)));
        $courses = $query->orderBy('title')->paginate($perPage);

        return response()->json($courses);
    }

    /**
     * Store a newly created course.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:courses,code',
            'description' => 'nullable|string',
            'category' => 'required|string|max:100',
            'credits' => 'required|integer|min:1|max:12',
            'capacity' => 'required|integer|min:1|max:500',
            'teacher_id' => 'nullable|exists:users,id',
            'status' => 'nullable|in:active,draft,archived',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        if ($request->user()->isTeacher()) {
            if (! empty($validated['teacher_id']) && (int) $validated['teacher_id'] !== (int) $request->user()->id) {
                abort(403, 'Teachers may create courses only under their own account.');
            }
            $validated['teacher_id'] = $request->user()->id;
        }
        $course = Course::create($validated);

        return response()->json([
            'message' => 'Course created successfully',
            'course' => $course->load('teacher:id,name,email'),
        ], 201);
    }

    /**
     * Display the specified course with its schedules and assignments.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $course = Course::with([
            'teacher:id,name,email,bio',
            'schedules',
        ])->withCount('enrollments')->findOrFail($id);

        if ($user->isTeacher()) {
            abort_unless((int) $course->teacher_id === (int) $user->id, 403);
            $course->load(['assignments' => function ($query) {
                $query->withCount('submissions');
            }]);
        } elseif ($user->isStudent()) {
            $isEnrolled = $user->enrolledCourses()->where('courses.id', $course->id)->exists();
            abort_unless($course->status === 'active' || $isEnrolled, 403);
            $course->load(['assignments' => function ($query) use ($user, $isEnrolled) {
                $query->where(function ($visibility) use ($user, $isEnrolled) {
                    $visibility->where('target_type', 'all_students')
                        ->orWhere(function ($enrolled) use ($isEnrolled) {
                            if ($isEnrolled) {
                                $enrolled->where('target_type', 'all_enrolled');
                            } else {
                                $enrolled->whereRaw('1 = 0');
                            }
                        })
                        ->orWhere(function ($specific) use ($user) {
                            $specific->where('target_type', 'specific_student')
                                ->where('student_id', $user->id);
                        });
                })->withCount('submissions');
            }]);
        } elseif ($user->isAdmin()) {
            $course->load(['assignments' => function ($query) {
                $query->withCount('submissions');
            }]);
        } else {
            abort(403);
        }

        return response()->json(['course' => $course]);
    }

    /**
     * Update the specified course.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $course = Course::findOrFail($id);
        if ($request->user()->isTeacher()) {
            abort_unless((int) $course->teacher_id === (int) $request->user()->id, 403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'code' => 'sometimes|required|string|max:50|unique:courses,code,'.$course->id,
            'description' => 'nullable|string',
            'category' => 'sometimes|required|string|max:100',
            'credits' => 'sometimes|required|integer|min:1|max:12',
            'capacity' => 'sometimes|required|integer|min:1|max:500',
            'teacher_id' => 'nullable|exists:users,id',
            'status' => 'sometimes|required|in:active,draft,archived',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        if ($request->user()->isTeacher()) {
            if (array_key_exists('teacher_id', $validated) && (int) $validated['teacher_id'] !== (int) $request->user()->id) {
                abort(403);
            }
            unset($validated['teacher_id']);
        }
        if (isset($validated['capacity'])) {
            $occupied = $course->enrollments()->where('status', 'enrolled')->count();
            if ((int) $validated['capacity'] < $occupied) {
                return response()->json(['message' => 'Capacity cannot be lower than currently enrolled students.'], 422);
            }
        }
        $course->update($validated);

        return response()->json([
            'message' => 'Course updated successfully',
            'course' => $course->fresh()->load('teacher:id,name,email'),
        ]);
    }

    /**
     * Remove the specified course.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        $course = Course::findOrFail($id);
        if ($course->enrollments()->exists() || $course->assessments()->exists() || $course->assignments()->exists()) {
            return response()->json(['message' => 'Course has academic history; archive it instead of deleting it.'], 409);
        }
        $course->delete();

        return response()->json(['message' => 'Course deleted successfully']);
    }
}
