<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('staff_salary_structures')) {
            Schema::create('staff_salary_structures', function (Blueprint $table) {
                $table->id();
                $table->string('code', 40)->unique();
                $table->string('name', 160);
                $table->decimal('basic_salary', 12, 2)->default(0);
                $table->decimal('dearness_allowance', 12, 2)->default(0);
                $table->decimal('hra', 12, 2)->default(0);
                $table->decimal('conveyance_allowance', 12, 2)->default(0);
                $table->decimal('other_allowances', 12, 2)->default(0);
                $table->decimal('fixed_monthly_earnings', 12, 2)->default(0);
                $table->decimal('fixed_monthly_deductions', 12, 2)->default(0);
                $table->date('effective_from');
                $table->date('effective_to')->nullable();
                $table->boolean('is_active')->default(true);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->index(['is_active', 'effective_from', 'effective_to'], 'salary_structures_effective_idx');
            });
        }

        if (! Schema::hasTable('staff_salary_assignments')) {
            Schema::create('staff_salary_assignments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('staff_id')->constrained('users')->restrictOnDelete();
                $table->foreignId('salary_structure_id')->constrained('staff_salary_structures')->restrictOnDelete();
                $table->date('effective_from');
                $table->date('effective_to')->nullable();
                $table->boolean('pf_applicable')->default(false);
                $table->boolean('pf_existing_member')->default(false);
                $table->boolean('eps_applicable')->default(false);
                $table->boolean('international_worker')->default(false);
                $table->boolean('pf_on_actual_wages')->default(false);
                $table->boolean('esi_applicable')->default(false);
                $table->boolean('esi_person_with_disability')->default(false);
                $table->date('esi_coverage_until')->nullable();
                $table->boolean('professional_tax_exempt')->default(false);
                $table->string('professional_tax_state', 2)->default('MP');
                $table->decimal('annual_salary_projection', 14, 2)->nullable();
                $table->decimal('annual_tds_projection', 12, 2)->nullable();
                $table->boolean('tds_nil_confirmed')->default(false);
                $table->string('tax_regime', 30)->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->index(['staff_id', 'effective_from', 'effective_to'], 'salary_assignment_effective_idx');
            });
        }

        if (! Schema::hasTable('staff_statutory_rule_versions')) {
            Schema::create('staff_statutory_rule_versions', function (Blueprint $table) {
                $table->id();
                $table->string('rule_code', 30);
                $table->string('jurisdiction', 10)->default('IN');
                $table->date('effective_from');
                $table->date('effective_to')->nullable();
                $table->decimal('employee_rate_percent', 8, 4)->default(0);
                $table->decimal('employer_rate_percent', 8, 4)->default(0);
                $table->decimal('wage_ceiling', 12, 2)->nullable();
                $table->decimal('daily_exemption_limit', 12, 2)->nullable();
                $table->json('rule_parameters')->nullable();
                $table->string('source_reference', 500)->nullable();
                $table->boolean('is_enabled')->default(false);
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->index(['rule_code', 'jurisdiction', 'effective_from', 'effective_to'], 'statutory_rule_effective_idx');
            });
        }

        Schema::table('staff_payrolls', function (Blueprint $table) {
            if (! Schema::hasColumn('staff_payrolls', 'salary_assignment_id')) {
                $table->foreignId('salary_assignment_id')->nullable()->after('staff_id')->constrained('staff_salary_assignments')->nullOnDelete();
            }
            if (! Schema::hasColumn('staff_payrolls', 'working_days')) {
                $table->decimal('working_days', 6, 2)->nullable();
            }
            if (! Schema::hasColumn('staff_payrolls', 'payable_days')) {
                $table->decimal('payable_days', 6, 2)->nullable();
            }
            if (! Schema::hasColumn('staff_payrolls', 'basic_and_da')) {
                $table->decimal('basic_and_da', 12, 2)->default(0);
            }
            if (! Schema::hasColumn('staff_payrolls', 'pf_wages')) {
                $table->decimal('pf_wages', 12, 2)->default(0);
            }
            if (! Schema::hasColumn('staff_payrolls', 'employee_pf')) {
                $table->decimal('employee_pf', 12, 2)->default(0);
            }
            if (! Schema::hasColumn('staff_payrolls', 'employer_pf')) {
                $table->decimal('employer_pf', 12, 2)->default(0);
            }
            if (! Schema::hasColumn('staff_payrolls', 'employer_eps')) {
                $table->decimal('employer_eps', 12, 2)->default(0);
            }
            if (! Schema::hasColumn('staff_payrolls', 'employer_epf_balance')) {
                $table->decimal('employer_epf_balance', 12, 2)->default(0);
            }
            if (! Schema::hasColumn('staff_payrolls', 'edli_contribution')) {
                $table->decimal('edli_contribution', 12, 2)->default(0);
            }
            if (! Schema::hasColumn('staff_payrolls', 'pf_admin_charge')) {
                $table->decimal('pf_admin_charge', 12, 2)->default(0);
            }
            if (! Schema::hasColumn('staff_payrolls', 'employee_esi')) {
                $table->decimal('employee_esi', 12, 2)->default(0);
            }
            if (! Schema::hasColumn('staff_payrolls', 'employer_esi')) {
                $table->decimal('employer_esi', 12, 2)->default(0);
            }
            if (! Schema::hasColumn('staff_payrolls', 'professional_tax')) {
                $table->decimal('professional_tax', 12, 2)->default(0);
            }
            if (! Schema::hasColumn('staff_payrolls', 'tds')) {
                $table->decimal('tds', 12, 2)->default(0);
            }
            if (! Schema::hasColumn('staff_payrolls', 'employer_statutory_cost')) {
                $table->decimal('employer_statutory_cost', 12, 2)->default(0);
            }
            if (! Schema::hasColumn('staff_payrolls', 'approval_status')) {
                $table->string('approval_status', 20)->default('draft');
            }
            if (! Schema::hasColumn('staff_payrolls', 'generated_by')) {
                $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('staff_payrolls', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('staff_payrolls', 'approved_at')) {
                $table->timestamp('approved_at')->nullable();
            }
            if (! Schema::hasColumn('staff_payrolls', 'payment_reference')) {
                $table->string('payment_reference', 120)->nullable();
            }
            if (! Schema::hasColumn('staff_payrolls', 'paid_on')) {
                $table->date('paid_on')->nullable();
            }
            if (! Schema::hasColumn('staff_payrolls', 'calculation_snapshot')) {
                $table->json('calculation_snapshot')->nullable();
            }
        });
        try {
            Schema::table('staff_payrolls', function (Blueprint $table) {
                $table->index(['approval_status', 'payroll_month'], 'staff_payroll_approval_month_idx');
            });
        } catch (Throwable $_) {
        }

        DB::table('staff_payrolls')->where('payment_status', 'paid')->update(['approval_status' => 'approved']);
        DB::table('staff_payrolls')->where('payment_status', '<>', 'paid')->update(['approval_status' => 'legacy']);

        // Effective-dated baseline rule versions — inserted individually for SQLite column consistency.
        $now = now();
        foreach ([
            ['rule_code' => 'EPF', 'jurisdiction' => 'IN', 'effective_from' => '2014-09-01', 'effective_to' => '2026-09-16', 'employee_rate_percent' => 12.00, 'employer_rate_percent' => 12.00, 'wage_ceiling' => 15000.00, 'daily_exemption_limit' => null, 'rule_parameters' => json_encode(['eps_rate_percent' => 8.33, 'edli_rate_percent' => 0.50, 'admin_charge_percent' => 0.50, 'note' => 'Historical baseline; verify employee membership and exceptions.']), 'source_reference' => 'EPFO contribution rate schedule; historical statutory wage ceiling', 'is_enabled' => false, 'created_at' => $now, 'updated_at' => $now],
            ['rule_code' => 'EPF', 'jurisdiction' => 'IN', 'effective_from' => '2026-09-17', 'effective_to' => null, 'employee_rate_percent' => 12.00, 'employer_rate_percent' => 12.00, 'wage_ceiling' => 25000.00, 'daily_exemption_limit' => null, 'rule_parameters' => json_encode(['eps_rate_percent' => 8.33, 'edli_rate_percent' => 0.50, 'admin_charge_percent' => 0.50, 'note' => 'Employer allocation and membership exceptions require payroll professional confirmation.']), 'source_reference' => 'PIB release PRID 2311548; EPFO contribution rate schedule; Gazette notification S.O. 5109(E) dated 17-09-2026', 'is_enabled' => false, 'created_at' => $now, 'updated_at' => $now],
            ['rule_code' => 'ESI', 'jurisdiction' => 'IN', 'effective_from' => '2019-07-01', 'effective_to' => null, 'employee_rate_percent' => 0.75, 'employer_rate_percent' => 3.25, 'wage_ceiling' => 21000.00, 'daily_exemption_limit' => 176.00, 'rule_parameters' => json_encode(['disability_wage_ceiling' => 25000]), 'source_reference' => 'ESIC Know Your ESI Scheme; ESIC Rule 51 contribution rates', 'is_enabled' => false, 'created_at' => $now, 'updated_at' => $now],
            ['rule_code' => 'MP_PT', 'jurisdiction' => 'MP', 'effective_from' => '2018-04-01', 'effective_to' => null, 'employee_rate_percent' => 0, 'employer_rate_percent' => 0, 'wage_ceiling' => null, 'daily_exemption_limit' => null, 'rule_parameters' => json_encode(['annual_salary_bands' => [['up_to' => 225000, 'annual_tax' => 0], ['up_to' => 300000, 'annual_tax' => 1500], ['up_to' => 400000, 'annual_tax' => 2000], ['up_to' => null, 'annual_tax' => 2500]], 'monthly_distribution' => '11 equal rounded instalments; balance in final month']), 'source_reference' => 'MP Commercial Tax Department MPCTD Registration Checklist & Rates; MP Vritti Kar Adhiniyam 1995 Schedule as amended by MP Act 20 of 2018', 'is_enabled' => false, 'created_at' => $now, 'updated_at' => $now],
            ['rule_code' => 'TDS', 'jurisdiction' => 'IN', 'effective_from' => '2026-04-01', 'effective_to' => null, 'employee_rate_percent' => 0, 'employer_rate_percent' => 0, 'wage_ceiling' => null, 'daily_exemption_limit' => null, 'rule_parameters' => json_encode(['calculation' => 'annual_projection_input_required', 'law' => 'Income-tax Act, 2025 section 392(1) for payments from 01-04-2026', 'do_not_auto_calculate_without_tax_regime_and_declarations' => true]), 'source_reference' => 'Income Tax Department TDS Compliance FAQ, updated for Income-tax Act 2025', 'is_enabled' => false, 'created_at' => $now, 'updated_at' => $now],
        ] as $row) {
            DB::table('staff_statutory_rule_versions')->insertOrIgnore($row);
        }
    }

    public function down(): void
    {
        Schema::table('staff_payrolls', function (Blueprint $table) {
            $table->dropIndex('staff_payroll_approval_month_idx');
            $table->dropConstrainedForeignId('approved_by');
            $table->dropConstrainedForeignId('generated_by');
            $table->dropConstrainedForeignId('salary_assignment_id');
            foreach (['working_days', 'payable_days', 'basic_and_da', 'pf_wages', 'employee_pf', 'employer_pf', 'employer_eps', 'employer_epf_balance', 'edli_contribution', 'pf_admin_charge', 'employee_esi', 'employer_esi', 'professional_tax', 'tds', 'employer_statutory_cost', 'approval_status', 'approved_at', 'payment_reference', 'paid_on', 'calculation_snapshot'] as $column) {
                $table->dropColumn($column);
            }
        });
        Schema::dropIfExists('staff_statutory_rule_versions');
        Schema::dropIfExists('staff_salary_assignments');
        Schema::dropIfExists('staff_salary_structures');
    }
};
