@extends('layouts.app')

@section('title', 'Student Directory & Admissions - School Management System')

@section('content')
<div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-4 space-y-4" x-data="{ viewMode: 'cards' }">

    <!-- Infix Breadcrumbs & Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <!-- Breadcrumbs -->
            <div class="flex items-center space-x-2 text-[11px] text-slate-400 font-semibold mb-1">
                <a href="{{ route('dashboard') }}" class="hover:text-slate-600 dark:hover:text-slate-200 transition">Dashboard</a>
                <span>/</span>
                <span class="text-slate-500">Administration</span>
                <span>/</span>
                <span class="text-slate-800 dark:text-slate-200 font-bold">Student Directory</span>
            </div>

            <div class="flex items-center space-x-2.5">
                <span class="w-8 h-8 rounded-lg flex items-center justify-center text-white text-sm shadow-xs" style="background: var(--system_primary_gradient);">
                    <i class="ti-id-badge"></i>
                </span>
                <h1 class="text-lg sm:text-xl font-black text-slate-900 dark:text-white tracking-tight">
                    Student Directory
                </h1>
            </div>
        </div>

        <div class="flex items-center flex-wrap gap-2">
            <!-- View Mode Switcher -->
            <div class="inline-flex items-center p-0.5 rounded-lg bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                <button type="button" @click="viewMode = 'cards'" :class="viewMode === 'cards' ? 'bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-xs' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'" class="px-2.5 py-1 rounded text-xs font-semibold transition flex items-center space-x-1">
                    <i class="ti-layout-grid2 text-xs"></i> <span>Cards</span>
                </button>
                <button type="button" @click="viewMode = 'table'" :class="viewMode === 'table' ? 'bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-xs' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'" class="px-2.5 py-1 rounded text-xs font-semibold transition flex items-center space-x-1">
                    <i class="ti-view-list text-xs"></i> <span>Table</span>
                </button>
            </div>

            @if(Auth::user()->isAdmin())
            <a href="{{ route('school.bulk.index', ['tab' => 'students']) }}" 
               class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold transition shadow-xs flex items-center space-x-1.5">
                <i class="ti-upload text-xs"></i>
                <span>Bulk Import</span>
            </a>
            @endif
            <a href="{{ route('school.students.export-csv', ['class_id' => $classId]) }}" 
               class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold transition flex items-center space-x-1.5">
                <i class="ti-download text-xs"></i>
                <span>Export CSV</span>
            </a>
            @if(Auth::user()->isAdmin() || Auth::user()->isTeacher())
            <a href="{{ route('school.students.create') }}" 
               class="primary-btn inline-flex items-center space-x-1.5 shadow-sm" style="padding: 0.45rem 1rem; font-size: 0.75rem;">
                <i class="ti-plus"></i>
                <span>Admit Student</span>
            </a>
            @endif
        </div>
    </div>

    <!-- Stats Bar (Infix .white-box.single-summery) -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="white-box single-summery p-3.5 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Admitted</span>
                    <p class="text-[10px] text-slate-400">Official Students</p>
                </div>
                <div class="gradient-color2 text-2xl font-black font-mono leading-none">{{ $stats['total_students'] }}</div>
            </div>
        </div>
        <div class="white-box single-summery p-3.5 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Active Classes</span>
                    <p class="text-[10px] text-slate-400">Grade Divisions</p>
                </div>
                <div class="gradient-color2 text-2xl font-black font-mono leading-none">{{ $stats['total_classes'] }}</div>
            </div>
        </div>
        <div class="white-box single-summery p-3.5 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Placements</span>
                    <p class="text-[10px] text-slate-400">Class Enrolled</p>
                </div>
                <div class="gradient-color2 text-2xl font-black font-mono leading-none">{{ $stats['active_admissions'] }}</div>
            </div>
        </div>
        <div class="white-box single-summery p-3.5 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Unassigned</span>
                    <p class="text-[10px] text-slate-400">Students without a class</p>
                </div>
                <div class="text-xl font-black font-mono text-slate-800 dark:text-slate-100 leading-none">{{ $stats['unassigned'] }}</div>
            </div>
        </div>
    </div>

    <!-- Filters & Search -->
    <div class="bg-white dark:bg-slate-900 p-3 rounded-xl border border-slate-200 dark:border-slate-800 shadow-xs flex flex-col md:flex-row items-center justify-between gap-3">
        <form id="student-directory-filters" method="GET" action="{{ route('school.students.index') }}" class="flex flex-wrap items-center gap-2.5 w-full">
            <div class="flex-1 min-w-[200px]">
                <input id="student-directory-search" type="search" name="search" value="{{ $search }}" autocomplete="off" placeholder="Search name, admission no, roll no..."
                       class="w-full px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/60 text-xs text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500">
            </div>

            <div>
                <select id="student-directory-class" name="class_id" class="px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/60 text-xs text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500">
                    <option value="">-- All Classes &amp; Sections --</option>
                    @foreach($classes as $c)
                    <option value="{{ $c->id }}" {{ $classId == $c->id ? 'selected' : '' }}>
                        {{ $c->display_name }} ({{ $c->students_count }})
                    </option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="px-3.5 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold transition">
                Filter
            </button>

            @if($search || $classId)
            <a href="{{ route('school.students.index') }}" data-clear-student-filters class="text-xs font-bold text-rose-500 hover:underline px-2">
                Clear Filters
            </a>
            @endif
        </form>
    </div>

    <div id="student-directory-loading" class="hidden text-xs text-slate-500" role="status" aria-live="polite">Updating student results…</div>
    @include('school.students.partials.results', ['students' => $students])

