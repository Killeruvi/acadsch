<?php

namespace App\Services;

use App\Models\AcademicClass;
use App\Models\AcademySetting;
use App\Models\AttendanceRejectionLog;
use App\Models\SchoolPeriodTimetable;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AttendanceAccessControlService
{
    /**
     * Comprehensive validation for attendance marking access control.
     * Enforces Admin privileges, Teacher timetable/period restrictions, and holiday/weekend rules.
     *
     * @return array ['allowed' => bool, 'reason' => string|null, 'period' => SchoolPeriodTimetable|null]
     */
    public function validateAttendanceAttempt(
        Request $request,
        User $user,
        AcademicClass $class,
        ?string $sessionDate = null,
        ?int $periodNumber = null,
        string $action = 'store'
    ): array {
        // 1. Holiday & Weekend Rules
        if ($sessionDate) {
            if (in_array(\Carbon\Carbon::parse($sessionDate)->format('l'), config('attendance.weekend_days', []), true)) {
                $reason = 'Weekend Restriction: attendance cannot be recorded on a configured non-instructional weekend day.';
                $this->logRejection($request, $user, $class->id, $action, $reason, $sessionDate);
                return ['allowed' => false, 'reason' => $reason, 'period' => null];
            }

            $holiday = \App\Models\AcademicHoliday::findHolidayForDate($sessionDate);

            if ($holiday) {
                $reason = "Academic Holiday: Attendance cannot be recorded on '{$holiday->name}'. The institution is closed.";
                $this->logRejection($request, $user, $class->id, $action, $reason, $sessionDate);
                return ['allowed' => false, 'reason' => $reason, 'period' => null];
            }
        }

        // 2. Admin Rule: Can mark attendance anytime, for any class/period. Not restricted by period time.
        if ($user->isAdmin()) {
            return [
                'allowed' => true,
                'is_admin' => true,
                'reason' => null,
                'period' => null,
            ];
        }

        // 3. Reject non-teachers (Parents and Students cannot mark attendance)
        if (!$user->isTeacher()) {
            $reason = "Access Denied: Only faculty and administrators are permitted to record attendance.";
            $this->logRejection($request, $user, $class->id, $action, $reason, $sessionDate);
            return [
                'allowed' => false,
                'reason' => $reason,
                'period' => null,
            ];
        }

        // 4. Teacher Rules: Assigned class + assigned period time (per timetable)
        return $this->validateTeacherTimetable($request, $user, $class, $sessionDate, $periodNumber, $action);
    }

    /**
     * Validate that teacher is assigned to the class and current time is within an assigned period.
     */
    public function validateTeacherTimetable(
        Request $request,
        User $user,
        AcademicClass $class,
        ?string $sessionDate = null,
        ?int $periodNumber = null,
        string $action = 'timetable_check'
    ): array {
        $now = Carbon::now();
        $targetDate = $sessionDate ? Carbon::parse($sessionDate)->toDateString() : $now->toDateString();

        // 1. Assignment Check: Teacher must be assigned as class teacher, subject teacher, timetable teacher, or active proxy substitute
        $isClassTeacher = ($class->class_teacher_id === $user->id);
        $teachesSubject = $class->subjects()->where('teacher_id', $user->id)->exists();
        $hasTimetable = SchoolPeriodTimetable::where('class_id', $class->id)
            ->where(function ($q) use ($user) {
                $q->where('teacher_id', $user->id)
                  ->orWhereHas('subject', fn($sq) => $sq->where('teacher_id', $user->id));
            })->exists();

        $substituteTimetableIds = \App\Models\SchoolPeriodSubstitute::active()
            ->where('substitute_teacher_id', $user->id)
            ->where('date', $targetDate)
            ->whereHas('timetable', fn($q) => $q->where('class_id', $class->id))
            ->pluck('timetable_id');
        $isSubstitute = $substituteTimetableIds->isNotEmpty();

        if (!$isClassTeacher && !$teachesSubject && !$hasTimetable && !$isSubstitute) {
            $reason = "Access Denied: You cannot mark attendance for {$class->display_name} as you are not assigned to this class, any of its subjects, or as an active proxy substitute.";
            $this->logRejection($request, $user, $class->id, $action, $reason, $sessionDate);
            return ['allowed' => false, 'reason' => $reason, 'period' => null];
        }

        // 2. Day & Period Time Check (per timetable)
        $currentDay = $now->format('l'); // 'Monday', 'Tuesday', etc.
        $currentTime = $now->format('H:i');

        // Check if attendance is being submitted for today
        if ($sessionDate && Carbon::parse($sessionDate)->toDateString() !== $now->toDateString()) {
            $targetDateStr = Carbon::parse($sessionDate)->format('d M Y');
            $reason = "Access Denied: Attendance can only be marked during your live assigned period today ({$now->format('d M Y')}). Marking retroactively or in advance for {$targetDateStr} is reserved for administrators.";
            $this->logRejection($request, $user, $class->id, $action, $reason, $sessionDate);
            return ['allowed' => false, 'reason' => $reason, 'period' => null];
        }

        // Fetch all non-break periods assigned to this teacher for this class
        $query = SchoolPeriodTimetable::where('class_id', $class->id)
            ->where(function ($q) use ($user, $substituteTimetableIds) {
                $q->where('teacher_id', $user->id)
                  ->orWhereHas('subject', fn($sq) => $sq->where('teacher_id', $user->id));
                if ($substituteTimetableIds->isNotEmpty()) {
                    $q->orWhereIn('id', $substituteTimetableIds);
                }
            })
            ->where('is_break', false);

        if ($periodNumber !== null) {
            $query->where('period_number', $periodNumber);
        }

        $allAssignedPeriods = (clone $query)->get();

        // Check if teacher has periods assigned on any day
        if ($allAssignedPeriods->isEmpty()) {
            $classHasTimetable = SchoolPeriodTimetable::where('class_id', $class->id)->where('is_break', false)->exists();
            if ($isClassTeacher || !$classHasTimetable) {
                // Class teacher, or assigned teacher when no class periods are configured yet, has general roll-call authority
                return ['allowed' => true, 'reason' => null, 'period' => null];
            }

            $reason = "Access Denied: You have no assigned timetable periods for {$class->display_name}. Attendance can only be marked during your assigned period.";
            $this->logRejection($request, $user, $class->id, $action, $reason, $sessionDate);
            return ['allowed' => false, 'reason' => $reason, 'period' => null];
        }

        // Check periods assigned for today
        $periodsToday = $allAssignedPeriods->where('day_of_week', $currentDay)->values();

        if ($periodsToday->isEmpty()) {
            $assignedDays = $allAssignedPeriods->pluck('day_of_week')->unique()->implode(', ');
            $reason = "Access Denied: You have no assigned periods for {$class->display_name} today ({$currentDay}). Your assigned teaching days are: {$assignedDays}.";
            $this->logRejection($request, $user, $class->id, $action, $reason, $sessionDate);
            return ['allowed' => false, 'reason' => $reason, 'period' => null];
        }

        // Check if current time falls within any assigned period today (with configurable grace window)
        $graceMinutes = (int) AcademySetting::get('attendance_period_grace_minutes', (string) config('attendance.period_grace_minutes', 5));
        $matchingPeriod = null;

        foreach ($periodsToday as $period) {
            $startFormatted = substr($period->start_time, 0, 5);
            $endFormatted = substr($period->end_time, 0, 5);

            $periodStart = Carbon::createFromFormat('H:i', $startFormatted)->subMinutes($graceMinutes);
            $periodEnd = Carbon::createFromFormat('H:i', $endFormatted)->addMinutes($graceMinutes);
            $nowCarbon = Carbon::createFromFormat('H:i', $currentTime);

            if ($nowCarbon->between($periodStart, $periodEnd)) {
                $matchingPeriod = $period;
                break;
            }
        }

        if ($matchingPeriod) {
            return [
                'allowed' => true,
                'reason' => null,
                'period' => $matchingPeriod,
            ];
        }

        // Current time is outside all assigned periods for today
        $slots = $periodsToday->map(function ($p) {
            $s = substr($p->start_time, 0, 5);
            $e = substr($p->end_time, 0, 5);
            $title = $p->title ?: "Period {$p->period_number}";
            return "{$title} ({$s} - {$e})";
        })->implode(', ');

        $reason = "Access Denied: Cannot mark attendance outside your assigned period time. Your assigned period(s) for {$class->display_name} today ({$currentDay}): {$slots}. Current time is {$currentTime}.";
        $this->logRejection($request, $user, $class->id, $action, $reason, $sessionDate);

        return [
            'allowed' => false,
            'reason' => $reason,
            'period' => null,
        ];
    }

    /**
     * Log rejected attendance attempt to database audit log and Laravel system log.
     */
    public function logRejection(
        Request $request,
        User $user,
        ?int $classId,
        string $action,
        string $reason,
        ?string $sessionDate = null
    ): AttendanceRejectionLog {
        $clientIp = $request->ip() ?: '127.0.0.1';

        $log = AttendanceRejectionLog::create([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_role' => $user->role,
            'class_id' => $classId,
            'action' => $action,
            'reason' => $reason,
            'ip_address' => $clientIp,
            'session_date' => $sessionDate ? Carbon::parse($sessionDate)->toDateString() : now()->toDateString(),
            'attempted_at' => now(),
            'context' => [
                'user_agent' => $request->userAgent(),
                'method' => $request->method(),
                'url' => $request->fullUrl(),
            ],
        ]);

        Log::warning("Attendance access rejected: [{$user->name} (#{$user->id}) - Role: {$user->role}] Reason: {$reason}. Class: {$classId}. IP: {$clientIp}. Date: {$sessionDate}");

        return $log;
    }
}
