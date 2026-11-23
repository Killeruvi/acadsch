@extends('layouts.app')

@section('title', 'School Fee Management - School Management System')

@section('content')
<div class="max-w-[1600px] mx-auto space-y-5 pb-24 sm:pb-8">

    @if(Auth::user()->isParent())
    @include('school.parent._nav')
    @endif

    <!-- Infix Breadcrumbs & Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <!-- Breadcrumbs -->
            <div class="flex items-center space-x-2 text-[11px] text-slate-400 font-semibold mb-1">
                <a href="{{ route('dashboard') }}" class="hover:text-slate-600 dark:hover:text-slate-200 transition">Dashboard</a>
                <span>/</span>
                <span class="text-slate-500">Finance</span>
                <span>/</span>
                <span class="text-slate-800 dark:text-slate-200 font-bold">Fee Management</span>
            </div>

            <div class="flex items-center space-x-2.5">
                <span class="w-8 h-8 rounded-lg flex items-center justify-center text-white text-sm shadow-xs" style="background: var(--system_primary_gradient);">
                    <i class="ti-wallet"></i>
                </span>
                <h1 class="text-lg sm:text-xl font-black text-slate-900 dark:text-white tracking-tight">
                    @if(Auth::user()->isParent())
                    Fees &amp; Online Payment
                    @elseif(Auth::user()->isStudent())
                    My Fees &amp; Receipts
                    @else
                    School Fees &amp; Billing
                    @endif
                </h1>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold text-white shadow-2xs" style="background: var(--system_primary_gradient);">
                    Infix Finance
                </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Manage fee structures, generate student invoices, track payments, and verify UPI reconciliations.
            </p>
        </div>

        <div class="flex items-center flex-wrap gap-2">
            @if(Auth::user()->isAdmin())
            <button type="button" 
                    onclick="document.getElementById('bulk-payment-modal').classList.remove('hidden')"
                    class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold transition shadow-xs flex items-center space-x-1.5">
                <span>📑 Bulk Record</span>
            </button>
            <button type="button" 
                    onclick="document.getElementById('generate-invoices-modal').classList.remove('hidden')"
                    class="primary-btn outline" style="padding: 0.4rem 1rem; font-size: 0.75rem;">
                <span>⚡ Generate Invoices</span>
            </button>
            <button type="button" 
                    onclick="document.getElementById('create-structure-modal').classList.remove('hidden')"
                    class="primary-btn" style="padding: 0.4rem 1.1rem; font-size: 0.75rem;">
                <i class="ti-plus text-xs"></i>
                <span>Add Structure</span>
            </button>
            @endif
        </div>
    </div>

    <!-- Infix Stats Bar (.white-box.single-summery) -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="white-box single-summery p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs">
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Total Invoiced</span>
            <div class="text-2xl font-black text-slate-900 dark:text-white mt-1.5 font-mono">₹{{ number_format($stats['total_billed'], 2) }}</div>
            <p class="text-[10px] text-slate-400 mt-1">Cumulative student billings</p>
        </div>
        <div class="white-box single-summery p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs">
            <span class="text-[11px] font-bold text-emerald-600 uppercase tracking-wider block">Total Paid</span>
            <div class="text-2xl font-black gradient-color2 mt-1.5 font-mono">₹{{ number_format($stats['total_collected'], 2) }}</div>
            <p class="text-[10px] text-emerald-600 font-semibold mt-1">Realized collections</p>
        </div>
        <div class="white-box single-summery p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs">
            <span class="text-[11px] font-bold text-rose-500 uppercase tracking-wider block">Outstanding Dues</span>
            <div class="text-2xl font-black text-rose-500 mt-1.5 font-mono">₹{{ number_format($stats['total_pending'], 2) }}</div>
            <p class="text-[10px] text-slate-400 mt-1">Pending payments</p>
        </div>
        <div class="white-box single-summery p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs">
            <span class="text-[11px] font-bold text-purple-600 dark:text-purple-400 uppercase tracking-wider block">
                @if(Auth::user()->isParent() || Auth::user()->isStudent())
                Invoices
                @else
                Structures
                @endif
            </span>
            <div class="text-2xl font-black text-purple-600 dark:text-purple-400 mt-1.5 font-mono">
                @if(Auth::user()->isParent() || Auth::user()->isStudent())
                {{ $invoices->total() }}
                @else
                {{ $stats['structures_count'] }}
                @endif
            </div>
            <p class="text-[10px] text-slate-400 mt-1">Active billing policies</p>
        </div>
    </div>

    <!-- Main Grid: Fee Policies & Invoices Table -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-3.5">

        <!-- Left: Class Fee Policy Structures (1 Col) -->
        <div class="space-y-3">
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-3.5 shadow-2xs">
                <div class="flex items-center justify-between mb-3 pb-2 border-b border-slate-100 dark:border-slate-800">
                    <div>
                        <h2 class="text-sm font-black text-slate-900 dark:text-white">Fee Breakdown</h2>
                    </div>
                    <span class="text-[10px] font-bold text-slate-500">
                        {{ $selectedClass ? $selectedClass->display_name : 'Class Tariff' }}
                    </span>
                </div>

                <div class="space-y-2">
                    @php
                        $displayStructures = $selectedClass ? $selectedClass->feeStructures : ($structures ?? collect());
                    @endphp
                    @if($displayStructures && $displayStructures->isNotEmpty())
                    @foreach($displayStructures as $structure)
                    <div class="p-2.5 rounded-lg border border-slate-100 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-800/40 flex items-center justify-between">
                        <div>
                            <span class="text-xs font-bold text-slate-900 dark:text-white block">{{ $structure->fee_title }}</span>
                            <div class="flex items-center gap-1.5 mt-0.5">
                                <span class="px-1.5 py-0.2 rounded text-[9px] font-mono font-bold bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 capitalize">{{ $structure->due_frequency }}</span>
                                <span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-500 capitalize">{{ $structure->fee_type }}</span>
                                @if(!$selectedClass && $structure->academicClass)
                                <span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-purple-100 dark:bg-purple-900/40 text-purple-700 dark:text-purple-300">{{ $structure->academicClass->display_name }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="text-right flex items-center space-x-2">
                            <span class="text-xs font-black text-slate-900 dark:text-white font-mono">₹{{ number_format($structure->amount, 2) }}</span>
                            @if(Auth::user()->isAdmin())
                            <form method="POST" action="{{ route('school.fees.structures.destroy', $structure->id) }}" onsubmit="return confirm('Remove fee structure?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-slate-400 hover:text-rose-500 text-xs p-1">✕</button>
                            </form>
                            @endif
                        </div>
                    </div>
                    @endforeach
                    @if(isset($structures) && $structures instanceof \Illuminate\Pagination\LengthAwarePaginator && !$selectedClass)
                    <div class="pt-2 border-t border-slate-100 dark:border-slate-800">
                        {{ $structures->links() }}
                    </div>
                    @endif
                    @else
                    <div class="p-4 text-center text-xs text-slate-400">
                        @if(Auth::user()->isAdmin())
                        <span class="text-[11px] block">Standard Class Tuition applies (₹4,500.00 / mo). Click "Add Structure" to customize.</span>
                        @else
                        <span class="text-[11px] block">Standard monthly tuition applies for this academic tier.</span>
                        @endif
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Right: Invoices & Payment Settlement (2 Cols) -->
        <div class="lg:col-span-2 space-y-3">
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-3.5 shadow-2xs">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 mb-3 pb-2 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center space-x-2">
                        <h2 class="text-sm font-black text-slate-900 dark:text-white">Fee Invoices &amp; Receipts</h2>
                        @if(!empty($filter))
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 uppercase">
                            Filter: {{ $filter }}
                        </span>
                        @endif
                    </div>

                    <div class="flex items-center flex-wrap gap-2">
                        @if(Auth::user()->isAdmin() || Auth::user()->isTeacher())
                        <!-- Class Filter (Staff only) -->
                        @if($classes->isNotEmpty())
                        <form method="GET" action="{{ route('school.fees.index') }}" class="inline-flex">
                            <input type="hidden" name="filter" value="{{ $filter ?? '' }}">
                            <input type="hidden" name="search" value="{{ $search ?? '' }}">
                            <select name="class_id" onchange="this.form.submit()" class="px-2.5 py-1 rounded-lg border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800 text-xs font-bold text-slate-900 dark:text-white">
                                <option value="">-- All Classes --</option>
                                @foreach($classes as $c)
                                <option value="{{ $c->id }}" {{ $selectedClass && $selectedClass->id == $c->id ? 'selected' : '' }}>{{ $c->display_name }}</option>
                                @endforeach
                            </select>
                        </form>
                        @endif
                        @endif
                    </div>
                </div>

                <!-- Live Debounced Search & Filter Tabs -->
                <div class="space-y-2 mb-3">
                    <div class="flex flex-col md:flex-row items-stretch md:items-center gap-2">
                        <!-- Live Search Bar (Debounced, no Enter needed) -->
                        <form method="GET" action="{{ route('school.fees.index') }}" class="flex-1">
                            <input type="hidden" name="class_id" value="{{ $selectedClass?->id ?? '' }}">
                            <input type="hidden" name="filter" value="{{ $filter ?? '' }}">
                            <div class="relative">
                                <input type="text" name="search" value="{{ $search ?? '' }}" 
                                       placeholder="Live search by student name, roll no, invoice no..."
                                       oninput="clearTimeout(this._searchTimer); this._searchTimer = setTimeout(() => this.form.submit(), 400);"
                                       class="w-full px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/80 text-xs text-slate-900 dark:text-white font-medium focus:ring-2 focus:ring-emerald-500">
                                @if(!empty($search))
                                <a href="{{ route('school.fees.index', ['class_id' => $selectedClass?->id, 'filter' => $filter]) }}" class="absolute right-2.5 top-1.5 text-xs text-slate-400 hover:text-rose-500 font-bold">✕</a>
                                @endif
                            </div>
                        </form>

                        <!-- Quick 1-Click Drill-down Filter Pills -->
                        <div class="flex items-center flex-wrap gap-1 text-[11px] font-bold">
                            <a href="{{ route('school.fees.index', ['class_id' => $selectedClass?->id, 'search' => $search ?? '']) }}"
                               class="px-2.5 py-1 rounded-lg border transition {{ empty($filter) ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900 border-transparent' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700' }}">
                                All
                            </a>
                            <a href="{{ route('school.fees.index', ['class_id' => $selectedClass?->id, 'filter' => 'defaulters', 'search' => $search ?? '']) }}"
                               class="px-2.5 py-1 rounded-lg border transition flex items-center space-x-1 {{ ($filter ?? '') === 'defaulters' ? 'bg-rose-600 text-white border-rose-600' : 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border-rose-200 dark:border-rose-900' }}">
                                <span>⚠️ Defaulters List</span>
                                <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-rose-200 dark:bg-rose-900 text-rose-900 dark:text-rose-200">{{ $stats['defaulters_count'] ?? 0 }}</span>
                            </a>
                            <a href="{{ route('school.fees.index', ['class_id' => $selectedClass?->id, 'filter' => 'unpaid', 'search' => $search ?? '']) }}"
                               class="px-2.5 py-1 rounded-lg border transition {{ ($filter ?? '') === 'unpaid' ? 'bg-amber-600 text-white border-amber-600' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700' }}">
                                Unpaid
                            </a>
                            <a href="{{ route('school.fees.index', ['class_id' => $selectedClass?->id, 'filter' => 'partial', 'search' => $search ?? '']) }}"
                               class="px-2.5 py-1 rounded-lg border transition {{ ($filter ?? '') === 'partial' ? 'bg-blue-600 text-white border-blue-600' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700' }}">
                                Partial
                            </a>
                            <a href="{{ route('school.fees.index', ['class_id' => $selectedClass?->id, 'filter' => 'paid', 'search' => $search ?? '']) }}"
                               class="px-2.5 py-1 rounded-lg border transition {{ ($filter ?? '') === 'paid' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700' }}">
                                Settled
                            </a>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-600 dark:text-slate-400">
                        <thead class="bg-slate-50 dark:bg-slate-800/60 uppercase font-black text-[10px] text-slate-500 tracking-wider">
                            <tr>
                                <th class="p-3">Invoice No</th>
                                <th class="p-3">Student Name</th>
                                <th class="p-3">Amount</th>
                                <th class="p-3">Paid</th>
                                <th class="p-3">Status</th>
                                <th class="p-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse($invoices as $inv)
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                                <td class="p-3 font-mono font-bold text-emerald-600 dark:text-emerald-400">
                                    {{ $inv->invoice_number }}
                                    <span class="text-[10px] text-slate-400 block font-sans">{{ $inv->title }}</span>
                                </td>
                                <td class="p-3">
                                    <span class="font-bold text-slate-900 dark:text-white block">{{ $inv->student ? $inv->student->name : 'Scholar' }}</span>
                                    <span class="text-[10px] text-slate-400">{{ $inv->student?->currentClass?->display_name }}</span>
                                </td>
                                <td class="p-3 font-mono font-bold text-slate-900 dark:text-white">
                                    ₹{{ number_format($inv->total_due, 2) }}
                                </td>
                                <td class="p-3 font-mono text-emerald-600 dark:text-emerald-400 font-bold">
                                    ₹{{ number_format($inv->paid_amount, 2) }}
                                </td>
                                <td class="p-3">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $inv->status === 'paid' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300' }}">
                                        {{ strtoupper($inv->status) }}
                                    </span>
                                </td>
                                <td class="p-3 text-right space-x-1 whitespace-nowrap">
                                    @if($inv->status !== 'paid')
                                    <button type="button" 
                                            onclick="openPayModal({{ $inv->id }}, '{{ $inv->invoice_number }}', {{ $inv->total_due - $inv->paid_amount }})"
                                            class="px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-xs transition">
                                        @if(Auth::user()->isParent() || Auth::user()->isStudent())
                                        💳 Pay Online
                                        @else
                                        Collect Fee
                                        @endif
                                    </button>
                                    @if(Auth::user()->isAdmin())
                                    <button type="button"
                                            onclick="openWaiverModal({{ $inv->id }}, '{{ $inv->invoice_number }}', {{ $inv->total_due - $inv->paid_amount }})"
                                            class="px-2.5 py-1.5 rounded-xl bg-purple-50 dark:bg-purple-950/60 hover:bg-purple-100 text-purple-700 dark:text-purple-300 font-bold text-xs border border-purple-200 dark:border-purple-800 transition">
                                        Waiver
                                    </button>
                                    @endif
                                    @endif

                                    @if($inv->payments && $inv->payments->isNotEmpty())
                                    <a href="{{ route('school.fees.receipt', $inv->payments->last()->id) }}" 
                                       class="px-2.5 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 font-bold text-xs inline-block transition">
                                        🧾 Receipt
                                    </a>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="p-8 text-center text-slate-400">
                                    @if(Auth::user()->isParent() || Auth::user()->isStudent())
                                    All fee dues are cleared! No outstanding invoices found.
                                    @else
                                    No fee invoices found for this selection. Click "Generate Class Invoices" to issue monthly dues.
                                    @endif
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $invoices->withQueryString()->links() }}
                </div>
            </div>
        </div>

    </div>

    <!-- Settled Fee Payment Receipts & Reconciliation Ledger (Paginated) -->
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-4 shadow-2xs">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3 pb-2 border-b border-slate-100 dark:border-slate-800">
            <div>
                <h3 class="text-sm font-black text-slate-900 dark:text-white flex items-center gap-1.5">
                    <span>🧾</span> Fee Payment Receipts &amp; Transaction Ledger
                </h3>
                <span class="text-[11px] text-slate-400 font-medium">Reconciled electronic settlements, UPI payments &amp; counter receipts</span>
            </div>
            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300">
                Audited Ledger
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600 dark:text-slate-400">
                <thead class="bg-slate-50 dark:bg-slate-800/60 uppercase font-black text-[10px] text-slate-500 tracking-wider">
                    <tr>
                        <th class="p-3">Receipt No</th>
                        @if(!Auth::user()->isStudent())
                        <th class="p-3">Student</th>
                        @endif
                        <th class="p-3">Invoice Ref</th>
                        <th class="p-3">Amount</th>
                        <th class="p-3">Method</th>
                        <th class="p-3">Txn Ref / UTR</th>
                        <th class="p-3">Paid Date</th>
                        <th class="p-3">Status</th>
                        <th class="p-3 text-right">Receipt</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($payments as $pmt)
                    <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                        <td class="p-3 font-mono font-bold text-emerald-600 dark:text-emerald-400">
                            {{ $pmt->receipt_number }}
                        </td>
                        @if(!Auth::user()->isStudent())
                        <td class="p-3">
                            <span class="font-bold text-slate-900 dark:text-white block">{{ $pmt->student?->name }}</span>
                            <span class="text-[10px] text-slate-400 font-mono">{{ $pmt->student?->currentClass?->display_name }}</span>
                        </td>
                        @endif
                        <td class="p-3 font-mono text-slate-700 dark:text-slate-300">
                            {{ $pmt->invoice?->invoice_number ?? 'N/A' }}
                        </td>
                        <td class="p-3 font-mono font-bold text-slate-900 dark:text-white">
                            ₹{{ number_format($pmt->amount, 2) }}
                        </td>
                        <td class="p-3">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 uppercase">
                                {{ $pmt->payment_method }}
                            </span>
                        </td>
                        <td class="p-3 font-mono text-[11px] text-slate-500">
                            {{ $pmt->transaction_reference ?: ($pmt->transaction_id ?: 'TXN-CONFIRMED') }}
                        </td>
                        <td class="p-3 text-slate-500 whitespace-nowrap">
                            {{ $pmt->paid_at ? $pmt->paid_at->format('M d, Y h:i A') : $pmt->created_at->format('M d, Y') }}
                        </td>
                        <td class="p-3">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $pmt->status === 'completed' || $pmt->status === 'successful' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300' }}">
                                {{ strtoupper($pmt->status) }}
                            </span>
                        </td>
                        <td class="p-3 text-right whitespace-nowrap">
                            <a href="{{ route('school.fees.receipt', $pmt->id) }}" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 font-bold text-xs inline-block transition">
                                🧾 View
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ Auth::user()->isStudent() ? '8' : '9' }}" class="p-6 text-center text-slate-400">
                            No settled payment receipts found in ledger.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(isset($payments) && $payments instanceof \Illuminate\Pagination\LengthAwarePaginator)
        <div class="mt-3 pt-3 border-t border-slate-100 dark:border-slate-800">
            {{ $payments->links() }}
        </div>
        @endif
    </div>

