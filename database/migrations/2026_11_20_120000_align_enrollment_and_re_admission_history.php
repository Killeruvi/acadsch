<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('class_enrollments')) {
            // Backfill legacy rows before introducing session-scoped uniqueness.
            DB::table('class_enrollments')
                ->whereNull('academic_year')
                ->orderBy('id')
                ->chunkById(200, function ($rows): void {
                    foreach ($rows as $row) {
                        $year = DB::table('academic_classes')->where('id', $row->class_id)->value('academic_year');
                        if ($year) {
                            DB::table('class_enrollments')->where('id', $row->id)->update(['academic_year' => $year]);
                        }
                    }
                });

            Schema::table('class_enrollments', function (Blueprint $table): void {
                if (!Schema::hasColumn('class_enrollments', 'ended_at')) {
                    $table->timestamp('ended_at')->nullable();
                }
                if (!Schema::hasColumn('class_enrollments', 'ended_by')) {
                    $table->foreignId('ended_by')->nullable()->constrained('users')->nullOnDelete();
                }
                if (!Schema::hasColumn('class_enrollments', 'transition_reason')) {
                    $table->text('transition_reason')->nullable();
                }
                // Old uniqueness prevented a student returning to the same class in a later year.
                $table->dropUnique('class_enrollments_class_id_student_id_unique');
                $table->dropUnique('class_enrollments_class_id_roll_number_unique');
                $table->index(['class_id', 'student_id', 'academic_year'], 'class_enrollments_class_student_year_idx');
                $table->unique(['class_id', 'academic_year', 'roll_number'], 'class_enrollments_class_year_roll_unique');
                $table->index(['student_id', 'academic_year', 'status'], 'class_enrollments_student_year_status_idx');
            });
        }

        if (Schema::hasTable('student_withdrawal_records')) {
            Schema::table('student_withdrawal_records', function (Blueprint $table): void {
                $table->dropUnique('student_withdrawal_records_student_id_unique');
                if (!Schema::hasColumn('student_withdrawal_records', 're_admitted_at')) {
                    $table->timestamp('re_admitted_at')->nullable()->after('effective_date');
                }
                if (!Schema::hasColumn('student_withdrawal_records', 're_admitted_by')) {
                    $table->foreignId('re_admitted_by')->nullable()->after('re_admitted_at')->constrained('users')->nullOnDelete();
                }
                $table->index(['student_id', 're_admitted_at'], 'student_withdrawal_student_open_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('student_withdrawal_records')) {
            Schema::table('student_withdrawal_records', function (Blueprint $table): void {
                $table->dropIndex('student_withdrawal_student_open_idx');
                $table->dropConstrainedForeignId('re_admitted_by');
                $table->dropColumn('re_admitted_at');
                // Do not restore the old one-withdrawal-per-student unique key: valid re-admission
                // history may now contain multiple withdrawal episodes.
            });
        }

        if (Schema::hasTable('class_enrollments')) {
            Schema::table('class_enrollments', function (Blueprint $table): void {
                $table->dropIndex('class_enrollments_student_year_status_idx');
                $table->dropIndex('class_enrollments_class_student_year_idx');
                $table->dropUnique('class_enrollments_class_year_roll_unique');
                $table->dropConstrainedForeignId('ended_by');
                $table->dropColumn(['ended_at', 'transition_reason']);
                $table->unique(['class_id', 'student_id'], 'class_enrollments_class_id_student_id_unique');
                $table->unique(['class_id', 'roll_number'], 'class_enrollments_class_id_roll_number_unique');
            });
        }
    }
};
