<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('school_sections') && !Schema::hasColumn('school_sections', 'name_key')) {
            Schema::table('school_sections', function (Blueprint $table) {
                $table->string('name_key', 30)->nullable();
            });
        }

        if (Schema::hasTable('school_sections')) {
            foreach (DB::table('school_sections')->select('id', 'name')->get() as $section) {
                DB::table('school_sections')->where('id', $section->id)->update([
                    'name_key' => mb_strtoupper(trim((string) $section->name)),
                ]);
            }

            $duplicateSections = DB::table('school_sections')
                ->select('stream', 'name_key', DB::raw('COUNT(*) AS record_count'))
                ->groupBy('stream', 'name_key')
                ->havingRaw('COUNT(*) > 1')
                ->limit(10)
                ->get();
            if ($duplicateSections->isNotEmpty()) {
                throw new RuntimeException('Cannot enforce section uniqueness. Resolve case-insensitive duplicate stream/section records first: '.json_encode($duplicateSections));
            }

            Schema::table('school_sections', function (Blueprint $table) {
                $table->unique(['stream', 'name_key'], 'school_sections_stream_name_key_unique');
            });
        }

        if (Schema::hasTable('academic_classes')) {
            $duplicateClasses = DB::table('academic_classes')
                ->select('grade_level', 'stream', 'section', 'academic_year', DB::raw('COUNT(*) AS record_count'))
                ->groupBy('grade_level', 'stream', 'section', 'academic_year')
                ->havingRaw('COUNT(*) > 1')
                ->limit(10)
                ->get();
            if ($duplicateClasses->isNotEmpty()) {
                throw new RuntimeException('Cannot enforce class uniqueness. Resolve duplicate grade/stream/section/session records first: '.json_encode($duplicateClasses));
            }

            Schema::table('academic_classes', function (Blueprint $table) {
                $table->unique(
                    ['grade_level', 'stream', 'section', 'academic_year'],
                    'academic_classes_grade_stream_section_year_unique'
                );
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('academic_classes')) {
            Schema::table('academic_classes', function (Blueprint $table) {
                $table->dropUnique('academic_classes_grade_stream_section_year_unique');
            });
        }
        if (Schema::hasTable('school_sections')) {
            Schema::table('school_sections', function (Blueprint $table) {
                $table->dropUnique('school_sections_stream_name_key_unique');
                $table->dropColumn('name_key');
            });
        }
    }
};
