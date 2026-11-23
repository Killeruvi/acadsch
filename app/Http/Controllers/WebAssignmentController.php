<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Submission;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class WebAssignmentController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();
        $query = Assignment::query()->with(['course:id,title,code', 'teacher:id,name', 'student:id,name'])->withCount('submissions');
        if ($user->isAdmin()) {
            $courses = Course::orderBy('title')->get();
        } elseif ($user->isTeacher()) {
            $query->where('teacher_id', $user->id);
            $courses = Course::where('teacher_id', $user->id)->orderBy('title')->get();
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
            $courses = Course::whereIn('id', $courseIds)->get();
        } elseif ($user->isParent()) {
            $childIds = User::childrenOf($user)->pluck('id');
            $courseIds = Enrollment::whereIn('student_id', $childIds)->pluck('course_id')->unique();
            $query->where(function ($q) use ($childIds, $courseIds) {
                $q->where(function ($q) use ($courseIds) {
                    $q->where('target_type', 'all_enrolled')->whereIn('course_id', $courseIds);
                })
                    ->orWhere('target_type', 'all_students')
                    ->orWhere(function ($q) use ($childIds) {
                        $q->where('target_type', 'specific_student')->whereIn('student_id', $childIds);
                    });
            });
            $courses = collect();
        } else {
            abort(403);
        }

        $assignments = $query->orderBy('due_date')->paginate(15)->withQueryString();
        if ($user->isStudent()) {
            $mySubmissions = Submission::where('student_id', $user->id)->whereIn('assignment_id', $assignments->pluck('id'))->get()->keyBy('assignment_id');
        } elseif ($user->isParent()) {
            $childIds = User::childrenOf($user)->pluck('id');
            $mySubmissions = Submission::whereIn('student_id', $childIds)->whereIn('assignment_id', $assignments->pluck('id'))->with('student:id,name')->get()->groupBy('assignment_id')->map(fn ($items) => $items->first());
        } else {
            $mySubmissions = collect();
        }
        // Student/parent views must never expose the campus-wide student directory.
        $students = ($user->isAdmin() || $user->isTeacher())
            ? User::where('role', 'student')->orderBy('name')->get(['id', 'name'])
            : collect();

        return view('assignments.index', compact('assignments', 'courses', 'mySubmissions', 'students'));
    }

    public function show(int $id): View
    {
        $assignment = Assignment::with([
            'course:id,title,code',
            'teacher:id,name',
            'student:id,name,email',
        ])->withCount('submissions')->findOrFail($id);

        $user = Auth::user();
        abort_unless($user && $user->can('view', $assignment), 403);
        if ($user->isStudent()) {
            $mySubmission = $assignment->submissions()->where('student_id', $user->id)->first();
        } elseif ($user->isParent()) {
            $childIds = User::childrenOf($user)->pluck('id');
            $mySubmission = $assignment->submissions()->whereIn('student_id', $childIds)->with('student:id,name')->first();
        } else {
            $mySubmission = null;
        }
        $canReview = $user->isAdmin() || ($user->isTeacher() && (int) $assignment->teacher_id === (int) $user->id);
        $submissions = $canReview
            ? $assignment->submissions()->with(['student:id,name,email', 'grader:id,name'])->latest('submitted_at')->paginate(10, ['*'], 'submissions_page')->withQueryString()
            : collect();

        return view('assignments.show', compact('assignment', 'mySubmission', 'submissions', 'canReview'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if (! $user || (! $user->isAdmin() && ! $user->isTeacher())) {
            abort(403, 'Unauthorized. Only teachers and administrators can assign coursework.');
        }

        $validated = $request->validate([
            'course_id' => 'nullable|exists:courses,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'required|date',
            'max_points' => 'required|integer|min:1|max:1000',
            'target_type' => 'nullable|string|in:all_enrolled,all_students,specific_student',
            'student_id' => 'nullable|required_if:target_type,specific_student|exists:users,id',
            'attachment' => 'nullable|file|mimes:pdf,doc,docx,ppt,pptx,txt,png,jpg,jpeg,webp,zip|max:25600',
        ]);

        if ($user->isTeacher() && ! empty($validated['course_id']) && ! Course::whereKey($validated['course_id'])->where('teacher_id', $user->id)->exists()) {
            abort(403, 'You may only assign work to courses you teach.');
        }
        if (($validated['target_type'] ?? $request->input('target_type', 'all_enrolled')) === 'specific_student' && ! User::whereKey($validated['student_id'] ?? $request->input('student_id'))->where('role', 'student')->exists()) {
            abort(422, 'Select a valid student account.');
        }
        $validated['target_type'] = $request->input('target_type', 'all_enrolled');
        if ($validated['target_type'] === 'all_students' && ! $user->isAdmin()) {
            abort(403, 'Only administrators may publish campus-wide assignments.');
        }
        if ($validated['target_type'] === 'all_enrolled' && empty($validated['course_id'])) {
            abort(422, 'Choose a course for an enrolled-student assignment.');
        }
        if ($validated['target_type'] === 'specific_student' && empty($validated['course_id']) && ! $user->isAdmin()) {
            abort(422, 'Choose the course associated with this personal assignment.');
        }
        $validated['teacher_id'] = Auth::id();
        $validated['student_id'] = ($validated['target_type'] === 'specific_student') ? $request->input('student_id') : null;
        if ($validated['target_type'] === 'specific_student' && ! empty($validated['course_id']) && ! Enrollment::where('student_id', $validated['student_id'])->where('course_id', $validated['course_id'])->exists()) {
            abort(422, 'The selected student is not enrolled in this course.');
        }

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $originalName = mb_substr(basename($file->getClientOriginalName()), 0, 255);
            $validated['attachment_path'] = $file->store('assignments', 'local');
            $validated['attachment_name'] = $originalName;
            $validated['attachment_type'] = $file->getMimeType();
            $validated['attachment_size'] = $file->getSize();
        }

        $assignment = Assignment::create($validated);

        if ($assignment->target_type === 'specific_student' && $assignment->student_id) {
            NotificationService::notifyUser(
                $assignment->student_id,
                'assignment',
                "New Personal Assignment: {$assignment->title}",
                "You have been assigned a deliverable: {$assignment->title}. Due: ".date('M d, Y', strtotime($assignment->due_date)),
                route('assignments.show', $assignment->id),
                '📝'
            );
        } elseif ($assignment->target_type === 'all_students') {
            $studentIds = User::where('role', 'student')->pluck('id');
            foreach ($studentIds as $sid) {
                NotificationService::notifyUser(
                    $sid,
                    'assignment',
                    "New Academy-Wide Assignment: {$assignment->title}",
                    "A new assignment has been posted for all students: {$assignment->title}. Due: ".date('M d, Y', strtotime($assignment->due_date)),
                    route('assignments.show', $assignment->id),
                    '📝'
                );
            }
        } elseif ($assignment->course_id) {
            NotificationService::notifyCourseStudents(
                $assignment->course_id,
                'assignment',
                "New Assignment: {$assignment->title}",
                'A new assignment has been posted for your course. Due: '.date('M d, Y', strtotime($assignment->due_date)),
                route('assignments.show', $assignment->id),
                '📝'
            );
        }

        return redirect()->route('assignments.show', $assignment->id)
            ->with('success', 'Assignment published successfully!');
    }

    public function submit(Request $request, int $id): RedirectResponse
    {
        $assignment = Assignment::findOrFail($id);
        $user = Auth::user();

        if (! $user || ! $user->isStudent()) {
            abort(403, 'Only students can submit assignments.');
        }

        abort_unless($user->can('submit', $assignment), 403, 'You are not eligible to submit this assignment.');

        $validated = $request->validate([
            'content' => 'required|string|max:50000',
            'attachment_url' => ['nullable', 'url', 'max:2048', 'regex:/^https:\/\//i'],
        ]);

        $isLate = now()->isAfter($assignment->due_date);

        Submission::updateOrCreate(
            [
                'assignment_id' => $assignment->id,
                'student_id' => $user->id,
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

        return back()->with('success', 'Your assignment has been submitted successfully!');
    }

    public function grade(Request $request, int $id): RedirectResponse
    {
        $user = Auth::user();
        if (! $user || (! $user->isAdmin() && ! $user->isTeacher())) {
            abort(403, 'Unauthorized. Only teachers and administrators can evaluate assignments.');
        }

        $submission = Submission::with('assignment')->findOrFail($id);

        abort_unless($user->can('grade', $submission->assignment), 403);
        $validated = $request->validate([
            'grade' => 'required|numeric|min:0|max:'.$submission->assignment->max_points,
            'feedback' => 'nullable|string',
        ]);

        $submission->update([
            'grade' => $validated['grade'],
            'feedback' => $validated['feedback'] ?? null,
            'status' => 'graded',
            'graded_by' => Auth::id(),
            'graded_at' => now(),
        ]);

        NotificationService::notifyUser(
            $submission->student_id,
            'assignment',
            "Assignment Evaluated: {$submission->assignment->title}",
            "Your submission received a score of {$validated['grade']}/{$submission->assignment->max_points}.",
            route('assignments.show', $submission->assignment_id),
            '✅'
        );

        return back()->with('success', 'Submission graded successfully!');
    }

    public function edit(int $id): View
    {
        $user = Auth::user();
        if (! $user->isTeacher() && ! $user->isAdmin()) {
            abort(403);
        }

        $assignment = Assignment::findOrFail($id);
        abort_unless($user->can('update', $assignment), 403);
        $courses = $user->isAdmin() ? Course::orderBy('title')->get() : Course::where('teacher_id', $user->id)->orderBy('title')->get();
        $students = $user->isAdmin() ? User::where('role', 'student')->orderBy('name')->get(['id', 'name']) : User::where('role', 'student')->whereIn('id', Enrollment::whereIn('course_id', $courses->pluck('id'))->pluck('student_id'))->orderBy('name')->get(['id', 'name']);

        return view('assignments.edit', compact('assignment', 'courses', 'students'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $user = Auth::user();
        if (! $user->isTeacher() && ! $user->isAdmin()) {
            abort(403);
        }

        $assignment = Assignment::findOrFail($id);
        abort_unless($user->can('update', $assignment), 403);

        $validated = $request->validate([
            'course_id' => 'nullable|exists:courses,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'required|date',
            'max_points' => 'required|integer|min:1|max:1000',
            'attachment' => 'nullable|file|mimes:pdf,doc,docx,ppt,pptx,txt,png,jpg,jpeg,webp,zip|max:25600',
            'remove_attachment' => 'nullable|boolean',
        ]);

        if ($user->isTeacher() && ! empty($validated['course_id']) && ! Course::whereKey($validated['course_id'])->where('teacher_id', $user->id)->exists()) {
            abort(403);
        }
        if ($assignment->target_type === 'all_enrolled' && empty($validated['course_id'])) {
            abort(422, 'An enrolled-student assignment must remain linked to a course.');
        }
        if ($assignment->target_type === 'specific_student' && ! empty($validated['course_id']) && ! Enrollment::where('student_id', $assignment->student_id)->where('course_id', $validated['course_id'])->exists()) {
            abort(422, 'The selected student is not enrolled in this course.');
        }
        if ($assignment->target_type === 'all_students' && $user->isTeacher()) {
            abort(403);
        }

        if ($request->boolean('remove_attachment')) {
            if ($assignment->attachment_path) {
                $old = ltrim($assignment->attachment_path, '/');
                if (Storage::disk('local')->exists($old)) {
                    Storage::disk('local')->delete($old);
                } elseif (Storage::disk('public')->exists($old)) {
                    Storage::disk('public')->delete($old);
                } elseif (str_starts_with($old, 'uploads/assignments/')) {
                    $legacy = public_path($old);
                    if (is_file($legacy)) {
                        @unlink($legacy);
                    }
                }
            }
            $validated['attachment_path'] = null;
            $validated['attachment_name'] = null;
            $validated['attachment_type'] = null;
            $validated['attachment_size'] = null;
        }

        if ($request->hasFile('attachment')) {
            if ($assignment->attachment_path) {
                $old = ltrim($assignment->attachment_path, '/');
                if (Storage::disk('local')->exists($old)) {
                    Storage::disk('local')->delete($old);
                } elseif (Storage::disk('public')->exists($old)) {
                    Storage::disk('public')->delete($old);
                } elseif (str_starts_with($old, 'uploads/assignments/')) {
                    $legacy = public_path($old);
                    if (is_file($legacy)) {
                        @unlink($legacy);
                    }
                }
            }
            $file = $request->file('attachment');
            $originalName = mb_substr(basename($file->getClientOriginalName()), 0, 255);
            $validated['attachment_path'] = $file->store('assignments', 'local');
            $validated['attachment_name'] = $originalName;
            $validated['attachment_type'] = $file->getMimeType();
            $validated['attachment_size'] = $file->getSize();
        }

        $assignment->update($validated);

        return redirect()->route('assignments.show', $assignment->id)
            ->with('success', "Assignment \"{$assignment->title}\" updated successfully.");
    }

    public function downloadAttachment(int $id): Response
    {
        $assignment = Assignment::findOrFail($id);
        abort_unless(Auth::user() && Auth::user()->can('view', $assignment), 403);
        if (! $assignment->hasAttachment()) {
            abort(404, 'No attachment associated with this assignment.');
        }

        $relPath = ltrim($assignment->attachment_path, '/');
        $privateDisk = Storage::disk('local');
        if ($privateDisk->exists($relPath)) {
            return $privateDisk->download($relPath, $assignment->attachment_name ?: basename($relPath));
        }
        $disk = Storage::disk('public');
        if ($disk->exists($relPath)) {
            return $disk->download($relPath, $assignment->attachment_name ?: basename($relPath));
        }
        // Backward compatibility for legacy public/uploads/assignments records.
        $legacyPath = public_path($relPath);
        if (! is_file($legacyPath)) {
            abort(404, 'Attachment file not found on disk.');
        }

        return response()->download($legacyPath, $assignment->attachment_name ?: basename($legacyPath));
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        $user = Auth::user();
        if (! $user->isTeacher() && ! $user->isAdmin()) {
            abort(403);
        }

        $assignment = Assignment::with('submissions')->findOrFail($id);
        abort_unless($user->can('delete', $assignment), 403);
        $title = $assignment->title;
        if ($assignment->attachment_path) {
            $storedPath = ltrim($assignment->attachment_path, '/');
            foreach (['local', 'public'] as $diskName) {
                if (Storage::disk($diskName)->exists($storedPath)) {
                    Storage::disk($diskName)->delete($storedPath);
                }
            }
            if (str_starts_with($storedPath, 'uploads/assignments/')) {
                $legacy = public_path($storedPath);
                if (is_file($legacy)) {
                    @unlink($legacy);
                }
            }
        }
        $fromCourseId = $request->input('course_id') ?: $request->input('from_course');

        $assignment->submissions()->delete();
        $assignment->delete();

        if ($fromCourseId) {
            return redirect()->route('courses.show', ['id' => $fromCourseId, 'tab' => 'homework'])
                ->with('success', "Assignment \"{$title}\" was successfully deleted.");
        }

        return redirect()->route('assignments.index')
            ->with('success', "Assignment \"{$title}\" was successfully deleted.");
    }
}
