<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_payroll_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('users')->restrictOnDelete();
            $table->string('adjustment_type', 24); // overtime, bonus, incentive, arrears, reimbursement, deduction
            $table->string('description', 180);
            $table->decimal('amount', 12, 2);
            $table->char('payroll_month', 7);
            $table->string('status', 16)->default('pending'); // pending, approved, rejected, applied
            $table->boolean('taxable')->default(true);
            $table->foreignId('submitted_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('staff_payroll_id')->nullable()->constrained('staff_payrolls')->nullOnDelete();
            $table->timestamps();
            $table->index(['staff_id', 'payroll_month', 'status'], 'staff_adjustment_period_idx');
            $table->index(['status', 'payroll_month'], 'staff_adjustment_review_idx');
        });

        Schema::create('staff_salary_advances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('users')->restrictOnDelete();
            $table->decimal('principal_amount', 12, 2);
            $table->decimal('monthly_installment', 12, 2);
            $table->decimal('repaid_amount', 12, 2)->default(0);
            $table->date('disbursed_on')->nullable();
            $table->string('disbursement_reference', 120)->nullable();
            $table->char('first_deduction_month', 7);
            $table->string('status', 16)->default('pending'); // pending, active, completed, cancelled
            $table->string('reference', 120)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->index(['staff_id', 'status', 'first_deduction_month'], 'staff_advance_due_idx');
        });

        Schema::table('staff_payrolls', function (Blueprint $table) {
            $table->decimal('variable_earnings', 12, 2)->default(0);
            $table->decimal('reimbursements', 12, 2)->default(0);
            $table->decimal('other_deductions', 12, 2)->default(0);
            $table->decimal('advance_recovery', 12, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('staff_payrolls', function (Blueprint $table) {
            $table->dropColumn(['variable_earnings', 'reimbursements', 'other_deductions', 'advance_recovery']);
        });
        Schema::dropIfExists('staff_salary_advances');
        Schema::dropIfExists('staff_payroll_adjustments');
    }
};
