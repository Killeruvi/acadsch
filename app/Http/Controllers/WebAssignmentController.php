<?php

namespace App\Http\Controllers;

use App\Models\AcademicClass;
use App\Models\AcademicSubject;
use App\Models\Assignment;
use App\Models\ClassEnrollment;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\SchoolSection;
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
        $query = Assignment::query()
            ->with([
                'academicClass.schoolSection',
                'section:id,name',
                'subject:id,name,code',
                'course:id,title,code',
                'teacher:id,name',
                'student:id,name',
            ])
            ->withCount('submissions');

        $selectedClassId = $request->query('class_id');
        $selectedSectionId = $request->query('section_id');
        $selectedSubjectId = $request->query('subject_id');
        $search = $request->query('search');

        if ($user->isAdmin()) {
            $classes = AcademicClass::with(['schoolSection', 'subjects'])->orderBy('grade_level')->get();
        } elseif ($user->isTeacher()) {
            $teacherClassIds = AcademicSubject::where('teacher_id', $user->id)->pluck('class_id')
                ->merge(AcademicClass::where('class_teacher_id', $user->id)->pluck('id'))
                ->merge(Assignment::where('teacher_id', $user->id)->whereNotNull('class_id')->pluck('class_id'))
                ->unique();

            $classes = AcademicClass::whereIn('id', $teacherClassIds)->with(['schoolSection', 'subjects'])->orderBy('grade_level')->get();

            $query->where(function ($q) use ($user, $teacherClassIds) {
                $q->where('teacher_id', $user->id)
                    ->orWhereIn('class_id', $teacherClassIds);
            });
        } elseif ($user->isStudent()) {
            $studentClassIds = $user->classEnrollments()->where(function ($q) {
                $q->whereNull('status')->orWhereIn('status', ['active', 'enrolled']);
            })->pluck('class_id');

            if ($user->current_class_id) {
                $studentClassIds->push($user->current_class_id);
            }
            $studentClassIds = $studentClassIds->unique()->filter()->values();

            $courseIds = $user->enrolledCourses()->pluck('courses.id');

            $query->where(function ($q) use ($user, $studentClassIds, $courseIds) {
                $q->where(function ($sq) use ($studentClassIds) {
                    $sq->where('target_type', 'class')->whereIn('class_id', $studentClassIds);
                })
                    ->orWhere('target_type', 'all_students')
                    ->orWhere(function ($sq) use ($user) {
                        $sq->where('target_type', 'specific_student')->where('student_id', $user->id);
                    })
                    ->orWhere(function ($sq) use ($courseIds) {
                        $sq->where('target_type', 'all_enrolled')->whereIn('course_id', $courseIds);
                    });
            });

            $classes = AcademicClass::whereIn('id', $studentClassIds)->with(['schoolSection', 'subjects'])->orderBy('grade_level')->get();
        } elseif ($user->isParent()) {
            $childIds = User::childrenOf($user)->pluck('id');
            $childClassIds = ClassEnrollment::whereIn('student_id', $childIds)->whereIn('status', ['active', 'enrolled'])->pluck('class_id')
                ->merge(User::whereIn('id', $childIds)->pluck('current_class_id'))
                ->unique()->filter()->values();

            $childCourseIds = Enrollment::whereIn('student_id', $childIds)->pluck('course_id')->unique();

            $query->where(function ($q) use ($childIds, $childClassIds, $childCourseIds) {
                $q->where(function ($sq) use ($childClassIds) {
                    $sq->where('target_type', 'class')->whereIn('class_id', $childClassIds);
                })
                    ->orWhere('target_type', 'all_students')
                    ->orWhere(function ($sq) use ($childIds) {
                        $sq->where('target_type', 'specific_student')->whereIn('student_id', $childIds);
                    })
                    ->orWhere(function ($sq) use ($childCourseIds) {
                        $sq->where('target_type', 'all_enrolled')->whereIn('course_id', $childCourseIds);
                    });
            });

            $classes = AcademicClass::whereIn('id', $childClassIds)->with(['schoolSection', 'subjects'])->orderBy('grade_level')->get();
        } else {
            abort(403);
        }

        // Apply filters
        if (! empty($selectedClassId) && $selectedClassId !== 'all') {
            $query->where('class_id', (int) $selectedClassId);
        }
        if (! empty($selectedSectionId) && $selectedSectionId !== 'all') {
            $query->where('section_id', (int) $selectedSectionId);
        }
        if (! empty($selectedSubjectId) && $selectedSubjectId !== 'all') {
            $query->where('subject_id', (int) $selectedSubjectId);
        }
        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $assignments = $query->orderBy('due_date')->paginate(15)->withQueryString();

        if ($user->isStudent()) {
            $mySubmissions = Submission::where('student_id', $user->id)
                ->whereIn('assignment_id', $assignments->pluck('id'))
                ->get()
                ->keyBy('assignment_id');
        } elseif ($user->isParent()) {
            $childIds = User::childrenOf($user)->pluck('id');
            $mySubmissions = Submission::whereIn('student_id', $childIds)
                ->whereIn('assignment_id', $assignments->pluck('id'))
                ->with('student:id,name')
                ->get()
                ->groupBy('assignment_id')
                ->map(fn ($items) => $items->first());
        } else {
            $mySubmissions = collect();
        }

        $sections = SchoolSection::where('is_active', true)->orderBy('name')->get();
        $subjects = (! empty($selectedClassId) && $selectedClassId !== 'all')
            ? AcademicSubject::where('class_id', (int) $selectedClassId)->orderBy('name')->get()
            : AcademicSubject::orderBy('name')->get();

        $students = ($user->isAdmin() || $user->isTeacher())
            ? User::where('role', 'student')->where('is_approved', true)->orderBy('name')->get(['id', 'name', 'student_id_number', 'current_class_id'])
            : collect();

        $courses = Course::orderBy('title')->get(['id', 'title', 'code']);

        return view('assignments.index', compact(
            'assignments',
            'classes',
            'sections',
            'subjects',
            'courses',
            'mySubmissions',
            'students',
            'selectedClassId',
            'selectedSectionId',
            'selectedSubjectId',
            'search'
        ));
    }

    public function show(int $id): View
    {
        $assignment = Assignment::with([
            'academicClass.schoolSection',
            'section:id,name',
            'subject:id,name,code',
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
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'required|date',
            'max_points' => 'required|integer|min:1|max:1000',
            'target_type' => 'required|string|in:class,all_students,specific_student,all_enrolled',
            'class_id' => 'nullable|required_if:target_type,class|exists:academic_classes,id',
            'section_id' => 'nullable|exists:school_sections,id',
            'subject_id' => 'nullable|exists:academic_subjects,id',
            'student_id' => 'nullable|required_if:target_type,specific_student|exists:users,id',
            'course_id' => 'nullable|exists:courses,id',
            'attachment' => 'nullable|file|mimes:pdf,doc,docx,ppt,pptx,txt,png,jpg,jpeg,webp,zip|max:25600',
        ], [
            'class_id.required_if' => 'Please select an academic class for this class assignment.',
            'student_id.required_if' => 'Please select a specific student for this individual assignment.',
        ]);

        if ($user->isTeacher() && ! empty($validated['class_id'])) {
            $teachesClass = AcademicSubject::where('class_id', $validated['class_id'])->where('teacher_id', $user->id)->exists()
                || AcademicClass::where('id', $validated['class_id'])->where('class_teacher_id', $user->id)->exists()
                || $user->isAdmin();

            if (! $teachesClass && ! empty($validated['course_id']) && ! Course::whereKey($validated['course_id'])->where('teacher_id', $user->id)->exists()) {
                // allow if teacher is creating for their subjects
            }
        }

        if ($validated['target_type'] === 'all_students' && ! $user->isAdmin()) {
            abort(403, 'Only administrators may publish school-wide assignments.');
        }

        $validated['teacher_id'] = Auth::id();

        if (! empty($validated['class_id'])) {
            $class = AcademicClass::find($validated['class_id']);
            if ($class) {
                $validated['academic_year'] = $class->academic_year;
                if (empty($validated['section_id']) && $class->section_id) {
                    $validated['section_id'] = $class->section_id;
                }
            }
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

        // Notify target students
        if ($assignment->target_type === 'specific_student' && $assignment->student_id) {
            NotificationService::notifyUser(
                $assignment->student_id,
                'assignment',
                "New Assignment: {$assignment->title}",
                "You have been assigned: {$assignment->title}. Due: ".date('M d, Y', strtotime($assignment->due_date)),
                route('assignments.show', $assignment->id),
                '📝'
            );
        } elseif ($assignment->target_type === 'all_students') {
            $studentIds = User::where('role', 'student')->where('is_approved', true)->pluck('id');
            foreach ($studentIds as $sid) {
                NotificationService::notifyUser(
                    $sid,
                    'assignment',
                    "New School-Wide Assignment: {$assignment->title}",
                    "A new assignment has been posted for all students: {$assignment->title}. Due: ".date('M d, Y', strtotime($assignment->due_date)),
                    route('assignments.show', $assignment->id),
                    '📝'
                );
            }
        } elseif ($assignment->target_type === 'class' && $assignment->class_id) {
            $studentIds = ClassEnrollment::where('class_id', $assignment->class_id)
                ->where(function ($q) {
                    $q->whereNull('status')->orWhereIn('status', ['active', 'enrolled']);
                })
                ->pluck('student_id')
                ->merge(User::where('current_class_id', $assignment->class_id)->where('role', 'student')->pluck('id'))
                ->unique();

            foreach ($studentIds as $sid) {
                NotificationService::notifyUser(
                    $sid,
                    'assignment',
                    "New Class Assignment: {$assignment->title}",
                    "A new assignment has been posted for your class: {$assignment->title}. Due: ".date('M d, Y', strtotime($assignment->due_date)),
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

        $assignment = Assignment::with(['academicClass', 'section', 'subject', 'course'])->findOrFail($id);
        abort_unless($user->can('update', $assignment), 403);

        $classes = AcademicClass::with(['schoolSection', 'subjects'])->orderBy('grade_level')->get();
        $sections = SchoolSection::where('is_active', true)->orderBy('name')->get();
        $subjects = $assignment->class_id
            ? AcademicSubject::where('class_id', $assignment->class_id)->orderBy('name')->get()
            : AcademicSubject::orderBy('name')->get();

        $students = User::where('role', 'student')->where('is_approved', true)->orderBy('name')->get(['id', 'name']);
        $courses = Course::orderBy('title')->get(['id', 'title', 'code']);

        return view('assignments.edit', compact('assignment', 'classes', 'sections', 'subjects', 'courses', 'students'));
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
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'required|date',
            'max_points' => 'required|integer|min:1|max:1000',
            'class_id' => 'nullable|exists:academic_classes,id',
            'section_id' => 'nullable|exists:school_sections,id',
            'subject_id' => 'nullable|exists:academic_subjects,id',
            'attachment' => 'nullable|file|mimes:pdf,doc,docx,ppt,pptx,txt,png,jpg,jpeg,webp,zip|max:25600',
            'remove_attachment' => 'nullable|boolean',
        ]);

        if (! empty($validated['class_id'])) {
            $class = AcademicClass::find($validated['class_id']);
            if ($class) {
                $validated['academic_year'] = $class->academic_year;
            }
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

        $assignment->submissions()->delete();
        $assignment->delete();

        return redirect()->route('assignments.index')
            ->with('success', "Assignment \"{$title}\" was successfully deleted.");
    }
}
