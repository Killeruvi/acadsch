<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\AcademicClass;
use App\Models\AcademicSubject;
use App\Models\AcademySetting;
use App\Models\ClassEnrollment;
use App\Models\SchoolPeriod;
use App\Models\SchoolPeriodSubstitute;
use App\Models\SchoolPeriodTimetable;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\SchoolAcademicAuditService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SchoolTimetableController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();
        abort_unless($user, 403);
        abort_unless($user->isAdmin() || $user->isTeacher() || $user->isStudent() || $user->isParent(), 403);

        $viewType = $request->input('view', 'class');
        abort_unless(in_array($viewType, ['class', 'teacher'], true), 422, 'Invalid timetable view.');

        $children = collect();
        $activeChild = null;
        $classesQuery = AcademicClass::query()->with(['subjects.teacher', 'subjects.subjectMaster.defaultTeacher', 'classTeacher']);
        $teachersQuery = User::where('role', 'teacher')->orderBy('name');

        if ($user->isTeacher()) {
            $teacherId = $user->id;
            $classesQuery->where(function ($query) use ($teacherId) {
                $query->where('class_teacher_id', $teacherId)
                    ->orWhereHas('subjects', fn ($subjects) => $subjects->where('teacher_id', $teacherId))
                    ->orWhereHas('timetables', fn ($slots) => $slots->where('teacher_id', $teacherId));
            });
            $teachersQuery->whereKey($user->id);
            $viewType = $request->has('view') ? $viewType : 'teacher';
        } elseif ($user->isStudent()) {
            $activeEnrollmentClassId = ClassEnrollment::where('student_id', $user->id)->where('status', 'active')->latest('id')->value('class_id');
            $classesQuery->whereKey($activeEnrollmentClassId ?? 0);
            $viewType = 'class';
        } elseif ($user->isParent()) {
            $children = User::childrenOf($user)->with('currentClass')->orderBy('name')->get();
            $requestedChildId = $request->input('child_id', session('parent_active_student_id'));
            if ($requestedChildId) {
                $activeChild = $children->firstWhere('id', (int) $requestedChildId);
                if (! $activeChild) {
                    abort(404, 'The selected student is not linked to this parent account.');
                }
            } else {
                $activeChild = $children->first();
            }
            if ($activeChild) {
                session(['parent_active_student_id' => $activeChild->id]);
                $activeEnrollmentClassId = ClassEnrollment::where('student_id', $activeChild->id)->where('status', 'active')->latest('id')->value('class_id');
                $classesQuery->whereKey($activeEnrollmentClassId ?? 0);
            } else {
                $classesQuery->whereRaw('1 = 0');
            }
            $viewType = 'class';
        }

        $classes = $classesQuery->orderBy('grade_level')->orderBy('stream')->orderBy('section')->get();
        $teachers = $teachersQuery->get();
        $classId = $request->input('class_id');
        $teacherId = $request->input('teacher_id');

        if ($user->isTeacher()) {
            $teacherId = $user->id;
        }
        if ($user->isStudent()) {
            $classId = ClassEnrollment::where('student_id', $user->id)->where('status', 'active')->latest('id')->value('class_id');
        }
        if ($user->isParent() && $activeChild) {
            $classId = ClassEnrollment::where('student_id', $activeChild->id)->where('status', 'active')->latest('id')->value('class_id');
        }
        if (! $classId && $classes->isNotEmpty()) {
            $classId = $classes->first()->id;
        }
        if (! $teacherId && $viewType === 'teacher' && $teachers->isNotEmpty()) {
            $teacherId = $teachers->first()->id;
        }

        $selectedClass = $classes->firstWhere('id', (int) $classId);
        $selectedTeacher = $teachers->firstWhere('id', (int) $teacherId);
        if ($viewType === 'class' && $classId && ! $selectedClass) {
            abort(403, 'You are not authorized to view this class timetable.');
        }
        if ($viewType === 'teacher' && $teacherId && ! $selectedTeacher) {
            abort(403, 'You are not authorized to view this teacher timetable.');
        }

        $days = config('school_timetable.days', []);
        $periodRecords = SchoolPeriod::active()->get();
        $periods = $periodRecords->mapWithKeys(fn (SchoolPeriod $period) => [
            $period->period_number => [
                'name' => $period->name,
                'start' => substr((string) $period->start_time, 0, 5),
                'end' => substr((string) $period->end_time, 0, 5),
                'is_break' => $period->is_break,
            ],
        ])->all();

        $grid = [];
        foreach ($days as $day) {
            $grid[$day] = [];
        }

        if ($viewType === 'class' && $selectedClass) {
            $entries = SchoolPeriodTimetable::where('class_id', $selectedClass->id)
                ->with(['subject', 'teacher'])->get();
        } elseif ($viewType === 'teacher' && $selectedTeacher) {
            $entries = SchoolPeriodTimetable::where('teacher_id', $selectedTeacher->id)
                ->with(['subject', 'academicClass'])->get();
        } else {
            $entries = collect();
        }
        foreach ($entries as $entry) {
            if (isset($grid[$entry->day_of_week])) {
                $grid[$entry->day_of_week][$entry->period_number] = $entry;
            }
        }

        $allPeriods = SchoolPeriod::orderBy('sort_order')->get();
        $todayDate = now()->toDateString();
        $substitutes = SchoolPeriodSubstitute::active()
            ->with(['substituteTeacher', 'originalTeacher'])
            ->where('date', $todayDate)
            ->get()
            ->keyBy('timetable_id');

        return view('school.timetable.index', compact(
            'classes', 'teachers', 'viewType', 'classId', 'teacherId', 'selectedClass',
            'selectedTeacher', 'days', 'periods', 'grid', 'children', 'activeChild', 'allPeriods',
            'substitutes', 'todayDate'
        ));
    }

    /** Store a slot using period-master timings; submitted times are never trusted. */
    public function store(Request $request): RedirectResponse
    {
        abort_unless(Auth::user()?->isAdmin(), 403);
        $data = $request->validate([
            'class_id' => 'required|integer|exists:academic_classes,id',
            'subject_id' => 'required|integer|exists:academic_subjects,id',
            'teacher_id' => 'nullable|integer|exists:users,id',
            'teacher_override' => 'nullable|boolean',
            'day_of_week' => ['required', 'string', Rule::in(config('school_timetable.days', []))],
            'period_number' => 'required|integer|exists:school_periods,period_number',
            'room_number' => 'nullable|string|max:50',
        ]);

        $class = AcademicClass::findOrFail($data['class_id']);
        $subject = AcademicSubject::where('class_id', $class->id)->findOrFail($data['subject_id']);
        $period = SchoolPeriod::active()->where('period_number', $data['period_number'])->firstOrFail();
        if ($period->is_break) {
            throw ValidationException::withMessages(['period_number' => 'A break period cannot contain a subject lesson.']);
        }

        $data['room_number'] = trim((string) ($data['room_number'] ?? $class->room_number ?? '')) ?: null;
        $override = $request->boolean('teacher_override');
        $sessionDefaultTeacherId = $class->academic_year === AcademySetting::get('academic_session') ? $subject->subjectMaster?->default_teacher_id : null;
        $defaultTeacherId = $subject->teacher_id ?? $sessionDefaultTeacherId;
        $teacherId = ($data['teacher_id'] ?? null) ?: $defaultTeacherId;
        if (! $teacherId) {
            throw ValidationException::withMessages(['teacher_id' => 'Assign a subject teacher before scheduling this lesson, or select an explicit override teacher.']);
        }
        $teacher = User::findOrFail($teacherId);
        if (! $teacher->isTeacher()) {
            throw ValidationException::withMessages(['teacher_id' => 'Only active teacher accounts can be scheduled.']);
        }
        if (! $defaultTeacherId && ! $override) {
            throw ValidationException::withMessages(['teacher_id' => 'Assign a subject teacher first, or explicitly enable teacher override.']);
        }
        if ($defaultTeacherId && (int) $defaultTeacherId !== (int) $teacher->id && ! $override) {
            throw ValidationException::withMessages(['teacher_id' => 'This subject has a globally assigned teacher. Enable teacher override to schedule another teacher for this class.']);
        }
        if (! $subject->teacher_id && $defaultTeacherId) {
            $subject->update(['teacher_id' => $defaultTeacherId]);
            app(SchoolAcademicAuditService::class)->log(Auth::user(), 'inherited_subject_master_teacher', 'Inherited the subject master default teacher for '.$subject->name.' in '.$class->display_name.'.', ['teacher_id' => null], ['teacher_id' => $defaultTeacherId], $request, $class->id, $subject->id);
        }

        $slot = DB::transaction(function () use ($class, $subject, $period, $teacher, $data) {
            // Serialize timetable writes for this period so concurrent administrators cannot
            // both pass the conflict checks and create overlapping assignments.
            SchoolPeriod::whereKey($period->id)->lockForUpdate()->firstOrFail();
            $existing = SchoolPeriodTimetable::where('class_id', $class->id)->where('day_of_week', $data['day_of_week'])->where('period_number', $period->period_number)->lockForUpdate()->first();
            if ($existing && (int) $existing->subject_id !== (int) $subject->id) {
                throw ValidationException::withMessages(['period_number' => 'This class period is already occupied by another subject. Remove or edit that slot first.']);
            }
            if ($subject->weekly_periods && SchoolPeriodTimetable::where('class_id', $class->id)
                ->where('subject_id', $subject->id)->when($existing, fn ($query) => $query->where('id', '!=', $existing->id))->count() >= (int) $subject->weekly_periods) {
                throw ValidationException::withMessages(['subject_id' => 'This subject has already reached its configured weekly period load. Update the subject load before adding another slot.']);
            }
            $this->assertSlotAvailable($class->id, $teacher->id, $data['day_of_week'], $period->period_number, $data['room_number'], $existing?->id);

            return SchoolPeriodTimetable::updateOrCreate(
                ['class_id' => $class->id, 'day_of_week' => $data['day_of_week'], 'period_number' => $period->period_number],
                [
                    'subject_id' => $subject->id,
                    'teacher_id' => $teacher->id,
                    'start_time' => $period->start_time,
                    'end_time' => $period->end_time,
                    'room_number' => $data['room_number'],
                    'is_break' => false,
                    'title' => $subject->name,
                ]
            );
        }, 3);

        app(SchoolAcademicAuditService::class)->log(Auth::user(), 'saved_timetable_slot', 'Saved '.$subject->name.' for '.$class->display_name.' on '.$data['day_of_week'].' period '.$period->period_number.'.', null, ['slot_id' => $slot->id, 'subject_id' => $subject->id, 'teacher_id' => $teacher->id, 'period_number' => $period->period_number, 'start_time' => $period->start_time, 'end_time' => $period->end_time, 'room_number' => $slot->room_number], $request, $class->id, $subject->id);

        return back()->with('success', 'Timetable slot saved using the configured period master.');
    }

    private function assertSlotAvailable(int $classId, int $teacherId, string $day, int $period, ?string $room, ?int $ignoreId = null): void
    {
        $classClash = SchoolPeriodTimetable::where('class_id', $classId)->where('day_of_week', $day)->where('period_number', $period)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists();
        if ($classClash) {
            throw ValidationException::withMessages(['period_number' => 'This class already has a lesson in that day and period.']);
        }
        $teacherClash = SchoolPeriodTimetable::where('teacher_id', $teacherId)->where('day_of_week', $day)->where('period_number', $period)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists();
        if ($teacherClash) {
            throw ValidationException::withMessages(['teacher_id' => 'This teacher is already scheduled in another class during this period.']);
        }
        if ($room) {
            $roomClash = SchoolPeriodTimetable::whereRaw('UPPER(TRIM(room_number)) = ?', [mb_strtoupper(trim($room))])->where('day_of_week', $day)->where('period_number', $period)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists();
            if ($roomClash) {
                throw ValidationException::withMessages(['room_number' => 'This room is already occupied during the selected period.']);
            }
        }
    }

    public function destroy(int $id): RedirectResponse
    {
        abort_unless(Auth::user()?->isAdmin(), 403);
        $slot = SchoolPeriodTimetable::with(['academicClass', 'subject', 'teacher'])->findOrFail($id);
        app(SchoolAcademicAuditService::class)->log(Auth::user(), 'removed_timetable_slot', 'Removed '.$slot->subject?->name.' from '.$slot->academicClass?->display_name.' on '.$slot->day_of_week.' period '.$slot->period_number.'.', ['slot_id' => $slot->id, 'teacher_id' => $slot->teacher_id, 'room_number' => $slot->room_number], null, request(), $slot->class_id, $slot->subject_id);
        $slot->delete();

        return back()->with('success', 'Timetable slot removed.');
    }

    /** Generate only empty slots, respecting weekly subject loads and teacher/room conflicts. */
    public function autoGenerate(Request $request, int $classId): RedirectResponse
    {
        abort_unless(Auth::user()?->isAdmin(), 403);
        $class = AcademicClass::with(['subjects.teacher', 'subjects.subjectMaster.defaultTeacher'])->findOrFail($classId);
        $unassigned = $class->subjects->filter(function ($subject) use ($class) {
            $sessionDefaultTeacher = $class->academic_year === AcademySetting::get('academic_session') ? $subject->subjectMaster?->defaultTeacher : null;
            $teacher = $subject->teacher ?? $sessionDefaultTeacher;

            return ! $teacher || ! $teacher->isTeacher();
        });
        if ($unassigned->isNotEmpty()) {
            return back()->withErrors(['subjects' => 'Assign a valid teacher to every class subject before generating the timetable: '.$unassigned->pluck('name')->implode(', ')]);
        }
        $invalidLoads = $class->subjects->filter(fn ($subject) => ! $subject->weekly_periods || (int) $subject->weekly_periods < 1);
        if ($invalidLoads->isNotEmpty()) {
            return back()->withErrors(['subjects' => 'Configure a valid weekly period load for every class subject before generating the timetable: '.$invalidLoads->pluck('name')->implode(', ')]);
        }
        $subjects = $class->subjects;
        if ($subjects->isEmpty()) {
            return back()->withErrors(['subjects' => 'Assign teachers to class subjects before generating a timetable.']);
        }
        $overAllocated = $subjects->filter(function ($subject) use ($class) {
            $alreadyScheduled = SchoolPeriodTimetable::where('class_id', $class->id)->where('subject_id', $subject->id)->count();

            return $alreadyScheduled > (int) $subject->weekly_periods;
        });
        if ($overAllocated->isNotEmpty()) {
            return back()->withErrors(['subjects' => 'Existing timetable entries exceed the configured weekly load for: '.$overAllocated->pluck('name')->implode(', ').'. Increase the subject loads or remove excess slots before generating.']);
        }

        $periods = SchoolPeriod::active()->where('is_break', false)->orderBy('sort_order')->get();
        $days = config('school_timetable.days', []);
        if ($periods->isEmpty() || empty($days)) {
            return back()->withErrors(['periods' => 'Configure active teaching periods and school days first.']);
        }

        try {
            $generatedCount = DB::transaction(function () use ($class, $subjects, $periods, $days) {
                SchoolPeriod::whereIn('id', $periods->pluck('id'))->lockForUpdate()->get();
                $existing = SchoolPeriodTimetable::where('class_id', $class->id)->get();
                $occupied = [];
                foreach ($existing as $slot) {
                    $occupied[$slot->day_of_week.':'.$slot->period_number] = true;
                }

                $requests = [];
                $dayLoads = array_fill_keys($days, 0);
                $subjectDayLoads = [];
                foreach ($existing as $existingSlot) {
                    $dayLoads[$existingSlot->day_of_week] = ($dayLoads[$existingSlot->day_of_week] ?? 0) + 1;
                    $subjectDayLoads[$existingSlot->subject_id][$existingSlot->day_of_week] = ($subjectDayLoads[$existingSlot->subject_id][$existingSlot->day_of_week] ?? 0) + 1;
                }
                foreach ($subjects->sortByDesc('weekly_periods') as $subject) {
                    $sessionDefaultTeacher = $class->academic_year === AcademySetting::get('academic_session') ? $subject->subjectMaster?->defaultTeacher : null;
                    $teacher = $subject->teacher ?? $sessionDefaultTeacher;
                    if (! $subject->teacher_id && $teacher) {
                        $subject->teacher_id = $teacher->id;
                        $subject->save();
                        app(SchoolAcademicAuditService::class)->log(Auth::user(), 'inherited_subject_master_teacher', 'Inherited the subject master default teacher for '.$subject->name.' in '.$class->display_name.'.', ['teacher_id' => null], ['teacher_id' => $teacher->id], request(), $class->id, $subject->id);
                    }
                    $alreadyAssigned = SchoolPeriodTimetable::where('class_id', $class->id)->where('subject_id', $subject->id)->count();
                    $remaining = max(0, (int) $subject->weekly_periods - $alreadyAssigned);
                    for ($i = 0; $i < $remaining; $i++) {
                        $candidates = [];
                        foreach ($days as $dayIndex => $day) {
                            foreach ($periods as $periodIndex => $period) {
                                $key = $day.':'.$period->period_number;
                                if (isset($occupied[$key])) {
                                    continue;
                                }
                                $teacherBusy = SchoolPeriodTimetable::where('teacher_id', $teacher->id)->where('day_of_week', $day)->where('period_number', $period->period_number)->exists();
                                $roomBusy = $class->room_number && SchoolPeriodTimetable::whereRaw('UPPER(TRIM(room_number)) = ?', [mb_strtoupper(trim($class->room_number))])->where('day_of_week', $day)->where('period_number', $period->period_number)->exists();
                                if ($teacherBusy || $roomBusy) {
                                    continue;
                                }
                                $sameSubjectToday = $subjectDayLoads[$subject->id][$day] ?? 0;
                                $candidates[] = [
                                    'day' => $day,
                                    'period' => $period,
                                    'key' => $key,
                                    // Prefer distributing a subject over the week, then balance daily loads.
                                    'score' => [$sameSubjectToday > 0 ? 1 : 0, $dayLoads[$day] ?? 0, $periodIndex, $dayIndex],
                                ];
                            }
                        }
                        usort($candidates, fn ($a, $b) => $a['score'] <=> $b['score']);
                        $candidate = $candidates[0] ?? null;
                        if (! $candidate) {
                            throw ValidationException::withMessages(['timetable' => "Not enough conflict-free slots remain to satisfy {$subject->name}'s weekly load. No generated slots were saved."]);
                        }
                        $day = $candidate['day'];
                        $period = $candidate['period'];
                        $requests[] = [
                            'class_id' => $class->id,
                            'subject_id' => $subject->id,
                            'teacher_id' => $teacher->id,
                            'day_of_week' => $day,
                            'period_number' => $period->period_number,
                            'start_time' => $period->start_time,
                            'end_time' => $period->end_time,
                            'room_number' => $class->room_number,
                            'is_break' => false,
                            'title' => $subject->name,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                        $occupied[$candidate['key']] = true;
                        $dayLoads[$day] = ($dayLoads[$day] ?? 0) + 1;
                        $subjectDayLoads[$subject->id][$day] = ($subjectDayLoads[$subject->id][$day] ?? 0) + 1;
                    }
                }
                if ($requests) {
                    SchoolPeriodTimetable::insert($requests);
                }

                return count($requests);
            }, 3);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        app(SchoolAcademicAuditService::class)->log(Auth::user(), 'auto_generated_timetable', 'Generated '.$generatedCount.' timetable slots for '.$class->display_name.'.', null, ['generated_slots' => $generatedCount, 'class_id' => $class->id], $request, $class->id);

        return back()->with('success', 'Missing weekly subject periods were added to empty slots. Existing timetable entries were preserved.');
    }

    private function assertNoPeriodOverlap(string $startTime, string $endTime, ?int $ignoreId = null): void
    {
        $overlap = SchoolPeriod::where('is_active', true)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->where('start_time', '<', $endTime)
            ->where('end_time', '>', $startTime)
            ->exists();
        if ($overlap) {
            throw ValidationException::withMessages(['start_time' => 'This period overlaps another active period. Period master times must not overlap.']);
        }
    }

    /** Period master maintenance. Updating times also synchronizes existing timetable snapshots. */
    public function storePeriod(Request $request): RedirectResponse
    {
        abort_unless(Auth::user()?->isAdmin(), 403);
        $data = $request->validate([
            'period_number' => 'required|integer|min:0|max:'.config('school.periods.maximum_number').'|unique:school_periods,period_number',
            'name' => 'required|string|max:80',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'is_break' => 'nullable|boolean',
        ]);
        $this->assertNoPeriodOverlap($data['start_time'], $data['end_time']);
        $data['is_break'] = $request->boolean('is_break');
        $data['is_active'] = true;
        $data['sort_order'] = (int) $data['period_number'];
        $period = SchoolPeriod::create($data);
        app(SchoolAcademicAuditService::class)->log(Auth::user(), 'created_period_master', 'Created period master '.$period->name.'.', null, $period->only(['id', 'period_number', 'name', 'start_time', 'end_time', 'is_break']), $request);

        return back()->with('success', 'Period master created.');
    }

    public function updatePeriod(Request $request, int $id): RedirectResponse
    {
        abort_unless(Auth::user()?->isAdmin(), 403);
        $period = SchoolPeriod::findOrFail($id);
        $data = $request->validate([
            'name' => 'required|string|max:80',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'is_break' => 'required|boolean',
        ]);
        $this->assertNoPeriodOverlap($data['start_time'], $data['end_time'], $period->id);
        $oldPeriodValues = $period->only(['name', 'start_time', 'end_time', 'is_break', 'is_active']);
        if ((bool) $data['is_break'] !== $period->is_break && SchoolPeriodTimetable::where('period_number', $period->period_number)->exists()) {
            return back()->withErrors(['is_break' => 'A period with existing timetable entries cannot be converted between teaching and break type.']);
        }
        DB::transaction(function () use ($period, $data) {
            $period->update($data);
            SchoolPeriodTimetable::where('period_number', $period->period_number)->update([
                'start_time' => $period->start_time,
                'end_time' => $period->end_time,
            ]);
        });
        app(SchoolAcademicAuditService::class)->log(Auth::user(), 'updated_period_master', 'Updated period master '.$period->name.'.', $oldPeriodValues, $period->only(['name', 'start_time', 'end_time', 'is_break', 'is_active']), $request);

        return back()->with('success', 'Period master updated and existing timetable timings synchronized.');
    }

    public function togglePeriod(int $id): RedirectResponse
    {
        abort_unless(Auth::user()?->isAdmin(), 403);
        $period = SchoolPeriod::findOrFail($id);
        if (! $period->is_active) {
            try {
                $this->assertNoPeriodOverlap((string) $period->start_time, (string) $period->end_time, $period->id);
            } catch (ValidationException $exception) {
                return back()->withErrors($exception->errors());
            }
        }
        if ($period->is_active && SchoolPeriodTimetable::where('period_number', $period->period_number)->exists()) {
            return back()->withErrors(['period' => 'This period is referenced by timetable entries. Remove or migrate those entries before deactivating it.']);
        }
        $oldActive = $period->is_active;
        $period->is_active = ! $period->is_active;
        $period->save();
        app(SchoolAcademicAuditService::class)->log(Auth::user(), 'toggled_period_master', 'Changed activation state for period '.$period->name.'.', ['is_active' => $oldActive], ['is_active' => $period->is_active], request());

        return back()->with('success', $period->is_active ? 'Period reactivated.' : 'Period deactivated.');
    }

    public function storeSubstitute(Request $request): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user && $user->isAdmin(), 403, 'Administrator privileges required to assign proxy substitutes.');

        $validated = $request->validate([
            'timetable_id' => 'required|integer|exists:school_period_timetables,id',
            'substitute_teacher_id' => 'required|integer|exists:users,id',
            'date' => 'required|date',
            'reason' => 'nullable|string|max:255',
        ]);

        $subTeacher = User::findOrFail($validated['substitute_teacher_id']);
        if (! $subTeacher->isTeacher()) {
            return back()->withErrors(['substitute_teacher_id' => 'Only verified faculty accounts can be assigned as substitute teachers.']);
        }

        $slot = SchoolPeriodTimetable::with(['academicClass', 'subject', 'teacher'])->findOrFail($validated['timetable_id']);
        if ($slot->is_break) {
            return back()->withErrors(['timetable_id' => 'Proxy substitutes cannot be assigned to break periods.']);
        }

        if ((int) $slot->teacher_id === (int) $subTeacher->id) {
            return back()->withErrors(['substitute_teacher_id' => 'The selected faculty is already the primary teacher assigned to this period.']);
        }

        // Conflict Detection 1: Substitute teacher already has a timetable slot at this day/period
        $timetableConflict = SchoolPeriodTimetable::where('day_of_week', $slot->day_of_week)
            ->where('period_number', $slot->period_number)
            ->where('is_break', false)
            ->where(function ($q) use ($subTeacher) {
                $q->where('teacher_id', $subTeacher->id)
                    ->orWhereHas('subject', fn ($sq) => $sq->where('teacher_id', $subTeacher->id));
            })->exists();

        if ($timetableConflict) {
            return back()->withErrors(['substitute_teacher_id' => "Timetable Conflict: {$subTeacher->name} already has an instructional period allocated during Period {$slot->period_number} on {$slot->day_of_week}."]);
        }

        $targetDate = Carbon::parse($validated['date'])->toDateString();

        // Conflict Detection 2: Substitute teacher already assigned as proxy elsewhere on this date & period
        $proxyConflict = SchoolPeriodSubstitute::active()
            ->whereDate('date', $targetDate)
            ->where('substitute_teacher_id', $subTeacher->id)
            ->where('timetable_id', '!=', $slot->id)
            ->whereHas('timetable', fn ($q) => $q->where('period_number', $slot->period_number))
            ->exists();

        if ($proxyConflict) {
            return back()->withErrors(['substitute_teacher_id' => "Proxy Conflict: {$subTeacher->name} is already assigned as a proxy substitute in another class for Period {$slot->period_number} on {$targetDate}."]);
        }

        $substitute = SchoolPeriodSubstitute::updateOrCreate(
            [
                'timetable_id' => $slot->id,
                'date' => $targetDate,
            ],
            [
                'original_teacher_id' => $slot->teacher_id,
                'substitute_teacher_id' => $subTeacher->id,
                'reason' => $validated['reason'] ?? null,
                'status' => 'active',
                'assigned_by' => $user->id,
            ]
        );

        NotificationService::notifyUser(
            $subTeacher->id,
            'timetable',
            "Proxy Assignment: Period {$slot->period_number}",
            "You have been assigned as proxy substitute for Period {$slot->period_number} in {$slot->academicClass->display_name} for {$slot->subject?->name} on {$validated['date']}.",
            route('school.timetable.index'),
            '📋'
        );

        app(SchoolAcademicAuditService::class)->log(
            $user,
            'assigned_period_substitute',
            "Assigned {$subTeacher->name} as substitute for Period {$slot->period_number} ({$slot->academicClass->display_name}) on {$validated['date']}.",
            null,
            $substitute->only(['timetable_id', 'substitute_teacher_id', 'date', 'reason']),
            $request
        );

        return back()->with('success', "Proxy teacher {$subTeacher->name} assigned for Period {$slot->period_number} on {$validated['date']}.");
    }

    public function destroySubstitute(Request $request, int $id): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user && $user->isAdmin(), 403, 'Administrator privileges required.');

        $substitute = SchoolPeriodSubstitute::findOrFail($id);
        $substitute->update(['status' => 'cancelled']);

        app(SchoolAcademicAuditService::class)->log(
            $user,
            'cancelled_period_substitute',
            "Cancelled proxy substitute assignment #{$id} for date {$substitute->date}.",
            ['status' => 'active'],
            ['status' => 'cancelled'],
            $request
        );

        return back()->with('success', 'Proxy substitute assignment cancelled.');
    }

    /**
     * AJAX endpoint: Check for timetable conflicts prior to form submission.
     */
    public function checkConflict(Request $request, \App\Services\TimetableConflictService $conflictService): \Illuminate\Http\JsonResponse
    {
        $classId = (int) $request->input('class_id');
        $teacherId = $request->filled('teacher_id') ? (int) $request->input('teacher_id') : null;
        $dayOfWeek = (string) $request->input('day_of_week');
        $periodNumber = (int) $request->input('period_number');
        $roomNumber = $request->input('room_number');
        $ignoreId = $request->filled('ignore_id') ? (int) $request->input('ignore_id') : null;

        $result = $conflictService->checkSlotConflict(
            $classId,
            $teacherId,
            $dayOfWeek,
            $periodNumber,
            $roomNumber,
            $ignoreId
        );

        return response()->json($result);
    }

    /**
     * AJAX endpoint: Get list of available substitute teachers for a date and period.
     */
    public function availableSubstitutes(Request $request, \App\Services\TimetableConflictService $conflictService): \Illuminate\Http\JsonResponse
    {
        $date = (string) $request->input('date', now()->toDateString());
        $dayOfWeek = (string) $request->input('day_of_week', strtolower(now()->format('l')));
        $periodNumber = (int) $request->input('period_number');

        $availableTeachers = $conflictService->findAvailableSubstituteTeachers($date, $dayOfWeek, $periodNumber);

        return response()->json([
            'teachers' => $availableTeachers->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'email' => $t->email,
            ]),
        ]);
    }
}
