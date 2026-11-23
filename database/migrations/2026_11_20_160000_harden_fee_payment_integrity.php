<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fee_payments')) {
            Schema::table('fee_payments', function (Blueprint $table) {
                if (!Schema::hasColumn('fee_payments', 'refund_reference')) {
                    $table->string('refund_reference', 150)->nullable()->unique();
                }
                if (!Schema::hasColumn('fee_payments', 'refund_reason')) {
                    $table->string('refund_reason', 255)->nullable();
                }
                if (!Schema::hasColumn('fee_payments', 'refunded_at')) {
                    $table->timestamp('refunded_at')->nullable();
                }
                $table->index(['student_id', 'status', 'paid_at'], 'fee_payments_student_status_paid_idx');
            });
        }

        if (Schema::hasTable('fee_invoices')) {
            Schema::table('fee_invoices', function (Blueprint $table) {
                $table->index(['student_id', 'status', 'due_date'], 'fee_invoices_student_status_due_idx');
                $table->index(['billing_type', 'billing_month'], 'fee_invoices_billing_period_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('fee_invoices')) {
            Schema::table('fee_invoices', function (Blueprint $table) {
                $table->dropIndex('fee_invoices_student_status_due_idx');
                $table->dropIndex('fee_invoices_billing_period_idx');
            });
        }
        if (Schema::hasTable('fee_payments')) {
            Schema::table('fee_payments', function (Blueprint $table) {
                $table->dropIndex('fee_payments_student_status_paid_idx');
                if (Schema::hasColumn('fee_payments', 'refund_reference')) $table->dropColumn('refund_reference');
                if (Schema::hasColumn('fee_payments', 'refund_reason')) $table->dropColumn('refund_reason');
                if (Schema::hasColumn('fee_payments', 'refunded_at')) $table->dropColumn('refunded_at');
            });
        }
    }
};
