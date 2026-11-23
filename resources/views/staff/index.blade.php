@extends('layouts.app')

@section('title', 'Faculty & Staff Directory' . ' — ' . ($school['name'] ?? 'PrimeOS Academy'))

@section('content')
<div x-data="{ 
    viewMode: localStorage.getItem('school_staff_view_mode') || 'grid',
    openDeleteModal: false, 
    deletingStaffId: null, 
    deletingStaffName: '', 
    deletingStaffRole: '',
    openDossierModal: false,
    inspectingStaff: null,
    copiedToast: false,
    toastMessage: '',
    setViewMode(mode) {
        this.viewMode = mode;
        try { localStorage.setItem('school_staff_view_mode', mode); } catch(e) {}
    },
    triggerToast(msg) {
        this.toastMessage = msg;
        this.copiedToast = true;
        setTimeout(() => { this.copiedToast = false; }, 2800);
    },
    copyText(text, label) {
        if (!text) return;
        navigator.clipboard.writeText(text).then(() => {
            this.triggerToast('Copied ' + label + ' to clipboard!');
        }).catch(() => {
            this.triggerToast('Copied: ' + text);
        });
    },
    copyAllVisible() {
        const rows = document.querySelectorAll('[data-staff-row]');
        if (!rows.length) {
            this.triggerToast('No staff records currently displayed.');
            return;
        }
        let tsv = 'Staff ID\tName\tRole\tEmail\tPhone\tStatus\tCourses\n';
        rows.forEach(r => {
            const sid = r.getAttribute('data-staff-id') || '';
            const name = r.getAttribute('data-staff-name') || '';
            const role = r.getAttribute('data-staff-role') || '';
            const email = r.getAttribute('data-staff-email') || '';
            const phone = r.getAttribute('data-staff-phone') || '';
            const status = r.getAttribute('data-staff-status') || '';
            const courses = r.getAttribute('data-staff-courses') || '';
            tsv += `${sid}\t${name}\t${role}\t${email}\t${phone}\t${status}\t${courses}\n`;
        });
        navigator.clipboard.writeText(tsv).then(() => {
            this.triggerToast('Copied ' + rows.length + ' staff records to clipboard (TSV / Sheets)!');
        });
    },
    openInspect(staffObj) {
        this.inspectingStaff = staffObj;
        this.openDossierModal = true;
    }
}" class="w-full max-w-[1680px] mx-auto space-y-5">

    <!-- FLOATING ACTION TOAST -->
    <div x-show="copiedToast" 
         x-cloak 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-3 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 translate-y-3 scale-95"
         class="fixed bottom-6 right-6 z-50 bg-slate-900/95 dark:bg-slate-900/95 backdrop-blur-md text-white border border-emerald-500/50 px-4 py-3 rounded-2xl shadow-2xl flex items-center space-x-3 text-xs font-semibold">
        <span class="w-6 h-6 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center font-black text-xs">✓</span>
        <span x-text="toastMessage"></span>
    </div>

    <!-- HEADER & ACTION ROW (Dense, utilitarian, zero bloated banners) -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-2 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center space-x-2">
                <h1 class="text-lg sm:text-xl font-black text-slate-900 dark:text-white tracking-tight">
                    Faculty &amp; Staff Directory
                </h1>
                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                    Institutional Personnel
                </span>
                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                    {{ $totalApprovedStaff }} Active Staff
                </span>
            </div>
            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                Instructional professors, academic officers, and course assignments.
            </div>
        </div>

        <!-- EXECUTIVE ACTION TOOLBAR -->
        <div class="flex items-center flex-wrap gap-2">
            @if(Auth::user()->isAdmin())
            <a href="{{ route('school.bulk.index', ['tab' => 'staff']) }}" 
               class="px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold transition flex items-center space-x-1.5 shadow-2xs">
                <span>📂</span>
                <span>Bulk Ingest</span>
            </a>
            @endif

            <button type="button" 
                    @click="copyAllVisible()" 
                    class="px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold border border-slate-200 dark:border-slate-700 transition flex items-center space-x-1.5">
                <span>📋</span>
                <span>Copy TSV</span>
            </button>

            <a href="{{ route('staff.export-csv', request()->query()) }}" 
               class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold transition flex items-center space-x-1.5 shadow-2xs">
                <span>📥</span>
                <span>Export CSV</span>
            </a>

            @if(Auth::user()->isAdmin())
            <a href="{{ route('admin.faculty-codes.index') }}" 
               class="px-3 py-1.5 rounded-lg bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold transition flex items-center space-x-1.5 shadow-2xs">
                <span>🔑</span>
                <span>Faculty Codes</span>
            </a>
            @endif
        </div>
    </div>

    <!-- 4 COMPACT METRICS CARDS -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
        <div class="p-3 rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Total Personnel</span>
            <div class="text-lg font-black font-mono text-slate-900 dark:text-white mt-0.5">
                {{ $totalTeachers + $totalAdmins }}
            </div>
            <span class="text-[10px] text-emerald-600 dark:text-emerald-400 font-bold block mt-0.5">
                {{ $totalApprovedStaff }} Verified Active
            </span>
        </div>

        <div class="p-3 rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Instructional Faculty</span>
            <div class="text-lg font-black font-mono text-emerald-600 dark:text-emerald-400 mt-0.5">
                {{ $totalTeachers }}
            </div>
            <span class="text-[10px] text-slate-400 block mt-0.5">Teaching Faculty</span>
        </div>

        <div class="p-3 rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Academic Officers</span>
            <div class="text-lg font-black font-mono text-indigo-600 dark:text-indigo-400 mt-0.5">
                {{ $totalAdmins }}
            </div>
            <span class="text-[10px] text-slate-400 block mt-0.5">Institutional Leadership</span>
        </div>

        <div class="p-3 rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Assigned Courses</span>
            <div class="text-lg font-black font-mono text-amber-600 dark:text-amber-400 mt-0.5">
                {{ $totalAssignedCourses }}
            </div>
            <span class="text-[10px] text-slate-400 block mt-0.5">Active Subject Deliveries</span>
        </div>
    </div>

    <!-- FILTER, SEARCH & VIEW SWITCHER TOOLBAR -->
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-3 sm:p-3.5 shadow-2xs space-y-3">
        
        <!-- Search & Dropdowns Form -->
        <form method="GET" action="{{ route('staff.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-2.5 items-end">
            <!-- Search Query Input -->
            <div class="sm:col-span-2 lg:col-span-5">
                <label for="staff-search-input" class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">
                    Search Staff Dossiers
                </label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 text-xs">
                        🔍
                    </span>
                    <input type="text" 
                           id="staff-search-input"
                           name="search" 
                           value="{{ $searchTerm }}" 
                           placeholder="Search by name, official email, phone or staff ID..." 
                           class="w-full pl-8 pr-8 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-medium focus:ring-1 focus:ring-emerald-500 focus:outline-hidden transition">
                    @if(!empty($searchTerm))
                    <a href="{{ route('staff.index', array_merge(request()->except(['search', 'page']))) }}" 
                       class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-rose-500 text-xs" 
                       title="Clear Search">
                        ✕
                    </a>
                    @endif
                </div>
            </div>

            <!-- Role Selector -->
            <div class="lg:col-span-3">
                <label for="staff-role-select" class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">
                    Institutional Role
                </label>
                <select id="staff-role-select" 
                        name="role" 
                        class="w-full px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-semibold focus:ring-1 focus:ring-emerald-500 focus:outline-hidden transition">
                    <option value="all">All Roles (Faculty &amp; Admins)</option>
                    <option value="teacher" {{ $roleFilter === 'teacher' ? 'selected' : '' }}>🎓 Faculty / Teachers ({{ $totalTeachers }})</option>
                    <option value="admin" {{ $roleFilter === 'admin' ? 'selected' : '' }}>🏛️ Administrators ({{ $totalAdmins }})</option>
                </select>
            </div>

            <!-- Status Selector -->
            <div class="lg:col-span-2">
                <label for="staff-status-select" class="block text-[10px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                    Account Status
                </label>
                <select id="staff-status-select" 
                        name="status" 
                        class="w-full px-3.5 py-2.5 rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/80 text-slate-900 dark:text-white text-xs font-semibold focus:ring-2 focus:ring-emerald-500 focus:outline-hidden transition">
                    <option value="all">All Statuses</option>
                    <option value="active" {{ $statusFilter === 'active' ? 'selected' : '' }}>✓ Verified / Active</option>
                    <option value="pending" {{ $statusFilter === 'pending' ? 'selected' : '' }}>⏳ Pending Onboarding</option>
                </select>
            </div>

            <!-- Filter Submit Buttons -->
            <div class="lg:col-span-2 flex items-center space-x-2">
                <button type="submit" 
                        class="w-full py-2.5 px-4 bg-emerald-600 hover:bg-emerald-500 active:scale-95 text-white rounded-2xl text-xs font-bold transition shadow-md shadow-emerald-600/20 text-center flex items-center justify-center space-x-1.5">
                    <span>Apply Filter</span>
                </button>
                @if(!empty($searchTerm) || $roleFilter !== 'all' || $statusFilter !== 'all')
                <a href="{{ route('staff.index') }}" 
                   class="p-2.5 rounded-2xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-400 transition" 
                   title="Reset All Filters">
                    🔄
                </a>
                @endif
            </div>
        </form>

        <!-- Quick Filter Pills & View Mode Switcher -->
        <div class="pt-3 border-t border-slate-100 dark:border-slate-800/80 flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-1.5">
                <span class="text-[11px] font-bold text-slate-400 mr-1">Quick View:</span>
                
                <a href="{{ route('staff.index', array_merge(request()->except(['role', 'page']))) }}" 
                   class="px-3 py-1.5 rounded-xl text-xs font-bold transition flex items-center space-x-1.5 {{ $roleFilter === 'all' ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-950 shadow-xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                    <span>All Staff</span>
                    <span class="text-[10px] px-1.5 py-0.2 rounded-full {{ $roleFilter === 'all' ? 'bg-white/20 dark:bg-black/20' : 'bg-slate-200 dark:bg-slate-700' }}">{{ $totalTeachers + $totalAdmins }}</span>
                </a>

                <a href="{{ route('staff.index', array_merge(request()->except(['page']), ['role' => 'teacher'])) }}" 
                   class="px-3 py-1.5 rounded-xl text-xs font-bold transition flex items-center space-x-1.5 {{ $roleFilter === 'teacher' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                    <span>Faculty / Teachers</span>
                    <span class="text-[10px] px-1.5 py-0.2 rounded-full {{ $roleFilter === 'teacher' ? 'bg-white/20' : 'bg-slate-200 dark:bg-slate-700' }}">{{ $totalTeachers }}</span>
                </a>

                <a href="{{ route('staff.index', array_merge(request()->except(['page']), ['role' => 'admin'])) }}" 
                   class="px-3 py-1.5 rounded-xl text-xs font-bold transition flex items-center space-x-1.5 {{ $roleFilter === 'admin' ? 'bg-indigo-600 text-white shadow-xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                    <span>Administrators</span>
                    <span class="text-[10px] px-1.5 py-0.2 rounded-full {{ $roleFilter === 'admin' ? 'bg-white/20' : 'bg-slate-200 dark:bg-slate-700' }}">{{ $totalAdmins }}</span>
                </a>
            </div>

            <!-- View Switcher (Grid vs Dense Table) -->
            <div class="inline-flex items-center p-1 rounded-2xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                <button type="button" 
                        @click="setViewMode('grid')" 
                        :class="viewMode === 'grid' ? 'bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-xs' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200'"
                        class="px-3 py-1.5 rounded-xl text-xs font-bold transition flex items-center space-x-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                    <span>Executive Cards</span>
                </button>
                <button type="button" 
                        @click="setViewMode('table')" 
                        :class="viewMode === 'table' ? 'bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-xs' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200'"
                        class="px-3 py-1.5 rounded-xl text-xs font-bold transition flex items-center space-x-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    <span>Dense Table</span>
                </button>
            </div>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- VIEW 1: EXECUTIVE DOSSIER CARDS (GRID) -->
    <!-- ======================================================== -->
    <div x-show="viewMode === 'grid'" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 sm:gap-5">
            @forelse($staffMembers as $staff)
            @php
                $staffIdFormatted = $staff->student_id_number ?? ('PA-' . ($staff->role === 'admin' ? 'ADM' : 'FAC') . '-' . str_pad($staff->id, 4, '0', STR_PAD_LEFT));
                $coursesList = $staff->taughtCourses->pluck('code')->filter()->implode(', ');
            @endphp
            <div data-staff-row 
                 data-staff-id="{{ $staffIdFormatted }}"
                 data-staff-name="{{ $staff->name }}"
                 data-staff-role="{{ ucfirst($staff->role) }}"
                 data-staff-email="{{ $staff->email }}"
                 data-staff-phone="{{ $staff->phone ?? 'N/A' }}"
                 data-staff-status="{{ $staff->is_approved ? 'Approved' : 'Pending' }}"
                 data-staff-courses="{{ $coursesList ?: 'None' }}"
                 class="group relative rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 sm:p-6 shadow-xs hover:shadow-xl hover:border-emerald-500/40 dark:hover:border-emerald-500/40 transition-all duration-300 flex flex-col justify-between">
                
                <!-- Card Header -->
                <div>
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center space-x-3.5">
                            <!-- High-end Avatar with status pip -->
                            <div class="relative shrink-0">
                                <div class="w-13 h-13 rounded-2xl bg-gradient-to-tr {{ $staff->role === 'admin' ? 'from-purple-600 via-indigo-600 to-indigo-700' : 'from-emerald-600 via-teal-600 to-emerald-700' }} text-white font-black text-base flex items-center justify-center shadow-md">
                                    {{ strtoupper(substr($staff->name, 0, 2)) }}
                                </div>
                                <span class="absolute -bottom-1 -right-1 w-4 h-4 rounded-full border-2 border-white dark:border-slate-900 flex items-center justify-center {{ $staff->is_approved ? 'bg-emerald-500' : 'bg-amber-500' }}" title="{{ $staff->is_approved ? 'Verified Active Account' : 'Pending Verification' }}">
                                    @if($staff->is_approved)
                                    <span class="w-1.5 h-1.5 rounded-full bg-white"></span>
                                    @endif
                                </span>
                            </div>

                            <div class="min-w-0">
                                <h3 class="text-sm sm:text-base font-black text-slate-900 dark:text-white truncate group-hover:text-emerald-500 transition">
                                    {{ $staff->name }}
                                </h3>
                                <div class="flex items-center space-x-2 mt-0.5">
                                    <span class="font-mono text-[11px] font-bold text-slate-500 dark:text-slate-400">
                                        {{ $staffIdFormatted }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Role Badge -->
                        <span class="shrink-0 px-2.5 py-1 rounded-xl text-[10px] font-black uppercase tracking-wider border {{ $staff->role === 'admin' ? 'bg-purple-500/10 text-purple-700 dark:text-purple-300 border-purple-500/30' : 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border-emerald-500/30' }}">
                            {{ $staff->role === 'admin' ? 'Administrator' : 'Faculty Lead' }}
                        </span>
                    </div>

                    <!-- Contact Details Strip -->
                    <div class="mt-4 pt-3.5 border-t border-slate-100 dark:border-slate-800/80 space-y-2">
                        <!-- Email with one-click copy -->
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-400 flex items-center space-x-1.5 text-[11px]">
                                <span>✉️</span>
                                <span>Official Email:</span>
                            </span>
                            <button type="button" 
                                    @click="copyText('{{ $staff->email }}', 'Email')" 
                                    class="font-mono font-semibold text-slate-700 dark:text-slate-300 hover:text-emerald-600 dark:hover:text-emerald-400 transition truncate max-w-[200px]"
                                    title="Click to copy email">
                                {{ $staff->email }}
                            </button>
                        </div>

                        <!-- Phone -->
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-400 flex items-center space-x-1.5 text-[11px]">
                                <span>📞</span>
                                <span>Phone Number:</span>
                            </span>
                            @if($staff->phone)
                            <button type="button" 
                                    @click="copyText('{{ $staff->phone }}', 'Phone')" 
                                    class="font-mono font-semibold text-slate-700 dark:text-slate-300 hover:text-emerald-600 dark:hover:text-emerald-400 transition">
                                {{ $staff->phone }}
                            </button>
                            @else
                            <span class="text-slate-400 italic text-[11px]">Unregistered</span>
                            @endif
                        </div>
                    </div>

                    <!-- Assigned Courses Tag Cloud -->
                    <div class="mt-3.5 pt-3 border-t border-slate-100 dark:border-slate-800/80">
                        <div class="flex items-center justify-between text-[11px] font-bold text-slate-500 dark:text-slate-400 mb-2">
                            <span>Teaching Curriculum:</span>
                            <span class="text-emerald-600 dark:text-emerald-400 font-extrabold">
                                {{ $staff->taughtCourses->count() }} {{ Str::plural('Course', (int)$staff->taughtCourses->count()) }}
                            </span>
                        </div>
                        <div class="flex flex-wrap gap-1.5 min-h-[28px]">
                            @forelse($staff->taughtCourses as $tc)
                            <a href="{{ route('courses.show', $tc->id) }}" 
                               class="inline-flex items-center px-2 py-0.5 rounded-lg text-[10px] font-extrabold bg-slate-100 hover:bg-emerald-50 text-slate-800 hover:text-emerald-700 dark:bg-slate-800 dark:hover:bg-emerald-950 dark:text-slate-300 dark:hover:text-emerald-300 border border-slate-200 dark:border-slate-700 transition"
                               title="{{ $tc->title }}">
                                <span>{{ $tc->code }}</span>
                            </a>
                            @empty
                            <span class="text-[11px] text-slate-400 italic">No courses assigned yet</span>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- Card Footer Actions -->
                <div class="mt-5 pt-3.5 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-2">
                    <div class="flex items-center space-x-1.5">
                        <!-- Quick Inspect Button -->
                        <button type="button" 
                                @click="openInspect({
                                    id: {{ $staff->id }},
                                    formattedId: '{{ $staffIdFormatted }}',
                                    name: '{{ addslashes($staff->name) }}',
                                    role: '{{ ucfirst($staff->role) }}',
                                    email: '{{ addslashes($staff->email) }}',
                                    phone: '{{ addslashes($staff->phone ?? '') }}',
                                    status: '{{ $staff->is_approved ? 'Verified Active' : 'Pending Verification' }}',
                                    joined: '{{ $staff->created_at ? $staff->created_at->format('M d, Y') : 'N/A' }}',
                                    coursesCount: {{ $staff->taughtCourses->count() }},
                                    courses: [
                                        @foreach($staff->taughtCourses as $tc)
                                        { id: {{ $tc->id }}, code: '{{ addslashes($tc->code) }}', title: '{{ addslashes($tc->title) }}' },
                                        @endforeach
                                    ]
                                })" 
                                class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold transition flex items-center space-x-1.5">
                            <span>👁️</span>
                            <span>Dossier</span>
                        </button>

                        <a href="{{ route('staff.edit', $staff->id) }}" 
                           class="px-3 py-1.5 rounded-xl bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/60 dark:hover:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300 text-xs font-bold border border-indigo-200/60 dark:border-indigo-800/60 transition flex items-center space-x-1">
                            <span>✏️</span>
                            <span>Edit</span>
                        </a>
                    </div>

                    @if($staff->id !== Auth::id())
                    <button type="button" 
                            @click="deletingStaffId = {{ $staff->id }}; deletingStaffName = '{{ addslashes($staff->name) }}'; deletingStaffRole = '{{ ucfirst($staff->role) }}'; openDeleteModal = true" 
                            class="p-2 rounded-xl text-rose-500 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/60 transition"
                            title="Terminate Staff Account">
                        🗑️
                    </button>
                    @endif
                </div>

            </div>
            @empty
            <div class="col-span-full p-12 text-center rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 space-y-3">
                <span class="text-4xl block">🔍</span>
                <h3 class="text-sm sm:text-base font-black text-slate-800 dark:text-slate-200">No staff members found matching query</h3>
                <p class="text-xs text-slate-400 max-w-sm mx-auto">Try adjusting your search terms or clearing your role and status filters.</p>
                <div class="pt-2">
                    <a href="{{ route('staff.index') }}" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold transition inline-flex items-center space-x-1.5 shadow-sm">
                        <span>Reset All Filters</span>
                    </a>
                </div>
            </div>
            @endforelse
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- VIEW 2: DENSE DATA ROSTER (TABLE) -->
    <!-- ======================================================== -->
    <div x-show="viewMode === 'table'" class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left">
                <thead class="bg-slate-50 dark:bg-slate-850 uppercase text-[10px] font-black text-slate-400 dark:text-slate-500 border-b border-slate-200 dark:border-slate-800 tracking-wider">
                    <tr>
                        <th class="p-4">Personnel Dossier</th>
                        <th class="p-4">Designated Role</th>
                        <th class="p-4">Official Contact</th>
                        <th class="p-4">Assigned Curriculum</th>
                        <th class="p-4">Account Status</th>
                        <th class="p-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium">
                    @forelse($staffMembers as $staff)
                    @php
                        $staffIdFormatted = $staff->student_id_number ?? ('PA-' . ($staff->role === 'admin' ? 'ADM' : 'FAC') . '-' . str_pad($staff->id, 4, '0', STR_PAD_LEFT));
                        $coursesList = $staff->taughtCourses->pluck('code')->filter()->implode(', ');
                    @endphp
                    <tr data-staff-row 
                        data-staff-id="{{ $staffIdFormatted }}"
                        data-staff-name="{{ $staff->name }}"
                        data-staff-role="{{ ucfirst($staff->role) }}"
                        data-staff-email="{{ $staff->email }}"
                        data-staff-phone="{{ $staff->phone ?? 'N/A' }}"
                        data-staff-status="{{ $staff->is_approved ? 'Approved' : 'Pending' }}"
                        data-staff-courses="{{ $coursesList ?: 'None' }}"
                        class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition">
                        
                        <!-- Staff Name & ID -->
                        <td class="p-4">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr {{ $staff->role === 'admin' ? 'from-purple-600 to-indigo-600' : 'from-emerald-600 to-teal-600' }} text-white font-extrabold flex items-center justify-center text-xs shrink-0 shadow-xs">
                                    {{ strtoupper(substr($staff->name, 0, 2)) }}
                                </div>
                                <div>
                                    <span class="font-bold text-slate-900 dark:text-white text-xs sm:text-sm block">{{ $staff->name }}</span>
                                    <span class="text-[10px] text-slate-400 font-mono font-bold">{{ $staffIdFormatted }}</span>
                                </div>
                            </div>
                        </td>

                        <!-- Role -->
                        <td class="p-4">
                            <span class="px-2.5 py-1 rounded-xl text-[10px] font-black uppercase tracking-wider border {{ $staff->role === 'admin' ? 'bg-purple-500/10 text-purple-700 dark:text-purple-300 border-purple-500/30' : 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border-emerald-500/30' }}">
                                {{ $staff->role === 'admin' ? 'Administrator' : 'Faculty Lead' }}
                            </span>
                        </td>

                        <!-- Contact Details -->
                        <td class="p-4">
                            <div class="space-y-1">
                                <button type="button" 
                                        @click="copyText('{{ $staff->email }}', 'Email')" 
                                        class="font-mono text-[11px] text-slate-700 dark:text-slate-300 hover:text-emerald-600 dark:hover:text-emerald-400 transition flex items-center space-x-1"
                                        title="Click to copy email">
                                    <span>✉️</span>
                                    <span>{{ $staff->email }}</span>
                                </button>
                                <div class="text-[10px] text-slate-400 font-mono flex items-center space-x-1">
                                    <span>📞</span>
                                    <span>{{ $staff->phone ?? 'No phone registered' }}</span>
                                </div>
                            </div>
                        </td>

                        <!-- Teaching Courses -->
                        <td class="p-4">
                            <div class="flex flex-wrap gap-1 max-w-xs">
                                @forelse($staff->taughtCourses as $tc)
                                <a href="{{ route('courses.show', $tc->id) }}" 
                                   class="px-2 py-0.5 rounded-lg text-[10px] font-bold bg-slate-100 hover:bg-emerald-100 text-slate-700 hover:text-emerald-800 dark:bg-slate-800 dark:hover:bg-emerald-950 dark:text-slate-300 dark:hover:text-emerald-300 border border-slate-200 dark:border-slate-700 transition"
                                   title="{{ $tc->title }}">
                                    {{ $tc->code }}
                                </a>
                                @empty
                                <span class="text-slate-400 text-[11px] italic">None</span>
                                @endforelse
                            </div>
                        </td>

                        <!-- Status -->
                        <td class="p-4">
                            <span class="inline-flex items-center space-x-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold {{ $staff->is_approved ? 'bg-emerald-50 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800' : 'bg-amber-50 dark:bg-amber-950 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $staff->is_approved ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                                <span>{{ $staff->is_approved ? 'Verified Active' : 'Pending' }}</span>
                            </span>
                        </td>

                        <!-- Actions -->
                        <td class="p-4 text-right">
                            <div class="inline-flex items-center space-x-1.5">
                                <button type="button" 
                                        @click="openInspect({
                                            id: {{ $staff->id }},
                                            formattedId: '{{ $staffIdFormatted }}',
                                            name: '{{ addslashes($staff->name) }}',
                                            role: '{{ ucfirst($staff->role) }}',
                                            email: '{{ addslashes($staff->email) }}',
                                            phone: '{{ addslashes($staff->phone ?? '') }}',
                                            status: '{{ $staff->is_approved ? 'Verified Active' : 'Pending Verification' }}',
                                            joined: '{{ $staff->created_at ? $staff->created_at->format('M d, Y') : 'N/A' }}',
                                            coursesCount: {{ $staff->taughtCourses->count() }},
                                            courses: [
                                                @foreach($staff->taughtCourses as $tc)
                                                { id: {{ $tc->id }}, code: '{{ addslashes($tc->code) }}', title: '{{ addslashes($tc->title) }}' },
                                                @endforeach
                                            ]
                                        })" 
                                        class="px-2.5 py-1.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-xl text-xs font-bold transition flex items-center space-x-1">
                                    <span>👁️</span>
                                    <span>View</span>
                                </button>

                                <a href="{{ route('staff.edit', $staff->id) }}" 
                                   class="px-2.5 py-1.5 bg-indigo-50 dark:bg-indigo-950/60 hover:bg-indigo-100 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 rounded-xl text-xs font-bold transition flex items-center space-x-1">
                                    <span>✏️</span>
                                    <span>Edit</span>
                                </a>

                                @if($staff->id !== Auth::id())
                                <button type="button" 
                                        @click="deletingStaffId = {{ $staff->id }}; deletingStaffName = '{{ addslashes($staff->name) }}'; deletingStaffRole = '{{ ucfirst($staff->role) }}'; openDeleteModal = true" 
                                        class="px-2 py-1.5 bg-rose-50 dark:bg-rose-950/60 hover:bg-rose-100 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800 rounded-xl text-xs font-bold transition"
                                        title="Terminate Account">
                                    <span>🗑️</span>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="p-8 text-center text-slate-400">
                            No staff records match your query.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- PAGINATION BAR -->
    @if($staffMembers->hasPages())
    <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs">
        {{ $staffMembers->links() }}
    </div>
    @endif

    <!-- ======================================================== -->
    <!-- MODAL 1: QUICK DOSSIER INSPECTOR (FLYOUT MODAL) -->
    <!-- ======================================================== -->
    <div x-show="openDossierModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-xs" @click="openDossierModal = false"></div>

        <template x-if="inspectingStaff">
            <div class="relative bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-2xl max-w-lg w-full p-6 z-10 space-y-5" @click.stop>
                <!-- Top Header -->
                <div class="flex items-start justify-between pb-4 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center space-x-3.5">
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-emerald-600 to-indigo-600 text-white font-black text-sm flex items-center justify-center shadow-md">
                            <span x-text="inspectingStaff.name.substring(0, 2).toUpperCase()"></span>
                        </div>
                        <div>
                            <h3 class="text-base font-black text-slate-900 dark:text-white" x-text="inspectingStaff.name"></h3>
                            <div class="flex items-center space-x-2 mt-0.5">
                                <span class="font-mono text-xs font-bold text-slate-400" x-text="inspectingStaff.formattedId"></span>
                                <span class="px-2 py-0.2 rounded-md text-[10px] font-bold uppercase bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300" x-text="inspectingStaff.role"></span>
                            </div>
                        </div>
                    </div>
                    <button type="button" @click="openDossierModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-lg leading-none">&times;</button>
                </div>

                <!-- Personnel Data Grid -->
                <div class="grid grid-cols-2 gap-3 text-xs">
                    <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/60 dark:border-slate-700/60">
                        <span class="text-[10px] font-black uppercase text-slate-400 block mb-1">Official Email</span>
                        <div class="font-mono font-semibold text-slate-900 dark:text-white truncate" x-text="inspectingStaff.email"></div>
                    </div>
                    <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/60 dark:border-slate-700/60">
                        <span class="text-[10px] font-black uppercase text-slate-400 block mb-1">Phone Contact</span>
                        <div class="font-mono font-semibold text-slate-900 dark:text-white truncate" x-text="inspectingStaff.phone || 'Unregistered'"></div>
                    </div>
                    <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/60 dark:border-slate-700/60">
                        <span class="text-[10px] font-black uppercase text-slate-400 block mb-1">Account Status</span>
                        <div class="font-semibold text-emerald-600 dark:text-emerald-400" x-text="inspectingStaff.status"></div>
                    </div>
                    <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/60 dark:border-slate-700/60">
                        <span class="text-[10px] font-black uppercase text-slate-400 block mb-1">Onboarded Since</span>
                        <div class="font-semibold text-slate-700 dark:text-slate-300" x-text="inspectingStaff.joined"></div>
                    </div>
                </div>

                <!-- Assigned Courses -->
                <div>
                    <div class="flex items-center justify-between text-xs font-bold text-slate-700 dark:text-slate-300 mb-2">
                        <span>Assigned Courses &amp; Modules:</span>
                        <span class="text-emerald-600 dark:text-emerald-400 font-extrabold" x-text="inspectingStaff.coursesCount + ' Courses'"></span>
                    </div>
                    <div class="space-y-1.5 max-h-44 overflow-y-auto">
                        <template x-for="c in inspectingStaff.courses" :key="c.id">
                            <a :href="'/courses/' + c.id" class="p-2.5 rounded-xl bg-slate-50 hover:bg-emerald-50 dark:bg-slate-800/80 dark:hover:bg-slate-800 border border-slate-200/60 dark:border-slate-700/60 flex items-center justify-between transition text-xs">
                                <div class="flex items-center space-x-2">
                                    <span class="font-mono font-extrabold text-emerald-600 dark:text-emerald-400" x-text="c.code"></span>
                                    <span class="font-semibold text-slate-800 dark:text-slate-200" x-text="c.title"></span>
                                </div>
                                <span class="text-slate-400 text-xs">&rarr;</span>
                            </a>
                        </template>
                        <div x-show="!inspectingStaff.courses.length" class="text-slate-400 text-xs italic text-center py-3">
                            No instructional courses currently assigned to this personnel.
                        </div>
                    </div>
                </div>

                <!-- Modal Actions -->
                <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <a :href="'/staff/' + inspectingStaff.id" class="text-xs font-bold text-emerald-600 dark:text-emerald-400 hover:underline flex items-center space-x-1">
                        <span>Full Institutional Profile &rarr;</span>
                    </a>

                    <div class="flex items-center space-x-2">
                        <button type="button" @click="openDossierModal = false" class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold transition">
                            Close
                        </button>
                        <a :href="'/staff/' + inspectingStaff.id + '/edit'" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold transition shadow-xs">
                            Edit Dossier
                        </a>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- ======================================================== -->
    <!-- MODAL 2: DANGER ZONE - TRIPLE FACTOR STAFF DELETION -->
    <!-- ======================================================== -->
    <div x-show="openDeleteModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-xs" @click="openDeleteModal = false"></div>

        <div class="relative bg-white dark:bg-slate-900 rounded-3xl border border-rose-500/40 shadow-2xl max-w-lg w-full p-5 sm:p-6 z-10 space-y-4">
            <div class="flex items-center space-x-3 text-rose-600 dark:text-rose-400">
                <span class="text-2xl">⚠️</span>
                <div>
                    <h3 class="text-base font-black text-slate-900 dark:text-white">Caution: Terminate Staff Account</h3>
                    <p class="text-[11px] text-rose-600 dark:text-rose-400 font-semibold">Irreversible Administrative Procedure</p>
                </div>
            </div>

            <div class="p-3.5 rounded-2xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900/60 text-xs text-rose-800 dark:text-rose-300 space-y-1.5 leading-relaxed">
                <p>You are about to permanently delete staff account <strong x-text="deletingStaffName + ' (' + deletingStaffRole + ')'"></strong>.</p>
                <p class="text-[11px]">This action permanently revokes all faculty instructional credentials and administrative permissions. Triple-factor verification is required.</p>
            </div>

            <form method="POST" :action="'/staff/' + deletingStaffId" class="space-y-3 text-xs">
                @csrf
                @method('DELETE')

                <!-- 1. Type confirmation keyword -->
                <div>
                    <label class="block text-[10px] font-black uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1">
                        1. Type <span class="text-rose-600 font-mono">DELETE</span> to confirm *
                    </label>
                    <input type="text" name="confirmation_text" required placeholder="Type DELETE in capital letters" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-mono font-bold focus:ring-1 focus:ring-rose-500">
                </div>

                <!-- 2. Security Math Captcha -->
                <div>
                    <label class="block text-[10px] font-black uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1">
                        2. Security Captcha: What is <span class="font-mono text-emerald-600 font-black">{{ $captchaNum1 }} + {{ $captchaNum2 }}</span> ? *
                    </label>
                    <input type="hidden" name="captcha_expected" value="{{ $captchaExpected }}">
                    <input type="number" name="captcha_answer" required placeholder="Calculate sum" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-mono font-bold focus:ring-1 focus:ring-rose-500">
                </div>

                <!-- 3. Password Verification -->
                <div>
                    <label class="block text-[10px] font-black uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1">
                        3. Your Administrator Password *
                    </label>
                    <input type="password" name="admin_password" required placeholder="Enter your login password" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-1 focus:ring-rose-500">
                </div>

                <div class="pt-2 flex items-center justify-end space-x-2">
                    <button type="button" @click="openDeleteModal = false" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold transition">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-bold transition shadow-md shadow-rose-600/20">
                        Authorize Staff Deletion 🗑️
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
