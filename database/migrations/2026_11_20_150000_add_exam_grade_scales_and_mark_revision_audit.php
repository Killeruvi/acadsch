<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('school_exam_grade_scales')) {
            Schema::create('school_exam_grade_scales', function (Blueprint $table) {
                $table->id();
                $table->string('grade_code', 10)->unique();
                $table->decimal('minimum_percentage', 5, 2)->unique();
                $table->string('label', 80);
                $table->boolean('is_passing')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });

            $now = now();
            DB::table('school_exam_grade_scales')->insert([
                ['grade_code' => 'A+', 'minimum_percentage' => 90, 'label' => 'Excellent', 'is_passing' => true, 'sort_order' => 1, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
                ['grade_code' => 'A', 'minimum_percentage' => 80, 'label' => 'Very Good', 'is_passing' => true, 'sort_order' => 2, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
                ['grade_code' => 'B+', 'minimum_percentage' => 70, 'label' => 'Good', 'is_passing' => true, 'sort_order' => 3, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
                ['grade_code' => 'B', 'minimum_percentage' => 60, 'label' => 'Above Average', 'is_passing' => true, 'sort_order' => 4, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
                ['grade_code' => 'C', 'minimum_percentage' => 50, 'label' => 'Average', 'is_passing' => true, 'sort_order' => 5, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
                ['grade_code' => 'D', 'minimum_percentage' => 35, 'label' => 'Needs Improvement', 'is_passing' => true, 'sort_order' => 6, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
                ['grade_code' => 'F', 'minimum_percentage' => 0, 'label' => 'Below Passing', 'is_passing' => false, 'sort_order' => 7, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ]);
        }

        if (! Schema::hasTable('school_exam_mark_revisions')) {
            Schema::create('school_exam_mark_revisions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('exam_mark_id')->constrained('school_exam_marks')->restrictOnDelete();
                $table->decimal('old_marks', 8, 2);
                $table->decimal('new_marks', 8, 2);
                $table->text('reason');
                $table->foreignId('changed_by')->constrained('users')->restrictOnDelete();
                $table->timestamps();
                $table->index(['exam_mark_id', 'created_at']);
            });
        }

        if (Schema::hasTable('school_report_cards')) {
            Schema::table('school_report_cards', function (Blueprint $table) {
                if (! Schema::hasColumn('school_report_cards', 'attendance_data_available')) {
                    $table->boolean('attendance_data_available')->default(false);
                }
                if (! Schema::hasColumn('school_report_cards', 'gpa_configured')) {
                    $table->boolean('gpa_configured')->default(false);
                }
            });
        }

        if (Schema::hasTable('school_exam_schedules')) {
            $duplicate = DB::table('school_exam_schedules')
                ->select('exam_term_id', 'class_id', 'subject_id')
                ->groupBy('exam_term_id', 'class_id', 'subject_id')
                ->havingRaw('COUNT(*) > 1')->exists();
            if ($duplicate) {
                throw new RuntimeException('Duplicate exam schedule slots exist. Resolve them before applying the examination integrity migration.');
            }
            Schema::table('school_exam_schedules', function (Blueprint $table) {
                $table->unique(['exam_term_id', 'class_id', 'subject_id'], 'exam_schedule_slot_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('school_exam_mark_revisions');
        Schema::dropIfExists('school_exam_grade_scales');
        if (Schema::hasTable('school_report_cards')) {
            Schema::table('school_report_cards', function (Blueprint $table) {
                if (Schema::hasColumn('school_report_cards', 'attendance_data_available')) {
                    $table->dropColumn('attendance_data_available');
                }
                if (Schema::hasColumn('school_report_cards', 'gpa_configured')) {
                    $table->dropColumn('gpa_configured');
                }
            });
        }

        if (Schema::hasTable('school_report_cards')) {
            Schema::table('school_report_cards', function (Blueprint $table) {
                if (! Schema::hasColumn('school_report_cards', 'attendance_data_available')) {
                    $table->boolean('attendance_data_available')->default(false);
                }
                if (! Schema::hasColumn('school_report_cards', 'gpa_configured')) {
                    $table->boolean('gpa_configured')->default(false);
                }
            });
        }

        if (Schema::hasTable('school_exam_schedules')) {
            Schema::table('school_exam_schedules', function (Blueprint $table) {
                $table->dropUnique('exam_schedule_slot_unique');
            });
        }
    }
};
