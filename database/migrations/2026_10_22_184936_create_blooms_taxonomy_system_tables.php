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
        // 1. Academic Classes (Classes 1 - 12)
        Schema::create('academic_classes', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // "Class 8", "Class 7"
            $table->unsignedSmallInteger('grade_level'); // 1 - 12
            $table->string('section', 10)->default('A');
            $table->string('academic_year', 20)->default('2026-2027');
            $table->timestamps();
        });

        // 2. Academic Subjects
        Schema::create('academic_subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('academic_classes')->cascadeOnDelete();
            $table->string('name'); // "Science", "Mathematics"
            $table->string('code', 20); // "SCI-08"
            $table->timestamps();
        });

        // 3. Curriculum Topics
        Schema::create('curriculum_topics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained('academic_subjects')->cascadeOnDelete();
            $table->string('title'); // "Photosynthesis and Plant Nutrition"
            $table->unsignedSmallInteger('chapter_number')->nullable();
            $table->json('subtopics')->nullable(); // List of subtopics
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // 4. Topic Bloom Activities (Pre-mapped 6 Bloom levels per topic)
        Schema::create('topic_bloom_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('topic_id')->constrained('curriculum_topics')->cascadeOnDelete();
            $table->unsignedSmallInteger('bloom_level'); // 1 - 6
            $table->string('level_name', 30); // Remember, Understand, Apply, Analyze, Evaluate, Create
            $table->string('verb_keywords')->nullable(); // "Define, List, Recall"
            $table->text('activity_prompt'); // Specific student prompt
            $table->text('rubric_guide')->nullable();
            $table->timestamps();

            $table->unique(['topic_id', 'bloom_level']);
        });

        // 5. Class Enrollments & Roll Numbers
        Schema::create('class_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('academic_classes')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('roll_number');
            $table->string('status', 20)->default('active'); // active, inactive
            $table->timestamps();

            $table->unique(['class_id', 'student_id']);
            $table->unique(['class_id', 'roll_number']);
        });

        // 6. Daily Lesson Records
        Schema::create('daily_lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('academic_classes')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('academic_subjects')->cascadeOnDelete();
            $table->foreignId('topic_id')->constrained('curriculum_topics')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->string('subtopic')->nullable();
            $table->date('lesson_date');
            $table->string('distribution_mode', 30)->default('balanced_random'); // balanced_random, uniform_level, roll_range
            $table->unsignedSmallInteger('target_level')->nullable(); // If uniform_level
            $table->text('lesson_notes')->nullable();
            $table->timestamps();
        });

        // 7. Student Bloom's Taxonomy Assignments (Distributed by Roll Number)
        Schema::create('bloom_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained('daily_lessons')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('roll_number');
            $table->unsignedSmallInteger('bloom_level'); // 1 - 6
            $table->string('level_name', 30); // Remember, Understand, Apply, Analyze, Evaluate, Create
            $table->text('activity_prompt');
            $table->text('submission_content')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->string('status', 20)->default('assigned')->index(); // assigned, submitted, reviewed, late
            $table->text('teacher_feedback')->nullable();
            $table->decimal('score', 5, 2)->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->unique(['lesson_id', 'student_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bloom_assignments');
        Schema::dropIfExists('daily_lessons');
        Schema::dropIfExists('class_enrollments');
        Schema::dropIfExists('topic_bloom_activities');
        Schema::dropIfExists('curriculum_topics');
        Schema::dropIfExists('academic_subjects');
        Schema::dropIfExists('academic_classes');
    }
};
