<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('academic_subjects') || !Schema::hasTable('subject_masters')) {
            return;
        }

        if (!Schema::hasColumn('academic_subjects', 'subject_master_id')) {
            Schema::table('academic_subjects', function (Blueprint $table) {
                $table->foreignId('subject_master_id')->nullable()->after('class_id')
                    ->constrained('subject_masters')->restrictOnDelete();
                $table->index('subject_master_id', 'academic_subjects_subject_master_index');
            });
        }

        $subjects = DB::table('academic_subjects')
            ->join('academic_classes', 'academic_classes.id', '=', 'academic_subjects.class_id')
            ->select(
                'academic_subjects.id', 'academic_subjects.name', 'academic_subjects.code',
                'academic_classes.stream', 'academic_classes.grade_level'
            )->whereNull('academic_subjects.subject_master_id')->get();

        foreach ($subjects as $subject) {
            $baseCode = preg_replace('/-[0-9]+[A-Za-z]*$/', '', strtoupper(trim((string) $subject->code)));
            $candidates = DB::table('subject_masters')
                ->whereRaw('UPPER(name) = ?', [strtoupper(trim((string) $subject->name))])
                ->where(function ($query) use ($subject) {
                    $query->where('stream', $subject->stream)->orWhere('stream', 'All');
                })
                ->where('min_grade', '<=', $subject->grade_level)
                ->where('max_grade', '>=', $subject->grade_level)
                ->orderByRaw('CASE WHEN stream = ? THEN 0 ELSE 1 END', [$subject->stream])
                ->get();

            $master = $candidates->first(fn ($candidate) => strtoupper((string) $candidate->code) === $baseCode)
                ?? $candidates->first(fn ($candidate) => strtoupper((string) $candidate->code) === strtoupper((string) $subject->code));
            if (!$master && $candidates->count() === 1) {
                $master = $candidates->first();
            }

            if ($master) {
                DB::table('academic_subjects')->where('id', $subject->id)->update(['subject_master_id' => $master->id]);
            }
        }

        $duplicates = DB::table('academic_subjects')->whereNotNull('subject_master_id')
            ->select('class_id', 'subject_master_id', DB::raw('COUNT(*) AS total'))
            ->groupBy('class_id', 'subject_master_id')->havingRaw('COUNT(*) > 1')->limit(10)->get();
        if ($duplicates->isNotEmpty()) {
            throw new RuntimeException('Resolve duplicate class-to-subject-master mappings before enforcing uniqueness: '.json_encode($duplicates));
        }
        Schema::table('academic_subjects', function (Blueprint $table) {
            $table->unique(['class_id', 'subject_master_id'], 'academic_subjects_class_master_unique');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('academic_subjects') && Schema::hasColumn('academic_subjects', 'subject_master_id')) {
            Schema::table('academic_subjects', function (Blueprint $table) {
                $table->dropUnique('academic_subjects_class_master_unique');
                $table->dropForeign(['subject_master_id']);
                $table->dropIndex('academic_subjects_subject_master_index');
                $table->dropColumn('subject_master_id');
            });
        }
    }
};
