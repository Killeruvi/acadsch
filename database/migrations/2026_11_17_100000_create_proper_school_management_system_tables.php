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
        // 1. Extend academic_classes with class teacher and room
        Schema::table('academic_classes', function (Blueprint $table) {
            if (! Schema::hasColumn('academic_classes', 'class_teacher_id')) {
                $table->foreignId('class_teacher_id')->nullable()->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('academic_classes', 'room_number')) {
                $table->string('room_number', 50)->nullable()->default('Room 101');
            }
            if (! Schema::hasColumn('academic_classes', 'capacity')) {
                $table->integer('capacity')->nullable();
            }
        });

        // 2. Extend academic_subjects with assigned subject teacher and periods
        Schema::table('academic_subjects', function (Blueprint $table) {
            if (! Schema::hasColumn('academic_subjects', 'teacher_id')) {
                $table->foreignId('teacher_id')->nullable()->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('academic_subjects', 'weekly_periods')) {
                $table->integer('weekly_periods')->nullable();
            }
        });

        // 3. Extend class_enrollments with admission number and year
        Schema::table('class_enrollments', function (Blueprint $table) {
            if (! Schema::hasColumn('class_enrollments', 'admission_number')) {
                $table->string('admission_number', 50)->nullable();
            }
            if (! Schema::hasColumn('class_enrollments', 'academic_year')) {
                $table->string('academic_year', 30)->nullable()->default('2026-2027');
            }
        });

        // 4. Extend users with parent_id for Parent-Student linkage
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'parent_id')) {
                $table->foreignId('parent_id')->nullable()->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('users', 'admission_number')) {
                $table->string('admission_number', 50)->nullable();
            }
            if (! Schema::hasColumn('users', 'current_class_id')) {
                $table->foreignId('current_class_id')->nullable()->constrained('academic_classes')->nullOnDelete();
            }
            if (! Schema::hasColumn('users', 'roll_number')) {
                $table->integer('roll_number')->nullable();
            }
        });

        // 5. School Exam Terms (e.g., Term 1 / Midterm, Term 2 / Annual, Unit Test 1)
        Schema::create('school_exam_terms', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g. "Term 1 / Mid-Term Examination", "Annual Examination 2026-27"
            $table->string('academic_year', 30)->default('2026-2027');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 30)->default('completed'); // upcoming, ongoing, completed
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // 6. School Exam Schedules (datesheet per class & subject)
        Schema::create('school_exam_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_term_id')->constrained('school_exam_terms')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('academic_classes')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('academic_subjects')->cascadeOnDelete();
            $table->date('exam_date');
            $table->time('start_time')->default('09:00:00');
            $table->time('end_time')->default('12:00:00');
            $table->string('room_number', 50)->nullable()->default('Exam Hall A');
            $table->decimal('max_marks', 6, 2)->default(100.00);
            $table->decimal('pass_marks', 6, 2)->default(35.00);
            $table->timestamps();
        });

        // 7. School Exam Marks (marks entry per student per subject)
        Schema::create('school_exam_marks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_term_id')->constrained('school_exam_terms')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('academic_classes')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('academic_subjects')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('marks_obtained', 6, 2)->default(0.00);
            $table->decimal('max_marks', 6, 2)->default(100.00);
            $table->decimal('pass_marks', 6, 2)->default(35.00);
            $table->string('letter_grade', 10)->default('A');
            $table->string('teacher_remarks', 255)->nullable();
            $table->timestamps();

            $table->unique(['exam_term_id', 'class_id', 'subject_id', 'student_id'], 'exam_mark_unique');
        });

        // 8. Official Student Report Cards / Marksheets
        Schema::create('school_report_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_term_id')->constrained('school_exam_terms')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('academic_classes')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('total_max_marks', 8, 2)->default(500.00);
            $table->decimal('total_marks_obtained', 8, 2)->default(0.00);
            $table->decimal('percentage', 5, 2)->default(0.00);
            $table->decimal('gpa', 4, 2)->default(0.00);
            $table->string('overall_grade', 10)->default('A');
            $table->integer('class_rank')->nullable();
            $table->integer('total_working_days')->default(110);
            $table->integer('days_present')->default(105);
            $table->decimal('attendance_percentage', 5, 2)->default(95.45);
            $table->string('result_status', 20)->default('PASS'); // PASS, FAIL, COMPARTMENT
            $table->text('teacher_remarks')->nullable();
            $table->text('principal_remarks')->nullable();
            $table->boolean('is_published')->default(true);
            $table->date('generated_date')->nullable();
            $table->timestamps();

            $table->unique(['exam_term_id', 'class_id', 'student_id'], 'report_card_unique');
        });

        // 9. School Period Timetable (Daily Schedule: Periods 1 to 8 + Break)
        Schema::create('school_period_timetables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('academic_classes')->cascadeOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained('academic_subjects')->nullOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('day_of_week', 20); // Monday, Tuesday, Wednesday, Thursday, Friday, Saturday
            $table->integer('period_number'); // 1 through 8
            $table->time('start_time');
            $table->time('end_time');
            $table->string('room_number', 50)->nullable();
            $table->boolean('is_break')->default(false);
            $table->string('title')->nullable(); // e.g. "Morning Assembly", "Lunch Recess"
            $table->timestamps();

            $table->index(['class_id', 'day_of_week', 'period_number']);
        });

        // 10. School Fee Structures (Class-wise fee policies)
        Schema::create('school_fee_structures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('academic_classes')->cascadeOnDelete();
            $table->string('fee_title'); // e.g., "Monthly Tuition Fee", "Science Lab & Library Fee", "Annual Activity & Exam Fee"
            $table->string('fee_type', 40)->default('tuition'); // tuition, admission, exam, transport, lab, annual
            $table->decimal('amount', 10, 2)->default(0.00);
            $table->string('due_frequency', 30)->default('monthly'); // monthly, quarterly, annual
            $table->string('academic_year', 30)->default('2026-2027');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_fee_structures');
        Schema::dropIfExists('school_period_timetables');
        Schema::dropIfExists('school_report_cards');
        Schema::dropIfExists('school_exam_marks');
        Schema::dropIfExists('school_exam_schedules');
        Schema::dropIfExists('school_exam_terms');

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropForeign(['current_class_id']);
            $table->dropColumn(['parent_id', 'admission_number', 'current_class_id', 'roll_number']);
        });

        Schema::table('class_enrollments', function (Blueprint $table) {
            $table->dropColumn(['admission_number', 'academic_year']);
        });

        Schema::table('academic_subjects', function (Blueprint $table) {
            $table->dropForeign(['teacher_id']);
            $table->dropColumn(['teacher_id', 'weekly_periods']);
        });

        Schema::table('academic_classes', function (Blueprint $table) {
            $table->dropForeign(['class_teacher_id']);
            $table->dropColumn(['class_teacher_id', 'room_number', 'capacity']);
        });
    }
};
