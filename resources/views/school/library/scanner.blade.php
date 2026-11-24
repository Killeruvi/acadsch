@extends('layouts.app')

@section('title', 'Library Barcode Quick-Scan Terminal — PrimeOS')

@section('content')
<div class="space-y-6" x-data="{
    bookBarcode: '',
    memberBarcode: '',
    message: '',
    status: '',
    loading: false,
    awaitingMember: false,
    identifiedBook: '',
    history: [],

    async submitScan() {
        if (!this.bookBarcode.trim()) return;
        this.loading = true;
        this.message = '';

        try {
            const res = await fetch('{{ route('school.library.scanner.scan') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    barcode: this.bookBarcode,
                    member_barcode: this.memberBarcode
                })
            });
            const data = await res.json();
            this.loading = false;

            if (data.success) {
                this.status = 'success';
                this.message = data.message;

                if (data.action === 'awaiting_member') {
                    this.awaitingMember = true;
                    this.identifiedBook = data.book_title;
                    this.$nextTick(() => this.$refs.memberInput.focus());
                } else {
                    // Finished issue or return
                    this.history.unshift({
                        action: data.action,
                        book: data.book_title,
                        member: data.member_name,
                        time: new Date().toLocaleTimeString(),
                        message: data.message
                    });
                    this.resetInputs();
                }
            } else {
                this.status = 'error';
                this.message = data.message || 'Operation failed.';
            }
        } catch (e) {
            this.loading = false;
            this.status = 'error';
            this.message = 'Network error during barcode scan.';
        }
    },

    resetInputs() {
        this.bookBarcode = '';
        this.memberBarcode = '';
        this.awaitingMember = false;
        this.identifiedBook = '';
        this.$nextTick(() => this.$refs.bookInput.focus());
    }
}">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2 text-xs text-slate-500 mb-1">
                <a href="{{ route('school.library.index') }}" class="hover:text-indigo-600">Library Catalog</a>
                <span>&bull;</span>
                <span>Circulation Desk</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                Barcode Quick-Scan Circulation Terminal
            </h1>
            <p class="text-sm text-slate-600 dark:text-slate-400">
                Plug in any USB/Bluetooth handheld barcode reader. Scan to issue or return instantly.
            </p>
        </div>

        <div class="flex items-center space-x-2">
            <a href="{{ route('school.library.index') }}" class="px-3.5 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                &larr; Back to Catalog
            </a>
        </div>
    </div>

    <!-- Active Scanner Station -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        <!-- Left: Scan Console (7 cols) -->
        <div class="lg:col-span-7 space-y-5">
            <div class="bg-white dark:bg-slate-900 rounded-3xl border-2 border-indigo-200 dark:border-indigo-900/60 p-6 sm:p-8 shadow-sm space-y-6">
                
                <div class="flex items-center justify-between">
                    <span class="inline-flex items-center space-x-2 px-3 py-1 rounded-full text-xs font-bold bg-indigo-50 text-indigo-800 dark:bg-indigo-950/60 dark:text-indigo-300">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span>Scanner Ready &bull; Auto-Focus Active</span>
                    </span>
                    <button type="button" @click="resetInputs()" class="text-xs font-bold text-slate-400 hover:text-slate-600">
                        Clear &amp; Reset
                    </button>
                </div>

                <!-- Input Form -->
                <form @submit.prevent="submitScan()" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                            1. Scan Book Accession Barcode / ISBN <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 text-base">
                                📖
                            </span>
                            <input type="text" 
                                x-ref="bookInput" 
                                x-model="bookBarcode" 
                                placeholder="Scan book barcode or type accession number..." 
                                autofocus 
                                required
                                class="w-full pl-11 pr-4 py-3 rounded-2xl border-2 border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-950 text-slate-900 dark:text-white text-sm font-mono font-bold focus:border-indigo-500 focus:bg-white focus:outline-hidden transition">
                        </div>
                    </div>

                    <div x-show="awaitingMember" x-cloak class="p-4 rounded-2xl bg-indigo-50 dark:bg-indigo-950/40 border border-indigo-200 dark:border-indigo-800 space-y-3">
                        <div>
                            <span class="text-[11px] font-bold text-indigo-700 dark:text-indigo-300 uppercase block">Selected Book:</span>
                            <div class="text-sm font-bold text-slate-900 dark:text-white" x-text="identifiedBook"></div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                2. Now Scan Scholar ID Card / Staff Badge <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 text-base">
                                    🪪
                                </span>
                                <input type="text" 
                                    x-ref="memberInput" 
                                    x-model="memberBarcode" 
                                    placeholder="Scan member admission number or badge ID..." 
                                    class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-indigo-300 dark:border-indigo-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white text-xs font-mono font-bold focus:ring-2 focus:ring-indigo-500">
                            </div>
                        </div>
                    </div>

                    <button type="submit" :disabled="loading" class="w-full py-3 rounded-2xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-black tracking-wide uppercase shadow-md transition disabled:opacity-50">
                        <span x-show="!loading">Execute Scan Transaction</span>
                        <span x-show="loading">Processing Barcode...</span>
                    </button>
                </form>

                <!-- Feedback Banner -->
                <div x-show="message" x-cloak class="p-4 rounded-2xl text-xs font-bold flex items-start space-x-2.5"
                    :class="status === 'success' ? 'bg-emerald-50 text-emerald-900 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800' : 'bg-rose-50 text-rose-900 border border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800'">
                    <span class="text-base" x-text="status === 'success' ? '✓' : '✕'"></span>
                    <div class="flex-1" x-text="message"></div>
                </div>

                <!-- Instructions -->
                <div class="pt-4 border-t border-slate-100 dark:border-slate-800 grid grid-cols-2 gap-3 text-[11px] text-slate-500">
                    <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/40">
                        <span class="font-bold text-slate-700 dark:text-slate-300 block mb-0.5">🔄 Instant Return:</span>
                        If a book is currently on loan, scanning it immediately checks it back into inventory.
                    </div>
                    <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/40">
                        <span class="font-bold text-slate-700 dark:text-slate-300 block mb-0.5">📚 Instant Issue:</span>
                        If a book is available, the console prompts for the scholar's ID card barcode to check out.
                    </div>
                </div>

            </div>
        </div>

        <!-- Right: Real-time Scan Session History (5 cols) -->
        <div class="lg:col-span-5 space-y-4">
            <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-5 shadow-xs space-y-3">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white border-b pb-2 flex items-center justify-between">
                    <span>Recent Circulation Feed</span>
                    <span class="text-[10px] text-slate-400 font-normal">Live Terminal Activity</span>
                </h3>

                <div class="space-y-2 max-h-[480px] overflow-y-auto">
                    <!-- Session Scans -->
                    <template x-for="item in history" :key="item.time + item.book">
                        <div class="p-3 rounded-xl border text-xs space-y-1 transition"
                            :class="item.action === 'return' ? 'bg-amber-50/60 border-amber-200 dark:bg-amber-950/30 dark:border-amber-900' : 'bg-emerald-50/60 border-emerald-200 dark:bg-emerald-950/30 dark:border-emerald-900'">
                            <div class="flex items-center justify-between">
                                <span class="font-bold uppercase text-[10px] px-2 py-0.5 rounded-full"
                                    :class="item.action === 'return' ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-300' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-300'"
                                    x-text="item.action === 'return' ? 'Book Returned' : 'Book Issued'">
                                </span>
                                <span class="text-[10px] text-slate-400" x-text="item.time"></span>
                            </div>
                            <div class="font-bold text-slate-900 dark:text-white" x-text="item.book"></div>
                            <div class="text-[11px] text-slate-600 dark:text-slate-400" x-text="'Member: ' + item.member"></div>
                        </div>
                    </template>

                    <!-- Initial Historical Scans -->
                    @forelse($recentTransactions as $tx)
                    <div class="p-3 rounded-xl border border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/20 text-xs space-y-1">
                        <div class="flex items-center justify-between">
                            <span class="font-bold uppercase text-[10px] px-2 py-0.5 rounded-full
                                {{ $tx->status === 'returned' ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300' }}">
                                {{ $tx->status }}
                            </span>
                            <span class="text-[10px] text-slate-400">{{ $tx->updated_at->diffForHumans() }}</span>
                        </div>
                        <div class="font-bold text-slate-900 dark:text-white">{{ $tx->book?->book_title }}</div>
                        <div class="text-[11px] text-slate-500">Member: {{ $tx->member?->name }} ({{ $tx->member?->admission_number ?? 'Staff' }})</div>
                    </div>
                    @empty
                    <div class="text-center p-6 text-slate-400 text-xs">
                        No transactions recorded in this session.
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
