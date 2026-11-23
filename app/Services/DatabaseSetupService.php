<?php

namespace App\Services;

use App\Models\AcademicClass;
use App\Models\AcademicSubject;
use App\Models\AcademySetting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class DatabaseSetupService
{
    /**
     * Get database connection status and data footprint.
     */
    public function getStatus(): array
    {
        $connected = false;
        $driver = config('database.default');
        $database = config("database.connections.{$driver}.database");
        $tableCount = 0;

        try {
            DB::connection()->getPdo();
            $connected = true;

            if ($driver === 'sqlite') {
                $tables = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
                $tableCount = count($tables);
            } else {
                $tables = DB::select('SHOW TABLES');
                $tableCount = count($tables);
            }
        } catch (\Throwable $e) {
            $connected = false;
        }

        $totalClasses = $connected && Schema::hasTable('academic_classes') ? AcademicClass::count() : 0;
        $totalSubjects = $connected && Schema::hasTable('academic_subjects') ? AcademicSubject::count() : 0;
        $totalStudents = $connected && Schema::hasTable('users') ? User::where('role', 'student')->count() : 0;
        $totalTeachers = $connected && Schema::hasTable('users') ? User::where('role', 'teacher')->count() : 0;
        $totalAdmins = $connected && Schema::hasTable('users') ? User::where('role', 'admin')->count() : 0;

        $isClean = ($totalClasses === 0 && $totalSubjects === 0 && $totalStudents === 0);

        return [
            'connected' => $connected,
            'driver' => $driver,
            'database' => $database,
            'table_count' => $tableCount,
            'is_clean' => $isClean,
            'total_classes' => $totalClasses,
            'total_subjects' => $totalSubjects,
            'total_students' => $totalStudents,
            'total_teachers' => $totalTeachers,
            'total_admins' => $totalAdmins,
        ];
    }

    /**
     * Wipe all operational and test records with option to retain or remove administrator.
     */
    public function wipeAndInitializeCleanSlate(?string $adminEmail = 'principal@primearc.edu.in', ?string $adminPassword = 'password123', bool $retainAdmin = true): array
    {
        $tablesToClear = [
            'class_enrollments',
            'teacher_assignment_audits',
            'school_period_timetables',
            'school_exam_marks',
            'school_exam_schedules',
            'school_exam_terms',
            'school_report_cards',
            'school_fee_structures',
            'fee_payments',
            'fee_invoices',
            'attendance_records',
            'attendance_sessions',
            'attendance_rejection_logs',
            'student_leave_requests',
            'daily_dpps',
            'dpp_submissions',
            'dpp_questions',
            'homework_submissions',
            'homework_tasks',
            'bloom_assignments',
            'topic_bloom_activities',
            'curriculum_videos',
            'curriculum_topics',
            'curriculum_notes',
            'daily_lessons',
            'academy_notices',
            'academic_subjects',
            'academic_classes',
            'course_lesson_progress',
            'course_lesson_videos',
            'course_lessons',
            'course_modules',
            'course_assessments',
            'course_assessment_submissions',
            'course_homework_submissions',
            'courses',
            'enrollments',
            'submissions',
            'assignments',
            'batch_change_requests',
            'batches',
            'schedules',
            'live_quiz_questions',
            'quiz_attempts',
            'live_quizzes',
            'project_submissions',
            'academic_projects',
            'academy_events',
            'lesson_plans',
        ];

        DB::beginTransaction();
        try {
            // Disable foreign keys temporarily
            if (config('database.default') === 'sqlite') {
                DB::statement('PRAGMA foreign_keys = OFF;');
            } else {
                DB::statement('SET FOREIGN_KEY_CHECKS = 0;');
            }

            foreach ($tablesToClear as $table) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->delete();
                }
            }

            // Remove users
            if (Schema::hasTable('users')) {
                if (!$retainAdmin) {
                    User::query()->delete();
                } else {
                    User::where('email', '!=', $adminEmail)->delete();

                    $admin = User::where('email', $adminEmail)->first();
                    if ($admin) {
                        $updateData = [
                            'role' => 'admin',
                            'is_approved' => true,
                            'current_class_id' => null,
                            'batch_id' => null,
                        ];
                        if (!empty($adminPassword)) {
                            $updateData['password'] = Hash::make($adminPassword);
                        }
                        $admin->update($updateData);
                    } else {
                        User::create([
                            'name' => 'Administrator',
                            'email' => $adminEmail,
                            'password' => Hash::make($adminPassword ?? 'AdminSecure2026!'),
                            'role' => 'admin',
                            'is_approved' => true,
                        ]);
                    }
                }
            }

            // Reset Academy Settings to fresh default state
            if (Schema::hasTable('academy_settings')) {
                AcademySetting::whereIn('key', [
                    'onboarding_completed',
                    'onboarding_completed_at',
                    'onboarding_current_step',
                    'onboarding_step_1_done',
                    'onboarding_step_2_done',
                    'onboarding_step_3_done',
                    'onboarding_step_4_done',
                    'onboarding_step_5_done',
                ])->delete();

                if ($retainAdmin) {
                    foreach ([
                        'institute_name' => config('school.institution_name'),
                        'campus_name' => config('school.campus_name'),
                        'academic_session' => config('school.default_academic_session'),
                        'currency_symbol' => config('school.currency_symbol'),
                    ] as $settingKey => $settingValue) {
                        if ($settingValue !== null && $settingValue !== '') {
                            AcademySetting::set($settingKey, $settingValue);
                        } else {
                            AcademySetting::where('key', $settingKey)->delete();
                        }
                    }
                } else {
                    AcademySetting::whereIn('key', [
                        'institute_name',
                        'campus_name',
                        'academic_session',
                        'currency_symbol',
                        'principal_name',
                        'institution_phone',
                        'institution_email',
                        'institution_address',
                    ])->delete();
                }
            }

            // Re-enable foreign keys
            if (config('database.default') === 'sqlite') {
                DB::statement('PRAGMA foreign_keys = ON;');
            } else {
                DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
            }

            DB::commit();

            return [
                'success' => true,
                'message' => $retainAdmin 
                    ? 'Database successfully wiped to a clean slate. Retained 1 master administrator account.'
                    : 'Database completely wiped to absolute zero users. Ready for initial master administrator setup.',
                'admin_email' => $retainAdmin ? $adminEmail : null,
                'retained_admin' => $retainAdmin,
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Clean slate initialization failed: ' . $e->getMessage(),
            ];
        }
    }
}
