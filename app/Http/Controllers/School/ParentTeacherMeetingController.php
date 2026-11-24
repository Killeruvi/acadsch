<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\AcademicClass;
use App\Models\AcademySetting;
use App\Models\PtmBooking;
use App\Models\PtmSchedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ParentTeacherMeetingController extends Controller
{
    /**
     * Display listing of PTM events.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $query = PtmSchedule::with(['academicClass', 'creator', 'bookings'])
            ->orderBy('meeting_date', 'desc');

        if ($user->isStudent()) {
            abort(403, 'Parent-Teacher Consultations are reserved for parents, faculty, and administrators.');
        }

        $schedules = $query->paginate(15);
        $classes = AcademicClass::orderBy('grade_level')->get();
        $teachers = User::where('role', 'teacher')->where('is_approved', true)->orderBy('name')->get();

        // If user is a parent, find their active bookings
        $myBookings = collect();
        $children = collect();
        if ($user->isParent()) {
            $myBookings = PtmBooking::where('parent_id', $user->id)
                ->with(['schedule.academicClass', 'teacher', 'student'])
                ->latest()
                ->get();

            $children = User::where('role', 'student')
                ->where(function ($q) use ($user) {
                    $q->where('parent_id', $user->id);
                    if ($user->email) {
                        $q->orWhereRaw('LOWER(guardian_email) = ?', [mb_strtolower(trim($user->email))]);
                    }
                })
                ->get();
        }

        return view('school.ptm.index', compact('schedules', 'classes', 'teachers', 'myBookings', 'children'));
    }

    /**
     * Store new PTM schedule event.
     */
    public function store(Request $request): RedirectResponse
    {
        abort_unless(Auth::user()?->isAdmin(), 403, 'Unauthorized action.');

        $data = $request->validate([
            'title' => 'required|string|max:191',
            'class_id' => 'nullable|exists:academic_classes,id',
            'meeting_date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'slot_duration_minutes' => 'required|integer|min:5|max:60',
            'mode' => 'required|in:in_person,online,hybrid',
            'meeting_venue' => 'nullable|string|max:191',
            'meeting_link' => 'nullable|url|max:500',
            'description' => 'nullable|string|max:1000',
        ]);

        $data['academic_year'] = AcademySetting::get('academic_session', date('Y'));
        $data['created_by'] = Auth::id();
        $data['status'] = 'scheduled';

        $schedule = PtmSchedule::create($data);

        return redirect()->route('school.ptm.show', $schedule->id)
            ->with('success', "Parent-Teacher Consultation '{$schedule->title}' created successfully.");
    }

    /**
     * View detailed schedule and slot matrix.
     */
    public function show(PtmSchedule $schedule): View
    {
        $user = Auth::user();
        $schedule->load(['academicClass', 'creator', 'bookings.parent', 'bookings.student', 'bookings.teacher']);

        $slots = $schedule->generateTimeSlots();

        // Relevant teachers for this PTM
        if ($schedule->class_id) {
            $teacherIds = $schedule->academicClass?->subjects()->pluck('teacher_id')->filter()->unique()->toArray() ?? [];
            if ($schedule->academicClass?->class_teacher_id) {
                $teacherIds[] = $schedule->academicClass->class_teacher_id;
            }
            $teachers = User::whereIn('id', array_unique($teacherIds))->orderBy('name')->get();
        } else {
            $teachers = User::where('role', 'teacher')->where('is_approved', true)->orderBy('name')->get();
        }

        // Organize bookings by teacher_id and slot_time
        $bookingMatrix = [];
        foreach ($schedule->bookings as $booking) {
            $timeKey = substr((string) $booking->slot_time, 0, 5);
            $bookingMatrix[$booking->teacher_id][$timeKey] = $booking;
        }

        return view('school.ptm.show', compact('schedule', 'slots', 'teachers', 'bookingMatrix'));
    }

    /**
     * Parent slot booking view.
     */
    public function bookView(PtmSchedule $schedule): View
    {
        $user = Auth::user();
        abort_unless($user->isParent() || $user->isAdmin(), 403);

        $schedule->load(['academicClass', 'bookings']);
        $slots = $schedule->generateTimeSlots();

        // Children of this parent
        $children = User::where('role', 'student')
            ->where(function ($q) use ($user) {
                $q->where('parent_id', $user->id);
                if ($user->email) {
                    $q->orWhereRaw('LOWER(guardian_email) = ?', [mb_strtolower(trim($user->email))]);
                }
            })
            ->with('currentClass.subjects.teacher')
            ->get();

        // Teachers available
        if ($schedule->class_id) {
            $teacherIds = $schedule->academicClass?->subjects()->pluck('teacher_id')->filter()->unique()->toArray() ?? [];
            if ($schedule->academicClass?->class_teacher_id) {
                $teacherIds[] = $schedule->academicClass->class_teacher_id;
            }
            $teachers = User::whereIn('id', array_unique($teacherIds))->orderBy('name')->get();
        } else {
            $teachers = User::where('role', 'teacher')->where('is_approved', true)->orderBy('name')->get();
        }

        // Booked slots lookup
        $bookedSlots = PtmBooking::where('schedule_id', $schedule->id)
            ->where('status', 'booked')
            ->get()
            ->groupBy(fn ($b) => $b->teacher_id.'_'.substr((string) $b->slot_time, 0, 5));

        return view('school.ptm.book', compact('schedule', 'slots', 'children', 'teachers', 'bookedSlots'));
    }

    /**
     * Book a specific time slot for parent and child.
     */
    public function storeBooking(Request $request, PtmSchedule $schedule): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user->isParent() || $user->isAdmin(), 403);

        $data = $request->validate([
            'student_id' => 'required|exists:users,id',
            'teacher_id' => 'required|exists:users,id',
            'slot_time' => 'required|date_format:H:i',
            'parent_notes' => 'nullable|string|max:500',
        ]);

        $parentId = $user->isParent() ? $user->id : ($request->input('parent_id') ?? $user->id);

        // Check if teacher slot is already taken
        $isTaken = PtmBooking::where('schedule_id', $schedule->id)
            ->where('teacher_id', $data['teacher_id'])
            ->where('slot_time', $data['slot_time'])
            ->where('status', 'booked')
            ->exists();

        if ($isTaken) {
            return back()->withErrors(['slot_time' => 'This slot has already been reserved. Please pick another available time slot.']);
        }

        // Check if student already booked with this teacher in this event
        $alreadyBookedWithTeacher = PtmBooking::where('schedule_id', $schedule->id)
            ->where('student_id', $data['student_id'])
            ->where('teacher_id', $data['teacher_id'])
            ->where('status', 'booked')
            ->exists();

        if ($alreadyBookedWithTeacher) {
            return back()->withErrors(['teacher_id' => 'An appointment for this student is already confirmed with this faculty member.']);
        }

        PtmBooking::create([
            'schedule_id' => $schedule->id,
            'parent_id' => $parentId,
            'student_id' => $data['student_id'],
            'teacher_id' => $data['teacher_id'],
            'slot_time' => $data['slot_time'],
            'status' => 'booked',
            'parent_notes' => $data['parent_notes'] ?? null,
        ]);

        return redirect()->route('school.ptm.index')
            ->with('success', 'Parent-Teacher consultation appointment confirmed successfully!');
    }

    /**
     * Teacher records consultation feedback and action items.
     */
    public function updateFeedback(Request $request, PtmBooking $booking): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user->isAdmin() || $user->id === $booking->teacher_id, 403);

        $data = $request->validate([
            'status' => 'required|in:booked,completed,no_show,cancelled',
            'teacher_feedback' => 'nullable|string|max:1000',
            'action_items' => 'nullable|string|max:1000',
        ]);

        $booking->update($data);

        return back()->with('success', 'Consultation feedback and action items recorded.');
    }

    /**
     * Cancel appointment.
     */
    public function cancelBooking(PtmBooking $booking): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user->isAdmin() || $user->id === $booking->parent_id || $user->id === $booking->teacher_id, 403);

        $booking->update(['status' => 'cancelled']);

        return back()->with('success', 'PTM appointment has been cancelled.');
    }

    /**
     * Delete or cancel PTM schedule event.
     */
    public function destroy(PtmSchedule $schedule): RedirectResponse
    {
        abort_unless(Auth::user()?->isAdmin(), 403);

        $schedule->delete();

        return redirect()->route('school.ptm.index')
            ->with('success', 'PTM consultation schedule removed.');
    }
}
