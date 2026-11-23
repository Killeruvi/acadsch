<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $indexes = [
        'daily_dpps' => [
            'dpp_teacher_class_date_idx' => ['teacher_id', 'class_id', 'assigned_date'],
        ],
        'dpp_submissions' => [
            'dpp_submission_student_dpp_idx' => ['student_id', 'dpp_id'],
        ],
        'homework_tasks' => [
            'homework_teacher_class_due_idx' => ['teacher_id', 'class_id', 'due_date'],
        ],
        'homework_submissions' => [
            'homework_submission_student_task_status_idx' => ['student_id', 'homework_id', 'status'],
        ],
        'attendance_sessions' => [
            'attendance_class_date_idx' => ['class_id', 'session_date'],
            'attendance_teacher_date_idx' => ['teacher_id', 'session_date'],
        ],
        'attendance_records' => [
            'attendance_student_status_session_idx' => ['student_id', 'status', 'session_id'],
        ],
        'class_enrollments' => [
            'class_enrollment_class_status_student_idx' => ['class_id', 'status', 'student_id'],
        ],
        'school_report_cards' => [
            'report_card_published_class_student_idx' => ['is_published', 'class_id', 'student_id'],
        ],
        'bloom_assignments' => [
            'bloom_student_status_lesson_idx' => ['student_id', 'status', 'lesson_id'],
        ],
    ];

    public function up(): void
    {
        foreach ($this->indexes as $tableName => $indexes) {
            if (!Schema::hasTable($tableName)) continue;
            foreach ($indexes as $indexName => $columns) {
                $available = true;
                foreach ($columns as $column) {
                    if (!Schema::hasColumn($tableName, $column)) {
                        $available = false;
                        break;
                    }
                }
                if ($available) {
                    Schema::table($tableName, function (Blueprint $table) use ($columns, $indexName) {
                        $table->index($columns, $indexName);
                    });
                }
            }
        }
    }

    public function down(): void
    {
        foreach ($this->indexes as $tableName => $indexes) {
            if (!Schema::hasTable($tableName)) continue;
            foreach (array_keys($indexes) as $indexName) {
                Schema::table($tableName, function (Blueprint $table) use ($indexName) {
                    $table->dropIndex($indexName);
                });
            }
        }
    }
};
