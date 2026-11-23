@extends('layouts.app')

@section('title', 'Edit ' . $assignment->title . ' — ' . ($school['name'] ?? config('app.name', 'School Management System')))

@section('content')
<div class="max-w-4xl mx-auto space-y-4">

    <!-- Top Action Breadcrumb -->
    <div class="flex items-center justify-between pb-2 border-b border-slate-200 dark:border-slate-800 text-xs">
        <a href="{{ route('assignments.show', $assignment->id) }}" class="font-bold text-slate-500 hover:text-emerald-600 dark:text-slate-400 dark:hover:text-emerald-400 flex items-center space-x-1.5 transition">
            <span>&larr;</span>
            <span>Back to Assignment</span>
        </a>

        <span class="text-xs text-slate-400 font-mono">Assignment ID: {{ $assignment->id }}</span>
    </div>

    <!-- Edit Assignment Form Card -->
    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-5 sm:p-7 shadow-xs space-y-5">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
            <div>
                <h2 class="text-lg font-black text-slate-900 dark:text-white">Edit Academic Assignment</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Update class, section, subject, rubric points, target due date, and instructions.</p>
            </div>
            <span class="px-3 py-1 rounded-full text-xs font-bold bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                School Coursework
            </span>
        </div>

        <form method="POST" action="{{ route('assignments.update', $assignment->id) }}" enctype="multipart/form-data" class="space-y-4 text-xs">
            @csrf
            @method('PUT')

            <div class="space-y-3">
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1">Assignment Title *</label>
                    <input type="text" name="title" value="{{ old('title', $assignment->title) }}" required class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-bold">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1">Academic Class</label>
                        <select name="class_id" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-bold">
                            <option value="">-- No Specific Class / School-Wide --</option>
                            @foreach($classes as $c)
                            <option value="{{ $c->id }}" {{ old('class_id', $assignment->class_id) == $c->id ? 'selected' : '' }}>
                                {{ $c->display_name }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1">Section</label>
                        <select name="section_id" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-bold">
                            <option value="">Default Class Section / All Sections</option>
                            @foreach($sections as $sec)
                            <option value="{{ $sec->id }}" {{ old('section_id', $assignment->section_id) == $sec->id ? 'selected' : '' }}>
                                Section {{ $sec->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1">Academic Subject</label>
                        <select name="subject_id" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-bold">
                            <option value="">General Academic Coursework</option>
                            @foreach($subjects as $sub)
                            <option value="{{ $sub->id }}" {{ old('subject_id', $assignment->subject_id) == $sub->id ? 'selected' : '' }}>
                                {{ $sub->name }} ({{ $sub->code ?? 'SUB' }})
                            </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1">Due Date &amp; Deadline *</label>
                    <input type="datetime-local" name="due_date" value="{{ old('due_date', date('Y-m-d\TH:i', strtotime($assignment->due_date))) }}" required class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-mono font-bold">
                </div>

                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1">Maximum Rubric Points *</label>
                    <input type="number" name="max_points" value="{{ old('max_points', $assignment->max_points) }}" min="1" max="1000" required class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-mono font-bold">
                </div>
            </div>

            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1">Instructions &amp; Requirements</label>
                <textarea name="description" rows="4" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">{{ old('description', $assignment->description) }}</textarea>
            </div>

            <!-- Attachment Section -->
            <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 space-y-3">
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                    📎 Assignment Attachment / Printable Worksheet
                </label>

                @if($assignment->hasAttachment())
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 p-3 bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800">
                    <div class="flex items-center space-x-2.5">
                        <span class="text-lg">{{ $assignment->is_pdf_attachment ? '📄' : '📎' }}</span>
                        <div>
                            <div class="font-bold text-slate-900 dark:text-white flex items-center space-x-2">
                                <span>{{ $assignment->attachment_name ?: basename($assignment->attachment_path) }}</span>
                                @if($assignment->formatted_attachment_size)
                                <span class="px-1.5 py-0.5 rounded text-[10px] bg-slate-100 dark:bg-slate-800 text-slate-500 font-normal">
                                    {{ $assignment->formatted_attachment_size }}
                                </span>
                                @endif
                            </div>
                            <span class="text-[10px] text-slate-400">Currently attached file</span>
                        </div>
                    </div>

                    <div class="flex items-center space-x-3">
                        <a href="{{ route('assignments.attachment', $assignment->id) }}" target="_blank" class="px-2.5 py-1 text-xs font-bold bg-indigo-50 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400 rounded-lg hover:bg-indigo-100 dark:hover:bg-indigo-900 transition">
                            Download / Preview &rarr;
                        </a>
                        <label class="inline-flex items-center space-x-1.5 text-xs text-rose-600 dark:text-rose-400 font-bold cursor-pointer">
                            <input type="checkbox" name="remove_attachment" value="1" class="rounded border-rose-300 text-rose-600 focus:ring-rose-500">
                            <span>Remove</span>
                        </label>
                    </div>
                </div>
                @endif

                <div>
                    <label class="block text-[10px] font-semibold text-slate-500 dark:text-slate-400 mb-1">
                        {{ $assignment->hasAttachment() ? 'Upload replacement file (Optional):' : 'Upload attachment file (PDF, DOCX, ZIP, etc.):' }}
                    </label>
                    <input type="file" name="attachment" accept=".pdf,.doc,.docx,.ppt,.pptx,.zip,.png,.jpg,.jpeg" class="w-full text-xs text-slate-600 dark:text-slate-400 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 dark:file:bg-indigo-950 dark:file:text-indigo-300 hover:file:bg-indigo-100 dark:hover:file:bg-indigo-900 border border-slate-300 dark:border-slate-700 rounded-xl p-1 bg-white dark:bg-slate-800">
                    <p class="text-[10px] text-slate-400 mt-1">Maximum file size 25MB.</p>
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end space-x-2.5">
                <a href="{{ route('assignments.show', $assignment->id) }}" class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold transition">
                    Cancel
                </a>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold shadow-md shadow-emerald-600/20 transition flex items-center space-x-1.5">
                    <span>💾 Save Changes</span>
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
