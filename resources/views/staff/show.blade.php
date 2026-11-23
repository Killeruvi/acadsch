@extends('layouts.app')

@section('title', $staff->name . ' — Faculty Dossier' . ' — ' . ($school['name'] ?? 'PrimeOS Academy'))

@section('content')
<div x-data="{ openDeleteModal: false }" class="max-w-6xl mx-auto space-y-4">

    <!-- Top Action Breadcrumb -->
    <div class="flex items-center justify-between pb-2 border-b border-slate-200 dark:border-slate-800 text-xs">
        <a href="{{ route('staff.index') }}" class="font-bold text-slate-500 hover:text-emerald-600 dark:text-slate-400 dark:hover:text-emerald-400 flex items-center space-x-1.5 transition">
            <span>&larr;</span>
            <span>Back to Staff Directory</span>
        </a>

        <div class="flex items-center space-x-2">
            @if(Auth::user()->isAdmin())
            <a href="{{ route('staff.employment.show', $staff->id) }}" class="px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold transition flex items-center space-x-1.5 shadow-xs">
                <span>📁</span><span>Employment & HR Records</span>
            </a>
            @endif
            <a href="{{ route('staff.edit', $staff->id) }}" class="px-3 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold transition flex items-center space-x-1.5 shadow-xs">
                <span>✏️</span>
                <span>Edit Staff Member</span>
            </a>
            @if(Auth::user()->isAdmin())
            <button type="button" onclick="document.getElementById('reset-staff-modal').classList.remove('hidden')"
                    class="px-3 py-1.5 rounded-xl bg-amber-50 hover:bg-amber-100 dark:bg-amber-950/60 dark:hover:bg-amber-900/60 text-amber-700 dark:text-amber-300 border border-amber-300 dark:border-amber-800 text-xs font-bold transition flex items-center space-x-1">
                <span>🔑</span>
                <span>Reset Password</span>
            </button>
            @endif
            @if(Auth::user()->isAdmin() && $staff->id !== Auth::id())
            <button type="button" @click="openDeleteModal = true" class="px-3 py-1.5 rounded-xl bg-rose-50 dark:bg-rose-950/60 hover:bg-rose-100 text-rose-700 dark:text-rose-300 border border-rose-300 dark:border-rose-800 text-xs font-bold transition flex items-center space-x-1">
                <span>🗑️</span>
                <span>Delete</span>
            </button>
            @endif
        </div>
    </div>

    @if($errors->has('account_reset'))
    <div class="p-3 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-xs text-rose-700 dark:text-rose-300" role="alert">
        {{ $errors->first('account_reset') }}
    </div>
    @endif

    @if(session('account_reset_result'))
    @php $res = session('account_reset_result'); @endphp
    <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800" role="status">
        <p class="text-xs font-bold text-emerald-800 dark:text-emerald-300">{{ $res['message'] ?? 'Password reset instructions were processed.' }}</p>
        @if(!empty($res['email']))
            <p class="text-xs text-emerald-700 dark:text-emerald-400 mt-1">Account email: {{ $res['email'] }}</p>
        @endif
    </div>
    @endif
    <!-- Staff Master Header Card -->
    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-5 sm:p-6 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center space-x-4">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-indigo-600 to-purple-600 text-white font-black text-xl flex items-center justify-center shrink-0 shadow-md">
                {{ strtoupper(substr($staff->name, 0, 2)) }}
            </div>
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-xl font-black text-slate-900 dark:text-white">{{ $staff->name }}</h1>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                        {{ ucfirst($staff->role) }}
                    </span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $staff->is_approved ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300' }}">
                        {{ $staff->is_approved ? '✓ Approved Faculty' : 'Approval Pending' }}
                    </span>
                </div>
                <div class="text-xs text-slate-500 dark:text-slate-400 mt-1 flex flex-wrap items-center gap-2">
                    <span>Email: <strong class="text-slate-700 dark:text-slate-300">{{ $staff->email }}</strong></span>
                    <span>&bull;</span>
                    <span>Phone: <strong class="font-mono text-slate-700 dark:text-slate-300">{{ $staff->phone ?? 'Not Listed' }}</strong></span>
                </div>
            </div>
        </div>

        <div class="text-xs">
            <span class="text-slate-400">Institutional Faculty Member since {{ $staff->created_at->format('M Y') }}</span>
        </div>
    </div>

    <!-- Assigned Courses & Timetable Slots -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Assigned Courses -->
        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs space-y-3">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
                <h3 class="text-xs font-black uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center space-x-1.5">
                    <span>📚</span>
                    <span>Instructional Courses ({{ $courses->count() }})</span>
                </h3>
            </div>

            @if($courses->isEmpty())
            <p class="text-xs text-slate-400 py-4 text-center">No instructional courses assigned yet.</p>
            @else
            <div class="divide-y divide-slate-100 dark:divide-slate-800">
                @foreach($courses as $c)
                <div class="py-2.5 flex items-center justify-between text-xs">
                    <div>
                        <span class="font-bold text-slate-900 dark:text-white block">{{ $c->title }}</span>
                        <span class="text-[10px] text-slate-400 font-mono">{{ $c->code }} &bull; {{ $c->credits }} Credits</span>
                    </div>
                    <a href="{{ route('courses.show', $c->id) }}" class="text-emerald-600 dark:text-emerald-400 font-bold hover:underline">
                        View &rarr;
                    </a>
                </div>
                @endforeach
            </div>
            @endif
        </div>

        <!-- Weekly Timetable Sessions -->
        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs space-y-3">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
                <h3 class="text-xs font-black uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center space-x-1.5">
                    <span>🗓️</span>
                    <span>Timetable Sessions ({{ $schedules->count() }})</span>
                </h3>
            </div>

            @if($schedules->isEmpty())
            <p class="text-xs text-slate-400 py-4 text-center">No weekly timetable sessions mapped.</p>
            @else
            <div class="divide-y divide-slate-100 dark:divide-slate-800">
                @foreach($schedules as $sch)
                <div class="py-2.5 flex items-center justify-between text-xs">
                    <div>
                        <span class="font-bold text-slate-900 dark:text-white block">{{ $sch->title }}</span>
                        <span class="text-[10px] text-slate-400">{{ $sch->day_of_week }} &bull; {{ substr($sch->start_time, 0, 5) }} - {{ substr($sch->end_time, 0, 5) }}</span>
                    </div>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                        {{ $sch->room ?? 'Lab' }}
                    </span>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>

    <!-- DANGER ZONE: STAFF DELETION MODAL WITH CAUTION & CAPTCHA/PASSWORD GUARD -->
    @if(Auth::user()->isAdmin() && $staff->id !== Auth::id())
    <div x-show="openDeleteModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-xs" @click="openDeleteModal = false"></div>

        <div class="relative bg-white dark:bg-slate-900 rounded-3xl border border-rose-500/40 shadow-2xl max-w-lg w-full p-5 sm:p-6 z-10 space-y-4">
            <div class="flex items-center space-x-3 text-rose-600 dark:text-rose-400">
                <span class="text-2xl">⚠️</span>
                <div>
                    <h3 class="text-base font-black text-slate-900 dark:text-white">Caution: Permanent Staff Account Deletion</h3>
                    <p class="text-[11px] text-rose-600 dark:text-rose-400 font-semibold">Irreversible Administrative Procedure</p>
                </div>
            </div>

            <div class="p-3.5 rounded-2xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900/60 text-xs text-rose-800 dark:text-rose-300 space-y-1.5 leading-relaxed">
                <p>You are about to permanently delete faculty account for <strong>{{ $staff->name }} ({{ ucfirst($staff->role) }})</strong>.</p>
                <p class="text-[11px]">This action terminates all authorization codes, course teaching assignments, and administrative privileges. Triple-factor verification is required.</p>
            </div>

            <form method="POST" action="{{ route('staff.destroy', $staff->id) }}" class="space-y-3 text-xs">
                @csrf
                @method('DELETE')

                <!-- 1. Type confirmation keyword -->
                <div>
                    <label class="block text-[10px] font-black uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1">
                        1. Type <span class="text-rose-600 font-mono">DELETE</span> to confirm *
                    </label>
                    <input type="text" name="confirmation_text" required placeholder="Type DELETE in capital letters" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-mono font-bold focus:ring-1 focus:ring-rose-500">
                </div>

                <!-- 2. Security Math Captcha -->
                <div>
                    <label class="block text-[10px] font-black uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1">
                        2. Security Captcha: What is <span class="font-mono text-emerald-600 font-black">{{ $captchaNum1 }} + {{ $captchaNum2 }}</span> ? *
                    </label>
                    <input type="hidden" name="captcha_expected" value="{{ $captchaExpected }}">
                    <input type="number" name="captcha_answer" required placeholder="Calculate sum" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-mono font-bold focus:ring-1 focus:ring-rose-500">
                </div>

                <!-- 3. Password Verification -->
                <div>
                    <label class="block text-[10px] font-black uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1">
                        3. Your Administrator Password *
                    </label>
                    <input type="password" name="admin_password" required placeholder="Enter your login password" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-1 focus:ring-rose-500">
                </div>

                <div class="pt-2 flex items-center justify-end space-x-2">
                    <button type="button" @click="openDeleteModal = false" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold transition">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-bold transition shadow-md shadow-rose-600/20">
                        Authorize Deletion 🗑️
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <!-- Admin Password Reset Modal -->
    @if(Auth::user()->isAdmin())
    <div id="reset-staff-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs hidden p-4">
        <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200 dark:border-slate-800 max-w-md w-full shadow-2xl">
            <div class="flex items-center justify-between mb-4 pb-2 border-b border-slate-100 dark:border-slate-800">
                <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <span>🔑</span> Reset Faculty Credentials
                </h3>
                <button onclick="document.getElementById('reset-staff-modal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 dark:hover:text-white">✕</button>
            </div>
            <form method="POST" action="{{ route('school.users.reset-account', $staff->id) }}" class="space-y-4">
                @csrf
                <p class="text-xs text-slate-600 dark:text-slate-300">
                    Send a single-use password reset link to <strong>{{ $staff->email ?: 'the staff account' }}</strong>. The current password will not be displayed or replaced unless the account holder completes the reset.
                </p>
                @if(empty($staff->email))
                    <p class="text-xs text-rose-600">This account has no email address. Add and verify an email address before requesting a reset link.</p>
                @endif
                <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" onclick="document.getElementById('reset-staff-modal').classList.add('hidden')"
                            class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-bold">
                        Cancel
                    </button>
                    <button type="submit" {{ empty($staff->email) ? 'disabled' : '' }} class="px-4 py-2 rounded-xl bg-amber-600 hover:bg-amber-500 disabled:opacity-50 disabled:cursor-not-allowed text-white text-xs font-bold shadow-xs">
                        Send Reset Link
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

</div>
@endsection
