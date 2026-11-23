@extends('layouts.public')

@section('title', 'Student ID Verification')

@section('content')
<div class="max-w-lg mx-auto px-4 py-12">
    <section class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-lg p-6 sm:p-8 space-y-5">
        <header class="text-center space-y-2">
            <div class="inline-flex items-center rounded-full px-3 py-1 text-xs font-bold {{ $isActive ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-900' }}">
                @if($isExpired)
                    Expired identity card
                @elseif($isActive)
                    Active student identity
                @else
                    Identity verified — enrollment inactive
                @endif
            </div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white">Student ID verification</h1>
            <p class="text-sm text-slate-500">{{ $school['name'] ?? config('app.name') }}</p>
        </header>

        <div class="rounded-xl bg-slate-50 dark:bg-slate-800 p-4 space-y-3">
            <div>
                <span class="block text-[10px] font-bold uppercase tracking-wide text-slate-500">Student</span>
                <span class="font-bold text-slate-900 dark:text-white">{{ $student->name }}</span>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <span class="block text-[10px] font-bold uppercase tracking-wide text-slate-500">Admission number</span>
                    <span class="font-mono text-sm text-slate-800 dark:text-slate-200">••••{{ substr((string) $student->admission_number, -4) }}</span>
                </div>
                <div>
                    <span class="block text-[10px] font-bold uppercase tracking-wide text-slate-500">Class</span>
                    <span class="text-sm font-semibold text-slate-800 dark:text-slate-200">{{ $student->currentClass?->display_name ?? 'Not currently assigned' }}</span>
                </div>
            </div>
            <div>
                <span class="block text-[10px] font-bold uppercase tracking-wide text-slate-500">Enrollment</span>
                <span class="text-sm font-bold {{ $isActive ? 'text-emerald-700' : 'text-amber-700' }}">{{ $isActive ? 'Currently active' : 'Not currently active' }}</span>
            </div>
            @if($student->card_valid_until)
                <div>
                    <span class="block text-[10px] font-bold uppercase tracking-wide text-slate-500">Card valid until</span>
                    <span class="text-sm text-slate-800 dark:text-slate-200">{{ $student->card_valid_until->format('d M Y') }}</span>
                </div>
            @endif
        </div>

        <p class="text-xs text-slate-500 text-center">The cryptographic signature is valid. This page displays only limited identity information and does not disclose contact details, date of birth, address, or guardian information.</p>
        <p class="text-[10px] text-slate-400 text-center">Checked {{ $verifiedAt->format('Y-m-d H:i:s T') }}</p>
    </section>
</div>
@endsection
