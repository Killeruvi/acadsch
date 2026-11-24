<?php

namespace App\Services;

use App\Models\AcademicClass;
use App\Models\AcademicSubject;
use App\Models\SchoolPeriod;
use App\Models\SchoolPeriodSubstitute;
use App\Models\SchoolPeriodTimetable;
use App\Models\StaffLeaveApplication;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class TimetableConflictService
{
    /**
     * Check if a specific slot has conflicts for a teacher, class, or room.
     */
    public function checkSlotConflict(
        int $classId,
        ?int $teacherId,
        string $dayOfWeek,
        int $periodNumber,
        ?string $roomNumber = null,
        ?int $ignoreTimetableId = null
    ): array {
        $conflicts = [];

        // 1. Class conflict
        $existingClassSlot = SchoolPeriodTimetable::where('class_id', $classId)
            ->where('day_of_week', $dayOfWeek)
            ->where('period_number', $periodNumber)
            ->when($ignoreTimetableId, fn ($q) => $q->where('id', '!=', $ignoreTimetableId))
            ->with(['subject', 'teacher'])
            ->first();

        if ($existingClassSlot) {
            $conflicts[] = [
                'type' => 'class_occupied',
                'message' => "Class already has {$existingClassSlot->subject?->name} scheduled with {$existingClassSlot->teacher?->name}.",
                'existing_slot_id' => $existingClassSlot->id,
            ];
        }

        // 2. Teacher conflict
        if ($teacherId) {
            $existingTeacherSlot = SchoolPeriodTimetable::where('teacher_id', $teacherId)
                ->where('day_of_week', $dayOfWeek)
                ->where('period_number', $periodNumber)
                ->when($ignoreTimetableId, fn ($q) => $q->where('id', '!=', $ignoreTimetableId))
                ->with(['academicClass', 'subject'])
                ->first();

            if ($existingTeacherSlot) {
                $className = $existingTeacherSlot->academicClass?->display_name ?? $existingTeacherSlot->academicClass?->name ?? 'another class';
                $conflicts[] = [
                    'type' => 'teacher_clash',
                    'message' => "Selected teacher is already teaching {$existingTeacherSlot->subject?->name} in {$className}.",
                    'existing_slot_id' => $existingTeacherSlot->id,
                ];
            }
        }

        // 3. Room conflict
        if (! empty($roomNumber)) {
            $existingRoomSlot = SchoolPeriodTimetable::where('room_number', $roomNumber)
                ->where('day_of_week', $dayOfWeek)
                ->where('period_number', $periodNumber)
                ->when($ignoreTimetableId, fn ($q) => $q->where('id', '!=', $ignoreTimetableId))
                ->with(['academicClass', 'teacher'])
                ->first();

            if ($existingRoomSlot) {
                $className = $existingRoomSlot->academicClass?->display_name ?? 'another class';
                $conflicts[] = [
                    'type' => 'room_clash',
                    'message' => "Room {$roomNumber} is already occupied by {$className}.",
                    'existing_slot_id' => $existingRoomSlot->id,
                ];
            }
        }

        return [
            'has_conflict' => ! empty($conflicts),
            'conflicts' => $conflicts,
        ];
    }

    /**
     * Find qualified teachers who are free during a specific day, date, and period.
     */
    public function findAvailableSubstituteTeachers(
        string $date,
        string $dayOfWeek,
        int $periodNumber,
        ?int $subjectId = null
    ): Collection {
        $parsedDate = Carbon::parse($date)->toDateString();

        // 1. All active teachers
        $allTeachers = User::where('role', 'teacher')
            ->where('is_approved', true)
            ->get();

        // 2. Teachers currently on approved leave on this date
        $onLeaveTeacherIds = StaffLeaveApplication::where('status', 'approved')
            ->whereDate('start_date', '<=', $parsedDate)
            ->whereDate('end_date', '>=', $parsedDate)
            ->pluck('user_id')
            ->toArray();

        // 3. Teachers already teaching in this period on this weekday
        $busyTeacherIds = SchoolPeriodTimetable::where('day_of_week', $dayOfWeek)
            ->where('period_number', $periodNumber)
            ->pluck('teacher_id')
            ->filter()
            ->toArray();

        // 4. Teachers already acting as substitutes on this date and period
        $substituteBusyIds = SchoolPeriodSubstitute::active()
            ->where('date', $parsedDate)
            ->whereHas('timetable', fn ($q) => $q->where('period_number', $periodNumber))
            ->pluck('substitute_teacher_id')
            ->toArray();

        $unavailableIds = array_unique(array_merge($onLeaveTeacherIds, $busyTeacherIds, $substituteBusyIds));

        // Filter free teachers
        return $allTeachers->filter(function ($teacher) use ($unavailableIds) {
            return ! in_array($teacher->id, $unavailableIds, true);
        })->values();
    }
}
