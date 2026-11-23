<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    public function up(): void
    {
        $duplicates = DB::table('staff_payrolls')
            ->select('staff_id', 'payroll_month', DB::raw('COUNT(*) as row_count'))
            ->groupBy('staff_id', 'payroll_month')
            ->havingRaw('COUNT(*) > 1')
            ->limit(10)
            ->get();

        if ($duplicates->isNotEmpty()) {
            throw new RuntimeException('Cannot add the staff/month payroll uniqueness constraint: duplicate payroll rows exist. Reconcile these records manually first: ' . $duplicates->map(fn ($r) => "staff {$r->staff_id}, month {$r->payroll_month} ({$r->row_count} rows)")->implode('; '));
        }

        Schema::table('staff_payrolls', function (Blueprint $table) {
            $table->dropForeign(['staff_id']);
            $table->foreign('staff_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreignId('paid_by')->nullable()->after('paid_at')->constrained('users')->nullOnDelete();
            $table->unique(['staff_id', 'payroll_month'], 'staff_payroll_staff_month_unique');
            $table->index(['payment_status', 'payroll_month'], 'staff_payroll_status_month_idx');
        });

        Schema::create('staff_payroll_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_payroll_id')->nullable()->constrained('staff_payrolls')->nullOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_type', 40);
            $table->json('before_data')->nullable();
            $table->json('after_data')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['staff_payroll_id', 'created_at'], 'staff_payroll_audit_record_created_idx');
            $table->index(['actor_id', 'created_at'], 'staff_payroll_audit_actor_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_payroll_audits');
        Schema::table('staff_payrolls', function (Blueprint $table) {
            $table->dropUnique('staff_payroll_staff_month_unique');
            $table->dropIndex('staff_payroll_status_month_idx');
            $table->dropConstrainedForeignId('paid_by');
            $table->dropForeign(['staff_id']);
            $table->foreign('staff_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
