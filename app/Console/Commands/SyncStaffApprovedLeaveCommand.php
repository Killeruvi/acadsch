<?php

namespace App\Console\Commands;

use App\Models\AcademicHoliday;
use App\Models\StaffAttendanceRecord;
use App\Models\StaffLeaveRequest;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncStaffApprovedLeaveCommand extends Command
{
    protected $signature = 'staff:sync-approved-leave';

    protected $description = 'Create today staff attendance leave records from approved staff leave requests';

    public function handle(): int
    {
        $date = now()->toDateString();
        if (in_array(now()->format('l'), config('attendance.weekend_days', ['Sunday']), true) || AcademicHoliday::findHolidayForDate(now())) {
            $this->info('Today is a configured non-working day; no staff leave attendance was created.');

            return self::SUCCESS;
        }

        StaffLeaveRequest::query()->where('status', 'approved')
            ->whereDate('start_date', '<=', $date)->whereDate('end_date', '>=', $date)
            ->orderBy('id')->chunkById(200, function ($leaves) use ($date) {
                foreach ($leaves as $leave) {
                    DB::transaction(function () use ($leave, $date) {
                        $attendance = StaffAttendanceRecord::firstOrCreate(
                            ['staff_id' => $leave->staff_id, 'attendance_date' => $date],
                            ['status' => 'leave', 'remarks' => ucfirst($leave->leave_type).' leave approved', 'marked_by' => $leave->reviewed_by]
                        );
                        if ($attendance->wasRecentlyCreated || $attendance->status === 'absent') {
                            $before = $attendance->wasRecentlyCreated ? null : $attendance->only(['staff_id', 'attendance_date', 'status', 'remarks', 'marked_by']);
                            if ($before) {
                                $attendance->update(['status' => 'leave', 'remarks' => ucfirst($leave->leave_type).' leave approved', 'marked_by' => $leave->reviewed_by]);
                            }
                            DB::table('staff_hr_audits')->insert([
                                'entity_type' => 'attendance', 'entity_id' => $attendance->id,
                                'actor_id' => $leave->reviewed_by, 'event_type' => $before ? 'absence_excused_by_leave' : 'leave_synced',
                                'before_data' => $before ? json_encode($before, JSON_THROW_ON_ERROR) : null,
                                'after_data' => json_encode($attendance->fresh()->only(['staff_id', 'attendance_date', 'status', 'remarks', 'marked_by']), JSON_THROW_ON_ERROR),
                                'created_at' => now(),
                            ]);
                        }
                    }, 3);
                }
            });

        $this->info('Approved staff leave synchronized for '.$date.'.');

        return self::SUCCESS;
    }
}
