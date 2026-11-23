@extends('layouts.app')

@section('title', 'Academic Holidays & Festivals — ' . ($school['name'] ?? 'PrimeArc Public School'))

@section('content')
<div class="max-w-[1680px] mx-auto space-y-4" x-data="{
    openAddModal: false,
    openEditModal: false,
    editHoliday: { id: null, name: '', holiday_date: '', end_date: '', type: 'festival', badge_icon: '🎉', description: '', is_instructional_off: true }
}">

    <!-- Single Title Header (Rule 18: One title per page, no banners) -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-2 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="inline-flex items-center space-x-1.5 px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 dark:bg-amber-950 text-amber-700 dark:text-amber-300 mb-1 border border-amber-200 dark:border-amber-800">
                <span>📅</span>
                <span>Institutional Calendar</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight">Academic Holidays &amp; Festivals</h1>
            <p class="text-xs text-slate-600 dark:text-slate-400 mt-0.5">
                Official calendar recesses, national observances, and festive breaks. Attendance and timetables skip these dates automatically.
            </p>
        </div>

        <div class="flex items-center space-x-2">
            <div class="flex items-center gap-1 p-1 rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs font-bold">
                <a aria-label="Previous year" href="{{ route('school.holidays.index', ['year' => $year - 1]) }}" class="px-2.5 py-1.5 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-white dark:hover:bg-slate-700">←</a>
                <span class="px-2 py-1.5 text-slate-900 dark:text-white">{{ $year }}</span>
                <a aria-label="Next year" href="{{ route('school.holidays.index', ['year' => $year + 1]) }}" class="px-2.5 py-1.5 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-white dark:hover:bg-slate-700">→</a>
            </div>

            @if(Auth::user()->isAdmin())
            <button type="button" @click="openAddModal = true" class="px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-2xs transition flex items-center space-x-1.5">
                <span>➕</span>
                <span>Add Holiday</span>
            </button>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-3">
            <p class="text-[10px] uppercase font-bold tracking-wide text-slate-500">Days in year</p>
            <p class="text-xl font-black text-slate-900 dark:text-white">{{ number_format($calendarStats['total_days']) }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-3">
            <p class="text-[10px] uppercase font-bold tracking-wide text-slate-500">Weekend days</p>
            <p class="text-xl font-black text-slate-900 dark:text-white">{{ number_format($calendarStats['weekend_days']) }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-3">
            <p class="text-[10px] uppercase font-bold tracking-wide text-slate-500">Holiday closure days</p>
            <p class="text-xl font-black text-slate-900 dark:text-white">{{ number_format($calendarStats['holiday_days']) }}</p>
        </div>
        <div class="rounded-xl border border-emerald-200 dark:border-emerald-900 bg-emerald-50/70 dark:bg-emerald-950/30 p-3">
            <p class="text-[10px] uppercase font-bold tracking-wide text-emerald-700 dark:text-emerald-300">Estimated instructional days</p>
            <p class="text-xl font-black text-emerald-800 dark:text-emerald-200">{{ number_format($calendarStats['instructional_days']) }}</p>
        </div>
    </div>

    <!-- Holidays Grid -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-xs space-y-4">
        <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
            <h2 class="text-xs font-black uppercase tracking-wider text-slate-900 dark:text-white">
                Declared Recesses for {{ $year }} ({{ $holidays->total() }})
            </h2>
            <span class="text-[10px] text-slate-500 font-mono">Skip attendance &amp; timetables</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
            @forelse($holidays as $h)
            <div class="p-3.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/40 flex flex-col justify-between space-y-2">
                <div class="flex items-start justify-between gap-2">
                    <div class="flex items-start space-x-2.5 min-w-0">
                        <span class="text-2xl p-1.5 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-2xs flex-shrink-0">
                            {{ $h->badge_icon ?: '🎉' }}
                        </span>
                        <div class="min-w-0">
                            <h3 class="text-xs font-bold text-slate-900 dark:text-white truncate" title="{{ $h->name }}">
                                {{ $h->name }}
                            </h3>
                            <span class="inline-block mt-0.5 px-2 py-0.5 rounded text-[9px] font-black uppercase tracking-wider {{ match($h->type) { 'national' => 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300', 'vacation' => 'bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-300', default => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' } }}">
                                {{ ucfirst($h->type) }}
                            </span>
                        </div>
                    </div>

                    @if(Auth::user()->isAdmin())
                    <div class="flex items-center space-x-1 flex-shrink-0">
                        <button type="button" @click="editHoliday = {
                            id: {{ $h->id }},
                            name: @js($h->name),
                            holiday_date: '{{ $h->holiday_date->format('Y-m-d') }}',
                            end_date: '{{ $h->end_date ? $h->end_date->format('Y-m-d') : '' }}',
                            type: '{{ $h->type }}',
                            badge_icon: @js($h->badge_icon),
                            description: @js($h->description ?? ''),
                            is_instructional_off: {{ $h->is_instructional_off ? 'true' : 'false' }}
                        }; openEditModal = true" class="p-1 rounded text-slate-400 hover:text-emerald-600 text-xs">
                            ✏️
                        </button>
                        <form method="POST" action="{{ route('school.holidays.destroy', $h->id) }}" onsubmit="return confirm(@js('Remove holiday ' . $h->name . '?'));" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="p-1 rounded text-slate-400 hover:text-rose-600 text-xs">
                                🗑️
                            </button>
                        </form>
                    </div>
                    @endif
                </div>

                <div class="pt-2 border-t border-slate-200/60 dark:border-slate-800 text-[11px] text-slate-600 dark:text-slate-400 flex items-center justify-between font-mono">
                    <span>
                        📅 {{ $h->holiday_date->format('d M Y') }}
                        @if($h->end_date && $h->end_date->format('Y-m-d') !== $h->holiday_date->format('Y-m-d'))
                            &rarr; {{ $h->end_date->format('d M') }}
                        @endif
                    </span>
                    <span class="text-[10px] font-bold text-slate-500">
                        {{ $h->is_instructional_off ? 'No Class' : 'Observance' }}
                    </span>
                </div>
            </div>
            @empty
            <div class="col-span-full p-8 text-center text-xs text-slate-400">
                No holidays recorded for academic calendar {{ $year }}.
            </div>
            @endforelse
        </div>

        @if($holidays->hasPages())
        <div class="pt-3 border-t border-slate-100 dark:border-slate-800">
            {{ $holidays->links() }}
        </div>
        @endif
    </div>

    <!-- Admin Add Holiday Modal -->
    @if(Auth::user()->isAdmin())
    <div x-show="openAddModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs" @click="openAddModal = false"></div>
        <div class="relative bg-white dark:bg-slate-900 rounded-2xl max-w-md w-full p-5 border border-slate-200 dark:border-slate-800 shadow-2xl space-y-4">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Add Academic Holiday / Festival</h3>
                <button type="button" @click="openAddModal = false" class="text-slate-400 hover:text-slate-600 text-xs">✕</button>
            </div>

            <form method="POST" action="{{ route('school.holidays.store') }}" class="space-y-3 text-xs">
                @csrf
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Holiday / Festival Name *</label>
                    <input type="text" name="name" required placeholder="e.g. Diwali / Republic Day" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Start Date *</label>
                        <input type="date" name="holiday_date" required class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">End Date</label>
                        <input type="date" name="end_date" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Category *</label>
                        <select name="type" required class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                            <option value="festival">Festival Break</option>
                            <option value="national">National Holiday</option>
                            <option value="vacation">Term Vacation</option>
                            <option value="institutional">Institutional Off</option>
                            <option value="restricted">Restricted Holiday</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Icon Badge</label>
                        <input type="text" name="badge_icon" value="🎉" maxlength="4" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Description / Circular Note</label>
                    <textarea name="description" rows="2" placeholder="Institutional advisory..." class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white"></textarea>
                </div>

                <label class="flex items-center space-x-2 cursor-pointer pt-1">
                    <input type="checkbox" name="is_instructional_off" value="1" checked class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                    <span class="text-slate-700 dark:text-slate-300 font-semibold">Instructional classes suspended (Skip attendance &amp; timetables)</span>
                </label>

                <div class="flex justify-end space-x-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="openAddModal = false" class="px-4 py-2 rounded-xl border border-slate-300 dark:border-slate-700 text-slate-600 dark:text-slate-400 font-bold">Cancel</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold">Add Holiday</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Admin Edit Holiday Modal -->
    <div x-show="openEditModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs" @click="openEditModal = false"></div>
        <div class="relative bg-white dark:bg-slate-900 rounded-2xl max-w-md w-full p-5 border border-slate-200 dark:border-slate-800 shadow-2xl space-y-4">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Edit Academic Holiday</h3>
                <button type="button" @click="openEditModal = false" class="text-slate-400 hover:text-slate-600 text-xs">✕</button>
            </div>

            <form method="POST" :action="'{{ url('/school/holidays') }}/' + editHoliday.id" class="space-y-3 text-xs">
                @csrf
                @method('PUT')
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Holiday Name *</label>
                    <input type="text" name="name" x-model="editHoliday.name" required class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Start Date *</label>
                        <input type="date" name="holiday_date" x-model="editHoliday.holiday_date" required class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">End Date</label>
                        <input type="date" name="end_date" x-model="editHoliday.end_date" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Category *</label>
                        <select name="type" x-model="editHoliday.type" required class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                            <option value="festival">Festival Break</option>
                            <option value="national">National Holiday</option>
                            <option value="vacation">Term Vacation</option>
                            <option value="institutional">Institutional Off</option>
                            <option value="restricted">Restricted Holiday</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Icon Badge</label>
                        <input type="text" name="badge_icon" x-model="editHoliday.badge_icon" maxlength="4" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Description</label>
                    <textarea name="description" x-model="editHoliday.description" rows="2" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white"></textarea>
                </div>

                <label class="flex items-center space-x-2 cursor-pointer pt-1">
                    <input type="checkbox" name="is_instructional_off" value="1" :checked="editHoliday.is_instructional_off" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                    <span class="text-slate-700 dark:text-slate-300 font-semibold">Instructional classes suspended</span>
                </label>

                <div class="flex justify-end space-x-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="openEditModal = false" class="px-4 py-2 rounded-xl border border-slate-300 dark:border-slate-700 text-slate-600 dark:text-slate-400 font-bold">Cancel</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
    @endif

</div>
@endsection
