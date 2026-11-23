<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('users')->restrictOnDelete();
            $table->date('attendance_date');
            $table->enum('status', ['present', 'absent', 'half_day', 'leave']);
            $table->string('remarks', 500)->nullable();
            $table->foreignId('marked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['staff_id', 'attendance_date'], 'staff_attendance_staff_date_unique');
            $table->index(['attendance_date', 'status'], 'staff_attendance_date_status_idx');
        });

        Schema::create('staff_leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('users')->restrictOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('leave_type', ['paid', 'unpaid']);
            $table->string('reason', 2000);
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('review_remarks')->nullable();
            $table->timestamps();
            $table->index(['staff_id', 'status', 'start_date', 'end_date'], 'staff_leave_staff_status_dates_idx');
            $table->index(['status', 'start_date'], 'staff_leave_status_start_idx');
        });

        Schema::create('staff_hr_audits', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type', 40);
            $table->unsignedBigInteger('entity_id');
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_type', 40);
            $table->json('before_data')->nullable();
            $table->json('after_data')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['entity_type', 'entity_id', 'created_at'], 'staff_hr_audit_entity_created_idx');
            $table->index(['actor_id', 'created_at'], 'staff_hr_audit_actor_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_hr_audits');
        Schema::dropIfExists('staff_leave_requests');
        Schema::dropIfExists('staff_attendance_records');
    }
};
