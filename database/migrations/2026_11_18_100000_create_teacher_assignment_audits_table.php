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
        Schema::create('teacher_assignment_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('users')->cascadeOnDelete();
            $table->string('action', 50);
            $table->foreignId('teacher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('class_id')->nullable()->constrained('academic_classes')->nullOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained('academic_subjects')->nullOnDelete();
            $table->unsignedBigInteger('period_timetable_id')->nullable();
            $table->string('teacher_name')->nullable();
            $table->string('class_name')->nullable();
            $table->string('subject_name')->nullable();
            $table->text('description');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['admin_id', 'created_at']);
            $table->index(['teacher_id', 'created_at']);
            $table->index(['class_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_assignment_audits');
    }
};
