<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('study_materials', function (Blueprint $table) {
            $table->index(['class_id', 'available_for', 'upload_date'], 'study_materials_class_audience_date_idx');
            $table->index(['content_type', 'upload_date'], 'study_materials_type_date_idx');
            $table->index(['created_by', 'upload_date'], 'study_materials_author_date_idx');
        });
        Schema::table('course_lessons', function (Blueprint $table) {
            $table->index(['module_id', 'order_index'], 'course_lessons_module_order_idx');
        });
        Schema::table('course_lesson_videos', function (Blueprint $table) {
            $table->index(['lesson_id', 'order_index'], 'course_lesson_videos_lesson_order_idx');
        });
    }

    public function down(): void
    {
        Schema::table('course_lesson_videos', fn (Blueprint $table) => $table->dropIndex('course_lesson_videos_lesson_order_idx'));
        Schema::table('course_lessons', function (Blueprint $table) {
            $table->dropIndex('course_lessons_module_order_idx');
        });
        Schema::table('study_materials', function (Blueprint $table) {
            $table->dropIndex('study_materials_class_audience_date_idx');
            $table->dropIndex('study_materials_type_date_idx');
            $table->dropIndex('study_materials_author_date_idx');
        });
    }
};
