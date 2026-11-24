@extends('layouts.app')

@section('title', 'Academic Leaderboard' . ' — ' . ($school['name'] ?? config('app.name', 'School ERP')))

@section('content')
<div class="max-w-5xl mx-auto space-y-4">

    <!-- Header & Action Row -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-1">
        <div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                Campus Leaderboard
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Academic rankings for XP points and study streaks
            </p>
        </div>

        @if($myRank)
        <!-- My Current Standing Card -->
        <div class="p-2 sm:p-2.5 rounded-xl border border-emerald-500/30 bg-emerald-50/50 dark:bg-emerald-950/20 flex items-center space-x-2.5">
            <div class="w-8 h-8 rounded-lg bg-emerald-600 text-white font-black text-xs flex items-center justify-center shadow-xs">
                #{{ $myRank }}
            </div>
            <div class="text-xs">
                <span class="text-slate-400 block text-[10px] uppercase font-bold">Your Rank</span>
                <span class="font-extrabold text-slate-900 dark:text-white">{{ $currentUser->xp_points }} XP &bull; {{ $currentUser->streak_count }}d Streak</span>
            </div>
        </div>
        @endif
    </div>

    <!-- Metric Switcher Tabs & Filter -->
    <div class="flex flex-wrap items-center justify-between gap-3 text-xs">
        <div class="inline-flex rounded-xl bg-slate-100 dark:bg-slate-800 p-1 border border-slate-200 dark:border-slate-700">
            <a href="{{ route('leaderboard.index', array_merge(request()->except(['tab']), ['tab' => 'xp'])) }}" class="px-3 py-1.5 rounded-lg font-bold transition flex items-center space-x-1.5 {{ ($tab ?? 'xp') === 'xp' ? 'bg-white dark:bg-slate-700 text-amber-500 shadow-xs' : 'text-slate-500' }}">
                <span>⚡</span> <span>Overall XP</span>
            </a>
            <a href="{{ route('leaderboard.index', array_merge(request()->except(['tab']), ['tab' => 'streak'])) }}" class="px-3 py-1.5 rounded-lg font-bold transition flex items-center space-x-1.5 {{ ($tab ?? '') === 'streak' ? 'bg-white dark:bg-slate-700 text-orange-500 shadow-xs' : 'text-slate-500' }}">
                <span>🔥</span> <span>Streak</span>
            </a>
        </div>

        <!-- Class Filter Dropdown -->
        <form method="GET" action="{{ route('leaderboard.index') }}" class="flex items-center space-x-2">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <select name="class_id" onchange="this.form.submit()" class="px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs font-bold">
                <option value="">🏫 Campus-Wide (All Classes)</option>
                @foreach($allClasses as $cl)
                    <option value="{{ $cl->id }}" {{ $selectedClassId == $cl->id ? 'selected' : '' }}>
                        {{ $cl->name }}
                    </option>
                @endforeach
            </select>
        </form>
    </div>

    <!-- COMPACT TOP 3 PODIUM STRIP (CLEAN & MOBILE-FRIENDLY) -->
    @if($topThree->isNotEmpty())
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 items-stretch pt-1">
        
        <!-- 2nd Place (Silver) -->
        @if($topThree->count() >= 2)
        @php $second = $topThree->get(1); @endphp
        <div class="order-2 sm:order-1 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3 sm:p-3.5 text-center flex sm:flex-col items-center justify-between sm:justify-center gap-2 shadow-2xs">
            <div class="flex items-center sm:flex-col space-x-2 sm:space-x-0 sm:space-y-1">
                <span class="text-xl">🥈</span>
                <div class="text-left sm:text-center">
                    <span class="text-[9px] font-black uppercase text-slate-400 block">Rank 2 &bull; Silver</span>
                    <h3 class="font-bold text-xs sm:text-sm text-slate-900 dark:text-white truncate max-w-[120px]">{{ $second->name }}</h3>
                </div>
            </div>
            <div class="flex items-center space-x-2 text-[11px] font-bold">
                <span class="text-amber-500">⚡ {{ $second->xp_points }} XP</span>
                <span class="text-orange-500">🔥 {{ $second->streak_count }}d</span>
            </div>
        </div>
        @endif

        <!-- 1st Place (Gold) -->
        @if($topThree->count() >= 1)
        @php $first = $topThree->get(0); @endphp
        <div class="order-1 sm:order-2 bg-gradient-to-r sm:bg-gradient-to-b from-amber-500/15 via-amber-500/5 to-transparent border border-amber-400/60 rounded-xl p-3 sm:p-3.5 text-center flex sm:flex-col items-center justify-between sm:justify-center gap-2 shadow-2xs">
            <div class="flex items-center sm:flex-col space-x-2 sm:space-x-0 sm:space-y-1">
                <span class="text-2xl animate-bounce">👑</span>
                <div class="text-left sm:text-center">
                    <span class="text-[9px] font-black uppercase text-amber-600 dark:text-amber-400 block">🥇 Champion</span>
                    <h3 class="font-black text-xs sm:text-sm text-slate-900 dark:text-white truncate max-w-[140px]">{{ $first->name }}</h3>
                </div>
            </div>
            <div class="flex items-center space-x-2 text-[11px] font-black">
                <span class="text-amber-500">⚡ {{ $first->xp_points }} XP</span>
                <span class="text-orange-500">🔥 {{ $first->streak_count }}d</span>
            </div>
        </div>
        @endif

        <!-- 3rd Place (Bronze) -->
        @if($topThree->count() >= 3)
        @php $third = $topThree->get(2); @endphp
        <div class="order-3 sm:order-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3 sm:p-3.5 text-center flex sm:flex-col items-center justify-between sm:justify-center gap-2 shadow-2xs">
            <div class="flex items-center sm:flex-col space-x-2 sm:space-x-0 sm:space-y-1">
                <span class="text-xl">🥉</span>
                <div class="text-left sm:text-center">
                    <span class="text-[9px] font-black uppercase text-amber-700 dark:text-amber-500 block">Rank 3 &bull; Bronze</span>
                    <h3 class="font-bold text-xs sm:text-sm text-slate-900 dark:text-white truncate max-w-[120px]">{{ $third->name }}</h3>
                </div>
            </div>
            <div class="flex items-center space-x-2 text-[11px] font-bold">
                <span class="text-amber-500">⚡ {{ $third->xp_points }} XP</span>
                <span class="text-orange-500">🔥 {{ $third->streak_count }}d</span>
            </div>
        </div>
        @endif
    </div>
    @endif

    <!-- FULL LEADERBOARD TABLE -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-2xs overflow-hidden">
        <div class="p-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                Student Academic Rankings
            </h3>
            <span class="text-xs text-slate-400">{{ $leaderboardUsers->total() }} Ranked Students</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-800/70 border-b border-slate-200 dark:border-slate-800 text-[11px] font-black uppercase text-slate-600 dark:text-slate-300">
                        <th class="p-3 w-16 text-center">Rank</th>
                        <th class="p-3">Student</th>
                        <th class="p-3">Class &amp; Section</th>
                        <th class="p-3">Academic Level</th>
                        <th class="p-3">Active Streak</th>
                        <th class="p-3 text-right">XP Points</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach($leaderboardUsers as $index => $u)
                    @php
                        $rank = ($leaderboardUsers->currentPage() - 1) * $leaderboardUsers->perPage() + $index + 1;
                        $isMe = ($currentUser->id === $u->id);
                        $enrollment = $u->classEnrollments->first();
                        $className = $enrollment?->academicClass?->display_name ?? ($u->currentClass?->display_name ?? 'Class');
                    @endphp
                    <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition {{ $isMe ? 'bg-emerald-50/50 dark:bg-emerald-950/20 font-bold' : '' }}">
                        <td class="p-3 text-center align-middle">
                            @if($rank === 1) <span class="text-base">🥇</span>
                            @elseif($rank === 2) <span class="text-base">🥈</span>
                            @elseif($rank === 3) <span class="text-base">🥉</span>
                            @else <span class="font-mono font-bold text-slate-500">#{{ $rank }}</span>
                            @endif
                        </td>
                        <td class="p-3 align-middle">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 rounded-xl bg-slate-200 dark:bg-slate-800 font-bold text-slate-700 dark:text-slate-300 flex items-center justify-center text-xs">
                                    {{ strtoupper(substr($u->name, 0, 2)) }}
                                </div>
                                <div>
                                    <span class="font-bold text-slate-900 dark:text-white block">
                                        {{ $u->name }}
                                        @if($isMe) <span class="text-[10px] text-emerald-500 ml-1">(You)</span> @endif
                                    </span>
                                    <span class="text-[10px] text-slate-400 font-mono">{{ $u->student_id_number }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="p-3 align-middle">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                {{ $className }}
                            </span>
                        </td>
                        <td class="p-3 align-middle">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300">
                                {{ $u->academic_tier ?? 'Enrolled Student' }}
                            </span>
                        </td>
                        <td class="p-3 align-middle">
                            <span class="font-bold text-orange-500 flex items-center space-x-1">
                                <span>🔥</span>
                                <span>{{ $u->streak_count ?? 1 }}d</span>
                            </span>
                        </td>
                        <td class="p-3 align-middle text-right">
                            <span class="font-mono font-black text-amber-500 text-sm">
                                {{ $u->xp_points ?? 100 }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-100 dark:border-slate-800">
            {{ $leaderboardUsers->links() }}
        </div>
    </div>

</div>
@endsection
