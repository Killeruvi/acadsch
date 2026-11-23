<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('academic_subjects')) {
            if (!Schema::hasColumn('academic_subjects', 'code_key')) {
                Schema::table('academic_subjects', function (Blueprint $table) {
                    $table->string('code_key', 30)->nullable();
                });
            }
            foreach (DB::table('academic_subjects')->select('id', 'code')->get() as $subject) {
                DB::table('academic_subjects')->where('id', $subject->id)->update(['code_key' => strtoupper(trim((string) $subject->code))]);
            }
            $duplicates = DB::table('academic_subjects')
                ->select('class_id', 'code_key', DB::raw('COUNT(*) AS total'))
                ->groupBy('class_id', 'code_key')
                ->havingRaw('COUNT(*) > 1')->limit(10)->get();
            if ($duplicates->isNotEmpty()) {
                throw new RuntimeException('Resolve duplicate case-normalized subject codes per class before enforcing uniqueness: '.json_encode($duplicates));
            }
            Schema::table('academic_subjects', function (Blueprint $table) {
                $table->unique(['class_id', 'code_key'], 'academic_subjects_class_code_key_unique');
            });
        }

        if (Schema::hasTable('school_period_timetables')) {
            $classDuplicates = DB::table('school_period_timetables')
                ->select('class_id', 'day_of_week', 'period_number', DB::raw('COUNT(*) AS total'))
                ->groupBy('class_id', 'day_of_week', 'period_number')
                ->havingRaw('COUNT(*) > 1')->limit(10)->get();
            if ($classDuplicates->isNotEmpty()) {
                throw new RuntimeException('Resolve duplicate class timetable slots before enforcing uniqueness: '.json_encode($classDuplicates));
            }

            $teacherDuplicates = DB::table('school_period_timetables')->whereNotNull('teacher_id')
                ->select('teacher_id', 'day_of_week', 'period_number', DB::raw('COUNT(*) AS total'))
                ->groupBy('teacher_id', 'day_of_week', 'period_number')
                ->havingRaw('COUNT(*) > 1')->limit(10)->get();
            if ($teacherDuplicates->isNotEmpty()) {
                throw new RuntimeException('Resolve teacher timetable clashes before enforcing uniqueness: '.json_encode($teacherDuplicates));
            }

            Schema::table('school_period_timetables', function (Blueprint $table) {
                $table->unique(['class_id', 'day_of_week', 'period_number'], 'school_timetable_class_slot_unique');
                $table->unique(['teacher_id', 'day_of_week', 'period_number'], 'school_timetable_teacher_slot_unique');
                $table->index(['room_number', 'day_of_week', 'period_number'], 'school_timetable_room_slot_index');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('school_period_timetables')) {
            Schema::table('school_period_timetables', function (Blueprint $table) {
                $table->dropUnique('school_timetable_class_slot_unique');
                $table->dropUnique('school_timetable_teacher_slot_unique');
                $table->dropIndex('school_timetable_room_slot_index');
            });
        }
        if (Schema::hasTable('academic_subjects')) {
            Schema::table('academic_subjects', function (Blueprint $table) {
                $table->dropUnique('academic_subjects_class_code_key_unique');
                $table->dropColumn('code_key');
            });
        }
    }
};
