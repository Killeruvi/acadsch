<?php

namespace App\Console\Commands;

use App\Models\AcademicClass;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\StudentLeaveRequest;
use App\Models\User;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class DailyAttendanceAlertsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'attendance:auto-alert 
                            {--date= : Target date (YYYY-MM-DD, defaults to today)}
                            {--dry-run : Preview alerts without updating records or sending notifications}';

    /**
     * The console command description.
     */
    protected $description = 'Automatically dispatch parent absence alerts and notify homeroom teachers of missing morning roll calls';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dateStr = $this->option('date') ?: Carbon::today()->toDateString();
        $isDryRun = (bool) $this->option('dry-run');

        $this->info("Scanning attendance records for date: {$dateStr}" . ($isDryRun ? " (DRY-RUN)" : ""));

        // 1. Process Absent Students
        $absentRecords = AttendanceRecord::with(['student.parent', 'student.currentClass', 'session.academicClass'])
            ->whereHas('session', function ($query) use ($dateStr) {
                $query->whereDate('session_date', $dateStr);
            })
            ->where('status', 'absent')
            ->get();

        $alertsSent = 0;
        $leavesExcused = 0;

        foreach ($absentRecords as $record) {
            $student = $record->student;
            if (!$student) {
                continue;
            }

            // Check if student has an approved leave request covering this date
            $hasApprovedLeave = StudentLeaveRequest::where('student_id', $student->id)
                ->where('status', 'approved')
                ->whereDate('start_date', '<=', $dateStr)
                ->whereDate('end_date', '>=', $dateStr)
                ->exists();

            if ($hasApprovedLeave) {
                if (!$isDryRun) {
                    $record->update([
                        'status' => 'excused',
                        'remarks' => $record->remarks ? $record->remarks . ' (Auto-excused: Approved Leave)' : 'Auto-excused: Approved Leave',
                    ]);
                }
                $leavesExcused++;
                continue;
            }

            $className = $record->session?->academicClass?->display_name ?? $student->currentClass?->display_name ?? 'Class';
            $parent = $student->parent ?: User::where('role', 'parent')
                ->where('email', $student->guardian_email)
                ->first();

            if (!$isDryRun) {
                // Alert Parent if linked
                if ($parent) {
                    NotificationService::notifyUser(
                        $parent->id,
                        'attendance',
                        "⚠️ Absence Alert: {$student->name}",
                        "Your child {$student->name} was marked absent on {$dateStr} in {$className}. Please contact the school office if this was in error.",
                        route('school.parent.dashboard'),
                        '⚠️'
                    );
                }

                // Alert Student directly
                NotificationService::notifyUser(
                    $student->id,
                    'attendance',
                    "Attendance Notification: Marked Absent",
                    "You were recorded as absent on {$dateStr} for {$className}.",
                    route('attendance.index'),
                    '⚠️'
                );
            }

            $alertsSent++;
        }

        // 2. Check Active Classes with Missing Roll Call
        $classes = AcademicClass::all();
        $missingClassesCount = 0;
        $remindedTeachersCount = 0;

        foreach ($classes as $class) {
            $hasSession = AttendanceSession::where('class_id', $class->id)
                ->whereDate('session_date', $dateStr)
                ->exists();

            if (!$hasSession) {
                $missingClassesCount++;
                if ($class->class_teacher_id) {
                    if (!$isDryRun) {
                        NotificationService::notifyUser(
                            $class->class_teacher_id,
                            'attendance',
                            "⏰ Action Required: Morning Roll Call Missing",
                            "Daily roll call for {$class->display_name} on {$dateStr} has not been recorded yet. Please submit the attendance register.",
                            route('school.attendance.take', $class->id),
                            '⏰'
                        );
                    }
                    $remindedTeachersCount++;
                }
            }
        }

        $this->table(
            ['Metric', 'Count'],
            [
                ['Target Date', $dateStr],
                ['Absent Records Scanned', $absentRecords->count()],
                ['Auto-Excused by Approved Leave', $leavesExcused],
                ['Parent & Student Absence Alerts Dispatched', $alertsSent],
                ['Classes Missing Daily Roll Call', $missingClassesCount],
                ['Homeroom Teachers Reminded', $remindedTeachersCount],
                ['Execution Mode', $isDryRun ? 'DRY-RUN (Simulated)' : 'LIVE (Dispatched)'],
            ]
        );

        $this->info("Daily attendance automation completed successfully.");
        return self::SUCCESS;
    }
}
