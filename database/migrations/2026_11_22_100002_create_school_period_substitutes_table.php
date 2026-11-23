<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('school_period_substitutes')) {
            Schema::create('school_period_substitutes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('timetable_id')->constrained('school_period_timetables')->cascadeOnDelete();
                $table->foreignId('original_teacher_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('substitute_teacher_id')->constrained('users')->cascadeOnDelete();
                $table->date('date');
                $table->string('reason', 255)->nullable();
                $table->string('status', 30)->default('active');
                $table->foreignId('assigned_by')->constrained('users')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['timetable_id', 'date'], 'substitutes_slot_date_unique');
                $table->index(['substitute_teacher_id', 'date', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('school_period_substitutes');
    }
};
