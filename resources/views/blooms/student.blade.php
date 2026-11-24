@extends('layouts.app')

@section('title', 'My Bloom Assignments' . ' — ' . ($school['name'] ?? config('app.name', 'School ERP')))

@section('content')
<div class="space-y-4" x-data="{ activeTab: 'assignments' }">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-1 border-b border-slate-100 dark:border-slate-800">
        <div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight">Daily Bloom's Assignments</h1>
            <p class="text-slate-500 dark:text-slate-400 text-xs mt-0.5">
                Cognitive prompts from recall to creative synthesis.
            </p>
        </div>

        <div class="flex items-center space-x-2">
            <span class="px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-bold border border-slate-200 dark:border-slate-700">
                Roll # {{ Auth::user()->classEnrollments->first()?->roll_number ?? '1' }}
            </span>
        </div>
    </div>

    <!-- Segmented Tab Navigation: Tab 1 (Assignments) vs Tab 2 (Cognitive Tracker) -->
    <div class="bg-white dark:bg-slate-900 p-1.5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-2xs flex flex-col sm:flex-row items-center justify-between gap-2.5">
        <div class="grid grid-cols-2 gap-1.5 w-full sm:w-auto">
            <!-- Tab 1: Assignments by Bloom's Taxonomy -->
            <button type="button"
                    @click="activeTab = 'assignments'"
                    :class="activeTab === 'assignments' ? 'bg-emerald-600 text-white shadow-2xs font-bold' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 font-semibold'"
                    class="px-3 py-1.5 rounded-lg text-xs transition flex items-center justify-center space-x-1.5">
                <span>📝</span>
                <span>Assignments by Bloom's</span>
                <span class="px-1.5 py-0.2 rounded-full text-[10px] font-bold" :class="activeTab === 'assignments' ? 'bg-white/20 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300'">
                    {{ $myAssignments->total() ?? $myAssignments->count() }}
                </span>
            </button>

            <!-- Tab 2: Cognitive Tracker Progress -->
            <button type="button"
                    @click="activeTab = 'tracker'"
                    :class="activeTab === 'tracker' ? 'bg-emerald-600 text-white shadow-2xs font-bold' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 font-semibold'"
                    class="px-3 py-1.5 rounded-lg text-xs transition flex items-center justify-center space-x-1.5">
                <span>🧠</span>
                <span>Cognitive Tracker</span>
                <span class="px-1.5 py-0.2 rounded-full text-[10px] font-bold" :class="activeTab === 'tracker' ? 'bg-white/20 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300'">
                    6 Tiers
                </span>
            </button>
        </div>
        
        <span class="text-[11px] font-bold text-amber-600 dark:text-amber-400 hidden sm:inline-block pr-2">
            🔥 Aim, Ascend, Achieve
        </span>
    </div>

    <!-- TAB 1: COGNITIVE TRACKER PROGRESS -->
    <div x-show="activeTab === 'tracker'" class="space-y-4" x-cloak>
        <div class="bg-white dark:bg-slate-900 p-3.5 sm:p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-2xs">
            <div class="flex items-center justify-between mb-3">
                <div>
                    <h3 class="font-bold text-slate-900 dark:text-white text-sm sm:text-base">Your Cognitive Progress Tracker</h3>
                    <p class="text-[10px] text-slate-500 dark:text-slate-400">Challenges completed across Bloom's 6 tiers</p>
                </div>
                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                    6 Bloom's Tiers
                </span>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2">
                @foreach($levelStats as $stat)
                <div class="p-2.5 rounded-lg border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/60 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between text-[10px] font-bold text-slate-500 dark:text-slate-400">
                            <span>TIER {{ $stat['level'] }}</span>
                            <span class="px-1.5 py-0.2 rounded {{ $stat['badge_color'] }} font-black text-[10px]">{{ $stat['completed'] }}/{{ $stat['total_assigned'] }}</span>
                        </div>
                        <h4 class="text-xs font-extrabold text-slate-900 dark:text-white mt-1">{{ $stat['name'] }}</h4>
                    </div>

                    @php
                        $percent = $stat['total_assigned'] > 0 ? min(100, round(($stat['completed'] / $stat['total_assigned']) * 100)) : 0;
                    @endphp
                    <div class="mt-2">
                        <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-1.5 overflow-hidden">
                            <div class="h-1.5 rounded-full transition-all duration-300" style="width: {{ $percent }}%; background-color: {{ $stat['accent_color'] }}"></div>
                        </div>
                        <span class="text-[10px] text-slate-500 dark:text-slate-400 block mt-0.5 text-right font-semibold">{{ $percent }}%</span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Compact Bloom's Taxonomy Cognitive Hierarchy -->
        <div class="bg-white dark:bg-slate-900 p-3 sm:p-3.5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-2xs">
            <h4 class="text-[10px] font-bold text-slate-900 dark:text-white uppercase tracking-wider mb-2">Bloom's Hierarchy</h4>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2 text-xs">
                <div class="p-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/40 text-center">
                    <span class="font-extrabold text-slate-800 dark:text-slate-200 block text-xs">📚 L1: Recall</span>
                    <span class="text-[10px] text-slate-400">Remember facts</span>
                </div>
                <div class="p-2.5 rounded-xl border border-emerald-200 dark:border-emerald-900/60 bg-emerald-50/50 dark:bg-emerald-950/20 text-center">
                    <span class="font-extrabold text-emerald-800 dark:text-emerald-300 block text-xs">💡 L2: Explain</span>
                    <span class="text-[10px] text-emerald-600/70 dark:text-emerald-400/70">Understand</span>
                </div>
                <div class="p-2.5 rounded-xl border border-blue-200 dark:border-blue-900/60 bg-blue-50/50 dark:bg-blue-950/20 text-center">
                    <span class="font-extrabold text-blue-800 dark:text-blue-300 block text-xs">🛠️ L3: Solve</span>
                    <span class="text-[10px] text-blue-600/70 dark:text-blue-400/70">Apply rules</span>
                </div>
                <div class="p-2.5 rounded-xl border border-amber-200 dark:border-amber-900/60 bg-amber-50/50 dark:bg-amber-950/20 text-center">
                    <span class="font-extrabold text-amber-800 dark:text-amber-300 block text-xs">🔬 L4: Examine</span>
                    <span class="text-[10px] text-amber-600/70 dark:text-amber-400/70">Analyze parts</span>
                </div>
                <div class="p-2.5 rounded-xl border border-rose-200 dark:border-rose-900/60 bg-rose-50/50 dark:bg-rose-950/20 text-center">
                    <span class="font-extrabold text-rose-800 dark:text-rose-300 block text-xs">⚖️ L5: Critique</span>
                    <span class="text-[10px] text-rose-600/70 dark:text-rose-400/70">Evaluate</span>
                </div>
                <div class="p-2.5 rounded-xl border border-purple-200 dark:border-purple-900/60 bg-purple-50/50 dark:bg-purple-950/20 text-center">
                    <span class="font-extrabold text-purple-800 dark:text-purple-300 block text-xs">✨ L6: Create</span>
                    <span class="text-[10px] text-purple-600/70 dark:text-purple-400/70">Synthesize</span>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 2: ASSIGNMENTS BY BLOOM'S TAXONOMY -->
    <div x-show="activeTab === 'assignments'" class="space-y-3">
        <div class="flex items-center justify-between">
            <h3 class="font-bold text-slate-900 dark:text-white text-base">Active &amp; Past Daily Prompts</h3>
            <span class="text-xs text-slate-500 dark:text-slate-400 font-medium">{{ $myAssignments->total() ?? $myAssignments->count() }} Total Daily Prompts</span>
        </div>

        @forelse($myAssignments as $assignment)
        @php 
            $meta = $assignment->metadata; 
            $lesson = $assignment->lesson;
        @endphp
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-2xs p-3.5 sm:p-4 space-y-3">
            <!-- Assignment Card Top -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 pb-2.5 border-b border-slate-100 dark:border-slate-800">
                <div>
                    <div class="flex items-center space-x-2 text-xs">
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200">
                            {{ $lesson->academicClass->name }} &bull; {{ $lesson->subject->name }}
                        </span>
                        <span class="text-slate-400 text-[10px]">&bull;</span>
                        <span class="text-slate-500 dark:text-slate-400 text-[10px]">{{ $lesson->lesson_date->format('M d, Y') }}</span>
                    </div>

                    <h2 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white mt-1">{{ $lesson->topic->title }}</h2>

                    @if($lesson->subtopic)
                    <div class="text-[11px] text-emerald-700 dark:text-emerald-400 font-medium mt-0.5">
                        Focus: {{ $lesson->subtopic }}
                    </div>
                    @endif
                </div>

                <!-- Bloom's Level Badge & Due Date -->
                <div class="flex flex-col items-start sm:items-end gap-1">
                    <span class="px-2.5 py-1 rounded-lg text-xs font-black uppercase tracking-wider {{ $meta['badge_color'] }} border shadow-2xs">
                        LEVEL {{ $assignment->bloom_level }}: {{ $assignment->level_name }}
                    </span>
                    @if($assignment->due_date)
                    <span class="text-[10px] font-semibold {{ $assignment->due_date->isPast() ? 'text-rose-600 dark:text-rose-400 font-bold' : 'text-slate-500 dark:text-slate-400' }}">
                        📅 Due: {{ $assignment->due_date->format('M d, Y') }} {{ $assignment->due_date->isPast() ? '(Overdue)' : '' }}
                    </span>
                    @endif
                    <span class="text-[10px] text-slate-400 font-mono">
                        {{ $meta['verbs'] }}
                    </span>
                </div>
            </div>

            <!-- Activity Prompt & Rubric Guide -->
            <div class="p-3 rounded-lg bg-emerald-50/50 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-800/50 text-xs text-slate-800 dark:text-slate-200 leading-relaxed whitespace-pre-line font-medium space-y-1.5">
                <div>
                    <span class="font-bold text-emerald-950 dark:text-emerald-300 block mb-0.5 text-[10px] uppercase tracking-wider">Your Assigned Challenge Prompt:</span>
                    {{ $assignment->activity_prompt }}
                </div>
                @if($assignment->rubric_guide)
                <div class="pt-1.5 border-t border-emerald-200/60 dark:border-emerald-800/60 text-[11px] text-emerald-900 dark:text-emerald-300 flex items-start gap-1">
                    <span class="text-xs">📋</span>
                    <span><strong>Evaluation Rubric:</strong> {{ $assignment->rubric_guide }}</span>
                </div>
                @endif
                @if($assignment->hasTeacherAttachment())
                <div class="pt-2 border-t border-emerald-200/60 dark:border-emerald-800/60 flex items-center justify-between gap-2">
                    <div class="flex items-center gap-1.5 min-w-0">
                        <span class="text-base">{{ $assignment->teacher_attachment_icon }}</span>
                        <div class="truncate">
                            <span class="font-bold text-slate-900 dark:text-white truncate block text-xs">{{ $assignment->teacher_attachment_name }}</span>
                            <span class="text-[10px] text-slate-500 uppercase">{{ $assignment->teacher_attachment_type }} &bull; {{ $assignment->formatted_teacher_attachment_size }} &bull; Reference Document</span>
                        </div>
                    </div>
                    <a href="{{ route('blooms.assignment.download-attachment', ['id' => $assignment->id, 'type' => 'teacher']) }}"
                       class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold rounded-lg bg-emerald-700 hover:bg-emerald-800 text-white shrink-0 transition"
                       download>
                        <span>⬇️ Download Deck</span>
                    </a>
                </div>
                @endif
            </div>

            <!-- Submission Area -->
            @if($assignment->status === 'revision_requested')
                <!-- Revision Requested Banner & Resubmission Form -->
                <div class="p-4 rounded-xl bg-amber-50 dark:bg-amber-950/30 border border-amber-300 dark:border-amber-800 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-amber-200 dark:bg-amber-900 text-amber-900 dark:text-amber-100 uppercase flex items-center gap-1">
                            <span>↩️</span>
                            <span>Revision Requested by Teacher</span>
                        </span>
                        <span class="text-xs text-amber-700 dark:text-amber-300 font-semibold">Please update and resubmit</span>
                    </div>

                    @if($assignment->teacher_feedback)
                    <div class="p-3 bg-white dark:bg-slate-900 rounded-lg border border-amber-200 dark:border-amber-800 text-xs text-slate-800 dark:text-slate-200">
                        <strong class="text-amber-800 dark:text-amber-400 block mb-0.5">Teacher's Improvement Notes:</strong>
                        {{ $assignment->teacher_feedback }}
                    </div>
                    @endif

                    @if($assignment->hasSubmissionAttachment())
                    <div class="p-2.5 rounded-lg bg-amber-100/60 dark:bg-amber-900/40 border border-amber-200 dark:border-amber-700 flex items-center justify-between gap-2 text-xs">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="text-base">{{ $assignment->submission_attachment_icon }}</span>
                            <div class="truncate">
                                <span class="font-bold text-amber-950 dark:text-amber-100 truncate block">{{ $assignment->submission_attachment_name }}</span>
                                <span class="text-[10px] text-amber-700 dark:text-amber-300 uppercase">{{ $assignment->submission_attachment_type }} &bull; {{ $assignment->formatted_submission_attachment_size }}</span>
                            </div>
                        </div>
                        <a href="{{ route('blooms.assignment.download-attachment', ['id' => $assignment->id, 'type' => 'submission']) }}"
                           class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-bold text-amber-800 dark:text-amber-200 hover:underline shrink-0"
                           download>
                            <span>⬇️ Current File</span>
                        </a>
                    </div>
                    @endif

                    <form method="POST" action="{{ route('blooms.assignment.submit', $assignment->id) }}" enctype="multipart/form-data" class="space-y-3 pt-1">
                        @csrf
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                Your Revised Response / Solution
                            </label>
                            <textarea name="submission_content" rows="4"
                                      placeholder="Update your response notes or summary here..."
                                      class="w-full px-3.5 py-2.5 rounded-xl border border-amber-300 dark:border-amber-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-2 focus:ring-amber-500 text-xs sm:text-sm">{{ old('submission_content', $assignment->submission_content) }}</textarea>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                📎 Replace or Attach Document / Slides Deck (PDF, PPT/PPTX, DOC/DOCX, up to 50MB)
                            </label>
                            <input type="file" name="attachment" accept=".pdf,.doc,.docx,.ppt,.pptx,.pps,.ppsx,.txt,.odt,.rtf,.png,.jpg,.jpeg,.webp,.zip"
                                   class="block w-full text-xs text-slate-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-amber-100 file:text-amber-800 hover:file:bg-amber-200 dark:file:bg-amber-900 dark:file:text-amber-200 border border-amber-300 dark:border-amber-700 rounded-xl bg-white dark:bg-slate-950 px-2 py-1.5">
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="px-5 py-2.5 bg-amber-600 hover:bg-amber-500 text-white font-bold text-xs sm:text-sm rounded-xl shadow-xs transition flex items-center space-x-1.5">
                                <span>Resubmit Revised Response &rarr;</span>
                                <span>🚀</span>
                            </button>
                        </div>
                    </form>
                </div>
            @elseif($assignment->status === 'reviewed')
                <!-- Reviewed Box -->
                <div class="p-5 rounded-2xl bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-200 dark:bg-emerald-900 text-emerald-900 dark:text-emerald-100 uppercase flex items-center gap-1">
                            <span>🎉</span>
                            <span>Evaluated &amp; Graded</span>
                            <span>🏆</span>
                        </span>
                        <div class="text-right">
                            <span class="text-xs text-slate-500 dark:text-slate-400">Score:</span>
                            <span class="text-xl font-black text-emerald-800 dark:text-emerald-300 ml-1 font-mono">💯 {{ $assignment->score }}/100</span>
                        </div>
                    </div>

                    @if($assignment->submission_content)
                    <div class="p-3.5 bg-white dark:bg-slate-900 rounded-xl border border-emerald-100 dark:border-emerald-900 text-xs text-slate-700 dark:text-slate-300 whitespace-pre-wrap">
                        <strong class="block text-slate-900 dark:text-white mb-1">Your Submitted Response:</strong>
                        {{ $assignment->submission_content }}
                    </div>
                    @endif

                    @if($assignment->hasSubmissionAttachment())
                    <div class="p-2.5 bg-white dark:bg-slate-900 rounded-xl border border-emerald-200 dark:border-emerald-800 flex items-center justify-between gap-2 text-xs">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="text-base">{{ $assignment->submission_attachment_icon }}</span>
                            <div class="truncate">
                                <span class="font-bold text-slate-900 dark:text-white truncate block">{{ $assignment->submission_attachment_name }}</span>
                                <span class="text-[10px] text-slate-500 uppercase">{{ $assignment->submission_attachment_type }} &bull; {{ $assignment->formatted_submission_attachment_size }}</span>
                            </div>
                        </div>
                        <a href="{{ route('blooms.assignment.download-attachment', ['id' => $assignment->id, 'type' => 'submission']) }}"
                           class="inline-flex items-center gap-1 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-lg text-xs shadow-xs transition shrink-0"
                           download>
                            <span>⬇️ Download Submitted File</span>
                        </a>
                    </div>
                    @endif

                    @if($assignment->teacher_feedback)
                    <div class="text-xs text-emerald-900 dark:text-emerald-200 bg-emerald-100/70 dark:bg-emerald-900/40 p-3 rounded-xl border border-emerald-200 dark:border-emerald-800 flex items-start gap-1.5">
                        <span class="text-sm">💬</span>
                        <div><strong>Teacher Feedback:</strong> {{ $assignment->teacher_feedback }}</div>
                    </div>
                    @endif
                </div>
            @elseif($assignment->status === 'submitted')
                <!-- Submitted Awaiting Review -->
                <div class="p-5 rounded-2xl bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-blue-200 dark:bg-blue-900 text-blue-900 dark:text-blue-100 uppercase flex items-center gap-1">
                            <span>⏳</span>
                            <span>Submitted &bull; Awaiting Evaluation</span>
                            <span>🚀</span>
                        </span>
                        <span class="text-xs text-slate-500 dark:text-slate-400">{{ $assignment->submitted_at?->diffForHumans() }}</span>
                    </div>

                    @if($assignment->submission_content)
                    <div class="p-3.5 bg-white dark:bg-slate-900 rounded-xl border border-blue-100 dark:border-blue-900 text-xs text-slate-700 dark:text-slate-300 whitespace-pre-wrap">
                        <strong class="block text-slate-900 dark:text-white mb-1">Your Submitted Work:</strong>
                        {{ $assignment->submission_content }}
                    </div>
                    @endif

                    @if($assignment->hasSubmissionAttachment())
                    <div class="p-2.5 bg-white dark:bg-slate-900 rounded-xl border border-blue-200 dark:border-blue-800 flex items-center justify-between gap-2 text-xs">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="text-base">{{ $assignment->submission_attachment_icon }}</span>
                            <div class="truncate">
                                <span class="font-bold text-slate-900 dark:text-white truncate block">{{ $assignment->submission_attachment_name }}</span>
                                <span class="text-[10px] text-slate-500 uppercase">{{ $assignment->submission_attachment_type }} &bull; {{ $assignment->formatted_submission_attachment_size }}</span>
                            </div>
                        </div>
                        <a href="{{ route('blooms.assignment.download-attachment', ['id' => $assignment->id, 'type' => 'submission']) }}"
                           class="inline-flex items-center gap-1 px-3 py-1.5 bg-blue-600 hover:bg-blue-500 text-white font-bold rounded-lg text-xs shadow-xs transition shrink-0"
                           download>
                            <span>⬇️ Download Submitted File</span>
                        </a>
                    </div>
                    @endif
                </div>
            @else
                <!-- Active Submission Form -->
                <form method="POST" action="{{ route('blooms.assignment.submit', $assignment->id) }}" enctype="multipart/form-data" class="space-y-3 pt-2">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1 flex items-center gap-1">
                            <span>✍️</span>
                            <span>Your Response / Solution Submission</span>
                        </label>
                        <textarea name="submission_content" rows="4" placeholder="Type your full answer, notes, or analytical summary here (or attach document/slides below)..."
                                  class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white focus:outline-hidden focus:ring-2 focus:ring-emerald-500 text-xs sm:text-sm"></textarea>
                    </div>

                    <div class="space-y-1">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                            📎 Attach Presentation / Document / PDF (Optional, up to 50MB)
                        </label>
                        <input type="file" name="attachment" accept=".pdf,.doc,.docx,.ppt,.pptx,.pps,.ppsx,.txt,.odt,.rtf,.png,.jpg,.jpeg,.webp,.zip"
                               class="block w-full text-xs text-slate-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 dark:file:bg-emerald-950 dark:file:text-emerald-300 border border-slate-200 dark:border-slate-700 rounded-xl bg-white dark:bg-slate-950 px-2 py-1.5">
                        <span class="text-[10px] text-slate-500 dark:text-slate-400">💡 Supported: PDF, PowerPoint slides (.ppt, .pptx), Word docs, Images, Markdown, Zip.</span>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs sm:text-sm rounded-xl shadow-xs transition flex items-center space-x-1.5">
                            <span>Submit Bloom's Response &rarr;</span>
                            <span>🚀</span>
                        </button>
                    </div>
                </form>
            @endif

        </div>
        @empty
        <div class="bg-white dark:bg-slate-900 p-12 text-center rounded-2xl border border-slate-200 dark:border-slate-800 text-slate-400">
            <p class="text-sm">No Bloom's taxonomy assignments distributed yet. Once your teacher records today's lesson, your personalized prompt will appear here.</p>
        </div>
        @endforelse

        <!-- Pagination for Student Bloom's Assignments -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-slate-200 dark:border-slate-800 shadow-xs">
            {{ $myAssignments->links() }}
        </div>
    </div>

</div>
@endsection
