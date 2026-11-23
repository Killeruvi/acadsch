<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_promotion_policies', function (Blueprint $table) {
            $table->id();
            $table->string('academic_year', 30);
            $table->unsignedBigInteger('source_class_id')->nullable();
            $table->decimal('minimum_overall_percentage', 5, 2)->default(35);
            $table->decimal('minimum_subject_percentage', 5, 2)->default(35);
            $table->boolean('require_all_subject_marks')->default(true);
            $table->boolean('require_published_result')->default(true);
            $table->timestamps();
            $table->unique(['academic_year', 'source_class_id'], 'promotion_policy_session_class_unique');
            $table->foreign('source_class_id')->references('id')->on('academic_classes')->nullOnDelete();
        });

        Schema::create('school_graduation_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('class_id')->constrained('academic_classes')->restrictOnDelete();
            $table->string('academic_year', 30);
            $table->string('certificate_number', 60)->unique();
            $table->date('date_of_leaving');
            $table->string('reason_for_leaving', 255)->nullable();
            $table->string('result_status', 20);
            $table->foreignId('issued_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['student_id', 'academic_year'], 'graduation_student_session_unique');
        });

        Schema::create('school_promotion_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('source_class_id')->constrained('academic_classes')->restrictOnDelete();
            $table->foreignId('target_class_id')->constrained('academic_classes')->restrictOnDelete();
            $table->string('source_year', 30);
            $table->string('target_year', 30);
            $table->string('decision', 20);
            $table->string('reason', 255)->nullable();
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['source_class_id', 'target_class_id', 'created_at'], 'promotion_audit_class_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_promotion_audits');
        Schema::dropIfExists('school_graduation_records');
        Schema::dropIfExists('school_promotion_policies');
    }
};
