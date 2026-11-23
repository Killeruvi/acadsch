@extends('layouts.app')

@section('title', 'Receipt #' . $payment->receipt_number . ' — ' . ($school['name'] ?? 'PrimeOS Academy'))

@section('content')
<div class="max-w-3xl mx-auto space-y-4">

    <!-- Screen Navigation / Actions (Hidden during print) -->
    <div class="print:hidden flex items-center justify-between pb-2 border-b border-slate-200 dark:border-slate-800 text-xs">
        <a href="{{ route('fees.show', $payment->invoice_id) }}" class="font-bold text-slate-500 hover:text-emerald-600 dark:text-slate-400 dark:hover:text-emerald-400 flex items-center space-x-1.5 transition">
            <span>&larr;</span>
            <span>Back to Invoice</span>
        </a>

        <div class="flex items-center space-x-2">
            <button onclick="window.print()" class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold shadow-xs transition flex items-center space-x-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                <span>Print Official Receipt</span>
            </button>
        </div>
    </div>

    <!-- Official Printable Receipt Paper -->
    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-xl p-6 sm:p-10 relative overflow-hidden print:m-0 print:max-w-none print:shadow-none print:border-none">

        <!-- Top Emerald Accent Strip -->
        <div class="absolute top-0 left-0 right-0 h-2 bg-gradient-to-r from-emerald-500 via-teal-400 to-cyan-500"></div>

        <!-- Watermark Stamp -->
        <div class="absolute right-6 top-24 transform rotate-[-20deg] border-4 border-emerald-500/25 dark:border-emerald-500/20 text-emerald-600/30 dark:text-emerald-400/20 font-black text-xl sm:text-2xl uppercase tracking-widest px-4 py-1.5 rounded-xl pointer-events-none select-none">
            OFFICIALLY PAID
        </div>

        <!-- Receipt Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-5 border-b border-slate-200 dark:border-slate-800 gap-4">
            <div class="flex items-center space-x-3.5">
                <div class="w-12 h-12 rounded-2xl bg-slate-950 dark:bg-slate-800 text-white flex items-center justify-center font-black text-xl shadow-md shrink-0">
                    PA
                </div>
                <div>
                    <h2 class="text-lg sm:text-xl font-black text-slate-900 dark:text-white tracking-tight">{{ strtoupper($instituteName ?? 'PRIMEOS ACADEMY') }}</h2>
                    <span class="text-xs text-slate-500 dark:text-slate-400 block">Office of Financial Affairs &bull; Electronic Receipt</span>
                    <span class="text-[10px] text-slate-400 dark:text-slate-500 block">{{ $institutionAddress ?? 'Plot 42, Knowledge Corridor, Institutional Area, New Delhi 110092' }}</span>
                </div>
            </div>

            <div class="sm:text-right">
                <span class="text-[10px] uppercase font-bold tracking-widest text-slate-400 dark:text-slate-500 block">RECEIPT NUMBER</span>
                <span class="font-mono text-base font-black text-slate-900 dark:text-white">{{ $payment->receipt_number }}</span>
                <span class="text-xs text-slate-500 dark:text-slate-400 block mt-0.5">{{ $payment->paid_at->format('M d, Y &bull; h:i A') }}</span>
            </div>
        </div>

        <!-- Student & Transaction Symmetrical Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 py-5 text-xs border-b border-slate-100 dark:border-slate-800">
            <div class="p-4 rounded-2xl bg-slate-50/80 dark:bg-slate-800/40 border border-slate-200/70 dark:border-slate-800 space-y-1">
                <span class="text-slate-400 dark:text-slate-500 uppercase font-black text-[10px] tracking-wider block mb-1">Scholar Information</span>
                <strong class="text-sm font-extrabold text-slate-900 dark:text-white block">{{ $payment->student->name }}</strong>
                <div class="font-mono text-emerald-600 dark:text-emerald-400 font-bold">ID: {{ $payment->student->student_id_number }}</div>
                <div class="text-slate-500 dark:text-slate-400">{{ $payment->student->email }}</div>
            </div>

            <div class="p-4 rounded-2xl bg-slate-50/80 dark:bg-slate-800/40 border border-slate-200/70 dark:border-slate-800 space-y-1">
                <span class="text-slate-400 dark:text-slate-500 uppercase font-black text-[10px] tracking-wider block mb-1">Transaction Ledger</span>
                <div class="text-slate-600 dark:text-slate-400">Txn Ref: <strong class="font-mono text-slate-900 dark:text-white">{{ $payment->transaction_reference }}</strong></div>
                <div class="text-slate-600 dark:text-slate-400">Payment Channel: <strong class="text-slate-800 dark:text-slate-200">{{ $payment->payment_method }}</strong></div>
                <div class="text-slate-600 dark:text-slate-400">Status: <span class="font-bold uppercase text-emerald-600 dark:text-emerald-400 font-mono">✓ {{ $payment->status }}</span></div>
            </div>
        </div>

        <!-- Itemized Table -->
        <div class="py-5">
            <table class="w-full text-xs text-left">
                <thead class="bg-slate-50 dark:bg-slate-800/80 uppercase text-[10px] font-black text-slate-400 dark:text-slate-500 border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="py-2.5 px-3">Description</th>
                        <th class="py-2.5 px-3">Category</th>
                        <th class="py-2.5 px-3 text-right">Amount Settled</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                    <tr>
                        <td class="py-3 px-3">
                            <span class="font-bold text-slate-900 dark:text-white block">{{ $payment->invoice->title }}</span>
                            <span class="text-slate-500 dark:text-slate-400 text-[11px] font-mono">Invoice Reference: {{ $payment->invoice->invoice_number }}</span>
                        </td>
                        <td class="py-3 px-3 text-slate-600 dark:text-slate-400">{{ $payment->invoice->category }}</td>
                        <td class="py-3 px-3 text-right font-mono font-bold text-slate-900 dark:text-white">{{ $currencySymbol ?? '₹' }}{{ number_format($payment->amount, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Receipt Total Card -->
        <div class="p-4 rounded-2xl bg-gradient-to-br from-slate-50 to-emerald-50/30 dark:from-slate-800/60 dark:to-emerald-950/20 border border-emerald-200 dark:border-emerald-800/60 flex justify-between items-center text-xs">
            <div>
                <span class="text-slate-500 dark:text-slate-400 block text-[11px]">Total Net Amount Settled (INR):</span>
                <span class="text-[11px] text-emerald-700 dark:text-emerald-300 font-semibold">{{ $payment->notes ?? 'Online verified digital payment.' }}</span>
            </div>
            <div class="text-right">
                <span class="text-2xl font-black font-mono text-emerald-600 dark:text-emerald-400">{{ $currencySymbol ?? '₹' }}{{ number_format($payment->amount, 2) }}</span>
            </div>
        </div>

        <!-- Footer / Signature -->
        <div class="mt-6 pt-5 border-t border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500 dark:text-slate-400 gap-3">
            <div>
                <p>Digital Cryptographic Sign: <span class="font-mono text-slate-700 dark:text-slate-300 font-bold ml-1">PA-DIGITAL-SIGN-OK</span></p>
                <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5">Automated electronic fee receipt issued by {{ $school["name"] ?? "PrimeOS Academy" }} Financial Services.</p>
            </div>
            <div class="text-right">
                <div class="w-28 border-b border-slate-300 dark:border-slate-700 pb-0.5 mb-0.5 font-mono text-[10px] text-center text-slate-600 dark:text-slate-400">Financial Seal</div>
                <span class="text-[10px] text-slate-400 dark:text-slate-500 block text-center">{{ $school["name"] ?? "PrimeOS Academy" }}</span>
            </div>
        </div>

    </div>

</div>

<style>
@media print {
    body * {
        visibility: hidden;
    }
    .print\:hidden {
        display: none !important;
    }
    .max-w-3xl, .max-w-3xl * {
        visibility: visible;
    }
    .max-w-3xl {
        position: absolute;
        left: 0;
        top: 0;
        width: 100% !important;
        margin: 0 !important;
        padding: 20px !important;
        box-shadow: none !important;
        border: none !important;
    }
}
</style>
@endsection
