@php
    $currentRoute = request()->route()?->getName();
    $isDashboard = request()->routeIs('school.parent.dashboard') || request()->routeIs('school.parent.*');
    
    // Ensure $children and $activeChild are available across all parent views
    if (!isset($children) && Auth::check() && Auth::user()->isParent()) {
        $parentUser = Auth::user();
        $children = \App\Models\User::where('role', 'student')
            ->where(function ($q) use ($parentUser) {
                $q->where('parent_id', $parentUser->id)
                  ->orWhere('guardian_email', $parentUser->email);
            })
            ->with(['currentClass'])
            ->get();
        $activeChildId = session('parent_active_student_id', $children->first()?->id);
        $activeChild = $children->firstWhere('id', $activeChildId) ?? $children->first();
    }
@endphp

<div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-2.5 sm:p-3 shadow-xs mb-3">
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
        <!-- Navigation Tabs: Large Touch Targets, Readability, Client-side switching -->
        <nav class="flex items-center space-x-1.5 overflow-x-auto pb-1 lg:pb-0 scrollbar-none" aria-label="Parent Portal Navigation">
            @if($isDashboard)
                <button type="button" @click="setTab('overview')" 
                        :class="activeTab === 'overview' ? 'bg-slate-900 text-white dark:bg-emerald-600 shadow-sm' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800'"
                        class="px-4 py-2.5 rounded-xl text-xs sm:text-sm font-bold transition flex items-center space-x-2 whitespace-nowrap min-h-[42px]">
                    <span class="text-base">📊</span>
                    <span>Overview</span>
                </button>
                <button type="button" @click="setTab('attendance')" 
                        :class="activeTab === 'attendance' ? 'bg-slate-900 text-white dark:bg-emerald-600 shadow-sm' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800'"
                        class="px-4 py-2.5 rounded-xl text-xs sm:text-sm font-bold transition flex items-center space-x-2 whitespace-nowrap min-h-[42px]">
                    <span class="text-base">📋</span>
                    <span>Attendance</span>
                </button>
                <button type="button" @click="setTab('leaves')" 
                        :class="activeTab === 'leaves' ? 'bg-slate-900 text-white dark:bg-emerald-600 shadow-sm' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800'"
                        class="px-4 py-2.5 rounded-xl text-xs sm:text-sm font-bold transition flex items-center space-x-2 whitespace-nowrap min-h-[42px]">
                    <span class="text-base">📝</span>
                    <span>Leave Portal</span>
                </button>
                <button type="button" @click="setTab('exams')" 
                        :class="activeTab === 'exams' ? 'bg-slate-900 text-white dark:bg-emerald-600 shadow-sm' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800'"
                        class="px-4 py-2.5 rounded-xl text-xs sm:text-sm font-bold transition flex items-center space-x-2 whitespace-nowrap min-h-[42px]">
                    <span class="text-base">📜</span>
                    <span>Report Cards</span>
                </button>
                <button type="button" @click="setTab('timetable')" 
                        :class="activeTab === 'timetable' ? 'bg-slate-900 text-white dark:bg-emerald-600 shadow-sm' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800'"
                        class="px-4 py-2.5 rounded-xl text-xs sm:text-sm font-bold transition flex items-center space-x-2 whitespace-nowrap min-h-[42px]">
                    <span class="text-base">🗓️</span>
                    <span>Routine</span>
                </button>
                <button type="button" @click="setTab('fees')" 
                        :class="activeTab === 'fees' ? 'bg-slate-900 text-white dark:bg-emerald-600 shadow-sm' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800'"
                        class="px-4 py-2.5 rounded-xl text-xs sm:text-sm font-bold transition flex items-center space-x-2 whitespace-nowrap min-h-[42px]">
                    <span class="text-base">💳</span>
                    <span>Fees</span>
                </button>
            @else
                <a href="{{ route('school.parent.dashboard', ['tab' => 'overview']) }}" 
                   class="px-4 py-2.5 rounded-xl text-xs sm:text-sm font-bold transition flex items-center space-x-2 whitespace-nowrap min-h-[42px] {{ request()->routeIs('school.parent.*') ? 'bg-slate-900 text-white dark:bg-emerald-600 shadow-sm' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                    <span class="text-base">📊</span>
                    <span>Overview</span>
                </a>
                <a href="{{ route('school.parent.dashboard', ['tab' => 'attendance']) }}" 
                   class="px-4 py-2.5 rounded-xl text-xs sm:text-sm font-bold transition flex items-center space-x-2 whitespace-nowrap min-h-[42px] {{ request()->routeIs('school.attendance.*') ? 'bg-slate-900 text-white dark:bg-emerald-600 shadow-sm' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                    <span class="text-base">📋</span>
                    <span>Attendance</span>
                </a>
                <a href="{{ route('school.parent.dashboard', ['tab' => 'leaves']) }}" 
                   class="px-4 py-2.5 rounded-xl text-xs sm:text-sm font-bold transition flex items-center space-x-2 whitespace-nowrap min-h-[42px] {{ request()->routeIs('leaves.*') ? 'bg-slate-900 text-white dark:bg-emerald-600 shadow-sm' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                    <span class="text-base">📝</span>
                    <span>Leave Portal</span>
                </a>
                <a href="{{ route('school.parent.dashboard', ['tab' => 'exams']) }}" 
                   class="px-4 py-2.5 rounded-xl text-xs sm:text-sm font-bold transition flex items-center space-x-2 whitespace-nowrap min-h-[42px] {{ request()->routeIs('school.exams.*', 'school.report-cards.*') ? 'bg-slate-900 text-white dark:bg-emerald-600 shadow-sm' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                    <span class="text-base">📜</span>
                    <span>Report Cards</span>
                </a>
                <a href="{{ route('school.parent.dashboard', ['tab' => 'timetable']) }}" 
                   class="px-4 py-2.5 rounded-xl text-xs sm:text-sm font-bold transition flex items-center space-x-2 whitespace-nowrap min-h-[42px] {{ request()->routeIs('school.timetable.*') ? 'bg-slate-900 text-white dark:bg-emerald-600 shadow-sm' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                    <span class="text-base">🗓️</span>
                    <span>Routine</span>
                </a>
                <a href="{{ route('school.parent.dashboard', ['tab' => 'fees']) }}" 
                   class="px-4 py-2.5 rounded-xl text-xs sm:text-sm font-bold transition flex items-center space-x-2 whitespace-nowrap min-h-[42px] {{ request()->routeIs('school.fees.*') ? 'bg-slate-900 text-white dark:bg-emerald-600 shadow-sm' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                    <span class="text-base">💳</span>
                    <span>Fees</span>
                </a>
            @endif
        </nav>

        <!-- Multi-Student Selector (Consistent Across All Pages) -->
        @if(isset($children) && $children->count() > 1)
        <div class="flex items-center space-x-2 border-t lg:border-t-0 lg:border-l border-slate-200 dark:border-slate-800 pt-2 lg:pt-0 lg:pl-3 flex-shrink-0">
            <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider flex items-center space-x-1">
                <span>👨‍👩‍👧</span>
                <span class="hidden sm:inline">Student:</span>
            </span>

            <div class="relative">
                <select onchange="const url = new URL(window.location); url.searchParams.set('child_id', this.value); if(typeof activeTab !== 'undefined') url.searchParams.set('tab', activeTab); window.location.href = url.toString();"
                        aria-label="Select Student"
                        class="px-3.5 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-xs sm:text-sm font-bold text-slate-900 dark:text-white cursor-pointer focus:ring-2 focus:ring-emerald-500 shadow-2xs">
                    @foreach($children as $child)
                    <option value="{{ $child->id }}" {{ (isset($activeChild) && $activeChild->id === $child->id) ? 'selected' : '' }}>
                        {{ $child->name }} — {{ $child->currentClass?->display_name ?? 'Class' }}
                    </option>
                    @endforeach
                </select>
            </div>

            <!-- Quick Pill Buttons for Instant Tap -->
            <div class="hidden xl:flex items-center space-x-1">
                @foreach($children as $child)
                <a href="{{ request()->fullUrlWithQuery(['child_id' => $child->id]) }}" 
                   title="{{ $child->name }} ({{ $child->currentClass?->display_name ?? 'Class' }})"
                   class="px-2.5 py-1.5 rounded-lg text-xs font-bold transition whitespace-nowrap {{ (isset($activeChild) && $activeChild->id === $child->id) ? 'bg-emerald-600 text-white shadow-xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                    {{ Str::limit($child->name, 12) }}
                </a>
                @endforeach
            </div>
        </div>
        @elseif(isset($activeChild))
        <div class="hidden sm:flex items-center space-x-2 text-xs font-semibold text-slate-600 dark:text-slate-300 border-t lg:border-t-0 lg:border-l border-slate-200 dark:border-slate-800 pt-2 lg:pt-0 lg:pl-3">
            <span class="w-7 h-7 rounded-lg bg-emerald-600 text-white text-xs font-black flex items-center justify-center flex-shrink-0">
                {{ strtoupper(substr($activeChild->name, 0, 1)) }}
            </span>
            <div class="truncate">
                <span class="font-bold text-slate-900 dark:text-white block truncate">{{ $activeChild->name }}</span>
                <span class="text-[11px] text-slate-400 block truncate">{{ $activeChild->currentClass?->display_name ?? 'Enrolled' }}</span>
            </div>
        </div>
        @endif
    </div>
</div>
