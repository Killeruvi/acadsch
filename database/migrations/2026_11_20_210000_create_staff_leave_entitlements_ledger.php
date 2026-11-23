<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('staff_leave_policies', function (Blueprint $table) {
            $table->id();
            $table->string('leave_type', 40)->unique();
            $table->decimal('annual_entitlement', 7, 2)->default(0);
            $table->decimal('monthly_accrual', 7, 4)->default(0);
            $table->decimal('maximum_carry_forward', 7, 2)->default(0);
            $table->boolean('allow_encashment')->default(false);
            $table->decimal('maximum_encashment', 7, 2)->default(0);
            $table->boolean('is_enabled')->default(false);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->foreignId('configured_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['is_enabled', 'effective_from', 'effective_to'], 'staff_leave_policy_active_idx');
        });

        Schema::create('staff_leave_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('users')->restrictOnDelete();
            $table->string('leave_type', 40);
            $table->unsignedSmallInteger('leave_year');
            $table->decimal('opening_balance', 8, 2)->default(0);
            $table->decimal('accrued', 8, 2)->default(0);
            $table->decimal('used', 8, 2)->default(0);
            $table->decimal('encashed', 8, 2)->default(0);
            $table->decimal('adjusted', 8, 2)->default(0);
            $table->timestamps();
            $table->unique(['staff_id', 'leave_type', 'leave_year'], 'staff_leave_balance_period_unique');
            $table->index(['leave_year', 'leave_type'], 'staff_leave_balance_period_idx');
        });

        Schema::create('staff_leave_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_leave_balance_id')->constrained('staff_leave_balances')->restrictOnDelete();
            $table->foreignId('staff_id')->constrained('users')->restrictOnDelete();
            $table->string('leave_type', 40);
            $table->string('transaction_type', 24); // accrual, usage, carry_forward, encashment, adjustment, reversal
            $table->decimal('quantity', 8, 2);
            $table->string('source_type', 40)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('idempotency_key', 120)->unique();
            $table->text('notes')->nullable();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['staff_id', 'leave_type', 'created_at'], 'staff_leave_tx_staff_type_idx');
            $table->index(['source_type', 'source_id'], 'staff_leave_tx_source_idx');
        });

        Schema::table('staff_leave_requests', function (Blueprint $table) {
            $table->decimal('working_days', 7, 2)->nullable()->after('end_date');
            $table->string('balance_status', 20)->default('not_applicable')->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('staff_leave_requests', function (Blueprint $table) {
            $table->dropColumn(['working_days', 'balance_status']);
        });
        Schema::dropIfExists('staff_leave_transactions');
        Schema::dropIfExists('staff_leave_balances');
        Schema::dropIfExists('staff_leave_policies');
    }
};
