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
            $table->foreignId('class_id')->nullable()->after('id')->constrained('academic_classes')->nullOnDelete();
            $table->foreignId('section_id')->nullable()->after('class_id')->constrained('school_sections')->nullOnDelete();
            $table->foreignId('subject_id')->nullable()->after('section_id')->constrained('academic_subjects')->nullOnDelete();
            $table->string('academic_year', 20)->nullable()->after('subject_id');

            $table->index(['class_id', 'due_date']);
            $table->index(['subject_id', 'due_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->dropIndex(['class_id', 'due_date']);
            $table->dropIndex(['subject_id', 'due_date']);
            $table->dropForeign(['class_id']);
            $table->dropForeign(['section_id']);
            $table->dropForeign(['subject_id']);
            $table->dropColumn(['class_id', 'section_id', 'subject_id', 'academic_year']);
        });
    }
};
