@extends('install.layout')

@section('title', 'Installation Complete')

@section('content')
<div class="text-center py-4">
    <!-- Success Icon -->
    <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-emerald-500 to-teal-400 flex items-center justify-center shadow-xl shadow-emerald-500/20 text-white font-black text-2xl mx-auto mb-5">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
        </svg>
    </div>

    <h2 class="text-2xl sm:text-3xl font-black text-white tracking-tight">Installation Successfully Completed!</h2>
    <p class="text-sm text-slate-400 mt-2 max-w-lg mx-auto">
        Your Institutional School ERP system has been configured, migrated, branded, and permanently secured for production deployment.
    </p>

    <!-- Installation Summary Card -->
    <div class="my-8 max-w-md mx-auto p-5 rounded-xl border border-slate-800 bg-slate-900/60 text-left space-y-3">
        <div class="flex justify-between items-center text-xs border-b border-slate-800/80 pb-2.5">
            <span class="text-slate-400">School Name</span>
            <span class="font-bold text-white">{{ $branding['name'] ?? 'School ERP' }}</span>
        </div>
        <div class="flex justify-between items-center text-xs border-b border-slate-800/80 pb-2.5">
            <span class="text-slate-400">Board / Affiliation</span>
            <span class="font-bold text-white">{{ $branding['board_name'] ?? 'General Board' }}</span>
        </div>
        <div class="flex justify-between items-center text-xs border-b border-slate-800/80 pb-2.5">
            <span class="text-slate-400">Academic Session</span>
            <span class="font-bold text-white">{{ $branding['academic_session'] ?? '2026–2027' }}</span>
        </div>
        <div class="flex justify-between items-center text-xs border-b border-slate-800/80 pb-2.5">
            <span class="text-slate-400">Master Super Admin</span>
            <span class="font-mono text-purple-300 font-bold">{{ $admin->email ?? ($meta['admin_email'] ?? 'admin@school.local') }}</span>
        </div>
        <div class="flex justify-between items-center text-xs">
            <span class="text-slate-400">Security Lock</span>
            <span class="inline-flex items-center gap-1 font-bold text-emerald-400">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
                storage/installed locked
            </span>
        </div>
    </div>

    <!-- Security Advisory Note -->
    <div class="p-4 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-300 text-xs text-left max-w-lg mx-auto mb-8 flex items-start gap-3">
        <svg class="w-5 h-5 text-amber-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <div>
            <div class="font-bold text-amber-200">Security & Commercial Ready</div>
            <div class="mt-0.5 text-slate-300 text-[11px]">
                The web installer has been permanently locked. Any future attempts to access <code class="font-mono text-amber-300">/install</code> will automatically redirect to the login screen. You are currently logged in as Super Administrator.
            </div>
        </div>
    </div>

    <!-- Primary Launch Action -->
    <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
        <a href="{{ route('dashboard') }}" class="w-full sm:w-auto px-8 py-3 rounded-xl text-xs font-black text-white bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 shadow-xl shadow-purple-500/30 transition transform hover:-translate-y-0.5">
            Launch Institutional Dashboard &rarr;
        </a>
        <a href="{{ route('public.admissions.apply') }}" target="_blank" class="w-full sm:w-auto px-6 py-3 rounded-xl text-xs font-bold text-slate-300 bg-slate-900 hover:bg-slate-800 border border-slate-800 transition">
            Preview Online Admission Portal ↗
        </a>
    </div>
</div>
@endsection
