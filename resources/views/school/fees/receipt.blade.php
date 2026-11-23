@extends('layouts.app')

@section('title', 'Fee Receipt - ' . $payment->receipt_number)

@section('content')
<div class="max-w-2xl mx-auto px-4 py-8 space-y-6">

    <!-- Top Action Row -->
    <div class="flex items-center justify-between no-print">
        <a href="{{ route('school.fees.index') }}" class="text-xs text-slate-500 hover:text-emerald-500 flex items-center space-x-1">
            <span>←</span>
            <span>Back to Fee Department</span>
        </a>
        <button onclick="window.print()" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-md shadow-emerald-600/25 flex items-center space-x-1.5 transition">
            <span>🖨️</span>
            <span>Print Official Fee Receipt</span>
        </button>
    </div>

    <!-- Official Fee Receipt Card -->
    <div id="fee-receipt-card" class="bg-white text-slate-900 p-8 rounded-3xl shadow-2xl border-2 border-slate-300 relative print:border-none print:shadow-none print:p-0">
        <!-- School Header (Customized School + PrimeOS Mark) -->
        <div class="text-center pb-4 border-b-2 border-slate-900">
            @if(!empty($school['logo_url']))
            <img src="{{ $school['logo_url'] }}" alt="{{ $school['name'] }}" class="w-12 h-12 rounded-xl object-contain mx-auto mb-1.5 shadow-sm">
            @endif
            <h1 class="text-xl font-black tracking-tight text-slate-900 uppercase">
                {{ $school['name'] }}
            </h1>
            <p class="text-[10px] font-bold text-emerald-800 tracking-wider uppercase">
                Fee &amp; Accounts Department • Official Payment Receipt
            </p>
            <p class="text-[9px] text-slate-500 mt-0.5">
                {{ $school['campus'] ?: 'Campus not configured' }} • {{ $school['address'] ?: 'Address not configured' }} • UPI: {{ $school['upi_vpa'] ?: 'Not configured' }}
            </p>
        </div>

        <!-- Receipt No & Date -->
        <div class="flex items-center justify-between py-4 border-b border-slate-200 text-xs">
            <div>
                <span class="text-[10px] font-bold text-slate-400 uppercase block">Receipt Number</span>
                <span class="font-mono font-black text-emerald-700 text-sm">{{ $payment->receipt_number }}</span>
            </div>
            <div class="text-right">
                <span class="text-[10px] font-bold text-slate-400 uppercase block">Payment Date &amp; Time</span>
                <span class="font-medium text-slate-700">{{ \Carbon\Carbon::parse($payment->paid_at)->format('d M Y, h:i A') }}</span>
            </div>
        </div>

        <!-- Student & Invoice Details -->
        <div class="grid grid-cols-2 gap-4 py-4 border-b border-slate-200 text-xs">
            <div>
                <span class="text-[10px] font-bold text-slate-400 uppercase block">Student Name</span>
                <span class="font-bold text-slate-900 text-sm block">{{ $payment->invoice?->student?->name ?? 'Student' }}</span>
                <span class="text-[10px] text-slate-500 font-mono">Adm: {{ $payment->invoice?->student?->admission_number ?? '-' }}</span>
            </div>
            <div>
                <span class="text-[10px] font-bold text-slate-400 uppercase block">Class &amp; Section</span>
                <span class="font-bold text-slate-900 block">{{ $payment->invoice?->student?->currentClass?->display_name ?? 'N/A' }}</span>
                <span class="text-[10px] text-slate-500">Roll No: #{{ $payment->invoice?->student?->roll_number ?? '-' }}</span>
            </div>
        </div>

        <!-- Payment Breakdown -->
        <div class="py-4">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-100 uppercase font-black text-[10px] text-slate-600">
                    <tr>
                        <th class="p-2.5">Description</th>
                        <th class="p-2.5">Invoice Ref</th>
                        <th class="p-2.5 text-right">Amount (₹)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <tr>
                        <td class="p-2.5 font-bold text-slate-900">
                            {{ $payment->invoice?->title ?? 'Tuition Fee' }}
                        </td>
                        <td class="p-2.5 font-mono text-[11px] text-slate-500">
                            {{ $payment->invoice?->invoice_number }}
                        </td>
                        <td class="p-2.5 text-right font-black font-mono text-slate-900">
                            ₹{{ number_format($payment->amount, 2) }}
                        </td>
                    </tr>
                </tbody>
                <tfoot class="border-t-2 border-slate-900 font-bold bg-slate-50">
                    <tr>
                        <td colspan="2" class="p-2.5 uppercase font-black">Total Paid Amount:</td>
                        <td class="p-2.5 text-right font-black font-mono text-emerald-800 text-sm">
                            ₹{{ number_format($payment->amount, 2) }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Transaction Details & Signatures -->
        <div class="pt-4 border-t border-slate-200 text-xs flex items-end justify-between">
            <div class="text-[10px] space-y-0.5 text-slate-500">
                <p>Mode: <b class="uppercase text-slate-800">{{ $payment->payment_method }}</b></p>
                <p>Txn Ref: <b class="font-mono text-slate-800">{{ $payment->transaction_reference }}</b></p>
                <p>Cashier / Agent: <b>{{ $payment->received_by }}</b></p>
            </div>

            <div class="text-center">
                <div class="h-8 border-b border-slate-400 flex items-end justify-center font-serif italic text-xs text-slate-800 px-6">
                    Accounts Office
                </div>
                <span class="text-[9px] font-bold uppercase text-slate-400 tracking-wider">Authorized Signature</span>
            </div>
        </div>

        <div class="mt-6 pt-2 border-t border-slate-100 flex items-center justify-between text-[9px] text-slate-400">
            <span>Computer-generated school fee receipt • No signature required.</span>
            <span class="font-extrabold text-emerald-700">⚡ Powered by PrimeOS</span>
        </div>
    </div>

</div>

<style>
@media print {
    body * {
        visibility: hidden;
    }
    #fee-receipt-card, #fee-receipt-card * {
        visibility: visible;
    }
    #fee-receipt-card {
        position: absolute;
        left: 50%;
        top: 20%;
        transform: translateX(-50%);
        width: 100% !important;
        max-width: 500px !important;
        box-shadow: none !important;
        border: 2px solid #000 !important;
    }
    .no-print {
        display: none !important;
    }
}
</style>
@endsection
