@extends('layouts.app')

@section('title', 'Invoice #' . $invoice->invoice_number . ' — ' . ($school['name'] ?? config('app.name', 'School ERP')))

@section('content')
<div class="max-w-5xl mx-auto space-y-4">

    <!-- Top Breadcrumb & Actions Bar (Hidden when printing) -->
    <div class="flex items-center justify-between pb-2 border-b border-slate-200 dark:border-slate-800 print:hidden text-xs">
        <a href="{{ route('fees.index') }}" class="font-bold text-slate-500 hover:text-emerald-600 dark:text-slate-400 dark:hover:text-emerald-400 flex items-center space-x-1.5 transition">
            <span>&larr;</span>
            <span>Back to Invoices &amp; Fee Ledger</span>
        </a>

        <div class="flex items-center space-x-2">
            <button type="button" onclick="window.print()" class="px-3.5 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold border border-slate-200 dark:border-slate-700 transition flex items-center space-x-1.5 shadow-2xs">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                <span>Print Invoice</span>
            </button>
            @if(Auth::user()->isAdmin() && $invoice->balance > 0)
            <form method="POST" action="{{ route('fees.reminder', $invoice->id) }}" class="inline">
                @csrf
                <button type="submit" class="px-3.5 py-1.5 rounded-xl bg-amber-50 hover:bg-amber-100 dark:bg-amber-950/60 dark:hover:bg-amber-900/60 text-amber-800 dark:text-amber-300 font-bold border border-amber-200 dark:border-amber-800 transition flex items-center space-x-1.5 shadow-2xs" title="Send Fee Reminder to Student and Guardian">
                    <span>⏰ Send Reminder</span>
                </button>
            </form>
            @endif
            @if(Auth::user()->isAdmin())
            <form method="POST" action="{{ route('fees.destroy', $invoice->id) }}" class="inline" onsubmit="return confirm('CAUTION: Are you sure you want to permanently delete this invoice and all recorded payments?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-3.5 py-1.5 rounded-xl bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/60 dark:hover:bg-rose-900/60 text-rose-700 dark:text-rose-300 font-bold border border-rose-200 dark:border-rose-800 transition flex items-center space-x-1.5 shadow-2xs" title="Delete Invoice">
                    <span>🗑️ Delete</span>
                </button>
            </form>
            @endif
        </div>
    </div>

    <!-- Official Institutional Invoice Paper -->
    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-xl overflow-hidden print:shadow-none print:border-none">
        
        <!-- Premium Header Bar -->
        <div class="bg-gradient-to-r from-slate-950 via-slate-900 to-indigo-950 p-5 sm:p-6 text-white border-b border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center space-x-3.5">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-emerald-500 to-teal-400 flex items-center justify-center text-slate-950 font-black text-xl shadow-lg shadow-emerald-500/20 shrink-0">
                    PA
                </div>
                <div>
                    <span class="text-[10px] font-black uppercase tracking-widest text-emerald-400 block">Verified Fee Demand Note</span>
                    <h1 class="text-lg sm:text-xl font-black text-white tracking-tight">{{ $instituteName ?? ($school['name'] ?? config('app.name', 'School ERP')) }}</h1>
                    <span class="text-xs text-slate-400 font-mono">Invoice Reference: <strong class="text-slate-200">{{ $invoice->invoice_number }}</strong></span>
                </div>
            </div>

            <div class="flex items-center space-x-2.5 self-start sm:self-auto">
                @if($invoice->isMonthlyAuto())
                <span class="px-2.5 py-1 rounded-full text-xs font-black uppercase tracking-wider bg-purple-500/20 text-purple-300 border border-purple-500/40">
                    ⚡ Monthly Recurring
                </span>
                @endif
                <span class="px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider
                    @if($invoice->status === 'paid') bg-emerald-500/20 text-emerald-300 border border-emerald-500/40
                    @elseif($invoice->status === 'partial') bg-blue-500/20 text-blue-300 border border-blue-500/40
                    @elseif($invoice->status === 'overdue') bg-rose-500/20 text-rose-300 border border-rose-500/40
                    @else bg-amber-500/20 text-amber-300 border border-amber-500/40 @endif">
                    {{ ucfirst($invoice->status) }}
                </span>
            </div>
        </div>

        <!-- Symmetrical 3-Column Meta Details Grid -->
        <div class="p-5 sm:p-6 grid grid-cols-1 md:grid-cols-3 gap-4 border-b border-slate-100 dark:border-slate-800">
            <!-- Col 1: Student Information -->
            <div class="p-4 rounded-2xl bg-slate-50/80 dark:bg-slate-800/40 border border-slate-200/70 dark:border-slate-800 space-y-1 text-xs">
                <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-500 block mb-1">Student Information</span>
                <strong class="text-sm font-extrabold text-slate-900 dark:text-white block">{{ $invoice->student->name }}</strong>
                <div class="flex items-center space-x-1 font-mono text-emerald-600 dark:text-emerald-400 font-bold">
                    <span>ID:</span>
                    <span>{{ $invoice->student->student_id_number ?? 'PA-STU-2026' }}</span>
                </div>
                <div class="text-slate-600 dark:text-slate-400">Class: <strong class="text-slate-800 dark:text-slate-200">{{ $invoice->student->academicClass->name ?? 'Enrolled Student' }}</strong></div>
                <div class="text-slate-600 dark:text-slate-400">Roll No: <span class="font-mono">{{ $invoice->student->roll_number ?? 'N/A' }}</span></div>
                <div class="text-slate-400 dark:text-slate-500 text-[11px] truncate pt-0.5">{{ $invoice->student->email }}</div>
            </div>

            <!-- Col 2: Invoice Schedule -->
            <div class="p-4 rounded-2xl bg-slate-50/80 dark:bg-slate-800/40 border border-slate-200/70 dark:border-slate-800 space-y-1 text-xs">
                <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-500 block mb-1">Billing Schedule</span>
                <div class="text-slate-600 dark:text-slate-400">Fee Head: <strong class="text-slate-800 dark:text-slate-200">{{ $invoice->category }}</strong></div>
                <div class="text-slate-600 dark:text-slate-400">Batch / Program: <strong class="text-slate-800 dark:text-slate-200">{{ $invoice->course?->title ?? 'Institutional General' }}</strong></div>
                <div class="text-slate-600 dark:text-slate-400">Issue Date: <span class="font-mono">{{ $invoice->created_at->format('M d, Y') }}</span></div>
                <div class="text-slate-600 dark:text-slate-400">Statutory Due Date: <strong class="font-mono text-rose-600 dark:text-rose-400">{{ $invoice->due_date->format('M d, Y') }}</strong></div>
                <div class="text-slate-400 dark:text-slate-500 text-[11px] pt-0.5">Mode: Bharat UPI / Online Settle</div>
            </div>

            <!-- Col 3: Balance Summary Highlight Card -->
            <div class="p-4 rounded-2xl bg-gradient-to-br from-slate-50 via-emerald-50/30 to-slate-100 dark:from-slate-800/60 dark:via-emerald-950/20 dark:to-slate-800/60 border border-emerald-200 dark:border-emerald-800/60 flex flex-col justify-between text-xs">
                <div>
                    <span class="text-[10px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400 block mb-0.5">Outstanding Balance Due</span>
                    <div class="text-2xl sm:text-3xl font-black font-mono tracking-tight {{ $invoice->balance > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                        {{ $currencySymbol ?? '₹' }}{{ number_format($invoice->balance, 2) }}
                    </div>
                </div>

                <div class="pt-2 mt-2 border-t border-slate-200/80 dark:border-slate-700/80 space-y-0.5 text-[11px] text-slate-500 dark:text-slate-400">
                    <div class="flex justify-between">
                        <span>Total Invoiced:</span>
                        <strong class="font-mono text-slate-800 dark:text-slate-200">{{ $currencySymbol ?? '₹' }}{{ number_format($invoice->total_due, 2) }}</strong>
                    </div>
                    <div class="flex justify-between">
                        <span>Settled to Date:</span>
                        <strong class="font-mono text-emerald-600 dark:text-emerald-400">{{ $currencySymbol ?? '₹' }}{{ number_format($invoice->paid_amount, 2) }}</strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Itemized Institutional Fee Ledger Table -->
        <div class="p-5 sm:p-6 space-y-3">
            <h3 class="text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-wider">Itemized Institutional Fee Heads</h3>
            <div class="border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-2xs">
                <table class="w-full text-xs text-left">
                    <thead class="bg-slate-50 dark:bg-slate-800/80 uppercase text-[10px] font-black text-slate-400 dark:text-slate-500 border-b border-slate-200 dark:border-slate-800">
                        <tr>
                            <th class="py-3 px-4">Institutional Head</th>
                            <th class="py-3 px-4">Particulars</th>
                            <th class="py-3 px-4 text-right">Standard Fee</th>
                            <th class="py-3 px-4 text-right">Concession</th>
                            <th class="py-3 px-4 text-right">Net Demand</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium text-slate-700 dark:text-slate-300">
                        <tr>
                            <td class="py-3 px-4 font-bold text-slate-900 dark:text-white">1. {{ $invoice->category }}</td>
                            <td class="py-3 px-4 text-slate-500 dark:text-slate-400">{{ $invoice->title }} &bull; {{ $invoice->notes ?? 'Standard curriculum fee schedule' }}</td>
                            <td class="py-3 px-4 text-right font-mono">{{ $currencySymbol ?? '₹' }}{{ number_format($invoice->amount, 2) }}</td>
                            <td class="py-3 px-4 text-right font-mono text-emerald-600 dark:text-emerald-400">-{{ $currencySymbol ?? '₹' }}{{ number_format($invoice->discount, 2) }}</td>
                            <td class="py-3 px-4 text-right font-mono font-bold text-slate-900 dark:text-white">{{ $currencySymbol ?? '₹' }}{{ number_format($invoice->total_due, 2) }}</td>
                        </tr>

                    </tbody>
                    <tfoot class="bg-slate-50 dark:bg-slate-800/80 font-bold border-t border-slate-200 dark:border-slate-800">
                        <tr>
                            <td colspan="4" class="py-3 px-4 text-right text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px]">Total Net Demand:</td>
                            <td class="py-3 px-4 text-right font-mono text-slate-900 dark:text-white font-black text-sm">{{ $currencySymbol ?? '₹' }}{{ number_format($invoice->total_due, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        @if($invoice->balance > 0)
        <div class="mx-5 sm:mx-6 mb-6 rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 overflow-hidden print:hidden">

            {{-- QR Code display for all users --}}
            @if($qrImageUrl ?? false)
            <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center gap-5">
                <div class="shrink-0 p-3 bg-slate-50 dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 text-center">
                    <img src="{{ $qrImageUrl }}" alt="School Payment QR Code" class="w-36 h-36 mx-auto rounded-lg object-contain">
                    <span class="text-[9px] font-bold text-slate-500 block mt-1.5">Scan to Pay (GPay / PhonePe / Paytm / BHIM)</span>
                </div>
                <div class="space-y-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-teal-100 text-teal-800 dark:bg-teal-950 dark:text-teal-300">
                        💰 Outstanding: {{ $currencySymbol ?? '₹' }}{{ number_format($invoice->balance, 2) }}
                    </span>
                    <h3 class="text-sm font-black text-slate-900 dark:text-white">Pay via School QR Code</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 max-w-sm leading-relaxed">
                        Scan the QR code with any UPI app. After payment, note your UTR / transaction reference and visit the school cashier to get your receipt.
                    </p>
                    @if($upiVpa ?? false)
                    <p class="text-xs font-mono text-slate-700 dark:text-slate-300">UPI ID: <strong>{{ $upiVpa }}</strong></p>
                    @endif
                    @if(($bankName ?? '') || ($bankAccount ?? ''))
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Bank: <strong>{{ $bankName }}</strong> &bull; A/C: <strong class="font-mono">{{ $bankAccount }}</strong>
                        @if($bankIfsc ?? false) &bull; IFSC: <strong class="font-mono">{{ $bankIfsc }}</strong>@endif
                    </p>
                    @endif
                </div>
            </div>
            @else
            <div class="p-4 bg-amber-50 dark:bg-amber-950/40 border-b border-amber-200 dark:border-amber-800 text-xs text-amber-800 dark:text-amber-300 font-semibold">
                ℹ️ Payment QR code not configured yet. Please visit the school cashier for payment.
            </div>
            @endif

            {{-- Cashier / Admin: Mark Payment Received --}}
            @if(auth()->user()?->isAdmin() || auth()->user()?->isCashier())
            <div x-data="{ open: false }" class="p-5">
                <button @click="open = !open" class="flex items-center space-x-2 text-sm font-black text-emerald-700 dark:text-emerald-400 hover:underline">
                    <span x-text="open ? '▲ Hide Payment Form' : '▼ Record Payment Received'"></span>
                </button>
                <div x-show="open" x-transition class="mt-4">
                    <form action="{{ route('fees.pay', $invoice->id) }}" method="POST" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        @csrf
                        <div>
                            <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Amount Received ({{ $currencySymbol ?? '₹' }}) *</label>
                            <input type="number" step="0.01" min="0.01" max="{{ number_format($invoice->balance, 2, '.', '') }}" name="amount" value="{{ number_format($invoice->balance, 2, '.', '') }}" required class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm font-mono font-bold text-slate-900 dark:text-white focus:ring-1 focus:ring-emerald-500">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Payment Date *</label>
                            <input type="date" name="payment_date" value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" required class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm text-slate-900 dark:text-white focus:ring-1 focus:ring-emerald-500">
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
                            <input type="text" name="reference_no" maxlength="100" placeholder="UTR / Cheque No. / TXN ID" class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm font-mono text-slate-900 dark:text-white focus:ring-1 focus:ring-emerald-500">
                        </div>
                        <div class="sm:col-span-2 lg:col-span-2">
                            <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Notes</label>
                            <input type="text" name="notes" maxlength="500" placeholder="Optional remarks" class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm text-slate-900 dark:text-white focus:ring-1 focus:ring-emerald-500">
                        </div>
                        <div class="sm:col-span-2 lg:col-span-3">
                            <button type="submit" class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-black text-sm shadow transition">
                                ✅ Confirm Payment Received &rarr;
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            @else
            <div class="px-5 py-3 text-xs text-slate-500 dark:text-slate-400 border-t border-slate-100 dark:border-slate-800">
                After scanning and paying, bring your UTR / transaction reference to the school cashier for receipt generation.
            </div>
            @endif
        </div>
        @endif

        <!-- Payment History / Official Receipts Section -->
        @if($invoice->payments->count() > 0)
        <div class="px-5 sm:px-6 pb-6 pt-3 border-t border-slate-100 dark:border-slate-800 space-y-3">
            <h3 class="text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-wider">Settled Electronic Payment Receipts</h3>
            <div class="divide-y divide-slate-100 dark:divide-slate-800 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-2xs">
                @foreach($invoice->payments as $pmt)
                <div class="p-4 bg-slate-50/50 dark:bg-slate-800/40 hover:bg-slate-50 dark:hover:bg-slate-800/60 transition flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                    <div>
                        <div class="flex items-center space-x-2">
                            <span class="font-bold text-slate-900 dark:text-white font-mono text-sm">{{ $pmt->receipt_number }}</span>
                            <span class="px-2 py-0.2 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">✓ Settled</span>
                        </div>
                        <div class="text-slate-500 dark:text-slate-400 mt-0.5">
                            Txn Ref: <span class="font-mono text-slate-700 dark:text-slate-300">{{ $pmt->transaction_reference }}</span> &bull; Mode: {{ $pmt->payment_method }}
                        </div>
                        <span class="text-[10px] text-slate-400 block mt-0.5">{{ $pmt->paid_at->format('M d, Y &bull; h:i A') }}</span>
                    </div>

                    <div class="flex items-center space-x-3 self-end sm:self-auto">
                        <span class="text-sm sm:text-base font-black font-mono text-emerald-600 dark:text-emerald-400">{{ $currencySymbol ?? '₹' }}{{ number_format($pmt->amount, 2) }}</span>
                        <a href="{{ route('fees.receipt', $pmt->id) }}" class="px-3.5 py-1.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 font-bold rounded-xl text-xs shadow-2xs transition">
                            View Receipt &rarr;
                        </a>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Institutional Footer / Motto -->
        <div class="p-4 sm:p-5 bg-slate-50 dark:bg-slate-800/60 border-t border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500 dark:text-slate-400">
            <div class="flex items-center space-x-2">
                <span>🏛️ Affiliation: <strong class="text-slate-700 dark:text-slate-300">{{ \App\Models\AcademySetting::get('affiliation_number', 'CBSE/AFF/2026/089') }}</strong></span>
                <span>&bull;</span>
                <span>Session: <strong class="text-slate-700 dark:text-slate-300">{{ \App\Models\AcademySetting::get('academic_session', '2026–2027') }}</strong></span>
            </div>
            <div class="text-emerald-700 dark:text-emerald-400 font-semibold italic">
                "Aim, Ascend, Achieve" &bull; Verified Institutional Document
            </div>
        </div>

    </div>

</div>
@endsection
