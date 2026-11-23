<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('academic_classes') && Schema::hasColumn('academic_classes', 'capacity')) {
            Schema::table('academic_classes', fn (Blueprint $table) => $table->integer('capacity')->nullable()->default(null)->change());
        }
        if (Schema::hasTable('subject_masters') && Schema::hasColumn('subject_masters', 'default_periods')) {
            Schema::table('subject_masters', fn (Blueprint $table) => $table->unsignedTinyInteger('default_periods')->nullable()->default(null)->change());
        }
        if (Schema::hasTable('academic_subjects') && Schema::hasColumn('academic_subjects', 'weekly_periods')) {
            Schema::table('academic_subjects', fn (Blueprint $table) => $table->integer('weekly_periods')->nullable()->default(null)->change());
        }
        if (Schema::hasTable('school_sections') && Schema::hasColumn('school_sections', 'capacity')) {
            Schema::table('school_sections', fn (Blueprint $table) => $table->unsignedSmallInteger('capacity')->nullable()->default(null)->change());
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('academic_classes') && Schema::hasColumn('academic_classes', 'capacity')) {
            Schema::table('academic_classes', fn (Blueprint $table) => $table->integer('capacity')->default(40)->change());
        }
        if (Schema::hasTable('subject_masters') && Schema::hasColumn('subject_masters', 'default_periods')) {
            Schema::table('subject_masters', fn (Blueprint $table) => $table->unsignedTinyInteger('default_periods')->nullable()->default(null)->change());
        }
        if (Schema::hasTable('subject_masters') && Schema::hasColumn('subject_masters', 'default_periods')) {
            Schema::table('subject_masters', fn (Blueprint $table) => $table->unsignedTinyInteger('default_periods')->default(5)->change());
        }
        if (Schema::hasTable('academic_subjects') && Schema::hasColumn('academic_subjects', 'weekly_periods')) {
            Schema::table('academic_subjects', fn (Blueprint $table) => $table->integer('weekly_periods')->default(5)->change());
        }
        if (Schema::hasTable('school_sections') && Schema::hasColumn('school_sections', 'capacity')) {
            Schema::table('school_sections', fn (Blueprint $table) => $table->unsignedSmallInteger('capacity')->default(40)->change());
        }
    }
};
