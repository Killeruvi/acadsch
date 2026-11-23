@extends('layouts.app')

@section('title', 'Admit Card Verification - ' . $verificationData['student_name'])

@section('content')
<div class="max-w-xl mx-auto px-4 py-12">
    <div class="bg-white dark:bg-slate-900 rounded-3xl p-8 border border-slate-200 dark:border-slate-800 shadow-xl text-center space-y-6">
        
        <div class="w-16 h-16 rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mx-auto text-3xl shadow-md shadow-emerald-500/20">
            ✓
        </div>

        <div>
            <span class="inline-block px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300">
                Official Document Verified
            </span>
            <h1 class="text-xl font-black text-slate-900 dark:text-white mt-2">
                Valid Examination Admit Card
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Verified cryptographically against institutional records
            </p>
        </div>

        <div class="bg-slate-50 dark:bg-slate-800/60 rounded-2xl p-4 text-left space-y-3 text-xs border border-slate-200 dark:border-slate-700/60">
            <div class="flex justify-between py-1 border-b border-slate-200 dark:border-slate-700">
                <span class="text-slate-500">Candidate Name:</span>
                <span class="font-bold text-slate-900 dark:text-white">{{ $verificationData['student_name'] }}</span>
            </div>
            <div class="flex justify-between py-1 border-b border-slate-200 dark:border-slate-700">
                <span class="text-slate-500">Admission Number:</span>
                <span class="font-mono font-bold text-indigo-600 dark:text-indigo-400">{{ $verificationData['admission_number'] }}</span>
            </div>
            <div class="flex justify-between py-1 border-b border-slate-200 dark:border-slate-700">
                <span class="text-slate-500">Class &amp; Section:</span>
                <span class="font-bold text-slate-900 dark:text-white">{{ $verificationData['class_name'] }}</span>
            </div>
            <div class="flex justify-between py-1 border-b border-slate-200 dark:border-slate-700">
                <span class="text-slate-500">Roll Number:</span>
                <span class="font-mono font-bold text-slate-900 dark:text-white">#{{ $verificationData['roll_number'] ?? '-' }}</span>
            </div>
            <div class="flex justify-between py-1 border-b border-slate-200 dark:border-slate-700">
                <span class="text-slate-500">Exam Term:</span>
                <span class="font-bold text-slate-900 dark:text-white">{{ $verificationData['term_name'] }} ({{ $verificationData['academic_year'] }})</span>
            </div>
            <div class="flex justify-between py-1">
                <span class="text-slate-500">Verification Timestamp:</span>
                <span class="text-[11px] font-mono text-slate-600 dark:text-slate-400">{{ \Carbon\Carbon::parse($verificationData['verified_at'])->format('d M Y, h:i:s A') }}</span>
            </div>
        </div>

        <div class="pt-2">
            <a href="{{ route('school.exams.index') }}" class="inline-block px-5 py-2.5 rounded-xl bg-slate-900 text-white dark:bg-white dark:text-slate-900 text-xs font-bold hover:opacity-90 transition">
                Return to Portal
            </a>
        </div>

    </div>
</div>
@endsection
