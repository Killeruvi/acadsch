<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Live Interactive Quizzes & Arena
        Schema::create('live_quizzes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->nullable()->constrained('academic_classes')->cascadeOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained('academic_subjects')->cascadeOnDelete();
            $table->foreignId('creator_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('access_code', 10)->unique();
            $table->integer('time_per_question')->default(30);
            $table->integer('xp_reward')->default(100);
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('live_quiz_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained('live_quizzes')->cascadeOnDelete();
            $table->text('question');
            $table->string('option_a');
            $table->string('option_b');
            $table->string('option_c');
            $table->string('option_d');
            $table->string('correct_option', 2);
            $table->text('explanation')->nullable();
            $table->integer('points')->default(10);
            $table->timestamps();
        });

        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained('live_quizzes')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->integer('score')->default(0);
            $table->integer('total_questions')->default(0);
            $table->integer('correct_answers')->default(0);
            $table->integer('xp_earned')->default(0);
            $table->json('answers_payload')->nullable();
            $table->timestamps();
        });

        // 2. Case Studies & Capstone Projects (Grouped & Ungrouped)
        Schema::create('academic_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('academic_classes')->cascadeOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained('academic_subjects')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('type')->default('project'); // project, case_study
            $table->string('group_mode')->default('individual'); // individual (ungrouped), grouped
            $table->integer('max_team_size')->default(1);
            $table->text('description');
            $table->text('problem_statement')->nullable();
            $table->text('evaluation_rubric')->nullable();
            $table->date('due_date');
            $table->integer('max_marks')->default(100);
            $table->timestamps();
        });

        Schema::create('project_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('academic_projects')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->string('team_name')->nullable();
            $table->json('team_members')->nullable();
            $table->text('solution_summary');
            $table->string('deliverable_url')->nullable();
            $table->integer('score')->nullable();
            $table->text('teacher_feedback')->nullable();
            $table->string('status')->default('submitted');
            $table->timestamp('submitted_at')->useCurrent();
            $table->timestamps();
        });

        // 3. Debate Organisations, Talent Shows & Competitions
        Schema::create('academy_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coordinator_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('category'); // debate, talent_show, science_exhibition, hackathon, literary
            $table->string('event_format')->default('individual'); // individual, parliamentary_team, group
            $table->text('description');
            $table->text('rules_and_rubric')->nullable();
            $table->dateTime('event_datetime');
            $table->string('venue')->default('Main Auditorium & Virtual Stream');
            $table->string('status')->default('upcoming'); // upcoming, live, completed
            $table->json('registered_participants')->nullable();
            $table->json('podium_winners')->nullable();
            $table->timestamps();
        });

        // 4. Chapterwise Flashcards System
        Schema::create('chapter_flashcards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('academic_classes')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('academic_subjects')->cascadeOnDelete();
            $table->foreignId('topic_id')->nullable()->constrained('curriculum_topics')->cascadeOnDelete();
            $table->string('chapter_name');
            $table->text('question');
            $table->text('answer');
            $table->text('hint')->nullable();
            $table->string('difficulty')->default('medium');
            $table->timestamps();
        });

        Schema::create('user_flashcard_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('flashcard_id')->constrained('chapter_flashcards')->cascadeOnDelete();
            $table->string('mastery_status')->default('learning'); // learning, mastered, needs_review
            $table->integer('review_count')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'flashcard_id']);
        });

        // 5. Official Certificates & Digital Achievement Badges
        Schema::create('user_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('issuer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('certificate_number')->unique();
            $table->string('title');
            $table->string('category');
            $table->text('citation');
            $table->date('issued_date');
            $table->string('verification_hash')->unique();
            $table->timestamps();
        });

        Schema::create('user_badges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('badge_key');
            $table->string('title');
            $table->string('icon');
            $table->string('category');
            $table->text('description');
            $table->timestamp('awarded_at')->useCurrent();
            $table->timestamps();

            $table->unique(['user_id', 'badge_key']);
        });

        // 6. Offline Downloadable Content Repository
        Schema::create('offline_downloads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->nullable()->constrained('academic_classes')->cascadeOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained('academic_subjects')->cascadeOnDelete();
            $table->string('title');
            $table->string('content_type')->default('study_pack');
            $table->string('file_size_label')->default('1.5 MB');
            $table->text('description')->nullable();
            $table->string('download_url');
            $table->json('offline_cache_payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offline_downloads');
        Schema::dropIfExists('user_badges');
        Schema::dropIfExists('user_certificates');
        Schema::dropIfExists('user_flashcard_progress');
        Schema::dropIfExists('chapter_flashcards');
        Schema::dropIfExists('academy_events');
        Schema::dropIfExists('project_submissions');
        Schema::dropIfExists('academic_projects');
        Schema::dropIfExists('quiz_attempts');
        Schema::dropIfExists('live_quiz_questions');
        Schema::dropIfExists('live_quizzes');
    }
};
