@extends('layouts.app')

@section('title', 'Online Fees & Invoicing' . ' — ' . ($school['name'] ?? 'PrimeOS Academy'))

@section('content')
<div x-data="{ openInvoiceModal: false, openMonthlyModal: false, openConfigModal: false, openPayModal: false, activeInvoice: null, targetScope: 'all' }" class="space-y-4">

    <!-- Header & Action Row -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-1 border-b border-slate-200 dark:border-slate-800">
        <div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight">Online Fees &amp; Invoicing</h1>
            <div class="flex flex-wrap items-center gap-1.5 mt-1">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                    Module: Fees &amp; Invoicing
                </span>
                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                    Gateway: Verified Active
                </span>
                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-purple-50 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-800">
                    Cycle: Monthly Recurring
                </span>
            </div>
        </div>

        @if(Auth::user()->isAdmin())
        <div class="flex items-center flex-wrap gap-2">
            <!-- 1. Auto Monthly Invoices Button -->
            <button @click.stop="openMonthlyModal = true" 
                    type="button"
                    class="inline-flex items-center space-x-1.5 px-3 py-1.5 bg-purple-600 hover:bg-purple-500 text-white rounded-lg text-xs font-bold shadow-xs transition">
                <span>⚡ Auto Monthly Invoices</span>
            </button>

            <!-- 2. Monthly Fee Automation Settings Button -->
            <button @click.stop="openConfigModal = true" 
                    type="button"
                    class="inline-flex items-center space-x-1.5 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg text-xs font-bold shadow-xs transition">
                <span>⚙️ Monthly Settings</span>
            </button>

            <!-- Automated Monthly Auto-Charge Trigger -->
            <form method="POST" action="{{ route('fees.generate-monthly') }}" class="inline">
                @csrf
                <button type="submit"
                        class="inline-flex items-center space-x-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-bold shadow-xs transition"
                        title="Auto-calculate and issue monthly tuition dues for all active scholars">
                    <span>⚡ Run Auto-Charge Now</span>
                </button>
            </form>

            <!-- Manual invoice button deprecated (kept hidden for backward compatibility) -->
            <button @click.stop="openInvoiceModal = true" 
                    type="button"
                    style="display:none;"
                    class="hidden"
                    aria-hidden="true">
                <span>+ Issue Manual Invoice</span>
            </button>
        </div>
        @endif
    </div>

    <!-- Automated Monthly Billing Information Banner -->
    <div class="p-3.5 bg-purple-50/80 dark:bg-purple-950/40 border border-purple-200 dark:border-purple-800 rounded-xl text-xs flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2.5">
        <div class="flex items-start sm:items-center space-x-2.5">
            <span class="text-lg leading-none">⚡</span>
            <div>
                <strong class="font-bold text-purple-950 dark:text-purple-200 block">Automated Monthly Charging &amp; Dynamic Fee Calculation Active</strong>
                <p class="text-[11px] text-purple-700 dark:text-purple-300">
                    Manual invoice entry is replaced with monthly automated billing. The system automatically calculates dues per student based on class fee structures, concessions, and outstanding arrears.
                </p>
            </div>
        </div>
        <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-purple-600 text-white shrink-0 shadow-2xs">
            Cycle: Automated Monthly
        </span>
    </div>

    <!-- Financial KPI Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div class="bg-white dark:bg-slate-900 p-3.5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wider">Total Invoiced</span>
                <span class="text-[10px] px-1.5 py-0.5 rounded font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400">Demand</span>
            </div>
            <div class="text-xl sm:text-2xl font-black font-mono text-slate-900 dark:text-white mt-1">{{ $currencySymbol ?? '₹' }}{{ number_format($totalInvoiced, 2) }}</div>
            <div class="text-[10px] text-slate-400 mt-1">Campus gross billings</div>
        </div>

        <div class="bg-white dark:bg-slate-900 p-3.5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wider">Settled to Date</span>
                <span class="text-[10px] px-1.5 py-0.5 rounded font-bold bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300">Verified</span>
            </div>
            <div class="text-xl sm:text-2xl font-black font-mono text-emerald-600 dark:text-emerald-400 mt-1">{{ $currencySymbol ?? '₹' }}{{ number_format($totalPaid, 2) }}</div>
            <div class="text-[10px] text-emerald-600 dark:text-emerald-400 mt-1">Total collections verified</div>
        </div>

        <div class="bg-white dark:bg-slate-900 p-3.5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wider">Outstanding Arrears</span>
                <span class="text-[10px] px-1.5 py-0.5 rounded font-bold {{ $totalArrears > 0 ? 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300' : 'bg-slate-100 text-slate-600' }}">Balance</span>
            </div>
            <div class="text-xl sm:text-2xl font-black font-mono {{ $totalArrears > 0 ? 'text-amber-500' : 'text-slate-900 dark:text-white' }} mt-1">
                {{ $currencySymbol ?? '₹' }}{{ number_format($totalArrears, 2) }}
            </div>
            <div class="text-[10px] text-slate-400 mt-1">Pending student balances</div>
        </div>
    </div>

    <!-- Invoices List Table -->
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-xs overflow-hidden">
        <div class="p-3.5 border-b border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h3 class="font-black text-slate-900 dark:text-white text-sm">
                    {{ Auth::user()->isStudent() ? 'My Academic Invoices' : 'All Student Fee Invoices' }}
                </h3>
                <span class="text-[11px] text-slate-400 font-medium">Digital Gateway Verified</span>
            </div>

            @if(!Auth::user()->isStudent())
            <!-- Invoicing Mode Filter Pills -->
            <div class="flex items-center space-x-1.5 text-xs">
                @php $currentType = request('billing_type', 'all'); @endphp
                <a href="{{ route('fees.index', array_merge(request()->query(), ['billing_type' => 'all'])) }}" 
                   class="px-2.5 py-1 rounded-lg font-bold transition {{ $currentType === 'all' ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200' }}">
                    All
                </a>
                <a href="{{ route('fees.index', array_merge(request()->query(), ['billing_type' => 'monthly_auto'])) }}" 
                   class="px-2.5 py-1 rounded-lg font-bold transition flex items-center space-x-1 {{ $currentType === 'monthly_auto' ? 'bg-purple-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-purple-700 dark:text-purple-300 hover:bg-slate-200' }}">
                    <span>⚡ Monthly Auto</span>
                    @if(isset($monthlyAutoCount))
                    <span class="text-[10px] px-1.5 py-0.2 rounded-full {{ $currentType === 'monthly_auto' ? 'bg-purple-800 text-purple-200' : 'bg-purple-100 dark:bg-purple-900 text-purple-700 dark:text-purple-300' }}">{{ $monthlyAutoCount }}</span>
                    @endif
                </a>
                <a href="{{ route('fees.index', array_merge(request()->query(), ['billing_type' => 'manual'])) }}" 
                   class="px-2.5 py-1 rounded-lg font-bold transition flex items-center space-x-1 {{ $currentType === 'manual' ? 'bg-emerald-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200' }}">
                    <span>📝 Manual</span>
                    @if(isset($manualCount))
                    <span class="text-[10px] px-1.5 py-0.2 rounded-full {{ $currentType === 'manual' ? 'bg-emerald-800 text-emerald-200' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300' }}">{{ $manualCount }}</span>
                    @endif
                </a>
            </div>
            @endif
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 uppercase text-[10px] font-bold text-slate-400 border-b border-slate-100">
                    <tr>
                        <th class="px-4 py-3">Invoice #</th>
                        @if(!Auth::user()->isStudent())
                        <th class="px-4 py-3">Student</th>
                        @endif
                        <th class="px-4 py-3">Title &amp; Category</th>
                        <th class="px-4 py-3">Total Due</th>
                        <th class="px-4 py-3">Paid</th>
                        <th class="px-4 py-3">Balance</th>
                        <th class="px-4 py-3">Due Date</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($invoices as $inv)
                    <tr class="hover:bg-slate-50/80 transition">
                        <td class="px-4 py-3 font-mono font-bold text-slate-900">
                            <a href="{{ route('fees.show', $inv->id) }}" class="hover:text-emerald-600 underline">
                                {{ $inv->invoice_number }}
                            </a>
                        </td>

                        @if(!Auth::user()->isStudent())
                        <td class="px-4 py-3">
                            <span class="font-bold text-slate-900 block">{{ $inv->student?->name }}</span>
                            <span class="text-[10px] text-slate-400 font-mono">{{ $inv->student?->student_id_number }}</span>
                        </td>
                        @endif

                        <td class="px-4 py-3">
                            <span class="font-semibold text-slate-900 dark:text-white block">{{ $inv->title }}</span>
                            <div class="flex items-center space-x-1.5 mt-0.5">
                                @if($inv->isMonthlyAuto())
                                <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-black uppercase tracking-wider bg-purple-100 dark:bg-purple-950 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-800">
                                    ⚡ Monthly Auto
                                </span>
                                @else
                                <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-bold uppercase tracking-wider bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400">
                                    Manual
                                </span>
                                @endif
                                <span class="text-[11px] text-slate-500">{{ $inv->category }} &bull; {{ $inv->course?->code ?? 'Academy Wide' }}</span>
                            </div>
                        </td>

                        <td class="px-4 py-3 font-bold text-slate-800">
                            {{ $currencySymbol ?? '₹' }}{{ number_format($inv->total_due, 2) }}
                        </td>

                        <td class="px-4 py-3 font-medium text-emerald-700">
                            {{ $currencySymbol ?? '₹' }}{{ number_format($inv->paid_amount, 2) }}
                        </td>

                        <td class="px-4 py-3 font-black {{ $inv->balance > 0 ? 'text-amber-600' : 'text-slate-400' }}">
                            {{ $currencySymbol ?? '₹' }}{{ number_format($inv->balance, 2) }}
                        </td>

                        <td class="px-4 py-3 text-slate-500 whitespace-nowrap">
                            {{ $inv->due_date->format('M d, Y') }}
                            @if($inv->status === 'overdue')
                                <span class="text-[10px] text-rose-600 block font-bold">Past Due</span>
                            @endif
                        </td>

                        <td class="px-4 py-3">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider
                                @if($inv->status === 'paid') bg-emerald-100 text-emerald-800
                                @elseif($inv->status === 'partial') bg-blue-100 text-blue-800
                                @elseif($inv->status === 'overdue') bg-rose-100 text-rose-800
                                @else bg-amber-100 text-amber-800 @endif">
                                {{ $inv->status }}
                            </span>
                        </td>

                        <td class="px-4 py-3 text-right space-x-1.5 whitespace-nowrap">
                            <a href="{{ route('fees.show', $inv->id) }}" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition">
                                Details
                            </a>

                            @if($inv->balance > 0)
                            <button @click.stop="activeInvoice = { id: {{ $inv->id }}, number: '{{ $inv->invoice_number }}', title: '{{ addslashes($inv->title) }}', balance: {{ $inv->balance }}, payAmount: {{ $inv->balance }} }; openPayModal = true"
                                    type="button"
                                    class="px-3 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-xs transition">
                                Pay Online
                            </button>
                            @if(Auth::user()->isAdmin())
                            <form method="POST" action="{{ route('fees.reminder', $inv->id) }}" class="inline" title="Send Due Reminder">
                                @csrf
                                <button type="submit" class="px-2 py-1 rounded-lg bg-amber-50 hover:bg-amber-100 dark:bg-amber-950/50 text-amber-800 dark:text-amber-300 font-bold text-xs border border-amber-200 dark:border-amber-800 transition">
                                    ⏰ Remind
                                </button>
                            </form>
                            @endif
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-4 py-6 text-center text-xs text-slate-400 font-medium">
                            No fee invoices found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($invoices instanceof \Illuminate\Pagination\LengthAwarePaginator)
        <div class="p-4 border-t border-slate-100">
            {{ $invoices->links() }}
        </div>
        @endif
    </div>

    <!-- Settled Payment Receipts & Ledger (Paginated) -->
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-xs overflow-hidden">
        <div class="p-3.5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <div>
                <h3 class="font-black text-slate-900 dark:text-white text-sm">
                    Settled Payment Receipts &amp; Transaction Ledger
                </h3>
                <span class="text-[11px] text-slate-400 font-medium">Reconciled via Bharat UPI &amp; Campus Bursar Desk</span>
            </div>
            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300">
                Verified Ledger
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600 dark:text-slate-400">
                <thead class="bg-slate-50 dark:bg-slate-800/80 uppercase text-[10px] font-bold text-slate-400 dark:text-slate-500 border-b border-slate-100 dark:border-slate-800">
                    <tr>
                        <th class="px-4 py-3">Receipt #</th>
                        @if(!Auth::user()->isStudent())
                        <th class="px-4 py-3">Student</th>
                        @endif
                        <th class="px-4 py-3">Invoice Ref</th>
                        <th class="px-4 py-3">Amount</th>
                        <th class="px-4 py-3">Payment Method</th>
                        <th class="px-4 py-3">Txn Reference</th>
                        <th class="px-4 py-3">Settlement Date</th>
                        <th class="px-4 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($payments as $pmt)
                    <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/60 transition">
                        <td class="px-4 py-3 font-mono font-bold text-emerald-600 dark:text-emerald-400">
                            {{ $pmt->receipt_number }}
                        </td>
                        @if(!Auth::user()->isStudent())
                        <td class="px-4 py-3">
                            <span class="font-bold text-slate-900 dark:text-white block">{{ $pmt->student?->name }}</span>
                            <span class="text-[10px] text-slate-400 font-mono">{{ $pmt->student?->student_id_number }}</span>
                        </td>
                        @endif
                        <td class="px-4 py-3 font-mono font-semibold text-slate-800 dark:text-slate-200">
                            {{ $pmt->invoice?->invoice_number ?? 'N/A' }}
                        </td>
                        <td class="px-4 py-3 font-mono font-bold text-slate-900 dark:text-white">
                            {{ $currencySymbol ?? '₹' }}{{ number_format($pmt->amount, 2) }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                {{ $pmt->payment_method }}
                            </span>
                        </td>
                        <td class="px-4 py-3 font-mono text-[11px] text-slate-500">
                            {{ $pmt->transaction_reference ?: ($pmt->transaction_id ?: 'TXN-CONFIRMED') }}
                        </td>
                        <td class="px-4 py-3 text-slate-500 whitespace-nowrap">
                            {{ $pmt->paid_at ? $pmt->paid_at->format('M d, Y h:i A') : $pmt->created_at->format('M d, Y') }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300">
                                {{ $pmt->status }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ Auth::user()->isStudent() ? '7' : '8' }}" class="px-4 py-6 text-center text-xs text-slate-400 font-medium">
                            No settled payment transactions found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(isset($payments) && $payments instanceof \Illuminate\Pagination\LengthAwarePaginator)
        <div class="p-4 border-t border-slate-100 dark:border-slate-800">
            {{ $payments->links() }}
        </div>
        @endif
    </div>

    <!-- PAYMENT MODAL: QR Display + Cashier Record Form -->
    <div x-show="openPayModal" x-cloak 
         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="openPayModal = false"></div>
        <div class="relative bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 max-w-md w-full p-5 sm:p-6 z-10">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center space-x-2.5">
                    <div class="w-8 h-8 rounded-xl bg-teal-100 dark:bg-teal-900/50 text-teal-600 dark:text-teal-400 flex items-center justify-center font-bold text-sm">
                        💰
                    </div>
                    <div>
                        <h3 class="text-sm font-black text-slate-900 dark:text-white">Fee Payment</h3>
                        <span class="text-[11px] text-slate-400 font-mono" x-text="activeInvoice ? activeInvoice.number : ''"></span>
                    </div>
                </div>
                <button @click="openPayModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-white text-lg font-bold">✕</button>
            </div>

            <template x-if="activeInvoice">
                <div class="space-y-4 mt-4">
                    <!-- Invoice Summary -->
                    <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 flex justify-between items-center text-xs">
                        <div>
                            <span class="text-slate-400 block text-[10px] uppercase font-bold tracking-wider">Invoice:</span>
                            <span class="font-bold text-slate-800 dark:text-slate-200" x-text="activeInvoice.title"></span>
                        </div>
                        <div class="text-right">
                            <span class="text-slate-400 block text-[10px] uppercase font-bold tracking-wider">Outstanding:</span>
                            <span class="text-base font-black font-mono text-emerald-600 dark:text-emerald-400" x-text="'{{ $currencySymbol ?? '₹' }}' + Number(activeInvoice.balance).toFixed(2)"></span>
                        </div>
                    </div>

                    @php $qrImageUrl = \App\Models\AcademySetting::get('payment_qr_image') ? asset('storage/' . \App\Models\AcademySetting::get('payment_qr_image')) : null; @endphp
                    @if($qrImageUrl)
                    <div class="bg-slate-50 dark:bg-slate-800/40 rounded-2xl p-4 border border-slate-200 dark:border-slate-700 text-center">
                        <img src="{{ $qrImageUrl }}" alt="School Payment QR" class="w-40 h-40 mx-auto rounded-lg object-contain">
                        <p class="text-xs font-bold text-slate-700 dark:text-slate-300 mt-2">Scan to Pay</p>
                        <p class="text-[10px] text-slate-400">GPay &bull; PhonePe &bull; Paytm &bull; BHIM</p>
                        @php $upiVpa = \App\Models\AcademySetting::get('upi_vpa'); @endphp
                        @if($upiVpa)
                        <p class="text-[10px] font-mono text-slate-500 mt-1">UPI: <strong>{{ $upiVpa }}</strong></p>
                        @endif
                    </div>
                    @else
                    <div class="p-3 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 text-xs text-amber-700 dark:text-amber-300">
                        ℹ️ School QR code not configured. Please visit the school cashier for payment.
                    </div>
                    @endif

                    @if(auth()->user()?->isAdmin() || auth()->user()?->isCashier())
                    <!-- Cashier/Admin payment recording form -->
                    <form :action="'/fees/' + activeInvoice.id + '/pay'" method="POST" class="space-y-3 pt-2 border-t border-slate-100 dark:border-slate-800">
                        @csrf
                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Record Payment Received</p>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Amount *</label>
                                <input type="number" step="0.01" min="0.01" :max="activeInvoice.balance" x-model="activeInvoice.payAmount" name="amount" required class="w-full px-2 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-mono font-bold text-slate-900 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Date *</label>
                                <input type="date" name="payment_date" :value="new Date().toISOString().slice(0,10)" :max="new Date().toISOString().slice(0,10)" required class="w-full px-2 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Mode *</label>
                                <select name="payment_mode" required class="w-full px-2 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                                    <option value="cash">Cash</option>
                                    <option value="upi" selected>UPI</option>
                                    <option value="qr">QR Scan</option>
                                    <option value="bank">Bank</option>
                                    <option value="cheque">Cheque</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">UTR / Ref No.</label>
                                <input type="text" name="reference_no" maxlength="100" placeholder="UTR / TXN ID" class="w-full px-2 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-mono text-slate-900 dark:text-white">
                            </div>
                        </div>
                        <input type="text" name="notes" maxlength="500" placeholder="Notes (optional)" class="w-full px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                        <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs shadow">
                            ✅ Confirm Payment Received &rarr;
                        </button>
                    </form>
                    @else
                    <p class="text-xs text-slate-500 dark:text-slate-400 text-center pt-1">After payment, bring your UTR to the school cashier for a receipt.</p>
                    @endif

                    <div class="text-center">
                        <button type="button" @click="openPayModal = false" class="text-xs text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 font-semibold">Close</button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- CREATE INVOICE MODAL (ADMIN ONLY) -->
    @if(Auth::user()->isAdmin())
    <div x-show="openInvoiceModal" x-cloak 
         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="openInvoiceModal = false"></div>
        <div class="relative bg-white rounded-xl shadow-2xl border border-slate-200 max-w-lg w-full p-4 sm:p-5 z-10">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="text-sm font-bold text-slate-900">Issue Academic Fee Invoice</h3>
                <button type="button" @click="openInvoiceModal = false" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <form method="POST" action="{{ route('fees.store') }}" class="space-y-4 mt-4">
                @csrf

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Target Student *</label>
                    <select name="student_id" required class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm bg-white">
                        <option value="">Select Student...</option>
                        @foreach($students as $st)
                            <option value="{{ $st->id }}">{{ $st->name }} ({{ $st->student_id_number }}) - {{ $st->email }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Fee Category *</label>
                        <select name="category" required class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm bg-white">
                            <option value="Tuition">Tuition</option>
                            <option value="Lab & Tech Fee">Lab & Tech Fee</option>
                            <option value="Exam Fee">Exam Fee</option>
                            <option value="Registration">Registration</option>
                            <option value="Library">Library</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Linked Course (Optional)</label>
                        <select name="course_id" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm bg-white">
                            <option value="">None / General</option>
                            @foreach($courses as $crs)
                                <option value="{{ $crs->id }}">{{ $crs->code }} - {{ $crs->title }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Invoice Title / Description *</label>
                    <input type="text" name="title" required placeholder="e.g. Term 2 Computer Lab Facility Fee" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm">
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Amount ({{ $currencySymbol ?? '₹' }}) *</label>
                        <input type="number" step="0.01" name="amount" required placeholder="500.00" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Discount ({{ $currencySymbol ?? '₹' }})</label>
                        <input type="number" step="0.01" name="discount" value="0.00" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Due Date *</label>
                        <input type="date" name="due_date" required value="{{ now()->addDays(14)->format('Y-m-d') }}" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm">
                    </div>
                </div>

                <div class="flex justify-end space-x-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="openInvoiceModal = false" class="px-4 py-2 text-slate-600 text-xs font-semibold">Cancel</button>
                    <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold shadow-sm">Issue Invoice</button>
                </div>
            </form>
        </div>
    </div>

    <!-- AUTO MONTHLY INVOICE GENERATOR MODAL (ADMIN ONLY) -->
    <div x-show="openMonthlyModal" x-cloak 
         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="openMonthlyModal = false"></div>
        <div class="relative bg-white dark:bg-slate-900 rounded-xl shadow-2xl border border-slate-200 dark:border-slate-800 max-w-lg w-full p-4 sm:p-5 z-10 text-slate-900 dark:text-white">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center space-x-2">
                    <span class="w-7 h-7 rounded-lg bg-purple-100 dark:bg-purple-950 text-purple-700 dark:text-purple-300 flex items-center justify-center text-xs font-bold">⚡</span>
                    <div>
                        <h3 class="text-sm font-bold">Auto Monthly Fee Invoicing</h3>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">Generate recurring monthly invoices in bulk across scholars</p>
                    </div>
                </div>
                <button type="button" @click="openMonthlyModal = false" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <form method="POST" action="{{ route('fees.generate-monthly') }}" class="space-y-4 mt-4 text-xs">
                @csrf

                <!-- Month & Year -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold mb-1">Billing Month *</label>
                        <select name="month" required class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs">
                            @for($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" {{ $m == date('n') ? 'selected' : '' }}>
                                    {{ date('F', mktime(0, 0, 0, $m, 10)) }} ({{ sprintf('%02d', $m) }})
                                </option>
                            @endfor
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold mb-1">Billing Year *</label>
                        <input type="number" name="year" required value="{{ date('Y') }}" min="2020" max="2099" class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-mono">
                    </div>
                </div>

                <!-- Scope Selection -->
                <div>
                    <label class="block font-semibold mb-1">Target Scholars *</label>
                    <div class="grid grid-cols-3 gap-2 mb-2">
                        <button type="button" @click="targetScope = 'all'" :class="targetScope === 'all' ? 'bg-purple-600 text-white border-purple-600' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700'" class="py-1.5 px-2 rounded-lg border text-center font-bold text-[11px] transition">
                            All Scholars
                        </button>
                        <button type="button" @click="targetScope = 'class'" :class="targetScope === 'class' ? 'bg-purple-600 text-white border-purple-600' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700'" class="py-1.5 px-2 rounded-lg border text-center font-bold text-[11px] transition">
                            By Class
                        </button>
                        <button type="button" @click="targetScope = 'batch'" :class="targetScope === 'batch' ? 'bg-purple-600 text-white border-purple-600' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700'" class="py-1.5 px-2 rounded-lg border text-center font-bold text-[11px] transition">
                            By Batch
                        </button>
                    </div>

                    <div x-show="targetScope === 'class'" x-cloak class="mt-2">
                        <select name="class_id" class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs">
                            <option value="">Select Academic Class...</option>
                            @foreach($classes ?? [] as $cls)
                                <option value="{{ $cls->id }}">{{ $cls->name }} (Grade {{ $cls->grade_level }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div x-show="targetScope === 'batch'" x-cloak class="mt-2">
                        <select name="batch_id" class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs">
                            <option value="">Select Class Batch...</option>
                            @foreach($batches ?? [] as $bt)
                                <option value="{{ $bt->id }}">{{ $bt->name }} ({{ $bt->code }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Amount & Due Date -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold mb-1">Monthly Tuition ({{ $currencySymbol ?? '₹' }}) *</label>
                        <input type="number" step="0.01" name="amount" required value="{{ $monthlyConfig['default_amount'] ?? '1500.00' }}" class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-mono font-bold">
                    </div>

                    <div>
                        <label class="block font-semibold mb-1">Payment Due Date *</label>
                        <input type="date" name="due_date" required value="{{ date('Y-m-') . sprintf('%02d', $monthlyConfig['due_day'] ?? 10) }}" class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-mono">
                    </div>
                </div>

                <!-- Custom Title -->
                <div>
                    <label class="block font-semibold mb-1">Invoice Title (Optional Override)</label>
                    <input type="text" name="title" placeholder="Leave empty for: Monthly Tuition Fee - [Month] [Year]" class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs">
                </div>

                <!-- Idempotency Notice -->
                <div class="p-3 rounded-xl bg-purple-50 dark:bg-purple-950/40 border border-purple-200 dark:border-purple-800 text-[11px] text-purple-900 dark:text-purple-300 flex items-start space-x-2">
                    <span class="text-sm">🛡️</span>
                    <span><strong>Double-Billing Protection:</strong> Scholars who already have an invoice for this target month will be safely skipped automatically.</span>
                </div>

                <div class="flex justify-end space-x-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="openMonthlyModal = false" class="px-4 py-2 text-slate-600 dark:text-slate-400 font-semibold">Cancel</button>
                    <button type="submit" class="px-5 py-2.5 bg-purple-600 hover:bg-purple-500 text-white rounded-xl font-bold shadow-md shadow-purple-600/20 transition flex items-center space-x-1.5">
                        <span>⚡ Run Monthly Invoicing Batch</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MONTHLY FEE AUTOMATION SETTINGS MODAL (ADMIN ONLY) -->
    <div x-show="openConfigModal" x-cloak 
         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="openConfigModal = false"></div>
        <div class="relative bg-white dark:bg-slate-900 rounded-xl shadow-2xl border border-slate-200 dark:border-slate-800 max-w-lg w-full p-4 sm:p-5 z-10 text-slate-900 dark:text-white">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center space-x-2">
                    <span class="w-7 h-7 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 flex items-center justify-center text-xs font-bold">⚙️</span>
                    <div>
                        <h3 class="text-sm font-bold">Monthly Fee Automation Settings</h3>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">Configure scheduled monthly recurring fee cycles</p>
                    </div>
                </div>
                <button type="button" @click="openConfigModal = false" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <form method="POST" action="{{ route('fees.monthly-config') }}" class="space-y-4 mt-4 text-xs">
                @csrf

                <!-- Scheduled Automation Toggle -->
                <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 flex items-center justify-between">
                    <div>
                        <strong class="text-xs block text-slate-900 dark:text-white">Monthly Cron Scheduler (1st of Each Month)</strong>
                        <span class="text-[11px] text-slate-500 dark:text-slate-400">Automatically creates invoices at 00:05 AM every month</span>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="auto_enabled" value="1" {{ ($monthlyConfig['auto_enabled'] ?? true) ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-hidden rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
                    </label>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold mb-1">Default Monthly Fee ({{ $currencySymbol ?? '₹' }}) *</label>
                        <input type="number" step="0.01" name="default_amount" required value="{{ $monthlyConfig['default_amount'] ?? '1500.00' }}" class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-mono font-bold">
                    </div>

                    <div>
                        <label class="block font-semibold mb-1">Due Day of Month (1-28) *</label>
                        <input type="number" name="due_day" required min="1" max="28" value="{{ $monthlyConfig['due_day'] ?? 10 }}" class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-mono">
                    </div>
                </div>

                <div>
                    <label class="block font-semibold mb-1">Fee Category *</label>
                    <input type="text" name="category" required value="{{ $monthlyConfig['category'] ?? 'Tuition' }}" class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs">
                </div>

                <div>
                    <label class="block font-semibold mb-1">Invoice Title Template *</label>
                    <input type="text" name="title_template" required value="{{ $monthlyConfig['title_template'] ?? 'Monthly Tuition Fee - {month} {year}' }}" class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-mono">
                    <span class="text-[10px] text-slate-400 mt-0.5 block">Use <code>{month}</code> and <code>{year}</code> as dynamic placeholders.</span>
                </div>

                <div class="flex justify-end space-x-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="openConfigModal = false" class="px-4 py-2 text-slate-600 dark:text-slate-400 font-semibold">Cancel</button>
                    <button type="submit" class="px-5 py-2.5 bg-slate-900 hover:bg-slate-800 dark:bg-emerald-600 dark:hover:bg-emerald-500 text-white rounded-xl font-bold shadow-md transition">
                        💾 Save Automation Settings
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

</div>
@endsection
