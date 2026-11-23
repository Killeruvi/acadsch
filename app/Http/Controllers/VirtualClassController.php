<?php

namespace App\Http\Controllers;

use App\Models\AcademicClass;
use App\Models\Batch;
use App\Models\User;
use App\Models\VirtualClass;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class VirtualClassController extends Controller
{
    /**
     * Display listing of virtual classes with status filters and stats.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $query = VirtualClass::with(['academicClass', 'batch', 'instructor']);

        // Filter by role if student
        if ($user && $user->isStudent()) {
            $classId = $user->current_class_id;
            $batchId = $user->batch_id;
            $query->where(function ($q) use ($classId, $batchId) {
                if ($classId) $q->orWhere('class_id', $classId);
                if ($batchId) $q->orWhere('batch_id', $batchId);
                $q->orWhereNull('class_id')->whereNull('batch_id'); // Open campus seminars
            });
        } elseif ($user && $user->isTeacher() && !$user->isAdmin()) {
            $query->where('instructor_id', $user->id);
        }

        // Status Filter
        $status = $request->input('status', 'all');
        if ($status === 'live') {
            $query->where('status', 'live')
                  ->orWhere(function ($q) {
                      $q->where('start_time', '<=', now())
                        ->where('end_time', '>=', now());
                  });
        } elseif ($status === 'upcoming') {
            $query->where('status', 'upcoming')
                  ->where('start_time', '>', now());
        } elseif ($status === 'completed') {
            $query->where('status', 'completed')
                  ->orWhere('end_time', '<', now());
        }

        // Host Filter
        if ($request->filled('host') && $request->input('host') !== 'all') {
            $query->where('host', $request->input('host'));
        }

        // Search Filter
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('meeting_id', 'like', "%{$search}%");
            });
        }

        $virtualClasses = $query->orderBy('start_time', 'desc')->paginate(12);

        $now = now();
        $allClasses = VirtualClass::all();
        $stats = [
            'total' => $allClasses->count(),
            'live_count' => $allClasses->filter->isLiveNow()->count(),
            'upcoming_count' => $allClasses->where('start_time', '>', $now)->count(),
            'completed_count' => $allClasses->where('end_time', '<', $now)->count(),
        ];

        $academicClasses = AcademicClass::orderBy('grade_level')->get();
        $batches = Batch::orderBy('name')->get();
        $instructors = User::whereIn('role', ['teacher', 'admin'])->orderBy('name')->get();

        return view('virtual_classes.index', compact(
            'virtualClasses',
            'stats',
            'status',
            'academicClasses',
            'batches',
            'instructors'
        ));
    }

    /**
     * Store newly created virtual class session.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if (!$user || (!$user->isAdmin() && !$user->isTeacher())) {
            abort(403, 'Unauthorized to schedule virtual classes.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'description' => 'nullable|string|max:1000',
            'class_id' => 'nullable|exists:academic_classes,id',
            'batch_id' => 'nullable|exists:batches,id',
            'host' => 'required|string|in:zoom,google_meet,jitsi,in_app,custom',
            'start_time' => 'required|date',
            'duration' => 'required|integer|min:15|max:480',
            'meeting_id' => 'nullable|string|max:100',
            'password' => 'nullable|string|max:50',
            'join_url' => 'nullable|url|max:500',
            'recording_url' => 'nullable|url|max:500',
            'capacity' => 'nullable|integer|min:1|max:500',
            'instructor_id' => 'nullable|exists:users,id',
        ]);

        $validated['instructor_id'] = $validated['instructor_id'] ?? $user->id;
        $validated['capacity'] = $validated['capacity'] ?? 100;
        
        $start = \Carbon\Carbon::parse($validated['start_time']);
        $validated['end_time'] = $start->copy()->addMinutes((int)$validated['duration']);

        // Fallback join URL if custom meeting
        if (empty($validated['join_url'])) {
            if ($validated['host'] === 'jitsi') {
                $roomName = 'paa_' . md5($validated['title'] . $start->timestamp);
                $validated['join_url'] = 'https://meet.jit.si/' . $roomName;
            } elseif ($validated['host'] === 'google_meet') {
                $validated['join_url'] = 'https://meet.google.com/new';
            }
        }

        $vc = VirtualClass::create($validated);

        return redirect()->route('virtual-classes.index')
            ->with('success', "Virtual Class '{$vc->title}' scheduled successfully!");
    }

    /**
     * Show single virtual class detail and joining lobby.
     */
    public function show(VirtualClass $virtualClass): View
    {
        $virtualClass->load(['academicClass', 'batch', 'instructor']);
        return view('virtual_classes.show', compact('virtualClass'));
    }

    /**
     * Direct join action for scholar or instructor.
     */
    public function join(VirtualClass $virtualClass): RedirectResponse|View
    {
        if ($virtualClass->join_url) {
            return redirect()->away($virtualClass->join_url);
        }

        return redirect()->route('virtual-classes.show', $virtualClass->id)
            ->with('info', 'Connecting to virtual classroom meeting...');
    }

    /**
     * Remove a scheduled virtual class.
     */
    public function destroy(VirtualClass $virtualClass): RedirectResponse
    {
        $user = Auth::user();
        if (!$user || (!$user->isAdmin() && $user->id !== $virtualClass->instructor_id)) {
            abort(403, 'Unauthorized to cancel this virtual class.');
        }

        $title = $virtualClass->title;
        $virtualClass->delete();

        return redirect()->route('virtual-classes.index')
            ->with('success', "Virtual Class '{$title}' has been cancelled.");
    }
}
