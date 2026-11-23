<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('staff_employment_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->unique()->constrained('users')->restrictOnDelete();
            $table->string('employee_code', 40)->unique();
            $table->string('designation', 120);
            $table->string('department', 120)->nullable();
            $table->enum('employment_type', ['permanent', 'probation', 'contract', 'part_time', 'intern'])->default('permanent');
            $table->date('joining_date');
            $table->date('probation_end_date')->nullable();
            $table->date('notice_period_end_date')->nullable();
            $table->date('last_working_date')->nullable();
            $table->enum('employment_status', ['onboarding', 'active', 'on_leave', 'notice_period', 'separated'])->default('onboarding');
            $table->string('work_location', 180)->nullable();
            $table->string('emergency_contact_name', 160)->nullable();
            $table->string('emergency_contact_phone', 30)->nullable();
            $table->text('admin_notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['employment_status', 'joining_date']);
        });

        Schema::create('staff_onboarding_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('users')->restrictOnDelete();
            $table->string('task_code', 60);
            $table->string('title', 180);
            $table->boolean('is_required')->default(true);
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['staff_id', 'task_code']);
            $table->index(['staff_id', 'completed_at']);
        });

        Schema::create('staff_employment_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('users')->restrictOnDelete();
            $table->string('contract_number', 60)->unique();
            $table->string('contract_type', 80);
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->decimal('monthly_ctc', 12, 2)->nullable();
            $table->string('currency', 3)->default('INR');
            $table->string('file_path', 500);
            $table->string('original_filename', 255);
            $table->string('mime_type', 120);
            $table->unsignedBigInteger('file_size');
            $table->enum('status', ['draft', 'active', 'expired', 'terminated'])->default('draft');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['staff_id', 'status', 'starts_on', 'ends_on']);
        });

        Schema::create('staff_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('users')->restrictOnDelete();
            $table->string('document_type', 80);
            $table->string('document_name', 180);
            $table->string('file_path', 500);
            $table->string('original_filename', 255);
            $table->string('mime_type', 120);
            $table->unsignedBigInteger('file_size');
            $table->date('expires_on')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['staff_id', 'document_type']);
            $table->index('expires_on');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_documents');
        Schema::dropIfExists('staff_employment_contracts');
        Schema::dropIfExists('staff_onboarding_tasks');
        Schema::dropIfExists('staff_employment_profiles');
    }
};
