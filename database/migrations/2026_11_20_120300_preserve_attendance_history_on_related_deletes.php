<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_sessions', function (Blueprint $table) {
            $table->dropForeign(['class_id']);
            $table->dropForeign(['course_id']);
            $table->dropForeign(['teacher_id']);
            $table->foreign('class_id')->references('id')->on('academic_classes')->restrictOnDelete();
            $table->foreign('course_id')->references('id')->on('courses')->nullOnDelete();
            $table->foreignId('teacher_id')->nullable()->change();
            $table->foreign('teacher_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('attendance_records', function (Blueprint $table) {
            $table->dropForeign(['session_id']);
            $table->dropForeign(['student_id']);
            $table->foreign('session_id')->references('id')->on('attendance_sessions')->restrictOnDelete();
            $table->foreign('student_id')->references('id')->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->dropForeign(['session_id']);
            $table->dropForeign(['student_id']);
            $table->foreign('session_id')->references('id')->on('attendance_sessions')->cascadeOnDelete();
            $table->foreign('student_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('attendance_sessions', function (Blueprint $table) {
            $table->dropForeign(['class_id']);
            $table->dropForeign(['course_id']);
            $table->dropForeign(['teacher_id']);
            $table->foreign('class_id')->references('id')->on('academic_classes')->cascadeOnDelete();
            $table->foreign('course_id')->references('id')->on('courses')->cascadeOnDelete();
            $table->foreign('teacher_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
