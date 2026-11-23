<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('school_incomes')) {
            Schema::table('school_incomes', function (Blueprint $table) {
                $table->index('date', 'school_incomes_date_idx');
            });
        }

        if (Schema::hasTable('school_expenses')) {
            Schema::table('school_expenses', function (Blueprint $table) {
                $table->index('date', 'school_expenses_date_idx');
            });
        }

        if (Schema::hasTable('fee_payments')) {
            Schema::table('fee_payments', function (Blueprint $table) {
                $table->index(['status', 'paid_at'], 'fee_payments_status_paid_idx');
            });
            if (Schema::hasColumn('fee_payments', 'refunded_at')) {
                Schema::table('fee_payments', function (Blueprint $table) {
                    $table->index(['status', 'refunded_at'], 'fee_payments_status_refund_idx');
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('school_incomes')) {
            Schema::table('school_incomes', function (Blueprint $table) {
                $table->dropIndex('school_incomes_date_idx');
            });
        }

        if (Schema::hasTable('school_expenses')) {
            Schema::table('school_expenses', function (Blueprint $table) {
                $table->dropIndex('school_expenses_date_idx');
            });
        }

        if (Schema::hasTable('fee_payments')) {
            Schema::table('fee_payments', function (Blueprint $table) {
                $table->dropIndex('fee_payments_status_paid_idx');
            });
            if (Schema::hasColumn('fee_payments', 'refunded_at')) {
                Schema::table('fee_payments', function (Blueprint $table) {
                    $table->dropIndex('fee_payments_status_refund_idx');
                });
            }
        }
    }
};
