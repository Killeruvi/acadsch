<?php

namespace App\Http\Controllers;

use App\Models\AcademicClass;
use App\Models\AcademicHoliday;
use App\Models\Assignment;
use App\Models\Batch;
use App\Models\Course;
use App\Models\DailyDpp;
use App\Models\HomeworkTask;
use App\Models\Schedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class WebScheduleController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $weekdays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

        $query = Schedule::with([
            'course:id,title,code,teacher_id',
            'course.teacher:id,name',
            'academicClass:id,name,section',
            'batch:id,name,shift,timing_slot',
        ]);

        if ($user->isStudent()) {
            $enrolledCourseIds = $user->enrolledCourses()->pluck('courses.id');
            $classIds = $user->classEnrollments()->pluck('class_id');
            $batchId = $user->batch_id;

            $query->where(function ($q) use ($enrolledCourseIds, $classIds, $batchId) {
                $hasFilter = false;
                if ($enrolledCourseIds->isNotEmpty()) {
                    $q->whereIn('course_id', $enrolledCourseIds);
                    $hasFilter = true;
                }
                if ($classIds->isNotEmpty()) {
                    if ($hasFilter) {
                        $q->orWhereIn('class_id', $classIds);
                    } else {
                        $q->whereIn('class_id', $classIds);
                        $hasFilter = true;
                    }
                }
                if ($batchId) {
                    if ($hasFilter) {
                        $q->orWhere('batch_id', $batchId);
                    } else {
                        $q->where('batch_id', $batchId);
                        $hasFilter = true;
                    }
                }
            });

            $courses = Course::whereIn('id', $enrolledCourseIds)->get();
        } elseif ($user->isTeacher()) {
            $courses = Course::where('teacher_id', $user->id)->get();
        } else {
            // Admin
            $courses = Course::orderBy('title')->get();
        }

        // Mode filter: all | offline | online
        $modeFilter = $request->query('mode', 'all');
        if (in_array($modeFilter, ['offline', 'online'], true)) {
            $query->where('delivery_mode', $modeFilter);
        }

        // Class filter
        $classFilter = $request->query('class_id');
        if (!empty($classFilter) && is_numeric($classFilter)) {
            $query->where('class_id', (int) $classFilter);
        }

        $allSchedules = $query->orderBy('start_time')->get();

        // Group by day of week
        $schedulesByDay = [];
        foreach ($days as $day) {
            $schedulesByDay[$day] = $allSchedules->where('day_of_week', $day)->values();
        }

        $allCourses = $courses;
        $allClasses = AcademicClass::orderBy('grade_level')->get();
        $allBatches = Batch::where('is_active', true)->orderBy('name')->get();

        // Fetch upcoming academic holidays
        $holidays = AcademicHoliday::where('holiday_date', '>=', now()->toDateString())
            ->orderBy('holiday_date')
            ->take(5)
            ->get();

        // Fetch upcoming deliverables: assignments, homeworks, and tests
        $upcomingAssignments = Assignment::with(['course:id,title,code', 'teacher:id,name'])
            ->orderBy('due_date')
            ->take(6)
            ->get();

        $upcomingHomework = HomeworkTask::with(['subject:id,name', 'teacher:id,name'])
            ->orderBy('due_date')
            ->take(6)
            ->get();

        $upcomingTests = DailyDpp::where('type', 'test')
            ->with(['academicClass:id,name', 'subject:id,name'])
            ->orderBy('assigned_date', 'desc')
            ->take(6)
            ->get();

        // Prepare JSON payload for client-side offline storage
        $offlinePayload = $allSchedules->map(function ($sch) {
            return [
                'id' => $sch->id,
                'title' => $sch->title,
                'subject_code' => $sch->course?->code ?? ($sch->academicClass?->name ?? 'ACAD'),
                'subject_name' => $sch->course?->title ?? ($sch->academicClass?->name ?? 'Academic Class'),
                'teacher_name' => $sch->course?->teacher?->name ?? 'Faculty Instructor',
                'delivery_mode' => $sch->delivery_mode ?? 'offline',
                'room' => $sch->room ?? 'TBA',
                'day_of_week' => $sch->day_of_week,
                'start_time' => substr($sch->start_time, 0, 5),
                'end_time' => substr($sch->end_time, 0, 5),
                'class_name' => $sch->academicClass?->name ?? null,
                'batch_name' => $sch->batch?->name ?? null,
                'is_cancelled' => (bool)$sch->is_cancelled,
                'cancellation_reason' => $sch->cancellation_reason,
            ];
        });

        return view('schedules.index', compact(
            'schedulesByDay',
            'days',
            'weekdays',
            'courses',
            'allCourses',
            'allClasses',
            'allBatches',
            'modeFilter',
            'classFilter',
            'holidays',
            'upcomingAssignments',
            'upcomingHomework',
            'upcomingTests',
            'offlinePayload'
        ));
    }

    public function toggleCancel(Request $request, Schedule $schedule): RedirectResponse
    {
        $user = Auth::user();
        if (!$user->isAdmin() && !$user->isTeacher()) {
            abort(403);
        }

        if ($schedule->is_cancelled) {
            $schedule->update([
                'is_cancelled' => false,
                'cancellation_reason' => null,
            ]);
            return back()->with('success', "Class schedule slot for {$schedule->day_of_week} has been restored.");
        }

        $reason = $request->input('reason', 'Cancelled by faculty instructor');
        $schedule->update([
            'is_cancelled' => true,
            'cancellation_reason' => $reason,
        ]);

        return back()->with('success', "Class schedule slot for {$schedule->day_of_week} has been marked as cancelled ({$reason}).");
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'course_id' => 'nullable|exists:courses,id',
            'class_id' => 'nullable|exists:academic_classes,id',
            'batch_id' => 'nullable|exists:batches,id',
            'delivery_mode' => 'nullable|string|in:offline,online',
            'title' => 'required|string|max:255',
            'day_of_week' => 'required|string|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'room' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        if (empty($validated['course_id']) && empty($validated['class_id'])) {
            $fallback = Course::first();
            if ($fallback) {
                $validated['course_id'] = $fallback->id;
            }
        }

        $validated['delivery_mode'] = $validated['delivery_mode'] ?? 'offline';

        Schedule::create($validated);

        $label = $validated['delivery_mode'] === 'offline' ? 'Offline classroom' : 'Online';
        return back()->with('success', "{$label} schedule slot added successfully!");
    }

    public function update(Request $request, Schedule $schedule): RedirectResponse
    {
        $user = Auth::user();
        if (!$user || (!$user->isAdmin() && !$user->isTeacher())) {
            abort(403);
        }

        $validated = $request->validate([
            'course_id' => 'nullable|exists:courses,id',
            'delivery_mode' => 'nullable|string|in:offline,online',
            'title' => 'required|string|max:255',
            'day_of_week' => 'required|string|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'room' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $schedule->update($validated);

        return back()->with('success', "Class schedule slot for {$schedule->day_of_week} updated successfully!");
    }

    public function destroy(Request $request, Schedule $schedule): RedirectResponse
    {
        $user = Auth::user();
        if (!$user || (!$user->isAdmin() && !$user->isTeacher())) {
            abort(403);
        }

        $day = $schedule->day_of_week;
        $title = $schedule->title;
        $schedule->delete();

        return back()->with('success', "Schedule slot \"{$title}\" ({$day}) has been deleted.");
    }
}
