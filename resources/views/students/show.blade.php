@extends('layouts.app')

@section('title', $student->name . ' — Student Dossier' . ' — ' . ($school['name'] ?? 'PrimeOS Academy'))

@section('content')
<div x-data="{ openDeleteModal: {{ $errors->hasAny(['confirmation_text', 'admin_password', 'withdrawal_reason', 'effective_date', 'student']) ? 'true' : 'false' }} }" class="max-w-6xl mx-auto space-y-4">

    <!-- Top Action Breadcrumb -->
    <div class="flex items-center justify-between pb-2 border-b border-slate-200 dark:border-slate-800 text-xs">
        <a href="{{ route('students.index') }}" class="font-bold text-slate-500 hover:text-emerald-600 dark:text-slate-400 dark:hover:text-emerald-400 flex items-center space-x-1.5 transition">
            <span>&larr;</span>
            <span>Back to Student Roster</span>
        </a>

        <!-- Action Buttons: Edit & Withdraw -->
        <div class="flex items-center space-x-2">
            <a href="{{ route('idcard.show', $student->id) }}" class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold transition flex items-center space-x-1.5 border border-slate-200 dark:border-slate-700">
                <span>🪪</span>
                <span>Virtual ID</span>
            </a>
            <a href="{{ route('students.edit', $student->id) }}" class="px-3 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold transition flex items-center space-x-1.5 shadow-xs">
                <span>✏️</span>
                <span>Edit Dossier</span>
            </a>
            @if(Auth::user()->isAdmin())
            <button type="button" @click="openDeleteModal = true" class="px-3 py-1.5 rounded-xl bg-rose-50 dark:bg-rose-950/60 hover:bg-rose-100 dark:hover:bg-rose-900/60 text-rose-700 dark:text-rose-300 border border-rose-300 dark:border-rose-800 text-xs font-bold transition flex items-center space-x-1">
                <span>🗑️</span>
                <span>Withdraw</span>
            </button>
            @endif
        </div>
    </div>

    <!-- Student Master Header Banner -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center space-x-4">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-emerald-600 to-teal-500 text-white font-black text-xl flex items-center justify-center shrink-0 shadow-md">
                {{ strtoupper(substr($student->name, 0, 2)) }}
            </div>
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-xl font-black text-slate-900 dark:text-white">{{ $student->name }}</h1>
                    <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                        {{ $student->student_id_number ?: 'Not assigned' }}
                    </span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $student->is_approved ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300' }}">
                        {{ $activeEnrollment ? 'Active' : (($lastEnrollment?->status === 'withdrawn') ? 'Withdrawn' : ($student->is_approved ? 'Approved — not enrolled' : 'Approval Pending')) }}
                    </span>
                </div>
                <div class="text-xs text-slate-500 dark:text-slate-400 mt-1 flex flex-wrap items-center gap-2">
                    <span>Class: <strong class="text-slate-700 dark:text-slate-300">{{ $activeEnrollment?->academicClass?->name ?? $student->currentClass?->name ?? 'Unassigned' }}</strong></span>
                    <span>&bull;</span>
                    <span>Section: <strong class="text-slate-700 dark:text-slate-300">Section {{ $activeEnrollment?->academicClass?->section ?? $student->currentClass?->section ?? 'A' }}</strong></span>
                    <span>&bull;</span>
                    <span>Roll: <strong class="font-mono text-slate-700 dark:text-slate-300">{{ $activeEnrollment?->roll_number ?? 'Not assigned' }}</strong></span>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2 text-xs">
            <span class="px-3 py-1.5 rounded-xl bg-purple-50 dark:bg-purple-950/60 border border-purple-200 dark:border-purple-800 text-purple-800 dark:text-purple-300 font-bold">
                {{ $student->academic_tier ?: 'Not assigned' }}
            </span>
            <span class="px-3 py-1.5 rounded-xl bg-amber-50 dark:bg-amber-950/60 border border-amber-200 dark:border-amber-800 text-amber-800 dark:text-amber-300 font-bold font-mono">
                ⚡ {{ $student->xp_points ?? 0 }} XP
            </span>
        </div>
    </div>

    <!-- Deep Analysis KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
        <!-- Attendance Metric -->
        <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs">
            <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-500 block">Attendance Record</span>
            <div class="text-2xl font-black font-mono text-emerald-600 dark:text-emerald-400 mt-1">
                {{ $attendanceRate !== null ? number_format($attendanceRate, 1) . '%' : 'No records' }}
            </div>
            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                {{ $presentAttendanceCount }} Present &bull; {{ $absentAttendanceCount }} Absent &bull; {{ $excusedAttendanceCount }} Excused
            </div>
        </div>

        <!-- Academic Performance Metric -->
        <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs">
            <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-500 block">Average Quiz Score</span>
            <div class="text-2xl font-black font-mono text-indigo-600 dark:text-indigo-400 mt-1">
                {{ $averageQuizScore !== null ? $averageQuizScore . ' pts' : 'No attempts' }}
            </div>
            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                {{ $quizAttempts->count() }} Recorded Attempts
            </div>
        </div>

        <!-- Invoiced Metric -->
        <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs">
            <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-500 block">Settled Fees</span>
            <div class="text-2xl font-black font-mono text-slate-900 dark:text-white mt-1">
                ₹{{ number_format($totalPaid, 2) }}
            </div>
            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                Total Invoiced: ₹{{ number_format($totalInvoiced, 2) }}
            </div>
        </div>

        <!-- Outstanding Arrears Metric -->
        <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs">
            <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-500 block">Outstanding Arrears</span>
            <div class="text-2xl font-black font-mono {{ $totalOutstanding > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400' }} mt-1">
                ₹{{ number_format($totalOutstanding, 2) }}
            </div>
            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                {{ $invoices->count() }} Invoices Generated
            </div>
        </div>
    </div>

    <!-- Student Detail Sections (2 Column) -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Contact & Guardian Information -->
        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs space-y-3">
            <h3 class="text-xs font-black uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center space-x-1.5">
                <span>📞</span>
                <span>Contact &amp; Guardian Record</span>
            </h3>
            <div class="divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                <div class="py-2 flex justify-between">
                    <span class="text-slate-500">Email:</span>
                    <strong class="text-slate-800 dark:text-slate-200">{{ $student->email }}</strong>
                </div>
                <div class="py-2 flex justify-between">
                    <span class="text-slate-500">Phone:</span>
                    <strong class="text-slate-800 dark:text-slate-200 font-mono">{{ $student->phone ?? 'Not Registered' }}</strong>
                </div>
                <div class="py-2 flex justify-between">
                    <span class="text-slate-500">Guardian Name:</span>
                    <strong class="text-slate-800 dark:text-slate-200">{{ $student->guardian_name ?? 'Not Listed' }}</strong>
                </div>
                <div class="py-2 flex justify-between">
                    <span class="text-slate-500">Guardian Phone:</span>
                    <strong class="text-slate-800 dark:text-slate-200 font-mono">{{ $student->guardian_phone ?? 'Not Listed' }}</strong>
                </div>
                <div class="py-2 flex justify-between">
                    <span class="text-slate-500">Blood Group:</span>
                    <strong class="text-slate-800 dark:text-slate-200 font-mono">{{ $student->blood_group ?: 'Not recorded' }}</strong>
                </div>
                <div class="py-2 flex justify-between">
                    <span class="text-slate-500">Residential Address:</span>
                    <span class="text-slate-700 dark:text-slate-300 max-w-xs text-right">{{ $student->address ?: 'Not recorded' }}</span>
                </div>
            </div>
        </div>

        <!-- Academic & Batch Enrollment Details -->
        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs space-y-3">
            <h3 class="text-xs font-black uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center space-x-1.5">
                <span>🏫</span>
                <span>Enrollment &amp; Program Details</span>
            </h3>
            <div class="divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                <div class="py-2 flex justify-between">
                    <span class="text-slate-500">Institutional ID:</span>
                    <strong class="font-mono text-emerald-600 dark:text-emerald-400">{{ $student->student_id_number ?: 'Not assigned' }}</strong>
                </div>
                <div class="py-2 flex justify-between">
                    <span class="text-slate-500">Academic Class:</span>
                    <strong class="text-slate-800 dark:text-slate-200">{{ $activeEnrollment?->academicClass?->name ?? 'Unassigned' }}</strong>
                </div>
                <div class="py-2 flex justify-between">
                    <span class="text-slate-500">Section:</span>
                    <strong class="text-slate-800 dark:text-slate-200">Section {{ $activeEnrollment?->academicClass?->section ?? $student->currentClass?->section ?? 'A' }}</strong>
                </div>
                <div class="py-2 flex justify-between">
                    <span class="text-slate-500">Stream:</span>
                    <strong class="text-slate-800 dark:text-slate-200">{{ $activeEnrollment?->academicClass?->stream ?? $student->currentClass?->stream ?? 'General' }}</strong>
                </div>
                <div class="py-2 flex justify-between">
                    <span class="text-slate-500">Room Number:</span>
                    <strong class="text-slate-800 dark:text-slate-200 font-mono">{{ $activeEnrollment?->academicClass?->room_number ?? 'Room 101' }}</strong>
                </div>
                <div class="py-2 flex justify-between">
                    <span class="text-slate-500">Registration Date:</span>
                    <strong class="text-slate-800 dark:text-slate-200">{{ $student->created_at->format('M d, Y') }}</strong>
                </div>
            </div>
        </div>
    </div>

    <!-- DANGER ZONE: PERMANENT RECORD DELETION MODAL WITH CAUTION & CAPTCHA/PASSWORD GUARD -->
    @if(Auth::user()->isAdmin())
    <div x-show="openDeleteModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-xs" @click="openDeleteModal = false"></div>

        <div class="relative bg-white dark:bg-slate-900 rounded-3xl border border-rose-500/40 shadow-2xl max-w-lg w-full p-5 sm:p-6 z-10 space-y-4">
            <div class="flex items-center space-x-3 text-rose-600 dark:text-rose-400">
                <span class="text-2xl">⚠️</span>
                <div>
                    <h3 class="text-base font-black text-slate-900 dark:text-white">Withdraw Student</h3>
                    <p class="text-[11px] text-rose-600 dark:text-rose-400 font-semibold">Administrative offboarding — academic and financial history is retained</p>
                </div>
            </div>

            <div class="p-3.5 rounded-2xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900/60 text-xs text-rose-800 dark:text-rose-300 space-y-1.5 leading-relaxed">
                <p>You are about to withdraw <strong>{{ $student->name }} ({{ $student->student_id_number ?: 'Not assigned' }})</strong> from the institution.</p>
                <p class="text-[11px]">The student's active enrollment and transport service will be ended, account access revoked, and historical academic, attendance and financial records retained. Confirm this action carefully.</p>
            </div>

            <form method="POST" action="{{ route('students.destroy', $student->id) }}" class="space-y-3 text-xs">
                @error('student')<p role="alert" class="rounded-lg bg-rose-50 p-2 text-rose-700">{{ $message }}</p>@enderror
                @csrf
                @method('DELETE')

                <!-- 1. Type confirmation keyword -->
                <div>
                    <label class="block text-[10px] font-black uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1">
                        1. Type <span class="text-rose-600 font-mono">WITHDRAW</span> to confirm *
                    </label>
                    <input type="text" name="confirmation_text" required value="{{ old('confirmation_text') }}" placeholder="Type WITHDRAW in capital letters" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-mono font-bold focus:ring-1 focus:ring-rose-500">
                    @error('confirmation_text')<p class="mt-1 text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="withdrawal_reason" class="block text-[10px] font-black uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1">
                        Reason for withdrawal *
                    </label>
                    <textarea id="withdrawal_reason" name="withdrawal_reason" required minlength="10" maxlength="2000" rows="3" placeholder="Record the documented reason for withdrawal" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-1 focus:ring-rose-500">{{ old('withdrawal_reason') }}</textarea>
                    @error('withdrawal_reason')<p class="mt-1 text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="effective_date" class="block text-[10px] font-black uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1">
                        Effective date *
                    </label>
                    <input id="effective_date" type="date" name="effective_date" required max="{{ now()->toDateString() }}" value="{{ old('effective_date', now()->toDateString()) }}" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-1 focus:ring-rose-500">
                    @error('effective_date')<p class="mt-1 text-rose-600">{{ $message }}</p>@enderror
                </div>

                <!-- Administrator password confirmation -->
                <div>
                    <label for="admin_password" class="block text-[10px] font-black uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1">
                        Administrator password *
                    </label>
                    <input id="admin_password" type="password" name="admin_password" required autocomplete="current-password" placeholder="Enter your login password" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-1 focus:ring-rose-500">
                    @error('admin_password')<p class="mt-1 text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div class="pt-2 flex items-center justify-end space-x-2">
                    <button type="button" @click="openDeleteModal = false" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold transition">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-bold transition shadow-md shadow-rose-600/20">
                        Confirm Withdrawal
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

</div>
@endsection
