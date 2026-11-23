@extends('layouts.app')

@section('title', 'Accounts & Income/Expense — ' . ($school['name'] ?? 'Infix LMS'))

@section('content')
<div class="max-w-[1600px] mx-auto space-y-5 pb-24 sm:pb-8">

    <!-- Infix Breadcrumbs & Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center space-x-2 text-[11px] text-slate-400 font-semibold mb-1">
                <a href="{{ route('dashboard') }}" class="hover:text-slate-600 dark:hover:text-slate-200 transition">Dashboard</a>
                <span>/</span>
                <span class="text-slate-500">Finance</span>
                <span>/</span>
                <span class="text-slate-800 dark:text-slate-200 font-bold">Accounts &amp; Treasury</span>
            </div>

            <div class="flex items-center space-x-2.5">
                <span class="w-8 h-8 rounded-lg flex items-center justify-center text-white text-sm shadow-xs" style="background: var(--system_primary_gradient);">
                    <i class="ti-money"></i>
                </span>
                <h1 class="text-lg sm:text-xl font-black text-slate-900 dark:text-white tracking-tight">
                    Institutional Accounts &amp; P&amp;L
                </h1>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold text-white shadow-2xs" style="background: var(--system_primary_gradient);">
                    Infix Accounts
                </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Comprehensive general ledger, campus operating income, institutional expenses, and treasury cashflow.
            </p>
        </div>

        @if(Auth::user()->isAdmin())
        <div class="flex items-center flex-wrap gap-2">
            <button type="button" 
                    onclick="document.getElementById('add-expense-modal').classList.remove('hidden')"
                    class="px-3 py-1.5 rounded-xl bg-rose-600 hover:bg-rose-500 text-white text-xs font-bold transition shadow-xs flex items-center space-x-1.5">
                <i class="ti-minus text-xs"></i>
                <span>Add Expense</span>
            </button>
            <button type="button" 
                    onclick="document.getElementById('add-income-modal').classList.remove('hidden')"
                    class="primary-btn" style="padding: 0.4rem 1.1rem; font-size: 0.75rem;">
                <i class="ti-plus text-xs"></i>
                <span>Add Income</span>
            </button>
        </div>
        @endif
    </div>

    <form method="GET" action="{{ route('school.accounts.index') }}" class="flex flex-wrap items-end gap-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4">
        <div>
            <label for="accounts-from" class="block text-[10px] font-bold uppercase text-slate-500 mb-1">From date</label>
            <input id="accounts-from" type="date" name="from" value="{{ $from }}" class="rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs px-3 py-2">
        </div>
        <div>
            <label for="accounts-to" class="block text-[10px] font-bold uppercase text-slate-500 mb-1">To date</label>
            <input id="accounts-to" type="date" name="to" value="{{ $to }}" min="{{ $from }}" class="rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs px-3 py-2">
        </div>
        <button type="submit" class="primary-btn" style="padding: 0.5rem 1rem; font-size: 0.75rem;">Apply period</button>
        <a href="{{ route('school.accounts.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800 dark:hover:text-slate-200">Clear dates</a>
        <p class="basis-full text-[10px] text-slate-400">Date filters apply to fee settlements, refunds, other income and expenses. Blank dates mean all recorded history.</p>
    </form>

    <!-- Infix Summary Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-4">
        <div class="white-box single-summery p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs">
            <span class="text-[11px] font-bold text-emerald-600 uppercase tracking-wider block">Net Income</span>
            <div class="text-2xl font-black text-emerald-600 mt-1.5 font-mono">₹{{ number_format($stats['gross_income'], 2) }}</div>
            <p class="text-[10px] text-slate-400 mt-1">Net of recorded fee refunds</p>
        </div>
        <div class="white-box single-summery p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs">
            <span class="text-[11px] font-bold text-blue-600 uppercase tracking-wider block">Net Fee Collections</span>
            <div class="text-2xl font-black text-blue-600 mt-1.5 font-mono">₹{{ number_format($stats['fee_collections'], 2) }}</div>
            <p class="text-[10px] text-slate-400 mt-1">Settled payments less refunds</p>
        </div>
        <div class="white-box single-summery p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs">
            <span class="text-[11px] font-bold text-amber-600 uppercase tracking-wider block">Fee Refunds</span>
            <div class="text-2xl font-black text-amber-600 mt-1.5 font-mono">₹{{ number_format($stats['fee_refunds'], 2) }}</div>
            <p class="text-[10px] text-slate-400 mt-1">Recorded full refunds</p>
        </div>
        <div class="white-box single-summery p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs">
            <span class="text-[11px] font-bold text-rose-500 uppercase tracking-wider block">Total Expenses</span>
            <div class="text-2xl font-black text-rose-500 mt-1.5 font-mono">₹{{ number_format($stats['total_expenses'], 2) }}</div>
            <p class="text-[10px] text-slate-400 mt-1">Campus operating expenditure</p>
        </div>
        <div class="white-box single-summery p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs">
            <span class="text-[11px] font-bold text-purple-600 uppercase tracking-wider block">Net Operating Balance</span>
            <div class="text-2xl font-black {{ $stats['net_balance'] >= 0 ? 'text-purple-600' : 'text-rose-600' }} mt-1.5 font-mono">
                ₹{{ number_format($stats['net_balance'], 2) }}
            </div>
            <p class="text-[10px] text-slate-400 mt-1">Net institutional surplus</p>
        </div>
    </div>

    <!-- Incomes and Expenses Ledgers Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <!-- Income Ledger -->
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-4 shadow-2xs space-y-3">
            <h2 class="text-sm font-black text-slate-900 dark:text-white flex items-center gap-1.5 pb-2 border-b border-slate-100 dark:border-slate-800">
                <span>📈</span> Other Income Ledger
            </h2>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600 dark:text-slate-400">
                    <thead class="bg-slate-50 dark:bg-slate-800/60 uppercase font-black text-[10px] text-slate-500 tracking-wider">
                        <tr>
                            <th class="p-3">Title / Head</th>
                            <th class="p-3">Date</th>
                            <th class="p-3">Method</th>
                            <th class="p-3 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($incomes as $inc)
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                            <td class="p-3">
                                <span class="font-bold text-slate-900 dark:text-white block">{{ $inc->name }}</span>
                                <span class="text-[10px] text-slate-400">{{ $inc->income_head }}</span>
                            </td>
                            <td class="p-3 text-slate-500 whitespace-nowrap">
                                {{ $inc->date->format('M d, Y') }}
                            </td>
                            <td class="p-3">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 uppercase">
                                    {{ $inc->payment_method }}
                                </span>
                            </td>
                            <td class="p-3 text-right font-mono font-bold text-emerald-600 dark:text-emerald-400">
                                ₹{{ number_format($inc->amount, 2) }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="p-6 text-center text-slate-400">No other income transactions recorded.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                {{ $incomes->links() }}
            </div>
        </div>

        <!-- Expense Ledger -->
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-4 shadow-2xs space-y-3">
            <h2 class="text-sm font-black text-slate-900 dark:text-white flex items-center gap-1.5 pb-2 border-b border-slate-100 dark:border-slate-800">
                <span>📉</span> Operating Expense Ledger
            </h2>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600 dark:text-slate-400">
                    <thead class="bg-slate-50 dark:bg-slate-800/60 uppercase font-black text-[10px] text-slate-500 tracking-wider">
                        <tr>
                            <th class="p-3">Expense Head</th>
                            <th class="p-3">Date</th>
                            <th class="p-3">Voucher #</th>
                            <th class="p-3 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($expenses as $exp)
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                            <td class="p-3">
                                <span class="font-bold text-slate-900 dark:text-white block">{{ $exp->name }}</span>
                                <span class="text-[10px] text-slate-400">{{ $exp->expense_head }} &bull; Paid to: {{ $exp->paid_to ?? 'Vendor' }}</span>
                            </td>
                            <td class="p-3 text-slate-500 whitespace-nowrap">
                                {{ $exp->date->format('M d, Y') }}
                            </td>
                            <td class="p-3 font-mono text-[11px] text-purple-600 dark:text-purple-400">
                                {{ $exp->voucher_number ?? 'VCH-N/A' }}
                            </td>
                            <td class="p-3 text-right font-mono font-bold text-rose-600 dark:text-rose-400">
                                ₹{{ number_format($exp->amount, 2) }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="p-6 text-center text-slate-400">No expense vouchers recorded.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                {{ $expenses->links() }}
            </div>
        </div>
    </div>

</div>

<!-- Modal: Add Income -->
@if(Auth::user()->isAdmin())
<div id="add-income-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs hidden p-4">
    <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200 dark:border-slate-800 max-w-md w-full shadow-2xl">
        <div class="flex items-center justify-between mb-4 pb-2 border-b border-slate-100 dark:border-slate-800">
            <h3 class="text-base font-bold text-slate-900 dark:text-white">Record Operating Income</h3>
            <button onclick="document.getElementById('add-income-modal').classList.add('hidden')" class="text-slate-400 hover:text-white text-sm">✕</button>
        </div>

        <form method="POST" action="{{ route('school.accounts.income.store') }}" class="space-y-3 text-xs">
            @csrf
            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Income Particulars *</label>
                <input type="text" name="name" placeholder="Annual Day Ticket Sales / Donation" required class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
            </div>

            <div class="grid grid-cols-2 gap-2.5">
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Income Head *</label>
                    <select name="income_head" required class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-bold">
                        <option value="Admission">Admission Fee</option>
                        <option value="Donation">Charity / Donation</option>
                        <option value="Canteen">Canteen &amp; Tuck Shop</option>
                        <option value="Event">Event / Olympiad</option>
                        <option value="Miscellaneous" selected>Miscellaneous</option>
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Amount (₹) *</label>
                    <input type="number" step="0.01" name="amount" placeholder="5000.00" required class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-mono">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2.5">
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Receipt Date *</label>
                    <input type="date" name="date" value="{{ now()->toDateString() }}" required class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Payment Method</label>
                    <select name="payment_method" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-bold">
                        <option value="cash">Cash Counter</option>
                        <option value="upi">UPI / QR Code</option>
                        <option value="bank_transfer">Direct Bank Transfer</option>
                        <option value="cheque">Demand Draft / Cheque</option>
                    </select>
                </div>
            </div>

            <div class="flex justify-end space-x-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                <button type="button" onclick="document.getElementById('add-income-modal').classList.add('hidden')" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold">Cancel</button>
                <button type="submit" class="primary-btn" style="padding: 0.4rem 1.1rem; font-size: 0.75rem;">Log Income</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Add Expense -->
<div id="add-expense-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs hidden p-4">
    <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200 dark:border-slate-800 max-w-md w-full shadow-2xl">
        <div class="flex items-center justify-between mb-4 pb-2 border-b border-slate-100 dark:border-slate-800">
            <h3 class="text-base font-bold text-slate-900 dark:text-white">Record Expense Voucher</h3>
            <button onclick="document.getElementById('add-expense-modal').classList.add('hidden')" class="text-slate-400 hover:text-white text-sm">✕</button>
        </div>

        <form method="POST" action="{{ route('school.accounts.expense.store') }}" class="space-y-3 text-xs">
            @csrf
            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Expense Title / Item *</label>
                <input type="text" name="name" placeholder="Campus High-Speed Fiber Internet Bill" required class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
            </div>

            <div class="grid grid-cols-2 gap-2.5">
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Expense Head *</label>
                    <select name="expense_head" required class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-bold">
                        <option value="Utilities">Electricity &amp; Utilities</option>
                        <option value="Maintenance">Campus Maintenance</option>
                        <option value="Stationery">Stationery &amp; Print</option>
                        <option value="Equipment">Lab Equipment &amp; IT</option>
                        <option value="Miscellaneous" selected>Miscellaneous</option>
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Amount (₹) *</label>
                    <input type="number" step="0.01" name="amount" placeholder="3200.00" required class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-mono">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2.5">
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Date *</label>
                    <input type="date" name="date" value="{{ now()->toDateString() }}" required class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Paid To / Vendor</label>
                    <input type="text" name="paid_to" placeholder="Airtel Telecom Ltd" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                </div>
            </div>

            <div class="flex justify-end space-x-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                <button type="button" onclick="document.getElementById('add-expense-modal').classList.add('hidden')" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold">Cancel</button>
                <button type="submit" class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-bold">Save Voucher</button>
            </div>
        </form>
    </div>
</div>
@endif
@endsection
