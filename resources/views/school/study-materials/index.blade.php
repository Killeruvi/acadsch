@extends('layouts.app')

@section('title', 'Study Material & Downloads — ' . ($school['name'] ?? 'Infix LMS'))

@section('content')
<div class="max-w-[1600px] mx-auto space-y-5 pb-24 sm:pb-8">

    <!-- Infix Breadcrumbs & Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center space-x-2 text-[11px] text-slate-400 font-semibold mb-1">
                <a href="{{ route('dashboard') }}" class="hover:text-slate-600 dark:hover:text-slate-200 transition">Dashboard</a>
                <span>/</span>
                <span class="text-slate-500">Academic</span>
                <span>/</span>
                <span class="text-slate-800 dark:text-slate-200 font-bold">Download Center</span>
            </div>

            <div class="flex items-center space-x-2.5">
                <span class="w-8 h-8 rounded-lg flex items-center justify-center text-white text-sm shadow-xs" style="background: var(--system_primary_gradient);">
                    <i class="ti-download"></i>
                </span>
                <h1 class="text-lg sm:text-xl font-black text-slate-900 dark:text-white tracking-tight">
                    Study Material &amp; Download Center
                </h1>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold text-white shadow-2xs" style="background: var(--system_primary_gradient);">
                    Infix Downloads
                </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Centralized curriculum document repository for syllabus outlines, handouts, assignments, and study guides.
            </p>
        </div>

        @if(Auth::user()->isAdmin() || Auth::user()->isTeacher())
        <div class="flex items-center flex-wrap gap-2">
            <button type="button" 
                    onclick="document.getElementById('upload-content-modal').classList.remove('hidden')"
                    class="primary-btn" style="padding: 0.4rem 1.1rem; font-size: 0.75rem;">
                <i class="ti-upload text-xs"></i>
                <span>Upload Content</span>
            </button>
        </div>
        @endif
    </div>

    <!-- Category Filter Pills -->
    <div class="flex items-center space-x-2 overflow-x-auto pb-1 text-xs">
        <a href="{{ route('school.study-materials.index') }}" 
           class="px-3 py-1.5 rounded-xl font-bold transition {{ empty($type) ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200' }}">
            All Materials
        </a>
        <a href="{{ route('school.study-materials.index', ['type' => 'syllabus']) }}" 
           class="px-3 py-1.5 rounded-xl font-bold transition {{ $type === 'syllabus' ? 'bg-purple-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200' }}">
            Syllabus
        </a>
        <a href="{{ route('school.study-materials.index', ['type' => 'assignment']) }}" 
           class="px-3 py-1.5 rounded-xl font-bold transition {{ $type === 'assignment' ? 'bg-purple-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200' }}">
            Assignments
        </a>
        <a href="{{ route('school.study-materials.index', ['type' => 'study_material']) }}" 
           class="px-3 py-1.5 rounded-xl font-bold transition {{ $type === 'study_material' ? 'bg-purple-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200' }}">
            Study Notes
        </a>
        <a href="{{ route('school.study-materials.index', ['type' => 'other_download']) }}" 
           class="px-3 py-1.5 rounded-xl font-bold transition {{ $type === 'other_download' ? 'bg-purple-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200' }}">
            Other Downloads
        </a>
    </div>

    <!-- Materials Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($materials as $mat)
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 shadow-2xs hover:shadow-md transition space-y-3 flex flex-col justify-between">
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider
                        @if($mat->content_type === 'syllabus') bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300
                        @elseif($mat->content_type === 'assignment') bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300
                        @else bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-300 @endif">
                        {{ str_replace('_', ' ', $mat->content_type) }}
                    </span>
                    <span class="text-[11px] text-slate-400 font-mono">{{ $mat->upload_date->format('M d, Y') }}</span>
                </div>

                <h3 class="text-sm font-black text-slate-900 dark:text-white line-clamp-2">
                    {{ $mat->content_title }}
                </h3>

                <p class="text-xs text-slate-500 dark:text-slate-400 line-clamp-2">
                    {{ $mat->description ?? 'Official curriculum resource.' }}
                </p>

                <div class="text-[11px] text-slate-400 flex items-center gap-2 pt-1">
                    <span>Class: <strong class="text-slate-700 dark:text-slate-300">{{ $mat->academicClass?->display_name ?? 'All Classes' }}</strong></span>
                    <span>&bull;</span>
                    <span>By: <strong class="text-slate-700 dark:text-slate-300">{{ $mat->author?->name ?? 'Faculty' }}</strong></span>
                </div>
            </div>

            <div class="pt-2 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <span class="text-[11px] font-mono text-slate-400 truncate max-w-[150px]">{{ $mat->file_name }}</span>
                <div class="flex items-center gap-2">
                    <a href="{{ route('school.study-materials.download', $mat->id) }}"
                       class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs shadow-xs transition">
                        <i class="ti-download text-xs"></i><span>Download</span>
                    </a>
                    @if(Auth::user()->isAdmin() || (Auth::user()->isTeacher() && (int) $mat->created_by === (int) Auth::id()))
                    <form method="POST" action="{{ route('school.study-materials.destroy', $mat->id) }}" onsubmit="return confirm('Delete this study material permanently?')">
                        @csrf @method('DELETE')
                        <button type="submit" aria-label="Delete material" class="px-2 py-1.5 rounded-xl border border-rose-200 text-rose-600 hover:bg-rose-50 text-xs font-bold">Delete</button>
                    </form>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div class="col-span-full p-8 text-center bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 text-slate-400">
            No study materials found for this selection.
        </div>
        @endforelse
    </div>

    <div class="pt-2">
        {{ $materials->links() }}
    </div>

