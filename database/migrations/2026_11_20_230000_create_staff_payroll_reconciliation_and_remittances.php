<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('staff_payroll_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->string('payroll_month', 7)->unique();
            $table->unsignedInteger('payroll_count')->default(0);
            $table->decimal('gross_total', 14, 2)->default(0);
            $table->decimal('net_total', 14, 2)->default(0);
            $table->decimal('employee_deductions_total', 14, 2)->default(0);
            $table->decimal('employer_statutory_total', 14, 2)->default(0);
            $table->json('control_totals');
            $table->string('status', 20)->default('reconciled');
            $table->foreignId('reconciled_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('reconciled_at');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('staff_statutory_remittances', function (Blueprint $table) {
            $table->id();
            $table->string('rule_code', 20);
            $table->string('jurisdiction', 10)->default('IN');
            $table->string('period_month', 7);
            $table->decimal('amount_due', 14, 2)->default(0);
            $table->decimal('amount_paid', 14, 2)->default(0);
            $table->date('due_date')->nullable();
            $table->date('paid_on')->nullable();
            $table->string('challan_number', 120)->nullable();
            $table->string('payment_reference', 120)->nullable();
            $table->string('status', 20)->default('pending');
            $table->text('variance_reason')->nullable();
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->unique(['rule_code', 'jurisdiction', 'period_month'], 'staff_remittance_period_unique');
            $table->index(['status', 'due_date'], 'staff_remittance_due_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_statutory_remittances');
        Schema::dropIfExists('staff_payroll_reconciliations');
    }
};
