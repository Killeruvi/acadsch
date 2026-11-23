@extends('layouts.app')

@section('title', 'Curriculum Subjects — ' . ($school['name'] ?? 'Infix LMS'))

@section('content')
<div class="max-w-[1600px] mx-auto space-y-4" x-data="{
    createModalOpen: {{ $errors->any() ? 'true' : 'false' }},
    viewMode: 'table'
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
                <span class="text-slate-800 dark:text-slate-200 font-bold">School Subjects</span>
            </div>

            <div class="flex items-center space-x-2.5">
                <span class="w-8 h-8 rounded-lg flex items-center justify-center text-white text-sm shadow-xs" style="background: var(--system_primary_gradient);">
                    <i class="ti-book"></i>
                </span>
                <h1 class="text-lg sm:text-xl font-black text-slate-900 dark:text-white tracking-tight">
                    Academic Subjects Directory
                </h1>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold text-white shadow-2xs" style="background: var(--system_primary_gradient);">
                    Infix Curriculum
                </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Define master academic subjects, syllabus categories, default weekly lecture periods, and class allocations.
            </p>
        </div>

        <div class="flex items-center space-x-2">
            <!-- View Mode Switcher -->
            <div class="inline-flex items-center p-0.5 rounded-lg bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                <button type="button" @click="viewMode = 'table'" :class="viewMode === 'table' ? 'bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-xs' : 'text-slate-500'" class="px-2.5 py-1 rounded text-xs font-semibold transition">
                    <i class="ti-view-list mr-1"></i> Table
                </button>
                <button type="button" @click="viewMode = 'grid'" :class="viewMode === 'grid' ? 'bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-xs' : 'text-slate-500'" class="px-2.5 py-1 rounded text-xs font-semibold transition">
                    <i class="ti-view-grid mr-1"></i> Grid
                </button>
            </div>

            @if(Auth::user()->isAdmin())
            <button type="button" 
                    @click="createModalOpen = true" 
                    class="primary-btn inline-flex items-center space-x-1.5 shadow-sm" style="padding: 0.4rem 1rem; font-size: 0.8rem;">
                <i class="ti-plus"></i>
                <span>Add New Subject</span>
            </button>
            @endif
        </div>
    </div>

    <!-- 4 Infix KPI Summary Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-3">
        <div class="white-box single-summery p-3.5 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs flex items-center space-x-3">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white shrink-0 shadow-xs" style="background: var(--system_primary_gradient);">
                <i class="ti-book text-base"></i>
            </div>
            <div class="min-w-0">
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Subjects</div>
                <div class="gradient-color2 text-xl font-black leading-tight font-mono">{{ $stats['total_subjects'] }}</div>
            </div>
        </div>

        <div class="white-box single-summery p-3.5 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs flex items-center space-x-3">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-indigo-500 text-white shrink-0 shadow-xs">
                <i class="ti-direction-alt text-base"></i>
            </div>
            <div class="min-w-0">
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Academic Streams</div>
                <div class="gradient-color2 text-xl font-black leading-tight font-mono">{{ $stats['total_streams'] }}</div>
            </div>
        </div>

        <div class="white-box single-summery p-3.5 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs flex items-center space-x-3">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-emerald-500 text-white shrink-0 shadow-xs">
                <i class="ti-layers text-base"></i>
            </div>
            <div class="min-w-0">
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Disciplines &amp; Categories</div>
                <div class="gradient-color2 text-xl font-black leading-tight font-mono">{{ $stats['total_categories'] }}</div>
            </div>
        </div>

        <div class="white-box single-summery p-3.5 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs flex items-center space-x-3">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-amber-500 text-white shrink-0 shadow-xs">
                <i class="ti-blackboard text-base"></i>
            </div>
            <div class="min-w-0">
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Class Allocations</div>
                <div class="gradient-color2 text-xl font-black leading-tight font-mono">{{ $stats['active_assignments'] }}</div>
            </div>
        </div>
    </div>

    <!-- Filter Bar: Stream Pills & Search -->
    <div class="p-3 rounded-xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs flex flex-col md:flex-row items-center justify-between gap-2.5">
        <div class="flex items-center space-x-1.5 overflow-x-auto w-full md:w-auto pb-1 md:pb-0 scrollbar-none">
            <a href="{{ route('school.subjects.index') }}" 
               class="px-3 py-1 rounded-full text-xs font-bold transition shrink-0 {{ empty($selectedStream) || $selectedStream === 'all' ? 'text-white shadow-xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200' }}"
               @if(empty($selectedStream) || $selectedStream === 'all') style="background: var(--system_primary_gradient);" @endif>
                All Streams
            </a>
            @foreach($streams as $st)
            <a href="{{ route('school.subjects.index', ['stream' => $st]) }}" 
               class="px-3 py-1 rounded-full text-xs font-bold transition shrink-0 {{ ($selectedStream ?? '') === $st ? 'text-white shadow-xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200' }}"
               @if(($selectedStream ?? '') === $st) style="background: var(--system_primary_gradient);" @endif>
                {{ $st }}
            </a>
            @endforeach
        </div>

        @if(Auth::user()->isAdmin())
        <form method="GET" action="{{ route('school.subjects.index') }}">
            <input type="hidden" name="stream" value="{{ $selectedStream ?? 'all' }}">
            <select name="status" onchange="this.form.submit()" class="rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs p-2 text-slate-900 dark:text-white">
                <option value="active" {{ ($status ?? 'active') === 'active' ? 'selected' : '' }}>Active masters</option>
                <option value="inactive" {{ ($status ?? '') === 'inactive' ? 'selected' : '' }}>Inactive masters</option>
            </select>
        </form>
        @endif
        <form method="GET" action="{{ route('school.subjects.index') }}" class="relative w-full md:w-72">
            @if(!empty($selectedStream) && $selectedStream !== 'all')
                <input type="hidden" name="stream" value="{{ $selectedStream }}">
            @endif
            <input type="hidden" name="status" value="{{ $status ?? 'active' }}">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search subject name, code..."
                   class="w-full pl-8 pr-8 py-1.5 text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
            <i class="ti-search absolute left-2.5 top-2 text-slate-400 text-xs"></i>
        </form>
    </div>

    <!-- Main Content: Infix Data Table -->
    <div x-show="viewMode === 'table'" class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200/80 dark:border-slate-800 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50/80 dark:bg-slate-800/50 border-b border-slate-200/80 dark:border-slate-800 text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                        <th class="p-3">Subject Name</th>
                        <th class="p-3">Code</th>
                        <th class="p-3">Stream</th>
                        <th class="p-3">Category</th>
                        <th class="p-3">Periods / Week</th>
                        <th class="p-3">Active Deployments</th>
                        <th class="p-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium">
                    @forelse($subjects as $subj)
                    @php $allocCount = $assignedClassesMap[$subj->id] ?? 0; @endphp
                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition">
                        <td class="p-3">
                            <div class="flex items-center space-x-2">
                                <span class="w-7 h-7 rounded flex items-center justify-center font-bold text-xs text-white shadow-xs shrink-0" style="background: var(--system_primary_gradient);">
                                    {{ strtoupper(substr($subj->code, 0, 2)) }}
                                </span>
                                <div>
                                    <span class="font-bold text-slate-900 dark:text-white block leading-tight">{{ $subj->name }}</span>
                                    <span class="text-[10px] text-slate-400 truncate">{{ $subj->description }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="p-3">
                            <span class="font-mono font-bold text-slate-800 dark:text-slate-200 bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded text-[11px]">
                                {{ $subj->code }}
                            </span>
                        </td>
                        <td class="p-3">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold
                                @if(strcasecmp($subj->stream, 'Science') === 0) bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300
                                @elseif(strcasecmp($subj->stream, 'Commerce') === 0) bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-300
                                @elseif(strcasecmp($subj->stream, 'Arts') === 0) bg-purple-50 text-purple-700 dark:bg-purple-950 dark:text-purple-300
                                @else bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300 @endif">
                                {{ $subj->stream }}
                            </span>
                        </td>
                        <td class="p-3 text-slate-600 dark:text-slate-300">
                            {{ $subj->category ?: 'Uncategorized' }}<br><span class="text-[10px] text-slate-400">Default teacher: {{ $subj->defaultTeacher?->name ?? 'Not assigned' }}</span>
                        </td>
                        <td class="p-3 font-semibold text-slate-700 dark:text-slate-300">
                            {{ $subj->default_periods ?: 'Not configured' }} periods<br><span class="text-[10px] text-slate-400">Grades {{ $subj->min_grade }}–{{ $subj->max_grade }}</span>
                        </td>
                        <td class="p-3">
                            @if($allocCount > 0)
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300">
                                {{ $allocCount }} Class{{ $allocCount !== 1 ? 'es' : '' }} Linked
                            </span>
                            @else
                            <span class="text-[10px] text-slate-400 font-semibold">Unassigned</span>
                            @endif
                        </td>
                        <td class="p-3 text-right">
                            @if(Auth::user()->isAdmin())
                            @if($subj->is_active)
                            <details class="inline-block text-left"><summary class="cursor-pointer text-xs text-indigo-600">Edit</summary>
                                <form method="POST" action="{{ route('school.subjects.update', $subj->id) }}" class="absolute z-20 mt-1 right-4 w-72 rounded-lg border bg-white dark:bg-slate-900 p-3 shadow-xl space-y-2">@csrf @method('PATCH')
                                    <input name="name" value="{{ $subj->name }}" required maxlength="120" class="w-full rounded border p-2 text-xs bg-transparent">
                                    <input name="code" value="{{ $subj->code }}" required maxlength="30" class="w-full rounded border p-2 text-xs bg-transparent">
                                    <select name="stream" required class="w-full rounded border p-2 text-xs bg-transparent">@foreach($streams as $st)<option value="{{ $st }}" {{ $subj->stream === $st ? 'selected' : '' }}>{{ $st }}</option>@endforeach</select>
                                    <input name="category" value="{{ $subj->category ?: 'Uncategorized' }}<br><span class="text-[10px] text-slate-400">Default teacher: {{ $subj->defaultTeacher?->name ?? 'Not assigned' }}</span>" required maxlength="50" class="w-full rounded border p-2 text-xs bg-transparent">
                                    <input type="number" name="default_periods" value="{{ $subj->default_periods }}" min="1" max="40" required class="w-full rounded border p-2 text-xs bg-transparent" placeholder="Weekly periods">
                                    <select name="default_teacher_id" {{ ($assignedClassesMap[$subj->id] ?? 0) > 0 ? 'disabled' : '' }} class="w-full rounded border p-2 text-xs bg-transparent"><option value="">No global teacher assigned</option>@foreach($teachers as $teacher)<option value="{{ $teacher->id }}" {{ (int) $subj->default_teacher_id === (int) $teacher->id ? 'selected' : '' }}>{{ $teacher->name }}</option>@endforeach</select>
                                    @if(($assignedClassesMap[$subj->id] ?? 0) > 0)<p class="text-[10px] text-amber-600">Use Teacher Assignment to change the global teacher after class mappings exist.</p>@endif
                                    <div class="grid grid-cols-2 gap-2"><input type="number" name="min_grade" value="{{ $subj->min_grade }}" min="{{ min(config('school.grade_levels')) }}" max="{{ max(config('school.grade_levels')) }}" required class="w-full rounded border p-2 text-xs bg-transparent" placeholder="Minimum grade"><input type="number" name="max_grade" value="{{ $subj->max_grade }}" min="{{ min(config('school.grade_levels')) }}" max="{{ max(config('school.grade_levels')) }}" required class="w-full rounded border p-2 text-xs bg-transparent" placeholder="Maximum grade"></div>
                                    <textarea name="description" maxlength="1000" class="w-full rounded border p-2 text-xs bg-transparent">{{ $subj->description }}</textarea>
                                    <button class="rounded bg-indigo-600 text-white px-3 py-1 text-xs">Save changes</button>
                                </form>
                            </details>
                            <form method="POST" action="{{ route('school.subjects.destroy', $subj->id) }}" onsubmit="return confirm('Deactivate this subject master? Existing class records will remain.');" class="inline">@csrf @method('DELETE')<button class="p-1 text-rose-600 text-xs">Deactivate</button></form>
                            @else
                            <form method="POST" action="{{ route('school.subjects.activate', $subj->id) }}">@csrf @method('PATCH')<button class="p-1 text-emerald-600 text-xs">Reactivate</button></form>
                            @endif
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="p-8 text-center text-slate-400">
                            No curriculum subjects registered.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Alternative View: Compact Card Grid -->
    <div x-show="viewMode === 'grid'" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
        @foreach($subjects as $subj)
        @php $allocCount = $assignedClassesMap[$subj->id] ?? 0; @endphp
        <div class="p-3.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs flex flex-col justify-between group">
            <div>
                <div class="flex items-start justify-between gap-2 mb-2">
                    <span class="font-mono font-bold text-xs text-white px-2 py-0.5 rounded shadow-xs" style="background: var(--system_primary_gradient);">
                        {{ $subj->code }}
                    </span>
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400">
                        {{ $subj->category ?: 'Uncategorized' }}<br><span class="text-[10px] text-slate-400">Default teacher: {{ $subj->defaultTeacher?->name ?? 'Not assigned' }}</span>
                    </span>
                </div>
                <h4 class="text-sm font-black text-slate-900 dark:text-white leading-tight">
                    {{ $subj->name }}
                </h4>
                <p class="text-[11px] text-slate-400 mt-1 line-clamp-2">
                    {{ $subj->description }}
                </p>
            </div>
            <div class="pt-2 mt-2 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-[11px]">
                <span class="text-slate-500 font-semibold">{{ $subj->default_periods ?: 'Not configured' }} periods/wk</span>
                <span class="font-bold text-indigo-600 dark:text-indigo-400">{{ $allocCount }} classes</span>
            </div>
        </div>
        @endforeach
    </div>

    <div class="mt-4">
        {{ $subjects->appends(request()->query())->links() }}
    </div>

    <!-- CREATE SUBJECT MODAL -->
    @if(Auth::user()->isAdmin())
    <div x-show="createModalOpen" style="display: none;"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div @click.away="createModalOpen = false" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-md w-full shadow-2xl overflow-hidden">
            <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between" style="background: var(--system_primary_gradient);">
                <div class="flex items-center space-x-2 text-white">
                    <i class="ti-book font-bold"></i>
                    <div>
                        <h3 class="font-bold text-sm">Add New Curriculum Subject</h3>
                        <span class="text-[10px] opacity-90">Infix Subject Management</span>
                    </div>
                </div>
                <button type="button" @click="createModalOpen = false" class="text-white/80 hover:text-white text-sm">
                    <i class="ti-close"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('school.subjects.store') }}" class="p-4 space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Subject Name <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" required placeholder="e.g. Advanced Chemistry, Computer Science" maxlength="100"
                           class="w-full text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2 text-slate-900 dark:text-white font-bold">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Subject Code <span class="text-rose-500">*</span></label>
                        <input type="text" name="code" required placeholder="e.g. CHEM-02" maxlength="20"
                               class="w-full text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2 text-slate-900 dark:text-white font-mono uppercase font-bold">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Weekly Periods <span class="text-rose-500">*</span></label>
                        <input type="number" name="default_periods" min="1" max="40" required
                               class="w-full text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2 text-slate-900 dark:text-white">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Default Subject Teacher (Global)</label>
                    <select name="default_teacher_id" class="w-full text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2 text-slate-900 dark:text-white"><option value="">No global teacher assigned</option>@foreach($teachers as $teacher)<option value="{{ $teacher->id }}">{{ $teacher->name }}</option>@endforeach</select>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Minimum Grade</label>
                        <input type="number" name="min_grade" min="{{ min(config('school.grade_levels')) }}" max="{{ max(config('school.grade_levels')) }}" value="1" required class="w-full text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2 text-slate-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Maximum Grade</label>
                        <input type="number" name="max_grade" min="{{ min(config('school.grade_levels')) }}" max="{{ max(config('school.grade_levels')) }}" value="12" required class="w-full text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2 text-slate-900 dark:text-white">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Academic Stream <span class="text-rose-500">*</span></label>
                        <select name="stream" required class="w-full text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2 text-slate-900 dark:text-white">
                            @foreach($streams as $st)<option value="{{ $st }}">{{ $st === 'All' ? 'All Streams (Common)' : $st }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Category <span class="text-rose-500">*</span></label>
                        <select name="category" required class="w-full text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2 text-slate-900 dark:text-white">
                            @foreach($categories as $category)<option value="{{ $category }}">{{ $category }}</option>@endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Description</label>
                    <textarea name="description" rows="2" placeholder="Syllabus scope and textbook references..."
                              class="w-full text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2 text-slate-900 dark:text-white"></textarea>
                </div>

                <div class="flex items-center justify-end space-x-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="createModalOpen = false" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 transition">
                        Cancel
                    </button>
                    <button type="submit" class="primary-btn text-xs px-4 py-1.5 shadow-sm">
                        Create Subject
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

</div>
@endsection
