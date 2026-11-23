<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_leave_requests', function (Blueprint $table) {
            $table->index(['student_id', 'status', 'start_date', 'end_date'], 'leave_student_status_dates_idx');
            $table->index(['reviewed_by', 'status'], 'leave_reviewer_status_idx');
        });

        // Retain leave history when a student account is offboarded; use archival/soft-delete workflows.
        Schema::table('student_leave_requests', function (Blueprint $table) {
            try { $table->dropForeign(['student_leave_requests_student_id_foreign']); } catch (\Throwable $_) {}
            $table->foreign('student_id', 'student_leave_requests_student_id_foreign')
                ->references('id')->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('student_leave_requests', function (Blueprint $table) {
            try { $table->dropForeign(['student_leave_requests_student_id_foreign']); } catch (\Throwable $_) {}
            $table->foreign('student_id', 'student_leave_requests_student_id_foreign')
                ->references('id')->on('users')->cascadeOnDelete();
            $table->dropIndex('leave_student_status_dates_idx');
            $table->dropIndex('leave_reviewer_status_idx');
        });
    }
};
