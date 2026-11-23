<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// 1. Automated Recurring Monthly Fee Generation & Calculations (Daily at 00:05, auto charges 1st of month & unbilled scholars)
Schedule::command('fees:generate-monthly')
    ->dailyAt('00:05')
    ->withoutOverlapping()
    ->runInBackground();

// 3. Faculty Absence & Timetable Proxy Reassignment Suggestion (Mon-Sat at 08:30)
Schedule::command('timetable:auto-substitute --notify')
    ->days([1, 2, 3, 4, 5, 6])
    ->at('08:30')
    ->withoutOverlapping()
    ->runInBackground();

// 4. Missing Roll Call Alert & Morning Absentee Parent Dispatch (Mon-Sat at 09:30)
Schedule::command('attendance:auto-alert')
    ->days([1, 2, 3, 4, 5, 6])
    ->at('09:30')
    ->withoutOverlapping()
    ->runInBackground();

// 5. Weekly Low-Attendance Defaulter Warning Audit (Every Friday at 16:00)
Schedule::command('attendance:audit-shortage')
    ->weeklyOn(5, '16:00')
    ->withoutOverlapping()
    ->runInBackground();

// 6. End-of-Day Principal & Admin Operational Digest (Mon-Sat at 17:00)
Schedule::command('school:daily-digest')
    ->days([1, 2, 3, 4, 5, 6])
    ->at('17:00')
    ->withoutOverlapping()
    ->runInBackground();

// 7. Automated Auto-Absent on Day Change (Daily at 23:55, skipping holidays, auto-excusing approved leaves - Rule 14)
Schedule::command('attendance:auto-absent')
    ->dailyAt(config('attendance.auto_absent_time', '23:55'))
    ->withoutOverlapping()
    ->runInBackground();

// Approved staff leave is recorded on the actual working day, not in advance.
Schedule::command('staff:sync-approved-leave')
    ->dailyAt('23:45')
    ->withoutOverlapping()
    ->runInBackground();

// Idempotent monthly staff leave accrual from explicitly enabled HR policies.
Schedule::command('staff:accrue-leave')->monthlyOn(1, '00:20')->withoutOverlapping();
