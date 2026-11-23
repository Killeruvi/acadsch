<?php

namespace App\Services\HR;

use App\Models\AcademicHoliday;
use App\Models\StaffLeaveRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class StaffLeaveEntitlementService
{
    public function workingDays(string $start, string $end): float
    {
        $date = Carbon::parse($start)->startOfDay();
        $last = Carbon::parse($end)->startOfDay();
        $weekends = config('attendance.weekend_days', ['Sunday']);
        $count = 0;
        for (; $date->lte($last); $date->addDay()) {
            if (in_array($date->format('l'), $weekends, true) || AcademicHoliday::findHolidayForDate($date)) {
                continue;
            }
            $count++;
        }

        return (float) $count;
    }

    public function balance(int $staffId, string $leaveType, int $year): float
    {
        $row = DB::table('staff_leave_balances')->where(['staff_id' => $staffId, 'leave_type' => $leaveType, 'leave_year' => $year])->first();

        return $row ? round((float) $row->opening_balance + (float) $row->accrued + (float) $row->adjusted - (float) $row->used - (float) $row->encashed, 2) : 0.0;
    }

    public function consumeApprovedLeave(StaffLeaveRequest $leave, int $actorId): void
    {
        if ($leave->leave_type !== 'paid') {
            $leave->update(['balance_status' => 'not_applicable']);

            return;
        }
        $start = Carbon::parse($leave->start_date);
        $end = Carbon::parse($leave->end_date);
        if ($start->year !== $end->year) {
            throw new RuntimeException('Paid leave spanning calendar years must be split into separate applications.');
        }
        $days = $this->workingDays($start->toDateString(), $end->toDateString());
        if ($days <= 0) {
            throw new RuntimeException('The selected leave period contains no working days.');
        }
        $category = $leave->leave_category ?: 'annual';
        $policy = DB::table('staff_leave_policies')->where('leave_type', $category)->where('is_enabled', true)->whereDate('effective_from', '<=', $start->toDateString())->where(fn ($q) => $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $end->toDateString()))->orderByDesc('effective_from')->first();
        if (! $policy) {
            throw new RuntimeException('No active paid-leave policy covers these dates. An administrator must configure and enable the policy before approving paid leave.');
        }
        $balance = DB::table('staff_leave_balances')->where(['staff_id' => $leave->staff_id, 'leave_type' => $category, 'leave_year' => $start->year])->lockForUpdate()->first();
        $available = $balance ? round((float) $balance->opening_balance + (float) $balance->accrued + (float) $balance->adjusted - (float) $balance->used - (float) $balance->encashed, 2) : 0.0;
        if ($available + 0.0001 < $days) {
            throw new RuntimeException("Insufficient paid-leave balance. Available: {$available}; requested working days: {$days}.");
        }
        if (! $balance) {
            throw new RuntimeException('No leave balance ledger exists for this employee and year.');
        }
        $key = 'leave-approval-'.$leave->id;
        if (! DB::table('staff_leave_transactions')->where('idempotency_key', $key)->exists()) {
            DB::table('staff_leave_balances')->where('id', $balance->id)->increment('used', $days);
            DB::table('staff_leave_transactions')->insert(['staff_leave_balance_id' => $balance->id, 'staff_id' => $leave->staff_id, 'leave_type' => $category, 'transaction_type' => 'usage', 'quantity' => $days, 'source_type' => 'staff_leave_request', 'source_id' => $leave->id, 'idempotency_key' => $key, 'notes' => 'Approved '.$category.' leave', 'actor_id' => $actorId, 'created_at' => now()]);
        }
        $leave->update(['working_days' => $days, 'balance_status' => 'deducted']);
    }

    public function accrueMonthly(Carbon $date): int
    {
        $policies = DB::table('staff_leave_policies')->where('is_enabled', true)->whereDate('effective_from', '<=', $date->toDateString())->where(fn ($q) => $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $date->toDateString()))->get();
        $count = 0;
        foreach ($policies as $policy) {
            $staff = User::query()->whereIn('role', ['teacher', 'admin'])->where('is_approved', true)->get(['id', 'created_at']);
            foreach ($staff as $employee) {
                $employment = DB::table('staff_employment_profiles')->where('staff_id', $employee->id)->first();
                $joinDate = $employment?->joining_date ? Carbon::parse($employment->joining_date) : ($employee->created_at ? Carbon::parse($employee->created_at) : $date->copy());
                $lastDay = $employment?->last_working_date ? Carbon::parse($employment->last_working_date) : null;
                if ($joinDate->gt($date->copy()->endOfMonth()) || ($lastDay && $lastDay->lt($date))) {
                    continue;
                }
                $eligibleStart = $joinDate->max($date->copy()->startOfMonth());
                $eligibleEnd = $lastDay ? $lastDay->min($date->copy()->endOfMonth()) : $date->copy()->endOfMonth();
                $proration = ($eligibleStart->diffInDays($eligibleEnd) + 1) / max(1, $date->daysInMonth);
                DB::transaction(function () use ($employee, $policy, $date, $proration, &$count) {
                    $balance = DB::table('staff_leave_balances')->where(['staff_id' => $employee->id, 'leave_type' => $policy->leave_type, 'leave_year' => $date->year])->lockForUpdate()->first();
                    if (! $balance) {
                        $opening = 0.0;
                        if ($date->month === 1 && (float) $policy->maximum_carry_forward > 0) {
                            $previous = DB::table('staff_leave_balances')->where(['staff_id' => $employee->id, 'leave_type' => $policy->leave_type, 'leave_year' => $date->year - 1])->first();
                            if ($previous) {
                                $remaining = max(0, (float) $previous->opening_balance + (float) $previous->accrued + (float) $previous->adjusted - (float) $previous->used - (float) $previous->encashed);
                                $opening = min($remaining, (float) $policy->maximum_carry_forward);
                            }
                        }
                        $id = DB::table('staff_leave_balances')->insertGetId(['staff_id' => $employee->id, 'leave_type' => $policy->leave_type, 'leave_year' => $date->year, 'opening_balance' => $opening, 'accrued' => 0, 'used' => 0, 'encashed' => 0, 'adjusted' => 0, 'created_at' => now(), 'updated_at' => now()]);
                        $balance = DB::table('staff_leave_balances')->where('id', $id)->first();
                        if ($opening > 0) {
                            $carryKey = 'carry-forward-'.$policy->id.'-'.$employee->id.'-'.$date->year;
                            DB::table('staff_leave_transactions')->insert(['staff_leave_balance_id' => $balance->id, 'staff_id' => $employee->id, 'leave_type' => $policy->leave_type, 'transaction_type' => 'carry_forward', 'quantity' => $opening, 'source_type' => 'staff_leave_balance', 'source_id' => $previous->id, 'idempotency_key' => $carryKey, 'notes' => 'Capped prior-year balance carried forward', 'actor_id' => null, 'created_at' => now()]);
                        }
                    }
                    $key = 'monthly-accrual-'.$policy->id.'-'.$employee->id.'-'.$date->format('Y-m');
                    if (DB::table('staff_leave_transactions')->where('idempotency_key', $key)->exists()) {
                        return;
                    }
                    $amount = round((float) $policy->monthly_accrual * $proration, 4);
                    $remainingAnnual = max(0, (float) $policy->annual_entitlement - (float) $balance->accrued);
                    $amount = min($amount, $remainingAnnual);
                    if ($amount <= 0) {
                        return;
                    }
                    DB::table('staff_leave_balances')->where('id', $balance->id)->increment('accrued', $amount);
                    DB::table('staff_leave_transactions')->insert(['staff_leave_balance_id' => $balance->id, 'staff_id' => $employee->id, 'leave_type' => $policy->leave_type, 'transaction_type' => 'accrual', 'quantity' => $amount, 'source_type' => 'staff_leave_policy', 'source_id' => $policy->id, 'idempotency_key' => $key, 'notes' => 'Scheduled monthly accrual', 'actor_id' => null, 'created_at' => now()]);
                    $count++;
                }, 3);
            }
        }

        return $count;
    }
}
