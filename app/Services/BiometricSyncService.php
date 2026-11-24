<?php

namespace App\Services;

use App\Models\AcademicClass;
use App\Models\AcademySetting;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\BiometricDevice;
use App\Models\BiometricLog;
use App\Models\ClassEnrollment;
use App\Models\StaffAttendanceRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BiometricSyncService
{
    /**
     * Record a raw biometric punch event.
     */
    public function recordRawPunch(
        string $pin,
        string|Carbon $punchTime,
        ?int $deviceId = null,
        string $punchType = 'auto'
    ): BiometricLog {
        $parsedTime = Carbon::parse($punchTime);

        // Deduplicate against identical punch within 3 minutes
        $existing = BiometricLog::where('biometric_pin', $pin)
            ->whereBetween('punch_time', [
                $parsedTime->copy()->subMinutes(3),
                $parsedTime->copy()->addMinutes(3),
            ])
            ->first();

        if ($existing) {
            return $existing;
        }

        $log = BiometricLog::create([
            'device_id' => $deviceId,
            'biometric_pin' => trim($pin),
            'punch_time' => $parsedTime,
            'punch_type' => in_array($punchType, ['in', 'out', 'auto']) ? $punchType : 'auto',
            'status' => 'pending',
        ]);

        // Attempt immediate synchronization
        $this->processSinglePunch($log);

        return $log;
    }

    /**
     * Process a batch of pending punch logs.
     */
    public function processPendingLogs(int $limit = 200): array
    {
        $pending = BiometricLog::where('status', 'pending')
            ->orderBy('punch_time', 'asc')
            ->limit($limit)
            ->get();

        $processed = 0;
        $failed = 0;

        foreach ($pending as $log) {
            if ($this->processSinglePunch($log)) {
                $processed++;
            } else {
                $failed++;
            }
        }

        return [
            'total_attempted' => $pending->count(),
            'processed' => $processed,
            'failed' => $failed,
        ];
    }

    /**
     * Process a single biometric log and update appropriate attendance registers.
     */
    public function processSinglePunch(BiometricLog $log): bool
    {
        try {
            return DB::transaction(function () use ($log) {
                $pin = $log->biometric_pin;

                // Match user: biometric_pin -> admission_number -> user_id
                $user = User::where('biometric_pin', $pin)
                    ->orWhere('admission_number', $pin)
                    ->orWhere('id', is_numeric($pin) ? (int) $pin : 0)
                    ->first();

                if (! $user) {
                    $log->update([
                        'status' => 'failed',
                        'error_message' => "No scholar or staff user found matching biometric PIN '{$pin}'.",
                    ]);

                    return false;
                }

                $log->user_id = $user->id;
                $punchDate = $log->punch_time->toDateString();
                $punchTimeStr = $log->punch_time->format('H:i');

                // Shift cutoff configuration (Default 08:30 AM)
                $morningCutoff = AcademySetting::get('school_morning_cutoff', '08:30');
                $isLate = $punchTimeStr > $morningCutoff;

                if ($user->isStudent()) {
                    // Resolve active class
                    $classId = $user->current_class_id ??
                        ClassEnrollment::where('student_id', $user->id)
                            ->where('status', 'active')
                            ->latest('id')
                            ->value('class_id');

                    if (! $classId) {
                        $log->update([
                            'status' => 'failed',
                            'error_message' => "Student #{$user->id} ({$user->name}) has no active class enrollment.",
                        ]);

                        return false;
                    }

                    // Get or create daily attendance session for this class
                    $session = AttendanceSession::firstOrCreate(
                        [
                            'class_id' => $classId,
                            'session_date' => $punchDate,
                        ],
                        [
                            'title' => "Daily Roll Call - {$punchDate}",
                            'teacher_id' => AcademicClass::find($classId)?->class_teacher_id ?? 1,
                        ]
                    );

                    // Update or create student attendance record
                    $status = $isLate ? 'late' : 'present';
                    $record = AttendanceRecord::updateOrCreate(
                        [
                            'session_id' => $session->id,
                            'student_id' => $user->id,
                        ],
                        [
                            'status' => $status,
                            'remarks' => "Biometric punch via device at {$punchTimeStr}".($isLate ? ' (Late arrival)' : ''),
                        ]
                    );
                } else {
                    // Staff attendance record
                    $status = $isLate ? 'late' : 'present';
                    StaffAttendanceRecord::updateOrCreate(
                        [
                            'staff_id' => $user->id,
                            'attendance_date' => $punchDate,
                        ],
                        [
                            'status' => $status,
                            'remarks' => "Biometric punch at {$punchTimeStr}".($isLate ? ' (Late arrival)' : ''),
                            'marked_by' => null,
                        ]
                    );
                }

                $log->status = 'processed';
                $log->error_message = null;
                $log->save();

                return true;
            });
        } catch (\Throwable $e) {
            Log::error("Biometric processing error for log #{$log->id}: ".$e->getMessage());
            $log->update([
                'status' => 'failed',
                'error_message' => substr($e->getMessage(), 0, 500),
            ]);

            return false;
        }
    }
}
