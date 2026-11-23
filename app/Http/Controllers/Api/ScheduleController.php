<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    /**
     * Display a listing of class schedules.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Schedule::with(['course:id,title,code,teacher_id', 'course.teacher:id,name']);

        if ($user->isTeacher()) {
            $query->whereHas('course', fn ($q) => $q->where('teacher_id', $user->id));
        } elseif ($user->isStudent()) {
            $query->whereHas('course', function ($q) use ($user) {
                $q->where('status', 'active')->whereHas('enrollments', fn ($eq) => $eq->where('student_id', $user->id)->where('status', 'enrolled'));
            });
        } elseif ($user->isParent()) {
            $childIds = User::childrenOf($user)->select('id');
            $query->whereHas('course', fn ($q) => $q->whereHas('enrollments', fn ($eq) => $eq->whereIn('student_id', $childIds)->where('status', 'enrolled')));
        } elseif (! $user->isAdmin()) {
            abort(403);
        }

        if ($request->filled('course_id')) {
            $query->where('course_id', $request->query('course_id'));
        }

        if ($request->filled('day_of_week')) {
            $query->where('day_of_week', $request->query('day_of_week'));
        }

        if ($request->has('page') || $request->has('per_page')) {
            $perPage = min(100, max(1, $request->integer('per_page', 20)));
            $schedules = $query->orderBy('day_of_week')
                ->orderBy('start_time')
                ->paginate($perPage);

            return response()->json($schedules);
        }

        $schedules = $query->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        return response()->json(['schedules' => $schedules]);
    }

    /**
     * Store a new class schedule.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'title' => 'required|string|max:255',
            'day_of_week' => 'required|string|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'room' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $user = $request->user();
        if ($user->isTeacher()) {
            abort_unless(Course::whereKey($validated['course_id'])->where('teacher_id', $user->id)->exists(), 403, 'You may only schedule courses you teach.');
        } elseif (! $user->isAdmin()) {
            abort(403);
        }

        $schedule = Schedule::create($validated);

        return response()->json([
            'message' => 'Class schedule added successfully',
            'schedule' => $schedule->load('course:id,title,code'),
        ], 201);
    }

    /**
     * Remove the specified schedule.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $schedule = Schedule::with('course')->findOrFail($id);
        $user = $request->user();
        abort_unless($user->isAdmin() || ($user->isTeacher() && (int) $schedule->course?->teacher_id === (int) $user->id), 403);
        $schedule->delete();

        return response()->json(['message' => 'Schedule removed successfully']);
    }
}
