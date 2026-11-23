@extends('layouts.app')

@section('title', 'Courses' . ' — ' . ($school['name'] ?? 'PrimeOS Academy'))

@section('content')
<div x-data="{ 
    openCreateModal: {{ $errors->any() ? 'true' : 'false' }},
    openEditModal: false,
    editCourseId: null,
    editCourseTitle: '',
    editCourseCode: '',
    editCourseCategory: '',
    editCourseCredits: 4,
    editCourseCapacity: 30,
    editCourseTeacherId: '',
    editCourseStatus: 'active',
    editCourseDescription: ''
}" class="space-y-4">

    <!-- INFIX BREADCRUMBS & HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <!-- Breadcrumbs -->
            <div class="flex items-center space-x-2 text-[11px] text-slate-400 font-semibold mb-1">
                <a href="{{ route('dashboard') }}" class="hover:text-slate-600 dark:hover:text-slate-200 transition">Dashboard</a>
                <span>/</span>
                <span class="text-slate-500">Learning Arena</span>
                <span>/</span>
                <span class="text-slate-800 dark:text-slate-200 font-bold">Courses Catalog</span>
            </div>

            <div class="flex items-center space-x-2.5">
                <span class="w-8 h-8 rounded-lg flex items-center justify-center text-white text-sm shadow-xs" style="background: var(--system_primary_gradient);">
                    <i class="ti-book"></i>
                </span>
                <h1 class="text-lg sm:text-xl font-black text-slate-900 dark:text-white tracking-tight">
                    Courses Catalog
                </h1>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold text-white shadow-2xs" style="background: var(--system_primary_gradient);">
                    Infix Courses
                </span>
            </div>
            <div class="flex flex-wrap items-center gap-1.5 mt-1.5">
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-50 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-800">
                    Electives &amp; Programs
                </span>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                    Institutional Catalog
                </span>
                <a href="{{ route('my-batch') }}" class="text-[11px] font-bold hover:underline ml-1 theme_color">
                    Open My Batch &rarr;
                </a>
            </div>
        </div>

        <div class="flex items-center flex-wrap gap-2">
            @auth
            <a href="{{ route('my-batch') }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-xl text-xs font-bold shadow-xs transition">
                🎒 Go to My Batch
            </a>
            @if(Auth::check() && (Auth::user()->isAdmin() || Auth::user()->isTeacher()))
            <button @click.stop="openCreateModal = true" 
                    class="primary-btn" style="padding: 0.4rem 1.1rem; font-size: 0.75rem;">
                <i class="ti-plus text-xs"></i>
                <span>+ New Course</span>
            </button>
            @endif
            @else
            <a href="{{ route('register') }}" class="primary-btn" style="padding: 0.4rem 1.1rem; font-size: 0.75rem;">
                <span>Enroll as Scholar &rarr;</span>
            </a>
            @endauth
        </div>
    </div>

        <!-- Filter & Search Bar (Compact) -->
        <div class="mt-3 pt-3 border-t border-slate-100 dark:border-slate-800">
            <form method="GET" action="{{ route('courses.index') }}" class="flex flex-col sm:flex-row gap-2">
                <div class="relative flex-grow">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by code, title, or keywords..."
                           class="w-full pl-8 pr-3 py-1.5 text-xs rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>

                <select name="category" onchange="this.form.submit()" class="px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                    @endforeach
                </select>

                <button type="submit" class="px-3 py-1.5 bg-slate-900 dark:bg-slate-100 text-white dark:text-slate-900 rounded-lg text-xs font-bold transition">
                    Filter
                </button>
                @if(request('search') || request('category'))
                <a href="{{ route('courses.index') }}" class="px-2.5 py-1.5 text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 text-xs flex items-center justify-center">
                    Reset
                </a>
                @endif
            </form>
        </div>
    </div>

    <!-- Course Cards Grid (Infix .couse_wizged Design) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
        @forelse($courses as $course)
        <div class="couse_wizged group">
            <!-- Infix Course Thumbnail with floating price tag & code pill -->
            <div class="thumb">
                <a href="{{ route('courses.show', $course->id) }}" class="w-full h-full flex items-center justify-center relative overflow-hidden">
                    <div class="w-full h-full flex flex-col items-center justify-center p-4 text-center transition-transform duration-300 group-hover:scale-105" style="background: linear-gradient(135deg, var(--system_primery_color_20) 0%, var(--system_primery_color_05) 100%);">
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white text-xl shadow-xs mb-1" style="background: var(--system_primary_gradient);">
                            <i class="ti-book"></i>
                        </div>
                        <span class="text-[11px] font-bold text-slate-700 dark:text-slate-300 truncate max-w-[180px]">{{ $course->category }}</span>
                    </div>

                    <!-- Floating Credits / Price Tag -->
                    <span class="prise_tag">
                        {{ $course->credits }} Cr
                    </span>

                    <!-- Floating Course Code -->
                    <span class="absolute top-3 right-3 px-2 py-0.5 rounded-md text-[10px] font-black bg-slate-900/75 text-white backdrop-blur-xs font-mono shadow-xs">
                        {{ $course->code }}
                    </span>
                </a>
            </div>

            <!-- Infix Course Content Body -->
            <div class="course_content">
                <div>
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">
                        {{ $course->category }}
                    </div>

                    <a href="{{ route('courses.show', $course->id) }}" class="block">
                        <h4 class="line-clamp-2" title="{{ $course->title }}">
                            {{ $course->title }}
                        </h4>
                    </a>

                    <!-- Rating & Faculty Row -->
                    <div class="rating_cart">
                        <div class="rateing">
                            <i class="fas fa-star text-amber-400 text-xs"></i>
                            <span class="text-xs font-bold text-slate-800 dark:text-slate-200">5.0</span>
                        </div>
                        <div class="flex items-center space-x-1.5 text-xs text-slate-500 dark:text-slate-400">
                            <span class="w-4 h-4 rounded-full flex items-center justify-center font-bold text-[9px] text-white" style="background: var(--system_primary_gradient);">
                                {{ strtoupper(substr($course->teacher?->name ?? 'F', 0, 1)) }}
                            </span>
                            <span class="truncate max-w-[95px]">{{ $course->teacher?->name ?? 'Faculty' }}</span>
                        </div>
                    </div>

                    <!-- Lessons & Enrolled Scholars Count -->
                    <div class="course_less_students">
                        <a>
                            <i class="ti-agenda text-theme-primary"></i>
                            <span>{{ $course->credits }} Credits</span>
                        </a>
                        <a>
                            <i class="ti-user text-theme-primary"></i>
                            <span>{{ $course->enrollments_count }}/{{ $course->capacity }}</span>
                        </a>
                    </div>
                </div>

                <!-- Infix Action Buttons Footer -->
                <div class="pt-3 mt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-2">
                    <a href="{{ route('courses.show', $course->id) }}" class="text-xs font-bold text-slate-600 dark:text-slate-300 hover:text-purple-600 transition flex items-center space-x-1">
                        <span>Details</span>
                        <i class="ti-arrow-right text-[10px]"></i>
                    </a>

                    @auth
                        @if(Auth::user()->isStudent())
                            @if(in_array($course->id, $enrolledIds))
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold text-white shadow-2xs" style="background: var(--system_primary_gradient);">
                                    Enrolled ✓
                                </span>
                            @else
                                @if($course->enrollments_count < $course->capacity)
                                <form method="POST" action="{{ route('courses.enroll', $course->id) }}" data-no-spa="false">
                                    @csrf
                                    <button type="submit" class="primary-btn" style="padding: 0.35rem 0.85rem; font-size: 0.72rem;">
                                        <span>Enroll Now</span>
                                    </button>
                                </form>
                                @else
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-200 dark:bg-slate-700 text-slate-500">
                                    Full
                                </span>
                                @endif
                            @endif
                        @elseif(Auth::user()->isTeacher() || Auth::user()->isAdmin())
                            <div class="flex items-center space-x-1.5">
                                <button type="button" 
                                        @click="editCourseId = {{ $course->id }}; editCourseTitle = '{{ addslashes($course->title) }}'; editCourseCode = '{{ addslashes($course->code) }}'; editCourseCategory = '{{ addslashes($course->category) }}'; editCourseCredits = {{ $course->credits }}; editCourseCapacity = {{ $course->capacity }}; editCourseTeacherId = '{{ $course->teacher_id }}'; editCourseStatus = '{{ $course->status }}'; editCourseDescription = '{{ addslashes($course->description ?? '') }}'; openEditModal = true"
                                        class="px-2 py-1 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-200 rounded-lg text-[11px] font-bold transition">
                                    ✏️ Edit
                                </button>
                                <form method="POST" action="{{ route('courses.destroy', $course->id) }}" class="inline" onsubmit="return confirm('CAUTION: Are you sure you want to delete course \'{{ addslashes($course->title) }}\' and all its curriculum data?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-2 py-1 bg-rose-50 dark:bg-rose-950/60 hover:bg-rose-100 text-rose-700 dark:text-rose-300 rounded-lg text-[11px] font-bold border border-rose-200 dark:border-rose-800 transition" title="Delete Course">
                                        🗑️
                                    </button>
                                </form>
                            </div>
                        @endif
                    @else
                        <a href="{{ route('login') }}" class="primary-btn" style="padding: 0.35rem 0.85rem; font-size: 0.72rem;">
                            Sign In
                        </a>
                    @endauth
                </div>
            </div>
        </div>
        @empty
        <div class="col-span-full white-box p-8 text-center text-slate-500 text-xs">
            <p class="font-medium">No courses found matching your criteria.</p>
        </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="mt-4">
        {{ $courses->links() }}
    </div>

    <!-- Create Course Modal (Admin & Teachers) -->
    @if(Auth::check() && (Auth::user()->isAdmin() || Auth::user()->isTeacher()))
    <div x-show="openCreateModal" x-cloak 
         class="fixed inset-0 z-50 overflow-y-auto"
         role="dialog" aria-modal="true"
         @keydown.escape.window="openCreateModal = false">
        
        <!-- Backdrop with smooth blur -->
        <div class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm transition-opacity" 
             @click="openCreateModal = false" 
             aria-hidden="true"></div>

        <!-- Positioning container: ensures proper scrolling without flex-centering clipping and leaves clearance for mobile nav -->
        <div class="min-h-full flex items-start sm:items-center justify-center p-3 pt-3 pb-24 sm:p-6 text-center">
            
            <!-- Modal Card with max-h and flex-col -->
            <div class="relative bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 max-w-2xl w-full text-left my-2 sm:my-4 max-h-[calc(100dvh-7rem)] sm:max-h-[85vh] flex flex-col z-10 overflow-hidden"
                 @click.stop>
                
                <!-- Pinned Header -->
                <div class="flex items-center justify-between p-4 sm:p-5 border-b border-slate-100 dark:border-slate-800 flex-shrink-0 bg-white dark:bg-slate-900">
                    <div class="flex items-center space-x-2.5">
                        <span class="p-2 rounded-xl bg-emerald-100 dark:bg-emerald-950 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        </span>
                        <div>
                            <h3 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white">Create New Course</h3>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400">Configure catalog details, instructor, and curriculum</p>
                        </div>
                    </div>
                    <button type="button" @click="openCreateModal = false" class="p-1.5 rounded-xl text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <!-- Form with Scrollable Body & Sticky Action Footer -->
                <form method="POST" action="{{ route('courses.store') }}" enctype="multipart/form-data" 
                      x-data="{ 
                          courseCode: '{{ old('code', '') }}',
                          courseTitle: '{{ old('title', '') }}',
                          courseCategory: '{{ old('category', '') }}',
                          generateUniqueCode() {
                              let prefix = '';
                              if (this.courseTitle && this.courseTitle.trim()) {
                                  const words = this.courseTitle.trim().split(/[\s\-_]+/);
                                  prefix = words.map(w => w[0]).join('').toUpperCase().slice(0, 4);
                              }
                              if (!prefix || prefix.length < 2) {
                                  prefix = (this.courseCategory ? this.courseCategory.replace(/[^a-zA-Z]/g, '').slice(0, 3).toUpperCase() : 'CRS') || 'CRS';
                              }
                              this.courseCode = prefix + '-' + Math.floor(100 + Math.random() * 900);
                          }
                      }"
                      class="flex flex-col flex-1 min-h-0">
                    @csrf
                    
                    <!-- Scrollable Form Fields -->
                    <div class="flex-1 overflow-y-auto p-4 sm:p-5 space-y-4 overscroll-contain">
                        @if($errors->any())
                        <div class="p-3.5 rounded-xl bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-900 text-xs text-rose-700 dark:text-rose-300">
                            <p class="font-bold mb-1">Please correct the following errors:</p>
                            <ul class="list-disc list-inside space-y-0.5">
                                @foreach($errors->all() as $err)
                                    <li>{{ $err }}</li>
                                @endforeach
                            </ul>
                        </div>
                        @endif

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase">Course Code *</label>
                                    <button type="button" 
                                            @click="generateUniqueCode()" 
                                            class="text-[10px] text-emerald-600 dark:text-emerald-400 font-bold hover:underline">
                                        ⚡ Auto-Generate
                                    </button>
                                </div>
                                <input type="text" name="code" x-model="courseCode" value="{{ old('code') }}" required placeholder="e.g. CS101" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-white text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none uppercase">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Course Title *</label>
                                <input type="text" name="title" x-model="courseTitle" @input="if(!courseCode) generateUniqueCode()" value="{{ old('title') }}" required placeholder="e.g. Data Structures &amp; Algorithms" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-white text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Category *</label>
                                <input type="text" name="category" x-model="courseCategory" list="category-options" value="{{ old('category') }}" required placeholder="e.g. Computer Science" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-white text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                                <datalist id="category-options">
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat }}"></option>
                                    @endforeach
                                </datalist>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Credits *</label>
                                <input type="number" name="credits" value="{{ old('credits', 4) }}" min="1" max="12" required class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-white text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Capacity (Seats) *</label>
                                <input type="number" name="capacity" value="{{ old('capacity', 30) }}" min="1" max="500" required placeholder="30" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-white text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Lead Instructor / Faculty</label>
                                <select name="teacher_id" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-white text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                                    <option value="">-- Assign Instructor --</option>
                                    @foreach($teachers as $teacher)
                                        <option value="{{ $teacher->id }}" {{ (old('teacher_id', (Auth::check() && Auth::user()->isTeacher()) ? Auth::id() : '') == $teacher->id) ? 'selected' : '' }}>
                                            {{ $teacher->name }} ({{ $teacher->role }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Course Status</label>
                                <select name="status" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-white text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                                    <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active (Enrollment Open)</option>
                                    <option value="draft" {{ old('status') === 'draft' ? 'selected' : '' }}>Draft (Under Preparation)</option>
                                    <option value="archived" {{ old('status') === 'archived' ? 'selected' : '' }}>Archived</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Start Date</label>
                                <input type="date" name="start_date" value="{{ old('start_date') }}" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-white text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">End Date</label>
                                <input type="date" name="end_date" value="{{ old('end_date') }}" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-white text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Course Description &amp; Overview</label>
                            <textarea name="description" rows="2" placeholder="Syllabus overview, prerequisite expectations, learning objectives..." class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-white text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none">{{ old('description') }}</textarea>
                        </div>

                        <!-- Manual Setup & Optional Bulk CSV Content Feeding -->
                        <div class="p-3.5 sm:p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-slate-900 dark:text-white flex items-center space-x-1.5">
                                    <span>📑</span>
                                    <span>Curriculum Setup &amp; Content Feeding</span>
                                </span>
                                <span class="text-[10px] font-semibold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950 px-2 py-0.5 rounded-md">
                                    Manual &bull; Bulk CSV
                                </span>
                            </div>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400">
                                Courses are created clean with zero dummy placeholder content. You can manage modules and lessons manually or upload a structured CSV file for bulk content feeding.
                            </p>

                            <div class="space-y-2.5 pt-1">
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-300 uppercase mb-1">Optional Curriculum CSV (Bulk Feed Structure &amp; Lessons)</label>
                                    <input type="file" name="curriculum_csv" accept=".csv,text/csv,text/plain" class="w-full text-xs text-slate-500 dark:text-slate-400 file:mr-2.5 file:py-1 file:px-2.5 file:rounded-xl file:border-0 file:text-[11px] file:font-bold file:bg-indigo-50 file:text-indigo-700 dark:file:bg-indigo-950 dark:file:text-indigo-300 hover:file:bg-indigo-100 transition">
                                    <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5">Upload a CSV containing module titles, lesson numbers, video links, and notes.</p>
                                </div>

                            </div>
                        </div>
                    </div>

                    <!-- Pinned Footer Action Buttons (Never cut off offscreen) -->
                    <div class="flex items-center justify-end space-x-2.5 p-3.5 sm:p-4 border-t border-slate-100 dark:border-slate-800 flex-shrink-0 bg-slate-50/80 dark:bg-slate-850">
                        <button type="button" @click="openCreateModal = false" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white transition">Cancel</button>
                        <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-500 active:scale-95 text-white rounded-xl text-xs font-bold shadow-md shadow-emerald-600/20 transition flex items-center space-x-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            <span>Create Course</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Course Modal (Admin & Teachers) -->
    <div x-show="openEditModal" x-cloak 
         class="fixed inset-0 z-50 overflow-y-auto"
         role="dialog" aria-modal="true"
         @keydown.escape.window="openEditModal = false">
        
        <div class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm transition-opacity" 
             @click="openEditModal = false" 
             aria-hidden="true"></div>

        <div class="min-h-full flex items-center justify-center p-3 sm:p-4">
            <div class="relative w-full max-w-xl bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 flex flex-col max-h-[90vh] overflow-hidden transition-all text-left"
                 @click.stop>
                
                <div class="flex items-center justify-between p-4 sm:p-5 border-b border-slate-100 dark:border-slate-800 flex-shrink-0 bg-white dark:bg-slate-900">
                    <div class="flex items-center space-x-2.5">
                        <span class="p-2 rounded-xl bg-indigo-100 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                            ✏️
                        </span>
                        <div>
                            <h3 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white">Edit Course Details</h3>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400">Update code, title, capacity, instructor, or status</p>
                        </div>
                    </div>
                    <button type="button" @click="openEditModal = false" class="p-1.5 rounded-xl text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                        &times;
                    </button>
                </div>

                <form :action="'/courses/' + editCourseId" method="POST" class="flex flex-col flex-1 min-h-0">
                    @csrf
                    @method('PUT')
                    
                    <div class="flex-1 overflow-y-auto p-4 sm:p-5 space-y-4 overscroll-contain">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Course Code *</label>
                                <input type="text" name="code" x-model="editCourseCode" required class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-white text-xs uppercase">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Course Title *</label>
                                <input type="text" name="title" x-model="editCourseTitle" required class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-white text-xs">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Category *</label>
                                <input type="text" name="category" x-model="editCourseCategory" required class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-white text-xs">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Credits *</label>
                                <input type="number" name="credits" x-model.number="editCourseCredits" min="1" max="12" required class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-white text-xs">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Capacity</label>
                                <input type="number" name="capacity" x-model.number="editCourseCapacity" min="1" max="500" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-white text-xs">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Assigned Instructor</label>
                                <select name="teacher_id" x-model="editCourseTeacherId" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-white text-xs">
                                    <option value="">-- No Teacher Assigned --</option>
                                    @foreach($teachers as $teacher)
                                        <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Status</label>
                                <select name="status" x-model="editCourseStatus" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-white text-xs">
                                    <option value="active">Active (Visible)</option>
                                    <option value="draft">Draft (Hidden)</option>
                                    <option value="archived">Archived</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Course Description</label>
                            <textarea name="description" x-model="editCourseDescription" rows="3" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-white text-xs"></textarea>
                        </div>
                    </div>

                    <div class="flex items-center justify-end space-x-2.5 p-3.5 sm:p-4 border-t border-slate-100 dark:border-slate-800 flex-shrink-0 bg-slate-50/80 dark:bg-slate-850">
                        <button type="button" @click="openEditModal = false" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:text-slate-800">Cancel</button>
                        <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold shadow-md transition">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

</div>
@endsection
