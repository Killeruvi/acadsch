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
        // 1. Academy Institutional Settings (Configurable by Admin)
        Schema::create('academy_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // 2. Official Notice Board & Academy Circulars
        Schema::create('academy_notices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->text('content');
            $table->string('priority', 20)->default('normal'); // normal, high, urgent
            $table->string('target_audience', 20)->default('all'); // all, students, teachers
            $table->boolean('is_pinned')->default(false);
            $table->timestamp('published_at')->useCurrent();
            $table->string('attachment_url')->nullable();
            $table->timestamps();
        });

        // 3. Student Attendance Leave Applications & Approvals
        Schema::create('student_leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('type', 30)->default('medical'); // medical, family, academic, other
            $table->text('reason');
            $table->string('status', 20)->default('pending'); // pending, approved, rejected
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('teacher_remarks')->nullable();
            $table->timestamps();
        });

        // 4. Enhance Curriculum Topics with Live Progress Tracking
        Schema::table('curriculum_topics', function (Blueprint $table) {
            $table->string('status', 20)->default('not_started')->after('topic_name'); // not_started, in_progress, completed
            $table->timestamp('completed_at')->nullable()->after('status');
            $table->text('study_notes')->nullable()->after('completed_at');
            $table->string('resource_url')->nullable()->after('study_notes');
        });

        // 5. Enhance DPP / Tests with Negative Marking
        Schema::table('daily_dpps', function (Blueprint $table) {
            $table->decimal('negative_marks', 4, 2)->default(0.00)->after('pass_percentage'); // e.g. 1.00 for -1 mark
        });

        // 6. Enhance DPP Submissions with Rank, Percentile & Detailed Breakdown
        Schema::table('dpp_submissions', function (Blueprint $table) {
            $table->integer('rank')->nullable()->after('is_passed');
            $table->decimal('percentile', 5, 2)->nullable()->after('rank');
            $table->integer('correct_count')->default(0)->after('percentile');
            $table->integer('incorrect_count')->default(0)->after('correct_count');
            $table->integer('unattempted_count')->default(0)->after('incorrect_count');
            $table->decimal('negative_deduction', 5, 2)->default(0.00)->after('unattempted_count');
        });

        // 7. Enhance Homework Submissions with Late Tagging & Resubmission
        Schema::table('homework_submissions', function (Blueprint $table) {
            $table->boolean('is_late')->default(false)->after('checked_at');
            $table->integer('resubmission_count')->default(0)->after('is_late');
        });

        // 8. Enhance Fee Invoices with Itemized Fee Heads & Late Fines
        Schema::table('fee_invoices', function (Blueprint $table) {
            $table->json('fee_breakdown')->nullable()->after('total_due'); // {"Tuition": 12000, "Lab": 3000, ...}
            $table->decimal('late_fine_amount', 10, 2)->default(0.00)->after('fee_breakdown');
            $table->integer('installment_number')->default(1)->after('late_fine_amount');
            $table->integer('total_installments')->default(1)->after('installment_number');
        });

        // 9. Enhance Fee Payments with Transaction Details & UPI VPA
        Schema::table('fee_payments', function (Blueprint $table) {
            $table->string('transaction_id')->nullable()->after('receipt_number');
            $table->string('payment_gateway', 30)->default('UPI')->after('transaction_id'); // UPI, NetBanking, Card, Cash
            $table->string('upi_vpa')->nullable()->after('payment_gateway');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fee_payments', function (Blueprint $table) {
            $table->dropColumn(['transaction_id', 'payment_gateway', 'upi_vpa']);
        });

        Schema::table('fee_invoices', function (Blueprint $table) {
            $table->dropColumn(['fee_breakdown', 'late_fine_amount', 'installment_number', 'total_installments']);
        });

        Schema::table('homework_submissions', function (Blueprint $table) {
            $table->dropColumn(['is_late', 'resubmission_count']);
        });

        Schema::table('dpp_submissions', function (Blueprint $table) {
            $table->dropColumn(['rank', 'percentile', 'correct_count', 'incorrect_count', 'unattempted_count', 'negative_deduction']);
        });

        Schema::table('daily_dpps', function (Blueprint $table) {
            $table->dropColumn('negative_marks');
        });

        Schema::table('curriculum_topics', function (Blueprint $table) {
            $table->dropColumn(['status', 'completed_at', 'study_notes', 'resource_url']);
        });

        Schema::dropIfExists('student_leave_requests');
        Schema::dropIfExists('academy_notices');
        Schema::dropIfExists('academy_settings');
    }
};
