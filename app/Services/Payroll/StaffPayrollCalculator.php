<?php

namespace App\Services\Payroll;

use App\Models\AcademicHoliday;
use App\Models\StaffAttendanceRecord;
use App\Models\StaffLeaveRequest;
use App\Models\StaffPayroll;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class StaffPayrollCalculator
{
    public function generate(User $staff, string $month, int $actorId): StaffPayroll
    {
        return DB::transaction(function () use ($staff, $month, $actorId) {
            $periodStart = Carbon::createFromFormat('Y-m-d', $month . '-01')->startOfMonth();
            $periodEnd = $periodStart->copy()->endOfMonth();
            if ($periodEnd->gte(today())) {
                throw new RuntimeException('Payroll can only be generated for a closed month. Complete and review attendance first.');
            }

            $employment = DB::table('staff_employment_profiles')->where('staff_id', $staff->id)->lockForUpdate()->first();
            if (!$employment) throw new RuntimeException('Create and activate the staff employment profile before generating payroll.');
            if ($employment->employment_status === 'onboarding') throw new RuntimeException('Payroll is blocked until required onboarding is completed and employment is activated.');
            if ($employment->employment_status === 'separated' && (!$employment->last_working_date || Carbon::parse($employment->last_working_date)->lt($periodStart))) throw new RuntimeException('The employee was separated before this payroll period.');
            if (Carbon::parse($employment->joining_date)->gt($periodEnd)) throw new RuntimeException('The employee joining date is after this payroll period.');

            $assignment = DB::table('staff_salary_assignments as a')
                ->join('staff_salary_structures as s', 's.id', '=', 'a.salary_structure_id')
                ->where('a.staff_id', $staff->id)
                ->whereDate('a.effective_from', '<=', $periodEnd->toDateString())
                ->where(function ($q) use ($periodStart) { $q->whereNull('a.effective_to')->orWhereDate('a.effective_to', '>=', $periodStart->toDateString()); })
                ->where('s.is_active', true)
                ->whereDate('s.effective_from', '<=', $periodEnd->toDateString())
                ->where(function ($q) use ($periodStart) { $q->whereNull('s.effective_to')->orWhereDate('s.effective_to', '>=', $periodStart->toDateString()); })
                ->orderByDesc('a.effective_from')->select('a.*', 's.code as structure_code', 's.name as structure_name', 's.basic_salary', 's.dearness_allowance', 's.hra', 's.conveyance_allowance', 's.other_allowances', 's.fixed_monthly_earnings', 's.fixed_monthly_deductions')
                ->lockForUpdate()->first();
            if (!$assignment) throw new RuntimeException('No active salary structure assignment covers this payroll month. Assign an effective-dated structure first.');

            $weekends = config('attendance.weekend_days', ['Sunday']);
            $workingDates = [];
            $employmentStart = Carbon::parse($employment->joining_date)->max($periodStart->copy());
            $employmentEnd = $employment->last_working_date ? Carbon::parse($employment->last_working_date)->min($periodEnd->copy()) : $periodEnd->copy();
            for ($date = $employmentStart->copy(); $date->lte($employmentEnd); $date->addDay()) {
                if (in_array($date->format('l'), $weekends, true) || AcademicHoliday::findHolidayForDate($date)) continue;
                $workingDates[] = $date->toDateString();
            }
            $attendance = StaffAttendanceRecord::where('staff_id', $staff->id)->whereBetween('attendance_date', [$periodStart->toDateString(), $periodEnd->toDateString()])->get()->keyBy(fn ($r) => Carbon::parse($r->attendance_date)->toDateString());
            $leaveRows = StaffLeaveRequest::where('staff_id', $staff->id)->where('status', 'approved')->whereDate('start_date', '<=', $periodEnd)->whereDate('end_date', '>=', $periodStart)->get();
            $payableDays = 0.0;
            $missing = [];
            foreach ($workingDates as $date) {
                $record = $attendance->get($date);
                if (!$record) { $missing[] = $date; continue; }
                if ($record->status === 'present') $payableDays += 1;
                elseif ($record->status === 'half_day') $payableDays += 0.5;
                elseif ($record->status === 'absent') $payableDays += 0;
                elseif ($record->status === 'leave') {
                    $leave = $leaveRows->first(fn ($r) => Carbon::parse($r->start_date)->toDateString() <= $date && Carbon::parse($r->end_date)->toDateString() >= $date);
                    if (!$leave) throw new RuntimeException("Attendance marks {$date} as leave but no approved leave request covers that date.");
                    if ($leave->leave_type === 'paid') $payableDays += 1;
                } else throw new RuntimeException("Unsupported attendance status on {$date}; correct attendance before payroll.");
            }
            if ($missing) throw new RuntimeException('Attendance is incomplete. Missing working-day records: ' . implode(', ', array_slice($missing, 0, 12)) . (count($missing) > 12 ? '…' : ''));
            $workingDays = count($workingDates);
            if ($workingDays <= 0) throw new RuntimeException('No working days were found for this payroll month.');
            $factor = $payableDays / $workingDays;
            $round = fn ($v) => round((float) $v, 2, PHP_ROUND_HALF_UP);

            $basic = $round($assignment->basic_salary * $factor);
            $da = $round($assignment->dearness_allowance * $factor);
            $hra = $round($assignment->hra * $factor);
            $conveyance = $round($assignment->conveyance_allowance * $factor);
            $other = $round($assignment->other_allowances * $factor);
            $fixedEarnings = $round($assignment->fixed_monthly_earnings * $factor);
            $fixedDeductions = $round($assignment->fixed_monthly_deductions * $factor);
            $adjustments = DB::table('staff_payroll_adjustments')->where('staff_id', $staff->id)->where('payroll_month', $month)->where('status', 'approved')->lockForUpdate()->get();
            $variableEarnings = $round($adjustments->whereIn('adjustment_type', ['overtime','bonus','incentive','arrears','leave_encashment'])->sum('amount'));
            $reimbursements = $round($adjustments->where('adjustment_type', 'reimbursement')->sum('amount'));
            $otherDeductions = $round($adjustments->where('adjustment_type', 'deduction')->sum('amount'));
            $gross = $round($basic + $da + $hra + $conveyance + $other + $fixedEarnings + $variableEarnings);
            $advanceRows = DB::table('staff_salary_advances')->where('staff_id', $staff->id)->where('status', 'active')->where('first_deduction_month', '<=', $month)->lockForUpdate()->get();
            $advanceRecovery = 0.0;
            foreach ($advanceRows as $advanceRow) {
                $balance = $round(max(0, (float) $advanceRow->principal_amount - (float) $advanceRow->repaid_amount));
                $advanceRecovery += min($balance, (float) $advanceRow->monthly_installment);
            }
            $advanceRecovery = $round($advanceRecovery);
            $basicDa = $round($basic + $da);
            $allowancesForWageTest = $round(max(0, $gross - $basicDa));
            // Labour Code wage definition: allowances beyond 50% of remuneration are added back.
            $statutoryWages = $round($basicDa + max(0, $allowancesForWageTest - ($gross * 0.50)));
            $pfWages = 0.0; $employeePf = 0.0; $employerPf = 0.0;
            $employerEps = $employerEpfBalance = $edli = $pfAdmin = 0.0;
            if ($assignment->pf_applicable) {
                if ($assignment->eps_applicable && !$assignment->pf_existing_member) throw new RuntimeException('EPS eligibility was selected without confirming existing EPF membership. Review employee membership history.');
                $rule = $this->rule('EPF', 'IN', $periodEnd);
                $pfWages = ($assignment->international_worker || $assignment->pf_on_actual_wages) ? $statutoryWages : min($statutoryWages, (float) $rule->wage_ceiling);
                $employeePf = $round($pfWages * ((float) $rule->employee_rate_percent / 100));
                $employerPf = $round($pfWages * ((float) $rule->employer_rate_percent / 100));
                $epsRate = (float) ($rule->rule_parameters->eps_rate_percent ?? 0);
                $epsWageBase = $assignment->eps_applicable ? min($pfWages, (float) $rule->wage_ceiling) : 0;
                $employerEps = $round($epsWageBase * $epsRate / 100);
                $employerEpfBalance = $round(max(0, $employerPf - $employerEps));
                $edli = $round(min($pfWages, (float) $rule->wage_ceiling) * ((float) ($rule->rule_parameters->edli_rate_percent ?? 0) / 100));
                $pfAdmin = $round($pfWages * ((float) ($rule->rule_parameters->admin_charge_percent ?? 0) / 100));
            }
            $employeeEsi = 0.0; $employerEsi = 0.0;
            if ($assignment->esi_applicable) {
                $rule = $this->rule('ESI', 'IN', $periodEnd);
                $ceiling = $assignment->esi_person_with_disability ? (float) ($rule->rule_parameters->disability_wage_ceiling ?? 25000) : (float) $rule->wage_ceiling;
                if ($statutoryWages <= $ceiling || ($assignment->esi_coverage_until && Carbon::parse($assignment->esi_coverage_until)->gte($periodEnd))) {
                    $employeeEsi = $round($statutoryWages * ((float) $rule->employee_rate_percent / 100));
                    $dailyExemption = (float) ($rule->daily_exemption_limit ?? 0);
                    $employeeEsi = ($dailyExemption > 0 && $statutoryWages / max(1, $workingDays) <= $dailyExemption) ? 0 : $employeeEsi;
                    $employerEsi = $round($statutoryWages * ((float) $rule->employer_rate_percent / 100));
                }
            }
            $professionalTax = $this->professionalTax($assignment, $gross, $periodEnd);
            if ($assignment->annual_tds_projection === null && !$assignment->tds_nil_confirmed) throw new RuntimeException('TDS cannot silently default to zero. Enter a verified annual TDS projection or explicitly confirm nil TDS after reviewing the employee tax declarations and regime.');
            $this->rule('TDS', 'IN', $periodEnd);
            $tds = 0.0;
            if ($assignment->annual_tds_projection !== null) {
                $fiscalStartYear = ((int) $periodEnd->format('n')) >= 4 ? (int) $periodEnd->format('Y') : ((int) $periodEnd->format('Y') - 1);
                $fiscalStart = Carbon::create($fiscalStartYear, 4, 1)->toDateString();
                $alreadyDeducted = (float) StaffPayroll::where('staff_id',$staff->id)->whereBetween('payroll_month', [substr($fiscalStart,0,7), $periodStart->copy()->subMonth()->format('Y-m')])->where(function($q){$q->where('approval_status','approved')->orWhere('payment_status','paid');})->sum('tds');
                $fiscalMonth = ((int) $periodEnd->format('n') >= 4) ? ((int) $periodEnd->format('n') - 3) : ((int) $periodEnd->format('n') + 9);
                $monthsRemaining = max(1, 13 - $fiscalMonth);
                $tds = $round(max(0, (float) $assignment->annual_tds_projection - $alreadyDeducted) / $monthsRemaining);
            }
            $deductions = $round($fixedDeductions + $employeePf + $employeeEsi + $professionalTax + $tds + $otherDeductions + $advanceRecovery);
            if ($deductions > $gross) throw new RuntimeException('Calculated deductions exceed gross pay. Review the salary structure and tax declarations.');
            $net = $round($gross + $reimbursements - $deductions);

            $existing = StaffPayroll::where('staff_id', $staff->id)->where('payroll_month', $month)->lockForUpdate()->first();
            if ($existing && ($existing->payment_status === 'paid' || $existing->approval_status === 'approved')) throw new RuntimeException('Approved or paid payroll is immutable. Use a documented adjustment in a later payroll period.');
            $before = $existing?->only(['gross_salary', 'deductions', 'net_salary', 'approval_status', 'payment_status']);
            $payload = [
                'salary_assignment_id' => $assignment->id, 'basic_salary' => $basic, 'earnings' => $round($gross - $basic), 'deductions' => $deductions,
                'variable_earnings' => $variableEarnings, 'reimbursements' => $reimbursements, 'other_deductions' => $otherDeductions, 'advance_recovery' => $advanceRecovery,
                'gross_salary' => $gross, 'net_salary' => $net, 'payment_method' => 'bank_transfer', 'payment_status' => 'generated',
                'working_days' => $workingDays, 'payable_days' => $round($payableDays), 'basic_and_da' => $basicDa, 'pf_wages' => $pfWages,
                'employee_pf' => $employeePf, 'employer_pf' => $employerPf, 'employer_eps' => $employerEps, 'employer_epf_balance' => $employerEpfBalance, 'edli_contribution' => $edli, 'pf_admin_charge' => $pfAdmin, 'employee_esi' => $employeeEsi, 'employer_esi' => $employerEsi,
                'professional_tax' => $professionalTax, 'tds' => $tds, 'employer_statutory_cost' => $round($employerPf + $edli + $pfAdmin + $employerEsi),
                'approval_status' => 'draft', 'generated_by' => $actorId, 'approved_by' => null, 'approved_at' => null, 'payment_reference' => null, 'paid_on' => null,
                'calculation_snapshot' => json_encode(['structure'=>['code'=>$assignment->structure_code,'name'=>$assignment->structure_name], 'components'=>compact('basic','da','hra','conveyance','other','fixedEarnings','fixedDeductions','variableEarnings','reimbursements','otherDeductions','advanceRecovery'), 'adjustments'=>$adjustments->map(fn($a)=>['id'=>$a->id,'type'=>$a->adjustment_type,'amount'=>$a->amount,'description'=>$a->description])->values()->all(), 'advance_ids'=>$advanceRows->pluck('id')->all(), 'attendance'=>['working_days'=>$workingDays,'payable_days'=>$round($payableDays)], 'statutory'=>compact('statutoryWages','pfWages','employeePf','employerPf','employerEps','employerEpfBalance','edli','pfAdmin','employeeEsi','employerEsi','professionalTax','tds'), 'calculated_at'=>now()->toIso8601String()], JSON_THROW_ON_ERROR),
                'notes' => 'Calculated from effective salary assignment and closed-month attendance.',
            ];
            if ($existing) { $existing->update($payload); $payroll = $existing->fresh(); $event = 'recalculated'; }
            else { $payroll = StaffPayroll::create($payload + ['staff_id'=>$staff->id,'payroll_month'=>$month]); $event = 'generated_from_structure'; }
            DB::table('staff_payroll_audits')->insert(['staff_payroll_id'=>$payroll->id,'actor_id'=>$actorId,'event_type'=>$event,'before_data'=>$before ? json_encode($before, JSON_THROW_ON_ERROR) : null,'after_data'=>json_encode($payload, JSON_THROW_ON_ERROR),'created_at'=>now()]);
            return $payroll;
        }, 3);
    }

    private function rule(string $code, string $jurisdiction, Carbon $date): object
    {
        $midMonthChange = DB::table('staff_statutory_rule_versions')->where('rule_code',$code)->where('jurisdiction',$jurisdiction)->whereDate('effective_from','>',$date->copy()->startOfMonth()->toDateString())->whereDate('effective_from','<=',$date->toDateString())->exists();
        if ($midMonthChange) throw new RuntimeException("{$code} changed effective date during this payroll month. Split-period statutory calculation requires an approved payroll adjustment; this run is blocked to avoid applying one rate to the full month.");
        $row = DB::table('staff_statutory_rule_versions')->where('rule_code',$code)->where('jurisdiction',$jurisdiction)->where('is_enabled',true)->whereDate('effective_from','<=',$date->toDateString())->where(fn($q)=>$q->whereNull('effective_to')->orWhereDate('effective_to','>=',$date->toDateString()))->orderByDesc('effective_from')->first();
        if (!$row) throw new RuntimeException("{$code} statutory rule is not enabled for this payroll period. An authorized payroll professional must verify and activate the effective rule version.");
        $row->rule_parameters = $row->rule_parameters ? json_decode($row->rule_parameters) : (object) [];
        return $row;
    }

    private function professionalTax(object $assignment, float $gross, Carbon $date): float
    {
        if ($assignment->professional_tax_exempt || $assignment->professional_tax_state !== 'MP') return 0.0;
        $rule = $this->rule('MP_PT','MP',$date);
        $annualProjection = $assignment->annual_salary_projection !== null ? (float) $assignment->annual_salary_projection : $gross * 12;
        $bands = $rule->rule_parameters->annual_salary_bands ?? [];
        $annualTax = 0;
        foreach ($bands as $band) { if ($band->up_to === null || $annualProjection <= (float) $band->up_to) { $annualTax = (float) $band->annual_tax; break; } }
        if ($annualTax <= 0) return 0.0;
        $month = (int) $date->format('n');
        $regular = match ((int) $annualTax) { 1500 => 125.00, 2000 => 166.00, 2500 => 208.00, default => round($annualTax / 12, 2, PHP_ROUND_HALF_UP) };
        return $month === 3 ? round($annualTax - ($regular * 11), 2, PHP_ROUND_HALF_UP) : $regular;
    }
}
