<?php

return [
    // Keep this aligned with the attendance weekend policy unless explicitly overridden.
    'weekend_days' => array_values(array_filter(array_map('trim', explode(',', env('ACADEMIC_CALENDAR_WEEKEND_DAYS', env('ATTENDANCE_WEEKEND_DAYS', 'Sunday')))))),
];
