@extends('layouts.app')

@section('title', 'Institutional Onboarding Wizard - School Management System')

@section('content')
<div class="max-w-[1200px] mx-auto px-4 sm:px-6 lg:px-8 py-4 space-y-4">

    <!-- Header Panel -->
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-3.5 sm:p-4 shadow-xs">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight flex items-center space-x-2">
                    <span>🚀 Institutional Onboarding Wizard</span>
                </h1>
                <div class="flex flex-wrap items-center gap-1.5 mt-1">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                        Setup Progress: {{ $progressPercent }}% Complete
                    </span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                        Step {{ $currentStep }} of 6
                    </span>
                    @if(!empty($settings['onboarding_current_step']))
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300">
                        ✓ Resume Tracker Active
                    </span>
                    @endif
                </div>
            </div>

            <div class="flex items-center space-x-2">
                @if(Auth::check() && Auth::user()->isAdmin())
                <a href="{{ route('school.bulk.index') }}" 
                   class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 text-xs font-bold transition">
                    📂 Bulk Ingestion Hub
                </a>
                @endif
            </div>
        </div>

        <!-- Slim Progress Bar -->
        <div class="w-full bg-slate-100 dark:bg-slate-800 h-1.5 rounded-full overflow-hidden mt-3">
            <div class="bg-indigo-600 h-1.5 transition-all duration-300" style="width: {{ $progressPercent }}%;"></div>
        </div>

        <!-- Step Nav Pills (6 Steps) -->
        <div class="grid grid-cols-2 sm:grid-cols-6 gap-1.5 mt-3 pt-3 border-t border-slate-100 dark:border-slate-800 text-xs font-bold">
            <a href="{{ route('school.onboarding.index', ['step' => 1]) }}" 
               class="px-2 py-1.5 rounded-lg text-center transition flex items-center justify-center space-x-1 {{ $currentStep === 1 ? 'bg-indigo-600 text-white' : ($checklist['step1_database'] ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400') }}">
                <span>{{ $checklist['step1_database'] ? '✓' : '1' }}</span>
                <span>Database</span>
            </a>

            <a href="{{ route('school.onboarding.index', ['step' => 2]) }}" 
               class="px-2 py-1.5 rounded-lg text-center transition flex items-center justify-center space-x-1 {{ $currentStep === 2 ? 'bg-indigo-600 text-white' : ($checklist['step2_profile'] ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400') }}">
                <span>{{ $checklist['step2_profile'] ? '✓' : '2' }}</span>
                <span>Profile</span>
            </a>

            <a href="{{ route('school.onboarding.index', ['step' => 3]) }}" 
               class="px-2 py-1.5 rounded-lg text-center transition flex items-center justify-center space-x-1 {{ $currentStep === 3 ? 'bg-indigo-600 text-white' : ($checklist['step3_classes'] ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400') }}">
                <span>{{ $checklist['step3_classes'] ? '✓' : '3' }}</span>
                <span>Curriculum</span>
            </a>

            <a href="{{ route('school.onboarding.index', ['step' => 4]) }}" 
               class="px-2 py-1.5 rounded-lg text-center transition flex items-center justify-center space-x-1 {{ $currentStep === 4 ? 'bg-indigo-600 text-white' : ($checklist['step4_faculty'] ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400') }}">
                <span>{{ $checklist['step4_faculty'] ? '✓' : '4' }}</span>
                <span>Faculty</span>
            </a>

            <a href="{{ route('school.onboarding.index', ['step' => 5]) }}" 
               class="px-2 py-1.5 rounded-lg text-center transition flex items-center justify-center space-x-1 {{ $currentStep === 5 ? 'bg-indigo-600 text-white' : ($checklist['step5_students'] ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400') }}">
                <span>{{ $checklist['step5_students'] ? '✓' : '5' }}</span>
                <span>Admissions</span>
            </a>

            <a href="{{ route('school.onboarding.index', ['step' => 6]) }}" 
               class="px-2 py-1.5 rounded-lg text-center transition flex items-center justify-center space-x-1 {{ $currentStep === 6 ? 'bg-indigo-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400' }}">
                <span>6</span>
                <span>Launch</span>
            </a>
        </div>
    </div>

    <!-- STEP 1: DATABASE SETUP & MASTER ADMIN INITIALIZATION -->
    @if($currentStep === 1)
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-4 shadow-xs space-y-4">
        <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
            <div>
                <h2 class="text-sm font-bold text-slate-900 dark:text-white">Step 1: Database Setup &amp; Master Administrator</h2>
                <span class="text-[11px] text-slate-400">Initialize the core database and establish the Super Administrator credentials</span>
            </div>
            <span class="text-xs font-bold {{ $dbStatus['connected'] ? 'text-emerald-600' : 'text-rose-600' }}">
                {{ $dbStatus['connected'] ? '🟢 Connected' : '🔴 Disconnected' }}
            </span>
        </div>

        <!-- Connection Footprint Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
            <div class="p-3 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700">
                <span class="text-[10px] text-slate-400 font-bold uppercase block">Database Driver</span>
                <span class="text-sm font-black text-slate-800 dark:text-slate-200 uppercase font-mono">{{ $dbStatus['driver'] }}</span>
            </div>
            <div class="p-3 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700">
                <span class="text-[10px] text-slate-400 font-bold uppercase block">Active Tables</span>
                <span class="text-sm font-black text-slate-800 dark:text-slate-200 font-mono">{{ $dbStatus['table_count'] }} Tables</span>
            </div>
            <div class="p-3 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700">
                <span class="text-[10px] text-slate-400 font-bold uppercase block">Current Records</span>
                <span class="text-sm font-black text-slate-800 dark:text-slate-200 font-mono">{{ $dbStatus['total_classes'] }} Classes &bull; {{ $dbStatus['total_students'] }} Students</span>
            </div>
            <div class="p-3 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700">
                <span class="text-[10px] text-slate-400 font-bold uppercase block">Database State</span>
                <span class="text-sm font-black {{ $dbStatus['is_clean'] ? 'text-emerald-600' : 'text-amber-500' }}">
                    {{ $dbStatus['is_clean'] ? 'Clean Slate' : 'Data Present' }}
                </span>
            </div>
        </div>

        @if($adminCount === 0)
        <!-- Case A: No Admin Exists Yet (Fresh Install Form) -->
        <div class="p-4 rounded-xl border border-indigo-200 dark:border-indigo-800 bg-indigo-50/40 dark:bg-indigo-950/20 space-y-3">
            <div class="flex items-center space-x-2">
                <span class="text-lg">👑</span>
                <h3 class="text-xs font-bold text-slate-900 dark:text-white">Create Master Super Administrator Account</h3>
            </div>
            <p class="text-[11px] text-slate-600 dark:text-slate-400">
                No administrative user is currently registered. Set up your Master Principal account to proceed with installation.
            </p>

            <form method="POST" action="{{ route('school.onboarding.create-admin') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-1">
                @csrf
                <div class="sm:col-span-3">
                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">One-time Installer Key *</label>
                    <input type="password" name="installer_key" required autocomplete="off" placeholder="Enter the server's SCHOOL_INSTALLER_KEY"
                           class="w-full px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-mono text-slate-900 dark:text-white">
                    <p class="text-[10px] text-slate-500 mt-1">The deployer must configure a unique random key of at least 32 characters on the server before first-admin setup.</p>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">Full Administrator Name *</label>
                    <input type="text" name="admin_name" required value="{{ old('admin_name') }}" placeholder="e.g. Dr. Rajeshwar Sharma"
                           class="w-full px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-bold text-slate-900 dark:text-white">
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">Administrator Email *</label>
                    <input type="email" name="admin_email" required value="{{ old('admin_email') }}" placeholder="e.g. principal@yourschool.edu"
                           class="w-full px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">Administrator Password *</label>
                    <input type="password" name="admin_password" required minlength="12" autocomplete="new-password" placeholder="At least 12 characters"
                           class="w-full px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-mono text-slate-900 dark:text-white">
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">Confirm Administrator Password *</label>
                    <input type="password" name="admin_password_confirmation" required minlength="12" autocomplete="new-password" placeholder="Re-enter password"
                           class="w-full px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-mono text-slate-900 dark:text-white">
                </div>

                <div class="sm:col-span-3 flex justify-end pt-2">
                    <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg text-xs font-bold shadow-xs transition flex items-center space-x-1.5">
                        <span>Initialize Master Administrator &amp; Proceed &rarr;</span>
                    </button>
                </div>
            </form>
        </div>
        @else
        <!-- Case B: Admin Exists (Clean Slate / Reset or Continue) -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
            
            <!-- Wipe / Fresh Slate Action -->
            <div class="p-3.5 rounded-xl border border-rose-200 dark:border-rose-900 bg-rose-50/40 dark:bg-rose-950/20 space-y-2.5">
                <div class="flex items-center space-x-2">
                    <span class="text-lg">🧹</span>
                    <h3 class="text-xs font-bold text-rose-800 dark:text-rose-300">Wipe Everything &amp; Start Fresh Clean Trial</h3>
                </div>
                <p class="text-[11px] text-rose-700 dark:text-rose-400 leading-normal">
                    Deletes all classes, subjects, students, teachers, fees, and attendance logs so you can test the onboarding wizard from absolute scratch.
                </p>
                <form method="POST" action="{{ route('school.onboarding.reset-database') }}" onsubmit="return confirm('CAUTION: Are you sure you want to wipe records and reset the system?');" class="space-y-2">
                    @csrf
                    <label class="flex items-center space-x-2 text-[11px] text-rose-800 dark:text-rose-300 cursor-pointer select-none">
                        <input type="checkbox" name="remove_admin" value="1" class="rounded border-rose-300 text-rose-600 focus:ring-rose-500">
                        <span>Also remove current administrator (reset to 0 users)</span>
                    </label>
                    <button type="submit" class="w-full px-4 py-2 bg-rose-600 hover:bg-rose-500 text-white rounded-lg text-xs font-bold shadow-xs transition">
                        🧹 Wipe Data &amp; Reset to Clean Slate
                    </button>
                </form>
            </div>

            <!-- Keep & Proceed Action -->
            <div class="p-3.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 space-y-2.5">
                <div class="flex items-center space-x-2">
                    <span class="text-lg">➡️</span>
                    <h3 class="text-xs font-bold text-slate-900 dark:text-white">Keep Current Database &amp; Continue</h3>
                </div>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-normal">
                    Retain existing records and proceed directly to Step 2 to review or update your institutional profile and school branding settings.
                </p>
                <div class="pt-3">
                    <a href="{{ route('school.onboarding.index', ['step' => 2]) }}" class="w-full block text-center px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg text-xs font-bold shadow-xs transition">
                        Proceed to Step 2: School Profile &rarr;
                    </a>
                </div>
            </div>

        </div>
        @endif
    </div>
    @endif

    <!-- STEP 2: INSTITUTION PROFILE -->
    @if($currentStep === 2)
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-4 shadow-xs space-y-4">
        <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
            <div>
                <h2 class="text-sm font-bold text-slate-900 dark:text-white">Step 2: School Profile &amp; Affiliation</h2>
                <span class="text-[11px] text-slate-400">Configure institutional identity, academic year session, and contact info</span>
            </div>
            <span class="text-xs font-bold text-indigo-600 dark:text-indigo-400">Identity Setup</span>
        </div>

        <form method="POST" action="{{ route('school.onboarding.save-profile') }}" class="space-y-3.5 text-xs">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="sm:col-span-2">
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Official School Name *</label>
                    <input type="text" name="institute_name" required value="{{ old('institute_name', $settings['institute_name'] ?? '') }}"
                           placeholder="e.g. Greenwood Public School"
                           class="w-full px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-bold text-slate-900 dark:text-white">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Short Acronym (2-5 Letters)</label>
                    <input type="text" name="school_short_name" maxlength="10" value="{{ old('school_short_name', $settings['school_short_name'] ?? '') }}"
                           placeholder="e.g. MPS"
                           class="w-full px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-mono font-bold text-slate-900 dark:text-white uppercase">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Campus / Branch Name</label>
                    <input type="text" name="campus_name" value="{{ old('campus_name', $settings['campus_name'] ?? '') }}"
                           class="w-full px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">School Tagline / Motto</label>
                    <input type="text" name="motto" value="{{ old('motto', $settings['motto'] ?? '') }}"
                           class="w-full px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Board / Affiliation No.</label>
                    <input type="text" name="affiliation_number" value="{{ old('affiliation_number', $settings['affiliation_number'] ?? '') }}"
                           placeholder="e.g. CBSE/AFF/8912"
                           class="w-full px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Academic Session *</label>
                    @php
                        $activeSession = old('academic_session', $settings['academic_session'] ?? '2026-2027');
                        $sessionOptions = ['2024-2025', '2025-2026', '2026-2027', '2027-2028', '2028-2029'];
                        if (!in_array($activeSession, $sessionOptions)) {
                            array_unshift($sessionOptions, $activeSession);
                        }
                    @endphp
                    <select name="academic_session" required
                            class="w-full px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-bold text-slate-900 dark:text-white">
                        @foreach($sessionOptions as $opt)
                            <option value="{{ $opt }}" {{ $opt === $activeSession ? 'selected' : '' }}>{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Currency Symbol</label>
                    <input type="text" name="currency_symbol" value="{{ old('currency_symbol', $settings['currency_symbol'] ?? '₹') }}"
                           class="w-full px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-mono font-bold text-slate-900 dark:text-white">
                </div>
            </div>

            <!-- PrimeOS Engine Mark -->
            <div class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-between text-xs">
                <span class="font-bold text-slate-700 dark:text-slate-300">Platform Infrastructure:</span>
                <span class="font-black text-emerald-600 dark:text-emerald-400">⚡ Powered by PrimeOS</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Principal / Headmaster Name</label>
                    <input type="text" name="principal_name" value="{{ old('principal_name', $settings['principal_name'] ?? '') }}"
                           placeholder="Dr. S. K. Mukherjee"
                           class="w-full px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Official Contact Phone</label>
                    <input type="text" name="institution_phone" value="{{ old('institution_phone', $settings['institution_phone'] ?? '') }}"
                           class="w-full px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Official Contact Email</label>
                    <input type="email" name="institution_email" value="{{ old('institution_email', $settings['institution_email'] ?? '') }}"
                           class="w-full px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                </div>
            </div>

            <div>
                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Campus Postal Address</label>
                <input type="text" name="institution_address" value="{{ old('institution_address', $settings['institution_address'] ?? '') }}"
                       class="w-full px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
            </div>

            <div class="pt-2 flex justify-between">
                <a href="{{ route('school.onboarding.index', ['step' => 1]) }}" class="px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 text-slate-600 dark:text-slate-400 text-xs font-bold">
                    &larr; Back to Step 1
                </a>
                <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg text-xs font-bold shadow-xs transition flex items-center space-x-1.5">
                    <span>Save Profile &amp; Proceed to Step 3 &rarr;</span>
                </button>
            </div>
        </form>
    </div>
    @endif

    <!-- STEP 3: CURRICULUM SETUP -->
    @if($currentStep === 3)
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-4 shadow-xs space-y-4">
        <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
            <div>
                <h2 class="text-sm font-bold text-slate-900 dark:text-white">Step 3: Academic Curriculum Setup</h2>
                <span class="text-[11px] text-slate-400">Initialize classes (Grade 1 to 12) and their assigned subject curricula</span>
            </div>
            <span class="text-xs font-bold text-indigo-600 dark:text-indigo-400">Current: {{ $stats['total_classes'] }} classes &bull; {{ $stats['total_subjects'] }} subjects</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            
            <!-- Option A: 1-Click Standard K-12 Presets -->
            <div class="bg-indigo-50/50 dark:bg-indigo-950/30 p-3.5 rounded-xl border border-indigo-200 dark:border-indigo-800 space-y-3">
                <div class="flex items-center space-x-2">
                    <span class="w-7 h-7 rounded-lg bg-indigo-600 text-white flex items-center justify-center font-bold text-xs">⚡</span>
                    <div>
                        <h3 class="text-xs font-bold text-slate-900 dark:text-white">Option A: 1-Click Standard K-12 Presets</h3>
                        <span class="text-[10px] text-slate-500 dark:text-slate-400">Fastest setup: Instantly generates Grade 1 to 12 + standard core subjects</span>
                    </div>
                </div>

                <div class="p-2.5 rounded-lg bg-white dark:bg-slate-900 border border-indigo-100 dark:border-indigo-900 text-[11px] text-slate-600 dark:text-slate-400 space-y-1">
                    <div>• <strong>Primary (Grades 1-5):</strong> English, Math, EVS, Second Language, Computer, Arts.</div>
                    <div>• <strong>Secondary (Grades 6-12):</strong> English, Math, Science, Social Studies, Lang II, IT/AI.</div>
                </div>

                <form method="POST" action="{{ route('school.onboarding.k12-presets') }}" class="pt-1">
                    @csrf
                    <input type="hidden" name="board" value="CBSE">
                    <button type="submit" class="w-full px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg text-xs font-bold shadow-xs transition flex items-center justify-center space-x-1.5">
                        <span>⚡ Apply Standard K-12 Presets Now</span>
                    </button>
                </form>
            </div>

            <!-- Option B: Custom Bulk CSV Ingestion -->
            <div class="bg-slate-50 dark:bg-slate-800/60 p-3.5 rounded-xl border border-slate-200 dark:border-slate-700 space-y-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <span class="w-7 h-7 rounded-lg bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 flex items-center justify-center font-bold text-xs">📂</span>
                        <div>
                            <h3 class="text-xs font-bold text-slate-900 dark:text-white">Option B: Custom CSV Upload</h3>
                            <span class="text-[10px] text-slate-500 dark:text-slate-400">Upload your school's unique classes and custom subjects</span>
                        </div>
                    </div>
                    <a href="{{ route('school.bulk.sample', 'classes_subjects') }}" download="sample_classes_subjects.csv" data-no-spa="true" class="text-[10px] font-bold text-indigo-600 hover:underline">⬇️ Sample CSV</a>
                </div>

                <form method="POST" action="{{ route('school.bulk.classes-subjects.import') }}" enctype="multipart/form-data" class="space-y-2">
                    @csrf
                    <input type="hidden" name="source" value="onboarding">
                    <input type="hidden" name="onboarding_step" value="3">
                    <input type="file" name="csv_file" accept=".csv,text/csv" required
                           class="w-full text-xs text-slate-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-slate-200 dark:file:bg-slate-700 file:text-slate-700 dark:file:text-slate-200">
                    <button type="submit" class="w-full px-3 py-1.5 bg-slate-800 hover:bg-slate-700 dark:bg-slate-700 dark:hover:bg-slate-600 text-white rounded-lg text-xs font-bold shadow-xs transition">
                        Upload Custom Classes CSV
                    </button>
                </form>
            </div>

        </div>

        <div class="pt-2 flex justify-between border-t border-slate-100 dark:border-slate-800">
            <a href="{{ route('school.onboarding.index', ['step' => 2]) }}" class="px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 text-slate-600 dark:text-slate-400 text-xs font-bold">
                &larr; Back to Step 2
            </a>
            <a href="{{ route('school.onboarding.index', ['step' => 4]) }}" class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg text-xs font-bold transition">
                Proceed to Step 4: Faculty Roster &rarr;
            </a>
        </div>
    </div>
    @endif

    <!-- STEP 4: FACULTY SETUP -->
    @if($currentStep === 4)
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-4 shadow-xs space-y-4">
        <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
            <div>
                <h2 class="text-sm font-bold text-slate-900 dark:text-white">Step 4: Faculty &amp; Staff Ingestion</h2>
                <span class="text-[11px] text-slate-400">Import teachers, department heads, and campus administrative personnel</span>
            </div>
            <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400">Current: {{ $stats['total_teachers'] }} Teachers Active</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Bulk Upload Box -->
            <div class="bg-white dark:bg-slate-900 p-3.5 rounded-xl border border-slate-200 dark:border-slate-800 space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-bold text-slate-900 dark:text-white">Upload Faculty CSV</h3>
                    <a href="{{ route('school.bulk.sample', 'staff') }}" download="sample_staff_import_template.csv" data-no-spa="true" class="text-[10px] font-bold text-indigo-600 hover:underline">⬇️ Download Template</a>
                </div>

                <form method="POST" action="{{ route('school.bulk.staff.import') }}" enctype="multipart/form-data" class="space-y-3">
                    @csrf
                    <input type="hidden" name="source" value="onboarding">
                    <input type="hidden" name="onboarding_step" value="4">
                    <div>
                        <input type="file" name="csv_file" accept=".csv,text/csv" required
                               class="w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                    </div>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg text-xs font-bold shadow-xs transition">
                        Upload Faculty Roster CSV
                    </button>
                </form>
            </div>

            <!-- Manual or View Directory -->
            <div class="bg-slate-50 dark:bg-slate-800/60 p-3.5 rounded-xl border border-slate-200 dark:border-slate-700 space-y-2.5 text-xs">
                <h3 class="font-bold text-slate-900 dark:text-white">Manual Add or Review</h3>
                <p class="text-slate-500 dark:text-slate-400 text-[11px]">
                    You can also add teachers individually or manage existing staff profiles in the Staff Directory module.
                </p>
                <div class="pt-1 flex items-center justify-between">
                    <a href="{{ route('staff.index') }}" target="_blank" class="text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:underline">
                        Open Staff Directory &rarr;
                    </a>
                    <form method="POST" action="{{ route('school.onboarding.skip-step', 4) }}">
                        @csrf
                        <button type="submit" class="text-xs font-bold text-slate-500 hover:text-slate-800 dark:hover:text-slate-200">
                            Skip for now &rarr;
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="pt-2 flex justify-between border-t border-slate-100 dark:border-slate-800">
            <a href="{{ route('school.onboarding.index', ['step' => 3]) }}" class="px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 text-slate-600 dark:text-slate-400 text-xs font-bold">
                &larr; Back to Step 3
            </a>
            <a href="{{ route('school.onboarding.index', ['step' => 5]) }}" class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg text-xs font-bold transition">
                Proceed to Step 5: Student Admissions &rarr;
            </a>
        </div>
    </div>
    @endif

    <!-- STEP 5: STUDENT ADMISSIONS -->
    @if($currentStep === 5)
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-4 shadow-xs space-y-4">
        <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
            <div>
                <h2 class="text-sm font-bold text-slate-900 dark:text-white">Step 5: Student Admissions Roster</h2>
                <span class="text-[11px] text-slate-400">Import students with roll numbers and class assignments</span>
            </div>
            <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400">Current: {{ $stats['total_students'] }} Students Admitted</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Bulk Upload Box -->
            <div class="bg-white dark:bg-slate-900 p-3.5 rounded-xl border border-slate-200 dark:border-slate-800 space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-bold text-slate-900 dark:text-white">Upload Student Roster CSV</h3>
                    <a href="{{ route('school.bulk.sample', 'students') }}" download="sample_students_import_template.csv" data-no-spa="true" class="text-[10px] font-bold text-emerald-600 hover:underline">⬇️ Download Template</a>
                </div>

                <form method="POST" action="{{ route('school.bulk.students.import') }}" enctype="multipart/form-data" class="space-y-3">
                    @csrf
                    <input type="hidden" name="source" value="onboarding">
                    <input type="hidden" name="onboarding_step" value="5">
                    <div>
                        <input type="file" name="csv_file" accept=".csv,text/csv" required
                               class="w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
                    </div>
                    <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-bold shadow-xs transition">
                        Upload Student Admissions CSV
                    </button>
                </form>
            </div>

            <!-- Manual Admission -->
            <div class="bg-slate-50 dark:bg-slate-800/60 p-3.5 rounded-xl border border-slate-200 dark:border-slate-700 space-y-2.5 text-xs">
                <h3 class="font-bold text-slate-900 dark:text-white">Formal Single Admission</h3>
                <p class="text-slate-500 dark:text-slate-400 text-[11px]">
                    To admit students individually with complete guardian documentation and health data, use the formal Admission Form.
                </p>
                <div class="pt-1 flex items-center justify-between">
                    <a href="{{ route('school.students.create') }}" target="_blank" class="text-xs font-bold text-emerald-600 dark:text-emerald-400 hover:underline">
                        Open Formal Admission Form &rarr;
                    </a>
                    <form method="POST" action="{{ route('school.onboarding.skip-step', 5) }}">
                        @csrf
                        <button type="submit" class="text-xs font-bold text-slate-500 hover:text-slate-800 dark:hover:text-slate-200">
                            Skip for now &rarr;
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="pt-2 flex justify-between border-t border-slate-100 dark:border-slate-800">
            <a href="{{ route('school.onboarding.index', ['step' => 4]) }}" class="px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 text-slate-600 dark:text-slate-400 text-xs font-bold">
                &larr; Back to Step 4
            </a>
            <a href="{{ route('school.onboarding.index', ['step' => 6]) }}" class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg text-xs font-bold transition">
                Proceed to Step 6: Final Review &amp; Launch &rarr;
            </a>
        </div>
    </div>
    @endif

    <!-- STEP 6: FINAL LAUNCH CHECKLIST -->
    @if($currentStep === 6)
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-4 shadow-xs space-y-4">
        <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
            <div>
                <h2 class="text-sm font-bold text-slate-900 dark:text-white">Step 6: System Ready &amp; Launch Checklist</h2>
                <span class="text-[11px] text-slate-400">Institutional setup status overview before going live</span>
            </div>
            <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400">Campus Readiness</span>
        </div>

        <!-- Verification Checklist Table -->
        <div class="divide-y divide-slate-100 dark:divide-slate-800 text-xs">
            <div class="py-2.5 flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <span class="{{ $checklist['step1_database'] ? 'text-emerald-600 font-bold' : 'text-slate-400' }}">
                        {{ $checklist['step1_database'] ? '✓' : '○' }}
                    </span>
                    <div>
                        <strong class="text-slate-900 dark:text-white">Database Environment &amp; Administrator</strong>
                        <div class="text-[11px] text-slate-400">{{ strtoupper($dbStatus['driver']) }} &bull; {{ $dbStatus['table_count'] }} Tables Active</div>
                    </div>
                </div>
                <a href="{{ route('school.onboarding.index', ['step' => 1]) }}" class="text-[11px] font-bold text-indigo-600 hover:underline">View</a>
            </div>

            <div class="py-2.5 flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <span class="{{ $checklist['step2_profile'] ? 'text-emerald-600 font-bold' : 'text-slate-400' }}">
                        {{ $checklist['step2_profile'] ? '✓' : '○' }}
                    </span>
                    <div>
                        <strong class="text-slate-900 dark:text-white">Institutional Profile &amp; Session</strong>
                        <div class="text-[11px] text-slate-400">{{ $settings['institute_name'] ?? 'Not set' }} &bull; Session {{ $settings['academic_session'] ?? '2026-27' }}</div>
                    </div>
                </div>
                <a href="{{ route('school.onboarding.index', ['step' => 2]) }}" class="text-[11px] font-bold text-indigo-600 hover:underline">Edit</a>
            </div>

            <div class="py-2.5 flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <span class="{{ $checklist['step3_classes'] ? 'text-emerald-600 font-bold' : 'text-slate-400' }}">
                        {{ $checklist['step3_classes'] ? '✓' : '○' }}
                    </span>
                    <div>
                        <strong class="text-slate-900 dark:text-white">Curriculum &amp; Class Hierarchy</strong>
                        <div class="text-[11px] text-slate-400">{{ $stats['total_classes'] }} Academic Classes &bull; {{ $stats['total_subjects'] }} Subjects Active</div>
                    </div>
                </div>
                <a href="{{ route('school.onboarding.index', ['step' => 3]) }}" class="text-[11px] font-bold text-indigo-600 hover:underline">Edit</a>
            </div>

            <div class="py-2.5 flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <span class="{{ $checklist['step4_faculty'] ? 'text-emerald-600 font-bold' : 'text-slate-400' }}">
                        {{ $checklist['step4_faculty'] ? '✓' : '○' }}
                    </span>
                    <div>
                        <strong class="text-slate-900 dark:text-white">Faculty &amp; Teaching Staff</strong>
                        <div class="text-[11px] text-slate-400">{{ $stats['total_teachers'] }} Teaching Masters Roster Active</div>
                    </div>
                </div>
                <a href="{{ route('school.onboarding.index', ['step' => 4]) }}" class="text-[11px] font-bold text-indigo-600 hover:underline">Edit</a>
            </div>

            <div class="py-2.5 flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <span class="{{ $checklist['step5_students'] ? 'text-emerald-600 font-bold' : 'text-slate-400' }}">
                        {{ $checklist['step5_students'] ? '✓' : '○' }}
                    </span>
                    <div>
                        <strong class="text-slate-900 dark:text-white">Student Admissions</strong>
                        <div class="text-[11px] text-slate-400">{{ $stats['total_students'] }} Enrolled Students Active</div>
                    </div>
                </div>
                <a href="{{ route('school.onboarding.index', ['step' => 5]) }}" class="text-[11px] font-bold text-indigo-600 hover:underline">Edit</a>
            </div>
        </div>

        <form method="POST" action="{{ route('school.onboarding.finish') }}" class="pt-3 border-t border-slate-100 dark:border-slate-800 flex justify-end">
            @csrf
            <button type="submit" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-black shadow-xs transition flex items-center space-x-2">
                <span>🎉 Complete Onboarding &amp; Open Live System &rarr;</span>
            </button>
        </form>
    </div>
    @endif

</div>
@endsection