</div>

@if(Auth::user()->isAdmin())
<!-- Modal: Add Fee Structure -->
<div id="create-structure-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs hidden">
    <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200 dark:border-slate-800 max-w-md w-full mx-4 shadow-2xl">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-bold text-slate-900 dark:text-white">Add Fee Structure</h3>
            <button onclick="document.getElementById('create-structure-modal').classList.add('hidden')" class="text-slate-400 hover:text-white text-sm">✕</button>
        </div>

        <form method="POST" action="{{ route('school.fees.structures.store') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Applicable Class *</label>
                <select name="class_id" required class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white font-bold">
                    @foreach($classes as $c)
                    <option value="{{ $c->id }}" {{ $selectedClass && $selectedClass->id == $c->id ? 'selected' : '' }}>{{ $c->display_name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Fee Item Title *</label>
                <input type="text" name="fee_title" placeholder="e.g. Monthly Tuition Fee" required
                       class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Type</label>
                    <select name="fee_type" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                        <option value="tuition">Tuition</option>
                        <option value="exam">Examination</option>
                        <option value="lab">Science / Computer Lab</option>
                        <option value="sports">Sports &amp; Games</option>
                        <option value="transport">School Bus</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Frequency</label>
                    <select name="due_frequency" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                        <option value="monthly">Monthly</option>
                        <option value="termly">Termly (3x/yr)</option>
                        <option value="annually">Annually</option>
                        <option value="one_time">One-Time</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Amount (₹) *</label>
                <input type="number" step="0.01" name="amount" placeholder="4500.00" required
                       class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white font-bold">
            </div>

            <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                <button type="button" onclick="document.getElementById('create-structure-modal').classList.add('hidden')"
                        class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold">
                    Cancel
                </button>
                <button type="submit" 
                        class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-sm shadow-emerald-600/30">
                    Save Fee Policy
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Generate Invoices -->
<div id="generate-invoices-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs hidden">
    <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200 dark:border-slate-800 max-w-md w-full mx-4 shadow-2xl">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-bold text-slate-900 dark:text-white">Batch Generate Fee Invoices</h3>
            <button onclick="document.getElementById('generate-invoices-modal').classList.add('hidden')" class="text-slate-400 hover:text-white text-sm">✕</button>
        </div>

        <form method="POST" action="{{ route('school.fees.generate-invoices') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Target Class *</label>
                <select name="class_id" required class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-bold text-slate-900 dark:text-white">
                    @foreach($classes as $c)
                    <option value="{{ $c->id }}">{{ $c->display_name }} ({{ $c->students->count() }} students)</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Billing Month *</label>
                    <input type="month" name="billing_month" value="{{ now()->format('Y-m') }}" required
                           class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Due Date *</label>
                    <input type="date" name="due_date" value="{{ now()->endOfMonth()->toDateString() }}" required
                           class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                </div>
            </div>

            <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                <button type="button" onclick="document.getElementById('generate-invoices-modal').classList.add('hidden')"
                        class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold">
                    Cancel
                </button>
                <button type="submit" 
                        class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold shadow-sm shadow-blue-600/30">
                    Generate Dues
                </button>
            </div>
        </form>
    </div>
</div>
@endif

<!-- Modal: Record Payment Received (Cashier + Admin) -->
<div id="collect-fee-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs hidden p-4 overflow-y-auto">
    <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200 dark:border-slate-800 max-w-lg w-full mx-auto shadow-2xl my-8">
        <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100 dark:border-slate-800">
            <div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <span>💰</span> Record Payment Received
                </h3>
                <p class="text-xs text-slate-500">Invoice: <span id="modal-inv-num" class="font-mono font-bold text-blue-600 dark:text-blue-400"></span></p>
            </div>
            <button onclick="closePayModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-white text-lg font-bold">✕</button>
        </div>

        @php $qrUrl = \App\Models\AcademySetting::get('payment_qr_image') ? asset('storage/' . \App\Models\AcademySetting::get('payment_qr_image')) : null; $upiVpa = \App\Models\AcademySetting::get('upi_vpa', ''); @endphp
        @if($qrUrl)
        <div class="text-center mb-4 p-3 bg-slate-50 dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700">
            <img src="{{ $qrUrl }}" alt="School Payment QR" class="w-32 h-32 mx-auto rounded-lg object-contain">
            <p class="text-[10px] text-slate-500 mt-1">School QR Code — Parent scans externally</p>
            @if($upiVpa)<p class="text-[10px] font-mono text-slate-600 dark:text-slate-400">UPI: <strong>{{ $upiVpa }}</strong></p>@endif
        </div>
        @endif

        <form id="pay-form" method="POST" action="" class="space-y-3">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Amount Received (₹) *</label>
                    <input type="number" step="0.01" min="0.01" id="modal-amount" name="amount" required class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm font-mono font-bold text-slate-900 dark:text-white focus:ring-1 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Payment Date *</label>
                    <input type="date" name="payment_date" id="modal-pay-date" max="{{ date('Y-m-d') }}" value="{{ date('Y-m-d') }}" required class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm text-slate-900 dark:text-white focus:ring-1 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Payment Mode *</label>
                    <select name="payment_mode" required class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm text-slate-900 dark:text-white focus:ring-1 focus:ring-emerald-500">
                        <option value="cash">Cash</option>
                        <option value="upi" selected>UPI</option>
                        <option value="qr">QR Scan</option>
                        <option value="bank">Bank Transfer</option>
                        <option value="cheque">Cheque</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">UTR / Reference No.</label>
                    <input type="text" name="reference_no" maxlength="100" placeholder="UTR / TXN ID / Cheque No." class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm font-mono text-slate-900 dark:text-white focus:ring-1 focus:ring-emerald-500">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Notes</label>
                    <input type="text" name="notes" maxlength="500" placeholder="Optional remarks" class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm text-slate-900 dark:text-white focus:ring-1 focus:ring-emerald-500">
                </div>
            </div>
            <div class="flex items-center justify-end gap-3 pt-2 border-t border-slate-100 dark:border-slate-800">
                <button type="button" onclick="closePayModal()" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow">
                    ✅ Record Payment &amp; Issue Receipt
                </button>
            </div>
        </form>
    </div>
</div>

@if(Auth::user()->isAdmin())
<!-- Modal: Approve Scholarship / Fee Waiver -->
<div id="waiver-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs hidden p-4">
    <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200 dark:border-slate-800 max-w-md w-full shadow-2xl">
        <div class="flex items-center justify-between mb-4 pb-2 border-b border-slate-100 dark:border-slate-800">
            <div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Approve Scholarship / Fee Waiver</h3>
                <p class="text-xs text-slate-500">Invoice: <span id="waiver-inv-num" class="font-mono font-bold text-purple-600 dark:text-purple-400"></span></p>
            </div>
            <button onclick="closeWaiverModal()" class="text-slate-400 hover:text-white text-sm font-bold">✕</button>
        </div>

        <form id="waiver-form" method="POST" action="" class="space-y-4 text-xs">
            @csrf
            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Waiver / Scholarship Amount (₹) *</label>
                <input type="number" step="0.01" min="1" id="waiver-amount" name="waiver_amount" required
                       class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-bold text-slate-900 dark:text-white">
                <span class="text-[10px] text-slate-400 mt-1 block">Maximum waiver: ₹<span id="waiver-max-display">0.00</span></span>
            </div>

            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Reason / Category *</label>
                <input type="text" name="reason" placeholder="e.g. Merit Scholarship / Sibling Concession / Financial Hardship" required
                       class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
            </div>

            <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                <button type="button" onclick="closeWaiverModal()"
                        class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold">
                    Cancel
                </button>
                <button type="submit" 
                        class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold shadow-xs">
                    Approve Waiver
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Bulk Payment Recording -->
<div id="bulk-payment-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs hidden p-4 overflow-y-auto">
    <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200 dark:border-slate-800 max-w-2xl w-full mx-auto shadow-2xl my-8">
        <div class="flex items-center justify-between mb-4 pb-2 border-b border-slate-100 dark:border-slate-800">
            <div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Bulk Payment Recording</h3>
                <p class="text-xs text-slate-500">Record payments for multiple fee invoices in a single transaction.</p>
            </div>
            <button onclick="document.getElementById('bulk-payment-modal').classList.add('hidden')" class="text-slate-400 hover:text-white text-sm font-bold">✕</button>
        </div>

        <form method="POST" action="{{ route('school.fees.bulk-payments') }}" class="space-y-4 text-xs">
            @csrf
            <div class="overflow-x-auto max-h-[350px]">
                <table class="w-full text-left text-xs text-slate-600 dark:text-slate-400">
                    <thead class="bg-slate-50 dark:bg-slate-800/80 font-black text-[10px] text-slate-500 uppercase sticky top-0">
                        <tr>
                            <th class="p-2">Invoice / Student</th>
                            <th class="p-2">Outstanding</th>
                            <th class="p-2">Collect Amount</th>
                            <th class="p-2">Method</th>
                            <th class="p-2">Ref / Cheque</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @php $unpaidInvoices = $invoices->filter(fn($i) => $i->status !== 'paid')->take(10); @endphp
                        @forelse($unpaidInvoices as $idx => $inv)
                        @php $due = max(0, $inv->total_due - $inv->paid_amount); @endphp
                        <tr>
                            <td class="p-2">
                                <input type="hidden" name="payments[{{ $idx }}][invoice_id]" value="{{ $inv->id }}">
                                <span class="font-bold text-slate-900 dark:text-white block">{{ $inv->student?->name ?? 'Student' }}</span>
                                <span class="font-mono text-[10px] text-slate-400">{{ $inv->invoice_number }}</span>
                            </td>
                            <td class="p-2 font-mono font-bold text-rose-500">
                                ₹{{ number_format($due, 2) }}
                            </td>
                            <td class="p-2">
                                <input type="number" step="0.01" min="1" max="{{ $due }}" name="payments[{{ $idx }}][amount]" value="{{ $due }}"
                                       class="w-24 px-2 py-1 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-bold text-slate-900 dark:text-white">
                            </td>
                            <td class="p-2">
                                <select name="payments[{{ $idx }}][payment_method]" class="px-2 py-1 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs">
                                    <option value="cash">Cash</option>
                                    <option value="upi">UPI</option>
                                    <option value="card">Card</option>
                                    <option value="cheque">Cheque</option>
                                </select>
                            </td>
                            <td class="p-2">
                                <input type="text" name="payments[{{ $idx }}][transaction_reference]" placeholder="Ref #"
                                       class="w-24 px-2 py-1 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs">
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="p-4 text-center text-slate-400">No outstanding invoices available in current selection.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                <button type="button" onclick="document.getElementById('bulk-payment-modal').classList.add('hidden')"
                        class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 rounded-xl bg-slate-900 dark:bg-white text-white dark:text-slate-900 text-xs font-bold shadow-xs">
                    Commit Bulk Payments
                </button>
            </div>
        </form>
    </div>
</div>
@endif

<script>
let currentInvoiceId = null;
let currentDueAmount = 0;

function openWaiverModal(invId, invNo, balance) {
    document.getElementById('waiver-inv-num').innerText = invNo;
    document.getElementById('waiver-amount').value = balance;
    document.getElementById('waiver-amount').max = balance;
    document.getElementById('waiver-max-display').innerText = Number(balance).toFixed(2);
    document.getElementById('waiver-form').action = '/school/fees/invoices/' + invId + '/waiver';
    document.getElementById('waiver-modal').classList.remove('hidden');
}

function closeWaiverModal() {
    document.getElementById('waiver-modal').classList.add('hidden');
}

function openPayModal(invId, invNo, dueAmount) {
    currentInvoiceId = invId;
    currentDueAmount = dueAmount;
    document.getElementById('modal-inv-num').innerText = invNo;
    var amountEl = document.getElementById('modal-amount');
    if (amountEl) { amountEl.value = Number(dueAmount).toFixed(2); amountEl.max = Number(dueAmount).toFixed(2); }
    var form = document.getElementById('pay-form');
    if (form) { form.action = '/school/fees/invoices/' + invId + '/pay'; }
    document.getElementById('collect-fee-modal').classList.remove('hidden');
}

function closePayModal() {
    document.getElementById('collect-fee-modal').classList.add('hidden');
}

function resetUpiStep() { /* removed - no longer needed */ }

// generateUpiIntent removed - no UPI gateway configured
</script>

@endsection
