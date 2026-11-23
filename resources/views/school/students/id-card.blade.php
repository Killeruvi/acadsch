@extends('layouts.app')

@section('title', 'Student ID Card - ' . $student->name)

@section('content')
<div class="max-w-2xl mx-auto px-4 py-8 space-y-5">
    <div class="flex items-center justify-between gap-3 no-print">
        <a href="{{ route('school.students.show', $student->id) }}" class="text-xs text-slate-600 hover:text-emerald-700">← Back to Student File</a>
        <button type="button" onclick="window.print()" class="px-4 py-2 rounded-xl bg-emerald-700 text-white text-xs font-bold">Print ID Card</button>
    </div>

    <article id="school-id-card" class="bg-white text-slate-900 border-2 border-emerald-700 rounded-2xl max-w-md mx-auto p-5 shadow-lg">
        <header class="text-center pb-3 border-b-2 border-emerald-700">
            @if(!empty($school['logo_url']))
                <img src="{{ $school['logo_url'] }}" alt="{{ $school['name'] }} logo" class="w-12 h-12 object-contain mx-auto mb-2">
            @endif
            <h1 class="text-base font-black uppercase tracking-wide">{{ $school['name'] }}</h1>
            @if(!empty($school['campus']) && $school['campus'] !== 'Not configured')
                <p class="text-xs text-slate-600">{{ $school['campus'] }}</p>
            @endif
            @if(!empty($school['address']))
                <p class="text-[10px] text-slate-500">{{ $school['address'] }}</p>
            @endif
            @if(!empty($school['affiliation']) && $school['affiliation'] !== 'Not configured')
                <p class="text-[10px] text-slate-500">Affiliation No: {{ $school['affiliation'] }}</p>
            @endif
        </header>

        <div class="pt-4 flex gap-4">
            <div class="w-24 h-28 shrink-0 border border-slate-300 rounded-lg bg-slate-50 flex items-center justify-center overflow-hidden">
                @if(!empty($student->avatar_url))
                    <img src="{{ $student->avatar_url }}" alt="Student photograph" class="w-full h-full object-cover">
                @else
                    <div class="text-center p-2">
                        <span class="block text-2xl font-black text-slate-500">{{ mb_strtoupper(mb_substr($student->name, 0, 1)) }}</span>
                        <span class="block text-[9px] text-slate-500">Photo not uploaded</span>
                    </div>
                @endif
            </div>
            <div class="min-w-0 flex-1 space-y-2 text-xs">
                <div><span class="block text-[9px] uppercase text-slate-500">Student Name</span><strong class="text-sm break-words">{{ $student->name }}</strong></div>
                <div class="grid grid-cols-2 gap-2">
                    <div><span class="block text-[9px] uppercase text-slate-500">Admission No.</span><strong class="font-mono">{{ $student->admission_number ?: 'Not assigned' }}</strong></div>
                    <div><span class="block text-[9px] uppercase text-slate-500">Roll No.</span><strong class="font-mono">{{ $student->roll_number ?: 'Not assigned' }}</strong></div>
                </div>
                <div><span class="block text-[9px] uppercase text-slate-500">Class / Section</span><strong>{{ $student->currentClass?->display_name ?? 'Not currently assigned' }}</strong></div>
                <div><span class="block text-[9px] uppercase text-slate-500">Academic Session</span><strong>{{ $school['academic_session'] ?? 'Not configured' }}</strong></div>
            </div>
        </div>

        <footer class="mt-4 pt-3 border-t border-slate-300 flex items-center justify-between gap-3">
            <div class="min-w-0">
                <span class="block text-[9px] uppercase font-bold text-slate-500">Official identity verification</span>
                @if($verificationUrl)
                    <a href="{{ $verificationUrl }}" target="_blank" rel="noopener noreferrer" class="text-xs font-bold text-emerald-800 underline break-all">Verify this ID online</a>
                @else
                    <span class="text-xs text-amber-800">Verification unavailable: admission number or application key is not configured.</span>
                @endif
            </div>
            <span class="shrink-0 text-[9px] font-bold uppercase text-slate-500">Student ID</span>
        </footer>
    </article>
</div>

<style>
@media print {
    body * { visibility: hidden !important; }
    #school-id-card, #school-id-card * { visibility: visible !important; }
    #school-id-card { position: absolute; left: 50%; top: 12mm; transform: translateX(-50%); width: 85.6mm; min-height: 54mm; box-shadow: none !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .no-print { display: none !important; }
    @page { size: auto; margin: 8mm; }
}
</style>
@endsection
