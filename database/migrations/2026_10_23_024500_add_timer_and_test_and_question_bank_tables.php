<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add timer and test fields to daily_dpps
        Schema::table('daily_dpps', function (Blueprint $table) {
            $table->string('type', 20)->default('dpp')->after('dpp_number'); // dpp, test
            $table->integer('duration_minutes')->default(15)->after('total_marks'); // 0 for untimed, >0 for timer countdown
            $table->boolean('is_timed')->default(true)->after('duration_minutes');
            $table->integer('pass_percentage')->default(40)->after('is_timed');
        });

        // 2. Add time tracking and passed status to dpp_submissions
        Schema::table('dpp_submissions', function (Blueprint $table) {
            $table->integer('time_taken_seconds')->nullable()->after('xp_earned');
            $table->boolean('is_passed')->default(true)->after('time_taken_seconds');
        });

        // 3. Central Institutional Question Bank
        Schema::create('question_bank', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('academic_classes')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('academic_subjects')->onDelete('cascade');
            $table->foreignId('topic_id')->nullable()->constrained('curriculum_topics')->onDelete('set null');
            $table->foreignId('teacher_id')->nullable()->constrained('users')->onDelete('set null');
            $table->text('question_text');
            $table->string('option_a');
            $table->string('option_b');
            $table->string('option_c');
            $table->string('option_d');
            $table->string('correct_option', 2); // A, B, C, D
            $table->text('explanation');
            $table->string('difficulty', 20)->default('standard'); // standard, advanced, olympiad
            $table->integer('marks')->default(4);
            $table->string('tags')->nullable(); // e.g. "Biology, Light Reaction, NEET"
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('question_bank');

        Schema::table('dpp_submissions', function (Blueprint $table) {
            $table->dropColumn(['time_taken_seconds', 'is_passed']);
        });

        Schema::table('daily_dpps', function (Blueprint $table) {
            $table->dropColumn(['type', 'duration_minutes', 'is_timed', 'pass_percentage']);
        });
    }
};
