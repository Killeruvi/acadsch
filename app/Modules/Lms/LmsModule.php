<?php

namespace App\Modules\Lms;

use App\Models\Course;
use App\Models\CurriculumVideo;
use App\Models\DailyDpp;
use App\Models\DppQuestion;
use App\Models\DppSubmission;
use App\Models\HomeworkSubmission;
use App\Models\HomeworkTask;
use App\Models\LiveQuiz;
use App\Models\ProjectSubmission;
use App\Models\QuizAttempt;
use App\Models\TopicBloomActivity;
use App\Models\UserBadge;
use App\Models\UserCertificate;

/**
 * LMS Module Boundary Definition
 *
 * The Learning Management System (LMS) domain encapsulates:
 * - Digital courses, modules, and video curriculum lessons.
 * - Daily Practice Problems (DPP) and automated quiz attempts.
 * - Homework tasks, student project submissions, and code/case studies.
 * - Gamification (streaks, badges, leaderboard, XP points).
 * - Bloom's Taxonomy learning outcome mapping and student handouts.
 *
 * All school administrative operations (enrollments, class timetable,
 * session rollover, fees ledger, staff payroll) belong strictly to SchoolErpModule.
 */
final class LmsModule
{
    public const NAME = 'Learning Management System (LMS)';

    public const VERSION = '2.0.0';

    /**
     * Core models owned by LMS domain.
     */
    public static function models(): array
    {
        return [
            Course::class,
            DailyDpp::class,
            DppQuestion::class,
            DppSubmission::class,
            HomeworkTask::class,
            HomeworkSubmission::class,
            LiveQuiz::class,
            QuizAttempt::class,
            ProjectSubmission::class,
            CurriculumVideo::class,
            TopicBloomActivity::class,
            UserBadge::class,
            UserCertificate::class,
        ];
    }
}
