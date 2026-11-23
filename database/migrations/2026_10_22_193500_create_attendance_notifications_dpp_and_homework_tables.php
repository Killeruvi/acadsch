<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Attendance Sessions & Records
        Schema::create('attendance_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->nullable()->constrained('academic_classes')->onDelete('cascade');
            $table->foreignId('course_id')->nullable()->constrained('courses')->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade');
            $table->date('session_date');
            $table->string('title')->default('Daily Attendance');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('attendance_sessions')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->string('status')->default('present'); // present, absent, late, excused
            $table->string('remarks')->nullable();
            $table->timestamps();

            $table->unique(['session_id', 'student_id']);
        });

        // 2. In-App Notification System
        Schema::create('in_app_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('type')->default('general'); // assignment, bloom, fee, dpp, homework, attendance, general
            $table->string('title');
            $table->text('message');
            $table->string('action_url')->nullable();
            $table->string('icon')->default('🔔');
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        // 3. Daily Practice Problems (DPP) with Automated Same-Day Checking
        Schema::create('daily_dpps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('academic_classes')->onDelete('cascade');
            $table->foreignId('subject_id')->nullable()->constrained('academic_subjects')->onDelete('cascade');
            $table->foreignId('topic_id')->nullable()->constrained('curriculum_topics')->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade');
            $table->string('dpp_number'); // e.g. DPP-08-01
            $table->string('title');
            $table->date('assigned_date');
            $table->string('due_time')->default('23:59');
            $table->integer('total_marks')->default(20);
            $table->string('difficulty')->default('standard'); // standard, advanced, olympiad
            $table->timestamps();
        });

        Schema::create('dpp_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dpp_id')->constrained('daily_dpps')->onDelete('cascade');
            $table->integer('question_number');
            $table->text('question_text');
            $table->string('option_a');
            $table->string('option_b');
            $table->string('option_c');
            $table->string('option_d');
            $table->string('correct_option', 2); // A, B, C, D
            $table->text('explanation');
            $table->integer('marks')->default(4);
            $table->timestamps();
        });

        Schema::create('dpp_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dpp_id')->constrained('daily_dpps')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->json('answers'); // {"1": "A", "2": "C"}
            $table->integer('score')->default(0);
            $table->integer('max_score')->default(20);
            $table->decimal('percentage', 5, 2)->default(0.00);
            $table->integer('xp_earned')->default(30);
            $table->timestamp('checked_at');
            $table->string('status')->default('evaluated');
            $table->timestamps();

            $table->unique(['dpp_id', 'student_id']);
        });

        // 4. Daily Homework System
        Schema::create('homework_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('academic_classes')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('academic_subjects')->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->text('instructions');
            $table->date('assigned_date');
            $table->date('due_date');
            $table->timestamps();
        });

        Schema::create('homework_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('homework_id')->constrained('homework_tasks')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->text('student_notes');
            $table->string('status')->default('submitted'); // submitted, checked, incomplete, missing
            $table->text('teacher_feedback')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();

            $table->unique(['homework_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('homework_submissions');
        Schema::dropIfExists('homework_tasks');
        Schema::dropIfExists('dpp_submissions');
        Schema::dropIfExists('dpp_questions');
        Schema::dropIfExists('daily_dpps');
        Schema::dropIfExists('in_app_notifications');
        Schema::dropIfExists('attendance_records');
        Schema::dropIfExists('attendance_sessions');
    }
};
