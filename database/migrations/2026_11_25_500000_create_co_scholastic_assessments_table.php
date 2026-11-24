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
        Schema::create('co_scholastic_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('academic_classes')->cascadeOnDelete();
            $table->foreignId('exam_term_id')->constrained('school_exam_terms')->cascadeOnDelete();
            $table->string('academic_year', 20)->nullable();

            // CBSE / K-12 Co-Scholastic Assessment Domains (Grades A, B, C)
            $table->string('work_education', 5)->default('A');
            $table->string('art_education', 5)->default('A');
            $table->string('health_physical_education', 5)->default('A');
            $table->string('discipline', 5)->default('A');

            // Physical Health & Vital Metrics
            $table->decimal('height_cm', 5, 2)->nullable();
            $table->decimal('weight_kg', 5, 2)->nullable();

            // 360-Degree Holistic Rubrics (NEP 2020 HPC)
            $table->text('self_assessment_notes')->nullable();
            $table->text('peer_feedback_notes')->nullable();
            $table->text('teacher_holistic_remarks')->nullable();

            $table->foreignId('assessed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['student_id', 'exam_term_id'], 'co_scholastic_student_term_unique');
            $table->index(['class_id', 'exam_term_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('co_scholastic_assessments');
    }
};