</div>

<!-- Modal: Upload Content -->
@if(Auth::user()->isAdmin() || Auth::user()->isTeacher())
<div id="upload-content-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs hidden p-4">
    <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200 dark:border-slate-800 max-w-md w-full shadow-2xl">
        <div class="flex items-center justify-between mb-4 pb-2 border-b border-slate-100 dark:border-slate-800">
            <h3 class="text-base font-bold text-slate-900 dark:text-white">Upload Study Material</h3>
            <button onclick="document.getElementById('upload-content-modal').classList.add('hidden')" class="text-slate-400 hover:text-white text-sm">✕</button>
        </div>

        <form method="POST" enctype="multipart/form-data" action="{{ route('school.study-materials.store') }}" class="space-y-3 text-xs">
            @csrf
            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Content Title *</label>
                <input type="text" name="content_title" placeholder="Unit 4 Modern Physics Lecture Notes" required class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
            </div>

            <div class="grid grid-cols-2 gap-2.5">
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Content Type *</label>
                    <select name="content_type" required class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-bold">
                        <option value="study_material" selected>Study Material</option>
                        <option value="syllabus">Syllabus</option>
                        <option value="assignment">Assignment</option>
                        <option value="other_download">Other Download</option>
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Applicable Class</label>
                    <select name="class_id" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-bold">
                        <option value="">All Classes</option>
                        @foreach($classes as $c)
                        <option value="{{ $c->id }}">{{ $c->display_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Available For</label>
                <select name="available_for" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-bold">
                    <option value="all" selected>All (Students &amp; Faculty)</option>
                    <option value="students">Students Only</option>
                    <option value="teachers">Teachers Only</option>
                </select>
            </div>

            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Upload File * (PDF, Office, CSV, text or image; max 20 MB)</label>
                <input type="file" name="material_file" required accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.csv,.txt,.jpg,.jpeg,.png,.webp" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
            </div>

            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Description</label>
                <textarea name="description" rows="2" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white"></textarea>
            </div>

            <div class="flex justify-end space-x-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                <button type="button" onclick="document.getElementById('upload-content-modal').classList.add('hidden')" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold">Cancel</button>
                <button type="submit" class="primary-btn" style="padding: 0.4rem 1.1rem; font-size: 0.75rem;">Publish Document</button>
            </div>
        </form>
    </div>
</div>
@endif
@endsection
