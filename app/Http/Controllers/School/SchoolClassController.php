<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\AcademicClass;
use App\Models\AcademicSubject;
use App\Models\AcademySetting;
use App\Models\ClassEnrollment;
use App\Models\SchoolSection;
use App\Models\SchoolStream;
use App\Models\SubjectMaster;
use App\Models\User;
use App\Services\SchoolAcademicAuditService;
use App\Services\SubjectCurriculumInheritanceService;
use App\Services\TeacherAssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SchoolClassController extends Controller
{
    /**
     * Display listing of all school classes and sections.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        if ($user && ! $user->isAdmin() && ! $user->isTeacher()) {
            abort(403, 'Unauthorized. Class management is reserved for faculty and administrators.');
        }

        $tab = $request->input('tab', 'classes');
        $search = $request->input('search');

        $query = AcademicClass::with(['classTeacher', 'subjects.teacher', 'schoolSection'])
            ->withCount(['students', 'subjects'])
            ->orderBy('grade_level')
            ->orderBy('stream')
            ->orderBy('section');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('stream', 'like', "%{$search}%")
                    ->orWhere('section', 'like', "%{$search}%")
                    ->orWhere('room_number', 'like', "%{$search}%");
            });
        }

        if ($user && $user->isTeacher()) {
            $teacherId = $user->id;
            $query->where(function ($q) use ($teacherId) {
                $q->where('class_teacher_id', $teacherId)
                    ->orWhereHas('subjects', fn ($sq) => $sq->where('teacher_id', $teacherId))
                    ->orWhere(function ($defaultSubject) use ($teacherId) {
                        $defaultSubject->where('academic_year', AcademySetting::get('academic_session'))
                            ->whereHas('subjects.subjectMaster', fn ($sq) => $sq->where('default_teacher_id', $teacherId));
                    })
                    ->orWhereHas('timetables', fn ($tq) => $tq->where('teacher_id', $teacherId));
            });
        }

        $classes = $query->paginate(15);
        $teachers = User::where('role', 'teacher')->orderBy('name')->get();

        // Reusable sections list grouped by stream
        $allReusableSections = SchoolSection::where('is_active', true)->withCount('classes')
            ->orderBy('stream')
            ->orderBy('name')
            ->get();

        $sectionsByStream = $allReusableSections->groupBy('stream');
        $streams = SchoolStream::query()->active()->orderBy('name')->pluck('name', 'name')->all();
        $academicYears = AcademicClass::query()->whereNotNull('academic_year')->distinct()->orderByDesc('academic_year')->pluck('academic_year')->all();
        $configuredSession = AcademySetting::get('academic_session');
        $currentSession = $configuredSession;
        if ($configuredSession && ! in_array($configuredSession, $academicYears, true)) {
            array_unshift($academicYears, $configuredSession);
        }

        $reusableSections = SchoolSection::where('is_active', true)->withCount('classes')
            ->orderBy('stream')
            ->orderBy('name')
            ->paginate(12, ['*'], 'sections_page');

        $stats = [
            'total_classes' => AcademicClass::count(),
            'total_students' => ClassEnrollment::where('status', 'active')->count(),
            'total_subjects' => AcademicSubject::count(),
            'assigned_teachers' => AcademicClass::whereNotNull('class_teacher_id')->distinct('class_teacher_id')->count(),
            'total_sections' => SchoolSection::count(),
        ];

        $allExistingClasses = AcademicClass::select('id', 'name', 'grade_level', 'stream', 'section', 'academic_year')->get();

        $defaultStream = config('school.onboarding.default_stream');

        return view('school.classes.index', compact('classes', 'teachers', 'stats', 'search', 'reusableSections', 'allReusableSections', 'sectionsByStream', 'streams', 'tab', 'allExistingClasses', 'academicYears', 'defaultStream', 'currentSession'));
    }

    /**
     * Show single class details with subjects, student roster and timetable.
     */
    public function show(int $id): View
    {
        $user = Auth::user();
        if ($user && ! $user->isAdmin() && ! $user->isTeacher()) {
            abort(403, 'Unauthorized. Class management is reserved for faculty and administrators.');
        }

        $class = AcademicClass::with([
            'classTeacher',
            'schoolSection',
            'subjects.teacher',
            'subjects.subjectMaster.defaultTeacher',
            'timetables.subject',
            'timetables.teacher',
            'feeStructures',
        ])->withCount('students')->findOrFail($id);

        if ($user && $user->isTeacher()) {
            $isAssigned = ($class->class_teacher_id === $user->id)
                || $class->subjects()->where('teacher_id', $user->id)->exists()
                || $class->timetables()->where('teacher_id', $user->id)->exists();

            if (! $isAssigned) {
                abort(403, 'Unauthorized. You are not assigned to this class.');
            }
        }

        $teachers = User::where('role', 'teacher')->orderBy('name')->get();
        $studentSearch = trim((string) request()->query('student_search', ''));
        $enrolledStudentsQuery = $class->students();
        if ($studentSearch !== '') {
            $enrolledStudentsQuery->where(function ($query) use ($studentSearch) {
                $query->where('users.name', 'like', '%'.$studentSearch.'%')
                    ->orWhere('users.admission_number', 'like', '%'.$studentSearch.'%')
                    ->orWhere('class_enrollments.admission_number', 'like', '%'.$studentSearch.'%')
                    ->orWhere('class_enrollments.roll_number', 'like', '%'.$studentSearch.'%');
            });
        }
        $enrolledStudents = $enrolledStudentsQuery
            ->orderBy('class_enrollments.roll_number')
            ->paginate(15, ['*'], 'students_page')->withQueryString();

        $classSubjects = $class->subjects()
            ->with('teacher')
            ->paginate(10, ['*'], 'subjects_page');

        // Get reusable sections under this class's stream for editing
        $stream = $class->stream;
        $availableSections = SchoolSection::where('is_active', true)
            ->where('stream', $stream)
            ->orderBy('name')
            ->get();

        $allSections = SchoolSection::where('is_active', true)
            ->orderBy('stream')
            ->orderBy('name')
            ->get();

        $streams = SchoolStream::query()->active()->orderBy('name')->pluck('name', 'name')->all();
        $academicYears = AcademicClass::query()->whereNotNull('academic_year')->distinct()->orderByDesc('academic_year')->pluck('academic_year')->all();
        $configuredSession = AcademySetting::get('academic_session');
        $currentSession = $configuredSession;
        if ($configuredSession && ! in_array($configuredSession, $academicYears, true)) {
            array_unshift($academicYears, $configuredSession);
        }
        $masterSubjects = SubjectMaster::getAvailableForClass($class);

        return view('school.classes.show', compact('class', 'teachers', 'enrolledStudents', 'classSubjects', 'availableSections', 'allSections', 'streams', 'masterSubjects', 'academicYears', 'studentSearch', 'currentSession'));
    }

    /**
     * Store newly created class(es) by selecting existing section(s).
     * No free-text section entry allowed.
     * Class = Class Grade/Name + Stream + Selected Section(s).
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $user = Auth::user();
        if (! $user || ! $user->isAdmin()) {
            abort(403, 'Only administrators can create classes.');
        }

        $validated = $request->validate([
            'grade_level' => ['required', 'integer', Rule::in(config('school.grade_levels', []))],
            'stream' => ['required', 'string', 'max:50', Rule::exists('school_streams', 'name')->where('is_active', true)],
            'name' => 'nullable|string|max:100',
            'section_ids' => 'required|array|min:1',
            'section_ids.*' => 'required|integer|distinct|exists:school_sections,id',
            'academic_year' => 'required|string|max:30',
            'class_teacher_id' => 'nullable|integer|exists:users,id',
            'room_number' => 'nullable|string|max:50',
            'capacity' => 'required|integer|min:'.config('school.capacity.minimum').'|max:'.config('school.capacity.maximum'),
        ]);

        $teacherId = $validated['class_teacher_id'] ?? null;
        if ($teacherId) {
            $teacher = User::findOrFail($teacherId);
            if (! $teacher->isTeacher()) {
                return back()->withInput()->withErrors(['class_teacher_id' => 'Select an active teacher account.']);
            }
        }

        $stream = trim($validated['stream']);
        $sections = SchoolSection::query()
            ->whereIn('id', $validated['section_ids'])
            ->where('is_active', true)
            ->lockForUpdate()
            ->get();

        if ($sections->count() !== count($validated['section_ids']) || $sections->contains(fn ($section) => strcasecmp($section->stream, $stream) !== 0 || ! preg_match('/^[A-Z]$/i', $section->name))) {
            return back()->withInput()->withErrors(['section_ids' => 'Choose active A-Z sections belonging to the selected stream.']);
        }
        $maxSectionCapacity = $sections->min('capacity');
        if (! $maxSectionCapacity || (int) $maxSectionCapacity < (int) config('school.capacity.minimum')) {
            return back()->withInput()->withErrors(['section_ids' => 'Configure a valid capacity in every selected Section Master before creating a class.']);
        }
        if ((int) $validated['capacity'] > (int) $maxSectionCapacity) {
            return back()->withInput()->withErrors(['capacity' => "Class capacity cannot exceed the selected section master capacity of {$maxSectionCapacity}."]);
        }

        $grade = (int) $validated['grade_level'];
        $className = trim($validated['name'] ?? "Class {$grade}");
        $created = [];
        $duplicates = [];
        $inheritedSubjects = 0;
        $inheritanceWarnings = [];

        DB::transaction(function () use ($sections, $grade, $stream, $validated, $teacherId, $className, $user, $request, &$created, &$duplicates, &$inheritedSubjects, &$inheritanceWarnings) {
            foreach ($sections as $section) {
                $existingClass = AcademicClass::query()
                    ->where('grade_level', $grade)
                    ->where('stream', $stream)
                    ->where('section', $section->name)
                    ->where('academic_year', $validated['academic_year'])
                    ->lockForUpdate()
                    ->first();

                if ($existingClass) {
                    $duplicates[] = $section->name;
                    $classRecord = $existingClass;
                } else {
                    $classRecord = AcademicClass::create([
                        'name' => $className,
                        'grade_level' => $grade,
                        'stream' => $stream,
                        'section' => $section->name,
                        'section_id' => $section->id,
                        'academic_year' => $validated['academic_year'],
                        'class_teacher_id' => $teacherId,
                        'room_number' => $validated['room_number'] ?? $section->room_number,
                        'capacity' => (int) $validated['capacity'],
                    ]);
                    $created[] = $classRecord;
                }
                $inheritance = app(SubjectCurriculumInheritanceService::class)->inheritForClass($classRecord, $user, $request);
                $inheritedSubjects += $inheritance['created'] + $inheritance['linked'];
                $inheritanceWarnings = array_merge($inheritanceWarnings, $inheritance['skipped']);
            }
        }, 3);

        foreach ($created as $createdClass) {
            app(SchoolAcademicAuditService::class)->log(
                $user,
                'created_academic_class',
                'Created class '.$createdClass->display_name.' for academic year '.$createdClass->academic_year.'.',
                null,
                $createdClass->only(['id', 'name', 'grade_level', 'stream', 'section', 'academic_year', 'class_teacher_id', 'room_number', 'capacity']),
                $request,
                $createdClass->id
            );
        }

        if (! $created && $inheritedSubjects === 0) {
            $message = 'No new class sections or missing subject mappings were found for the selected academic year.';

            return $request->wantsJson()
                ? response()->json(['success' => false, 'message' => $message, 'errors' => ['section_ids' => [$message]]], 422)
                : back()->withInput()->withErrors(['section_ids' => $message]);
        }

        $message = count($created).' new class section(s) created; '.$inheritedSubjects.' subject mappings created or linked from the curriculum master.';
        if ($duplicates) {
            $message .= ' Existing sections retained: '.implode(', ', $duplicates).'.';
        }
        if ($inheritanceWarnings) {
            $message .= ' Curriculum needs configuration: '.implode(' ', array_unique($inheritanceWarnings));
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'created_count' => count($created),
                'redirect_url' => count($created) === 1 ? route('school.classes.show', $created[0]->id) : route('school.classes.index'),
            ], 201);
        }

        return count($created) === 1
            ? redirect()->route('school.classes.show', $created[0]->id)->with('success', $message)
            : redirect()->route('school.classes.index')->with('success', $message);
    }

    /**
     * Update an existing class.
     * Section must be selected from existing reusable sections under the stream.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $user = Auth::user();
        if (! $user || ! $user->isAdmin()) {
            abort(403, 'Only administrators can modify classes.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'grade_level' => ['required', 'integer', Rule::in(config('school.grade_levels', []))],
            'stream' => ['required', 'string', 'max:50', Rule::exists('school_streams', 'name')->where('is_active', true)],
            'section_id' => 'required|integer|exists:school_sections,id',
            'academic_year' => 'required|string|max:30',
            'class_teacher_id' => 'nullable|integer|exists:users,id',
            'room_number' => 'nullable|string|max:50',
            'capacity' => 'required|integer|min:'.config('school.capacity.minimum').'|max:'.config('school.capacity.maximum'),
        ]);

        $classBefore = AcademicClass::findOrFail($id);
        $oldClassValues = $classBefore->only(['name', 'grade_level', 'stream', 'section', 'section_id', 'academic_year', 'class_teacher_id', 'room_number', 'capacity']);

        DB::transaction(function () use ($validated, $id) {
            $class = AcademicClass::query()->lockForUpdate()->findOrFail($id);
            $activeEnrollments = $class->enrollments()->where('status', 'active')->count();
            if ((int) $validated['capacity'] < $activeEnrollments) {
                throw ValidationException::withMessages([
                    'capacity' => "Capacity cannot be lower than the {$activeEnrollments} active enrollments.",
                ]);
            }

            $section = SchoolSection::query()->whereKey($validated['section_id'])->where('is_active', true)->firstOrFail();
            if (strcasecmp($section->stream, $validated['stream']) !== 0) {
                throw ValidationException::withMessages([
                    'section_id' => 'The selected section does not belong to the selected stream.',
                ]);
            }
            if (! $section->capacity || (int) $section->capacity < (int) config('school.capacity.minimum')) {
                throw ValidationException::withMessages(['section_id' => 'Configure a valid capacity in Section Master before updating this class.']);
            }
            if ((int) $validated['capacity'] > (int) $section->capacity) {
                throw ValidationException::withMessages([
                    'capacity' => 'Class capacity cannot exceed the selected section master capacity.',
                ]);
            }

            $structuralChange = (int) $class->grade_level !== (int) $validated['grade_level']
                || strcasecmp((string) $class->stream, (string) $validated['stream']) !== 0
                || (int) $class->section_id !== (int) $section->id
                || (string) $class->academic_year !== (string) $validated['academic_year'];

            $hasAcademicDependencies = $class->enrollments()->exists()
                || $class->subjects()->exists()
                || $class->timetables()->exists()
                || $class->feeStructures()->exists()
                || $class->examSchedules()->exists()
                || $class->attendanceSessions()->exists()
                || $class->reportCards()->exists()
                || $class->lessons()->exists()
                || $class->schedules()->exists()
                || $class->batches()->exists()
                || $class->lessonPlans()->exists();

            if ($structuralChange && $hasAcademicDependencies) {
                throw ValidationException::withMessages([
                    'section_id' => 'A class with enrollment or academic history cannot be structurally changed here. Create a new class for the target academic year and use the formal student transfer/promotion workflow.',
                ]);
            }

            if (! empty($validated['class_teacher_id'])) {
                $teacher = User::findOrFail($validated['class_teacher_id']);
                if (! $teacher->isTeacher()) {
                    throw ValidationException::withMessages([
                        'class_teacher_id' => 'Select an active teacher account.',
                    ]);
                }
            }

            $duplicate = AcademicClass::query()
                ->where('id', '!=', $class->id)
                ->where('grade_level', $validated['grade_level'])
                ->where('stream', $validated['stream'])
                ->where('section_id', $section->id)
                ->where('academic_year', $validated['academic_year'])
                ->exists();
            if ($duplicate) {
                throw ValidationException::withMessages([
                    'section_id' => 'This grade, stream, section and academic-year combination already exists.',
                ]);
            }

            $class->update([
                'name' => trim($validated['name']),
                'grade_level' => $validated['grade_level'],
                'stream' => trim($validated['stream']),
                'section' => $section->name,
                'section_id' => $section->id,
                'academic_year' => $validated['academic_year'],
                'class_teacher_id' => $validated['class_teacher_id'] ?? null,
                'room_number' => $validated['room_number'] ?? null,
                'capacity' => (int) $validated['capacity'],
            ]);
        }, 3);

        $updatedClass = AcademicClass::findOrFail($id);
        app(SchoolAcademicAuditService::class)->log($user, 'updated_academic_class', 'Updated class '.$updatedClass->display_name.'.', $oldClassValues, $updatedClass->only(['name', 'grade_level', 'stream', 'section', 'section_id', 'academic_year', 'class_teacher_id', 'room_number', 'capacity']), $request, $updatedClass->id);

        return redirect()->back()->with('success', 'Class details updated successfully.');
    }

    /**
     * Delete a class.
     */
    public function destroy(int $id): RedirectResponse
    {
        $user = Auth::user();
        if (! $user || ! $user->isAdmin()) {
            abort(403, 'Only administrators can delete classes.');
        }

        $class = AcademicClass::findOrFail($id);
        $hasHistory = $class->enrollments()->exists()
            || $class->subjects()->exists()
            || $class->timetables()->exists()
            || $class->feeStructures()->exists()
            || $class->examSchedules()->exists()
            || $class->attendanceSessions()->exists()
            || $class->reportCards()->exists()
            || $class->lessons()->exists()
            || $class->schedules()->exists()
            || $class->batches()->exists()
            || $class->lessonPlans()->exists();

        if ($hasHistory) {
            return redirect()->route('school.classes.index')->with('error',
                'This class has enrollment or academic/financial history and cannot be permanently deleted. Preserve it and use an approved archival workflow instead.');
        }

        $name = $class->display_name;
        app(SchoolAcademicAuditService::class)->log($user, 'deleted_empty_academic_class', 'Deleted empty class '.$name.'.', $class->only(['id', 'name', 'grade_level', 'stream', 'section', 'academic_year']), null, request(), $class->id);
        $class->delete();

        return redirect()->route('school.classes.index')->with('success', "Empty class {$name} deleted.");
    }

    /**
     * Return JSON options from the Subject Master for dynamic dropdown selectors.
     */
    public function masterOptions(Request $request): JsonResponse
    {
        $classId = $request->input('class_id');
        $stream = $request->input('stream');

        if ($classId) {
            $class = AcademicClass::with('subjects.teacher')->find($classId);
            if ($class) {
                $viewer = Auth::user();
                if ($viewer && $viewer->isTeacher()) {
                    $assigned = $class->class_teacher_id === $viewer->id
                        || $class->subjects()->where('teacher_id', $viewer->id)->exists()
                        || $class->timetables()->where('teacher_id', $viewer->id)->exists();
                    abort_unless($assigned, 403);
                }
                $stream = $class->stream;
                $masters = SubjectMaster::getAvailableForClass($class);
                $classSubjects = $class->subjects->map(fn ($s) => [
                    'id' => $s->id,
                    'name' => $s->name,
                    'code' => $s->code,
                    'weekly_periods' => $s->weekly_periods,
                    'teacher_id' => $s->teacher_id,
                    'teacher_name' => $s->teacher?->name,
                ]);

                return response()->json([
                    'class_id' => $class->id,
                    'class_name' => $class->display_name,
                    'stream' => $stream,
                    'grade' => $class->grade_level,
                    'masters' => $masters,
                    'class_subjects' => $classSubjects,
                ]);
            }
        }

        $stream = $stream ?: 'All';
        $masters = SubjectMaster::forStream($stream)->orderBy('category')->orderBy('name')->get();

        return response()->json([
            'stream' => $stream,
            'masters' => $masters,
            'class_subjects' => [],
        ]);
    }

    /**
     * Add a subject to a class using the Subject Master dropdown.
     * Prevents duplicate entries and ensures valid subject master selection.
     */
    public function storeSubject(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $user = Auth::user();
        if (! $user || ! $user->isAdmin()) {
            abort(403, 'Only administrators can change a class curriculum.');
        }

        $class = AcademicClass::findOrFail($id);
        $validated = $request->validate([
            'subject_master_id' => 'required|integer|exists:subject_masters,id',
            'teacher_id' => 'nullable|integer|exists:users,id',
            'teacher_override' => 'nullable|boolean',
            'weekly_periods' => 'required|integer|min:1|max:40',
        ]);

        $master = SubjectMaster::active()->findOrFail($validated['subject_master_id']);
        if (! in_array($master->stream, ['All', $class->stream], true) || (int) $master->min_grade > (int) $class->grade_level || (int) $master->max_grade < (int) $class->grade_level) {
            return back()->withInput()->withErrors(['subject_master_id' => 'This subject is not available for the selected class stream or grade range.']);
        }

        $teacherId = $validated['teacher_id'] ?? $master->default_teacher_id;
        $teacher = $teacherId ? User::findOrFail($teacherId) : null;
        if ($teacher && ! $teacher->isTeacher()) {
            return back()->withInput()->withErrors(['teacher_id' => 'Select an active teacher account.']);
        }
        if ($master->default_teacher_id && $teacher && (int) $master->default_teacher_id !== (int) $teacher->id && empty($validated['teacher_override'])) {
            return back()->withInput()->withErrors(['teacher_id' => 'This subject has a global default teacher. Enable override to use another teacher for this class.']);
        }

        $code = $master->generateCodeForGrade($class->grade_level);
        $duplicate = AcademicSubject::where('class_id', $class->id)
            ->where(function ($query) use ($code, $master) {
                $query->where('subject_master_id', $master->id)
                    ->orWhereRaw('UPPER(code) = ?', [strtoupper($code)])
                    ->orWhereRaw('UPPER(name) = ?', [strtoupper($master->name)]);
            })->exists();
        if ($duplicate) {
            return back()->withInput()->withErrors(['subject_master_id' => 'This subject is already assigned to this class.']);
        }

        try {
            $subject = app(TeacherAssignmentService::class)->assignSubject($user, [
                'class_id' => $class->id,
                'subject_master_id' => $master->id,
                'name' => $master->name,
                'code' => $code,
                'teacher_id' => $teacher?->id,
                'teacher_override' => (bool) ($validated['teacher_override'] ?? false),
                'weekly_periods' => $validated['weekly_periods'],
            ], $request);
        } catch (ValidationException $exception) {
            return back()->withInput()->withErrors($exception->errors());
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'subject' => $subject->load('teacher')], 201);
        }

        return back()->with('success', "{$subject->name} added to {$class->display_name}.");
    }

    /** Remove a class subject only when no academic results/content depend on it. */
    public function destroySubject(int $id, int $subjectId): RedirectResponse
    {
        abort_unless(Auth::user()?->isAdmin(), 403);
        $subject = AcademicSubject::where('class_id', $id)->findOrFail($subjectId);
        $hasHistory = $subject->examMarks()->exists()
            || $subject->examSchedules()->exists()
            || $subject->lessonPlans()->exists()
            || $subject->topics()->exists()
            || $subject->videos()->exists()
            || $subject->dpps()->exists()
            || $subject->homeworkTasks()->exists();

        if ($hasHistory) {
            return back()->with('error', 'This subject has academic history or learning content. Unassign its teacher instead; do not delete the subject record.');
        }

        try {
            app(TeacherAssignmentService::class)->removeSubjectAssignment(Auth::user(), $subject, true, request());
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return back()->with('success', "Subject '{$subject->name}' removed from the class. Its master curriculum record remains available.");
    }
}
