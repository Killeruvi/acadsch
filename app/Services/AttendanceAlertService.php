<?php

namespace App\Services;

use App\Models\AcademySetting;
use App\Models\AttendanceAlertLog;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AttendanceAlertService
{
    /**
     * Dispatch absentee alerts for a specific date (defaults to today).
     */
    public function dispatchDailyAbsenteeAlerts(?string $date = null): array
    {
        $targetDate = $date ? Carbon::parse($date)->toDateString() : now()->toDateString();
        $schoolName = AcademySetting::get('school_name', config('app.name', 'School ERP'));

        // Fetch all absent records for the given date
        $absentRecords = AttendanceRecord::whereHas('session', function ($q) use ($targetDate) {
            $q->whereDate('session_date', $targetDate);
        })
            ->where('status', 'absent')
            ->with(['student.currentClass', 'session'])
            ->get();

        $stats = [
            'date' => $targetDate,
            'total_absentees' => $absentRecords->count(),
            'in_app_sent' => 0,
            'telegram_sent' => 0,
            'already_notified' => 0,
            'failed' => 0,
        ];

        foreach ($absentRecords as $record) {
            $student = $record->student;
            if (! $student) {
                continue;
            }

            $className = $student->currentClass?->display_name ?? 'Class';
            $alertMessage = "Dear Guardian, your ward {$student->name} ({$className}) has been marked ABSENT for morning roll call on {$targetDate}. Please contact {$schoolName} administration if this absence is unexpected.";

            // 1. In-App Notification to Parent
            if ($student->parent_id) {
                $alreadyLogged = AttendanceAlertLog::where('student_id', $student->id)
                    ->whereDate('attendance_date', $targetDate)
                    ->where('channel', 'in_app')
                    ->exists();

                if (! $alreadyLogged) {
                    NotificationService::notifyUser(
                        $student->parent_id,
                        'attendance_alert',
                        "Morning Absence Alert: {$student->name}",
                        $alertMessage,
                        route('school.parent.dashboard'),
                        '⚠️'
                    );

                    AttendanceAlertLog::create([
                        'student_id' => $student->id,
                        'session_id' => $record->session_id,
                        'attendance_date' => $targetDate,
                        'channel' => 'in_app',
                        'recipient' => (string) $student->parent_id,
                        'status' => 'delivered',
                        'message_payload' => $alertMessage,
                    ]);

                    $stats['in_app_sent']++;
                } else {
                    $stats['already_notified']++;
                }
            }

            // 2. Telegram Alert Broadcast (if configured)
            if (TelegramService::getBotToken() && TelegramService::getDefaultChatId()) {
                $alreadyTelegram = AttendanceAlertLog::where('student_id', $student->id)
                    ->whereDate('attendance_date', $targetDate)
                    ->where('channel', 'telegram')
                    ->exists();

                if (! $alreadyTelegram) {
                    try {
                        TelegramService::sendMessage("🚨 <b>Absence Notice</b>\nStudent: {$student->name} ({$className})\nDate: {$targetDate}\nStatus: Unexcused Absence\nSchool: {$schoolName}");

                        AttendanceAlertLog::create([
                            'student_id' => $student->id,
                            'session_id' => $record->session_id,
                            'attendance_date' => $targetDate,
                            'channel' => 'telegram',
                            'recipient' => TelegramService::getDefaultChatId(),
                            'status' => 'delivered',
                            'message_payload' => $alertMessage,
                        ]);

                        $stats['telegram_sent']++;
                    } catch (\Throwable $e) {
                        Log::error("Telegram absentee alert failed for {$student->id}: ".$e->getMessage());
                        $stats['failed']++;
                    }
                }
            }
        }

        return $stats;
    }
}
