<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->index(['teacher_id', 'due_date'], 'assignments_teacher_due_idx');
            $table->index(['course_id', 'due_date'], 'assignments_course_due_idx');
            $table->index(['target_type', 'student_id', 'due_date'], 'assignments_target_student_due_idx');
        });

        Schema::table('submissions', function (Blueprint $table) {
            $table->index(['student_id', 'status', 'submitted_at'], 'submissions_student_status_idx');
            $table->index(['assignment_id', 'status', 'submitted_at'], 'submissions_assignment_status_idx');
        });

        Schema::table('homework_tasks', function (Blueprint $table) {
            $table->index(['class_id', 'due_date'], 'homework_class_due_idx');
            $table->index(['subject_id', 'assigned_date'], 'homework_subject_assigned_idx');
            $table->index(['teacher_id', 'assigned_date'], 'homework_teacher_assigned_idx');
        });

        Schema::table('homework_submissions', function (Blueprint $table) {
            $table->index(['student_id', 'status', 'updated_at'], 'homework_student_status_idx');
            $table->index(['homework_id', 'status', 'updated_at'], 'homework_task_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('homework_submissions', function (Blueprint $table) {
            $table->dropIndex('homework_student_status_idx');
            $table->dropIndex('homework_task_status_idx');
        });
        Schema::table('homework_tasks', function (Blueprint $table) {
            $table->dropIndex('homework_class_due_idx');
            $table->dropIndex('homework_subject_assigned_idx');
            $table->dropIndex('homework_teacher_assigned_idx');
        });
        Schema::table('submissions', function (Blueprint $table) {
            $table->dropIndex('submissions_student_status_idx');
            $table->dropIndex('submissions_assignment_status_idx');
        });
        Schema::table('assignments', function (Blueprint $table) {
            $table->dropIndex('assignments_teacher_due_idx');
            $table->dropIndex('assignments_course_due_idx');
            $table->dropIndex('assignments_target_student_due_idx');
        });
    }
};
