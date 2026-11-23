@extends('layouts.app')

@section('title', $cardType . ' - ' . $user->name)

@section('content')
<div class="max-w-2xl mx-auto px-4 py-8 space-y-5">
    <div class="flex items-center justify-between gap-3 print:hidden">
        <div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white">{{ $cardType }}</h1>
            <p class="text-xs text-slate-500">Institution-issued identity card</p>
        </div>
        <button type="button" onclick="window.print()" class="px-4 py-2 rounded-xl bg-emerald-700 text-white text-xs font-bold">Print / Save</button>
    </div>

    <article id="virtual-id-card" class="max-w-md mx-auto bg-white text-slate-900 border-2 border-slate-800 rounded-2xl p-5 shadow-lg">
        <header class="text-center border-b border-slate-300 pb-3">
            @if(!empty($school['logo_url']))
                <img src="{{ $school['logo_url'] }}" alt="{{ $school['name'] }} logo" class="w-12 h-12 object-contain mx-auto mb-2">
            @endif
            <h2 class="text-base font-black uppercase">{{ strtoupper($school['name']) }}</h2>
            <p class="text-[10px] uppercase tracking-wider text-slate-600">{{ $cardType }}</p>
        </header>

        <div class="flex gap-4 py-4">
            <div class="w-24 h-28 shrink-0 rounded-lg border border-slate-300 bg-slate-50 overflow-hidden flex items-center justify-center">
                @if(!empty($user->avatar_url))
                    <img src="{{ $user->avatar_url }}" alt="Profile photograph" class="w-full h-full object-cover">
                @else
                    <span class="text-3xl font-black text-slate-500">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                @endif
            </div>
            <div class="min-w-0 flex-1 space-y-2 text-xs">
                <div><span class="block text-[9px] uppercase text-slate-500">Name</span><strong class="text-sm">{{ $user->name }}</strong></div>
                <div><span class="block text-[9px] uppercase text-slate-500">Role</span><strong>{{ ucfirst($user->role) }}</strong></div>
                @if($user->role === 'student')
                    <div><span class="block text-[9px] uppercase text-slate-500">Admission No.</span><strong class="font-mono">{{ $user->admission_number ?: 'Not assigned' }}</strong></div>
                    <div><span class="block text-[9px] uppercase text-slate-500">Class</span><strong>{{ $user->currentClass?->display_name ?? 'Not currently assigned' }}</strong></div>
                @endif
                <div><span class="block text-[9px] uppercase text-slate-500">Institution email</span><strong class="break-all">{{ $user->email }}</strong></div>
            </div>
        </div>

        <footer class="border-t border-slate-300 pt-3 text-center">
            @if($verificationUrl)
                <a href="{{ $verificationUrl }}" target="_blank" rel="noopener noreferrer" class="text-xs font-bold text-emerald-800 underline">Open official online verification</a>
            @elseif($user->role === 'student')
                <span class="text-xs text-amber-800">Online verification unavailable until an admission number and application key are configured.</span>
            @else
                <span class="text-[10px] text-slate-500">Present this card to the institution for manual verification.</span>
            @endif
        </footer>
    </article>
</div>

<style>
@media print {
    body * { visibility: hidden !important; }
    #virtual-id-card, #virtual-id-card * { visibility: visible !important; }
    #virtual-id-card { position: absolute; left: 50%; top: 12mm; transform: translateX(-50%); width: 85.6mm; min-height: 54mm; box-shadow: none !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    @page { size: auto; margin: 8mm; }
}
</style>
@endsection
