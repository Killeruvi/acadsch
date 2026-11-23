<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_record_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_record_id')->constrained('attendance_records')->restrictOnDelete();
            $table->foreignId('attendance_session_id')->constrained('attendance_sessions')->restrictOnDelete();
            $table->foreignId('student_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('before_status', 20)->nullable();
            $table->string('after_status', 20);
            $table->string('source', 40);
            $table->text('reason')->nullable();
            $table->timestamp('changed_at');
            $table->timestamps();
            $table->index(['attendance_session_id', 'changed_at']);
            $table->index(['student_id', 'changed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_record_revisions');
    }
};
