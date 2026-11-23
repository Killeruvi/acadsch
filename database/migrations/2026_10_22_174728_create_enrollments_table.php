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
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('enrolled')->index(); // 'enrolled', 'completed', 'dropped'
            $table->timestamp('enrolled_at')->useCurrent();
            $table->string('grade_letter', 5)->nullable();
            $table->decimal('final_score', 5, 2)->nullable();
            $table->timestamps();

            $table->unique(['course_id', 'student_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
