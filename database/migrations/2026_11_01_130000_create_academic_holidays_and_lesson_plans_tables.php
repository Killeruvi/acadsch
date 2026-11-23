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
        Schema::create('academic_holidays', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('holiday_date');
            $table->date('end_date')->nullable();
            $table->string('type')->default('festival'); // national, festival, vacation, institutional, restricted
            $table->string('badge_icon')->default('🎉');
            $table->text('description')->nullable();
            $table->boolean('is_instructional_off')->default(true);
            $table->string('academic_year')->default('2026-2027');
            $table->timestamps();

            $table->index(['holiday_date', 'end_date']);
            $table->index('type');
        });

        Schema::create('lesson_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('academic_classes')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('academic_subjects')->cascadeOnDelete();
            $table->foreignId('topic_id')->nullable()->constrained('curriculum_topics')->nullOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('lesson_number')->default(1);
            $table->string('title');
            $table->text('subtopics')->nullable();
            $table->text('learning_objectives')->nullable();
            $table->string('pedagogy')->default('Interactive Lecture');
            $table->date('planned_date');
            $table->string('period_slot')->default('Period 1 (08:30 - 09:30)');
            $table->string('bloom_focus')->default('Understand & Apply');
            $table->text('resources_needed')->nullable();
            $table->text('practical_lab')->nullable();
            $table->text('homework_assignment')->nullable();
            $table->string('status')->default('planned'); // planned, in_progress, completed, holiday_shift, deferred
            $table->foreignId('holiday_id')->nullable()->constrained('academic_holidays')->nullOnDelete();
            $table->string('holiday_note')->nullable();
            $table->string('term')->default('Term 1');
            $table->unsignedInteger('order_index')->default(1);
            $table->timestamps();

            $table->index(['class_id', 'subject_id', 'planned_date']);
            $table->index(['planned_date', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesson_plans');
        Schema::dropIfExists('academic_holidays');
    }
};
