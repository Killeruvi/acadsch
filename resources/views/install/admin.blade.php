@extends('install.layout')

@section('title', 'Super Administrator Setup')

@section('content')
<div>
    <div class="mb-6">
        <h2 class="text-xl sm:text-2xl font-black text-white tracking-tight">4. Master Super Administrator Account</h2>
        <p class="text-sm text-slate-400 mt-1">
            Create the primary institutional administrator account. This master account has complete privileges across all modules, settings, and faculties.
        </p>
    </div>

    <form method="POST" action="{{ route('install.admin.post') }}" class="space-y-5">
        @csrf

        <!-- Admin Name & Email -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">Administrator Full Name <span class="text-rose-400">*</span></label>
                <input type="text" name="name" value="{{ old('name', 'Institutional Administrator') }}" required class="w-full px-3.5 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-white text-xs focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none" placeholder="e.g. Dr. Robert Vance">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">Administrator Login Email <span class="text-rose-400">*</span></label>
                <input type="email" name="email" value="{{ old('email', 'admin@school.local') }}" required class="w-full px-3.5 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-white text-xs focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none" placeholder="admin@yourschool.com">
            </div>
        </div>

        <!-- Admin Password & Confirmation -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">Secure Password <span class="text-rose-400">*</span></label>
                <input type="password" name="password" required minlength="8" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-white text-xs focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none" placeholder="••••••••••••">
                <p class="text-[11px] text-slate-500 mt-1">Minimum 8 characters with letters, numbers, and symbols.</p>
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">Confirm Password <span class="text-rose-400">*</span></label>
                <input type="password" name="password_confirmation" required minlength="8" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-white text-xs focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none" placeholder="••••••••••••">
            </div>
        </div>

        <!-- Academic Session -->
        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">Initial Academic Year / Session <span class="text-rose-400">*</span></label>
            <input type="text" name="academic_session" value="{{ old('academic_session', $defaultSession) }}" required class="w-full px-3.5 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-white text-xs focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none" placeholder="2026–2027">
            <p class="text-[11px] text-slate-500 mt-1">Format: YYYY–YYYY (used across timetable, grading terms, attendance registers, and fees).</p>
        </div>

        <!-- Clean Structure Provisioning Checkbox -->
        <div class="p-4 rounded-xl border border-purple-500/20 bg-purple-500/5">
            <label class="flex items-start gap-3 cursor-pointer">
                <input type="checkbox" name="provision_k12_structure" value="1" checked class="mt-0.5 rounded text-purple-600 focus:ring-purple-500">
                <div>
                    <div class="text-xs font-bold text-white">Provision Clean Production K-12 Structural Baseline (Recommended)</div>
                    <div class="text-[11px] text-slate-400 mt-0.5">
                        Automatically configures standard Nursery through Grade 12 classes (with Section A), standard core academic subject masters (English, Mathematics, Science, Social Studies, AI/Computer Science, Physics, Chemistry, Accountancy), 8 daily timetable periods, and standard fee heads.
                    </div>
                    <div class="text-[10px] text-emerald-400 font-semibold mt-1">
                        ✓ 100% Clean Baseline &bull; ZERO mock dummy students &bull; Purely production-ready for immediate admissions
                    </div>
                </div>
            </label>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-between pt-6 border-t border-slate-800">
            <a href="{{ route('install.branding') }}" class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-400 hover:text-white bg-slate-900 hover:bg-slate-800 transition">
                &larr; Back to Branding
            </a>
            <button type="submit" class="px-6 py-2.5 rounded-xl text-xs font-extrabold text-white bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 shadow-lg shadow-purple-500/25 transition">
                Complete Installation & Lock System &rarr;
            </button>
        </div>
    </form>
</div>
@endsection
