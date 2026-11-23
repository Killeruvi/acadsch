@if($user->isAdmin()) ADMIN PORTAL @else {{ strtoupper($user->role) }} @endif
                </span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                {{ $school['motto'] ?? 'Aim, Ascend, Achieve • Where Passion Meets Precision' }} &bull; <span class="hidden sm:inline">Your potential has no fixed limits</span>
            </p>
        </div>

        <div class="flex items-center flex-wrap gap-2">
            @if($user->isAdmin())
            <a href="{{ route('appearance.index') }}" class="primary-btn" style="padding: 0.4rem 1rem; font-size: 0.75rem;">
                <i class="ti-palette text-xs"></i> <span>Themes</span>
            </a>
            @endif
            <a href="{{ route('my-batch') }}" class="primary-btn" style="padding: 0.4rem 1rem; font-size: 0.75rem;">
                <span>🎒 My Batch</span>
            </a>
            <a href="{{ route('schedules.index') }}" class="px-3 py-1.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 rounded-lg text-xs font-bold transition flex items-center space-x-1">
                <i class="ti-calendar text-xs"></i>
                <span>Timetable</span>
            </a>
            @if($user->isAdmin() || $user->isTeacher())
            <a href="{{ route('analytics.index') }}" class="px-3 py-1.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 rounded-lg text-xs font-bold transition flex items-center space-x-1">
                <i class="ti-bar-chart text-xs"></i>
                <span>Reports</span>
            </a>
            @endif
        </div>
    </div>

    @if(!empty($birthdayCelebration))
    <div class="p-4 rounded-xl bg-gradient-to-r from-pink-500/10 via-purple-500/10 to-indigo-500/10 border border-pink-300 dark:border-pink-800 flex items-center justify-between">
        <div class="flex items-center space-x-3">
            <span class="text-3xl">🎂</span>
            <div>
                <h3 class="font-bold text-slate-900 dark:text-white">{{ $birthdayCelebration['message'] }}</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">Institutional birthday blessings! Enjoy your special day!</p>
            </div>
        </div>
        <span class="px-3 py-1 text-xs font-black bg-pink-500 text-white rounded-full shadow-sm">+100 XP</span>
    </div>
    @endif

    <!-- ========================================== -->
    <!-- 1. ADMIN EXECUTIVE DASHBOARD (INFIX ARCHITECTURE) -->
    <!-- ========================================== -->
    @if($user->isAdmin())
        @include('dashboard.infix-admin')
    @endif

    <!-- TEACHER DASHBOARD -->
    