@extends('layouts.app')

@section('title', 'Debates' . ' — ' . ($school['name'] ?? config('app.name', 'School ERP')))

@section('content')
<div class="max-w-6xl mx-auto space-y-4">

    <!-- Header & Action Row -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-1 border-b border-slate-100 dark:border-slate-800">
        <div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                Debates, Talent Shows &amp; Tournaments
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Extracurricular competitions and exhibitions
            </p>
        </div>

        @if(Auth::user()->isAdmin() || Auth::user()->isTeacher())
        <div x-data="{ openCreateModal: false }">
            <button @click="openCreateModal = true" class="px-3 py-1.5 bg-rose-600 hover:bg-rose-500 text-white rounded-lg text-xs font-bold shadow-xs transition flex items-center space-x-1.5">
                    <span>+</span>
                    <span>Organize New Event</span>
                </button>

                <!-- Create Event Modal -->
                <div x-show="openCreateModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs" @keydown.escape.window="openCreateModal = false">
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl max-w-lg w-full p-6 shadow-2xl relative text-left" @click.away="openCreateModal = false">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800 mb-4">
                            <h3 class="text-sm font-extrabold text-slate-900 dark:text-white flex items-center space-x-2">
                                <span>🏆</span> <span>Host School Event / Competition</span>
                            </h3>
                            <button @click="openCreateModal = false" class="text-slate-400 hover:text-slate-200 text-lg">&times;</button>
                        </div>
                        <form action="{{ route('events.store') }}" method="POST" class="space-y-3 text-xs">
                            @csrf
                            <div>
                                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Event Title</label>
                                <input type="text" name="title" required placeholder="e.g. Annual Inter-Batch Parliamentary Debate 2026" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs">
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Category</label>
                                    <select name="category" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs">
                                        <option value="debate">🗣️ Parliamentary Debate</option>
                                        <option value="talent_show">🎭 Talent &amp; Cultural Show</option>
                                        <option value="science_exhibition">🔬 Science &amp; Innovation Expo</option>
                                        <option value="hackathon">💻 Coding &amp; Robotics Hackathon</option>
                                        <option value="literary">📖 Literary &amp; Creative Arts</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Format</label>
                                    <select name="event_format" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs">
                                        <option value="individual">👤 Solo / Individual</option>
                                        <option value="parliamentary_team">👥 3-Member Parliamentary Team</option>
                                        <option value="group">👥 Group Ensemble</option>
                                    </select>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Date &amp; Time</label>
                                    <input type="datetime-local" name="event_datetime" required value="{{ now()->addDays(7)->format('Y-m-d\TH:i') }}" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs">
                                </div>
                                <div>
                                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Auditorium / Venue</label>
                                    <input type="text" name="venue" required value="Central Auditorium &amp; Live Stream" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs">
                                </div>
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Description &amp; Motions</label>
                                <textarea name="description" rows="3" required placeholder="Detail the debate motion, judging rubric, rules..." class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs"></textarea>
                            </div>
                            <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex justify-end space-x-2">
                                <button type="button" @click="openCreateModal = false" class="px-3 py-1.5 rounded-xl text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800">Cancel</button>
                                <button type="submit" class="px-4 py-2 bg-rose-600 hover:bg-rose-500 text-white font-bold rounded-xl shadow-xs">Host Event</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- Category Filter Pills -->
        <div class="flex items-center space-x-1.5 overflow-x-auto pt-4 mt-4 border-t border-slate-100 dark:border-slate-800 text-xs">
            <a href="{{ route('events.index') }}" class="px-3 py-1.5 rounded-xl font-bold transition {{ empty($categoryFilter) || $categoryFilter === 'all' ? 'bg-rose-600 text-white' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                All Tournaments
            </a>
            <a href="{{ route('events.index', ['category' => 'debate']) }}" class="px-3 py-1.5 rounded-xl font-bold transition flex items-center space-x-1 {{ ($categoryFilter ?? '') === 'debate' ? 'bg-rose-600 text-white' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                <span>🗣️</span> <span>Debates</span>
            </a>
            <a href="{{ route('events.index', ['category' => 'talent_show']) }}" class="px-3 py-1.5 rounded-xl font-bold transition flex items-center space-x-1 {{ ($categoryFilter ?? '') === 'talent_show' ? 'bg-rose-600 text-white' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                <span>🎭</span> <span>Talent Shows</span>
            </a>
            <a href="{{ route('events.index', ['category' => 'science_exhibition']) }}" class="px-3 py-1.5 rounded-xl font-bold transition flex items-center space-x-1 {{ ($categoryFilter ?? '') === 'science_exhibition' ? 'bg-rose-600 text-white' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                <span>🔬</span> <span>Exhibitions</span>
            </a>
            <a href="{{ route('events.index', ['category' => 'hackathon']) }}" class="px-3 py-1.5 rounded-xl font-bold transition flex items-center space-x-1 {{ ($categoryFilter ?? '') === 'hackathon' ? 'bg-rose-600 text-white' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                <span>💻</span> <span>Hackathons</span>
            </a>
        </div>

    <!-- Events High-Density List (Clean & Mobile-Friendly) -->
    @if($events->isEmpty())
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-8 text-center">
        <div class="w-10 h-10 rounded-full bg-rose-50 dark:bg-rose-950/60 text-rose-500 flex items-center justify-center text-lg mx-auto mb-2">
            🏆
        </div>
        <h3 class="text-xs font-bold text-slate-800 dark:text-slate-200">No active events in this category</h3>
        <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">Check back soon or organize an extracurricular tournament.</p>
    </div>
    @else
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl divide-y divide-slate-100 dark:divide-slate-800 shadow-2xs overflow-hidden">
        @foreach($events as $ev)
        <div class="p-3 sm:p-3.5 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
            <div class="min-w-0 space-y-1">
                <div class="flex flex-wrap items-center gap-1.5">
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-black uppercase tracking-wider
                        @if($ev->category === 'debate') bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300
                        @elseif($ev->category === 'talent_show') bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-300
                        @elseif($ev->category === 'hackathon') bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300
                        @else bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300 @endif">
                        {{ ucfirst(str_replace('_', ' ', $ev->category)) }}
                    </span>
                    <span class="px-1.5 py-0.5 rounded-full text-[9px] font-bold {{ $ev->status === 'completed' ? 'bg-slate-100 dark:bg-slate-800 text-slate-500' : 'bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300' }}">
                        ● {{ ucfirst($ev->status) }}
                    </span>
                    <span class="text-[10px] text-slate-400 font-medium">
                        &bull; {{ ucfirst(str_replace('_', ' ', $ev->event_format)) }}
                    </span>
                </div>

                <div>
                    <a href="{{ route('events.show', $ev->id) }}" class="font-bold text-xs sm:text-sm text-slate-900 dark:text-white hover:text-rose-600 dark:hover:text-rose-400 transition truncate block">
                        {{ $ev->title }}
                    </a>
                </div>

                <div class="flex items-center space-x-3 text-[11px] text-slate-500 dark:text-slate-400">
                    <span>📅 {{ $ev->event_datetime->format('M d, Y &bull; h:i A') }}</span>
                    <span>&bull;</span>
                    <span>📍 {{ Str::limit($ev->venue, 22) }}</span>
                    <span>&bull;</span>
                    <span>👥 {{ count($ev->registered_participants ?? []) }} Registered</span>
                </div>
            </div>

            <div class="flex items-center space-x-2 sm:self-center shrink-0 pt-1 sm:pt-0 border-t sm:border-t-0 border-slate-100 dark:border-slate-800">
                @if(Auth::user()->isTeacher() || Auth::user()->isAdmin())
                <a href="{{ route('events.edit', $ev->id) }}" class="p-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition text-xs" title="Edit Event">
                    ✏️
                </a>
                <form action="{{ route('events.destroy', $ev->id) }}" method="POST" onsubmit="return confirm('Cancel and delete this event?');" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="p-1 text-rose-400 hover:text-rose-600 transition text-xs" title="Delete Event">
                        🗑️
                    </button>
                </form>
                @endif
                <a href="{{ route('events.show', $ev->id) }}" class="px-3 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-500 text-white font-bold text-xs transition shadow-2xs whitespace-nowrap">
                    View &amp; Join &rarr;
                </a>
            </div>
        </div>
        @endforeach
    </div>

    <div class="pt-2">
        {{ $events->links() }}
    </div>
    @endif

</div>
@endsection
