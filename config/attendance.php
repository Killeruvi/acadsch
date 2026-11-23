<?php

return [
    'minimum_percentage' => (float) env('ATTENDANCE_MINIMUM_PERCENTAGE', 75),
    'warning_percentage' => (float) env('ATTENDANCE_WARNING_PERCENTAGE', 85),
    'minimum_sessions_for_alert' => (int) env('ATTENDANCE_MINIMUM_SESSIONS_FOR_ALERT', 10),
    'period_grace_minutes' => (int) env('ATTENDANCE_PERIOD_GRACE_MINUTES', 5),
    'weekend_days' => array_values(array_filter(array_map('trim', explode(',', env('ATTENDANCE_WEEKEND_DAYS', 'Sunday'))))),
    'auto_absent_time' => env('ATTENDANCE_AUTO_ABSENT_TIME', '23:55'),
];
