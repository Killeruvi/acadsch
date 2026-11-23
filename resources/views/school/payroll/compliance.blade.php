@extends('layouts.app')
@section('title', 'Payroll Reconciliation & Statutory Compliance')
@section('content')
<div class="max-w-7xl mx-auto space-y-5 pb-20">
    <div class="flex flex-wrap justify-between items-center gap-3">
        <div><h1 class="text-xl font-black">Payroll Reconciliation & Statutory Compliance</h1><p class="text-sm text-slate-500">Internal control totals, remittance evidence and statutory registers.</p></div>
        <a href="{{ route('school.payroll.index', ['month' => $month]) }}" class="border rounded-lg px-3 py-2 text-sm font-bold">Back to payroll</a>
    </div>
    @if(session('success'))<div class="rounded-lg bg-emerald-50 text-emerald-800 p-3 text-sm">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="rounded-lg bg-rose-50 text-rose-800 p-3 text-sm">{{ $errors->first() }}</div>@endif
    <form method="GET" class="bg-white dark:bg-slate-900 border rounded-xl p-4 flex flex-wrap gap-3 items-end">
        <label class="text-sm font-semibold">Payroll month<input type="month" name="month" value="{{ $month }}" class="block mt-1 border rounded-lg p-2 bg-transparent"></label>
        <button class="bg-indigo-600 text-white rounded-lg px-4 py-2 text-sm font-bold">Load period</button>
    </form>
    <section class="grid grid-cols-2 lg:grid-cols-5 gap-3">
        @foreach([['Payroll records',$totals->payroll_count],['Gross pay','₹'.number_format($totals->gross_total,2)],['Net pay','₹'.number_format($totals->net_total,2)],['Employee deductions','₹'.number_format($totals->employee_deductions_total,2)],['Employer statutory cost','₹'.number_format($totals->employer_statutory_total,2)]] as [$label,$value])
        <div class="bg-white dark:bg-slate-900 border rounded-xl p-4"><div class="text-xs text-slate-500">{{ $label }}</div><div class="font-black text-lg mt-1">{{ $value }}</div></div>
        @endforeach
    </section>
    <section class="bg-white dark:bg-slate-900 border rounded-xl p-4 space-y-3">
        <div class="flex flex-wrap justify-between gap-2"><div><h2 class="font-black">Payroll period control</h2><p class="text-xs text-slate-500">Requires every record in the month to be approved and paid. Locks the saved control totals.</p></div><span class="text-xs font-bold {{ $reconciliation ? 'text-emerald-700' : 'text-amber-700' }}">{{ $reconciliation ? 'Reconciled' : 'Not reconciled' }}</span></div>
        @if(!$reconciliation)
        <form method="POST" action="{{ route('school.payroll.compliance.reconcile') }}" class="space-y-2">@csrf<input type="hidden" name="payroll_month" value="{{ $month }}"><textarea name="notes" maxlength="2000" placeholder="Reconciliation notes (optional)" class="w-full border rounded-lg p-2 bg-transparent"></textarea><button class="bg-indigo-600 text-white rounded-lg px-4 py-2 text-sm font-bold">Reconcile & lock period</button></form>
        @else
        <p class="text-sm">Reconciled {{ $reconciliation->reconciled_at }} by administrator #{{ $reconciliation->reconciled_by }}. Saved control snapshot is immutable.</p>
        <pre class="text-xs overflow-auto bg-slate-50 dark:bg-slate-800 rounded-lg p-3">{{ json_encode(json_decode($reconciliation->control_totals, true), JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) }}</pre>
        @endif
        <p class="text-xs text-amber-700">This is an internal payroll control, not bank-statement reconciliation. Bank transaction matching is not yet connected.</p>
    </section>
    <section class="bg-white dark:bg-slate-900 border rounded-xl p-4 space-y-3">
        <div><h2 class="font-black">Calculated statutory liabilities</h2><p class="text-xs text-slate-500">Derived from payroll records for the selected month. Verify applicability and amounts before remittance.</p></div>
        <div class="grid md:grid-cols-4 gap-3">
            @foreach(['EPF'=>'EPF total (employee + employer + EDLI/admin)','ESI'=>'ESI total (employee + employer)','MP_PT'=>'Madhya Pradesh professional tax','TDS'=>'Tax deducted at source'] as $code=>$label)
            <div class="border rounded-lg p-3"><div class="text-xs text-slate-500">{{ $label }}</div><div class="font-black text-lg">₹{{ number_format($liabilities[$code],2) }}</div>
                <a class="text-xs text-indigo-600 font-bold" href="{{ route('school.payroll.compliance.register',['month'=>$month,'rule_code'=>$code]) }}">Download internal register</a>
                @if($reconciliation && $liabilities[$code] > 0)<form method="POST" action="{{ route('school.payroll.compliance.remittances.store') }}" class="mt-2">@csrf<input type="hidden" name="rule_code" value="{{ $code }}"><input type="hidden" name="period_month" value="{{ $month }}"><input type="date" name="due_date" class="w-full border rounded p-1 text-xs" placeholder="Due date"><button class="mt-1 text-xs bg-indigo-600 text-white rounded px-2 py-1">Create remittance record</button></form>@endif
            </div>
            @endforeach
        </div>
    </section>
    <section class="bg-white dark:bg-slate-900 border rounded-xl p-4 space-y-3">
        <h2 class="font-black">Challan & payment evidence</h2>
        <div class="overflow-x-auto"><table class="w-full text-sm"><thead><tr class="text-left border-b"><th class="p-2">Type / Period</th><th class="p-2">Due</th><th class="p-2">Paid</th><th class="p-2">Status / Challan</th><th class="p-2">Record payment</th></tr></thead><tbody>
        @forelse($remittances as $item)<tr class="border-b align-top"><td class="p-2 font-bold">{{ $item->rule_code }}<div class="text-xs text-slate-500">{{ $item->period_month }}</div></td><td class="p-2">₹{{ number_format($item->amount_due,2) }}</td><td class="p-2">₹{{ number_format($item->amount_paid,2) }}</td><td class="p-2">{{ ucfirst($item->status) }}<div class="text-xs text-slate-500">{{ $item->challan_number ?: 'No challan' }}</div></td><td class="p-2">
            @if($item->status !== 'paid')<form method="POST" action="{{ route('school.payroll.compliance.remittances.payment',$item->id) }}" class="grid grid-cols-2 gap-1 min-w-64">@csrf<input type="number" name="amount_paid" min="0.01" step="0.01" required placeholder="Amount paid" class="border rounded p-1"><input type="date" name="paid_on" max="{{ now()->toDateString() }}" required class="border rounded p-1"><input name="challan_number" required minlength="3" maxlength="120" placeholder="Challan number" class="border rounded p-1 col-span-2"><input name="payment_reference" maxlength="120" placeholder="Bank/payment ref" class="border rounded p-1 col-span-2"><input name="variance_reason" maxlength="1000" placeholder="Reason if amount differs" class="border rounded p-1 col-span-2"><button class="bg-emerald-600 text-white rounded p-1 col-span-2">Record evidence</button></form>@else<div class="text-xs text-slate-500">Paid {{ $item->paid_on }}</div>@endif
        </td></tr>@empty<tr><td colspan="5" class="p-3 text-slate-500">No remittance records for this month.</td></tr>@endforelse
        </tbody></table></div>
        <p class="text-xs text-amber-700">Challan numbers and payment references are evidence entered by an administrator; the application does not verify them with EPFO, ESIC, income-tax or MP tax portals.</p>
    </section>

    <section class="bg-white dark:bg-slate-900 border rounded-xl p-4 space-y-3">
        <div><h2 class="font-black">Bank statement import</h2><p class="text-xs text-slate-500">Import a CSV export from the bank. No online banking credentials or bank connection are requested.</p></div>
        <div class="rounded-lg bg-slate-50 dark:bg-slate-800 p-3 text-xs space-y-1">
            <p class="font-bold">Required CSV headers (exact names):</p>
            <code>transaction_date,description,reference,debit,credit</code>
            <p>Use YYYY-MM-DD dates and decimal amounts with up to two decimal places. Exactly one of debit or credit must be greater than zero. Maximum 10,000 rows and 10 MB per file.</p>
        </div>
        <form method="POST" enctype="multipart/form-data" action="{{ route('school.payroll.compliance.bank-statements.import') }}" class="grid md:grid-cols-3 gap-2 items-end">@csrf
            <label class="text-sm">Account label (e.g. Main salary account)<input name="account_label" required maxlength="80" minlength="2" class="block w-full mt-1 border rounded-lg p-2 bg-transparent"></label>
            <label class="text-sm">CSV statement<input type="file" name="statement" accept=".csv,.txt,text/csv" required class="block w-full mt-1 border rounded-lg p-2"></label>
            <button class="bg-indigo-600 text-white rounded-lg px-4 py-2 text-sm font-bold">Import statement</button>
        </form>
        <div class="text-xs text-slate-500">Recent imports</div>
        <div class="overflow-x-auto"><table class="w-full text-xs"><thead><tr class="border-b text-left"><th class="p-2">Imported file / account</th><th class="p-2">Rows</th><th class="p-2">Duplicates</th><th class="p-2">Invalid</th><th class="p-2">Imported by</th></tr></thead><tbody>
        @forelse($imports as $import)<tr class="border-b"><td class="p-2">{{ $import->original_filename }}<div class="text-slate-500">{{ $import->account_label }} · {{ $import->created_at }}</div></td><td class="p-2">{{ $import->row_count }}</td><td class="p-2">{{ $import->duplicate_count }}</td><td class="p-2">{{ $import->invalid_count }}</td><td class="p-2">#{{ $import->imported_by }}</td></tr>@empty<tr><td colspan="5" class="p-2 text-slate-500">No statements imported.</td></tr>@endforelse
        </tbody></table></div>
    </section>
    <section class="bg-white dark:bg-slate-900 border rounded-xl p-4 space-y-3">
        <div><h2 class="font-black">Bank transaction matching</h2><p class="text-xs text-slate-500">Showing up to 250 transactions in {{ $month }}. Only exact-value debit matches are accepted. Matching never changes payroll/payment status.</p></div>
        <div class="overflow-x-auto"><table class="w-full text-xs"><thead><tr class="text-left border-b"><th class="p-2">Date / account</th><th class="p-2">Description / reference</th><th class="p-2">Debit</th><th class="p-2">Credit</th><th class="p-2">Match</th></tr></thead><tbody>
        @forelse($bankTransactions as $transaction)<tr class="border-b align-top"><td class="p-2">{{ $transaction->transaction_date }}<div class="text-slate-500">{{ $transaction->account_label }}</div></td><td class="p-2">{{ $transaction->description }}<div class="text-slate-500">{{ $transaction->bank_reference }}</div></td><td class="p-2">{{ number_format($transaction->debit,2) }}</td><td class="p-2">{{ number_format($transaction->credit,2) }}</td><td class="p-2">
            @if($transaction->match_type)<span class="font-bold text-emerald-700">Matched {{ strtoupper($transaction->match_type) }} #{{ $transaction->matched_entity_id }}</span><div class="text-slate-500">{{ $transaction->matched_at }} · by #{{ $transaction->matched_by }}</div><form method="POST" action="{{ route('school.payroll.compliance.bank-transactions.unmatch',$transaction->id) }}" class="mt-1 space-y-1">@csrf<input name="reason" required minlength="10" maxlength="1000" placeholder="Reason for correction" class="border rounded p-1 w-full"><button class="text-rose-700 underline">Request independent unmatch</button></form>
            @elseif((float)$transaction->debit > 0)
                <form method="POST" action="{{ route('school.payroll.compliance.bank-transactions.match',$transaction->id) }}" class="space-y-1 min-w-56">@csrf<input type="hidden" name="match_type" value="payroll"><select name="matched_entity_id" required class="w-full border rounded p-1 bg-transparent"><option value="">Match payroll…</option>@foreach($payrollCandidates as $candidate)<option value="{{ $candidate->id }}">#{{ $candidate->id }} {{ $candidate->name }} ({{ $candidate->payroll_month }}) · ₹{{ number_format($candidate->net_salary,2) }}</option>@endforeach</select><button class="rounded bg-indigo-600 text-white px-2 py-1">Match payroll</button></form>
                <form method="POST" action="{{ route('school.payroll.compliance.bank-transactions.match',$transaction->id) }}" class="space-y-1 mt-2 min-w-56">@csrf<input type="hidden" name="match_type" value="remittance"><select name="matched_entity_id" required class="w-full border rounded p-1 bg-transparent"><option value="">Match remittance…</option>@foreach($remittanceCandidates as $remittance)<option value="{{ $remittance->id }}">#{{ $remittance->id }} {{ $remittance->rule_code }} · ₹{{ number_format($remittance->amount_paid,2) }}</option>@endforeach</select><button class="rounded bg-emerald-600 text-white px-2 py-1">Match remittance</button></form>
            @else<span class="text-slate-500">Credit · not matchable to outgoing payroll</span>@endif
        </td></tr>@empty<tr><td colspan="5" class="p-2 text-slate-500">No transactions found for this month. Import a bank CSV to begin.</td></tr>@endforelse
        </tbody></table></div>
        <p class="text-xs text-amber-700">Matching is manual and amount-based. It does not establish the beneficiary, clear a bank transfer, or validate the statement's authenticity. Review bank reference and account details independently. For split/partial payments, do not force a match; that workflow is not supported yet.</p>
    </section>
</div>
@endsection
