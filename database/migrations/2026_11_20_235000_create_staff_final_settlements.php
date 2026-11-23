<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('staff_final_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->unique()->constrained('users')->restrictOnDelete();
            $table->date('last_working_date');
            $table->date('settlement_date')->nullable();
            $table->string('status', 20)->default('draft'); // draft, approved, partially_paid, paid, cancelled
            $table->decimal('total_earnings', 14, 2)->default(0);
            $table->decimal('total_deductions', 14, 2)->default(0);
            $table->decimal('net_payable', 14, 2)->default(0);
            $table->decimal('amount_paid', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'last_working_date'], 'staff_final_settlement_status_idx');
        });

        Schema::create('staff_final_settlement_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('settlement_id')->constrained('staff_final_settlements')->restrictOnDelete();
            $table->string('item_type', 40); // salary_due, leave_encashment, gratuity, notice_pay, bonus, reimbursement, advance_recovery, tax, other
            $table->string('description', 180);
            $table->enum('direction', ['earning', 'deduction']);
            $table->decimal('amount', 14, 2);
            $table->boolean('taxable')->default(false);
            $table->string('basis_reference', 240)->nullable();
            $table->text('review_notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['settlement_id', 'direction'], 'staff_final_settlement_item_idx');
        });

        Schema::create('staff_final_settlement_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('settlement_id')->constrained('staff_final_settlements')->restrictOnDelete();
            $table->decimal('amount', 14, 2);
            $table->date('paid_on');
            $table->string('payment_method', 30);
            $table->string('payment_reference', 140);
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['settlement_id', 'payment_reference'], 'staff_final_settlement_payment_ref_unique');
            $table->index(['settlement_id', 'paid_on'], 'staff_final_settlement_payment_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_final_settlement_payments');
        Schema::dropIfExists('staff_final_settlement_items');
        Schema::dropIfExists('staff_final_settlements');
    }
};
