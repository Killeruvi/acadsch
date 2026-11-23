<?php

return [
    'max_active_issues' => (int) env('LIBRARY_MAX_ACTIVE_ISSUES', 5),
    'max_renewals' => (int) env('LIBRARY_MAX_RENEWALS', 2),
];
