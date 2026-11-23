@extends('layouts.app')

@section('title', 'Staff Payroll — ' . ($school['name'] ?? 'Infix LMS'))

@section('content')
<div class="max-w-[1600px] mx-auto space-y-5 pb-24 sm:pb-8">

    <!-- Infix Breadcrumbs & Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center space-x-2 text-[11px] text-slate-400 font-semibold mb-1">
                <a href="{{ route('dashboard') }}" class="hover:text-slate-600 dark:hover:text-slate-200 transition">Dashboard</a>
                <span>/</span>
                <span class="text-slate-500">Human Resources</span>
                <span>/</span>
                <span class="text-slate-800 dark:text-slate-200 font-bold">Staff Payroll</span>
            </div>

            <div class="flex items-center space-x-2.5">
                <span class="w-8 h-8 rounded-lg flex items-center justify-center text-white text-sm shadow-xs" style="background: var(--system_primary_gradient);">
                    <i class="ti-credit-card"></i>
                </span>
                <h1 class="text-lg sm:text-xl font-black text-slate-900 dark:text-white tracking-tight">
                    Staff &amp; Faculty Payroll Hub
                </h1>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold text-white shadow-2xs" style="background: var(--system_primary_gradient);">
                    Infix HR &amp; Payroll
                </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Effective-dated salary structures, attendance-prorated payroll, statutory deductions and approval-controlled payment records.
            </p>
        </div>

        @if(Auth::user()->isAdmin())
        <div class="flex items-center flex-wrap gap-2">
            <a href="{{ route('school.payroll.setup') }}" class="px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 text-xs font-bold">Salary & Statutory Setup</a><a href="{{ route('school.staff.final-settlements.index') }}" class="px-3 py-2 rounded-xl border border-amber-300 text-amber-800 text-xs font-bold">Final Settlements</a><a href="{{ route('school.payroll.compliance', ['month' => request('month', now()->format('Y-m'))]) }}" class="px-3 py-2 rounded-xl border border-indigo-300 text-indigo-700 text-xs font-bold">Reconciliation & Returns</a><a href="{{ route('school.payroll.export', request()->only('month', 'status')) }}" class="px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 text-xs font-bold">Export CSV</a>
            <button type="button"
                    onclick="document.getElementById('generate-payroll-modal').classList.remove('hidden')"
                    class="primary-btn" style="padding: 0.4rem 1.1rem; font-size: 0.75rem;">
                <i class="ti-plus text-xs"></i>
                <span>Generate Payroll</span>
            </button>
        </div>
        @endif
    </div>

    <!-- Infix Summary Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
        <div class="white-box single-summery p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs">
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Generated Payslips</span>
            <div class="text-2xl font-black text-slate-900 dark:text-white mt-1.5 font-mono">{{ $stats['total_payrolls'] }}</div>
            <p class="text-[10px] text-slate-400 mt-1">Processed salary slips</p>
        </div>
        <div class="white-box single-summery p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs">
            <span class="text-[11px] font-bold text-emerald-600 uppercase tracking-wider block">Settled Salary Total</span>
            <div class="text-2xl font-black text-emerald-600 mt-1.5 font-mono">₹{{ number_format($stats['paid_amount'], 2) }}</div>
            <p class="text-[10px] text-emerald-600 font-semibold mt-1">Disbursed net compensation</p>
        </div>
        <div class="white-box single-summery p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs">
            <span class="text-[11px] font-bold text-amber-500 uppercase tracking-wider block">Pending Disbursement</span>
            <div class="text-2xl font-black text-amber-500 mt-1.5 font-mono">₹{{ number_format($stats['pending_amount'], 2) }}</div>
            <p class="text-[10px] text-slate-400 mt-1">Awaiting bank authorization</p>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-4 space-y-3">
        <div class="flex flex-wrap items-center justify-between gap-2"><div><h2 class="text-sm font-black text-slate-900 dark:text-white">Attendance reconciliation — {{ $attendanceMonth }}</h2><p class="text-[10px] text-slate-500">Recorded attendance only; this is not an automatic salary calculation.</p></div><a href="{{ route('school.staff-attendance.index', ['month' => $attendanceMonth]) }}" class="text-xs font-bold text-indigo-600">Open staff attendance</a></div>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
            @foreach(['present' => 'Present', 'absent' => 'Absent', 'half_day' => 'Half day', 'leave' => 'Leave'] as $status => $label)
            <div class="rounded-lg bg-slate-50 dark:bg-slate-800 p-3"><div class="text-[10px] uppercase font-bold text-slate-500">{{ $label }}</div><div class="text-xl font-black text-slate-900 dark:text-white">{{ (int) ($attendanceSummary[$status] ?? 0) }}</div></div>
            @endforeach
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
        <section class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-4 space-y-3">
            <div><h2 class="text-sm font-black">Variable pay, overtime & reimbursements</h2><p class="text-[11px] text-slate-500">Requests are excluded from payroll until independently approved. Overtime eligibility and rates must follow the institution's verified policy and applicable law.</p></div>
            <form method="POST" action="{{ route('school.payroll.adjustments.store') }}" class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">@csrf
                @if(Auth::user()->isAdmin())<select name="staff_id" required class="border rounded-lg p-2 bg-transparent"><option value="">Select staff</option>@foreach($staffList as $st)<option value="{{ $st->id }}">{{ $st->name }}</option>@endforeach</select>@endif
                <select name="adjustment_type" required class="border rounded-lg p-2 bg-transparent"><option value="overtime">Overtime</option><option value="bonus">Bonus</option><option value="incentive">Incentive</option><option value="arrears">Arrears</option><option value="reimbursement">Reimbursement</option><option value="deduction">Other deduction</option></select>
                <input type="month" name="payroll_month" min="{{ now()->addMonth()->format('Y-m') }}" required class="border rounded-lg p-2 bg-transparent">
                <input type="number" name="amount" min="0.01" max="99999999.99" step="0.01" required placeholder="Amount ₹" class="border rounded-lg p-2 bg-transparent">
                <input name="description" maxlength="180" required placeholder="Reason / reference" class="border rounded-lg p-2 bg-transparent sm:col-span-2">
                <button class="rounded-lg bg-indigo-600 text-white font-bold p-2 sm:col-span-2">Submit for approval</button>
            </form>
            <div class="max-h-64 overflow-auto text-xs"><table class="w-full"><thead><tr class="text-left text-slate-500"><th class="p-2">Staff / Type</th><th class="p-2">Month</th><th class="p-2">Amount</th><th class="p-2">Status / Action</th></tr></thead><tbody>@forelse($adjustments as $item)<tr class="border-t"><td class="p-2">{{ $item->staff_name }}<div class="text-slate-500">{{ ucfirst($item->adjustment_type) }} · {{ $item->description }}</div></td><td class="p-2">{{ $item->payroll_month }}</td><td class="p-2">₹{{ number_format($item->amount,2) }}</td><td class="p-2">{{ $item->status }} @if(Auth::user()->isAdmin() && $item->status === 'pending' && (int)$item->submitted_by !== (int)Auth::id())<form method="POST" action="{{ route('school.payroll.adjustments.review',$item->id) }}" class="flex gap-1 mt-1">@csrf<button name="decision" value="approve" class="text-emerald-700 font-bold">Approve</button><button name="decision" value="reject" class="text-rose-700 font-bold">Reject</button></form>@endif</td></tr>@empty<tr><td colspan="4" class="p-3 text-slate-400">No adjustment requests.</td></tr>@endforelse</tbody></table></div>
        </section>
        <section class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-4 space-y-3">
            <div><h2 class="text-sm font-black">Salary advances & recovery</h2><p class="text-[11px] text-slate-500">Approved advances are recovered in installments only when the related payroll is marked paid. No interest is added automatically.</p></div>
            <form method="POST" action="{{ route('school.payroll.advances.store') }}" class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">@csrf
                @if(Auth::user()->isAdmin())<select name="staff_id" required class="border rounded-lg p-2 bg-transparent"><option value="">Select staff</option>@foreach($staffList as $st)<option value="{{ $st->id }}">{{ $st->name }}</option>@endforeach</select>@endif
                <input type="number" name="principal_amount" min="0.01" step="0.01" required placeholder="Advance amount ₹" class="border rounded-lg p-2 bg-transparent">
                <input type="number" name="monthly_installment" min="0.01" step="0.01" required placeholder="Monthly installment ₹" class="border rounded-lg p-2 bg-transparent">
                
                <input type="month" name="first_deduction_month" min="{{ now()->addMonth()->format('Y-m') }}" required class="border rounded-lg p-2 bg-transparent">
                <input name="reference" maxlength="120" placeholder="Disbursement reference" class="border rounded-lg p-2 bg-transparent">
                <input name="notes" maxlength="1000" placeholder="Notes" class="border rounded-lg p-2 bg-transparent">
                <button class="rounded-lg bg-indigo-600 text-white font-bold p-2 sm:col-span-2">Submit advance request</button>
            </form>
            <div class="max-h-64 overflow-auto text-xs"><table class="w-full"><thead><tr class="text-left text-slate-500"><th class="p-2">Staff</th><th class="p-2">Balance</th><th class="p-2">Monthly</th><th class="p-2">Status / Action</th></tr></thead><tbody>@forelse($advances as $advance)<tr class="border-t"><td class="p-2">{{ $advance->staff_name }}<div class="text-slate-500">First recovery: {{ $advance->first_deduction_month }} · Disbursed: {{ $advance->disbursed_on ?? 'not yet' }}</div></td><td class="p-2">₹{{ number_format(max(0,$advance->principal_amount-$advance->repaid_amount),2) }}</td><td class="p-2">₹{{ number_format($advance->monthly_installment,2) }}</td><td class="p-2">{{ $advance->status }} @if(Auth::user()->isAdmin() && $advance->status === 'pending' && (int)$advance->created_by !== (int)Auth::id())<form method="POST" action="{{ route('school.payroll.advances.review',$advance->id) }}" class="flex gap-1 mt-1">@csrf<button name="decision" value="approve" class="text-emerald-700 font-bold">Approve</button><button name="decision" value="reject" class="text-rose-700 font-bold">Reject</button></form>@endif @if(Auth::user()->isAdmin() && $advance->status === 'approved')<form method="POST" action="{{ route('school.payroll.advances.disburse',$advance->id) }}" class="mt-1 flex flex-col gap-1">@csrf<input type="date" name="disbursed_on" required max="{{ today()->toDateString() }}" value="{{ today()->toDateString() }}" class="border rounded p-1"><input name="disbursement_reference" required minlength="4" maxlength="120" placeholder="UTR / voucher reference" class="border rounded p-1"><button class="text-indigo-700 font-bold">Record disbursement</button></form>@endif</td></tr>@empty<tr><td colspan="4" class="p-3 text-slate-400">No salary advances.</td></tr>@endforelse</tbody></table></div>
        </section>
    </div>

    @if(Auth::user()->isAdmin())
    <form method="GET" action="{{ route('school.payroll.index') }}" class="flex flex-wrap items-end gap-3 bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-3">
        <div><label for="payroll-month-filter" class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Payroll month</label><input id="payroll-month-filter" type="month" name="month" value="{{ request('month') }}" class="rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent px-3 py-2 text-xs"></div>
        <div><label for="payroll-status-filter" class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Status</label><select id="payroll-status-filter" name="status" class="rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent px-3 py-2 text-xs"><option value="">All statuses</option><option value="generated" @selected(request('status') === 'generated')>Generated</option><option value="paid" @selected(request('status') === 'paid')>Paid</option></select></div>
        <button class="px-4 py-2 rounded-lg bg-slate-900 text-white text-xs font-bold">Apply filters</button>
        <a href="{{ route('school.payroll.index') }}" class="px-3 py-2 text-xs font-bold text-slate-500">Clear</a>
    </form>
    @endif

    <!-- Payroll Table -->
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-4 shadow-2xs space-y-3">
        @if($errors->has('payroll'))<div class="rounded-lg bg-rose-50 text-rose-800 p-3 text-xs">{{ $errors->first('payroll') }}</div>@endif
        <h2 class="text-sm font-black text-slate-900 dark:text-white flex items-center gap-1.5 pb-2 border-b border-slate-100 dark:border-slate-800">
            <span>📑</span> Faculty Salary Register &amp; Payslips
        </h2>
        <p class="text-[11px] text-slate-500">“Mark as paid” records an administrator confirmation only; it does not verify settlement with a bank, UPI provider, or cheque.</p>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600 dark:text-slate-400">
                <thead class="bg-slate-50 dark:bg-slate-800/60 uppercase font-black text-[10px] text-slate-500 tracking-wider">
                    <tr>
                        <th class="p-3">Staff Member</th>
                        <th class="p-3">Month</th>
                        <th class="p-3">Basic Pay</th>
                        <th class="p-3">Allowances</th>
                        <th class="p-3">PF / ESI / PT / TDS</th><th class="p-3">Payable days</th><th class="p-3">Deductions</th>
                        <th class="p-3 font-black text-slate-900 dark:text-white">Net Salary</th>
                        <th class="p-3">Status</th>
                        <th class="p-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($payrolls as $pay)
                    <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                        <td class="p-3">
                            <span class="font-bold text-slate-900 dark:text-white block">{{ $pay->staff?->name }}</span>
                            <span class="text-[10px] text-slate-400 font-mono">{{ $pay->staff?->email }}</span>
                        </td>
                        <td class="p-3 font-mono font-bold text-purple-600 dark:text-purple-400">
                            {{ $pay->payroll_month }}
                        </td>
                        <td class="p-3 font-mono">
                            ₹{{ number_format($pay->basic_salary, 2) }}
                        </td>
                        <td class="p-3 font-mono text-emerald-600 dark:text-emerald-400">
                            +₹{{ number_format($pay->earnings, 2) }}
                        </td>
                        <td class="p-3 font-mono text-[10px]">PF ₹{{ number_format($pay->employee_pf,2) }} · ESI ₹{{ number_format($pay->employee_esi,2) }} · PT ₹{{ number_format($pay->professional_tax,2) }} · TDS ₹{{ number_format($pay->tds,2) }}</td>
                        <td class="p-3 font-mono">{{ $pay->payable_days ?? '—' }}/{{ $pay->working_days ?? '—' }}</td>
                        <td class="p-3 font-mono text-rose-500">-₹{{ number_format($pay->deductions, 2) }}</td>
                        <td class="p-3 font-mono font-black text-sm text-slate-900 dark:text-white">
                            ₹{{ number_format($pay->net_salary, 2) }}
                        </td>
                        <td class="p-3">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase">{{ $pay->approval_status ?? 'legacy' }} / {{ $pay->payment_status }}</span>
                        </td>
                        <td class="p-3 text-right space-x-1.5 whitespace-nowrap">
                            @if($pay->approval_status === 'approved' || $pay->payment_status === 'paid')<a href="{{ route('school.payroll.payslip',$pay->id) }}" class="px-2 py-1 rounded-lg border text-xs font-bold">Payslip</a>@endif
                            @if(Auth::user()->isAdmin() && $pay->approval_status === 'draft' && $pay->payment_status !== 'paid')
                            <form method="POST" action="{{ route('school.payroll.approve', $pay->id) }}" class="inline">@csrf<button type="submit" class="px-2.5 py-1 rounded-lg bg-indigo-600 text-white font-bold text-xs">Approve</button></form>
                            @endif
                            @if(Auth::user()->isAdmin() && $pay->approval_status === 'approved' && $pay->payment_status !== 'paid')
                            <form method="POST" action="{{ route('school.payroll.pay', $pay->id) }}" class="inline-flex flex-wrap gap-1 items-center">@csrf
                                <input name="payment_reference" required minlength="4" maxlength="120" placeholder="UTR / cheque / voucher ref" class="w-36 border rounded px-1 py-1 text-[10px]">
                                <input type="date" name="paid_on" required max="{{ today()->toDateString() }}" value="{{ today()->toDateString() }}" class="border rounded px-1 py-1 text-[10px]">
                                <select name="payment_method" class="border rounded px-1 py-1 text-[10px]"><option value="bank_transfer">Bank</option><option value="upi">UPI</option><option value="cheque">Cheque</option><option value="cash">Cash</option></select>
                                <button type="submit" class="px-2.5 py-1 rounded-lg bg-emerald-600 text-white font-bold text-xs">Record payment</button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="p-6 text-center text-slate-400">No payroll entries generated yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-2 pt-2 border-t border-slate-100 dark:border-slate-800">
            {{ $payrolls->links() }}
        </div>
    </div>

