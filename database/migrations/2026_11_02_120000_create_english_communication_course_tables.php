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
        Schema::create('course_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->unsignedInteger('week_number');
            $table->string('title');
            $table->string('theme')->nullable();
            $table->text('description')->nullable();
            $table->json('learning_goals')->nullable();
            $table->unsignedInteger('order_index')->default(1);
            $table->timestamps();

            $table->unique(['course_id', 'week_number']);
        });

        Schema::create('course_lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignId('module_id')->constrained('course_modules')->cascadeOnDelete();
            $table->unsignedInteger('lesson_number');
            $table->unsignedInteger('week_number');
            $table->unsignedInteger('day_of_week'); // 1 to 5
            $table->string('title');
            $table->unsignedInteger('duration_minutes')->default(60);
            $table->string('level')->default('standard'); // 'foundation', 'standard', 'challenge'
            $table->text('topic_summary')->nullable();
            $table->json('learning_objectives')->nullable();
            $table->text('expected_outcomes')->nullable();
            $table->json('vocabulary')->nullable();
            $table->json('concepts')->nullable();
            $table->json('teaching_materials')->nullable();
            $table->json('time_allocation')->nullable();
            $table->json('procedure_steps')->nullable();
            $table->json('speaking_activity')->nullable();
            $table->json('listening_activity')->nullable();
            $table->json('reading_activity')->nullable();
            $table->json('writing_activity')->nullable();
            $table->text('real_life_scenario')->nullable();
            $table->json('common_mistakes')->nullable();
            $table->text('homework_task')->nullable();
            $table->text('evaluation_criteria')->nullable();
            $table->json('teacher_script')->nullable();
            $table->json('worksheet')->nullable();
            $table->json('answer_key')->nullable(); // Teacher-only answer key
            $table->unsignedInteger('order_index')->default(1);
            $table->timestamps();

            $table->unique(['course_id', 'lesson_number']);
            $table->index(['course_id', 'week_number']);
        });

        Schema::create('course_lesson_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignId('lesson_id')->constrained('course_lessons')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('is_completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamps();

            $table->unique(['lesson_id', 'student_id']);
            $table->index(['course_id', 'student_id']);
        });

        Schema::create('course_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignId('module_id')->nullable()->constrained('course_modules')->nullOnDelete();
            $table->string('type')->default('weekly'); // 'diagnostic', 'weekly', 'monthly', 'speaking', 'final'
            $table->string('title');
            $table->unsignedInteger('week_number')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('total_points')->default(100);
            $table->unsignedInteger('passing_score')->default(60);
            $table->unsignedInteger('time_limit_minutes')->default(30);
            $table->json('rubric')->nullable();
            $table->json('marking_scheme')->nullable();
            $table->json('questions')->nullable();
            $table->boolean('is_published')->default(true);
            $table->timestamps();

            $table->index(['course_id', 'type']);
        });

        Schema::create('course_assessment_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained('course_assessments')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('score', 5, 2)->nullable();
            $table->decimal('percentage', 5, 2)->nullable();
            $table->string('grade_letter', 5)->nullable();
            $table->text('feedback')->nullable();
            $table->string('speaking_audio_url')->nullable();
            $table->json('answers')->nullable();
            $table->string('status')->default('completed'); // 'submitted', 'evaluated'
            $table->foreignId('teacher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('evaluated_at')->nullable();
            $table->timestamps();

            $table->unique(['assessment_id', 'student_id']);
        });

        Schema::create('course_homework_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained('course_lessons')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->text('submission_text')->nullable();
            $table->string('audio_url')->nullable();
            $table->string('status')->default('submitted'); // 'submitted', 'reviewed'
            $table->string('grade')->nullable();
            $table->text('feedback')->nullable();
            $table->foreignId('evaluated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('evaluated_at')->nullable();
            $table->timestamps();

            $table->unique(['lesson_id', 'student_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_homework_submissions');
        Schema::dropIfExists('course_assessment_submissions');
        Schema::dropIfExists('course_assessments');
        Schema::dropIfExists('course_lesson_progress');
        Schema::dropIfExists('course_lessons');
        Schema::dropIfExists('course_modules');
    }
};
