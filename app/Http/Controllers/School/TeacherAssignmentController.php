<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\AcademicClass;
use App\Models\AcademicSubject;
use App\Models\AcademySetting;
use App\Models\SchoolPeriod;
use App\Models\SchoolPeriodTimetable;
use App\Models\SubjectMaster;
use App\Models\TeacherAssignmentAudit;
use App\Models\User;
use App\Services\TeacherAssignmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TeacherAssignmentController extends Controller
{
    protected TeacherAssignmentService $service;

    public function __construct(TeacherAssignmentService $service)
    {
        $this->service = $service;
    }

    /**
     * Display centralized Teacher & Subject Assignment dashboard.
     */
    public function index(Request $request): View
    {
        $tab = $request->query('tab', 'subjects'); // subjects, class_teachers, timetable, audit
        $search = trim($request->query('search', ''));
        $selectedClassId = $request->query('class_id');
        $selectedTeacherId = $request->query('teacher_id');

        // Master Dropdown Data
        $allClasses = AcademicClass::with(['classTeacher', 'subjects.teacher', 'subjects.subjectMaster.defaultTeacher'])
            ->orderBy('grade_level')
            ->orderBy('section')
            ->get();

        $allTeachers = User::where('role', 'teacher')
            ->orderBy('name')
            ->get();

        // 1. Subject Assignments Query
        $subjectsQuery = AcademicSubject::with(['academicClass.classTeacher', 'teacher', 'subjectMaster.defaultTeacher', 'timetables'])
            ->when($selectedClassId, fn ($q) => $q->where('academic_subjects.class_id', $selectedClassId))
            ->when($selectedTeacherId, fn ($q) => $q->where(function ($query) use ($selectedTeacherId) {
                $query->where('academic_subjects.teacher_id', $selectedTeacherId)
                    ->orWhere(function ($defaultTeacher) use ($selectedTeacherId) {
                        $defaultTeacher->whereHas('subjectMaster', fn ($masterQuery) => $masterQuery->where('default_teacher_id', $selectedTeacherId))
                            ->whereHas('academicClass', fn ($classQuery) => $classQuery->where('academic_year', AcademySetting::get('academic_session')));
                    });
            }))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($subQ) use ($search) {
                    $subQ->where('academic_subjects.name', 'like', "%{$search}%")
                        ->orWhere('academic_subjects.code', 'like', "%{$search}%")
                        ->orWhere('academic_classes.name', 'like', "%{$search}%")
                        ->orWhere('academic_classes.section', 'like', "%{$search}%")
                        ->orWhereHas('teacher', fn ($tq) => $tq->where('users.name', 'like', "%{$search}%"));
                });
            })
            ->join('academic_classes', 'academic_subjects.class_id', '=', 'academic_classes.id')
            ->orderBy('academic_classes.grade_level')
            ->orderBy('academic_classes.section')
            ->orderBy('academic_subjects.name')
            ->select('academic_subjects.*');

        $subjects = $subjectsQuery->paginate(25)->withQueryString();

        // 2. Class Teacher Mappings Query
        $classTeachersQuery = AcademicClass::with(['classTeacher', 'subjects.teacher', 'subjects.subjectMaster.defaultTeacher'])
            ->withCount('students')
            ->when($selectedClassId, fn ($q) => $q->where('academic_classes.id', $selectedClassId))
            ->when($selectedTeacherId, fn ($q) => $q->where('academic_classes.class_teacher_id', $selectedTeacherId))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($subQ) use ($search) {
                    $subQ->where('academic_classes.name', 'like', "%{$search}%")
                        ->orWhere('academic_classes.section', 'like', "%{$search}%")
                        ->orWhereHas('classTeacher', fn ($tq) => $tq->where('users.name', 'like', "%{$search}%"));
                });
            })
            ->orderBy('grade_level')
            ->orderBy('section');

        $classesWithTeachers = $classTeachersQuery->paginate(25)->withQueryString();

        // 3. Timetable Period Slots Query
        $timetableSlotsQuery = SchoolPeriodTimetable::with(['academicClass', 'subject', 'teacher'])
            ->when($selectedClassId, fn ($q) => $q->where('school_period_timetables.class_id', $selectedClassId))
            ->when($selectedTeacherId, fn ($q) => $q->where('school_period_timetables.teacher_id', $selectedTeacherId))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($subQ) use ($search) {
                    $subQ->whereHas('teacher', fn ($tq) => $tq->where('users.name', 'like', "%{$search}%"))
                        ->orWhereHas('subject', fn ($sq) => $sq->where('academic_subjects.name', 'like', "%{$search}%"))
                        ->orWhereHas('academicClass', fn ($cq) => $cq->where('academic_classes.name', 'like', "%{$search}%"));
                });
            })
            ->orderByRaw('CASE day_of_week '.collect(config('school_timetable.days', []))->values()->map(fn ($day, $index) => 'WHEN '.DB::getPdo()->quote($day).' THEN '.($index + 1))->implode(' ').' ELSE 999 END')
            ->orderBy('period_number');

        $timetableSlots = $timetableSlotsQuery->paginate(25)->withQueryString();

        // 4. Audit Log Query
        $audits = TeacherAssignmentAudit::with(['admin', 'teacher', 'academicClass', 'subject'])
            ->when($selectedClassId, fn ($q) => $q->where('teacher_assignment_audits.class_id', $selectedClassId))
            ->when($selectedTeacherId, fn ($q) => $q->where('teacher_assignment_audits.teacher_id', $selectedTeacherId))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($subQ) use ($search) {
                    $subQ->where('description', 'like', "%{$search}%")
                        ->orWhere('teacher_name', 'like', "%{$search}%")
                        ->orWhere('class_name', 'like', "%{$search}%")
                        ->orWhere('subject_name', 'like', "%{$search}%");
                });
            })
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        // Compact Overview Metrics
        $totalTeachers = $allTeachers->count();
        $assignedTeacherIds = AcademicSubject::whereNotNull('teacher_id')->pluck('teacher_id')
            ->concat(AcademicClass::whereNotNull('class_teacher_id')->pluck('class_teacher_id'))
            ->unique();
        $assignedTeachersCount = $assignedTeacherIds->count();

        $stats = [
            'total_teachers' => $totalTeachers,
            'assigned_teachers' => $assignedTeachersCount,
            'unassigned_teachers' => max(0, $totalTeachers - $assignedTeachersCount),
            'total_subjects' => AcademicSubject::count(),
            'unassigned_subjects' => AcademicSubject::whereNull('teacher_id')->count(),
            'total_classes' => $allClasses->count(),
            'unassigned_class_teachers' => AcademicClass::whereNull('class_teacher_id')->count(),
            'total_timetable_slots' => SchoolPeriodTimetable::count(),
            'period_timings' => SchoolPeriod::active()->where('is_break', false)->get()->mapWithKeys(fn ($period) => [$period->period_number => ['label' => $period->name.' ('.substr((string) $period->start_time, 0, 5).' - '.substr((string) $period->end_time, 0, 5).')']]),
        ];

        $allMasterSubjects = SubjectMaster::active()->orderBy('category')->orderBy('name')->get();

        return view('school.assignments.index', compact(
            'allClasses',
            'allTeachers',
            'allMasterSubjects',
            'subjects',
            'classesWithTeachers',
            'timetableSlots',
            'audits',
            'tab',
            'search',
            'selectedClassId',
            'selectedTeacherId',
            'stats'
        ));
    }

    /**
     * Assign teacher to a new or existing subject for a class/section.
     * Prevents manual typing, duplicate subjects, and invalid entries.
     */
    public function storeSubject(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'class_id' => 'required|exists:academic_classes,id',
            'subject_id' => 'nullable|exists:academic_subjects,id',
            'teacher_id' => 'nullable|exists:users,id',
            'teacher_override' => 'nullable|boolean',
            'name' => 'required_without:subject_id|nullable|string|max:120',
            'code' => 'required_without:subject_id|nullable|string|max:30',
            'weekly_periods' => 'nullable|integer|min:1|max:40',
            'apply_globally' => 'nullable|boolean',
            'link_timetable' => 'nullable|boolean',
            'day_of_week' => ['required_if:link_timetable,1', 'nullable', 'string', Rule::in(config('school_timetable.days', []))],
            'period_number' => 'required_if:link_timetable,1|nullable|integer|exists:school_periods,period_number',
            'room_number' => 'nullable|string|max:50',
        ]);

        $validated['link_timetable'] = $request->boolean('link_timetable');

        // New subject mappings match an active master record, or register one in the master catalogue.
        if (empty($validated['subject_id']) && ! empty($validated['name'])) {
            $class = AcademicClass::findOrFail($validated['class_id']);
            $cleanName = trim($validated['name']);
            $cleanCode = strtoupper(trim($validated['code'] ?? ''));
            $baseCode = preg_replace('/-[0-9]+[A-Za-z]*$/', '', $cleanCode);

            $master = SubjectMaster::active()->where(function ($query) use ($cleanName, $baseCode) {
                $query->whereRaw('UPPER(name) = ?', [strtoupper($cleanName)])
                    ->orWhereRaw('UPPER(code) = ?', [$baseCode]);
            })->first();

            if (! $master) {
                $master = SubjectMaster::create([
                    'name' => $cleanName,
                    'code' => $baseCode ?: strtoupper(substr($cleanName, 0, 4)),
                    'stream' => $class->stream ?: 'All',
                    'min_grade' => min(1, (int) $class->grade_level),
                    'max_grade' => max(12, (int) $class->grade_level),
                    'default_periods' => $validated['weekly_periods'] ?? 5,
                    'is_active' => true,
                ]);
            }

            $validated['subject_master_id'] = $master->id;
            $validated['name'] = $cleanName;
            $validated['code'] = $cleanCode ?: $master->generateCodeForGrade($class->grade_level);
            $validated['weekly_periods'] = $validated['weekly_periods'] ?? $master->default_periods ?? 5;
            if (! $validated['weekly_periods'] || (int) $validated['weekly_periods'] < 1 || (int) $validated['weekly_periods'] > 40) {
                return redirect()->back()->withInput()->withErrors(['weekly_periods' => 'Set a valid weekly period load in the Subject Master or this assignment form.']);
            }
        }

        try {
            $subject = $this->service->assignSubject(Auth::user(), $validated, $request);
            $msg = "Assignment saved: Subject '{$subject->name}' for {$subject->academicClass->display_name} successfully mapped.";

            return redirect()->route('school.assignments.index', ['tab' => 'subjects', 'class_id' => $subject->class_id])
                ->with('success', $msg);
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        }
    }

    /**
     * Update existing subject assignment.
     */
    public function updateSubject(Request $request, int $id): RedirectResponse
    {
        $subject = AcademicSubject::with('academicClass')->findOrFail($id);

        $validated = $request->validate([
            'teacher_id' => 'nullable|integer|exists:users,id',
            'teacher_override' => 'nullable|boolean',
            'name' => 'nullable|string|max:120',
            'code' => 'nullable|string|max:30',
            'weekly_periods' => 'required|integer|min:1|max:40',
            'apply_globally' => 'nullable|boolean',
        ]);
        if (isset($validated['name'])) {
            $validated['name'] = trim($validated['name']);
        }
        if (isset($validated['code'])) {
            $validated['code'] = strtoupper(trim($validated['code']));
        }

        try {
            $this->service->updateSubjectAssignment(Auth::user(), $subject, $validated, $request);

            return redirect()->back()->with('success', "Updated assignment for '{$subject->name}' in {$subject->academicClass->display_name}.");
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        }
    }

    /**
     * Remove or unassign subject teacher.
     */
    public function destroySubject(Request $request, int $id): RedirectResponse
    {
        $subject = AcademicSubject::with('academicClass')->findOrFail($id);
        $deleteSubject = $request->boolean('delete_subject', false);

        try {
            $this->service->removeSubjectAssignment(Auth::user(), $subject, $deleteSubject, $request);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        $msg = $deleteSubject
            ? "Subject '{$subject->name}' has been deleted."
            : "Teacher unassigned from '{$subject->name}'.";

        return redirect()->back()->with('success', $msg);
    }

    /**
     * Assign Class Teacher (Homeroom).
     */
    public function assignClassTeacher(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'class_id' => 'required|exists:academic_classes,id',
            'teacher_id' => 'required|exists:users,id',
        ]);

        $class = AcademicClass::findOrFail($validated['class_id']);

        try {
            $this->service->assignClassTeacher(Auth::user(), $class, (int) $validated['teacher_id'], $request);

            return redirect()->route('school.assignments.index', ['tab' => 'class_teachers'])
                ->with('success', "Class Teacher for {$class->display_name} assigned successfully.");
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        }
    }

    /**
     * Remove Class Teacher assignment.
     */
    public function removeClassTeacher(Request $request, int $classId): RedirectResponse
    {
        $class = AcademicClass::findOrFail($classId);
        $this->service->removeClassTeacher(Auth::user(), $class, $request);

        return redirect()->back()->with('success', "Class Teacher unassigned from {$class->display_name}.");
    }

    /**
     * Create or update a timetable period assignment with conflict validation.
     */
    public function storeTimetable(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'class_id' => 'required|exists:academic_classes,id',
            'teacher_id' => 'nullable|exists:users,id',
            'subject_id' => 'required|exists:academic_subjects,id',
            'teacher_override' => 'nullable|boolean',
            'day_of_week' => ['required', 'string', Rule::in(config('school_timetable.days', []))],
            'period_number' => 'required|integer|exists:school_periods,period_number',
            'room_number' => 'nullable|string|max:50',
            'slot_id' => 'nullable|exists:school_period_timetables,id',
        ]);

        try {
            $slot = $this->service->assignTimetableSlot(Auth::user(), $validated, $request);

            return redirect()->route('school.assignments.index', ['tab' => 'timetable', 'class_id' => $slot->class_id])
                ->with('success', "Period {$slot->period_number} on {$slot->day_of_week} mapped successfully.");
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        }
    }

    /**
     * Remove a timetable period assignment.
     */
    public function destroyTimetable(Request $request, int $id): RedirectResponse
    {
        $slot = SchoolPeriodTimetable::findOrFail($id);
        $this->service->removeTimetableSlot(Auth::user(), $slot, $request);

        return redirect()->back()->with('success', 'Timetable period slot removed.');
    }
}
