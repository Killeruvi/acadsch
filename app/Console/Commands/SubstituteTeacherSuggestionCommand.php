<?php

namespace App\Console\Commands;

use App\Models\AttendanceRecord;
use App\Models\SchoolPeriodTimetable;
use App\Models\StudentLeaveRequest;
use App\Models\User;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SubstituteTeacherSuggestionCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'timetable:auto-substitute 
                            {--date= : Target date (YYYY-MM-DD, defaults to today)}
                            {--day= : Day of week (1=Mon..7=Sun, defaults to today\'s day)}
                            {--notify : Dispatch proxy notification alerts to suggested teachers}
                            {--dry-run : Preview without sending alerts}';

    /**
     * The console command description.
     */
    protected $description = 'Automatically detect absent faculty and find free available teachers for period proxy substitution';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dateStr = $this->option('date') ?: Carbon::today()->toDateString();
        $carbonDate = Carbon::parse($dateStr);
        $dayOfWeek = $this->option('day') ? (int) $this->option('day') : $carbonDate->dayOfWeekIso; // 1=Mon, 7=Sun
        $shouldNotify = (bool) $this->option('notify');
        $isDryRun = (bool) $this->option('dry-run');

        $this->info("Scanning faculty attendance & timetable allocations for {$dateStr} (Day {$dayOfWeek})".($isDryRun ? ' [DRY-RUN]' : ''));

        // 1. Identify Absent Faculty Today
        $absentFacultyIds = AttendanceRecord::whereHas('session', function ($q) use ($dateStr) {
            $q->whereDate('session_date', $dateStr);
        })
            ->whereHas('student', function ($q) {
                $q->where('role', 'teacher');
            })
            ->where('status', 'absent')
            ->pluck('student_id')
            ->toArray();

        // Also check teachers on approved leave
        $leaveTeacherIds = StudentLeaveRequest::whereHas('student', fn ($q) => $q->where('role', 'teacher'))
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $dateStr)
            ->whereDate('end_date', '>=', $dateStr)
            ->pluck('student_id')
            ->toArray();

        $absentTeacherIds = array_unique(array_merge($absentFacultyIds, $leaveTeacherIds));

        if (empty($absentTeacherIds)) {
            $this->info("No absent faculty detected for {$dateStr}. Master timetable operating normally.");

            return self::SUCCESS;
        }

        // 2. Identify Timetable Slots Needing Proxy
        $slotsNeedingCoverage = SchoolPeriodTimetable::with(['academicClass', 'subject', 'teacher'])
            ->whereIn('teacher_id', $absentTeacherIds)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_break', false)
            ->orderBy('period_number')
            ->get();

        if ($slotsNeedingCoverage->isEmpty()) {
            $this->info("Absent faculty have no assigned teaching slots on day {$dayOfWeek}.");

            return self::SUCCESS;
        }

        // 3. For each slot, find available faculty with zero period conflicts
        $allTeachers = User::where('role', 'teacher')->whereNotIn('id', $absentTeacherIds)->get();
        $proxyProposals = [];

        foreach ($slotsNeedingCoverage as $slot) {
            $periodNum = $slot->period_number;
            $busyTeacherIds = SchoolPeriodTimetable::where('day_of_week', $dayOfWeek)
                ->where('period_number', $periodNum)
                ->where('is_break', false)
                ->pluck('teacher_id')
                ->filter()
                ->toArray();

            $freeTeachers = $allTeachers->reject(fn ($t) => in_array($t->id, $busyTeacherIds));
            $suggested = $freeTeachers->first();

            $proxyProposals[] = [
                'period' => "Period {$periodNum} ({$slot->start_time} - {$slot->end_time})",
                'class' => $slot->academicClass?->display_name ?? 'Class',
                'subject' => $slot->subject?->name ?? 'General',
                'absent_teacher' => $slot->teacher?->name ?? 'Absent Faculty',
                'suggested_proxy' => $suggested ? $suggested->name : '⚠️ None Available',
                'proxy_id' => $suggested?->id,
            ];

            if ($suggested && $shouldNotify && ! $isDryRun) {
                NotificationService::notifyUser(
                    $suggested->id,
                    'timetable',
                    "📋 Proxy Coverage Request: Period {$periodNum}",
                    "You are requested for proxy substitution for Period {$periodNum} in {$slot->academicClass?->display_name} for {$slot->subject?->name} ({$slot->start_time} - {$slot->end_time}) today.",
                    route('schedules.index'),
                    '📋'
                );
            }
        }

        $this->table(
            ['Period Slot', 'Class', 'Subject', 'Original Teacher', 'Suggested Free Proxy'],
            array_map(fn ($p) => [$p['period'], $p['class'], $p['subject'], $p['absent_teacher'], $p['suggested_proxy']], $proxyProposals)
        );

        $this->info('Proxy analysis complete. Generated '.count($proxyProposals).' coverage recommendations.');

        return self::SUCCESS;
    }
}