</div>

<!-- Modal: Generate Payroll -->
@if(Auth::user()->isAdmin())
<div id="generate-payroll-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs hidden p-4">
    <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200 dark:border-slate-800 max-w-md w-full shadow-2xl">
        <div class="flex items-center justify-between mb-4 pb-2 border-b border-slate-100 dark:border-slate-800">
            <h3 class="text-base font-bold text-slate-900 dark:text-white">Generate Staff Payslip</h3>
            <button onclick="document.getElementById('generate-payroll-modal').classList.add('hidden')" class="text-slate-400 hover:text-white text-sm">✕</button>
        </div>

        <form method="POST" action="{{ route('school.payroll.generate') }}" class="space-y-3 text-xs">
            @csrf
            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Select Staff Member *</label>
                <select name="staff_id" required class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-bold">
                    @foreach($staffList as $st)
                    <option value="{{ $st->id }}">{{ $st->name }} ({{ strtoupper($st->role) }})</option>
                    @endforeach
                </select>
            </div>

            <div class="rounded-xl bg-slate-50 dark:bg-slate-800 p-3 text-xs text-slate-600 dark:text-slate-300">Salary, allowances and recurring deductions are loaded from the employee’s effective-dated salary assignment. The system requires complete attendance for every working day in the closed month and creates a draft for review. Statutory rules must be explicitly verified and activated in Salary &amp; Statutory Setup.</div>
            <div class="flex justify-end space-x-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                <button type="button" onclick="document.getElementById('generate-payroll-modal').classList.add('hidden')" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold">Cancel</button>
                <button type="submit" class="primary-btn" style="padding: 0.4rem 1.1rem; font-size: 0.75rem;">Calculate draft payroll</button>
            </div>
        </form>
    </div>
</div>
@endif
@endsection
