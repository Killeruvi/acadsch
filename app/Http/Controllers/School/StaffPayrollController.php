<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\StaffPayroll;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Services\Payroll\StaffPayrollCalculator;
use Carbon\Carbon;

class StaffPayrollController extends Controller
{
    private const PAYMENT_METHODS = ['bank_transfer', 'upi', 'cheque', 'cash'];

    private function requireAdmin(): void
    {
        abort_unless(Auth::user()?->isAdmin(), 403, 'Administrator access is required for payroll administration.');
    }

    public function index(Request $request): View
    {
        $user = Auth::user();
        abort_unless($user && ($user->isAdmin() || $user->isTeacher() || $user->isCashier()), 403);
        $filters = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'status' => ['nullable', 'in:generated,paid'],
        ]);
        $attendanceMonth = $filters['month'] ?? now()->format('Y-m');

        $query = StaffPayroll::with('staff:id,name,email')
            ->when($user->isTeacher(), fn ($q) => $q->where('staff_id', $user->id))
            ->when(($user->isAdmin() || $user->isCashier()) && isset($filters['month']), fn ($q) => $q->where('payroll_month', $filters['month']))
            ->when(($user->isAdmin() || $user->isCashier()) && isset($filters['status']), fn ($q) => $q->where('payment_status', $filters['status']))
            ->orderByDesc('payroll_month')->orderByDesc('id');

        $payrolls = $query->paginate(15)->withQueryString();

        if ($user->isAdmin() || $user->isCashier()) {
            $statsQuery = StaffPayroll::query();
            if ($request->filled('month') && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', (string) $request->query('month'))) {
                $statsQuery->where('payroll_month', $request->query('month'));
            }
            $stats = [
                'total_payrolls' => (clone $statsQuery)->count(),
                'paid_amount' => (clone $statsQuery)->where('payment_status', 'paid')->sum('net_salary'),
                'pending_amount' => (clone $statsQuery)->where('payment_status', 'generated')->sum('net_salary'),
            ];
            $staffList = User::whereIn('role', ['teacher', 'admin'])->where('is_approved', true)->orderBy('name')->get(['id', 'name', 'role']);
        } else {
            $own = StaffPayroll::where('staff_id', $user->id);
            $stats = [
                'total_payrolls' => (clone $own)->count(),
                'paid_amount' => (clone $own)->where('payment_status', 'paid')->sum('net_salary'),
                'pending_amount' => (clone $own)->where('payment_status', 'generated')->sum('net_salary'),
            ];
            $staffList = collect();
        }

        $attendanceQuery = \App\Models\StaffAttendanceRecord::query()
            ->whereBetween('attendance_date', [$attendanceMonth . '-01', \Carbon\Carbon::createFromFormat('Y-m-d', $attendanceMonth . '-01')->endOfMonth()->toDateString()])
            ->when($user->isTeacher(), fn ($q) => $q->where('staff_id', $user->id));
        $attendanceSummary = (clone $attendanceQuery)->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');

        $adjustments = DB::table('staff_payroll_adjustments as a')->join('users as u', 'u.id', '=', 'a.staff_id')
            ->when($user->isTeacher(), fn ($q) => $q->where('a.staff_id', $user->id))
            ->select('a.*', 'u.name as staff_name')->orderByDesc('a.created_at')->limit(100)->get();
        $advances = DB::table('staff_salary_advances as a')->join('users as u', 'u.id', '=', 'a.staff_id')
            ->when($user->isTeacher(), fn ($q) => $q->where('a.staff_id', $user->id))
            ->select('a.*', 'u.name as staff_name')->orderByDesc('a.created_at')->limit(100)->get();
        return view('school.payroll.index', compact('payrolls', 'staffList', 'stats', 'attendanceSummary', 'attendanceMonth', 'adjustments', 'advances'));
    }

    public function generate(Request $request, StaffPayrollCalculator $calculator): RedirectResponse
    {
        $this->requireAdmin();
        $validated = $request->validate([
            'staff_id' => ['required', 'integer', 'exists:users,id'],
            'payroll_month' => ['required', 'date_format:Y-m'],
        ]);
        $staff = User::whereKey($validated['staff_id'])->whereIn('role', ['teacher', 'admin'])->where('is_approved', true)->firstOrFail();
        try {
            $payroll = $calculator->generate($staff, $validated['payroll_month'], (int) Auth::id());
        } catch (\RuntimeException $e) {
            return back()->withInput()->withErrors(['payroll' => $e->getMessage()]);
        } catch (\Throwable $e) {
            report($e);
            return back()->withInput()->withErrors(['payroll' => 'Payroll calculation failed safely. No payroll was approved or paid. Review the configuration and server logs.']);
        }
        return back()->with('success', "Draft payroll calculated for {$staff->name} ({$validated['payroll_month']}). Review and approve it before payment.");
    }

    public function approve(int $id): RedirectResponse
    {
        $this->requireAdmin();
        DB::transaction(function () use ($id) {
            $payroll = StaffPayroll::whereKey($id)->lockForUpdate()->firstOrFail();
            abort_unless($payroll->approval_status === 'draft' && $payroll->payment_status === 'generated', 409, 'Only an unapproved draft can be approved.');
            abort_if((int) $payroll->generated_by === (int) Auth::id(), 403, 'A different authorized administrator must approve this payroll (maker-checker control).');
            abort_if(!$payroll->salary_assignment_id || !$payroll->calculation_snapshot, 409, 'This payroll has no verified calculation snapshot. Recalculate it from an effective salary structure.');
            $before = $payroll->only(['approval_status', 'approved_by', 'approved_at']);
            $payroll->update(['approval_status' => 'approved', 'approved_by' => Auth::id(), 'approved_at' => now()]);
            DB::table('staff_payroll_audits')->insert(['staff_payroll_id'=>$payroll->id,'actor_id'=>Auth::id(),'event_type'=>'approved','before_data'=>json_encode($before, JSON_THROW_ON_ERROR),'after_data'=>json_encode($payroll->fresh()->only(['approval_status','approved_by','approved_at']), JSON_THROW_ON_ERROR),'created_at'=>now()]);
        }, 3);
        return back()->with('success', 'Payroll approved. Payment can now be recorded.');
    }

    public function setup(): View
    {
        $this->requireAdmin();
        $structures = DB::table('staff_salary_structures')->orderBy('name')->get();
        $assignments = DB::table('staff_salary_assignments as a')->join('users as u','u.id','=','a.staff_id')->join('staff_salary_structures as s','s.id','=','a.salary_structure_id')->select('a.*','u.name as staff_name','s.name as structure_name')->orderByDesc('a.effective_from')->paginate(20);
        $staffList = User::whereIn('role',['teacher','admin'])->where('is_approved',true)->orderBy('name')->get(['id','name']);
        $rules = DB::table('staff_statutory_rule_versions')->orderBy('rule_code')->orderByDesc('effective_from')->get();
        return view('school.payroll.setup', compact('structures','assignments','staffList','rules'));
    }

    public function storeStructure(Request $request): RedirectResponse
    {
        $this->requireAdmin();
        $data = $request->validate([
            'code'=>['required','alpha_dash','max:40','unique:staff_salary_structures,code'], 'name'=>['required','string','max:160'],
            'basic_salary'=>['required','numeric','min:0','max:999999999'], 'dearness_allowance'=>['nullable','numeric','min:0','max:999999999'],
            'hra'=>['nullable','numeric','min:0','max:999999999'], 'conveyance_allowance'=>['nullable','numeric','min:0','max:999999999'],
            'other_allowances'=>['nullable','numeric','min:0','max:999999999'], 'fixed_monthly_earnings'=>['nullable','numeric','min:0','max:999999999'],
            'fixed_monthly_deductions'=>['nullable','numeric','min:0','max:999999999'], 'effective_from'=>['required','date_format:Y-m-d'],
            'effective_to'=>['nullable','date_format:Y-m-d','after_or_equal:effective_from'],
        ]);
        DB::table('staff_salary_structures')->insert($data + ['is_active'=>true,'created_by'=>Auth::id(),'created_at'=>now(),'updated_at'=>now()]);
        return back()->with('success','Salary structure created. Assign it to employees with an effective date.');
    }

    public function assignStructure(Request $request): RedirectResponse
    {
        $this->requireAdmin();
        $data = $request->validate([
            'staff_id'=>['required','integer','exists:users,id'], 'salary_structure_id'=>['required','integer','exists:staff_salary_structures,id'],
            'effective_from'=>['required','date_format:Y-m-d'], 'effective_to'=>['nullable','date_format:Y-m-d','after_or_equal:effective_from'],
            'pf_applicable'=>['nullable','boolean'], 'pf_existing_member'=>['nullable','boolean'], 'eps_applicable'=>['nullable','boolean'], 'international_worker'=>['nullable','boolean'], 'pf_on_actual_wages'=>['nullable','boolean'], 'esi_applicable'=>['nullable','boolean'],
            'esi_person_with_disability'=>['nullable','boolean'], 'esi_coverage_until'=>['nullable','date_format:Y-m-d'], 'professional_tax_exempt'=>['nullable','boolean'],
            'professional_tax_state'=>['required','in:MP'], 'tds_nil_confirmed'=>['nullable','boolean'],
            'annual_salary_projection'=>['nullable','numeric','min:0','max:999999999'], 'annual_tds_projection'=>['nullable','numeric','min:0','max:999999999'],
            'tax_regime'=>['nullable','string','max:30'],
        ]);
        $staff = User::whereKey($data['staff_id'])->whereIn('role',['teacher','admin'])->where('is_approved',true)->firstOrFail();
        DB::transaction(function () use ($data,$staff) {
            $overlapping = DB::table('staff_salary_assignments')->where('staff_id',$staff->id)->whereDate('effective_from','<=',$data['effective_to'] ?? '9999-12-31')->where(fn($q)=>$q->whereNull('effective_to')->orWhereDate('effective_to','>=',$data['effective_from']))->lockForUpdate()->get();
            foreach ($overlapping as $previous) {
                if ($previous->effective_to === null && $previous->effective_from < $data['effective_from']) {
                    DB::table('staff_salary_assignments')->where('id',$previous->id)->update(['effective_to'=>Carbon::parse($data['effective_from'])->subDay()->toDateString(),'updated_at'=>now()]);
                    continue;
                }
                abort(422,'An existing salary assignment overlaps this effective period. Review the previous assignment dates.');
            }
            DB::table('staff_salary_assignments')->insert(array_merge($data,['pf_applicable'=>(bool)($data['pf_applicable']??false),'pf_existing_member'=>(bool)($data['pf_existing_member']??false),'eps_applicable'=>(bool)($data['eps_applicable']??false),'international_worker'=>(bool)($data['international_worker']??false),'pf_on_actual_wages'=>(bool)($data['pf_on_actual_wages']??false),'esi_applicable'=>(bool)($data['esi_applicable']??false),'esi_person_with_disability'=>(bool)($data['esi_person_with_disability']??false),'professional_tax_exempt'=>(bool)($data['professional_tax_exempt']??false),'tds_nil_confirmed'=>(bool)($data['tds_nil_confirmed']??false),'created_by'=>Auth::id(),'created_at'=>now(),'updated_at'=>now()]));
        },3);
        return back()->with('success','Effective-dated salary assignment saved.');
    }

    public function updateRuleStatus(Request $request, int $id): RedirectResponse
    {
        $this->requireAdmin();
        $data = $request->validate(['is_enabled'=>['required','boolean']]);
        $rule = DB::table('staff_statutory_rule_versions')->where('id',$id)->first();
        abort_unless($rule,404);
        // Enabling a statutory rule is an explicit administrative attestation; retain who enabled it.
        DB::table('staff_statutory_rule_versions')->where('id',$id)->update(['is_enabled'=>$data['is_enabled'],'approved_by'=>Auth::id(),'updated_at'=>now()]);
        DB::table('staff_hr_audits')->insert(['entity_type'=>'statutory_rule','entity_id'=>$id,'actor_id'=>Auth::id(),'event_type'=>$data['is_enabled']?'enabled':'disabled','before_data'=>json_encode(['is_enabled'=>(bool)$rule->is_enabled]),'after_data'=>json_encode(['is_enabled'=>(bool)$data['is_enabled'],'rule_code'=>$rule->rule_code]),'created_at'=>now()]);
        return back()->with('success','Statutory rule activation setting updated. Confirm source and applicability before enabling.');
    }

    public function storeAdjustment(Request $request): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user && ($user->isAdmin() || $user->isTeacher()), 403);
        $data = $request->validate([
            'staff_id' => ['nullable', 'integer', 'exists:users,id'],
            'adjustment_type' => ['required', 'in:overtime,bonus,incentive,arrears,reimbursement,deduction'],
            'description' => ['required', 'string', 'max:180'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:99999999.99'],
            'payroll_month' => ['required', 'date_format:Y-m'],
            'taxable' => ['nullable', 'boolean'],
        ]);
        $staffId = $user->isAdmin() ? (int) ($data['staff_id'] ?? 0) : (int) $user->id;
        abort_if(!$staffId, 422, 'Select an employee.');
        $staff = User::whereKey($staffId)->whereIn('role', ['teacher', 'admin'])->where('is_approved', true)->firstOrFail();
        abort_if($data['payroll_month'] <= now()->format('Y-m'), 422, 'Adjustments must be submitted for a future payroll period that has not been generated.');
        DB::table('staff_payroll_adjustments')->insert([
            'staff_id' => $staff->id, 'adjustment_type' => $data['adjustment_type'], 'description' => trim($data['description']),
            'amount' => $data['amount'], 'payroll_month' => $data['payroll_month'], 'status' => 'pending',
            'taxable' => in_array($data['adjustment_type'], ['overtime','bonus','incentive','arrears'], true),
            'submitted_by' => $user->id, 'created_at' => now(), 'updated_at' => now(),
        ]);
        return back()->with('success', 'Payroll adjustment submitted for independent review. It will not affect payroll until approved.');
    }

    public function reviewAdjustment(Request $request, int $id): RedirectResponse
    {
        $this->requireAdmin();
        $data = $request->validate(['decision' => ['required', 'in:approve,reject']]);
        DB::transaction(function () use ($id, $data) {
            $item = DB::table('staff_payroll_adjustments')->where('id', $id)->lockForUpdate()->first();
            abort_unless($item, 404);
            abort_unless($item->status === 'pending', 409, 'Only pending adjustments can be reviewed.');
            abort_if((int) $item->submitted_by === (int) Auth::id(), 403, 'A different administrator must review this request.');
            DB::table('staff_payroll_adjustments')->where('id', $id)->update([
                'status' => $data['decision'] === 'approve' ? 'approved' : 'rejected',
                'reviewed_by' => Auth::id(), 'reviewed_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('staff_hr_audits')->insert([
                'entity_type' => 'payroll_adjustment', 'entity_id' => $id, 'actor_id' => Auth::id(),
                'event_type' => $data['decision'], 'before_data' => json_encode(['status' => 'pending']),
                'after_data' => json_encode(['status' => $data['decision'] === 'approve' ? 'approved' : 'rejected']), 'created_at' => now(),
            ]);
        }, 3);
        return back()->with('success', 'Adjustment review recorded.');
    }

    public function storeAdvance(Request $request): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user && ($user->isAdmin() || $user->isTeacher()), 403);
        $data = $request->validate([
            'staff_id' => ['nullable', 'integer', 'exists:users,id'], 'principal_amount' => ['required', 'numeric', 'gt:0', 'max:99999999.99'],
            'monthly_installment' => ['required', 'numeric', 'gt:0', 'max:99999999.99'],
            'first_deduction_month' => ['required', 'date_format:Y-m'], 'reference' => ['nullable', 'string', 'max:120'], 'notes' => ['nullable', 'string', 'max:1000'],
        ]);
        $staffId = $user->isAdmin() ? (int) ($data['staff_id'] ?? 0) : (int) $user->id;
        abort_if(!$staffId, 422, 'Select an employee.');
        User::whereKey($staffId)->whereIn('role', ['teacher', 'admin'])->where('is_approved', true)->firstOrFail();
        abort_if($data['first_deduction_month'] <= now()->format('Y-m'), 422, 'The first deduction must be in a future payroll period.');
        DB::table('staff_salary_advances')->insert([
            'staff_id' => $staffId, 'principal_amount' => $data['principal_amount'], 'monthly_installment' => $data['monthly_installment'],
            'repaid_amount' => 0, 'disbursed_on' => null, 'disbursement_reference' => null, 'first_deduction_month' => $data['first_deduction_month'],
            'status' => 'pending', 'reference' => $data['reference'] ?? null, 'notes' => $data['notes'] ?? null,
            'created_by' => $user->id, 'created_at' => now(), 'updated_at' => now(),
        ]);
        return back()->with('success', 'Salary advance request recorded and awaiting independent approval.');
    }

    public function reviewAdvance(Request $request, int $id): RedirectResponse
    {
        $this->requireAdmin();
        $data = $request->validate(['decision' => ['required', 'in:approve,reject']]);
        DB::transaction(function () use ($id, $data) {
            $advance = DB::table('staff_salary_advances')->where('id', $id)->lockForUpdate()->first();
            abort_unless($advance, 404);
            abort_unless($advance->status === 'pending', 409, 'Only pending advances can be reviewed.');
            abort_if((int) $advance->created_by === (int) Auth::id(), 403, 'A different administrator must approve this advance.');
            DB::table('staff_salary_advances')->where('id', $id)->update([
                'status' => $data['decision'] === 'approve' ? 'approved' : 'cancelled', 'approved_by' => Auth::id(),
                'approved_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('staff_hr_audits')->insert([
                'entity_type' => 'salary_advance', 'entity_id' => $id, 'actor_id' => Auth::id(), 'event_type' => $data['decision'],
                'before_data' => json_encode(['status' => 'pending']), 'after_data' => json_encode(['status' => $data['decision'] === 'approve' ? 'approved' : 'cancelled']), 'created_at' => now(),
            ]);
        }, 3);
        return back()->with('success', 'Salary advance review recorded.');
    }

    public function disburseAdvance(Request $request, int $id): RedirectResponse
    {
        $this->requireAdmin();
        $data = $request->validate([
            'disbursed_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'disbursement_reference' => ['required', 'string', 'min:4', 'max:120'],
        ]);
        DB::transaction(function () use ($id, $data) {
            $advance = DB::table('staff_salary_advances')->where('id', $id)->lockForUpdate()->first();
            abort_unless($advance, 404);
            abort_unless($advance->status === 'approved', 409, 'Only an approved advance can be disbursed.');
            abort_if($advance->first_deduction_month <= Carbon::parse($data['disbursed_on'])->format('Y-m'), 422, 'First recovery month must be after the actual disbursement month.');
            DB::table('staff_salary_advances')->where('id', $id)->update([
                'status' => 'active', 'disbursed_on' => $data['disbursed_on'],
                'disbursement_reference' => trim($data['disbursement_reference']), 'updated_at' => now(),
            ]);
            DB::table('staff_hr_audits')->insert([
                'entity_type' => 'salary_advance', 'entity_id' => $id, 'actor_id' => Auth::id(), 'event_type' => 'disbursed',
                'before_data' => json_encode(['status' => 'approved']), 'after_data' => json_encode(['status' => 'active', 'disbursed_on' => $data['disbursed_on'], 'reference' => $data['disbursement_reference']]), 'created_at' => now(),
            ]);
        }, 3);
        return back()->with('success', 'Advance disbursement recorded. Installment recovery will begin in the configured first deduction month.');
    }

    public function markAsPaid(Request $request, int $id): RedirectResponse
    {
        abort_unless(Auth::user()?->isAdmin() || Auth::user()?->isCashier(), 403, 'Administrator or Cashier access is required to record teacher salary payments.');
        $payment = $request->validate(['payment_reference'=>['required','string','min:4','max:120'], 'paid_on'=>['required','date_format:Y-m-d','before_or_equal:today'], 'payment_method'=>['required','in:bank_transfer,upi,cheque,cash']]);

        $result = DB::transaction(function () use ($id, $payment) {
            $payroll = StaffPayroll::whereKey($id)->lockForUpdate()->firstOrFail();
            if ($payroll->payment_status === 'paid') {
                return 'already_paid';
            }
            if ($payroll->approval_status !== 'approved') {
                abort(409, 'Payroll must be approved before payment can be recorded.');
            }
            if ($payroll->payment_status !== 'generated') {
                abort(409, 'Only generated payroll records can be marked as paid.');
            }

            $before = $payroll->only(['payment_status', 'paid_at', 'paid_by', 'paid_on', 'payment_reference', 'payment_method']);
            $payroll->update([
                'payment_status' => 'paid',
                'paid_at' => now(),
                'paid_by' => Auth::id(),
                'paid_on' => $payment['paid_on'],
                'payment_reference' => trim($payment['payment_reference']),
                'payment_method' => $payment['payment_method'],
            ]);

            $snapshot = is_array($payroll->calculation_snapshot) ? $payroll->calculation_snapshot : [];
            foreach (($snapshot['advance_ids'] ?? []) as $advanceId) {
                $advance = DB::table('staff_salary_advances')->where('id', (int) $advanceId)->where('staff_id', $payroll->staff_id)->where('status', 'active')->lockForUpdate()->first();
                if (!$advance) continue;
                $balance = max(0, round((float) $advance->principal_amount - (float) $advance->repaid_amount, 2));
                $recovery = min($balance, (float) $advance->monthly_installment);
                $repaid = round((float) $advance->repaid_amount + $recovery, 2);
                DB::table('staff_salary_advances')->where('id', $advance->id)->update([
                    'repaid_amount' => $repaid, 'status' => $repaid >= (float) $advance->principal_amount ? 'completed' : 'active', 'updated_at' => now(),
                ]);
            }
            foreach (($snapshot['adjustments'] ?? []) as $adjustment) {
                DB::table('staff_payroll_adjustments')->where('id', (int) ($adjustment['id'] ?? 0))->where('staff_id', $payroll->staff_id)->where('payroll_month', $payroll->payroll_month)->where('status', 'approved')->update([
                    'status' => 'applied', 'staff_payroll_id' => $payroll->id, 'updated_at' => now(),
                ]);
            }

            DB::table('staff_payroll_audits')->insert([
                'staff_payroll_id' => $payroll->id,
                'actor_id' => Auth::id(),
                'event_type' => 'marked_paid',
                'before_data' => json_encode($before, JSON_THROW_ON_ERROR),
                'after_data' => json_encode($payroll->fresh()->only(['payment_status', 'paid_at', 'paid_by', 'paid_on', 'payment_reference', 'payment_method']), JSON_THROW_ON_ERROR),
                'created_at' => now(),
            ]);
            return 'paid';
        }, 3);

        return back()->with('success', $result === 'already_paid'
            ? 'This payroll record was already marked as paid; no duplicate action was made.'
            : 'Payment record saved with its reference. The bank or payment provider settlement has not been independently reconciled.');
    }

    public function showPayslip(int $id): View
    {
        $user = Auth::user();
        abort_unless($user && ($user->isAdmin() || $user->isTeacher() || $user->isCashier()), 403);
        $payroll = StaffPayroll::with(['staff:id,name,email','paidBy:id,name'])->findOrFail($id);
        abort_unless($user->isAdmin() || $user->isCashier() || (int) $payroll->staff_id === (int) $user->id, 403);
        abort_unless($payroll->approval_status === 'approved' || $payroll->payment_status === 'paid', 404);
        return view('school.payroll.payslip', compact('payroll'));
    }

    public function export(Request $request): StreamedResponse
    {
        abort_unless(Auth::user()?->isAdmin() || Auth::user()?->isCashier(), 403, 'Administrator or Cashier access is required.');
        $validated = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'status' => ['nullable', 'in:generated,paid'],
        ]);

        $query = StaffPayroll::with('staff:id,name,email')
            ->when($validated['month'] ?? null, fn ($q, $month) => $q->where('payroll_month', $month))
            ->when($validated['status'] ?? null, fn ($q, $status) => $q->where('payment_status', $status))
            ->orderBy('payroll_month')->orderBy('staff_id');

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fprintf($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Staff name', 'Staff email', 'Payroll month', 'Working days', 'Payable days', 'Basic salary', 'Earnings', 'Variable earnings', 'Reimbursements', 'Gross salary', 'EPF employee', 'ESI employee', 'Professional tax', 'TDS', 'Other deductions', 'Advance recovery', 'Total deductions', 'Net salary', 'Employer statutory cost', 'Approval status', 'Payment method', 'Payment reference', 'Payment status', 'Paid on']);
            $query->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $row) {
                    $values = [$row->staff?->name, $row->staff?->email, $row->payroll_month, $row->working_days, $row->payable_days, $row->basic_salary, $row->earnings, $row->variable_earnings, $row->reimbursements, $row->gross_salary, $row->employee_pf, $row->employee_esi, $row->professional_tax, $row->tds, $row->other_deductions, $row->advance_recovery, $row->deductions, $row->net_salary, $row->employer_statutory_cost, $row->approval_status, $row->payment_method, $row->payment_reference, $row->payment_status, optional($row->paid_on)->toDateString()];
                    fputcsv($out, array_map(static function ($value) {
                        $value = (string) ($value ?? '');
                        return preg_match('/^[=+@\-\t\r]/', $value) ? "'" . $value : $value;
                    }, $values));
                }
            });
            fclose($out);
        }, 'staff-payroll-' . now()->format('Ymd-His') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
