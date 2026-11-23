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
        Schema::table('bloom_assignments', function (Blueprint $table) {
            // Student submission attachments (PDF, PPT, DOC, etc.)
            $table->string('submission_attachment_path')->nullable()->after('submission_content');
            $table->string('submission_attachment_name')->nullable()->after('submission_attachment_path');
            $table->string('submission_attachment_type', 50)->nullable()->after('submission_attachment_name');
            $table->unsignedBigInteger('submission_attachment_size')->nullable()->after('submission_attachment_type');

            // Teacher reference/guideline attachments (presentation decks, assignment PDFs, rubrics)
            $table->string('teacher_attachment_path')->nullable()->after('rubric_guide');
            $table->string('teacher_attachment_name')->nullable()->after('teacher_attachment_path');
            $table->string('teacher_attachment_type', 50)->nullable()->after('teacher_attachment_name');
            $table->unsignedBigInteger('teacher_attachment_size')->nullable()->after('teacher_attachment_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bloom_assignments', function (Blueprint $table) {
            $table->dropColumn([
                'submission_attachment_path',
                'submission_attachment_name',
                'submission_attachment_type',
                'submission_attachment_size',
                'teacher_attachment_path',
                'teacher_attachment_name',
                'teacher_attachment_type',
                'teacher_attachment_size',
            ]);
        });
    }
};
