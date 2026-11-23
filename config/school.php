<?php

return [
    'installer_key' => env('SCHOOL_INSTALLER_KEY', ''),
    'default_academic_session' => env('SCHOOL_DEFAULT_ACADEMIC_SESSION'),
    'institution_name' => env('SCHOOL_INSTITUTION_NAME'),
    'campus_name' => env('SCHOOL_CAMPUS_NAME'),
    'currency_symbol' => env('SCHOOL_CURRENCY_SYMBOL', '₹'),
    'grade_levels' => range(1, 12),
    'capacity' => [
        'minimum' => 1,
        'maximum' => 150,
    ],
    'periods' => [
        'maximum_number' => 20,
    ],
    'onboarding' => [
        'default_stream' => env('SCHOOL_ONBOARDING_DEFAULT_STREAM', 'General'),
        'default_section' => env('SCHOOL_ONBOARDING_DEFAULT_SECTION', 'A'),
    ],
    'promotion' => [
        // Existing promotion UI used 75% as its attendance eligibility threshold.
        'minimum_attendance_percentage' => (float) env('SCHOOL_PROMOTION_MIN_ATTENDANCE_PERCENTAGE', 75),
    ],
];
