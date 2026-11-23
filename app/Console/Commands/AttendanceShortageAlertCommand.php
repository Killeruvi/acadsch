<?php

namespace App\Console\Commands;

use App\Models\AttendanceRecord;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class AttendanceShortageAlertCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'attendance:audit-shortage 
                            {--threshold= : Minimum attendance percentage threshold (defaults to institution configuration)}
                            {--min-sessions= : Minimum sessions before triggering shortage warnings}
                            {--dry-run : Preview defaulters without dispatching notifications}';

    /**
     * The console command description.
     */
    protected $description = 'Weekly audit to detect students below the institution-configured attendance threshold and dispatch alerts';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $threshold = $this->option('threshold') !== null ? (float) $this->option('threshold') : (float) config('attendance.minimum_percentage');
        $minSessions = $this->option('min-sessions') !== null ? (int) $this->option('min-sessions') : (int) config('attendance.minimum_sessions_for_alert');
        $isDryRun = (bool) $this->option('dry-run');

        $this->info("Starting attendance shortage audit (Threshold: {$threshold}%, Min Sessions: {$minSessions})" . ($isDryRun ? " [DRY-RUN]" : ""));

        $students = User::where('role', 'student')
            ->with(['parent', 'currentClass'])
            ->get();

        $defaulters = [];

        foreach ($students as $student) {
            $records = AttendanceRecord::where('student_id', $student->id)->get();
            $excusedCount = $records->where('status', 'excused')->count();
            $totalSessions = max(0, $records->count() - $excusedCount);

            if ($totalSessions < $minSessions) {
                continue; // Insufficient data
            }

            $fullPresence = $records->whereIn('status', ['present', 'late'])->count();
            $halfDays = $records->where('status', 'half_day')->count();
            $presentCount = $fullPresence + ($halfDays * 0.5);
            $pct = $totalSessions > 0 ? round(($presentCount / $totalSessions) * 100, 1) : 0.0;

            if ($pct < $threshold) {
                $defaulters[] = [
                    'student' => $student,
                    'total' => $totalSessions,
                    'present' => $presentCount,
                    'pct' => $pct,
                ];

                if (!$isDryRun) {
                    $className = $student->currentClass?->display_name ?? 'Class';

                    // Alert student
                    NotificationService::notifyUser(
                        $student->id,
                        'attendance',
                        "⚠️ Attendance Warning: {$pct}%",
                        "Your cumulative attendance in {$className} is {$pct}% ({$presentCount}/{$totalSessions} days), which is below the mandatory {$threshold}% requirement. Regular attendance is required for exam eligibility.",
                        route('attendance.index'),
                        '⚠️'
                    );

                    // Alert parent
                    $parent = $student->parent ?: User::where('role', 'parent')
                        ->where('email', $student->guardian_email)
                        ->first();

                    if ($parent) {
                        NotificationService::notifyUser(
                            $parent->id,
                            'attendance',
                            "⚠️ Low Attendance Warning: {$student->name}",
                            "Your child {$student->name}'s attendance has dropped to {$pct}% ({$presentCount}/{$totalSessions} days), falling below the required {$threshold}% institutional standard.",
                            route('school.parent.dashboard'),
                            '⚠️'
                        );
                    }
                }
            }
        }

        $tableRows = array_map(function ($d) {
            return [
                $d['student']->name,
                $d['student']->currentClass?->display_name ?? 'N/A',
                $d['total'],
                $d['present'],
                "{$d['pct']}%",
            ];
        }, array_slice($defaulters, 0, 15));

        if (!empty($tableRows)) {
            $this->table(
                ['Student', 'Class', 'Total Sessions', 'Attended', 'Attendance %'],
                $tableRows
            );
        }

        $this->info("Audit finished. Found " . count($defaulters) . " students below {$threshold}% attendance threshold.");
        return self::SUCCESS;
    }
}
