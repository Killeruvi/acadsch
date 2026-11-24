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
        Schema::create('ptm_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('academic_year', 20)->nullable();
            $table->foreignId('class_id')->nullable()->constrained('academic_classes')->nullOnDelete();
            $table->date('meeting_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedSmallInteger('slot_duration_minutes')->default(15);
            $table->enum('mode', ['in_person', 'online', 'hybrid'])->default('in_person');
            $table->string('meeting_venue', 191)->nullable();
            $table->string('meeting_link', 500)->nullable();
            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'cancelled'])->default('scheduled');
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['meeting_date', 'status']);
        });

        Schema::create('ptm_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained('ptm_schedules')->cascadeOnDelete();
            $table->foreignId('parent_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->time('slot_time');
            $table->enum('status', ['booked', 'completed', 'no_show', 'cancelled'])->default('booked');
            $table->text('parent_notes')->nullable();
            $table->text('teacher_feedback')->nullable();
            $table->text('action_items')->nullable();
            $table->timestamps();

            $table->unique(['schedule_id', 'teacher_id', 'slot_time'], 'ptm_teacher_slot_unique');
            $table->unique(['schedule_id', 'student_id', 'teacher_id'], 'ptm_student_teacher_unique');
            $table->index(['parent_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ptm_bookings');
        Schema::dropIfExists('ptm_schedules');
    }
};
