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
        Schema::table('assignments', function (Blueprint $table) {
            $table->string('target_type')->default('all_enrolled')->after('max_points'); // 'all_enrolled', 'all_students', 'specific_student'
            $table->foreignId('student_id')->nullable()->after('target_type')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->dropForeign(['student_id']);
            $table->dropColumn(['target_type', 'student_id']);
        });
    }
};
