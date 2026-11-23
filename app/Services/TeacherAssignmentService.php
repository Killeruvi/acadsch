<?php

namespace App\Services;

use App\Models\AcademicClass;
use App\Models\AcademicSubject;
use App\Models\AcademySetting;
use App\Models\SchoolPeriod;
use App\Models\SchoolPeriodTimetable;
use App\Models\SubjectMaster;
use App\Models\TeacherAssignmentAudit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TeacherAssignmentService
{
    /**
     * Validate against duplicate or conflicting timetable slots.
     *
     * @throws ValidationException
     */
    public function validateTimetableConflict(
        int $classId,
        int $teacherId,
        string $dayOfWeek,
        int $periodNumber,
        ?string $roomNumber = null,
        ?int $ignoreSlotId = null
    ): void {
        SchoolPeriod::where('period_number', $periodNumber)->lockForUpdate()->first();
        $teacher = User::findOrFail($teacherId);
        $class = AcademicClass::findOrFail($classId);

        // 1. Conflict Check: Is teacher already teaching another class during this exact day and period?
        $teacherConflict = SchoolPeriodTimetable::with(['academicClass', 'subject'])
            ->where('teacher_id', $teacherId)
            ->where('day_of_week', $dayOfWeek)
            ->where('period_number', $periodNumber)
            ->lockForUpdate()
            ->when($ignoreSlotId, fn ($q) => $q->where('id', '!=', $ignoreSlotId))
            ->where('class_id', '!=', $classId)
            ->first();

        if ($teacherConflict) {
            $otherClass = $teacherConflict->academicClass ? $teacherConflict->academicClass->display_name : 'another class';
            $otherSub = $teacherConflict->subject ? $teacherConflict->subject->name : 'assigned lesson';
            throw ValidationException::withMessages([
                'teacher_id' => "Schedule Conflict: {$teacher->name} is already assigned to teach {$otherClass} ({$otherSub}) on {$dayOfWeek} during Period {$periodNumber} ({$teacherConflict->start_time} - {$teacherConflict->end_time}).",
            ]);
        }

        // 2. Conflict Check: Does this class already have another subject/teacher in this period?
        $classConflict = SchoolPeriodTimetable::with(['subject', 'teacher'])
            ->where('class_id', $classId)
            ->where('day_of_week', $dayOfWeek)
            ->where('period_number', $periodNumber)
            ->lockForUpdate()
            ->when($ignoreSlotId, fn ($q) => $q->where('id', '!=', $ignoreSlotId))
            ->first();

        if ($classConflict) {
            $subName = $classConflict->subject ? $classConflict->subject->name : 'another subject';
            $tName = $classConflict->teacher ? $classConflict->teacher->name : 'another faculty';
            throw ValidationException::withMessages([
                'period_number' => "Period Collision: {$class->display_name} already has {$subName} (Teacher: {$tName}) scheduled on {$dayOfWeek} during Period {$periodNumber}.",
            ]);
        }

        // 3. Conflict Check: Room Occupancy (if specified)
        if (! empty($roomNumber)) {
            $roomConflict = SchoolPeriodTimetable::with(['academicClass', 'teacher'])
                ->whereRaw('UPPER(TRIM(room_number)) = ?', [mb_strtoupper(trim($roomNumber))])
                ->where('day_of_week', $dayOfWeek)
                ->where('period_number', $periodNumber)
                ->lockForUpdate()
                ->where('class_id', '!=', $classId)
                ->when($ignoreSlotId, fn ($q) => $q->where('id', '!=', $ignoreSlotId))
                ->first();

            if ($roomConflict) {
                $occupyingClass = $roomConflict->academicClass ? $roomConflict->academicClass->display_name : 'another class';
                throw ValidationException::withMessages([
                    'room_number' => "Room Conflict: Room '{$roomNumber}' is already occupied by {$occupyingClass} on {$dayOfWeek} during Period {$periodNumber}.",
                ]);
            }
        }
    }

    /**
     * Assign or map a teacher to a subject for a class/section.
     * Optionally creates/links to a timetable period slot.
     */
    public function assignSubject(User $admin, array $data, ?Request $request = null): AcademicSubject
    {
        return DB::transaction(function () use ($admin, $data, $request) {
            $class = AcademicClass::findOrFail($data['class_id']);
            $master = ! empty($data['subject_master_id']) ? SubjectMaster::findOrFail($data['subject_master_id']) : null;
            if (! $master && ! empty($data['subject_id'])) {
                $existingMapping = AcademicSubject::where('class_id', $class->id)->findOrFail($data['subject_id']);
                $master = $existingMapping->subjectMaster;
            }
            $sessionDefaultTeacherId = $class->academic_year === AcademySetting::get('academic_session') ? $master?->default_teacher_id : null;
            $teacherId = $data['teacher_id'] ?? $sessionDefaultTeacherId;
            $teacher = $teacherId ? User::findOrFail($teacherId) : null;

            if ($master && $sessionDefaultTeacherId && $teacher && (int) $sessionDefaultTeacherId !== (int) $teacher->id && empty($data['teacher_override']) && empty($data['apply_globally'])) {
                throw ValidationException::withMessages(['teacher_id' => 'This subject has a global default teacher. Enable the explicit override to use another teacher in this class.']);
            }
            if (! empty($data['apply_globally']) && $class->academic_year !== AcademySetting::get('academic_session')) {
                throw ValidationException::withMessages(['apply_globally' => 'Global subject-teacher changes can only be made from a class in the active academic session.']);
            }
            if (! empty($data['apply_globally']) && ! $teacher) {
                throw ValidationException::withMessages(['teacher_id' => 'Choose a teacher before applying a global subject-teacher assignment.']);
            }

            if ($teacher && ! $teacher->isTeacher()) {
                throw ValidationException::withMessages([
                    'teacher_id' => "Invalid Role: '{$teacher->name}' is not a teacher account.",
                ]);
            }

            if (! empty($data['apply_globally']) && $master && $teacher) {
                $oldDefaultTeacher = $master->default_teacher_id;
                $master->update(['default_teacher_id' => $teacher->id]);
                $this->logAudit(
                    admin: $admin,
                    action: 'updated_subject_master_default_teacher',
                    description: "Set {$teacher->name} as the global default teacher for {$master->name}.",
                    teacher: $teacher,
                    class: $class,
                    subject: null,
                    oldValues: ['default_teacher_id' => $oldDefaultTeacher],
                    newValues: ['default_teacher_id' => $teacher->id],
                    request: $request
                );
            }

            // Check if existing subject ID is provided
            if (! empty($data['subject_id'])) {
                $subject = AcademicSubject::where('class_id', $class->id)->findOrFail($data['subject_id']);

                if ($teacher && (int) ($subject->teacher_id ?? 0) === (int) $teacher->id) {
                    throw ValidationException::withMessages([
                        'teacher_id' => "{$teacher->name} is already assigned to teach {$subject->name} in {$class->display_name}.",
                    ]);
                }

                $oldValues = [
                    'teacher_id' => $subject->teacher_id,
                    'teacher_name' => $subject->teacher ? $subject->teacher->name : null,
                    'weekly_periods' => $subject->weekly_periods,
                    'subject_master_id' => $subject->subject_master_id,
                ];

                if ((int) ($subject->teacher_id ?? 0) !== (int) ($teacher?->id ?? 0)) {
                    $existingSlots = SchoolPeriodTimetable::where('subject_id', $subject->id)->get();
                    foreach ($existingSlots as $existingSlot) {
                        if ($teacher) {
                            $this->validateTimetableConflict($class->id, $teacher->id, $existingSlot->day_of_week, (int) $existingSlot->period_number, $existingSlot->room_number, $existingSlot->id);
                        }
                    }
                    SchoolPeriodTimetable::where('subject_id', $subject->id)->update(['teacher_id' => $teacher?->id]);
                }
                if (! empty($data['subject_master_id'])) {
                    $subject->subject_master_id = $data['subject_master_id'];
                }
                $subject->teacher_id = $teacher ? $teacher->id : null;
                if (isset($data['weekly_periods'])) {
                    $subject->weekly_periods = (int) $data['weekly_periods'];
                }
                $subject->save();

                $action = 'assigned_subject';
                $description = $teacher
                    ? "Assigned {$teacher->name} to teach {$subject->name} ({$subject->code}) for {$class->display_name}."
                    : "Unassigned teacher from {$subject->name} for {$class->display_name}.";
            } else {
                // Creating a new subject mapping for this class
                $name = trim($data['name']);
                $code = strtoupper(trim($data['code']));

                // Duplicate Check: Subject code or name already exists in this class
                $existingSubject = AcademicSubject::where('class_id', $class->id)
                    ->where(function ($q) use ($name, $code) {
                        $q->where('code', $code)->orWhere('name', $name);
                    })
                    ->first();

                if ($existingSubject) {
                    // Idempotently update an existing class-subject mapping rather than creating a duplicate.
                    $oldValues = [
                        'teacher_id' => $existingSubject->teacher_id,
                        'teacher_name' => $existingSubject->teacher ? $existingSubject->teacher->name : null,
                        'subject_master_id' => $existingSubject->subject_master_id,
                    ];
                    if (! empty($data['subject_master_id'])) {
                        $existingSubject->subject_master_id = $data['subject_master_id'];
                    }
                    if ((int) ($existingSubject->teacher_id ?? 0) !== (int) ($teacher?->id ?? 0)) {
                        $existingSlots = SchoolPeriodTimetable::where('subject_id', $existingSubject->id)->get();
                        foreach ($existingSlots as $existingSlot) {
                            if ($teacher) {
                                $this->validateTimetableConflict($class->id, $teacher->id, $existingSlot->day_of_week, (int) $existingSlot->period_number, $existingSlot->room_number, $existingSlot->id);
                            }
                        }
                        SchoolPeriodTimetable::where('subject_id', $existingSubject->id)->update(['teacher_id' => $teacher?->id]);
                    }
                    $existingSubject->teacher_id = $teacher ? $teacher->id : null;
                    if (! empty($data['weekly_periods'])) {
                        $existingSubject->weekly_periods = (int) $data['weekly_periods'];
                    }
                    $existingSubject->save();
                    $subject = $existingSubject;
                    $action = 'assigned_subject';
                    $description = $teacher
                        ? "Assigned {$teacher->name} to teach {$subject->name} for {$class->display_name}."
                        : "Updated {$subject->name} in {$class->display_name} with no assigned teacher.";
                } else {
                    $oldValues = null;
                    $subject = AcademicSubject::create([
                        'class_id' => $class->id,
                        'subject_master_id' => $data['subject_master_id'] ?? null,
                        'name' => $name,
                        'code' => $code,
                        'teacher_id' => $teacher ? $teacher->id : null,
                        'weekly_periods' => (int) $data['weekly_periods'],
                    ]);
                    $action = 'assigned_subject';
                    $description = "Created subject '{$subject->name}' ({$subject->code}) and mapped to teacher ".($teacher ? $teacher->name : 'Unassigned')." for {$class->display_name}.";
                }
            }

            // Optional Timetable Period Link
            $timetableSlotId = null;
            if (! empty($data['link_timetable']) && ! $teacher) {
                throw ValidationException::withMessages(['teacher_id' => 'Assign a teacher before creating a timetable slot.']);
            }
            if (! empty($data['link_timetable']) && ! empty($data['day_of_week']) && isset($data['period_number']) && $teacher) {
                $day = $data['day_of_week'];
                $period = (int) $data['period_number'];
                $room = trim((string) ($data['room_number'] ?? $class->room_number ?? '')) ?: null;
                $periodMaster = SchoolPeriod::active()->where('period_number', $period)->where('is_break', false)->first();
                if (! $periodMaster) {
                    throw ValidationException::withMessages(['period_number' => 'Select an active teaching period from the period master.']);
                }
                $periodMaster = SchoolPeriod::whereKey($periodMaster->id)->lockForUpdate()->first();
                $existingSlot = SchoolPeriodTimetable::where('class_id', $class->id)->where('day_of_week', $day)->where('period_number', $period)->lockForUpdate()->first();
                if ($existingSlot && (int) $existingSlot->subject_id !== (int) $subject->id) {
                    throw ValidationException::withMessages(['period_number' => 'This class period is already occupied by another subject.']);
                }
                $this->validateTimetableConflict($class->id, $teacher->id, $day, $period, $room, $existingSlot?->id);
                $startTime = $periodMaster->start_time;
                $endTime = $periodMaster->end_time;

                $slot = SchoolPeriodTimetable::updateOrCreate(
                    [
                        'class_id' => $class->id,
                        'day_of_week' => $day,
                        'period_number' => $period,
                    ],
                    [
                        'subject_id' => $subject->id,
                        'teacher_id' => $teacher->id,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'room_number' => $room,
                        'is_break' => false,
                        'title' => "{$subject->name} - {$teacher->name}",
                    ]
                );

                $timetableSlotId = $slot->id;
                $description .= " Linked to timetable on {$day} Period {$period} ({$startTime} - {$endTime}).";
            }

            // Auto-assign globally across all classes where this subject exists (Rule 16)
            if (! empty($data['apply_globally']) && $teacher) {
                $baseCode = preg_replace('/-[0-9]+[A-Za-z]*$/', '', strtoupper($subject->code));
                $otherSubjectsQuery = AcademicSubject::where('id', '!=', $subject->id)
                    ->whereHas('academicClass', fn ($query) => $query->where('academic_year', $class->academic_year));
                $otherSubjectsQuery->where(function ($query) use ($master, $subject, $baseCode) {
                    if ($master) {
                        $query->where('subject_master_id', $master->id)
                            ->orWhere(function ($legacy) use ($subject, $baseCode) {
                                $legacy->whereNull('subject_master_id')->where(function ($identity) use ($subject, $baseCode) {
                                    $identity->whereRaw('UPPER(name) = ?', [strtoupper($subject->name)])
                                        ->orWhereRaw('UPPER(code) = ?', [$baseCode])
                                        ->orWhereRaw('UPPER(code) LIKE ?', [$baseCode.'-%']);
                                });
                            });
                    } else {
                        $query->whereRaw('UPPER(name) = ?', [strtoupper($subject->name)])
                            ->orWhereRaw('UPPER(code) = ?', [$baseCode])
                            ->orWhereRaw('UPPER(code) LIKE ?', [$baseCode.'-%']);
                    }
                });
                $otherSubjects = $otherSubjectsQuery->get();

                foreach ($otherSubjects as $otherSub) {
                    $otherClass = $otherSub->academicClass;
                    $otherSlots = SchoolPeriodTimetable::where('subject_id', $otherSub->id)->get();
                    foreach ($otherSlots as $otherSlot) {
                        $this->validateTimetableConflict(
                            classId: $otherClass->id,
                            teacherId: $teacher->id,
                            dayOfWeek: $otherSlot->day_of_week,
                            periodNumber: (int) $otherSlot->period_number,
                            roomNumber: $otherSlot->room_number,
                            ignoreSlotId: $otherSlot->id
                        );
                    }
                    $oldTeacher = $otherSub->teacher;
                    if ($master && ! $otherSub->subject_master_id) {
                        $otherSub->subject_master_id = $master->id;
                    }
                    $otherSub->teacher_id = $teacher->id;
                    $otherSub->save();
                    SchoolPeriodTimetable::where('subject_id', $otherSub->id)->update(['teacher_id' => $teacher->id]);
                    $this->logAudit(
                        admin: $admin,
                        action: 'globally_assigned_subject',
                        description: "Globally assigned {$teacher->name} to {$otherSub->name} in {$otherClass->display_name}.",
                        teacher: $teacher,
                        class: $otherClass,
                        subject: $otherSub,
                        oldValues: ['teacher_id' => $oldTeacher?->id, 'teacher_name' => $oldTeacher?->name],
                        newValues: ['teacher_id' => $teacher->id, 'teacher_name' => $teacher->name],
                        request: $request
                    );
                }

                if ($otherSubjects->isNotEmpty()) {
                    $description .= " Also auto-assigned globally to {$otherSubjects->count()} matching class subject(s), with timetable conflicts checked.";
                }
            }

            if ($master) {
                if (! $master->default_teacher_id && $teacher && empty($data['teacher_override']) && $class->academic_year === AcademySetting::get('academic_session')) {
                    $master->update(['default_teacher_id' => $teacher->id]);
                    $this->logAudit(
                        admin: $admin,
                        action: 'set_initial_subject_master_default_teacher',
                        description: "Set {$teacher->name} as the initial global default teacher for {$master->name}.",
                        teacher: $teacher,
                        class: $class,
                        subject: $subject,
                        oldValues: ['default_teacher_id' => null],
                        newValues: ['default_teacher_id' => $teacher->id],
                        request: $request
                    );
                }
                app(SubjectCurriculumInheritanceService::class)->inheritMasterAcrossClasses($master, $class->academic_year, $admin, $request);
            }

            // Record Audit Log
            $this->logAudit(
                admin: $admin,
                action: $action,
                description: $description,
                teacher: $teacher,
                class: $class,
                subject: $subject,
                timetableSlotId: $timetableSlotId,
                oldValues: $oldValues,
                newValues: [
                    'teacher_id' => $subject->teacher_id,
                    'teacher_name' => $teacher ? $teacher->name : null,
                    'weekly_periods' => $subject->weekly_periods,
                    'timetable_slot_id' => $timetableSlotId,
                ],
                request: $request
            );

            return $subject;
        });
    }

    /**
     * Update an existing subject assignment.
     */
    public function updateSubjectAssignment(
        User $admin,
        AcademicSubject $subject,
        array $data,
        ?Request $request = null
    ): AcademicSubject {
        return DB::transaction(function () use ($admin, $subject, $data, $request) {
            $class = $subject->academicClass;
            $master = $subject->subjectMaster;
            $sessionDefaultTeacherId = $class->academic_year === AcademySetting::get('academic_session') ? $master?->default_teacher_id : null;
            $newTeacherId = $data['teacher_id'] ?? $sessionDefaultTeacherId;
            $newTeacher = $newTeacherId ? User::findOrFail($newTeacherId) : null;
            if ($master && $sessionDefaultTeacherId && $newTeacher && (int) $sessionDefaultTeacherId !== (int) $newTeacher->id && empty($data['teacher_override']) && empty($data['apply_globally'])) {
                throw ValidationException::withMessages(['teacher_id' => 'This subject has a global default teacher. Enable the explicit override to use another teacher in this class.']);
            }
            if (! empty($data['apply_globally']) && $class->academic_year !== AcademySetting::get('academic_session')) {
                throw ValidationException::withMessages(['apply_globally' => 'Global subject-teacher changes can only be made from a class in the active academic session.']);
            }
            if (! empty($data['apply_globally']) && ! $newTeacher) {
                throw ValidationException::withMessages(['teacher_id' => 'Choose a teacher before applying a global subject-teacher assignment.']);
            }

            if ($master && ! $master->default_teacher_id && $newTeacher && empty($data['teacher_override']) && $class->academic_year === AcademySetting::get('academic_session')) {
                $master->update(['default_teacher_id' => $newTeacher->id]);
                $this->logAudit(
                    admin: $admin,
                    action: 'set_initial_subject_master_default_teacher',
                    description: "Set {$newTeacher->name} as the initial global default teacher for {$master->name}.",
                    teacher: $newTeacher,
                    class: $class,
                    subject: $subject,
                    oldValues: ['default_teacher_id' => null],
                    newValues: ['default_teacher_id' => $newTeacher->id],
                    request: $request
                );
            }

            $oldValues = [
                'teacher_id' => $subject->teacher_id,
                'teacher_name' => $subject->teacher ? $subject->teacher->name : 'Unassigned',
                'name' => $subject->name,
                'code' => $subject->code,
                'weekly_periods' => $subject->weekly_periods,
            ];

            // If teacher is being changed, check if new teacher has timetable conflicts on existing subject slots
            if ($newTeacher && ! $newTeacher->isTeacher()) {
                throw ValidationException::withMessages(['teacher_id' => 'Only teacher accounts can be assigned to subjects.']);
            }
            if (array_key_exists('teacher_id', $data) && (int) ($newTeacher?->id ?? 0) !== (int) ($subject->teacher_id ?? 0)) {
                $existingSlots = SchoolPeriodTimetable::where('subject_id', $subject->id)->get();
                foreach ($existingSlots as $slot) {
                    if ($newTeacher) {
                        $this->validateTimetableConflict(
                            classId: $subject->class_id,
                            teacherId: $newTeacher->id,
                            dayOfWeek: $slot->day_of_week,
                            periodNumber: $slot->period_number,
                            roomNumber: $slot->room_number,
                            ignoreSlotId: $slot->id
                        );
                    }
                }
                SchoolPeriodTimetable::where('subject_id', $subject->id)->update(['teacher_id' => $newTeacher?->id]);
            }

            if (isset($data['name'])) {
                $subject->name = trim($data['name']);
            }
            if (isset($data['code'])) {
                $subject->code = strtoupper(trim($data['code']));
            }
            if (array_key_exists('teacher_id', $data)) {
                $subject->teacher_id = $newTeacher ? $newTeacher->id : null;
            }
            if (isset($data['weekly_periods'])) {
                $subject->weekly_periods = (int) $data['weekly_periods'];
            }
            $subject->save();

            if (! empty($data['apply_globally']) && $newTeacher) {
                if ($master) {
                    $oldDefaultTeacher = $master->default_teacher_id;
                    $master->update(['default_teacher_id' => $newTeacher->id]);
                    $this->logAudit(
                        admin: $admin,
                        action: 'updated_subject_master_default_teacher',
                        description: "Set {$newTeacher->name} as the global default teacher for {$master->name}.",
                        teacher: $newTeacher,
                        class: $subject->academicClass,
                        subject: $subject,
                        oldValues: ['default_teacher_id' => $oldDefaultTeacher],
                        newValues: ['default_teacher_id' => $newTeacher->id],
                        request: $request
                    );
                }
                $baseCode = preg_replace('/-[0-9]+[A-Za-z]*$/', '', strtoupper($subject->code));
                $matchingQuery = AcademicSubject::where('id', '!=', $subject->id)
                    ->whereHas('academicClass', fn ($query) => $query->where('academic_year', $class->academic_year));
                $matchingQuery->where(function ($query) use ($master, $subject, $baseCode) {
                    if ($master) {
                        $query->where('subject_master_id', $master->id)->orWhere(function ($legacy) use ($subject, $baseCode) {
                            $legacy->whereNull('subject_master_id')->where(function ($identity) use ($subject, $baseCode) {
                                $identity->whereRaw('UPPER(name) = ?', [strtoupper($subject->name)])
                                    ->orWhereRaw('UPPER(code) = ?', [$baseCode])
                                    ->orWhereRaw('UPPER(code) LIKE ?', [$baseCode.'-%']);
                            });
                        });
                    } else {
                        $query->whereRaw('UPPER(name) = ?', [strtoupper($subject->name)])
                            ->orWhereRaw('UPPER(code) = ?', [$baseCode])
                            ->orWhereRaw('UPPER(code) LIKE ?', [$baseCode.'-%']);
                    }
                });
                $matchingSubjects = $matchingQuery->get();
                foreach ($matchingSubjects as $matchingSubject) {
                    $matchingClass = $matchingSubject->academicClass;
                    $slots = SchoolPeriodTimetable::where('subject_id', $matchingSubject->id)->get();
                    foreach ($slots as $slot) {
                        $this->validateTimetableConflict($matchingClass->id, $newTeacher->id, $slot->day_of_week, (int) $slot->period_number, $slot->room_number, $slot->id);
                    }
                    $oldTeacher = $matchingSubject->teacher;
                    $matchingSubject->update([
                        'teacher_id' => $newTeacher->id,
                        'subject_master_id' => $matchingSubject->subject_master_id ?: $master?->id,
                    ]);
                    SchoolPeriodTimetable::where('subject_id', $matchingSubject->id)->update(['teacher_id' => $newTeacher->id]);
                    $this->logAudit(
                        admin: $admin,
                        action: 'globally_updated_subject_teacher',
                        description: "Globally updated teacher for {$matchingSubject->name} in {$matchingClass->display_name}.",
                        teacher: $newTeacher,
                        class: $matchingClass,
                        subject: $matchingSubject,
                        oldValues: ['teacher_id' => $oldTeacher?->id, 'teacher_name' => $oldTeacher?->name],
                        newValues: ['teacher_id' => $newTeacher->id, 'teacher_name' => $newTeacher->name],
                        request: $request
                    );
                }
            }

            if ($master) {
                app(SubjectCurriculumInheritanceService::class)->inheritMasterAcrossClasses($master, $class->academic_year, $admin, $request);
            }

            $newValues = [
                'teacher_id' => $subject->teacher_id,
                'teacher_name' => $newTeacher ? $newTeacher->name : 'Unassigned',
                'name' => $subject->name,
                'code' => $subject->code,
                'weekly_periods' => $subject->weekly_periods,
            ];

            $desc = "Updated subject assignment for {$subject->name} ({$subject->code}) in {$class->display_name}. Teacher: ".($newTeacher ? $newTeacher->name : 'Unassigned');

            $this->logAudit(
                admin: $admin,
                action: 'updated_subject',
                description: $desc,
                teacher: $newTeacher,
                class: $class,
                subject: $subject,
                timetableSlotId: null,
                oldValues: $oldValues,
                newValues: $newValues,
                request: $request
            );

            return $subject;
        });
    }

    /**
     * Remove teacher assignment or delete subject mapping.
     */
    public function removeSubjectAssignment(
        User $admin,
        AcademicSubject $subject,
        bool $deleteSubject = false,
        ?Request $request = null
    ): void {
        DB::transaction(function () use ($admin, $subject, $deleteSubject, $request) {
            $class = $subject->academicClass;
            $oldTeacher = $subject->teacher;

            $oldValues = [
                'teacher_id' => $subject->teacher_id,
                'teacher_name' => $oldTeacher ? $oldTeacher->name : 'Unassigned',
                'subject_name' => $subject->name,
                'subject_code' => $subject->code,
            ];

            if ($deleteSubject) {
                $hasAcademicHistory = $subject->examMarks()->exists()
                    || $subject->examSchedules()->exists()
                    || $subject->lessonPlans()->exists()
                    || $subject->topics()->exists()
                    || $subject->videos()->exists()
                    || $subject->dpps()->exists()
                    || $subject->homeworkTasks()->exists();
                if ($hasAcademicHistory) {
                    throw ValidationException::withMessages(['subject' => 'This subject has academic history or learning content and cannot be deleted. Unassign the teacher instead.']);
                }
                // Remove current timetable configuration, preserving master curriculum and any academic history.
                // Remove subject and its associated timetable slots
                SchoolPeriodTimetable::where('subject_id', $subject->id)->delete();
                $subjectName = $subject->name;
                $subject->delete();
                $desc = "Removed subject '{$subjectName}' and all associated assignments from {$class->display_name}.";
                $action = 'removed_subject';
            } else {
                // Just unassign the teacher
                $subject->teacher_id = null;
                $subject->save();
                // Clear teacher on timetable slots
                SchoolPeriodTimetable::where('subject_id', $subject->id)->update(['teacher_id' => null]);
                $desc = 'Unassigned '.($oldTeacher ? $oldTeacher->name : 'teacher')." from subject '{$subject->name}' in {$class->display_name}.";
                $action = 'unassigned_teacher';
            }

            $this->logAudit(
                admin: $admin,
                action: $action,
                description: $desc,
                teacher: $oldTeacher,
                class: $class,
                subject: $deleteSubject ? null : $subject,
                timetableSlotId: null,
                oldValues: $oldValues,
                newValues: null,
                request: $request
            );
        });
    }

    /**
     * Assign Class Teacher (Homeroom).
     */
    public function assignClassTeacher(
        User $admin,
        AcademicClass $class,
        int $teacherId,
        ?Request $request = null
    ): AcademicClass {
        return DB::transaction(function () use ($admin, $class, $teacherId, $request) {
            $teacher = User::findOrFail($teacherId);

            if (! $teacher->isTeacher()) {
                throw ValidationException::withMessages([
                    'teacher_id' => "Invalid Role: '{$teacher->name}' is not a teacher account.",
                ]);
            }

            if ($class->class_teacher_id === $teacher->id) {
                throw ValidationException::withMessages([
                    'teacher_id' => "Duplicate Assignment: {$teacher->name} is already the Class Teacher for {$class->display_name}.",
                ]);
            }

            $oldTeacher = $class->classTeacher;
            $oldValues = [
                'class_teacher_id' => $class->class_teacher_id,
                'class_teacher_name' => $oldTeacher ? $oldTeacher->name : 'Unassigned',
            ];

            $class->class_teacher_id = $teacher->id;
            $class->save();

            $desc = "Assigned {$teacher->name} as Class Teacher for {$class->display_name}".($oldTeacher ? " (replacing {$oldTeacher->name})." : '.');

            $this->logAudit(
                admin: $admin,
                action: 'assigned_class_teacher',
                description: $desc,
                teacher: $teacher,
                class: $class,
                subject: null,
                timetableSlotId: null,
                oldValues: $oldValues,
                newValues: [
                    'class_teacher_id' => $teacher->id,
                    'class_teacher_name' => $teacher->name,
                ],
                request: $request
            );

            return $class;
        });
    }

    /**
     * Remove Class Teacher assignment.
     */
    public function removeClassTeacher(User $admin, AcademicClass $class, ?Request $request = null): void
    {
        DB::transaction(function () use ($admin, $class, $request) {
            $oldTeacher = $class->classTeacher;
            if (! $oldTeacher) {
                return;
            }

            $oldValues = [
                'class_teacher_id' => $class->class_teacher_id,
                'class_teacher_name' => $oldTeacher->name,
            ];

            $class->class_teacher_id = null;
            $class->save();

            $desc = "Removed {$oldTeacher->name} as Class Teacher of {$class->display_name}.";

            $this->logAudit(
                admin: $admin,
                action: 'removed_class_teacher',
                description: $desc,
                teacher: $oldTeacher,
                class: $class,
                subject: null,
                timetableSlotId: null,
                oldValues: $oldValues,
                newValues: null,
                request: $request
            );
        });
    }

    /**
     * Create or update a Timetable Period assignment with conflict checking.
     */
    public function assignTimetableSlot(User $admin, array $data, ?Request $request = null): SchoolPeriodTimetable
    {
        return DB::transaction(function () use ($admin, $data, $request) {
            $class = AcademicClass::findOrFail($data['class_id']);
            $subject = AcademicSubject::where('class_id', $class->id)->findOrFail($data['subject_id']);
            $periodMaster = SchoolPeriod::active()->where('period_number', (int) $data['period_number'])->where('is_break', false)->first();
            if (! $periodMaster) {
                throw ValidationException::withMessages(['period_number' => 'Select an active teaching period.']);
            }

            $override = (bool) ($data['teacher_override'] ?? false);
            $sessionDefaultTeacherId = $class->academic_year === AcademySetting::get('academic_session') ? $subject->subjectMaster?->default_teacher_id : null;
            $defaultTeacherId = $subject->teacher_id ?? $sessionDefaultTeacherId;
            $teacherId = $data['teacher_id'] ?? $defaultTeacherId;
            if (! $teacherId) {
                throw ValidationException::withMessages(['teacher_id' => 'Assign a subject teacher before scheduling this subject.']);
            }
            $teacher = User::findOrFail($teacherId);
            if (! $teacher->isTeacher()) {
                throw ValidationException::withMessages(['teacher_id' => 'Only teacher accounts can be scheduled.']);
            }
            if (! $defaultTeacherId && ! $override) {
                throw ValidationException::withMessages(['teacher_id' => 'Assign a subject master default teacher first, or explicitly enable teacher override.']);
            }
            if ($defaultTeacherId && (int) $defaultTeacherId !== (int) $teacher->id && ! $override) {
                throw ValidationException::withMessages(['teacher_id' => 'A different teacher is assigned to this subject. Enable the explicit override to use another teacher.']);
            }
            if (! $subject->teacher_id && $defaultTeacherId) {
                $subject->update(['teacher_id' => $defaultTeacherId]);
            }

            $day = $data['day_of_week'];
            $period = (int) $data['period_number'];
            $room = trim((string) ($data['room_number'] ?? $class->room_number ?? '')) ?: null;
            $periodMaster = SchoolPeriod::whereKey($periodMaster->id)->lockForUpdate()->first();
            $existing = SchoolPeriodTimetable::where('class_id', $class->id)->where('day_of_week', $day)->where('period_number', $period)->lockForUpdate()->first();
            if ($existing && (int) $existing->subject_id !== (int) $subject->id) {
                throw ValidationException::withMessages(['period_number' => 'This class period is already occupied by another subject.']);
            }
            if ($subject->weekly_periods && SchoolPeriodTimetable::where('class_id', $class->id)
                ->where('subject_id', $subject->id)
                ->when($existing, fn ($query) => $query->where('id', '!=', $existing->id))
                ->count() >= (int) $subject->weekly_periods) {
                throw ValidationException::withMessages(['subject_id' => 'This subject has already reached its configured weekly period load. Update the subject load before adding another slot.']);
            }
            $this->validateTimetableConflict($class->id, $teacher->id, $day, $period, $room, $existing?->id);

            $slot = SchoolPeriodTimetable::updateOrCreate(
                ['class_id' => $class->id, 'day_of_week' => $day, 'period_number' => $period],
                [
                    'subject_id' => $subject->id,
                    'teacher_id' => $teacher->id,
                    'start_time' => $periodMaster->start_time,
                    'end_time' => $periodMaster->end_time,
                    'room_number' => $room,
                    'is_break' => false,
                    'title' => $subject->name,
                ]
            );

            $this->logAudit(
                admin: $admin,
                action: 'assigned_timetable_slot',
                description: "Scheduled {$subject->name} with {$teacher->name} in {$class->display_name} on {$day}, period {$period}.",
                teacher: $teacher,
                class: $class,
                subject: $subject,
                timetableSlotId: $slot->id,
                oldValues: null,
                newValues: ['day_of_week' => $day, 'period_number' => $period, 'room_number' => $room, 'start_time' => $periodMaster->start_time, 'end_time' => $periodMaster->end_time],
                request: $request
            );

            return $slot;
        }, 3);
    }

    /**
     * Remove a Timetable Period assignment.
     */
    public function removeTimetableSlot(User $admin, SchoolPeriodTimetable $slot, ?Request $request = null): void
    {
        DB::transaction(function () use ($admin, $slot, $request) {
            $class = $slot->academicClass;
            $teacher = $slot->teacher;
            $subject = $slot->subject;

            $oldValues = [
                'day_of_week' => $slot->day_of_week,
                'period_number' => $slot->period_number,
                'subject_name' => $subject ? $subject->name : null,
                'teacher_name' => $teacher ? $teacher->name : null,
            ];

            $desc = "Removed timetable period slot {$slot->period_number} ({$slot->day_of_week}) for {$class->display_name}.";

            $slot->delete();

            $this->logAudit(
                admin: $admin,
                action: 'removed_timetable_slot',
                description: $desc,
                teacher: $teacher,
                class: $class,
                subject: $subject,
                timetableSlotId: null,
                oldValues: $oldValues,
                newValues: null,
                request: $request
            );
        });
    }

    /**
     * Helper to write an audit log entry.
     */
    protected function logAudit(
        User $admin,
        string $action,
        string $description,
        ?User $teacher = null,
        ?AcademicClass $class = null,
        ?AcademicSubject $subject = null,
        ?int $timetableSlotId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?Request $request = null
    ): TeacherAssignmentAudit {
        $ip = $request?->ip();

        return TeacherAssignmentAudit::create([
            'admin_id' => $admin->id,
            'action' => $action,
            'teacher_id' => $teacher ? $teacher->id : null,
            'class_id' => $class ? $class->id : null,
            'subject_id' => $subject ? $subject->id : null,
            'period_timetable_id' => $timetableSlotId,
            'teacher_name' => $teacher ? $teacher->name : null,
            'class_name' => $class ? $class->display_name : null,
            'subject_name' => $subject ? $subject->name : null,
            'description' => $description,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $ip,
        ]);
    }
}
