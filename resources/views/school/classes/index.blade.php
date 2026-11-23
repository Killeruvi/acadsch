@extends('layouts.app')

@section('title', 'Classes & Sections Management — ' . ($school['name'] ?? 'Infix LMS'))

@section('content')
<div class="max-w-[1600px] mx-auto space-y-4" x-data="{
    activeTab: '{{ $tab ?? 'classes' }}',
    createClassOpen: {{ ($errors->has('section_ids') || $errors->has('grade_level')) ? 'true' : 'false' }},
    createSectionOpen: {{ ($errors->has('name') || $errors->has('stream')) ? 'true' : 'false' }},
    viewMode: 'grid',
    selectedStream: {{ Js::from($defaultStream) }},
    formGrade: '',
    formClassName: '',
    classError: '',
    sectionError: '',
    sectionSuccess: '',
    isSubmittingSection: false,
    allSections: {{ Js::from($allReusableSections ?? $reusableSections) }},
    classesList: {{ Js::from($allExistingClasses ?? $classes->items()) }},
    selectedSectionIds: [],
    updateClassName() {
        this.formClassName = this.formGrade ? 'Class ' + this.formGrade : '';
    },
    get filteredSections() {
        return this.allSections.filter(s => (s.stream || '').toLowerCase() === (this.selectedStream || '').toLowerCase());
    },
    isClassAlreadyExists(grade, stream, sectionName) {
        return this.classesList.some(c => 
            parseInt(c.grade_level) === parseInt(grade) && 
            (c.stream || '').toLowerCase() === (stream || '').toLowerCase() && 
            (c.section || '').toLowerCase() === (sectionName || '').toLowerCase()
        );
    },
    async submitSectionForm(event) {
        event.preventDefault();
        this.sectionError = '';
        this.sectionSuccess = '';
        this.isSubmittingSection = true;
        const form = event.target;
        const formData = new FormData(form);

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']')?.content || formData.get('_token')
                },
                body: formData
            });

            const data = await response.json();

            if (response.ok && data.success) {
                const exists = this.allSections.some(s => s.id === data.section.id);
                if (!exists) {
                    this.allSections.push(data.section);
                }
                const secId = Number(data.section.id);
                if (!this.selectedSectionIds.includes(secId)) {
                    this.selectedSectionIds.push(secId);
                }
                this.sectionSuccess = data.message || 'Section created successfully!';
                form.reset();
                setTimeout(() => {
                    this.createSectionOpen = false;
                    this.sectionSuccess = '';
                }, 800);
            } else {
                let msg = data.message || 'Failed to create section.';
                if (data.errors) {
                    msg = Object.values(data.errors).flat().join(' ');
                }
                this.sectionError = msg;
            }
        } catch (err) {
            form.submit();
        } finally {
            this.isSubmittingSection = false;
        }
    }
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
                <span class="text-slate-800 dark:text-slate-200 font-bold">Classes &amp; Sections</span>
            </div>

            <div class="flex items-center space-x-2.5">
                <span class="w-8 h-8 rounded-lg flex items-center justify-center text-white text-sm shadow-xs" style="background: var(--system_primary_gradient);">
                    <i class="ti-blackboard"></i>
                </span>
                <h1 class="text-lg sm:text-xl font-black text-slate-900 dark:text-white tracking-tight">
                    Classes &amp; Sections Management
                </h1>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold text-white shadow-2xs" style="background: var(--system_primary_gradient);">
                    Infix Academic
                </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Configure grade divisions, assign homeroom teachers, allocate room capacities, and manage reusable sections.
            </p>
        </div>

        <div class="flex items-center flex-wrap gap-2">
            <!-- View Mode Switcher -->
            <div class="inline-flex items-center p-0.5 rounded-lg bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                <button type="button" @click="viewMode = 'grid'" :class="viewMode === 'grid' ? 'bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-xs' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'" class="px-2.5 py-1 rounded text-xs font-semibold transition">
                    <i class="ti-view-grid mr-1"></i> Grid
                </button>
                <button type="button" @click="viewMode = 'table'" :class="viewMode === 'table' ? 'bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-xs' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'" class="px-2.5 py-1 rounded text-xs font-semibold transition">
                    <i class="ti-view-list mr-1"></i> Table
                </button>
            </div>

            <a href="{{ route('school.sections.index') }}" class="px-3 py-1.5 rounded-lg text-xs font-bold bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition flex items-center space-x-1.5">
                <i class="ti-layout-grid3"></i>
                <span>Sections Hub</span>
            </a>

            @if(Auth::user()->isAdmin())
            <button type="button" 
                    @click="createSectionOpen = true"
                    class="px-3 py-1.5 rounded-lg text-xs font-bold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 hover:bg-indigo-100 border border-indigo-200 dark:border-indigo-800 transition flex items-center space-x-1.5">
                <i class="ti-plus text-xs"></i>
                <span>Create Section</span>
            </button>
            <button type="button" 
                    @click="createClassOpen = true"
                    class="primary-btn inline-flex items-center space-x-1.5 shadow-sm" style="padding: 0.4rem 0.9rem; font-size: 0.8rem;">
                <i class="ti-plus"></i>
                <span>Add Class (from Sections)</span>
            </button>
            @endif
        </div>
    </div>

    <!-- Infix KPI Summary Strip -->
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-2.5 sm:gap-3">
        <div class="p-3 rounded-xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs flex items-center space-x-3">
            <div class="w-9 h-9 rounded-lg flex items-center justify-center text-white shrink-0 shadow-xs" style="background: var(--system_primary_gradient);">
                <i class="ti-blackboard text-sm"></i>
            </div>
            <div class="min-w-0">
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Active Classes</div>
                <div class="text-lg font-black text-slate-900 dark:text-white leading-tight">{{ $stats['total_classes'] }}</div>
            </div>
        </div>

        <div class="p-3 rounded-xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs flex items-center space-x-3">
            <div class="w-9 h-9 rounded-lg flex items-center justify-center bg-indigo-500 text-white shrink-0 shadow-xs">
                <i class="ti-layout-grid3 text-sm"></i>
            </div>
            <div class="min-w-0">
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Reusable Sections</div>
                <div class="text-lg font-black text-indigo-600 dark:text-indigo-400 leading-tight">{{ $stats['total_sections'] }}</div>
            </div>
        </div>

        <div class="p-3 rounded-xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs flex items-center space-x-3">
            <div class="w-9 h-9 rounded-lg flex items-center justify-center bg-emerald-500 text-white shrink-0 shadow-xs">
                <i class="ti-user text-sm"></i>
            </div>
            <div class="min-w-0">
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Enrolled Scholars</div>
                <div class="text-lg font-black text-slate-900 dark:text-white leading-tight">{{ $stats['total_students'] }}</div>
            </div>
        </div>

        <div class="p-3 rounded-xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs flex items-center space-x-3">
            <div class="w-9 h-9 rounded-lg flex items-center justify-center bg-amber-500 text-white shrink-0 shadow-xs">
                <i class="ti-book text-sm"></i>
            </div>
            <div class="min-w-0">
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Curriculum Subjects</div>
                <div class="text-lg font-black text-slate-900 dark:text-white leading-tight">{{ $stats['total_subjects'] }}</div>
            </div>
        </div>

        <div class="p-3 rounded-xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs flex items-center space-x-3">
            <div class="w-9 h-9 rounded-lg flex items-center justify-center bg-rose-500 text-white shrink-0 shadow-xs">
                <i class="ti-id-badge text-sm"></i>
            </div>
            <div class="min-w-0">
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Assigned Faculty</div>
                <div class="text-lg font-black text-rose-600 dark:text-rose-400 leading-tight">{{ $stats['assigned_teachers'] }}</div>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs: Classes vs Reusable Sections -->
    <div class="flex items-center space-x-2 border-b border-slate-200 dark:border-slate-800 pb-2">
        <button type="button" 
                @click="activeTab = 'classes'" 
                :class="activeTab === 'classes' ? 'text-white shadow-xs font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white bg-slate-100 dark:bg-slate-800'"
                :style="activeTab === 'classes' ? 'background: var(--system_primary_gradient);' : ''"
                class="px-4 py-2 rounded-xl text-xs transition flex items-center space-x-2">
            <i class="ti-blackboard"></i>
            <span>Classes &amp; Sections ({{ $stats['total_classes'] }})</span>
        </button>

        <button type="button" 
                @click="activeTab = 'sections'" 
                :class="activeTab === 'sections' ? 'text-white shadow-xs font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white bg-slate-100 dark:bg-slate-800'"
                :style="activeTab === 'sections' ? 'background: var(--system_primary_gradient);' : ''"
                class="px-4 py-2 rounded-xl text-xs transition flex items-center space-x-2">
            <i class="ti-layout-grid3"></i>
            <span>Reusable Sections Library ({{ $stats['total_sections'] }})</span>
        </button>
    </div>

    <!-- TAB 1: CLASSES LIST -->
    <div x-show="activeTab === 'classes'" class="space-y-3">
        <!-- Live Search & Filter Row -->
        <div class="p-3 rounded-xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs flex flex-col md:flex-row items-center justify-between gap-2.5">
            <form method="GET" action="{{ route('school.classes.index') }}" class="relative w-full md:w-96">
                <input type="hidden" name="tab" value="classes">
                <input type="text" name="search" value="{{ $search }}" placeholder="Search grade, stream, section, room..."
                       oninput="clearTimeout(this._searchTimer); this._searchTimer = setTimeout(() => this.form.submit(), 400);"
                       class="w-full pl-8 pr-8 py-1.5 text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/80 text-slate-900 dark:text-white focus:outline-none focus:ring-1"
                       style="--tw-ring-color: var(--system_primery_color);">
                <i class="ti-search absolute left-2.5 top-2.5 text-slate-400 text-xs"></i>
                @if($search)
                <a href="{{ route('school.classes.index', ['tab' => 'classes']) }}" class="absolute right-2.5 top-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-xs">
                    <i class="ti-close"></i>
                </a>
                @endif
            </form>

            <div class="text-[11px] text-slate-400 flex items-center space-x-1.5 self-end md:self-auto">
                <i class="ti-info-alt text-xs"></i>
                <span>Format: Class = Grade + Stream + Assigned Section</span>
            </div>
        </div>

        <!-- Infix Modern Card Grid -->
        <div x-show="viewMode === 'grid'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3.5">
            @forelse($classes as $class)
            @php $fillPct = $class->capacity > 0 ? min(100, round(($class->students_count / $class->capacity) * 100)) : 0; @endphp
            <div class="white-box p-4 border border-slate-200/80 dark:border-slate-800 shadow-xs hover:border-slate-300 dark:hover:border-slate-700 transition flex flex-col justify-between group">
                <div>
                    <!-- Header with Grade Pill & Stream -->
                    <div class="flex items-start justify-between gap-2 mb-2">
                        <div class="flex items-center space-x-2.5">
                            <div class="w-10 h-10 rounded-lg flex items-center justify-center font-black text-sm text-white shadow-xs shrink-0" style="background: var(--system_primary_gradient);">
                                G{{ $class->grade_level }}
                            </div>
                            <div>
                                <h3 class="text-sm font-black text-slate-900 dark:text-white leading-tight">
                                    {{ $class->display_name }}
                                </h3>
                                <div class="flex items-center gap-1.5 mt-0.5">
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
                                        Section {{ $class->section }}
                                    </span>
                                    <span class="text-slate-300 dark:text-slate-700">&bull;</span>
                                    <span class="text-[10px] text-slate-400 font-mono">
                                        {{ $class->academic_year ?: 'Session not assigned' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Stream Pill -->
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold shrink-0
                            @if(strcasecmp($class->stream, 'Science') === 0) bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800
                            @elseif(strcasecmp($class->stream, 'Commerce') === 0) bg-amber-50 dark:bg-amber-950/50 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800
                            @elseif(strcasecmp($class->stream, 'Arts') === 0) bg-purple-50 dark:bg-purple-950/50 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-800
                            @else bg-blue-50 dark:bg-blue-950/50 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800 @endif">
                            {{ $class->stream ?: 'Stream not set' }}
                        </span>
                    </div>

                    <!-- Room & Capacity Gauge -->
                    <div class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800 my-2 space-y-1.5">
                        <div class="flex items-center justify-between text-xs">
                            <span class="flex items-center space-x-1.5 text-slate-600 dark:text-slate-300 font-medium">
                                <i class="ti-home text-slate-400 text-xs"></i>
                                <span>{{ $class->room_number ?: 'Room not assigned' }}</span>
                            </span>
                            <span class="font-bold text-slate-900 dark:text-white font-mono text-[11px]">
                                {{ $class->students_count }} / {{ $class->capacity }} Enrolled
                            </span>
                        </div>
                        <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-1.5 overflow-hidden">
                            <div class="h-1.5 rounded-full transition-all" style="width: {{ $fillPct }}%; background: var(--system_primary_gradient);"></div>
                        </div>
                    </div>

                    <!-- Homeroom Teacher Incharge -->
                    <div class="flex items-center justify-between text-[11px] py-1">
                        <div class="flex items-center space-x-2 min-w-0">
                            <div class="w-6 h-6 rounded-full flex items-center justify-center font-bold text-[10px] text-white shrink-0 shadow-2xs" style="background: var(--system_primary_gradient);">
                                {{ $class->classTeacher ? strtoupper(substr($class->classTeacher->name, 0, 1)) : '?' }}
                            </div>
                            <div class="truncate">
                                <span class="text-[9px] text-slate-400 uppercase font-bold block leading-none">Class Teacher</span>
                                <span class="font-bold text-slate-800 dark:text-slate-200 truncate block text-[11px] mt-0.5">
                                    {{ $class->classTeacher ? $class->classTeacher->name : 'Unassigned' }}
                                </span>
                            </div>
                        </div>

                        <span class="px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 text-[10px] font-bold shrink-0">
                            <i class="ti-book mr-0.5 text-[9px]"></i> {{ $class->subjects_count }} Subjects
                        </span>
                    </div>
                </div>

                <!-- Action Button Row -->
                <div class="pt-2.5 mt-2.5 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between gap-2">
                    <a href="{{ route('school.classes.show', $class->id) }}" 
                       class="flex-1 py-1.5 px-2.5 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 text-center font-bold text-xs transition flex items-center justify-center space-x-1.5">
                        <i class="ti-eye text-xs"></i>
                        <span>Roster &amp; Curriculum</span>
                    </a>
                    <a href="{{ route('school.attendance.take', $class->id) }}" 
                       class="py-1.5 px-3 rounded-lg text-white font-bold text-xs transition flex items-center space-x-1 shadow-2xs shrink-0"
                       style="background: var(--system_primary_gradient);"
                       title="Take Daily Attendance">
                        <i class="ti-check-box text-xs"></i>
                        <span>Roll Call</span>
                    </a>
                </div>
            </div>
            @empty
            <div class="col-span-full p-8 text-center bg-white dark:bg-slate-900 rounded-xl border border-dashed border-slate-300 dark:border-slate-800 space-y-3">
                <div class="w-12 h-12 rounded-full mx-auto flex items-center justify-center text-slate-400 bg-slate-100 dark:bg-slate-800 mb-2">
                    <i class="ti-blackboard text-xl"></i>
                </div>
                @if(auth()->user()->isTeacher() && !auth()->user()->isAdmin())
                <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200">No Assigned Classes Yet</h3>
                <p class="text-xs text-slate-500 max-w-md mx-auto">
                    Admin will assign you classes after a while. When assigned, your designated classes will appear here for student roster review and attendance marking.
                </p>
                @else
                <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200">No Classes Registered</h3>
                <p class="text-xs text-slate-500 max-w-md mx-auto">
                    To create a class, select an existing reusable section under the desired stream. No free-text typing required.
                </p>
                <div class="pt-2 flex items-center justify-center space-x-2">
                    <button type="button" @click="createSectionOpen = true" class="px-3 py-1.5 rounded-lg bg-indigo-600 text-white text-xs font-bold shadow-xs">
                        <i class="ti-plus mr-1"></i> Create Section First
                    </button>
                    <button type="button" @click="createClassOpen = true" class="primary-btn text-xs px-3 py-1.5 inline-flex items-center space-x-1">
                        <i class="ti-plus"></i>
                        <span>Add Class</span>
                    </button>
                </div>
                @endif
            </div>
            @endforelse
        </div>

        <!-- Infix Data Table Alternative View -->
        <div x-show="viewMode === 'table'" class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200/80 dark:border-slate-800 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50/80 dark:bg-slate-800/50 border-b border-slate-200/80 dark:border-slate-800 text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                            <th class="p-3">Class / Division</th>
                            <th class="p-3">Stream</th>
                            <th class="p-3">Section</th>
                            <th class="p-3">Room</th>
                            <th class="p-3">Homeroom Teacher</th>
                            <th class="p-3">Occupancy</th>
                            <th class="p-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium">
                        @forelse($classes as $c)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition">
                            <td class="p-3">
                                <div class="flex items-center space-x-2">
                                    <span class="w-6 h-6 rounded flex items-center justify-center font-bold text-white text-[11px] shadow-xs" style="background: var(--system_primary_gradient);">
                                        G{{ $c->grade_level }}
                                    </span>
                                    <a href="{{ route('school.classes.show', $c->id) }}" class="font-bold text-slate-900 dark:text-white hover:text-indigo-600 transition">
                                        {{ $c->display_name }}
                                    </a>
                                </div>
                            </td>
                            <td class="p-3">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold
                                    @if(strcasecmp($c->stream, 'Science') === 0) bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300
                                    @elseif(strcasecmp($c->stream, 'Commerce') === 0) bg-amber-50 dark:bg-amber-950/50 text-amber-700 dark:text-amber-300
                                    @elseif(strcasecmp($c->stream, 'Arts') === 0) bg-purple-50 dark:bg-purple-950/50 text-purple-700 dark:text-purple-300
                                    @else bg-blue-50 dark:bg-blue-950/50 text-blue-700 dark:text-blue-300 @endif">
                                    {{ $c->stream ?: 'Stream not set' }}
                                </span>
                            </td>
                            <td class="p-3 font-mono font-bold text-slate-800 dark:text-slate-200">
                                {{ $c->section }}
                            </td>
                            <td class="p-3 text-slate-600 dark:text-slate-300">
                                {{ $c->room_number ?: 'Room not assigned' }}
                            </td>
                            <td class="p-3">
                                {{ $c->classTeacher ? $c->classTeacher->name : 'Unassigned' }}
                            </td>
                            <td class="p-3">
                                <span class="font-mono font-bold text-slate-900 dark:text-white">{{ $c->students_count }} / {{ $c->capacity }}</span>
                            </td>
                            <td class="p-3 text-right space-x-1">
                                <a href="{{ route('school.attendance.take', $c->id) }}" class="text-xs px-2 py-1 rounded bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300 font-bold" title="Roll Call">
                                    <i class="ti-check-box"></i>
                                </a>
                                <a href="{{ route('school.classes.show', $c->id) }}" class="text-xs px-2 py-1 rounded text-white font-bold" style="background: var(--system_primary_gradient);">
                                    <i class="ti-eye"></i> Details
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="p-6 text-center text-slate-400">
                                No classes registered.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">
            {{ $classes->appends(['tab' => 'classes', 'search' => $search])->links() }}
        </div>
    </div>

    <!-- TAB 2: REUSABLE SECTIONS LIBRARY -->
    <div x-show="activeTab === 'sections'" class="space-y-4">
        <!-- Explanatory Banner -->
        <div class="p-4 rounded-xl bg-indigo-50 dark:bg-indigo-950/50 border border-indigo-200 dark:border-indigo-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-start space-x-3">
                <span class="w-8 h-8 rounded-lg flex items-center justify-center text-white text-sm shrink-0" style="background: var(--system_primary_gradient);">
                    <i class="ti-layout-grid3"></i>
                </span>
                <div>
                    <h3 class="text-xs sm:text-sm font-bold text-indigo-900 dark:text-indigo-200">Institutional Reusable Sections Library</h3>
                    <p class="text-[11px] text-indigo-700 dark:text-indigo-300 mt-0.5">
                        Sections are defined once per stream (A, B, C, etc.) and reused across academic years and classes. Prevents duplicate and inconsistent section names.
                    </p>
                </div>
            </div>
            <button type="button" 
                    @click="createSectionOpen = true"
                    class="primary-btn inline-flex items-center space-x-1.5 shadow-sm text-xs px-3 py-1.5 shrink-0">
                <i class="ti-plus"></i>
                <span>Create New Section</span>
            </button>
        </div>

        <!-- Sections Grouped By Stream -->
        <div class="space-y-3">
            @foreach($streams as $streamKey => $streamTitle)
            @php $sectionsInStream = $sectionsByStream[$streamKey] ?? collect(); @endphp
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-4 shadow-xs">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800 mb-3">
                    <div class="flex items-center space-x-2">
                        <span class="w-6 h-6 rounded-md flex items-center justify-center font-black text-xs text-white shadow-2xs" style="background: var(--system_primary_gradient);">
                            {{ substr($streamKey, 0, 1) }}
                        </span>
                        <h4 class="text-sm font-black text-slate-900 dark:text-white">
                            {{ $streamTitle }} Stream
                        </h4>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                            {{ $sectionsInStream->count() }} Section{{ $sectionsInStream->count() !== 1 ? 's' : '' }}
                        </span>
                    </div>

                    <button type="button" 
                            @click="selectedStream = '{{ $streamKey }}'; createSectionOpen = true"
                            class="text-xs font-bold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 flex items-center space-x-1">
                        <i class="ti-plus text-[10px]"></i>
                        <span>Add Section to {{ $streamTitle }}</span>
                    </button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2.5">
                    @forelse($sectionsInStream as $sec)
                    <div class="p-3 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800 flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <span class="w-7 h-7 rounded flex items-center justify-center font-bold text-xs text-white shadow-xs" style="background: var(--system_primary_gradient);">
                                {{ $sec->name }}
                            </span>
                            <div>
                                <span class="text-xs font-bold text-slate-900 dark:text-white block leading-tight">Section {{ $sec->name }}</span>
                                <span class="text-[10px] text-slate-400">{{ $sec->capacity ?: 'Not set' }} seats &bull; {{ $sec->room_number ?: 'Room not assigned' }}</span>
                            </div>
                        </div>

                        <span class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950 px-2 py-0.5 rounded">
                            {{ $sec->classes_count }} class{{ $sec->classes_count !== 1 ? 'es' : '' }}
                        </span>
                    </div>
                    @empty
                    <div class="col-span-full py-4 text-center text-xs text-slate-400">
                        No sections created under {{ $streamKey }} yet.
                    </div>
                    @endforelse
                </div>
            </div>
            @endforeach
        </div>

        <div class="mt-4">
            {{ $reusableSections->appends(['tab' => 'sections', 'search' => $search])->links() }}
        </div>
    </div>

    <!-- MODAL 1: CREATE REUSABLE SECTION -->
    @if(Auth::user()->isAdmin())
    <div x-show="createSectionOpen" style="display: none;"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div @click.away="createSectionOpen = false" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-md w-full shadow-2xl overflow-hidden">
            <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between" style="background: var(--system_primary_gradient);">
                <div class="flex items-center space-x-2 text-white">
                    <i class="ti-plus font-bold"></i>
                    <h3 class="font-bold text-sm">Create Reusable Section</h3>
                </div>
                <button type="button" @click="createSectionOpen = false" class="text-white/80 hover:text-white text-sm">
                    <i class="ti-close"></i>
                </button>
            </div>

            <template x-if="sectionSuccess">
                <div class="m-4 p-2.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-xs font-bold flex items-center space-x-2">
                    <i class="ti-check"></i>
                    <span x-text="sectionSuccess"></span>
                </div>
            </template>

            <template x-if="sectionError">
                <div class="m-4 p-2.5 rounded-xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-300 text-xs font-bold flex items-center space-x-2">
                    <i class="ti-alert"></i>
                    <span x-text="sectionError"></span>
                </div>
            </template>

            <form method="POST" action="{{ route('school.sections.store') }}" @submit="submitSectionForm($event)" class="p-4 space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Academic Stream <span class="text-rose-500">*</span></label>
                    <select name="stream" x-model="selectedStream" required class="w-full text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2 text-slate-900 dark:text-white focus:ring-1" style="--tw-ring-color: var(--system_primery_color);">
                        @foreach($streams as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Section Letter <span class="text-rose-500">*</span></label>
                    <select name="name" required class="w-full text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2 text-slate-900 dark:text-white font-bold focus:ring-1" style="--tw-ring-color: var(--system_primery_color);">
                        @foreach(range('A', 'Z') as $letter)
                            <option value="{{ $letter }}">Section {{ $letter }}</option>
                        @endforeach
                    </select>
                    <span class="text-[10px] text-slate-400">Single letter identifier (A, B, C, D...)</span>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Default Capacity</label>
                        <input type="number" name="capacity" min="{{ config('school.capacity.minimum') }}" max="{{ config('school.capacity.maximum') }}" required
                               class="w-full text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2 text-slate-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Default Room</label>
                        <input type="text" name="room_number" placeholder="e.g. Room 204"
                               class="w-full text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2 text-slate-900 dark:text-white">
                    </div>
                </div>

                <div class="flex items-center justify-end space-x-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="createSectionOpen = false" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 transition">
                        Cancel
                    </button>
                    <button type="submit" :disabled="isSubmittingSection" class="primary-btn text-xs px-4 py-1.5 shadow-sm">
                        <span x-text="isSubmittingSection ? 'Saving...' : 'Save Reusable Section'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2: CREATE CLASS (SECTION SELECTOR FLOW) -->
    <div x-show="createClassOpen" style="display: none;"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div @click.away="createClassOpen = false" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-lg w-full shadow-2xl overflow-hidden">
            <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between" style="background: var(--system_primary_gradient);">
                <div class="flex items-center space-x-2 text-white">
                    <i class="ti-blackboard font-bold"></i>
                    <div>
                        <h3 class="font-bold text-sm">Add New Class</h3>
                        <span class="text-[10px] opacity-90">Class = Grade + Stream + Selected Existing Section(s)</span>
                    </div>
                </div>
                <button type="button" @click="createClassOpen = false" class="text-white/80 hover:text-white text-sm">
                    <i class="ti-close"></i>
                </button>
            </div>

            <template x-if="classError">
                <div class="m-4 p-2.5 rounded-xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-300 text-xs font-bold flex items-center space-x-2">
                    <i class="ti-alert"></i>
                    <span x-text="classError"></span>
                </div>
            </template>

            <form method="POST" action="{{ route('school.classes.store') }}" 
                  @submit="if (selectedSectionIds.length === 0) { $event.preventDefault(); classError = 'Please select at least one reusable section checkbox.'; return false; }"
                  class="p-4 space-y-3">
                @csrf
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Grade Level <span class="text-rose-500">*</span></label>
                        <select name="grade_level" x-model="formGrade" @change="updateClassName()" required
                                class="w-full text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2 text-slate-900 dark:text-white">
                            <option value="">-- Select grade --</option>
                            @foreach(config('school.grade_levels', []) as $g)<option value="{{ $g }}">Grade {{ $g }}</option>@endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Academic Stream <span class="text-rose-500">*</span></label>
                        <select name="stream" x-model="selectedStream" required
                                class="w-full text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2 text-slate-900 dark:text-white">
                            @foreach($streams as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Class Display Name</label>
                    <input type="text" name="name" x-model="formClassName" placeholder="e.g. Class 10" maxlength="100"
                           class="w-full text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2 text-slate-900 dark:text-white">
                </div>

                <!-- Reusable Sections Multi-Selector -->
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                            Select Section(s) to Instantiate <span class="text-rose-500">*</span>
                        </label>
                        <button type="button" @click="createSectionOpen = true" class="text-[11px] text-indigo-600 hover:text-indigo-700 font-bold">
                            + New Section
                        </button>
                    </div>

                    <div class="p-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 max-h-40 overflow-y-auto space-y-1.5">
                        <template x-for="sec in filteredSections" :key="sec.id">
                            <label class="flex items-center justify-between p-2 rounded-md bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 cursor-pointer hover:border-indigo-400 transition">
                                <div class="flex items-center space-x-2">
                                    <input type="checkbox" name="section_ids[]" :value="sec.id" x-model="selectedSectionIds"
                                           class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                    <span class="text-xs font-bold text-slate-800 dark:text-slate-200" x-text="'Section ' + sec.name"></span>
                                </div>
                                <span class="text-[10px] text-slate-400" x-text="(sec.room_number ? sec.room_number + ' • ' : '') + sec.capacity + ' seats'"></span>
                            </label>
                        </template>

                        <div x-show="filteredSections.length === 0" class="text-center py-3 text-xs text-slate-400">
                            No sections found under <span class="font-bold" x-text="selectedStream"></span> stream.
                            <button type="button" @click="createSectionOpen = true" class="text-indigo-600 font-bold hover:underline ml-1">Create Section</button>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Academic Session <span class="text-rose-500">*</span></label>
                    <select name="academic_year" required class="w-full text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2 text-slate-900 dark:text-white">
                        <option value="">Select configured academic session</option>
                        @foreach($academicYears as $year)
                            <option value="{{ $year }}" {{ $year === $currentSession ? 'selected' : '' }}>{{ $year }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Room Number</label>
                        <input type="text" name="room_number" placeholder="e.g. Room 102"
                               class="w-full text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2 text-slate-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Default Capacity</label>
                        <input type="number" name="capacity" min="{{ config('school.capacity.minimum') }}" max="{{ config('school.capacity.maximum') }}" required
                               class="w-full text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2 text-slate-900 dark:text-white">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Homeroom Class Teacher</label>
                    <select name="class_teacher_id" class="w-full text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2 text-slate-900 dark:text-white">
                        <option value="">-- Assign Homeroom Incharge (Optional) --</option>
                        @foreach($teachers as $teacher)
                        <option value="{{ $teacher->id }}">{{ $teacher->name }} ({{ $teacher->email }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center justify-end space-x-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="createClassOpen = false" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 transition">
                        Cancel
                    </button>
                    <button type="submit" class="primary-btn text-xs px-4 py-1.5 shadow-sm">
                        Instantiate Class
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

</div>
@endsection
