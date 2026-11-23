<?php

namespace App\Console\Commands;

use App\Models\AcademicClass;
use App\Models\AcademicHoliday;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\StudentLeaveRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AutoAbsentDayChangeCommand extends Command
{
    protected $signature = 'attendance:auto-absent {--date= : Target date (defaults to today for the scheduled end-of-day run)}';
    protected $description = 'Mark unrecorded active students absent after the instructional day, respecting holidays and approved leave.';

    public function handle(): int
    {
        $date = $this->option('date') ? Carbon::parse($this->option('date'))->startOfDay() : Carbon::today()->startOfDay();
        $dateStr = $date->toDateString();

        if ($date->isFuture()) {
            $this->error('Auto-absent cannot process a future date.');
            return self::FAILURE;
        }
        if (in_array($date->format('l'), config('attendance.weekend_days', []), true) || AcademicHoliday::findHolidayForDate($dateStr)) {
            $this->info("{$dateStr} is a non-instructional day; no absences were generated.");
            return self::SUCCESS;
        }

        $adminId = User::query()->where('role', 'admin')->value('id');
        if (!$adminId) {
            $this->error('No administrator account exists to own system-generated attendance sessions.');
            Log::critical('Auto-absent stopped: no administrator account exists.', ['date' => $dateStr]);
            return self::FAILURE;
        }

        $processed = 0;
        $absent = 0;
        $excused = 0;

        AcademicClass::query()->with('students')->orderBy('id')->chunk(50, function ($classes) use ($dateStr, $adminId, &$processed, &$absent, &$excused) {
            foreach ($classes as $class) {
                $students = $class->students;
                if ($students->isEmpty()) {
                    continue;
                }

                DB::transaction(function () use ($class, $students, $dateStr, $adminId, &$processed, &$absent, &$excused) {
                    $session = AttendanceSession::query()
                        ->where('class_id', $class->id)
                        ->whereDate('session_date', $dateStr)
                        ->lockForUpdate()
                        ->first();

                    if (!$session) {
                        $session = AttendanceSession::create([
                            'class_id' => $class->id,
                            'teacher_id' => $class->class_teacher_id ?: $adminId,
                            'session_date' => $dateStr,
                            'title' => 'Official Daily Roll Call',
                            'session_type' => 'daily_homeroom',
                            'notes' => 'System-generated close-of-day attendance completion.',
                        ]);
                    }

                    $studentIds = $students->pluck('id');
                    $existingRecords = AttendanceRecord::query()->where('session_id', $session->id)
                        ->whereIn('student_id', $studentIds)->get()->keyBy('student_id');
                    $leaves = StudentLeaveRequest::query()->where('status', 'approved')
                        ->whereDate('start_date', '<=', $dateStr)->whereDate('end_date', '>=', $dateStr)
                        ->whereIn('student_id', $studentIds)->get()->keyBy('student_id');

                    foreach ($students as $student) {
                        $leave = $leaves->get($student->id);
                        $existing = $existingRecords->get($student->id);
                        if ($existing) {
                            // Approved leave can convert only an auto-generated absence, never a teacher-entered decision.
                            if ($leave && $existing->status === 'absent'
                                && str_starts_with((string) $existing->remarks, 'No attendance submitted by close of instructional day.')) {
                                DB::table('attendance_record_revisions')->insert([
                                    'attendance_record_id' => $existing->id,
                                    'attendance_session_id' => $session->id,
                                    'student_id' => $student->id,
                                    'actor_id' => $adminId,
                                    'before_status' => 'absent',
                                    'after_status' => 'excused',
                                    'source' => 'auto_absent_leave_sync',
                                    'reason' => 'Approved leave synchronized to attendance.',
                                    'changed_at' => now(), 'created_at' => now(), 'updated_at' => now(),
                                ]);
                                $existing->update(['status' => 'excused', 'remarks' => 'Approved leave (system-synced)']);
                                $excused++;
                            }
                            continue;
                        }
                        AttendanceRecord::create([
                            'session_id' => $session->id,
                            'student_id' => $student->id,
                            'status' => $leave ? 'excused' : 'absent',
                            'remarks' => $leave ? 'Approved leave (system-generated)' : 'No attendance submitted by close of instructional day.',
                        ]);
                        $leave ? $excused++ : $absent++;
                    }
                    $processed++;
                });
            }
        });

        $summary = "Auto-absent completed for {$dateStr}: {$processed} classes, {$absent} absent, {$excused} excused.";
        $this->info($summary);
        Log::info($summary);
        return self::SUCCESS;
    }
}
