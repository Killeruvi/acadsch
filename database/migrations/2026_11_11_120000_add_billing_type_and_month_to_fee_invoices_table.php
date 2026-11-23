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
        Schema::table('fee_invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('fee_invoices', 'billing_type')) {
                $table->string('billing_type', 30)->default('manual')->after('status');
            }
            if (!Schema::hasColumn('fee_invoices', 'billing_month')) {
                $table->string('billing_month', 20)->nullable()->after('billing_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fee_invoices', function (Blueprint $table) {
            if (Schema::hasColumn('fee_invoices', 'billing_month')) {
                $table->dropColumn('billing_month');
            }
            if (Schema::hasColumn('fee_invoices', 'billing_type')) {
                $table->dropColumn('billing_type');
            }
        });
    }
};
