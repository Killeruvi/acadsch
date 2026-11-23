@extends('layouts.app')

@section('title', 'Faculty Authorization Codes & Teacher Approval' . ' — ' . ($school['name'] ?? 'PrimeArc Public School'))

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 py-6" x-data="{ copiedCode: null }">

    @if(session('success'))
    <div class="mb-4 p-3.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-xs font-semibold flex items-center justify-between shadow-xs">
        <div class="flex items-center space-x-2">
            <span>✅</span>
            <span>{{ session('success') }}</span>
        </div>
        <button type="button" @click="$el.parentElement.remove()" class="text-emerald-600 hover:text-emerald-800 dark:text-emerald-400">&times;</button>
    </div>
    @endif

    @if(session('error'))
    <div class="mb-4 p-3.5 rounded-xl bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-300 text-xs font-semibold flex items-center justify-between shadow-xs">
        <div class="flex items-center space-x-2">
            <span>⚠️</span>
            <span>{{ session('error') }}</span>
        </div>
        <button type="button" @click="$el.parentElement.remove()" class="text-rose-600 hover:text-rose-800 dark:text-rose-400">&times;</button>
    </div>
    @endif

    <!-- Header & Breadcrumb -->
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center space-x-2 text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1">
                <a href="{{ route('dashboard') }}" class="hover:text-emerald-600 dark:hover:text-emerald-400 transition">&larr; Back to Dashboard</a>
                <span>&bull;</span>
                <span>Administrative Security</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight flex items-center space-x-2">
                <span>🔑</span>
                <span>Faculty Authorization Codes &amp; Teacher Lock Control</span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Teacher registration is strictly locked. Teachers must enter a manual admin-generated code, restricted to one request per device.
            </p>
        </div>

        <div class="flex items-center space-x-2">
            <span class="px-3 py-1 rounded-full text-xs font-bold bg-purple-100 dark:bg-purple-950/60 text-purple-800 dark:text-purple-300 border border-purple-200 dark:border-purple-800">
                🔒 Admin Protected
            </span>
        </div>
    </div>

    <!-- 1. Generate New Code Card -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 mb-6 shadow-xs">
        <div class="flex items-center space-x-2 mb-3">
            <span class="text-lg">➕</span>
            <h2 class="text-sm font-extrabold text-slate-900 dark:text-white">Generate Manual Authorization Code</h2>
        </div>
        <form method="POST" action="{{ route('admin.faculty-codes.store') }}" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
            @csrf
            <div class="flex-1">
                <input type="text" name="notes" placeholder="Optional recipient note (e.g., Dr. Vikramaditya - Mathematics Department)"
                       class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>
            <button type="submit"
                    class="px-5 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold transition shadow-xs flex items-center justify-center space-x-1.5 flex-shrink-0">
                <span>🔑</span>
                <span>Generate Code</span>
            </button>
        </form>
    </div>

    <!-- 2 Column Layout: Active Codes & Faculty Status -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        
        <!-- Active / Unused Codes -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs flex flex-col">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800 mb-3">
                <div class="flex items-center space-x-2">
                    <span class="text-base">🏷️</span>
                    <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-800 dark:text-slate-200">
                        Active Authorization Codes ({{ $unusedCodes->count() }})
                    </h3>
                </div>
                <span class="text-[11px] text-emerald-600 dark:text-emerald-400 font-bold">Ready for input</span>
            </div>

            <div class="space-y-2.5 overflow-y-auto max-h-[350px] pr-1">
                @forelse($unusedCodes as $code)
                <div class="p-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 flex items-center justify-between gap-2">
                    <div>
                        <div class="flex items-center space-x-2">
                            <span class="font-mono font-black text-sm text-purple-700 dark:text-purple-300 tracking-wider select-all">
                                {{ $code->code }}
                            </span>
                            <button type="button" 
                                    @click="navigator.clipboard.writeText('{{ $code->code }}'); copiedCode = '{{ $code->code }}'; setTimeout(() => copiedCode = null, 2000)"
                                    class="text-[10px] font-bold px-2 py-0.5 rounded bg-purple-100 dark:bg-purple-900 text-purple-700 dark:text-purple-300 hover:bg-purple-200 transition">
                                <span x-text="copiedCode === '{{ $code->code }}' ? '✓ Copied' : '📋 Copy'"></span>
                            </button>
                        </div>
                        <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">
                            {{ $code->notes ?? 'Standard Faculty Onboarding' }} &bull; {{ $code->created_at->diffForHumans() }}
                        </div>
                    </div>

                    <form method="POST" action="{{ route('admin.faculty-codes.destroy', $code->id) }}" onsubmit="return confirm('Revoke this authorization code?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-xs text-rose-500 hover:text-rose-700 font-bold p-1" title="Revoke Code">
                            🗑️
                        </button>
                    </form>
                </div>
                @empty
                <div class="p-6 text-center text-slate-400 dark:text-slate-500 text-xs">
                    No active codes. Click "Generate Code" above to authorize a new teacher.
                </div>
                @endforelse
            </div>

            @if($unusedCodes->hasPages())
            <div class="mt-3 pt-2 border-t border-slate-100 dark:border-slate-800">
                {{ $unusedCodes->links() }}
            </div>
            @endif
        </div>

        <!-- Faculty Member Account Lock Controls -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs flex flex-col">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800 mb-3">
                <div class="flex items-center space-x-2">
                    <span class="text-base">👨‍🏫</span>
                    <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-800 dark:text-slate-200">
                        Faculty Accounts &amp; Lock Status ({{ $teachers->count() }})
                    </h3>
                </div>
                <span class="text-[11px] text-slate-400 dark:text-slate-500">Admin Approval Toggle</span>
            </div>

            <div class="space-y-2.5 overflow-y-auto max-h-[350px] pr-1">
                @forelse($teachers as $tch)
                <div class="p-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex items-center space-x-2">
                            <h4 class="text-xs font-extrabold text-slate-900 dark:text-white truncate">{{ $tch->name }}</h4>
                            <span class="px-1.5 py-0.2 rounded text-[10px] font-bold {{ $tch->is_approved ? 'bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300' : 'bg-rose-100 dark:bg-rose-950 text-rose-700 dark:text-rose-300' }}">
                                {{ $tch->is_approved ? '✓ Approved' : '🔒 Locked' }}
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate mt-0.5">
                            {{ $tch->email }} &bull; {{ $tch->phone ?? 'No Phone' }}
                        </p>
                    </div>

                    <form method="POST" action="{{ route('admin.faculty.toggle-lock', $tch->id) }}">
                        @csrf
                        <button type="submit" 
                                class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $tch->is_approved ? 'bg-rose-50 dark:bg-rose-950 text-rose-700 dark:text-rose-300 border border-rose-200 hover:bg-rose-100' : 'bg-emerald-600 hover:bg-emerald-500 text-white' }}">
                            {{ $tch->is_approved ? 'Lock Account' : 'Approve & Unlock' }}
                        </button>
                    </form>
                </div>
                @empty
                <div class="p-6 text-center text-slate-400 dark:text-slate-500 text-xs">
                    No faculty accounts registered yet.
                </div>
                @endforelse
            </div>

            @if($teachers->hasPages())
            <div class="mt-3 pt-2 border-t border-slate-100 dark:border-slate-800">
                {{ $teachers->links() }}
            </div>
            @endif
        </div>

    </div>

    <!-- 3. Redeemed Codes & Hardware Device Locks Registry -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        <!-- Redeemed Codes History -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800 mb-3">
                <div class="flex items-center space-x-2">
                    <span class="text-base">📋</span>
                    <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-800 dark:text-slate-200">
                        Redeemed Codes History ({{ $usedCodes->count() }})
                    </h3>
                </div>
            </div>

            <div class="space-y-2 overflow-y-auto max-h-[300px]">
                @forelse($usedCodes as $uc)
                <div class="p-2.5 rounded-xl border border-slate-100 dark:border-slate-800/80 bg-slate-50 dark:bg-slate-800/40 text-xs flex items-center justify-between">
                    <div>
                        <span class="font-mono font-bold text-slate-700 dark:text-slate-300">{{ $uc->code }}</span>
                        <div class="text-[11px] text-slate-500 dark:text-slate-400">
                            Teacher: <strong class="text-slate-800 dark:text-slate-200">{{ $uc->teacher?->name ?? 'User #' . $uc->used_by }}</strong>
                        </div>
                    </div>
                    <span class="text-[10px] text-slate-400 dark:text-slate-500">
                        {{ $uc->used_at?->format('M d, Y H:i') ?? 'Completed' }}
                    </span>
                </div>
                @empty
                <div class="p-4 text-center text-slate-400 text-xs">No codes redeemed yet.</div>
                @endforelse
            </div>

            @if($usedCodes->hasPages())
            <div class="mt-3 pt-2 border-t border-slate-100 dark:border-slate-800">
                {{ $usedCodes->links() }}
            </div>
            @endif
        </div>

        <!-- Device Lock Registry -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800 mb-3">
                <div class="flex items-center space-x-2">
                    <span class="text-base">📱</span>
                    <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-800 dark:text-slate-200">
                        Device Lock Registry ({{ $deviceLocks->count() }})
                    </h3>
                </div>
                <span class="text-[10px] text-purple-600 dark:text-purple-400 font-bold">1 Request / Device Enforced</span>
            </div>

            <div class="space-y-2 overflow-y-auto max-h-[300px]">
                @forelse($deviceLocks as $dl)
                <div class="p-2.5 rounded-xl border border-slate-100 dark:border-slate-800/80 bg-slate-50 dark:bg-slate-800/40 text-xs flex items-center justify-between">
                    <div class="min-w-0 flex-1 pr-2">
                        <div class="font-mono text-[11px] text-slate-700 dark:text-slate-300 truncate">
                            ID: {{ substr($dl->device_id, 0, 16) }}...
                        </div>
                        <div class="text-[10px] text-slate-500 dark:text-slate-400">
                            IP: {{ $dl->ip_address }} &bull; User: {{ $dl->user?->name ?? 'Faculty' }}
                        </div>
                    </div>
                    <form method="POST" action="{{ route('admin.faculty.unlock-device', $dl->id) }}" onsubmit="return confirm('Clear device lock for this device?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-2 py-1 rounded bg-slate-200 dark:bg-slate-700 hover:bg-rose-100 dark:hover:bg-rose-950 text-slate-700 dark:text-slate-300 text-[10px] font-bold transition">
                            Unlock Device
                        </button>
                    </form>
                </div>
                @empty
                <div class="p-4 text-center text-slate-400 text-xs">No devices locked yet.</div>
                @endforelse
            </div>

            @if($deviceLocks->hasPages())
            <div class="mt-3 pt-2 border-t border-slate-100 dark:border-slate-800">
                {{ $deviceLocks->links() }}
            </div>
            @endif
        </div>

    </div>

</div>
@endsection
