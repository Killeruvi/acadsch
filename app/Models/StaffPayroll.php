<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffPayroll extends Model
{
    use HasFactory;

    protected $fillable = [
        'staff_id',
        'payroll_month',
        'basic_salary',
        'earnings',
        'deductions',
        'gross_salary',
        'net_salary',
        'payment_method',
        'payment_status',
        'paid_at',
        'paid_by',
        'notes',
        'salary_assignment_id', 'working_days', 'payable_days', 'basic_and_da', 'pf_wages', 'employee_pf', 'employer_pf', 'employer_eps', 'employer_epf_balance', 'edli_contribution', 'pf_admin_charge',
        'employee_esi', 'employer_esi', 'professional_tax', 'tds', 'employer_statutory_cost', 'variable_earnings', 'reimbursements', 'other_deductions', 'advance_recovery', 'approval_status', 'generated_by', 'approved_by', 'approved_at', 'payment_reference', 'paid_on', 'calculation_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'basic_salary' => 'decimal:2',
            'earnings' => 'decimal:2',
            'deductions' => 'decimal:2',
            'gross_salary' => 'decimal:2',
            'net_salary' => 'decimal:2',
            'paid_at' => 'datetime',
            'approved_at' => 'datetime',
            'paid_on' => 'date',
            'calculation_snapshot' => 'array',
        ];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }
}
