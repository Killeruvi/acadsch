@extends('layouts.app')

@section('title', 'Sections & Room Allocations — ' . ($school['name'] ?? 'PrimeOS'))

@section('content')
<div class="max-w-[1600px] mx-auto space-y-4" x-data="{
    createModalOpen: {{ $errors->any() ? 'true' : 'false' }},
    viewMode: 'grid',
    searchQuery: '{{ addslashes($search ?? '') }}',
    activeStream: '{{ $selectedStream ?? 'all' }}',
    deleteModalOpen: false,
    deleteTargetId: null,
    deleteTargetName: '',
    confirmDelete(id, name) {
        this.deleteTargetId = id;
        this.deleteTargetName = name;
        this.deleteModalOpen = true;
    }
}">

    <!-- Page Header & Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <!-- Breadcrumbs -->
            <div class="flex items-center space-x-2 text-[11px] text-slate-400 font-semibold mb-1">
                <a href="{{ route('dashboard') }}" class="hover:text-slate-600 dark:hover:text-slate-200 transition">Dashboard</a>
                <span>/</span>
                <span class="text-slate-500">Academics</span>
                <span>/</span>
                <span class="text-slate-800 dark:text-slate-200 font-bold">Sections Hub</span>
            </div>

            <div class="flex items-center space-x-2.5">
                <span class="w-8 h-8 rounded-lg flex items-center justify-center text-white text-sm shadow-xs" style="background: var(--system_primary_gradient);">
                    <i class="ti-layout-grid3"></i>
                </span>
                <h1 class="text-lg sm:text-xl font-black text-slate-900 dark:text-white tracking-tight">
                    Reusable Academic Sections
                </h1>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold text-white shadow-2xs" style="background: var(--system_primary_gradient);">
                    {{ $stats['total_sections'] }} Total
                </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Manage reusable class divisions, room capacities, and section assignments across streams.
            </p>
        </div>

        <div class="flex items-center space-x-2">
            <!-- View Mode Switcher -->
            <div class="inline-flex items-center p-0.5 rounded-lg bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                <button type="button" @click="viewMode = 'grid'" :class="viewMode === 'grid' ? 'bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-xs' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'" class="px-2.5 py-1 rounded text-xs font-semibold transition">
                    <i class="ti-view-grid mr-1"></i> Grid
                </button>
                <button type="button" @click="viewMode = 'table'" :class="viewMode === 'table' ? 'bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-xs' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'" class="px-2.5 py-1 rounded text-xs font-semibold transition">
                    <i class="ti-view-list mr-1"></i> Table
                </button>
            </div>

            <!-- Quick Link to Classes -->
            @if(Auth::user()->isAdmin())
            <a href="{{ route('school.streams.index') }}" class="px-3 py-1.5 rounded-lg text-xs font-bold bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 transition">Streams</a>
            @endif
            <a href="{{ route('school.classes.index') }}" class="px-3 py-1.5 rounded-lg text-xs font-bold bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition flex items-center space-x-1.5">
                <i class="ti-bookmark"></i>
                <span>Classes Hub</span>
            </a>

            <!-- Add Section Button -->
            @if(Auth::user()->isAdmin())
            <button type="button" @click="createModalOpen = true" class="primary-btn inline-flex items-center space-x-1.5 shadow-sm" style="padding: 0.4rem 0.9rem; font-size: 0.8rem;">
                <i class="ti-plus"></i>
                <span>New Section</span>
            </button>
            @endif
        </div>
    </div>

    <!-- 4 Infix KPI Summary Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-3">
        <!-- 1. Total Sections -->
        <div class="white-box single-summery p-3 rounded-xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs flex items-center space-x-3">
            <div class="w-9 h-9 rounded-lg flex items-center justify-center text-white shrink-0 shadow-xs" style="background: var(--system_primary_gradient);">
                <i class="ti-layout-grid2 text-sm"></i>
            </div>
            <div class="min-w-0">
                <div class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Total Sections</div>
                <div class="gradient-color2 text-lg font-black leading-tight">{{ $stats['total_sections'] }}</div>
            </div>
        </div>

        <!-- 2. Active Streams -->
        <div class="white-box single-summery p-3 rounded-xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs flex items-center space-x-3">
            <div class="w-9 h-9 rounded-lg flex items-center justify-center bg-indigo-500 text-white shrink-0 shadow-xs">
                <i class="ti-direction-alt text-sm"></i>
            </div>
            <div class="min-w-0">
                <div class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Academic Streams</div>
                <div class="gradient-color2 text-lg font-black leading-tight">{{ $stats['total_streams'] }}</div>
            </div>
        </div>

        <!-- 3. Total Seating Capacity -->
        <div class="white-box single-summery p-3 rounded-xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs flex items-center space-x-3">
            <div class="w-9 h-9 rounded-lg flex items-center justify-center bg-emerald-500 text-white shrink-0 shadow-xs">
                <i class="ti-user text-sm"></i>
            </div>
            <div class="min-w-0">
                <div class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Total Seating</div>
                <div class="gradient-color2 text-lg font-black leading-tight">{{ number_format($stats['total_capacity']) }} <span class="text-[11px] font-normal text-slate-400">seats</span></div>
            </div>
        </div>

        <!-- 4. Class Deployments -->
        <div class="white-box single-summery p-3 rounded-xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs flex items-center space-x-3">
            <div class="w-9 h-9 rounded-lg flex items-center justify-center bg-amber-500 text-white shrink-0 shadow-xs">
                <i class="ti-blackboard text-sm"></i>
            </div>
            <div class="min-w-0">
                <div class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Active Classes</div>
                <div class="gradient-color2 text-lg font-black leading-tight">{{ $stats['assigned_classes_count'] }}</div>
            </div>
        </div>
    </div>

    <!-- Filters Row: Stream Tabs & Live Search -->
    <div class="p-3 rounded-xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs flex flex-col md:flex-row items-center justify-between gap-2.5">
        <!-- Stream Pills -->
        <div class="flex items-center space-x-1.5 overflow-x-auto w-full md:w-auto pb-1 md:pb-0 scrollbar-none">
            <a href="{{ route('school.sections.index', array_filter(['search' => $search])) }}" 
               class="px-3 py-1 rounded-full text-xs font-bold transition shrink-0 {{ empty($selectedStream) || $selectedStream === 'all' ? 'text-white shadow-xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}"
               @if(empty($selectedStream) || $selectedStream === 'all') style="background: var(--system_primary_gradient);" @endif>
                All Streams
            </a>
            @foreach($streams as $st)
            <a href="{{ route('school.sections.index', array_filter(['stream' => $st, 'search' => $search])) }}"
               class="px-3 py-1 rounded-full text-xs font-bold transition shrink-0 {{ ($selectedStream ?? '') === $st ? 'text-white shadow-xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}"
               @if(($selectedStream ?? '') === $st) style="background: var(--system_primary_gradient);" @endif>
                {{ $st }}
            </a>
            @endforeach
        </div>

        @if(Auth::user()->isAdmin())
        <form method="GET" action="{{ route('school.sections.index') }}">
            <input type="hidden" name="stream" value="{{ $selectedStream ?? 'all' }}">
            <input type="hidden" name="search" value="{{ $search }}">
            <select name="status" onchange="this.form.submit()" class="rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs p-2 text-slate-900 dark:text-white">
                <option value="active" {{ ($status ?? 'active') === 'active' ? 'selected' : '' }}>Active sections</option>
                <option value="inactive" {{ ($status ?? '') === 'inactive' ? 'selected' : '' }}>Inactive sections</option>
                <option value="all" {{ ($status ?? '') === 'all' ? 'selected' : '' }}>All sections</option>
            </select>
        </form>
        @endif
        <!-- Search Form -->
        <form method="GET" action="{{ route('school.sections.index') }}" class="relative w-full md:w-72">
            @if(!empty($selectedStream) && $selectedStream !== 'all')
                <input type="hidden" name="stream" value="{{ $selectedStream }}">
            @endif
            <input type="hidden" name="status" value="{{ $status ?? 'active' }}">
            <input type="text" name="search" value="{{ $search }}" placeholder="Search by section or room..." 
                   class="w-full pl-8 pr-8 py-1.5 text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/80 text-slate-900 dark:text-white focus:outline-none focus:ring-1"
                   style="--tw-ring-color: var(--system_primery_color);">
            <i class="ti-search absolute left-2.5 top-2.5 text-slate-400 text-xs"></i>
            @if($search)
            <a href="{{ route('school.sections.index', array_filter(['stream' => $selectedStream])) }}" class="absolute right-2.5 top-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-xs">
                <i class="ti-close"></i>
            </a>
            @endif
        </form>
    </div>

    <!-- Main Content: Compact Card Grid -->
    <div x-show="viewMode === 'grid'" class="space-y-3">
        @if($sections->isEmpty())
        <div class="p-8 text-center bg-white dark:bg-slate-900 rounded-xl border border-dashed border-slate-300 dark:border-slate-800">
            <div class="w-12 h-12 rounded-full mx-auto flex items-center justify-center text-slate-400 bg-slate-100 dark:bg-slate-800 mb-3">
                <i class="ti-layout-grid3 text-xl"></i>
            </div>
            <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200">No sections found</h3>
            <p class="text-xs text-slate-500 mt-1">There are no academic sections matching the current filter criteria.</p>
            @if(Auth::user()->isAdmin())
            <button type="button" @click="createModalOpen = true" class="mt-3 primary-btn text-xs px-3 py-1.5 inline-flex items-center space-x-1">
                <i class="ti-plus"></i>
                <span>Add First Section</span>
            </button>
            @endif
        </div>
        @else
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
            @foreach($sections as $sec)
            @php
                $assignedClasses = $assignedMap[$sec->id] ?? collect();
                $isAssigned = $assignedClasses->isNotEmpty();
            @endphp
            <div class="white-box p-4 border border-slate-200/80 dark:border-slate-800 shadow-xs hover:border-slate-300 dark:hover:border-slate-700 transition flex flex-col justify-between group">
                <div>
                    <!-- Top row: Avatar & Stream Badge -->
                    <div class="flex items-start justify-between gap-2 mb-2">
                        <div class="flex items-center space-x-2.5">
                            <div class="w-9 h-9 rounded-lg flex items-center justify-center font-black text-sm text-white shadow-xs shrink-0" style="background: var(--system_primary_gradient);">
                                {{ $sec->name }}
                            </div>
                            <div>
                                <h3 class="text-sm font-black text-slate-900 dark:text-white leading-tight">
                                    Section {{ $sec->name }}
                                </h3>
                                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
                                    {{ $sec->stream ?: 'Stream not set' }} Stream
                                </span>
                            </div>
                        </div>

                        <!-- Stream Pill -->
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold shrink-0
                            @if(strcasecmp($sec->stream, 'Science') === 0) bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800
                            @elseif(strcasecmp($sec->stream, 'Commerce') === 0) bg-amber-50 dark:bg-amber-950/50 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800
                            @elseif(strcasecmp($sec->stream, 'Arts') === 0) bg-purple-50 dark:bg-purple-950/50 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-800
                            @else bg-blue-50 dark:bg-blue-950/50 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800 @endif">
                            {{ $sec->stream ?: 'Stream not set' }}
                        </span>
                    </div>

                    <!-- Room & Capacity Badges -->
                    <div class="grid grid-cols-2 gap-1.5 my-2.5">
                        <div class="px-2 py-1 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800 flex items-center space-x-1.5 text-[11px]">
                            <i class="ti-home text-slate-400 text-xs"></i>
                            <span class="text-slate-600 dark:text-slate-300 font-semibold truncate">
                                {{ $sec->room_number ? $sec->room_number : 'Unassigned Room' }}
                            </span>
                        </div>
                        <div class="px-2 py-1 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800 flex items-center space-x-1.5 text-[11px]">
                            <i class="ti-user text-slate-400 text-xs"></i>
                            <span class="text-slate-600 dark:text-slate-300 font-semibold">
                                {{ $sec->capacity ?: 'Not set' }} Seats
                            </span>
                        </div>
                    </div>

                    @if($sec->description)
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 line-clamp-2 mb-2 italic">{{ $sec->description }}</p>
                    @endif
                    @if(Auth::user()->isAdmin())
                    <details class="mb-2"><summary class="cursor-pointer text-[10px] font-bold text-indigo-600">Edit room / capacity / notes</summary>
                        <form method="POST" action="{{ route('school.sections.update', $sec->id) }}" class="mt-2 space-y-2">@csrf @method('PATCH')
                            <input name="room_number" value="{{ $sec->room_number }}" maxlength="50" placeholder="Room" class="w-full rounded border p-1.5 text-xs bg-transparent">
                            <input type="number" name="capacity" value="{{ $sec->capacity }}" min="{{ config('school.capacity.minimum') }}" max="{{ config('school.capacity.maximum') }}" required class="w-full rounded border p-1.5 text-xs bg-transparent">
                            <textarea name="description" maxlength="255" class="w-full rounded border p-1.5 text-xs bg-transparent" placeholder="Notes">{{ $sec->description }}</textarea>
                            <button class="rounded bg-indigo-600 text-white px-2 py-1 text-xs">Save details</button>
                        </form>
                    </details>
                    @endif
                </div>

                <!-- Footer: Assigned Classes & Delete Action -->
                <div class="pt-2 border-t border-slate-100 dark:border-slate-800/60 flex items-center justify-between text-[11px]">
                    <div class="flex items-center space-x-1.5 text-slate-500">
                        <i class="ti-bookmark text-xs text-slate-400"></i>
                        @if($isAssigned)
                            <span class="font-bold text-indigo-600 dark:text-indigo-400">
                                {{ $assignedClasses->count() }} Class{{ $assignedClasses->count() > 1 ? 'es' : '' }} Linked
                            </span>
                        @else
                            <span class="text-slate-400">Not assigned</span>
                        @endif
                    </div>

                    @if(Auth::user()->isAdmin())
                    <form method="POST" action="{{ route('school.sections.toggle', $sec->id) }}">
                        @csrf @method('PATCH')
                        <button type="submit" class="text-xs font-semibold {{ $sec->is_active ? 'text-rose-600' : 'text-emerald-600' }}">{{ $sec->is_active ? 'Deactivate' : 'Reactivate' }}</button>
                    </form>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    <!-- Alternative View: Compact Table -->
    <div x-show="viewMode === 'table'" class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200/80 dark:border-slate-800 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50/80 dark:bg-slate-800/50 border-b border-slate-200/80 dark:border-slate-800 text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                        <th class="p-3">Section</th>
                        <th class="p-3">Stream</th>
                        <th class="p-3">Room</th>
                        <th class="p-3">Capacity</th>
                        <th class="p-3">Linked Classes</th>
                        <th class="p-3">Status</th>
                        <th class="p-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium">
                    @forelse($sections as $sec)
                    @php
                        $assignedClasses = $assignedMap[$sec->id] ?? collect();
                        $isAssigned = $assignedClasses->isNotEmpty();
                    @endphp
                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition">
                        <td class="p-3">
                            <div class="flex items-center space-x-2">
                                <span class="w-6 h-6 rounded flex items-center justify-center font-bold text-white text-[11px] shadow-xs" style="background: var(--system_primary_gradient);">
                                    {{ $sec->name }}
                                </span>
                                <span class="font-bold text-slate-900 dark:text-white">Section {{ $sec->name }}</span>
                            </div>
                        </td>
                        <td class="p-3">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold
                                @if(strcasecmp($sec->stream, 'Science') === 0) bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300
                                @elseif(strcasecmp($sec->stream, 'Commerce') === 0) bg-amber-50 dark:bg-amber-950/50 text-amber-700 dark:text-amber-300
                                @elseif(strcasecmp($sec->stream, 'Arts') === 0) bg-purple-50 dark:bg-purple-950/50 text-purple-700 dark:text-purple-300
                                @else bg-blue-50 dark:bg-blue-950/50 text-blue-700 dark:text-blue-300 @endif">
                                {{ $sec->stream ?: 'Stream not set' }}
                            </span>
                        </td>
                        <td class="p-3 text-slate-600 dark:text-slate-300">
                            {{ $sec->room_number ?? '—' }}
                        </td>
                        <td class="p-3 font-semibold text-slate-700 dark:text-slate-300">
                            {{ $sec->capacity ?: 'Not set' }} seats
                        </td>
                        <td class="p-3">
                            @if($isAssigned)
                                <div class="flex flex-wrap gap-1">
                                    @foreach($assignedClasses as $c)
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                            {{ $c->name }}
                                        </span>
                                    @endforeach
                                </div>
                            @else
                                <span class="text-slate-400 text-[11px]">Unlinked</span>
                            @endif
                        </td>
                        <td class="p-3">{{ $sec->is_active ? 'Active' : 'Inactive' }}</td>
                        <td class="p-3 text-right">
                            @if(Auth::user()->isAdmin())
                            <form method="POST" action="{{ route('school.sections.toggle', $sec->id) }}">@csrf @method('PATCH')
                                <button class="text-xs font-semibold {{ $sec->is_active ? 'text-rose-600' : 'text-emerald-600' }}">{{ $sec->is_active ? 'Deactivate' : 'Reactivate' }}</button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="p-6 text-center text-slate-400">
                            No sections registered.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination Links -->
    @if($sections instanceof \Illuminate\Pagination\LengthAwarePaginator && $sections->hasPages())
    <div class="mt-4 p-3 bg-white dark:bg-slate-900 rounded-xl border border-slate-200/80 dark:border-slate-800 shadow-2xs">
        {{ $sections->links() }}
    </div>
    @endif

    <!-- Create Section Modal -->
    <div x-show="createModalOpen" style="display: none;" 
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-md w-full shadow-2xl overflow-hidden"
             @click.away="createModalOpen = false">
            <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between" style="background: var(--system_primary_gradient);">
                <div class="flex items-center space-x-2 text-white">
                    <i class="ti-plus font-bold"></i>
                    <h3 class="font-bold text-sm">Create Reusable Section</h3>
                </div>
                <button type="button" @click="createModalOpen = false" class="text-white/80 hover:text-white text-sm">
                    <i class="ti-close"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('school.sections.store') }}" class="p-4 space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        Academic Stream <span class="text-rose-500">*</span>
                    </label>
                    <select name="stream" required class="w-full text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2 text-slate-900 dark:text-white focus:ring-1" style="--tw-ring-color: var(--system_primery_color);">
                        @foreach($streams as $st)
                            <option value="{{ $st }}">{{ $st }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        Section Letter <span class="text-rose-500">*</span>
                    </label>
                    <select name="name" required class="w-full text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2 text-slate-900 dark:text-white font-bold focus:ring-1" style="--tw-ring-color: var(--system_primery_color);">
                        @foreach(range('A', 'Z') as $letter)
                            <option value="{{ $letter }}">Section {{ $letter }}</option>
                        @endforeach
                    </select>
                    <span class="text-[10px] text-slate-400">Section letters are single letters (A, B, C, D...) reused when creating classes</span>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Room Number</label>
                        <input type="text" name="room_number" placeholder="e.g. Room 102" maxlength="50"
                               class="w-full text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2 text-slate-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Seating Capacity</label>
                        <input type="number" name="capacity" min="{{ config('school.capacity.minimum') }}" max="{{ config('school.capacity.maximum') }}" required
                               class="w-full text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2 text-slate-900 dark:text-white">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Description / Notes</label>
                    <textarea name="description" rows="2" placeholder="Optional notes regarding this section or lab facilities..."
                              class="w-full text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2 text-slate-900 dark:text-white"></textarea>
                </div>

                <div class="pt-2 flex items-center justify-end space-x-2">
                    <button type="button" @click="createModalOpen = false" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 transition">
                        Cancel
                    </button>
                    <button type="submit" class="primary-btn text-xs px-4 py-1.5 shadow-sm">
                        Create Section
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div x-show="deleteModalOpen" style="display: none;" 
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-sm w-full shadow-2xl p-4 space-y-3"
             @click.away="deleteModalOpen = false">
            <div class="w-10 h-10 rounded-full bg-rose-50 dark:bg-rose-950/60 text-rose-500 flex items-center justify-center mx-auto">
                <i class="ti-alert text-lg"></i>
            </div>
            <div class="text-center">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Delete Academic Section</h3>
                <p class="text-xs text-slate-500 mt-1">
                    Are you sure you want to delete section <span class="font-bold text-slate-800 dark:text-slate-200" x-text="deleteTargetName"></span>? This action cannot be undone.
                </p>
            </div>
            <form :action="'{{ url('/school/sections') }}/' + deleteTargetId" method="POST" class="flex items-center justify-center space-x-2 pt-2">
                @csrf
                @method('DELETE')
                <button type="button" @click="deleteModalOpen = false" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 transition">
                    Cancel
                </button>
                <button type="submit" class="px-3.5 py-1.5 rounded-lg text-xs font-bold bg-rose-600 hover:bg-rose-700 text-white shadow-xs transition">
                    Confirm Delete
                </button>
            </form>
        </div>
    </div>

</div>
@endsection
