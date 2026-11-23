@extends('layouts.app')

@section('title', $event->title . ' - Academy Tournament')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <!-- Top Navigation -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <a href="{{ route('events.index') }}" class="text-xs font-bold text-slate-500 hover:text-rose-500 flex items-center space-x-1">
            <span>&larr; Back to Competitions &amp; Events</span>
        </a>

        @if(Auth::user()->isTeacher() || Auth::user()->isAdmin())
        <div class="flex items-center space-x-2">
            <a href="{{ route('events.export-participants-csv', $event->id) }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-xl text-xs font-bold transition flex items-center space-x-1 border border-slate-300 dark:border-slate-700">
                <span>📄</span> <span>Export Participants CSV</span>
            </a>
            <a href="{{ route('events.edit', $event->id) }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-xl text-xs font-bold transition">
                ✏️ Edit
            </a>
            <form action="{{ route('events.destroy', $event->id) }}" method="POST" onsubmit="return confirm('Cancel and delete this event?');" class="inline">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-3 py-1.5 bg-rose-50 text-rose-600 dark:bg-rose-950/40 dark:text-rose-300 rounded-xl text-xs font-bold hover:bg-rose-100 transition">
                    🗑️ Delete
                </button>
            </form>
        </div>
        @endif
    </div>

    <!-- Event Banner Card -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-8 space-y-4 shadow-xs">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300 border border-rose-300">
                🏆 {{ ucfirst(str_replace('_', ' ', $event->category)) }}
            </span>
            <div class="flex items-center space-x-2">
                <span class="px-3 py-1 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs">
                    Format: {{ ucfirst(str_replace('_', ' ', $event->event_format)) }}
                </span>
                <span class="px-3 py-1 rounded-xl bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 font-bold text-xs">
                    ● {{ ucfirst($event->status) }}
                </span>
            </div>
        </div>

        <div>
            <h1 class="text-xl sm:text-3xl font-black text-slate-900 dark:text-white">
                {{ $event->title }}
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Organized by Faculty Coordinator <strong>{{ $event->coordinator->name }}</strong> &bull; Venue: <strong>{{ $event->venue }}</strong>
            </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs pt-2">
            <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/60 dark:border-slate-700/60">
                <span class="text-[10px] uppercase font-bold text-slate-500">Scheduled Date &amp; Time</span>
                <div class="font-extrabold text-slate-900 dark:text-white mt-0.5">
                    {{ $event->event_datetime->format('l, F j, Y \a\t h:i A') }}
                </div>
            </div>
            <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/60 dark:border-slate-700/60">
                <span class="text-[10px] uppercase font-bold text-slate-500">Participants Registered</span>
                <div class="font-extrabold text-slate-900 dark:text-white mt-0.5">
                    {{ count($event->registered_participants ?? []) }} Competitors
                </div>
            </div>
        </div>

        <div class="p-5 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/60 dark:border-slate-700/60 space-y-2 text-xs leading-relaxed">
            <h4 class="font-black text-slate-900 dark:text-white uppercase tracking-wider text-[11px]">Event Rules &amp; Motion:</h4>
            <p class="text-slate-700 dark:text-slate-300">{{ $event->description }}</p>
            @if($event->rules_and_rubric)
            <div class="pt-2 border-t border-slate-200/60 dark:border-slate-700/60">
                <p class="text-slate-600 dark:text-slate-400"><strong>Adjudication Rubric:</strong> {{ $event->rules_and_rubric }}</p>
            </div>
            @endif
        </div>

        <!-- Registration Action -->
        <div class="pt-2 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            @if($isRegistered)
            <div class="flex items-center space-x-2 text-xs font-bold text-emerald-600 dark:text-emerald-400">
                <span class="w-6 h-6 rounded-full bg-emerald-500 text-white flex items-center justify-center text-xs font-black">✓</span>
                <span>You are officially registered for this event!</span>
            </div>
            <form action="{{ route('events.unregister', $event->id) }}" method="POST" onsubmit="return confirm('Withdraw your registration from this event?');">
                @csrf
                <button type="submit" class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 text-xs font-bold transition">
                    Withdraw Registration
                </button>
            </form>
            @else
            <form action="{{ route('events.register', $event->id) }}" method="POST">
                @csrf
                <button type="submit" class="px-6 py-3 bg-gradient-to-r from-rose-600 to-pink-600 hover:from-rose-500 hover:to-pink-500 text-white font-black text-xs rounded-2xl shadow-lg shadow-rose-600/25 transition flex items-center space-x-2">
                    <span>Register as Competitor</span>
                    <span>(+50 XP) 🚀</span>
                </button>
            </form>
            @endif
        </div>
    </div>

    <!-- WINNERS PODIUM CARD -->
    @if(!empty($event->podium_winners))
    <div class="bg-gradient-to-b from-amber-500/10 via-amber-500/5 to-transparent border border-amber-500/30 rounded-3xl p-6 sm:p-8 space-y-4 shadow-sm text-center">
        <span class="px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider bg-amber-500/20 text-amber-400 border border-amber-500/40">
            🏆 Tournament Victory Podium
        </span>
        <h3 class="text-lg sm:text-xl font-black text-slate-900 dark:text-white">
            Honors &amp; Laureates
        </h3>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2">
            <!-- 2nd Place -->
            <div class="p-4 rounded-2xl bg-white/60 dark:bg-slate-900/60 border border-slate-300 dark:border-slate-700 order-2 sm:order-1">
                <span class="text-2xl block mb-1">🥈</span>
                <span class="text-[10px] uppercase font-bold text-slate-400">2nd Place (Silver)</span>
                <h4 class="font-black text-sm text-slate-900 dark:text-white mt-1">{{ $event->podium_winners['2nd'] ?? 'TBA' }}</h4>
            </div>

            <!-- 1st Place (Gold) -->
            <div class="p-5 rounded-2xl bg-gradient-to-b from-amber-500/20 to-amber-500/10 border-2 border-amber-400 shadow-md order-1 sm:order-2">
                <span class="text-3xl block mb-1">🥇</span>
                <span class="text-[10px] uppercase font-black text-amber-500">1st Place (Gold Laureate)</span>
                <h4 class="font-black text-base text-slate-900 dark:text-white mt-1">{{ $event->podium_winners['1st'] ?? 'Declared' }}</h4>
            </div>

            <!-- 3rd Place -->
            <div class="p-4 rounded-2xl bg-white/60 dark:bg-slate-900/60 border border-slate-300 dark:border-slate-700 order-3 sm:order-3">
                <span class="text-2xl block mb-1">🥉</span>
                <span class="text-[10px] uppercase font-bold text-amber-700 dark:text-amber-500">3rd Place (Bronze)</span>
                <h4 class="font-black text-sm text-slate-900 dark:text-white mt-1">{{ $event->podium_winners['3rd'] ?? 'TBA' }}</h4>
            </div>
        </div>
    </div>
    @endif

    <!-- Faculty Podium Updater -->
    @if(Auth::user()->isTeacher() || Auth::user()->isAdmin())
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-xs space-y-3">
        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400">Faculty Jury Controls: Declare Winners</h4>
        <form action="{{ route('events.podium', $event->id) }}" method="POST" class="grid grid-cols-1 sm:grid-cols-4 gap-3 text-xs">
            @csrf
            <div>
                <label class="block font-bold mb-1">🥇 1st Place Winner</label>
                <input type="text" name="winner_1st" required value="{{ $event->podium_winners['1st'] ?? '' }}" placeholder="Name / Team" class="w-full px-2 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-xs">
            </div>
            <div>
                <label class="block font-bold mb-1">🥈 2nd Place</label>
                <input type="text" name="winner_2nd" value="{{ $event->podium_winners['2nd'] ?? '' }}" placeholder="Name / Team" class="w-full px-2 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-xs">
            </div>
            <div>
                <label class="block font-bold mb-1">🥉 3rd Place</label>
                <input type="text" name="winner_3rd" value="{{ $event->podium_winners['3rd'] ?? '' }}" placeholder="Name / Team" class="w-full px-2 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-xs">
            </div>
            <div class="flex items-end space-x-2">
                <input type="hidden" name="status" value="completed">
                <button type="submit" class="w-full py-1.5 bg-rose-600 hover:bg-rose-500 text-white font-bold rounded-lg text-xs transition">
                    Declare Podium
                </button>
            </div>
        </form>
    </div>
    @endif

</div>
@endsection
