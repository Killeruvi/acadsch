@extends('layouts.app')

@section('title', 'Bulk Ingestion & Export Hub - School Management System')

@section('content')
<div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-4 space-y-4" x-data="{ activeTab: '{{ $activeTab }}' }">

    <!-- Header Strip -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-2 border-b border-slate-200 dark:border-slate-800">
        <div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight">Bulk Ingestion &amp; Export Hub</h1>
            <div class="flex flex-wrap items-center gap-1.5 mt-1">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                    Engine: Enterprise CSV
                </span>
                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                    Max Upload: 5MB
                </span>
                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                    Encoding: UTF-8
                </span>
            </div>
        </div>

        <div class="flex items-center flex-wrap gap-2">
            <a href="{{ route('school.onboarding.index') }}" 
               class="px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold transition shadow-xs flex items-center space-x-1.5">
                <span>🚀 School Onboarding Wizard</span>
            </a>
            <a href="{{ route('school.students.index') }}" 
               class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 text-xs font-bold transition">
                <span>Student Directory &rarr;</span>
            </a>
        </div>
    </div>

    <!-- Live System Metric Row -->
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
        <div class="bg-white dark:bg-slate-900 p-3 rounded-xl border border-slate-200 dark:border-slate-800 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Students</span>
                <span class="text-[10px] px-1.5 py-0.5 rounded font-bold bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300">Scholars</span>
            </div>
            <div class="text-xl font-black text-slate-900 dark:text-white mt-1">{{ $stats['total_students'] }}</div>
        </div>
        <div class="bg-white dark:bg-slate-900 p-3 rounded-xl border border-slate-200 dark:border-slate-800 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Parents</span>
                <span class="text-[10px] px-1.5 py-0.5 rounded font-bold bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-300">Guardians</span>
            </div>
            <div class="text-xl font-black text-slate-900 dark:text-white mt-1">{{ $stats['total_parents'] ?? 0 }}</div>
        </div>
        <div class="bg-white dark:bg-slate-900 p-3 rounded-xl border border-slate-200 dark:border-slate-800 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Faculty &amp; Staff</span>
                <span class="text-[10px] px-1.5 py-0.5 rounded font-bold bg-indigo-100 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300">Staff</span>
            </div>
            <div class="text-xl font-black text-slate-900 dark:text-white mt-1">{{ $stats['total_teachers'] + $stats['total_admins'] }}</div>
        </div>
        <div class="bg-white dark:bg-slate-900 p-3 rounded-xl border border-slate-200 dark:border-slate-800 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Classes</span>
                <span class="text-[10px] px-1.5 py-0.5 rounded font-bold bg-cyan-100 dark:bg-cyan-950 text-cyan-700 dark:text-cyan-300">Sections</span>
            </div>
            <div class="text-xl font-black text-slate-900 dark:text-white mt-1">{{ $stats['total_classes'] }}</div>
        </div>
        <div class="bg-white dark:bg-slate-900 p-3 rounded-xl border border-slate-200 dark:border-slate-800 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Subjects</span>
                <span class="text-[10px] px-1.5 py-0.5 rounded font-bold bg-purple-100 dark:bg-purple-950 text-purple-700 dark:text-purple-300">Courses</span>
            </div>
            <div class="text-xl font-black text-slate-900 dark:text-white mt-1">{{ $stats['total_subjects'] }}</div>
        </div>
    </div>

    @if(session('bulk_last_credentials'))
    @php $credCount = count(session('bulk_last_credentials')); @endphp
    <!-- Active Credentials Dispatch Card -->
    <div class="p-4 rounded-2xl bg-gradient-to-r from-emerald-500/10 via-teal-500/10 to-indigo-500/10 border-2 border-emerald-500/30 flex flex-col md:flex-row md:items-center justify-between gap-3 shadow-md">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center text-lg font-black shrink-0 shadow-sm">
                🔑
            </div>
            <div>
                <h4 class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-1.5">
                    <span>Credentials Generated &bull; {{ $credCount }} New User Accounts Ready</span>
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                </h4>
                <p class="text-[11px] text-slate-600 dark:text-slate-400 mt-0.5">
                    Temporary passwords generated securely. Export credential slips or bulk email credentials directly to recipients.
                </p>
            </div>
        </div>

        <div class="flex items-center flex-wrap gap-2 shrink-0">
            <a href="{{ route('school.bulk.credentials.export') }}" 
               class="px-3.5 py-2 rounded-xl bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 text-xs font-bold transition shadow-xs flex items-center space-x-1.5">
                <span>⬇️ Download Credentials CSV</span>
            </a>

            <form method="POST" action="{{ route('school.bulk.credentials.email') }}">
                @csrf
                <button type="submit" 
                        onclick="return confirm('Send official login credential emails to all {{ $credCount }} newly ingested accounts?');"
                        class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold transition shadow-sm flex items-center space-x-1.5">
                    <span>✉️ Bulk Send Passwords via Email</span>
                </button>
            </form>
        </div>
    </div>
    @endif

    <!-- Unified Tab Selector -->
    <div class="flex flex-wrap items-center gap-1.5 border-b border-slate-200 dark:border-slate-800 pb-2">
        <button type="button" @click="activeTab = 'students'" 
                :class="activeTab === 'students' ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 hover:bg-slate-200'"
                class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition flex items-center space-x-1.5">
            <span>🎓 Student Admissions</span>
        </button>

        <button type="button" @click="activeTab = 'parents'" 
                :class="activeTab === 'parents' ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 hover:bg-slate-200'"
                class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition flex items-center space-x-1.5">
            <span>👪 Parents &amp; Guardians</span>
        </button>

        <button type="button" @click="activeTab = 'staff'" 
                :class="activeTab === 'staff' ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 hover:bg-slate-200'"
                class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition flex items-center space-x-1.5">
            <span>👨‍🏫 Faculty &amp; Staff</span>
        </button>

        <button type="button" @click="activeTab = 'classes_subjects'" 
                :class="activeTab === 'classes_subjects' ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 hover:bg-slate-200'"
                class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition flex items-center space-x-1.5">
            <span>🏛️ Classes &amp; Subjects</span>
        </button>
    </div>

    <!-- TAB 1: STUDENTS BULK ACTIONS -->
    <div x-show="activeTab === 'students'" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            
            <!-- Import Card -->
            <div class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-xs space-y-3">
                <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center space-x-2">
                        <span class="w-7 h-7 rounded-lg bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 flex items-center justify-center font-bold text-xs">📥</span>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Bulk Ingest Students</h3>
                    </div>
                    <a href="{{ route('school.bulk.sample', 'students') }}" download="sample_students_import_template.csv" data-no-spa="true"
                       class="text-[11px] font-bold text-emerald-600 hover:underline flex items-center space-x-1">
                        <span>⬇️ Sample CSV Template</span>
                    </a>
                </div>

                <form method="POST" action="{{ route('school.bulk.students.import') }}" enctype="multipart/form-data" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Select Student Roster File (.CSV) *</label>
                        <input type="file" name="csv_file" accept=".csv,text/csv" required
                               class="w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 dark:file:bg-slate-800 dark:file:text-slate-300">
                    </div>

                    <div class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 text-[11px] text-slate-600 dark:text-slate-400 space-y-1">
                        <div><strong>Class matching:</strong> Class and section must already exist for the active academic session. This import never creates or guesses master data.</div>
                        <div><strong>Account security:</strong> New student and parent accounts receive one-time password setup links. Passwords are never shown or exported.</div>
                    </div>

                    <div class="space-y-1.5 pt-1">
                        <label class="flex items-center space-x-2 text-xs font-bold text-slate-700 dark:text-slate-300 cursor-pointer">
                            <input type="checkbox" name="create_parent_accounts" value="1" checked class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                            <span>👪 Create and link parent accounts; one-time setup links are emailed</span>
                        </label>
                    </div>

                    <div class="pt-1 flex justify-end">
                        <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-bold shadow-xs transition flex items-center space-x-1.5">
                            <span>🚀 Upload &amp; Ingest Student Roster</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Export Card -->
            <div class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-xs space-y-3">
                <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center space-x-2">
                        <span class="w-7 h-7 rounded-lg bg-blue-100 dark:bg-blue-950 text-blue-700 dark:text-blue-300 flex items-center justify-center font-bold text-xs">📤</span>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Export Student Directory</h3>
                    </div>
                    <span class="text-[11px] text-slate-400">Total: {{ $stats['total_students'] }} scholars</span>
                </div>

                <form method="GET" action="{{ route('school.bulk.students.export') }}" class="space-y-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Filter by Class (Optional)</label>
                        <select name="class_id" class="w-full px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                            <option value="">-- All Classes &amp; Sections (Complete School) --</option>
                            @foreach($classes as $c)
                            <option value="{{ $c->id }}">{{ $c->name }} - {{ $c->section }} ({{ $c->students_count }} enrolled)</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 text-[11px] text-slate-600 dark:text-slate-400">
                        Exports complete student dossier including Admission No, Roll No, Class, Section, Guardian Contact, and Blood Group.
                    </div>

                    <div class="pt-1 flex justify-end">
                        <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 dark:bg-slate-700 dark:hover:bg-slate-600 text-white rounded-lg text-xs font-bold shadow-xs transition flex items-center space-x-1.5">
                            <span>📥 Download Student Master CSV</span>
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>

    <!-- TAB 2: STAFF BULK ACTIONS -->
    <div x-show="activeTab === 'staff'" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            
            <!-- Import Staff -->
            <div class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-xs space-y-3">
                <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center space-x-2">
                        <span class="w-7 h-7 rounded-lg bg-indigo-100 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 flex items-center justify-center font-bold text-xs">📥</span>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Bulk Ingest Faculty &amp; Staff</h3>
                    </div>
                    <a href="{{ route('school.bulk.sample', 'staff') }}" download="sample_staff_import_template.csv" data-no-spa="true"
                       class="text-[11px] font-bold text-indigo-600 hover:underline flex items-center space-x-1">
                        <span>⬇️ Sample CSV Template</span>
                    </a>
                </div>

                <form method="POST" action="{{ route('school.bulk.staff.import') }}" enctype="multipart/form-data" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Select Faculty Roster File (.CSV) *</label>
                        <input type="file" name="csv_file" accept=".csv,text/csv" required
                               class="w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-slate-800 dark:file:text-slate-300">
                    </div>

                    <div class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 text-[11px] text-slate-600 dark:text-slate-400 space-y-1">
                        <div><strong>Roles Supported:</strong> <code>teacher</code>, <code>admin</code>.</div>
                        <div><strong>Credentials:</strong> Ingested faculty accounts are pre-approved with secure credentials.</div>
                    </div>

                    <div class="pt-1">
                        <label class="flex items-center space-x-2 text-xs font-bold text-slate-700 dark:text-slate-300 cursor-pointer">
                            <input type="checkbox" name="auto_generate_password" value="1" checked class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            <span>🔐 Auto-generate unique secure temporary passwords</span>
                        </label>
                    </div>

                    <div class="pt-1 flex justify-end">
                        <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg text-xs font-bold shadow-xs transition flex items-center space-x-1.5">
                            <span>🚀 Upload &amp; Ingest Faculty</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Export Staff -->
            <div class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-xs space-y-3">
                <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center space-x-2">
                        <span class="w-7 h-7 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 flex items-center justify-center font-bold text-xs">📤</span>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Export Staff Directory</h3>
                    </div>
                    <span class="text-[11px] text-slate-400">Total: {{ $stats['total_teachers'] + $stats['total_admins'] }} members</span>
                </div>

                <div class="p-3 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 text-[11px] text-slate-600 dark:text-slate-400">
                    Generates a master roster of all verified teachers, department heads, and campus administrative staff members.
                </div>

                <div class="pt-2 flex justify-end">
                    <a href="{{ route('school.bulk.staff.export') }}" 
                       class="px-4 py-2 bg-slate-800 hover:bg-slate-700 dark:bg-slate-700 dark:hover:bg-slate-600 text-white rounded-lg text-xs font-bold shadow-xs transition flex items-center space-x-1.5">
                        <span>📥 Download Faculty Roster CSV</span>
                    </a>
                </div>
            </div>

        </div>
    </div>

    <!-- TAB: PARENTS & GUARDIANS BULK ACTIONS -->
    <div x-show="activeTab === 'parents'" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            
            <!-- Import Parents -->
            <div class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-xs space-y-3">
                <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center space-x-2">
                        <span class="w-7 h-7 rounded-lg bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-300 flex items-center justify-center font-bold text-xs">📥</span>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Bulk Ingest Parents &amp; Guardians</h3>
                    </div>
                    <a href="{{ route('school.bulk.sample', 'parents') }}" download="sample_parents_import_template.csv" data-no-spa="true"
                       class="text-[11px] font-bold text-amber-600 hover:underline flex items-center space-x-1">
                        <span>⬇️ Sample CSV Template</span>
                    </a>
                </div>

                <form method="POST" action="{{ route('school.bulk.parents.import') }}" enctype="multipart/form-data" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Select Parent Roster File (.CSV) *</label>
                        <input type="file" name="csv_file" accept=".csv,text/csv" required
                               class="w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100 dark:file:bg-slate-800 dark:file:text-slate-300">
                    </div>

                    <div class="pt-1">
                        <label class="flex items-center space-x-2 text-xs font-bold text-slate-700 dark:text-slate-300 cursor-pointer">
                            <input type="checkbox" name="auto_generate_password" value="1" checked class="rounded border-slate-300 text-amber-600 focus:ring-amber-500">
                            <span>🔐 Auto-generate unique secure temporary passwords for parents</span>
                        </label>
                    </div>

                    <div class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 text-[11px] text-slate-600 dark:text-slate-400 space-y-1">
                        <div><strong>Parent Linkage:</strong> Automatically links parent user to student via <code>Student Admission No</code> or <code>Email</code>.</div>
                        <div><strong>Parent Portal Access:</strong> Grants immediate access to attendance, term report cards, fee settlements, and leave management.</div>
                    </div>

                    <div class="pt-1 flex justify-end">
                        <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-500 text-white rounded-lg text-xs font-bold shadow-xs transition flex items-center space-x-1.5">
                            <span>🚀 Upload &amp; Ingest Parents</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Export Parents -->
            <div class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-xs space-y-3">
                <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center space-x-2">
                        <span class="w-7 h-7 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 flex items-center justify-center font-bold text-xs">📤</span>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Export Parents Directory</h3>
                    </div>
                    <span class="text-[11px] text-slate-400">Total: {{ $stats['total_parents'] ?? 0 }} guardians</span>
                </div>

                <div class="p-3 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 text-[11px] text-slate-600 dark:text-slate-400">
                    Exports complete parent contact registry including guardian names, verified emails, phone numbers, residential addresses, and linked child scholars.
                </div>

                <div class="pt-2 flex justify-end">
                    <a href="{{ route('school.bulk.parents.export') }}" 
                       class="px-4 py-2 bg-slate-800 hover:bg-slate-700 dark:bg-slate-700 dark:hover:bg-slate-600 text-white rounded-lg text-xs font-bold shadow-xs transition flex items-center space-x-1.5">
                        <span>📥 Download Parents Master CSV</span>
                    </a>
                </div>
            </div>

        </div>
    </div>

    <!-- TAB 3: CLASSES & SUBJECTS BULK ACTIONS -->
    <div x-show="activeTab === 'classes_subjects'" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            
            <!-- Import Classes & Subjects -->
            <div class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-xs space-y-3">
                <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center space-x-2">
                        <span class="w-7 h-7 rounded-lg bg-purple-100 dark:bg-purple-950 text-purple-700 dark:text-purple-300 flex items-center justify-center font-bold text-xs">📥</span>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Bulk Ingest Classes &amp; Subjects</h3>
                    </div>
                    <a href="{{ route('school.bulk.sample', 'classes_subjects') }}" download="sample_classes_subjects_template.csv" data-no-spa="true"
                       class="text-[11px] font-bold text-purple-600 hover:underline flex items-center space-x-1">
                        <span>⬇️ Sample CSV Template</span>
                    </a>
                </div>

                <form method="POST" action="{{ route('school.bulk.classes-subjects.import') }}" enctype="multipart/form-data" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Select Curriculum Structure File (.CSV) *</label>
                        <input type="file" name="csv_file" accept=".csv,text/csv" required
                               class="w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100 dark:file:bg-slate-800 dark:file:text-slate-300">
                    </div>

                    <div class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 text-[11px] text-slate-600 dark:text-slate-400 space-y-1">
                        <div><strong>Columns:</strong> Grade Level, Class Name, Section, Room Number, Subject Name, Subject Code, Weekly Periods.</div>
                        <div><strong>Idempotent:</strong> Existing classes and subjects are matched safely without duplication.</div>
                    </div>

                    <div class="pt-1 flex justify-end">
                        <button type="submit" class="px-4 py-2 bg-purple-600 hover:bg-purple-500 text-white rounded-lg text-xs font-bold shadow-xs transition flex items-center space-x-1.5">
                            <span>🚀 Ingest Curriculum Structure</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Export Classes & Subjects -->
            <div class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-xs space-y-3">
                <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center space-x-2">
                        <span class="w-7 h-7 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 flex items-center justify-center font-bold text-xs">📤</span>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Export Curriculum Master</h3>
                    </div>
                    <span class="text-[11px] text-slate-400">{{ $stats['total_classes'] }} classes &bull; {{ $stats['total_subjects'] }} subjects</span>
                </div>

                <div class="p-3 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 text-[11px] text-slate-600 dark:text-slate-400">
                    Downloads the comprehensive academic structure mapping every class, section, room designation, and weekly teaching period requirement.
                </div>

                <div class="pt-2 flex justify-end">
                    <a href="{{ route('school.bulk.classes-subjects.export') }}" 
                       class="px-4 py-2 bg-slate-800 hover:bg-slate-700 dark:bg-slate-700 dark:hover:bg-slate-600 text-white rounded-lg text-xs font-bold shadow-xs transition flex items-center space-x-1.5">
                        <span>📥 Download Curriculum CSV</span>
                    </a>
                </div>
            </div>

        </div>
    </div>

</div>
@endsection
