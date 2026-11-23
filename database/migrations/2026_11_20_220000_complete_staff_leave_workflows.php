<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Replace the original single-row-per-type constraint with effective-dated policy versions.
        Schema::table('staff_leave_policies', function (Blueprint $table) {
            $table->dropUnique('staff_leave_policies_leave_type_unique');
            $table->unique(['leave_type', 'effective_from'], 'staff_leave_policy_version_unique');
        });

        Schema::table('staff_leave_requests', function (Blueprint $table) {
            $table->string('leave_category', 40)->default('annual')->after('leave_type');
        });

        Schema::create('staff_leave_encashment_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('users')->restrictOnDelete();
            $table->string('leave_type', 40);
            $table->unsignedSmallInteger('leave_year');
            $table->decimal('days_requested', 7, 2);
            $table->char('payroll_month', 7);
            $table->decimal('approved_amount', 12, 2)->nullable();
            $table->string('status', 16)->default('pending'); // pending, approved, rejected, applied, cancelled
            $table->string('reason', 1000)->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_remarks')->nullable();
            $table->foreignId('payroll_adjustment_id')->nullable()->constrained('staff_payroll_adjustments')->nullOnDelete();
            $table->timestamps();
            $table->index(['staff_id', 'leave_year', 'status'], 'staff_leave_encash_staff_year_idx');
            $table->index(['status', 'payroll_month'], 'staff_leave_encash_review_idx');
        });

        // Preserve legacy paid-leave policy configuration under the new default category.
        $legacyPaidPolicy = DB::table('staff_leave_policies')->where('leave_type', 'paid')->first();
        $annualPolicy = DB::table('staff_leave_policies')->where('leave_type', 'annual')->exists();
        if ($legacyPaidPolicy && !$annualPolicy) {
            DB::table('staff_leave_policies')->where('id', $legacyPaidPolicy->id)->update(['leave_type' => 'annual']);
        } elseif ($legacyPaidPolicy && $annualPolicy) {
            DB::table('staff_leave_policies')->where('id', $legacyPaidPolicy->id)->update(['is_enabled' => false]);
        }

        // Keep legacy records mapped to the default annual category.
        DB::table('staff_leave_requests')->whereNull('leave_category')->update(['leave_category' => 'annual']);
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_leave_encashment_requests');
        Schema::table('staff_leave_requests', function (Blueprint $table) {
            $table->dropColumn('leave_category');
        });
        Schema::table('staff_leave_policies', function (Blueprint $table) {
            $table->dropUnique('staff_leave_policy_version_unique');
            $table->unique('leave_type');
        });
    }
};
