<?php

namespace App\Services;

use App\Models\AcademicClass;
use App\Models\Course;
use App\Models\InAppNotification;

class NotificationService
{
    /**
     * Send a notification to a specific user (deduplicated against recent identical notifications).
     */
    public static function notifyUser(
        int $userId,
        string $type,
        string $title,
        string $message,
        ?string $actionUrl = null,
        string $icon = '🔔'
    ): ?InAppNotification {
        try {
            // Deduplicate: avoid sending duplicate notifications within 10 minutes
            $recent = InAppNotification::where('user_id', $userId)
                ->where('type', $type)
                ->where('title', $title)
                ->where('created_at', '>=', now()->subMinutes(10))
                ->first();

            if ($recent) {
                return $recent;
            }

            return InAppNotification::create([
                'user_id' => $userId,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'action_url' => self::safeInternalActionUrl($actionUrl),
                'icon' => $icon,
                'is_read' => false,
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Notification dispatch failure', [
                'user_id' => $userId,
                'type' => $type,
                'title' => $title,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Send bulk notifications to multiple users (deduplicated).
     */
    public static function notifyUsers(
        array $userIds,
        string $type,
        string $title,
        string $message,
        ?string $actionUrl = null,
        string $icon = '🔔'
    ): void {
        try {
            $recipientIds = array_values(array_unique(array_filter(
                array_map(static fn ($id) => filter_var($id, FILTER_VALIDATE_INT), $userIds),
                static fn ($id) => $id !== false && $id > 0
            )));

            if ($recipientIds === []) {
                return;
            }

            // Exclude users who already received this exact notification within 10 minutes
            $alreadyNotified = InAppNotification::whereIn('user_id', $recipientIds)
                ->where('type', $type)
                ->where('title', $title)
                ->where('created_at', '>=', now()->subMinutes(10))
                ->pluck('user_id')
                ->toArray();

            $pendingRecipientIds = array_diff($recipientIds, $alreadyNotified);
            if (empty($pendingRecipientIds)) {
                return;
            }

            $now = now();
            // Chunk inserts to avoid oversized SQL statements for school-wide broadcasts.
            foreach (array_chunk($pendingRecipientIds, 500) as $chunk) {
                $records = [];
                foreach ($chunk as $uid) {
                    $records[] = [
                        'user_id' => $uid,
                        'type' => $type,
                        'title' => $title,
                        'message' => $message,
                        'action_url' => self::safeInternalActionUrl($actionUrl),
                        'icon' => $icon,
                        'is_read' => false,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
                InAppNotification::insert($records);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Bulk notification dispatch failure', [
                'count' => count($userIds),
                'type' => $type,
                'title' => $title,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify all students enrolled in an Academic Class.
     */
    public static function notifyClass(
        int $classId,
        string $type,
        string $title,
        string $message,
        ?string $actionUrl = null,
        string $icon = '🔔'
    ): void {
        $class = AcademicClass::find($classId);
        if (!$class) return;

        $studentIds = $class->students()->pluck('users.id')->toArray();
        self::notifyUsers($studentIds, $type, $title, $message, $actionUrl, $icon);
    }

    /**
     * Notify all students enrolled in a Course.
     */
    public static function notifyCourseStudents(
        int $courseId,
        string $type,
        string $title,
        string $message,
        ?string $actionUrl = null,
        string $icon = '🔔'
    ): void {
        $course = Course::find($courseId);
        if (!$course) return;

        $studentIds = $course->enrollments()->pluck('student_id')->toArray();
        self::notifyUsers($studentIds, $type, $title, $message, $actionUrl, $icon);
    }

    /**
     * Broadcast an institutional notice to target user groups.
     */
    public static function broadcastNotice(
        string $title,
        string $message,
        ?string $actionUrl = null,
        string $icon = '📢',
        string $targetAudience = 'all'
    ): void {
        $query = \App\Models\User::query();
        if ($targetAudience === 'students') {
            $query->where('role', 'student');
        } elseif ($targetAudience === 'teachers') {
            $query->where('role', 'teacher');
        } elseif ($targetAudience === 'parents') {
            $query->where('role', 'parent');
        } elseif ($targetAudience === 'all') {
            // Explicitly all recognized account roles, not accidental future/system roles.
            $query->whereIn('role', ['admin', 'teacher', 'student', 'parent']);
        } else {
            return;
        }

        $userIds = $query->pluck('id')->toArray();
        self::notifyUsers($userIds, 'notice', $title, $message, $actionUrl, $icon);

        // Mirror broadcast to Telegram channel / admin
        try {
            TelegramService::sendAlert("📢 " . $title, $message);
        } catch (\Throwable $e) {
            // Silently continue if Telegram network is unreachable
        }
    }

    /**
     * Get unread notifications count for a user.
     */
    public static function getUnreadCount(int $userId): int
    {
        return InAppNotification::where('user_id', $userId)->where('is_read', false)->count();
    }

    private static function safeInternalActionUrl(?string $url): ?string
    {
        if ($url === null || trim($url) === '') {
            return null;
        }

        $url = trim($url);
        if (!str_starts_with($url, '/') || str_starts_with($url, '//') || str_contains($url, '\\')) {
            return null;
        }

        $parts = parse_url($url);
        if ($parts === false || isset($parts['scheme']) || isset($parts['host']) || isset($parts['user']) || isset($parts['pass'])) {
            return null;
        }

        return $url;
    }

}
