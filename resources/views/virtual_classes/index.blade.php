@extends('layouts.app')

@section('title', 'Virtual Classes & Live Sessions — ' . ($school['name'] ?? 'Infix LMS'))

@section('content')
<div class="max-w-[1600px] mx-auto space-y-4" x-data="{
    scheduleModalOpen: {{ $errors->any() ? 'true' : 'false' }},
    selectedHost: 'zoom',
}">

    <!-- Infix Breadcrumbs & Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <!-- Breadcrumbs -->
            <div class="flex items-center space-x-2 text-[11px] text-slate-400 font-semibold mb-1">
                <a href="{{ route('dashboard') }}" class="hover:text-slate-600 dark:hover:text-slate-200 transition">Dashboard</a>
                <span>/</span>
                <span class="text-slate-500">Academics</span>
                <span>/</span>
                <span class="text-slate-800 dark:text-slate-200 font-bold">Virtual Classes</span>
            </div>

            <div class="flex items-center space-x-2.5">
                <span class="w-8 h-8 rounded-lg flex items-center justify-center text-white text-sm shadow-xs" style="background: var(--system_primary_gradient);">
                    <i class="ti-video-camera"></i>
                </span>
                <h1 class="text-lg sm:text-xl font-black text-slate-900 dark:text-white tracking-tight">
                    Virtual Classes &amp; Live Sessions
                </h1>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold text-white shadow-2xs" style="background: var(--system_primary_gradient);">
                    Infix VirtualClass
                </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Conduct live interactive webinars, Zoom meetings, Google Meet, and Jitsi classrooms across batches and grades.
            </p>
        </div>

        <div class="flex items-center space-x-2">
            @if(Auth::user()->isAdmin() || Auth::user()->isTeacher())
            <button type="button" 
                    @click="scheduleModalOpen = true" 
                    class="primary-btn inline-flex items-center space-x-1.5 shadow-sm" style="padding: 0.4rem 1rem; font-size: 0.8rem;">
                <i class="ti-plus"></i>
                <span>Schedule Live Class</span>
            </button>
            @endif
        </div>
    </div>

    <!-- 4 KPI Summary Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-3">
        <!-- 1. Total Classes -->
        <div class="p-3.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs flex items-center space-x-3">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white shrink-0 shadow-xs" style="background: var(--system_primary_gradient);">
                <i class="ti-video-camera text-base"></i>
            </div>
            <div class="min-w-0">
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Sessions</div>
                <div class="text-xl font-black text-slate-900 dark:text-white leading-tight font-mono">{{ $stats['total'] }}</div>
            </div>
        </div>

        <!-- 2. Live Now -->
        <div class="p-3.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs flex items-center space-x-3">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-rose-500 text-white shrink-0 shadow-xs relative">
                <i class="ti-signal text-base"></i>
                @if($stats['live_count'] > 0)
                <span class="absolute -top-1 -right-1 w-3 h-3 bg-rose-500 rounded-full animate-ping"></span>
                @endif
            </div>
            <div class="min-w-0">
                <div class="text-[10px] font-bold text-rose-500 uppercase tracking-wider flex items-center space-x-1">
                    <span>Live Right Now</span>
                </div>
                <div class="text-xl font-black text-rose-600 dark:text-rose-400 leading-tight font-mono">{{ $stats['live_count'] }}</div>
            </div>
        </div>

        <!-- 3. Upcoming -->
        <div class="p-3.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs flex items-center space-x-3">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-blue-500 text-white shrink-0 shadow-xs">
                <i class="ti-time text-base"></i>
            </div>
            <div class="min-w-0">
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Upcoming</div>
                <div class="text-xl font-black text-slate-900 dark:text-white leading-tight font-mono">{{ $stats['upcoming_count'] }}</div>
            </div>
        </div>

        <!-- 4. Completed Archive -->
        <div class="p-3.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs flex items-center space-x-3">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-emerald-500 text-white shrink-0 shadow-xs">
                <i class="ti-check text-base"></i>
            </div>
            <div class="min-w-0">
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Completed Archive</div>
                <div class="text-xl font-black text-slate-900 dark:text-white leading-tight font-mono">{{ $stats['completed_count'] }}</div>
            </div>
        </div>
    </div>

    <!-- Filter Bar: Status Tabs & Host Dropdown -->
    <div class="p-3 rounded-xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs flex flex-col md:flex-row items-center justify-between gap-2.5">
        <!-- Status Tabs -->
        <div class="flex items-center space-x-1.5 overflow-x-auto w-full md:w-auto pb-1 md:pb-0 scrollbar-none">
            <a href="{{ route('virtual-classes.index', array_filter(['status' => 'all', 'host' => request('host'), 'search' => request('search')])) }}" 
               class="px-3 py-1 rounded-full text-xs font-bold transition shrink-0 {{ $status === 'all' ? 'text-white shadow-xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200' }}"
               @if($status === 'all') style="background: var(--system_primary_gradient);" @endif>
                All Classes ({{ $stats['total'] }})
            </a>
            <a href="{{ route('virtual-classes.index', array_filter(['status' => 'live', 'host' => request('host'), 'search' => request('search')])) }}" 
               class="px-3 py-1 rounded-full text-xs font-bold transition shrink-0 {{ $status === 'live' ? 'text-white shadow-xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200' }}"
               @if($status === 'live') style="background: var(--system_primary_gradient);" @endif>
                🔴 Live Now ({{ $stats['live_count'] }})
            </a>
            <a href="{{ route('virtual-classes.index', array_filter(['status' => 'upcoming', 'host' => request('host'), 'search' => request('search')])) }}" 
               class="px-3 py-1 rounded-full text-xs font-bold transition shrink-0 {{ $status === 'upcoming' ? 'text-white shadow-xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200' }}"
               @if($status === 'upcoming') style="background: var(--system_primary_gradient);" @endif>
                🕒 Upcoming ({{ $stats['upcoming_count'] }})
            </a>
            <a href="{{ route('virtual-classes.index', array_filter(['status' => 'completed', 'host' => request('host'), 'search' => request('search')])) }}" 
               class="px-3 py-1 rounded-full text-xs font-bold transition shrink-0 {{ $status === 'completed' ? 'text-white shadow-xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200' }}"
               @if($status === 'completed') style="background: var(--system_primary_gradient);" @endif>
                ✓ Completed ({{ $stats['completed_count'] }})
            </a>
        </div>

        <!-- Search & Host Filter -->
        <form method="GET" action="{{ route('virtual-classes.index') }}" class="flex items-center space-x-2 w-full md:w-auto">
            <input type="hidden" name="status" value="{{ $status }}">
            <select name="host" onchange="this.form.submit()" class="text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-1.5 px-2 text-slate-700 dark:text-slate-300">
                <option value="all">All Meeting Hosts</option>
                <option value="zoom" {{ request('host') === 'zoom' ? 'selected' : '' }}>Zoom</option>
                <option value="google_meet" {{ request('host') === 'google_meet' ? 'selected' : '' }}>Google Meet</option>
                <option value="jitsi" {{ request('host') === 'jitsi' ? 'selected' : '' }}>Jitsi Meet</option>
                <option value="in_app" {{ request('host') === 'in_app' ? 'selected' : '' }}>In-App Class</option>
                <option value="custom" {{ request('host') === 'custom' ? 'selected' : '' }}>Custom URL</option>
            </select>
            <div class="relative flex-1 md:w-56">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search class or topic..." 
                       class="w-full pl-7 pr-3 py-1.5 text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
                <i class="ti-search absolute left-2.5 top-2 text-slate-400 text-xs"></i>
            </div>
        </form>
    </div>

    <!-- Virtual Classes Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3.5">
        @forelse($virtualClasses as $class)
        @php
            $isLive = $class->isLiveNow();
            $isUpcoming = $class->start_time->isFuture();
        @endphp
        <div class="white-box p-4 {{ $isLive ? 'border-rose-400 dark:border-rose-800 shadow-md ring-1 ring-rose-400/30' : 'border border-slate-200/80 dark:border-slate-800 shadow-xs' }} flex flex-col justify-between group transition">
            <div>
                <!-- Top Row: Host badge & Status pill -->
                <div class="flex items-center justify-between gap-2 mb-2.5">
                    <span class="px-2.5 py-1 rounded-md text-[10px] font-bold flex items-center space-x-1.5
                        @if($class->host === 'zoom') bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 border border-blue-200 dark:border-blue-800
                        @elseif($class->host === 'google_meet') bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800
                        @elseif($class->host === 'jitsi') bg-amber-50 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300 border border-amber-200 dark:border-amber-800
                        @else bg-purple-50 text-purple-700 dark:bg-purple-950/50 dark:text-purple-300 border border-purple-200 dark:border-purple-800 @endif">
                        <i class="{{ $class->host_icon }}"></i>
                        <span>{{ $class->host_label }}</span>
                    </span>

                    @if($isLive)
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-rose-500 text-white flex items-center space-x-1 shadow-xs animate-pulse">
                        <span class="w-1.5 h-1.5 rounded-full bg-white"></span>
                        <span>Live Now</span>
                    </span>
                    @elseif($isUpcoming)
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 dark:bg-blue-950 text-blue-700 dark:text-blue-300">
                        Upcoming
                    </span>
                    @else
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-500">
                        Completed
                    </span>
                    @endif
                </div>

                <!-- Class Title -->
                <h3 class="text-sm font-black text-slate-900 dark:text-white leading-snug group-hover:text-indigo-600 transition">
                    {{ $class->title }}
                </h3>

                @if($class->description)
                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 line-clamp-2">
                    {{ $class->description }}
                </p>
                @endif

                <!-- Meta Pills -->
                <div class="flex flex-wrap gap-1.5 mt-3 text-[10px]">
                    @if($class->academicClass)
                    <span class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold">
                        🏫 {{ $class->academicClass->display_name }}
                    </span>
                    @endif
                    @if($class->batch)
                    <span class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold">
                        🎒 {{ $class->batch->name }}
                    </span>
                    @endif
                    <span class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 font-semibold">
                        ⏱️ {{ $class->duration }} mins
                    </span>
                    <span class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 font-semibold">
                        👥 Max {{ $class->capacity }}
                    </span>
                </div>

                <!-- Time Box -->
                <div class="p-2 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800 my-2.5 flex items-center justify-between text-xs">
                    <div class="flex items-center space-x-1.5 text-slate-700 dark:text-slate-300 font-semibold">
                        <i class="ti-calendar text-slate-400"></i>
                        <span>{{ $class->start_time->format('D, M j, Y') }}</span>
                    </div>
                    <span class="font-mono font-bold text-slate-900 dark:text-white">
                        {{ $class->start_time->format('h:i A') }}
                    </span>
                </div>

                <!-- Instructor Pill -->
                <div class="flex items-center space-x-2 py-1">
                    <div class="w-6 h-6 rounded-full flex items-center justify-center font-bold text-[10px] text-white shadow-2xs" style="background: var(--system_primary_gradient);">
                        {{ strtoupper(substr($class->instructor?->name ?? 'F', 0, 1)) }}
                    </div>
                    <span class="text-xs font-bold text-slate-700 dark:text-slate-300 truncate">
                        {{ $class->instructor?->name ?? 'Faculty Incharge' }}
                    </span>
                </div>
            </div>

            <!-- Footer: Actions -->
            <div class="pt-2.5 mt-2.5 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-2">
                <a href="{{ route('virtual-classes.show', $class->id) }}" class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold transition">
                    Details
                </a>

                @if($class->join_url)
                <a href="{{ route('virtual-classes.join', $class->id) }}" target="_blank" 
                   class="flex-1 py-1.5 px-3 rounded-lg text-white font-bold text-xs text-center transition shadow-xs flex items-center justify-center space-x-1.5 {{ $isLive ? 'animate-bounce' : '' }}"
                   style="background: var(--system_primary_gradient);">
                    <i class="ti-control-play text-xs"></i>
                    <span>{{ $isLive ? 'Join Live Now' : 'Enter Classroom' }}</span>
                </a>
                @endif

                @if(Auth::user()->isAdmin() || Auth::id() === $class->instructor_id)
                <form method="POST" action="{{ route('virtual-classes.destroy', $class->id) }}" onsubmit="return confirm('Cancel this virtual class?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="p-1.5 rounded text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/40" title="Delete session">
                        <i class="ti-trash text-xs"></i>
                    </button>
                </form>
                @endif
            </div>
        </div>
        @empty
        <div class="col-span-full p-8 text-center bg-white dark:bg-slate-900 rounded-xl border border-dashed border-slate-300 dark:border-slate-800 space-y-3">
            <div class="w-12 h-12 rounded-full mx-auto flex items-center justify-center text-slate-400 bg-slate-100 dark:bg-slate-800 mb-2">
                <i class="ti-video-camera text-xl"></i>
            </div>
            <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200">No Virtual Classes Found</h3>
            <p class="text-xs text-slate-500 max-w-md mx-auto">
                No online classroom sessions match the current filter.
            </p>
            @if(Auth::user()->isAdmin() || Auth::user()->isTeacher())
            <button type="button" @click="scheduleModalOpen = true" class="primary-btn text-xs px-3 py-1.5 inline-flex items-center space-x-1">
                <i class="ti-plus"></i>
                <span>Schedule First Virtual Class</span>
            </button>
            @endif
        </div>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $virtualClasses->appends(request()->query())->links() }}
    </div>

    <!-- INFIX SCHEDULE LIVE CLASS MODAL -->
    @if(Auth::user()->isAdmin() || Auth::user()->isTeacher())
    <div x-show="scheduleModalOpen" style="display: none;"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div @click.away="scheduleModalOpen = false" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-lg w-full shadow-2xl overflow-hidden">
            <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between" style="background: var(--system_primary_gradient);">
                <div class="flex items-center space-x-2 text-white">
                    <i class="ti-video-camera font-bold"></i>
                    <div>
                        <h3 class="font-bold text-sm">Schedule Virtual Live Class</h3>
                        <span class="text-[10px] opacity-90">Zoom, Google Meet, Jitsi, or In-App Class</span>
                    </div>
                </div>
                <button type="button" @click="scheduleModalOpen = false" class="text-white/80 hover:text-white text-sm">
                    <i class="ti-close"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('virtual-classes.store') }}" class="p-4 space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Session Title <span class="text-rose-500">*</span></label>
                    <input type="text" name="title" required placeholder="e.g. Organic Chemistry Live Doubt Clearing" maxlength="200"
                           class="w-full text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2 text-slate-900 dark:text-white font-bold focus:ring-1" style="--tw-ring-color: var(--system_primery_color);">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Meeting Host Engine <span class="text-rose-500">*</span></label>
                        <select name="host" x-model="selectedHost" required class="w-full text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2 text-slate-900 dark:text-white">
                            <option value="zoom">Zoom Video Meeting</option>
                            <option value="google_meet">Google Meet</option>
                            <option value="jitsi">Jitsi Meet (Instant Free)</option>
                            <option value="in_app">In-App Live Stream</option>
                            <option value="custom">Custom Meeting Link</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Target Class / Grade</label>
                        <select name="class_id" class="w-full text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2 text-slate-900 dark:text-white">
                            <option value="">-- All Enrolled Students (School-Wide) --</option>
                            @foreach($academicClasses as $c)
                            <option value="{{ $c->id }}">{{ $c->display_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Start Date &amp; Time <span class="text-rose-500">*</span></label>
                        <input type="datetime-local" name="start_time" required value="{{ now()->addHour()->format('Y-m-d\TH:00') }}"
                               class="w-full text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2 text-slate-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Duration (Minutes) <span class="text-rose-500">*</span></label>
                        <input type="number" name="duration" value="60" min="15" max="480" required
                               class="w-full text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2 text-slate-900 dark:text-white">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Join URL / Room Link</label>
                    <input type="url" name="join_url" placeholder="https://zoom.us/j/... or https://meet.google.com/..."
                           class="w-full text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2 text-slate-900 dark:text-white">
                    <span class="text-[10px] text-slate-400">Leave blank for Jitsi to auto-generate an instant room.</span>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Meeting ID / Room Code</label>
                        <input type="text" name="meeting_id" placeholder="Optional"
                               class="w-full text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2 text-slate-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Meeting Password</label>
                        <input type="text" name="password" placeholder="Optional"
                               class="w-full text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2 text-slate-900 dark:text-white">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Session Description / Agenda</label>
                    <textarea name="description" rows="2" placeholder="Topics covered, prerequisite notes, or reference textbooks..."
                              class="w-full text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2 text-slate-900 dark:text-white"></textarea>
                </div>

                <div class="flex items-center justify-end space-x-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="scheduleModalOpen = false" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 transition">
                        Cancel
                    </button>
                    <button type="submit" class="primary-btn text-xs px-4 py-1.5 shadow-sm">
                        Instantiate Virtual Class
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

</div>
@endsection
