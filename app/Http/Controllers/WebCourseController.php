<?php

namespace App\Http\Controllers;

use App\Models\AcademicSubject;
use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\CourseLessonVideo;
use App\Models\CourseModule;
use App\Models\DailyDpp;
use App\Models\Enrollment;
use App\Models\User;
use App\Services\PdfHandoutGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WebCourseController extends Controller
{
    public function index(Request $request): View
    {
        $query = Course::with(['teacher:id,name,email'])->withCount('enrollments');
        $viewer = Auth::user();
        if (! $viewer || (! $viewer->isAdmin() && ! $viewer->isTeacher())) {
            $query->where('status', 'active');
        } elseif ($viewer->isTeacher()) {
            $query->where('teacher_id', $viewer->id);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->query('category'));
        }

        if ($request->filled('search')) {
            $search = strtolower($request->query('search'));
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(title) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(code) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(description) LIKE ?', ["%{$search}%"]);
            });
        }

        $courses = $query->orderBy('title')->paginate(12)->withQueryString();
        $categories = Course::select('category')->distinct()->pluck('category');
        $teachers = User::whereIn('role', ['teacher', 'admin'])->orderBy('name')->get(['id', 'name']);

        // If user is student, get enrolled course IDs
        $enrolledIds = [];
        if (Auth::check() && Auth::user()->isStudent()) {
            $enrolledIds = Auth::user()->enrolledCourses()->pluck('courses.id')->toArray();
        }

        return view('courses.index', compact('courses', 'categories', 'teachers', 'enrolledIds'));
    }

    public function show(int $id): View
    {
        $course = Course::with([
            'teacher:id,name,email,bio',
            'modules.lessons.videos',
            'modules.lessons.dpp',
            'lessons.videos',
            'lessons.dpp',
            'schedules',
            'assignments.submissions' => function ($q) {
                if (Auth::check() && Auth::user()->isStudent()) {
                    $q->where('student_id', Auth::id());
                }
            },
            'assessments.submissions' => function ($q) {
                if (Auth::check() && Auth::user()->isStudent()) {
                    $q->where('student_id', Auth::id());
                }
            },
            'dpps.questions',
            'dpps.submissions' => function ($q) {
                if (Auth::check() && Auth::user()->isStudent()) {
                    $q->where('student_id', Auth::id());
                }
            },
            'enrollments' => function ($q) {
                $viewer = Auth::user();
                if ($viewer && $viewer->isStudent()) {
                    $q->where('student_id', $viewer->id);
                } elseif ($viewer && $viewer->isParent()) {
                    $childIds = User::where('parent_id', $viewer->id)->pluck('id');
                    $q->whereIn('student_id', $childIds);
                }
                $q->with('student:id,name,email');
            },
        ])->withCount([
            'enrollments',
            'modules',
            'lessons',
            'assignments',
            'assessments',
            'dpps',
            'schedules',
        ])->findOrFail($id);
        $viewer = Auth::user();
        if (! $viewer) {
            abort_unless($course->status === 'active', 404);
        } elseif ($viewer->isTeacher()) {
            abort_unless((int) $course->teacher_id === (int) $viewer->id, 403);
        } elseif ($viewer->isStudent()) {
            abort_unless($course->status === 'active' || $viewer->enrolledCourses()->where('courses.id', $course->id)->exists(), 403);
        } elseif (! $viewer->isAdmin() && ! $viewer->isParent()) {
            abort(403);
        }

        $isEnrolled = false;
        if (Auth::check() && Auth::user()->isStudent()) {
            $isEnrolled = Auth::user()->enrolledCourses()->where('courses.id', $course->id)->exists();
        }

        $classSubjects = collect();
        if (Auth::check() && (Auth::user()->isAdmin() || Auth::user()->isTeacher())) {
            $classSubjectsQuery = AcademicSubject::with('academicClass')->whereHas('academicClass');
            if (Auth::user()->isTeacher()) {
                $classSubjectsQuery->where(function ($query) {
                    $query->where('teacher_id', Auth::id())
                        ->orWhereHas('academicClass', fn ($classQuery) => $classQuery->where('class_teacher_id', Auth::id()));
                });
            }
            $classSubjects = $classSubjectsQuery->orderBy('name')->get();
        }

        return view('courses.show', compact('course', 'isEnrolled', 'classSubjects'));
    }

    public function store(Request $request): RedirectResponse
    {
        // Auto-resolve course code: generate if empty, or auto-suffix if already taken
        $rawCode = strtoupper(trim((string) $request->input('code', '')));
        if (empty($rawCode)) {
            $title = (string) $request->input('title', 'CRS');
            $words = preg_split('/[\s\-_]+/', $title);
            $prefix = '';
            foreach ($words as $w) {
                if (! empty($w)) {
                    $prefix .= strtoupper(substr($w, 0, 1));
                }
            }
            if (strlen($prefix) < 2) {
                $prefix = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $title), 0, 3)) ?: 'CRS';
            }
            $rawCode = substr($prefix, 0, 4).'-'.strtoupper(Str::random(6));
        }

        // If code already exists in courses table, auto-suffix to avoid collision
        if (Course::where('code', $rawCode)->exists()) {
            $baseCode = $rawCode;
            $counter = 2;
            while (Course::where('code', "{$baseCode}-{$counter}")->exists()) {
                $counter++;
            }
            $rawCode = "{$baseCode}-{$counter}";
        }

        $request->merge(['code' => $rawCode]);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:courses,code',
            'description' => 'nullable|string',
            'category' => 'required|string|max:100',
            'credits' => 'required|integer|min:1|max:12',
            'capacity' => 'nullable|integer|min:1|max:500',
            'teacher_id' => 'nullable|exists:users,id',
            'status' => 'nullable|in:active,draft,archived',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $request->validate([
            'curriculum_csv' => 'nullable|file|mimes:csv,txt|max:10240',
        ]);

        if (Auth::user()->isTeacher()) {
            if (! empty($validated['teacher_id']) && (int) $validated['teacher_id'] !== Auth::id()) {
                abort(403, 'Teachers may create courses only under their own account.');
            }
            $validated['teacher_id'] = Auth::id();
        }

        if (empty($validated['capacity'])) {
            $validated['capacity'] = 30;
        }

        if (empty($validated['status'])) {
            $validated['status'] = 'active';
        }

        if (empty($validated['teacher_id']) && Auth::user()->isTeacher()) {
            $validated['teacher_id'] = Auth::id();
        }

        $course = Course::create($validated);

        // Optional Curriculum CSV Bulk Import during course creation
        if ($request->hasFile('curriculum_csv')) {
            $stats = $this->processCurriculumCsv($course, $request->file('curriculum_csv'));

            return redirect()->route('courses.show', $course->id)
                ->with('success', "Course '{$course->title}' created with {$stats['modules']} modules and {$stats['lessons']} lessons imported from CSV.");
        }

        return redirect()->route('courses.show', $course->id)
            ->with('success', "Course '{$course->title}' created successfully! Set up curriculum modules and lessons manually or upload a bulk CSV.");
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $user = Auth::user();
        if (! $user || (! $user->isAdmin() && ! $user->isTeacher())) {
            abort(403);
        }

        $course = Course::findOrFail($id);
        $this->authorizeCourseManager($course);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:courses,code,'.$course->id,
            'description' => 'nullable|string',
            'category' => 'required|string|max:100',
            'credits' => 'required|integer|min:1|max:12',
            'capacity' => 'nullable|integer|min:1|max:500',
            'teacher_id' => 'nullable|exists:users,id',
            'status' => 'nullable|in:active,draft,archived',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        if ($user->isTeacher()) {
            abort_unless((int) $course->teacher_id === (int) $user->id, 403);
            if (array_key_exists('teacher_id', $validated) && (int) $validated['teacher_id'] !== (int) $user->id) {
                abort(403, 'Teachers cannot reassign course ownership.');
            }
            unset($validated['teacher_id']);
        }

        if (empty($validated['capacity'])) {
            $validated['capacity'] = 30;
        }

        if (empty($validated['status'])) {
            $validated['status'] = 'active';
        }
        if (isset($validated['capacity'])) {
            $occupied = $course->enrollments()->where('status', 'enrolled')->count();
            if ((int) $validated['capacity'] < $occupied) {
                return back()->withErrors(['capacity' => "Capacity cannot be lower than the {$occupied} currently enrolled students."])->withInput();
            }
        }

        $course->update($validated);

        return back()->with('success', "Course \"{$course->title}\" updated successfully!");
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        $user = Auth::user();
        if (! $user || (! $user->isAdmin() && ! $user->isTeacher())) {
            abort(403);
        }

        $course = Course::findOrFail($id);
        abort_unless($user->isAdmin(), 403, 'Only administrators may delete courses.');
        if ($course->enrollments()->exists() || $course->assessments()->exists() || $course->assignments()->exists()) {
            return back()->with('error', 'This course has enrollment or assessment history and cannot be deleted. Archive it instead.');
        }
        $title = $course->title;

        // Cascade delete relations
        $course->modules()->each(function ($module) {
            $module->lessons()->each(function ($lesson) {
                $lesson->videos()->delete();
                $lesson->delete();
            });
            $module->delete();
        });
        $course->lessons()->delete();
        $course->schedules()->delete();
        $course->dpps()->delete();
        $course->enrollments()->delete();
        $course->delete();

        return redirect()->route('courses.index')
            ->with('success', "Course \"{$title}\" has been deleted successfully.");
    }

    public function enroll(Request $request, int $id): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user && ($user->isStudent() || $user->isAdmin() || $user->isTeacher()), 403);
        $course = Course::findOrFail($id);
        abort_unless($course->status === 'active', 404);

        $requestedStudentId = $request->input('student_id');
        if (! $requestedStudentId && ! $user->isStudent()) {
            abort(422, 'Select a student account to enroll.');
        }
        if ($requestedStudentId && (int) $requestedStudentId !== $user->id) {
            if (! $user->isAdmin() && ! $user->isTeacher()) {
                abort(403, 'Unauthorized. Students cannot enroll other users.');
            }
            $studentId = (int) $requestedStudentId;
        } else {
            $studentId = $user->id;
        }
        abort_unless(User::whereKey($studentId)->where('role', 'student')->exists(), 422, 'Only student accounts can be enrolled.');

        $enrolled = DB::transaction(function () use ($course, $studentId) {
            $lockedCourse = Course::whereKey($course->id)->lockForUpdate()->firstOrFail();
            $existing = Enrollment::where('course_id', $lockedCourse->id)->where('student_id', $studentId)->lockForUpdate()->first();
            if ($existing && in_array($existing->status, ['enrolled', 'completed'], true)) {
                return 'existing';
            }
            $activeCount = Enrollment::where('course_id', $lockedCourse->id)->where('status', 'enrolled')->count();
            if ($activeCount >= $lockedCourse->capacity) {
                return 'full';
            }
            if ($existing) {
                $existing->update(['status' => 'enrolled', 'enrolled_at' => now()]);
            } else {
                Enrollment::create(['course_id' => $lockedCourse->id, 'student_id' => $studentId, 'status' => 'enrolled', 'enrolled_at' => now()]);
            }

            return 'created';
        });
        if ($enrolled === 'full') {
            return back()->with('error', 'This course is at full capacity.');
        }
        if ($enrolled === 'existing') {
            return back()->with('info', 'Already enrolled in this course.');
        }

        return back()->with('success', "Successfully enrolled in {$course->title}!");
    }

    public function downloadHandoutPdf(Request $request, int $id, string $filename): Response
    {
        $course = Course::findOrFail($id);
        $viewer = Auth::user();
        if (! $viewer || (! $viewer->isAdmin() && ! $viewer->isTeacher())) {
            abort_unless($course->status === 'active', 404);
        }
        if ($viewer && $viewer->isTeacher()) {
            abort_unless((int) $course->teacher_id === (int) $viewer->id, 403);
        }
        abort_unless(preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{0,119}\.pdf$/i', $filename) === 1, 404);

        // 1. Check if an actual uploaded file exists in public storage
        $storedRelPath = "courses/{$course->id}/handouts/{$filename}";
        if (Storage::disk('public')->exists($storedRelPath)) {
            $absPath = Storage::disk('public')->path($storedRelPath);

            return response()->file($absPath, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => ($request->query('download') ? 'attachment' : 'inline').'; filename="'.$filename.'"',
            ]);
        }

        // 2. Parse lesson number from filename (e.g. week-1-lesson-2.pdf, lesson-2.pdf, L2.pdf)
        $lessonNumber = 1;
        if (preg_match('/(?:lesson[-_]?|l)(\d+)/i', $filename, $m)) {
            $lessonNumber = (int) $m[1];
        } elseif (preg_match('/(\d+)\.pdf$/i', $filename, $m)) {
            $lessonNumber = (int) $m[1];
        }

        $lesson = CourseLesson::where('course_id', $course->id)
            ->where('lesson_number', $lessonNumber)
            ->first() ?? $course->lessons()->first();

        if (! $lesson) {
            $lesson = new CourseLesson([
                'course_id' => $course->id,
                'lesson_number' => $lessonNumber,
                'title' => "Lesson {$lessonNumber}: Study Material & Practical Exercises",
                'duration_minutes' => 60,
                'level' => 'foundation',
                'topic_summary' => "Official curriculum study handout for {$course->title}.",
            ]);
        }

        $pdfBytes = PdfHandoutGenerator::generate($course, $lesson);

        // Cache generated PDF to storage
        Storage::disk('public')->put($storedRelPath, $pdfBytes);

        return response($pdfBytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => ($request->query('download') ? 'attachment' : 'inline').'; filename="'.$filename.'"',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    public function lessonPdf(Request $request, int $id, int $lessonNumber): Response
    {
        $course = Course::findOrFail($id);
        $filename = "course-{$course->id}-lesson-{$lessonNumber}.pdf";

        return $this->downloadHandoutPdf($request, $id, $filename);
    }

    public function uploadLessonPdf(Request $request, int $courseId, int $lessonId = 0): RedirectResponse
    {
        $user = Auth::user();
        if (! $user || (! $user->isAdmin() && ! $user->isTeacher())) {
            abort(403);
        }

        $course = Course::findOrFail($courseId);
        $this->authorizeCourseManager($course);

        // Fallback lesson resolution: route parameter or form input
        $targetLessonId = $lessonId > 0 ? $lessonId : (int) $request->input('lesson_id', 0);
        $lesson = null;

        if ($targetLessonId > 0) {
            $lesson = CourseLesson::where('course_id', $courseId)->find($targetLessonId);
        }

        if (! $lesson) {
            $lesson = CourseLesson::where('course_id', $courseId)->orderBy('lesson_number')->first();
        }

        // Strictly disallow upload if no lectures/lessons exist yet
        if (! $lesson) {
            return back()->withErrors([
                'pdf_file' => 'Cannot upload lecture notes: no lectures exist for this course yet. Please create at least one lecture in the Curriculum tab first.',
            ])->with('error', 'Cannot upload lecture notes until lectures are created for this course.');
        }

        $request->validate([
            'pdf_file' => 'required|file|mimes:pdf|max:20480',
        ]);

        $file = $request->file('pdf_file');
        $fileName = 'course_'.$courseId.'_lesson_'.$lesson->lesson_number.'_'.time().'.pdf';
        $dest = public_path('uploads/course_notes');
        if (! file_exists($dest)) {
            mkdir($dest, 0755, true);
        }
        $file->move($dest, $fileName);

        $lesson->update([
            'pdf_url' => '/uploads/course_notes/'.$fileName,
        ]);

        return back()->with('success', "PDF lecture note uploaded successfully for Lecture {$lesson->lesson_number}: {$lesson->title}!");
    }

    public function storeVideo(Request $request, int $courseId): RedirectResponse
    {
        $user = Auth::user();
        if (! $user->isAdmin() && ! $user->isTeacher()) {
            abort(403);
        }

        $course = Course::findOrFail($courseId);
        $this->authorizeCourseManager($course);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'video_url' => 'required|url',
            'duration_minutes' => 'nullable|integer|min:1|max:360',
            'lesson_id' => 'nullable|exists:course_lessons,id',
            'subject_id' => 'required|integer|exists:academic_subjects,id',
            'module_id' => 'nullable|exists:course_modules,id',
            'description' => 'nullable|string',
        ]);

        if (! empty($validated['lesson_id'])) {
            $lesson = CourseLesson::where('course_id', $course->id)->findOrFail($validated['lesson_id']);
            $isFirstVideo = $lesson->videos()->count() === 0;

            $maxOrder = $lesson->videos()->max('order_index') ?? 0;
            $video = CourseLessonVideo::create([
                'lesson_id' => $lesson->id,
                'course_id' => $course->id,
                'title' => $validated['title'],
                'video_url' => $validated['video_url'],
                'duration_minutes' => $validated['duration_minutes'] ?? 30,
                'order_index' => $maxOrder + 1,
                'description' => $validated['description'] ?? null,
            ]);

            if ($isFirstVideo || empty($lesson->video_url) || $lesson->title === 'Initial Lesson') {
                $lesson->title = $validated['title'];
                $lesson->video_url = $validated['video_url'];
                if (! empty($validated['duration_minutes'])) {
                    $lesson->duration_minutes = $validated['duration_minutes'];
                }
            }
            if (! empty($validated['description']) && empty($lesson->topic_summary)) {
                $lesson->topic_summary = $validated['description'];
            }
            $lesson->save();

            return back()->with('success', "Video \"{$video->title}\" added to Lesson {$lesson->lesson_number} successfully!");
        }

        $moduleId = $validated['module_id'] ?? null;
        if (! $moduleId) {
            $module = $course->modules()->first();
            if (! $module) {
                $module = CourseModule::create([
                    'course_id' => $course->id,
                    'title' => 'Module 1: Foundation Lectures',
                    'description' => 'Core lecture presentations and video masterclasses',
                    'order_index' => 1,
                    'week_number' => 1,
                ]);
            }
            $moduleId = $module->id;
        }

        $maxLessonNum = CourseLesson::where('course_id', $course->id)->max('lesson_number') ?? 0;

        $lesson = CourseLesson::create([
            'course_id' => $course->id,
            'module_id' => $moduleId,
            'lesson_number' => $maxLessonNum + 1,
            'week_number' => 1,
            'day_of_week' => 1,
            'title' => $validated['title'],
            'video_url' => $validated['video_url'],
            'duration_minutes' => $validated['duration_minutes'] ?? 30,
            'topic_summary' => $validated['description'] ?? null,
            'order_index' => $maxLessonNum + 1,
        ]);

        CourseLessonVideo::create([
            'lesson_id' => $lesson->id,
            'course_id' => $course->id,
            'title' => $validated['title'],
            'video_url' => $validated['video_url'],
            'duration_minutes' => $validated['duration_minutes'] ?? 30,
            'order_index' => 1,
            'description' => $validated['description'] ?? null,
        ]);

        return back()->with('success', "Video lecture '{$validated['title']}' created successfully!");
    }

    public function updateLessonVideo(Request $request, int $courseId, int $videoId): RedirectResponse
    {
        $user = Auth::user();
        if (! $user || (! $user->isAdmin() && ! $user->isTeacher())) {
            abort(403);
        }

        $course = Course::findOrFail($courseId);
        $this->authorizeCourseManager($course);

        if ($videoId === 0 && $request->filled('lesson_id')) {
            $lesson = CourseLesson::where('course_id', $course->id)->findOrFail($request->input('lesson_id'));
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'video_url' => 'required|url',
                'duration_minutes' => 'nullable|integer|min:1|max:360',
            ]);
            $lesson->video_url = $validated['video_url'];
            if (! empty($validated['duration_minutes'])) {
                $lesson->duration_minutes = $validated['duration_minutes'];
            }
            $lesson->save();

            return back()->with('success', 'Lesson video updated successfully!');
        }

        $video = CourseLessonVideo::where('course_id', $course->id)->findOrFail($videoId);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'video_url' => 'required|url',
            'duration_minutes' => 'nullable|integer|min:1|max:360',
            'description' => 'nullable|string',
        ]);

        $video->update([
            'title' => $validated['title'],
            'video_url' => $validated['video_url'],
            'duration_minutes' => $validated['duration_minutes'] ?? $video->duration_minutes,
            'description' => $validated['description'] ?? $video->description,
        ]);

        $lesson = $video->lesson;
        if ($lesson && ($lesson->video_url === $video->getOriginal('video_url') || empty($lesson->video_url))) {
            $lesson->video_url = $validated['video_url'];
            $lesson->save();
        }

        return back()->with('success', "Video \"{$video->title}\" updated successfully!");
    }

    public function destroyLessonVideo(Request $request, int $courseId, int $videoId): RedirectResponse
    {
        $user = Auth::user();
        if (! $user || (! $user->isAdmin() && ! $user->isTeacher())) {
            abort(403);
        }

        $course = Course::findOrFail($courseId);
        $this->authorizeCourseManager($course);

        if ($videoId === 0 && $request->filled('lesson_id')) {
            $lesson = CourseLesson::where('course_id', $course->id)->findOrFail($request->input('lesson_id'));
            $lesson->video_url = null;
            $lesson->save();

            return back()->with('success', "Video removed from Lesson {$lesson->lesson_number}.");
        }

        $video = CourseLessonVideo::where('course_id', $courseId)->findOrFail($videoId);
        $lesson = $video->lesson;
        $title = $video->title;
        $video->delete();

        if ($lesson && $lesson->video_url === $video->video_url) {
            $next = $lesson->videos()->first();
            $lesson->video_url = $next ? $next->video_url : null;
            $lesson->save();
        }

        return back()->with('success', "Video \"{$title}\" removed successfully.");
    }

    public function destroyLessonPdf(Request $request, int $courseId, int $lessonId): RedirectResponse
    {
        $user = Auth::user();
        if (! $user || (! $user->isAdmin() && ! $user->isTeacher())) {
            abort(403);
        }

        $course = Course::findOrFail($courseId);
        $this->authorizeCourseManager($course);
        $lesson = CourseLesson::where('course_id', $course->id)->findOrFail($lessonId);

        if ($lesson->pdf_url) {
            $localPath = public_path(ltrim($lesson->pdf_url, '/'));
            if (file_exists($localPath) && is_file($localPath)) {
                @unlink($localPath);
            }
            $lesson->pdf_url = null;
            $lesson->save();
        }

        return back()->with('success', "PDF lecture note removed from Lesson {$lesson->lesson_number}.");
    }

    public function storeDpp(Request $request, int $courseId): RedirectResponse
    {
        $user = Auth::user();
        if (! $user->isAdmin() && ! $user->isTeacher()) {
            abort(403);
        }

        $course = Course::findOrFail($courseId);
        $this->authorizeCourseManager($course);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'assigned_date' => 'required|date',
            'difficulty' => 'required|in:standard,advanced,olympiad,easy,medium,hard',
            'duration_minutes' => 'nullable|integer|min:0|max:300',
            'total_marks' => 'nullable|integer|min:1|max:500',
            'lesson_id' => 'nullable|exists:course_lessons,id',
            'subject_id' => 'required|integer|exists:academic_subjects,id',
            'pass_percentage' => 'nullable|integer|min:1|max:100',
            'negative_marks' => 'nullable|numeric|min:0|max:10',
        ]);

        $subject = AcademicSubject::with('academicClass')->findOrFail($validated['subject_id']);
        $academicClass = $subject->academicClass;
        if (! $academicClass) {
            return back()->withErrors(['subject_id' => 'The selected subject is not linked to an active academic class.']);
        }
        if ($user->isTeacher() && $subject->teacher_id !== $user->id && $academicClass->class_teacher_id !== $user->id) {
            abort(403, 'You are not assigned to the selected class subject.');
        }

        $dppCount = DailyDpp::where('course_id', $course->id)->count();
        $dppNumber = 'DPP-'.str_pad($dppCount + 1, 2, '0', STR_PAD_LEFT);

        $dpp = DailyDpp::create([
            'course_id' => $course->id,
            'lesson_id' => $validated['lesson_id'] ?? null,
            'class_id' => $academicClass->id,
            'subject_id' => $subject->id,
            'teacher_id' => $user->id,
            'dpp_number' => $dppNumber,
            'title' => $validated['title'],
            'type' => 'dpp',
            'assigned_date' => $validated['assigned_date'],
            'due_time' => '23:59:00',
            'total_marks' => $validated['total_marks'] ?? 20,
            'difficulty' => $validated['difficulty'],
            'duration_minutes' => $validated['duration_minutes'] ?? 30,
            'is_timed' => ! empty($validated['duration_minutes']) && $validated['duration_minutes'] > 0,
            'pass_percentage' => $validated['pass_percentage'] ?? 60,
            'negative_marks' => $validated['negative_marks'] ?? 0.0,
        ]);

        return redirect()->route('dpp.edit', $dpp->id)
            ->with('success', "Daily DPP sheet '{$validated['title']}' created for {$course->title}! Add questions below or import from CSV.");
    }

    /**
     * Download a clean CSV template for curriculum bulk feeding.
     */
    public function downloadCurriculumCsvTemplate(int $courseId): StreamedResponse
    {
        $course = Course::findOrFail($courseId);
        $this->authorizeCourseManager($course);
        $filename = 'curriculum_template_'.Str::slug($course->code ?? $course->title).'.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            // Headers
            fputcsv($handle, [
                'module_title',
                'module_order',
                'module_description',
                'lesson_number',
                'lesson_title',
                'duration_minutes',
                'video_url',
                'pdf_url',
                'summary',
            ]);
            // Sample Rows for reference
            fputcsv($handle, [
                'Module 1: Foundations & Core Principles',
                '1',
                'Fundamental definitions, baseline framework, and theoretical orientation.',
                '1',
                'Orientation, Core Axioms & Methodology',
                '45',
                'https://www.youtube.com/watch?v=example1',
                '',
                'Introductory lecture introducing core axioms, notation, and essential framework.',
            ]);
            fputcsv($handle, [
                'Module 1: Foundations & Core Principles',
                '1',
                'Fundamental definitions, baseline framework, and theoretical orientation.',
                '2',
                'Structured Taxonomy & Analytical Problem Breakdown',
                '60',
                '',
                '',
                'Step-by-step application of baseline models to complex domain scenarios.',
            ]);
            fputcsv($handle, [
                'Module 2: Advanced System Analysis & Synthesis',
                '2',
                'Applied models, case methodologies, and multi-variable synthesis.',
                '3',
                'Dynamic Systems & Optimization Strategies',
                '60',
                '',
                '',
                'Mathematical optimization and case validations.',
            ]);
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }

    /**
     * Import curriculum structure and lessons in bulk from uploaded CSV.
     */
    public function importCurriculumCsv(Request $request, int $courseId): RedirectResponse
    {
        $user = Auth::user();
        if (! $user || (! $user->isAdmin() && ! $user->isTeacher())) {
            abort(403);
        }

        $course = Course::findOrFail($courseId);
        $this->authorizeCourseManager($course);

        $request->validate([
            'curriculum_csv' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        $stats = $this->processCurriculumCsv($course, $request->file('curriculum_csv'));

        return back()->with('success', "Curriculum CSV imported successfully: {$stats['modules']} module(s) and {$stats['lessons']} lesson(s) loaded into course structure!");
    }

    /**
     * Process CSV file rows into CourseModule and CourseLesson records.
     */
    private function processCurriculumCsv(Course $course, UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');
        if (! $handle) {
            return ['modules' => 0, 'lessons' => 0];
        }

        // Check and strip UTF-8 BOM if present
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $header = fgetcsv($handle);
        if (! $header) {
            fclose($handle);

            return ['modules' => 0, 'lessons' => 0];
        }

        $normalizedHeader = array_map(function ($col) {
            return strtolower(trim(str_replace([' ', '-', '_'], '', $col)));
        }, $header);

        $colIdx = function (array $names) use ($normalizedHeader) {
            foreach ($names as $name) {
                $needle = strtolower(str_replace([' ', '-', '_'], '', $name));
                $idx = array_search($needle, $normalizedHeader, true);
                if ($idx !== false) {
                    return $idx;
                }
            }

            return null;
        };

        $modTitleIdx = $colIdx(['module_title', 'moduletitle', 'module', 'unit', 'chapter', 'section']);
        $modOrderIdx = $colIdx(['module_order', 'moduleorder', 'order', 'week', 'week_number']);
        $modDescIdx = $colIdx(['module_description', 'moduledesc', 'description']);
        $lesNumIdx = $colIdx(['lesson_number', 'lessonnumber', 'lessonno', 'lec_no', 'lecture_number']);
        $lesTitleIdx = $colIdx(['lesson_title', 'lessontitle', 'lesson', 'topic', 'lecture_title']);
        $durIdx = $colIdx(['duration_minutes', 'duration', 'minutes', 'durationmins']);
        $vidIdx = $colIdx(['video_url', 'videourl', 'video', 'youtube']);
        $pdfIdx = $colIdx(['pdf_url', 'pdfurl', 'pdf', 'notes_pdf']);
        $sumIdx = $colIdx(['summary', 'topic_summary', 'notes', 'description']);

        $moduleMap = [];
        $importedModules = 0;
        $importedLessons = 0;
        $rowNum = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNum++;
            if (empty(array_filter($row))) {
                continue;
            }

            // 1. Module
            $modTitle = $modTitleIdx !== null && isset($row[$modTitleIdx]) ? trim($row[$modTitleIdx]) : '';
            if ($modTitle === '') {
                $modTitle = 'General Module';
            }

            $modOrder = $modOrderIdx !== null && isset($row[$modOrderIdx]) && is_numeric(trim($row[$modOrderIdx]))
                ? (int) trim($row[$modOrderIdx])
                : count($moduleMap) + 1;

            $modDesc = $modDescIdx !== null && isset($row[$modDescIdx]) ? trim($row[$modDescIdx]) : null;

            if (! isset($moduleMap[$modTitle])) {
                $module = CourseModule::firstOrCreate(
                    [
                        'course_id' => $course->id,
                        'title' => $modTitle,
                    ],
                    [
                        'order_index' => $modOrder,
                        'week_number' => $modOrder,
                        'description' => $modDesc,
                    ]
                );
                $moduleMap[$modTitle] = $module;
                $importedModules++;
            } else {
                $module = $moduleMap[$modTitle];
            }

            // 2. Lesson
            $lesTitle = $lesTitleIdx !== null && isset($row[$lesTitleIdx]) ? trim($row[$lesTitleIdx]) : '';
            if ($lesTitle === '') {
                $lesTitle = "Lecture {$rowNum}";
            }

            $lesNum = $lesNumIdx !== null && isset($row[$lesNumIdx]) && is_numeric(trim($row[$lesNumIdx]))
                ? (int) trim($row[$lesNumIdx])
                : (CourseLesson::where('course_id', $course->id)->max('lesson_number') ?? 0) + 1;

            $dur = $durIdx !== null && isset($row[$durIdx]) && is_numeric(trim($row[$durIdx])) ? (int) trim($row[$durIdx]) : 45;
            $vidUrl = $vidIdx !== null && isset($row[$vidIdx]) ? trim($row[$vidIdx]) : null;
            $pdfUrl = $pdfIdx !== null && isset($row[$pdfIdx]) ? trim($row[$pdfIdx]) : null;
            $summary = $sumIdx !== null && isset($row[$sumIdx]) ? trim($row[$sumIdx]) : null;

            CourseLesson::updateOrCreate(
                [
                    'course_id' => $course->id,
                    'lesson_number' => $lesNum,
                ],
                [
                    'module_id' => $module->id,
                    'title' => $lesTitle,
                    'duration_minutes' => $dur,
                    'video_url' => $vidUrl ?: null,
                    'pdf_url' => $pdfUrl ?: null,
                    'topic_summary' => $summary,
                    'order_index' => $lesNum,
                    'week_number' => $module->week_number ?? $module->order_index ?? 1,
                    'day_of_week' => 1,
                ]
            );

            $importedLessons++;
        }

        fclose($handle);

        return [
            'modules' => $importedModules,
            'lessons' => $importedLessons,
        ];
    }

    /**
     * Direct manual creation of a curriculum module.
     */
    public function storeModule(Request $request, int $courseId): RedirectResponse
    {
        $user = Auth::user();
        if (! $user || (! $user->isAdmin() && ! $user->isTeacher())) {
            abort(403);
        }

        $course = Course::findOrFail($courseId);
        $this->authorizeCourseManager($course);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'order_index' => 'nullable|integer|min:1',
            'theme' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        $orderIndex = $validated['order_index'] ?? (($course->modules()->max('order_index') ?? 0) + 1);

        CourseModule::create([
            'course_id' => $course->id,
            'title' => $validated['title'],
            'order_index' => $orderIndex,
            'week_number' => $orderIndex,
            'theme' => $validated['theme'] ?? null,
            'description' => $validated['description'] ?? null,
        ]);

        return back()->with('success', "Module \"{$validated['title']}\" added successfully!");
    }

    /**
     * Update an existing curriculum module.
     */
    public function updateModule(Request $request, int $courseId, int $moduleId): RedirectResponse
    {
        $user = Auth::user();
        if (! $user || (! $user->isAdmin() && ! $user->isTeacher())) {
            abort(403);
        }

        $course = Course::findOrFail($courseId);
        $this->authorizeCourseManager($course);
        $module = CourseModule::where('course_id', $course->id)->findOrFail($moduleId);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'order_index' => 'nullable|integer|min:1',
            'theme' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        $module->update($validated);

        return back()->with('success', "Module \"{$module->title}\" updated successfully!");
    }

    /**
     * Delete a curriculum module.
     */
    public function destroyModule(Request $request, int $courseId, int $moduleId): RedirectResponse
    {
        $user = Auth::user();
        if (! $user || (! $user->isAdmin() && ! $user->isTeacher())) {
            abort(403);
        }

        $course = Course::findOrFail($courseId);
        $this->authorizeCourseManager($course);
        $module = CourseModule::where('course_id', $course->id)->findOrFail($moduleId);
        $title = $module->title;

        $module->lessons()->delete();
        $module->delete();

        return back()->with('success', "Module \"{$title}\" and associated lessons were deleted.");
    }

    /**
     * Direct manual creation of a curriculum lesson.
     */
    public function storeLesson(Request $request, int $courseId): RedirectResponse
    {
        $user = Auth::user();
        if (! $user || (! $user->isAdmin() && ! $user->isTeacher())) {
            abort(403);
        }

        $course = Course::findOrFail($courseId);
        $this->authorizeCourseManager($course);

        $validated = $request->validate([
            'module_id' => 'required|exists:course_modules,id',
            'title' => 'required|string|max:255',
            'lesson_number' => 'nullable|integer|min:1',
            'duration_minutes' => 'nullable|integer|min:1|max:480',
            'video_url' => 'nullable|url',
            'pdf_file' => 'nullable|file|mimes:pdf|max:20480',
            'pdf_url' => 'nullable|url',
            'topic_summary' => 'nullable|string',
        ]);

        $module = CourseModule::where('course_id', $course->id)->findOrFail($validated['module_id']);
        $lessonNumber = $validated['lesson_number'] ?? (($course->lessons()->max('lesson_number') ?? 0) + 1);

        $pdfUrl = $validated['pdf_url'] ?? null;
        if ($request->hasFile('pdf_file')) {
            $file = $request->file('pdf_file');
            $fileName = 'course_'.$course->id.'_lesson_'.$lessonNumber.'_'.time().'.pdf';
            $dest = public_path('uploads/course_notes');
            if (! file_exists($dest)) {
                mkdir($dest, 0755, true);
            }
            $file->move($dest, $fileName);
            $pdfUrl = '/uploads/course_notes/'.$fileName;
        }

        CourseLesson::create([
            'course_id' => $course->id,
            'module_id' => $module->id,
            'lesson_number' => $lessonNumber,
            'week_number' => $module->week_number ?? $module->order_index ?? 1,
            'day_of_week' => 1,
            'title' => $validated['title'],
            'duration_minutes' => $validated['duration_minutes'] ?? 45,
            'video_url' => $validated['video_url'] ?? null,
            'pdf_url' => $pdfUrl,
            'topic_summary' => $validated['topic_summary'] ?? null,
            'order_index' => $lessonNumber,
        ]);

        return back()->with('success', "Lesson \"{$validated['title']}\" added successfully to {$module->title}!");
    }

    /**
     * Update an existing lesson.
     */
    public function updateLesson(Request $request, int $courseId, int $lessonId): RedirectResponse
    {
        $user = Auth::user();
        if (! $user || (! $user->isAdmin() && ! $user->isTeacher())) {
            abort(403);
        }

        $course = Course::findOrFail($courseId);
        $this->authorizeCourseManager($course);
        $lesson = CourseLesson::where('course_id', $course->id)->findOrFail($lessonId);

        $validated = $request->validate([
            'module_id' => 'required|exists:course_modules,id',
            'title' => 'required|string|max:255',
            'lesson_number' => 'required|integer|min:1',
            'duration_minutes' => 'nullable|integer|min:1|max:480',
            'video_url' => 'nullable|url',
            'pdf_file' => 'nullable|file|mimes:pdf|max:20480',
            'pdf_url' => 'nullable|url',
            'topic_summary' => 'nullable|string',
        ]);

        if ($request->hasFile('pdf_file')) {
            $file = $request->file('pdf_file');
            $fileName = 'course_'.$course->id.'_lesson_'.$validated['lesson_number'].'_'.time().'.pdf';
            $dest = public_path('uploads/course_notes');
            if (! file_exists($dest)) {
                mkdir($dest, 0755, true);
            }
            $file->move($dest, $fileName);
            $lesson->pdf_url = '/uploads/course_notes/'.$fileName;
        } elseif (! empty($validated['pdf_url'])) {
            $lesson->pdf_url = $validated['pdf_url'];
        }

        $lesson->module_id = $validated['module_id'];
        $lesson->title = $validated['title'];
        $lesson->lesson_number = $validated['lesson_number'];
        $lesson->duration_minutes = $validated['duration_minutes'] ?? $lesson->duration_minutes;
        $lesson->video_url = $validated['video_url'] ?? $lesson->video_url;
        $lesson->topic_summary = $validated['topic_summary'] ?? $lesson->topic_summary;
        $lesson->save();

        return back()->with('success', "Lesson \"{$lesson->title}\" updated successfully!");
    }

    /**
     * Delete an individual lesson.
     */
    public function destroyLesson(Request $request, int $courseId, int $lessonId): RedirectResponse
    {
        $user = Auth::user();
        if (! $user || (! $user->isAdmin() && ! $user->isTeacher())) {
            abort(403);
        }

        $course = Course::findOrFail($courseId);
        $this->authorizeCourseManager($course);
        $lesson = CourseLesson::where('course_id', $course->id)->findOrFail($lessonId);
        $title = $lesson->title;

        $lesson->delete();

        return back()->with('success', "Lesson \"{$title}\" was successfully deleted.");
    }

    private function authorizeCourseManager(Course $course): void
    {
        $user = Auth::user();
        abort_unless($user && ($user->isAdmin() || ($user->isTeacher() && (int) $course->teacher_id === (int) $user->id)), 403,
            'You may manage only courses assigned to you.');
    }
}
