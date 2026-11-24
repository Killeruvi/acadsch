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
        Schema::create('online_admissions', function (Blueprint $table) {
            $table->id();
            $table->string('application_number', 32)->unique();
            $table->string('academic_year', 20);
            $table->foreignId('target_class_id')->constrained('academic_classes')->cascadeOnDelete();
            
            // Scholar Personal Details
            $table->string('student_first_name', 100);
            $table->string('student_last_name', 100);
            $table->date('dob');
            $table->enum('gender', ['male', 'female', 'other']);
            $table->string('blood_group', 10)->nullable();
            $table->string('nationality', 50)->default('Indian');
            $table->string('religion', 50)->nullable();
            $table->string('caste_category', 50)->default('General');
            
            // Previous Academic Background
            $table->string('previous_school_name', 191)->nullable();
            $table->string('previous_class', 50)->nullable();
            $table->decimal('previous_marks_percentage', 5, 2)->nullable();
            
            // Parents & Guardian Details
            $table->string('father_name', 120);
            $table->string('father_phone', 25)->index();
            $table->string('father_email', 150)->nullable();
            $table->string('father_occupation', 100)->nullable();
            $table->string('mother_name', 120);
            $table->string('mother_phone', 25)->nullable();
            $table->string('mother_email', 150)->nullable();
            $table->string('mother_occupation', 100)->nullable();
            
            // Residential Address
            $table->text('guardian_address');
            $table->string('city', 100);
            $table->string('state', 100);
            $table->string('pincode', 20);
            
            // Uploaded Verification Documents
            $table->string('birth_certificate_path', 500)->nullable();
            $table->string('transfer_certificate_path', 500)->nullable();
            $table->string('previous_marksheet_path', 500)->nullable();
            $table->string('student_photo_path', 500)->nullable();
            
            // Administrative Review & Enrollment Workflow
            $table->enum('application_status', ['submitted', 'under_review', 'shortlisted', 'admitted', 'rejected'])->default('submitted')->index();
            $table->text('review_notes')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('enrolled_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['academic_year', 'application_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('online_admissions');
    }
};
