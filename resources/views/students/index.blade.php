@extends('layouts.app')

@section('title', 'Student Directory & Roster' . ' — ' . ($school['name'] ?? 'PrimeOS Academy'))

@section('content')
<div x-data="{
    activeModalStudent: null,
    copiedToast: false,
    toastMessage: '',
    copyStudentInfo(student) {
        const text = `=== {{ strtoupper($school['name'] ?? 'PRIMEOS ACADEMY') }} - STUDENT DOSSIER ===\n` +
            `Student ID: ${student.student_id_number || 'N/A'}\n` +
            `Full Name: ${student.name}\n` +
            `Class: ${student.class_name || 'Unassigned'} (Section: ${student.section || 'A'})\n` +
            `Roll Number: ${student.roll_number || 'N/A'}\n` +
            `Section: Section ${student.section || 'A'} (${student.stream || 'General'})\n` +
            `Phone: ${student.phone || 'N/A'}\n` +
            `Email: ${student.email}\n` +
            `Date of Birth: ${student.date_of_birth || 'N/A'}\n` +
            `Blood Group: ${student.blood_group || 'N/A'}\n` +
            `Guardian Name: ${student.guardian_name || 'N/A'}\n` +
            `Guardian Phone: ${student.guardian_phone || 'N/A'}\n` +
            `Guardian Email: ${student.guardian_email || 'N/A'}\n` +
            `Residential Address: ${student.address || 'N/A'}\n` +
            `Academic Tier: ${student.academic_tier || 'Junior Scholar'} (${student.xp_points || 0} XP)\n` +
            `Status: ${student.is_approved ? 'Active / Approved' : 'Pending Verification'}\n` +
            `===========================================`;

        navigator.clipboard.writeText(text).then(() => {
            this.toastMessage = `Copied ${student.name}'s details to clipboard!`;
            this.copiedToast = true;
            setTimeout(() => this.copiedToast = false, 3000);
        });
    },
    copyAllVisible() {
        const table = document.getElementById('students-roster-table');
        if (!table) return;
        let text = 'Roll\tStudent ID\tName\tClass\tSection\tPhone\tEmail\tGuardian\tGuardian Phone\n';
        const rows = table.querySelectorAll('tbody tr.student-row');
        rows.forEach(r => {
            const roll = r.dataset.roll || '-';
            const sid = r.dataset.sid || '-';
            const name = r.dataset.name || '-';
            const cls = r.dataset.cls || '-';
            const section = r.dataset.section || r.dataset.batch || 'A';
            const phone = r.dataset.phone || '-';
            const email = r.dataset.email || '-';
            const gname = r.dataset.gname || '-';
            const gphone = r.dataset.gphone || '-';
            text += `${roll}\t${sid}\t${name}\t${cls}\t${section}\t${phone}\t${email}\t${gname}\t${gphone}\n`;
        });
        navigator.clipboard.writeText(text).then(() => {
            this.toastMessage = `Copied ${rows.length} visible student records to clipboard!`;
            this.copiedToast = true;
            setTimeout(() => this.copiedToast = false, 3000);
        });
    }
}" class="max-w-7xl mx-auto space-y-5">

    <!-- COPIED TOAST NOTIFICATION -->
    <div x-show="copiedToast" 
         x-cloak 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform translate-y-2"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 transform translate-y-0"
         x-transition:leave-end="opacity-0 transform translate-y-2"
         class="fixed bottom-6 right-6 z-50 bg-slate-900 dark:bg-emerald-950 text-white border border-emerald-500/50 px-4 py-3 rounded-2xl shadow-2xl flex items-center space-x-3">
        <span class="w-7 h-7 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center font-bold text-sm">✓</span>
        <span class="text-xs sm:text-sm font-semibold" x-text="toastMessage"></span>
    </div>

    <!-- HEADER & ACTION TOOLBAR -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs transition-colors">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div>
                <div class="flex items-center space-x-2">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-indigo-100 dark:bg-indigo-950/60 text-indigo-800 dark:text-indigo-300 border border-indigo-300 dark:border-indigo-800">
                        Student Information System
                    </span>
                    <span class="text-xs text-amber-600 dark:text-amber-400 font-bold">
                        🔥 Institutional Roster
                    </span>
                </div>
                <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight mt-1">
                    Student Directory &amp; Academic Roster
                </h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                    Browse all enrolled students class-wise with roll numbers, contact dossiers, guardian info, and quick grab/export tools.
                </p>
            </div>

            <!-- QUICK GRAB & EXPORT BUTTONS -->
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" 
                        @click="copyAllVisible()" 
                        class="inline-flex items-center space-x-1.5 px-3 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-xl text-xs font-bold transition shadow-xs">
                    <span>📋</span>
                    <span>Copy Visible (Tabular)</span>
                </button>

                <a href="{{ route('students.export-csv', request()->query()) }}" 
                   class="inline-flex items-center space-x-1.5 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold transition shadow-xs">
                    <span>📥</span>
                    <span>Export CSV</span>
                </a>

                <a href="{{ route('students.roster-print', request()->query()) }}" 
                   target="_blank"
                   class="inline-flex items-center space-x-1.5 px-3 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold transition shadow-xs">
                    <span>🖨️</span>
                    <span>Print Register</span>
                </a>

                <a href="{{ route('students.grab-json', request()->query()) }}" 
                   target="_blank"
                   class="inline-flex items-center space-x-1.5 px-3 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-xl text-xs font-bold transition shadow-xs">
                    <span>📦</span>
                    <span>Raw JSON</span>
                </a>
            </div>
        </div>

        <!-- STATS PILLS -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-4 pt-4 border-t border-slate-100 dark:border-slate-800">
            <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/60 dark:border-slate-700/60">
                <span class="text-[10px] uppercase font-bold text-slate-500 dark:text-slate-400">Total Enrolled</span>
                <div class="text-lg font-black text-slate-900 dark:text-white">{{ $totalStudentsCount }} Students</div>
            </div>
            <div class="p-3 rounded-xl bg-emerald-50/50 dark:bg-emerald-950/20 border border-emerald-200/60 dark:border-emerald-800/60">
                <span class="text-[10px] uppercase font-bold text-emerald-600 dark:text-emerald-400">Matching Query</span>
                <div class="text-lg font-black text-emerald-700 dark:text-emerald-300">{{ $students->total() }} Listed</div>
            </div>
            <div class="p-3 rounded-xl bg-blue-50/50 dark:bg-blue-950/20 border border-blue-200/60 dark:border-blue-800/60">
                <span class="text-[10px] uppercase font-bold text-blue-600 dark:text-blue-400">Academic Classes</span>
                <div class="text-lg font-black text-blue-700 dark:text-blue-300">{{ $allClasses->count() }} Classes</div>
            </div>
            <div class="p-3 rounded-xl bg-amber-50/50 dark:bg-amber-950/20 border border-amber-200/60 dark:border-amber-800/60">
                <span class="text-[10px] uppercase font-bold text-amber-600 dark:text-amber-400">Class Sections</span>
                <div class="text-lg font-black text-amber-700 dark:text-amber-300">{{ ($allSections ?? $allBatches)->count() }} Sections</div>
            </div>
        </div>
    </div>

    <!-- 1. CLASS-WISE HORIZONTAL PILLS (CLASS 1 TO 12 & ALL) -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-2 shadow-xs overflow-x-auto">
        <div class="flex items-center space-x-1.5 min-w-max">
            <a href="{{ route('students.index', array_merge(request()->except(['class_id', 'page']))) }}" 
               class="px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center space-x-1.5 {{ empty($selectedClassId) ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                <span>🏫</span>
                <span>All Classes</span>
                <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ empty($selectedClassId) ? 'bg-white/20 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300' }}">
                    {{ $totalStudentsCount }}
                </span>
            </a>

            @foreach($allClasses as $c)
            @php
                $count = $classCounts[$c->id] ?? 0;
                $isActive = ($selectedClassId == $c->id);
            @endphp
            <a href="{{ route('students.index', array_merge(request()->except(['page']), ['class_id' => $c->id])) }}" 
               class="px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center space-x-1.5 {{ $isActive ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                <span>{{ $c->display_name }}</span>
                <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $isActive ? 'bg-white/20 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300' }}">
                    {{ $count }}
                </span>
            </a>
            @endforeach
        </div>
    </div>

    <!-- 2. SEARCH & ADVANCED FILTERS BAR -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs">
        <form method="GET" action="{{ route('students.index') }}" class="space-y-3">
            @if($selectedClassId)
            <input type="hidden" name="class_id" value="{{ $selectedClassId }}">
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                <!-- Search Input -->
                <div class="lg:col-span-2">
                    <label for="search" class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">
                        Search Student Dossier
                    </label>
                    <div class="relative">
                        <input type="text" 
                               id="search" 
                               name="search" 
                               value="{{ $searchTerm }}" 
                               placeholder="Search Name, ID (PA-STD-..), Phone, Email, Roll..." 
                               class="w-full pl-9 pr-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-hidden">
                        <span class="absolute left-3 top-2.5 text-slate-400 text-xs">🔍</span>
                    </div>
                </div>

                <!-- Section Filter -->
                <div>
                    <label for="section_id" class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">
                        Class Section
                    </label>
                    <select id="section_id" 
                            name="section_id" 
                            class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs">
                        <option value="">All Sections</option>
                        @foreach($allSections ?? $allBatches as $sec)
                            <option value="{{ $sec->id }}" {{ ($selectedSectionId ?? $selectedBatchId) == $sec->id ? 'selected' : '' }}>
                                Section {{ $sec->name }} ({{ $sec->stream ?? 'General' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Academic Tier Filter -->
                <div>
                    <label for="tier" class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">
                        Academic Tier
                    </label>
                    <select id="tier" 
                            name="tier" 
                            class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs">
                        <option value="all">All Tiers</option>
                        <option value="Junior Scholar" {{ $tierFilter === 'Junior Scholar' ? 'selected' : '' }}>Junior Scholar</option>
                        <option value="Honors Scholar" {{ $tierFilter === 'Honors Scholar' ? 'selected' : '' }}>Honors Scholar</option>
                        <option value="Distinguished Scholar" {{ $tierFilter === 'Distinguished Scholar' ? 'selected' : '' }}>Distinguished Scholar</option>
                        <option value="Presidential Fellow" {{ $tierFilter === 'Presidential Fellow' ? 'selected' : '' }}>Presidential Fellow</option>
                    </select>
                </div>

                <!-- Items Per Page & Action -->
                <div class="flex items-end space-x-2">
                    <div class="w-1/2">
                        <label for="per_page" class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">
                            Per Page
                        </label>
                        <select id="per_page" 
                                name="per_page" 
                                class="w-full px-2 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs">
                            <option value="15" {{ $perPage == 15 ? 'selected' : '' }}>15</option>
                            <option value="25" {{ $perPage == 25 ? 'selected' : '' }}>25</option>
                            <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50</option>
                            <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100</option>
                        </select>
                    </div>
                    <div class="w-1/2 flex space-x-1">
                        <button type="submit" 
                                class="w-full py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold transition shadow-xs text-center">
                            Filter
                        </button>
                        @if($searchTerm || $selectedBatchId || $tierFilter !== 'all' || $selectedClassId)
                        <a href="{{ route('students.index') }}" 
                           title="Reset all filters"
                           class="px-2 py-2 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 rounded-xl text-xs font-bold transition">
                            ✕
                        </a>
                        @endif
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- 3. STUDENTS LIST VIEW TABLE -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden transition-colors">
        <div class="p-4 border-b border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider flex items-center space-x-2">
                    <span>👥</span>
                    <span>Students List View</span>
                    <span class="px-2 py-0.5 rounded-full text-xs bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-extrabold">
                        {{ $students->total() }} Total
                    </span>
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    Showing {{ $students->firstItem() ?? 0 }} to {{ $students->lastItem() ?? 0 }} of {{ $students->total() }} students
                </p>
            </div>
        </div>

        @if($students->isEmpty())
        <div class="p-12 text-center">
            <div class="w-12 h-12 mx-auto rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center text-xl mb-3">
                🔍
            </div>
            <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200">No students found matching your criteria</h3>
            <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">Try adjusting the search query, selecting another class, or resetting filters.</p>
            <a href="{{ route('students.index') }}" class="inline-block mt-4 px-4 py-2 bg-emerald-600 text-white rounded-xl text-xs font-bold hover:bg-emerald-500 transition">
                Reset All Filters
            </a>
        </div>
        @else
        <div class="overflow-x-auto">
            <table id="students-roster-table" class="w-full border-collapse text-left text-xs">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-800 text-xs font-black uppercase tracking-wider text-slate-700 dark:text-slate-300">
                        <th class="p-3 w-14 text-center">Roll</th>
                        <th class="p-3">Student Dossier</th>
                        <th class="p-3">Class &amp; Section</th>
                        <th class="p-3">Contact Details</th>
                        <th class="p-3">Guardian Info</th>
                        <th class="p-3">Scholar Tier &amp; XP</th>
                        <th class="p-3 text-right">Grab / Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach($students as $s)
                    @php
                        $enrollment = $s->classEnrollments->first();
                        $academicClass = $enrollment?->academicClass ?? $s->currentClass;
                        $rollNumber = $enrollment?->roll_number ?? 'N/A';
                        $sectionName = $academicClass?->section ?? 'A';
                        $streamName = $academicClass?->stream ?? 'General';
                        $batchName = 'Section ' . $sectionName;

                        // Prepare student json for Alpine modal
                        $modalStudentData = [
                            'id' => $s->id,
                            'name' => $s->name,
                            'student_id_number' => $s->student_id_number,
                            'email' => $s->email,
                            'phone' => $s->phone,
                            'class_name' => $academicClass?->name ?? 'Unassigned',
                            'section' => $sectionName,
                            'stream' => $streamName,
                            'roll_number' => $rollNumber,
                            'batch_name' => $batchName,
                            'batch_shift' => $streamName,
                            'batch_timings' => $academicClass?->room_number ?? 'Room 101',
                            'guardian_name' => $s->guardian_name,
                            'guardian_phone' => $s->guardian_phone,
                            'guardian_email' => $s->guardian_email,
                            'address' => $s->address,
                            'blood_group' => $s->blood_group,
                            'date_of_birth' => $s->date_of_birth ? $s->date_of_birth->format('d M Y') : 'N/A',
                            'academic_tier' => $s->academic_tier ?? 'Junior Scholar',
                            'xp_points' => $s->xp_points ?? 0,
                            'streak_count' => $s->streak_count ?? 0,
                            'is_approved' => (bool) $s->is_approved,
                            'card_valid_until' => $s->card_valid_until ? $s->card_valid_until->format('d M Y') : 'N/A',
                        ];
                    @endphp
                    <tr class="student-row hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition"
                        data-roll="{{ $rollNumber }}"
                        data-sid="{{ $s->student_id_number }}"
                        data-name="{{ $s->name }}"
                        data-cls="{{ $academicClass?->display_name ?? 'N/A' }}"
                        data-section="{{ $sectionName }}"
                        data-batch="{{ $batchName }}"
                        data-phone="{{ $s->phone ?? 'N/A' }}"
                        data-email="{{ $s->email }}"
                        data-gname="{{ $s->guardian_name ?? 'N/A' }}"
                        data-gphone="{{ $s->guardian_phone ?? 'N/A' }}">
                        
                        <!-- 1. ROLL NUMBER -->
                        <td class="p-3 text-center align-middle">
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800 font-mono font-bold text-emerald-800 dark:text-emerald-300 text-xs">
                                {{ $rollNumber !== 'N/A' ? '#' . $rollNumber : '-' }}
                            </span>
                        </td>

                        <!-- 2. STUDENT DOSSIER & NAME -->
                        <td class="p-3 align-middle">
                            <div class="flex items-center space-x-3">
                                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-500 text-white font-extrabold flex items-center justify-center text-xs flex-shrink-0 shadow-xs">
                                    {{ strtoupper(substr($s->name, 0, 2)) }}
                                </div>
                                <div>
                                    <div class="flex items-center space-x-1.5">
                                        <button type="button" 
                                                @click="activeModalStudent = {{ json_encode($modalStudentData) }}" 
                                                class="font-black text-slate-900 dark:text-white hover:text-emerald-600 dark:hover:text-emerald-400 text-xs sm:text-sm text-left">
                                            {{ $s->name }}
                                        </button>
                                        @if($s->is_approved)
                                        <span class="w-2 h-2 rounded-full bg-emerald-500" title="Active Account"></span>
                                        @endif
                                    </div>
                                    <div class="flex items-center space-x-2 mt-0.5">
                                        <span class="font-mono text-[10px] font-bold px-1.5 py-0.2 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400">
                                            {{ $s->student_id_number }}
                                        </span>
                                        <a href="{{ route('idcard.show', $s->id) }}" 
                                           class="text-[10px] text-emerald-600 dark:text-emerald-400 font-semibold hover:underline">
                                            Virtual ID &rarr;
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </td>

                        <!-- 3. CLASS & SECTION -->
                        <td class="p-3 align-middle">
                            <div class="space-y-1">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                                    {{ $academicClass?->display_name ?? 'Unassigned Class' }}
                                </span>
                                <div class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-purple-100 dark:bg-purple-950 text-purple-800 dark:text-purple-300">
                                        Section {{ $academicClass?->section ?? 'A' }}
                                    </span>
                                    @if($academicClass?->room_number)
                                        &bull; <span class="font-semibold text-slate-600 dark:text-slate-400">📍 {{ $academicClass->room_number }}</span>
                                    @endif
                                </div>
                            </div>
                        </td>

                        <!-- 4. CONTACT DETAILS -->
                        <td class="p-3 align-middle">
                            <div class="space-y-0.5">
                                @if($s->phone)
                                <a href="tel:{{ $s->phone }}" class="text-xs font-bold text-slate-800 dark:text-slate-200 hover:text-emerald-600 dark:hover:text-emerald-400 flex items-center space-x-1">
                                    <span>📞</span>
                                    <span>{{ $s->phone }}</span>
                                </a>
                                @else
                                <span class="text-xs text-slate-400 italic">No phone recorded</span>
                                @endif
                                <a href="mailto:{{ $s->email }}" class="text-[11px] text-slate-500 dark:text-slate-400 hover:underline flex items-center space-x-1">
                                    <span>✉️</span>
                                    <span>{{ $s->email }}</span>
                                </a>
                            </div>
                        </td>

                        <!-- 5. GUARDIAN INFO -->
                        <td class="p-3 align-middle">
                            <div class="space-y-0.5">
                                <div class="font-semibold text-xs text-slate-800 dark:text-slate-200">
                                    {{ $s->guardian_name ?? 'Not Listed' }}
                                </div>
                                @if($s->guardian_phone)
                                <a href="tel:{{ $s->guardian_phone }}" class="text-[11px] text-slate-500 dark:text-slate-400 hover:text-emerald-600 block">
                                    📱 {{ $s->guardian_phone }}
                                </a>
                                @endif
                            </div>
                        </td>

                        <!-- 6. ACADEMIC TIER & XP -->
                        <td class="p-3 align-middle">
                            <div class="space-y-1">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black
                                    @if(($s->academic_tier ?? '') === 'Presidential Fellow') bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-300 border border-purple-300
                                    @elseif(($s->academic_tier ?? '') === 'Distinguished Scholar') bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 border border-amber-300
                                    @elseif(($s->academic_tier ?? '') === 'Honors Scholar') bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300 border border-blue-300
                                    @else bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border border-slate-300
                                    @endif">
                                    {{ $s->academic_tier ?? 'Junior Scholar' }}
                                </span>
                                <div class="text-[11px] text-slate-500 dark:text-slate-400 flex items-center space-x-2">
                                    <span class="font-bold text-amber-600 dark:text-amber-400">⚡ {{ $s->xp_points ?? 0 }} XP</span>
                                    <span>&bull;</span>
                                    <span>🔥 {{ $s->streak_count ?? 0 }}d streak</span>
                                </div>
                            </div>
                        </td>

                        <!-- 7. ACTIONS: VIEW, EDIT, DELETE & 1-CLICK GRAB INFO -->
                        <td class="p-3 align-middle text-right">
                            <div class="inline-flex items-center space-x-1.5">
                                <a href="{{ route('students.show', $s->id) }}" 
                                   title="View Full Student Dossier"
                                   class="px-2.5 py-1.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-xl text-xs font-bold transition flex items-center space-x-1">
                                    <span>👁️</span>
                                    <span class="hidden sm:inline">View</span>
                                </a>

                                <a href="{{ route('students.edit', $s->id) }}" 
                                   title="Edit Student Information"
                                   class="px-2.5 py-1.5 bg-indigo-50 dark:bg-indigo-950/60 hover:bg-indigo-100 dark:hover:bg-indigo-900 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 rounded-xl text-xs font-bold transition flex items-center space-x-1">
                                    <span>✏️</span>
                                    <span class="hidden sm:inline">Edit</span>
                                </a>

                                <a href="{{ route('students.show', $student->id ?? $s->id) }}" 
                                   title="Delete Student Profile"
                                   class="px-2 py-1.5 bg-rose-50 dark:bg-rose-950/60 hover:bg-rose-100 dark:hover:bg-rose-900 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800 rounded-xl text-xs font-bold transition flex items-center">
                                    <span>🗑️</span>
                                </a>

                                <button type="button" 
                                        @click="copyStudentInfo({{ json_encode($modalStudentData) }})" 
                                        title="Copy Student Info Dossier to Clipboard"
                                        class="px-2 py-1.5 bg-emerald-50 dark:bg-emerald-950/60 hover:bg-emerald-100 dark:hover:bg-emerald-900 text-emerald-700 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800 rounded-xl text-xs font-bold transition flex items-center">
                                    <span>📋</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- 4. PAGINATION FOOTER -->
        <div class="p-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30">
            {{ $students->withQueryString()->links() }}
        </div>
        @endif
    </div>

    <!-- 5. STUDENT DOSSIER MODAL -->
    <div x-show="activeModalStudent !== null" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
         @keydown.escape.window="activeModalStudent = null">
        
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl max-w-lg w-full p-6 shadow-2xl relative"
             @click.away="activeModalStudent = null">
            
            <template x-if="activeModalStudent">
                <div>
                    <!-- Header -->
                    <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800">
                        <div class="flex items-center space-x-3">
                            <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-emerald-600 to-teal-500 text-white font-black text-lg flex items-center justify-center shadow-md">
                                <span x-text="activeModalStudent.name.substring(0, 2).toUpperCase()"></span>
                            </div>
                            <div>
                                <h3 class="text-base font-extrabold text-slate-900 dark:text-white" x-text="activeModalStudent.name"></h3>
                                <div class="flex items-center space-x-2 mt-0.5">
                                    <span class="font-mono text-xs px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold" x-text="activeModalStudent.student_id_number"></span>
                                    <span class="text-xs text-emerald-600 dark:text-emerald-400 font-bold" x-text="'Roll #' + activeModalStudent.roll_number"></span>
                                </div>
                            </div>
                        </div>
                        <button type="button" @click="activeModalStudent = null" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-xl leading-none">&times;</button>
                    </div>

                    <!-- Details Grid -->
                    <div class="py-4 space-y-3 text-xs max-h-[60vh] overflow-y-auto pr-1">
                        <div class="grid grid-cols-2 gap-3">
                            <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/60 dark:border-slate-700/60">
                                <span class="text-[10px] font-bold uppercase text-slate-500">Academic Class</span>
                                <div class="font-bold text-slate-900 dark:text-white mt-0.5" x-text="activeModalStudent.class_name + ' (Sec ' + activeModalStudent.section + ')'"></div>
                            </div>
                            <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/60 dark:border-slate-700/60">
                                <span class="text-[10px] font-bold uppercase text-slate-500">Section &amp; Stream</span>
                                <div class="font-bold text-slate-900 dark:text-white mt-0.5" x-text="'Section ' + activeModalStudent.section + ' (' + activeModalStudent.stream + ')'"></div>
                            </div>
                        </div>

                        <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/60 dark:border-slate-700/60 space-y-1">
                            <span class="text-[10px] font-bold uppercase text-slate-500">Contact Dossier</span>
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500">Phone:</span>
                                <a :href="'tel:' + activeModalStudent.phone" class="font-bold text-emerald-600 dark:text-emerald-400 hover:underline" x-text="activeModalStudent.phone || 'N/A'"></a>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500">Email:</span>
                                <a :href="'mailto:' + activeModalStudent.email" class="font-bold text-slate-900 dark:text-white hover:underline" x-text="activeModalStudent.email"></a>
                            </div>
                        </div>

                        <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/60 dark:border-slate-700/60 space-y-1">
                            <span class="text-[10px] font-bold uppercase text-slate-500">Guardian Information</span>
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500">Name:</span>
                                <span class="font-bold text-slate-900 dark:text-white" x-text="activeModalStudent.guardian_name || 'N/A'"></span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500">Phone:</span>
                                <a :href="'tel:' + activeModalStudent.guardian_phone" class="font-bold text-emerald-600 dark:text-emerald-400 hover:underline" x-text="activeModalStudent.guardian_phone || 'N/A'"></a>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500">Email:</span>
                                <span class="font-medium text-slate-700 dark:text-slate-300" x-text="activeModalStudent.guardian_email || 'N/A'"></span>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/60 dark:border-slate-700/60">
                                <span class="text-[10px] font-bold uppercase text-slate-500">Date of Birth</span>
                                <div class="font-bold text-slate-900 dark:text-white mt-0.5" x-text="activeModalStudent.date_of_birth"></div>
                            </div>
                            <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/60 dark:border-slate-700/60">
                                <span class="text-[10px] font-bold uppercase text-slate-500">Blood Group</span>
                                <div class="font-bold text-rose-600 dark:text-rose-400 mt-0.5" x-text="activeModalStudent.blood_group || 'N/A'"></div>
                            </div>
                        </div>

                        <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/60 dark:border-slate-700/60">
                            <span class="text-[10px] font-bold uppercase text-slate-500">Residential Address</span>
                            <div class="font-medium text-slate-800 dark:text-slate-200 mt-0.5" x-text="activeModalStudent.address || 'N/A'"></div>
                        </div>
                    </div>

                    <!-- Footer Actions -->
                    <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex flex-wrap items-center justify-between gap-2">
                        <div class="flex items-center space-x-2">
                            <a :href="'/students/' + activeModalStudent.id" 
                               class="px-3 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold transition">
                                View Full Dossier &rarr;
                            </a>
                            <a :href="'/students/' + activeModalStudent.id + '/edit'" 
                               class="px-3 py-2 bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 rounded-xl text-xs font-bold hover:bg-indigo-100 transition">
                                ✏️ Edit Info
                            </a>
                        </div>

                        <div class="flex items-center space-x-2">
                            <a :href="'/id-card/' + activeModalStudent.id" 
                               target="_blank"
                               class="px-3 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-xl text-xs font-bold transition">
                                🪪 Virtual ID
                            </a>
                            <button type="button" 
                                    @click="copyStudentInfo(activeModalStudent)" 
                                    class="px-3 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-xl text-xs font-bold transition">
                                📋 Copy
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

</div>
@endsection
