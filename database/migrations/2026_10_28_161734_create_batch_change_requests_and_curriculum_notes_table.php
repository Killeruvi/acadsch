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
        // 1. Batch Change Requests (Students can only request; only teachers/admins approve)
        if (! Schema::hasTable('batch_change_requests')) {
            Schema::create('batch_change_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('current_batch_id')->nullable()->constrained('batches')->nullOnDelete();
                $table->foreignId('requested_batch_id')->constrained('batches')->cascadeOnDelete();
                $table->text('reason');
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('admin_notes')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamps();
            });
        }

        // 2. Curriculum Notes (Allows multiple notes / documents per chapter)
        if (! Schema::hasTable('curriculum_notes')) {
            Schema::create('curriculum_notes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('topic_id')->constrained('curriculum_topics')->cascadeOnDelete();
                $table->string('title');
                $table->string('note_type')->default('theory'); // 'theory', 'formula_sheet', 'revision', 'ncert_solution'
                $table->longText('content')->nullable();
                $table->string('download_url')->nullable();
                $table->integer('reading_minutes')->default(10);
                $table->boolean('is_published')->default(true);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('curriculum_notes');
        Schema::dropIfExists('batch_change_requests');
    }
};
