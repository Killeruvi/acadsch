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
        Schema::table('daily_dpps', function (Blueprint $table) {
            $table->foreignId('course_id')->nullable()->after('id')->constrained('courses')->nullOnDelete();
            $table->foreignId('lesson_id')->nullable()->after('course_id')->constrained('course_lessons')->nullOnDelete();
            $table->index(['course_id', 'dpp_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_dpps', function (Blueprint $table) {
            $table->dropForeign(['course_id']);
            $table->dropForeign(['lesson_id']);
            $table->dropIndex(['course_id', 'dpp_number']);
            $table->dropColumn(['course_id', 'lesson_id']);
        });
    }
};