</div>
<script>
(() => {
    const form = document.getElementById('student-directory-filters');
    const search = document.getElementById('student-directory-search');
    const loading = document.getElementById('student-directory-loading');
    if (!form || !results || !loading) return;

    let debounceTimer;
    let activeRequest;

    const loadResults = async (url, updateHistory = true) => {
        if (activeRequest) activeRequest.abort();
        const controller = new AbortController();
        activeRequest = controller;
        loading.classList.remove('hidden');
        document.getElementById('student-directory-results')?.setAttribute('aria-busy', 'true');
        try {
            const response = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                credentials: 'same-origin',
                signal: controller.signal
            });
            if (!response.ok) throw new Error(`Request failed (${response.status})`);
            const payload = await response.json();
            if (typeof payload.html !== 'string') throw new Error('Invalid results response');
            const parsed = new DOMParser().parseFromString(payload.html, 'text/html');
            const replacement = parsed.getElementById('student-directory-results');
            if (!replacement) throw new Error('Results container missing');
            document.getElementById('student-directory-results')?.replaceWith(replacement);
            window.Alpine?.initTree(replacement);
            if (updateHistory) history.pushState({}, '', url);
        } catch (error) {
            if (error.name !== 'AbortError') {
                // Preserve usability if asynchronous loading is unavailable.
                form.submit();
            }
        } finally {
            if (activeRequest === controller) {
                loading.classList.add('hidden');
                document.getElementById('student-directory-results')?.removeAttribute('aria-busy');
                activeRequest = null;
            }
        }
    };

    const buildUrl = () => {
        const url = new URL(form.action, window.location.origin);
        const data = new FormData(form);
        for (const [key, value] of data.entries()) {
            if (String(value).trim() !== '') url.searchParams.set(key, value);
        }
        return url.toString();
    };

    form.addEventListener('submit', event => {
        event.preventDefault();
        loadResults(buildUrl());
    });

    search?.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => loadResults(buildUrl()), 350);
    });

    form.querySelector('[name="class_id"]')?.addEventListener('change', () => loadResults(buildUrl()));

    document.addEventListener('click', event => {
        const clearLink = event.target.closest('[data-clear-student-filters]');
        if (clearLink) {
            event.preventDefault();
            if (search) search.value = '';
            const classSelect = form.querySelector('[name="class_id"]');
            if (classSelect) classSelect.value = '';
            loadResults(clearLink.href);
            return;
        }
        const currentResults = document.getElementById('student-directory-results');
        const link = event.target.closest('#student-directory-results .pagination a');
        if (!link || !currentResults?.contains(link)) return;
        event.preventDefault();
        loadResults(link.href);
    });

    window.addEventListener('popstate', () => {
        const url = new URL(window.location.href);
        if (search) search.value = url.searchParams.get('search') || '';
        const classSelect = form.querySelector('[name="class_id"]');
        if (classSelect) classSelect.value = url.searchParams.get('class_id') || '';
        loadResults(window.location.href, false);
    });
})();
</script>
@endsection
