@extends('layouts.app')

@section('title', 'Digital Video Classroom' . ' — ' . ($school['name'] ?? config('app.name', 'School ERP')))

@section('content')
<div class="space-y-4 max-w-6xl mx-auto py-2 sm:py-3" 
     x-data="{ 
         videoModalOpen: false, 
         currentVideoTitle: '', 
         currentVideoEmbed: '' 
     }">

    <!-- Header Section (Compact, Fire & Inspiring) -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 sm:p-5 text-slate-900 dark:text-white shadow-xs border border-slate-200 dark:border-slate-800 transition-colors">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <div class="flex items-center space-x-2">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-100 dark:bg-indigo-950/60 text-indigo-800 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                        🎬 Video Lectures
                    </span>
                    <span class="text-xs text-amber-600 dark:text-amber-400 font-bold">
                        🔥 <em>"तेजस्वि नावधीतमस्तु"</em> &bull; Ignite Brilliance
                    </span>
                </div>
                <h1 class="text-lg sm:text-xl font-black tracking-tight text-slate-900 dark:text-white mt-0.5">
                    Digital Video Classroom
                </h1>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    Curriculum video lectures filtered directly by class and subject for fastest, distraction-free learning.
                </p>
            </div>

            <div class="flex items-center space-x-2 text-xs">
                <div class="bg-slate-50 dark:bg-slate-800 rounded-xl px-3 py-1.5 border border-slate-200 dark:border-slate-700">
                    <span class="text-[10px] text-slate-400 block">Total Lectures</span>
                    <span class="text-xs font-black text-indigo-600 dark:text-indigo-400">{{ $stats['total_videos'] }}</span>
                </div>
                <div class="bg-slate-50 dark:bg-slate-800 rounded-xl px-3 py-1.5 border border-slate-200 dark:border-slate-700">
                    <span class="text-[10px] text-slate-400 block">My Watched</span>
                    <span class="text-xs font-black text-emerald-600 dark:text-emerald-400">{{ $stats['watched_count'] }} ({{ $stats['watch_rate'] }}%)</span>
                </div>
            </div>
        </div>
    </div>

    <!-- SIMPLE LEAST-COMPLICATED FORM: SELECT CLASS & SELECT SUBJECT -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-slate-200 dark:border-slate-800 shadow-xs">
        <form action="{{ route('videos.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3 items-end">
            <!-- 1. Select Class -->
            <div>
                <label for="grade" class="block text-[10px] font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-1">
                    1. Select Class / Grade
                </label>
                <select id="grade" name="grade" onchange="this.form.submit()" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-bold text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-emerald-500 focus:outline-hidden">
                    @foreach($allClasses as $c)
                    <option value="{{ $c->grade_level }}" {{ (int)$selectedGrade === (int)$c->grade_level ? 'selected' : '' }}>
                        {{ $c->display_name }}
                    </option>
                    @endforeach
                </select>
            </div>

            <!-- 2. Select Subject -->
            <div>
                <label for="subject_id" class="block text-[10px] font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-1">
                    2. Select Subject
                </label>
                <select id="subject_id" name="subject_id" onchange="this.form.submit()" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-bold text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-emerald-500 focus:outline-hidden">
                    <option value="">All Subjects in {{ $selectedClass->display_name ?? 'Class' }}</option>
                    @foreach($subjects as $subj)
                    <option value="{{ $subj->id }}" {{ (string)$selectedSubjectId === (string)$subj->id ? 'selected' : '' }}>
                        {{ $subj->name }} ({{ $subj->code }})
                    </option>
                    @endforeach
                </select>
            </div>

            <!-- Optional Search -->
            <div>
                <label for="video-search" class="block text-[10px] font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-1">
                    Filter by Keyword
                </label>
                <div class="flex items-center space-x-1.5">
                    <input type="text" 
                           id="video-search"
                           name="search" 
                           value="{{ $search }}" 
                           placeholder="Search topic..." 
                           class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-xs text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:outline-hidden">
                    <button type="submit" class="px-3.5 py-2 bg-slate-900 dark:bg-slate-800 text-white rounded-xl text-xs font-bold hover:bg-slate-800 transition shadow-xs flex-shrink-0">
                        Filter
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Active Filter Indicator -->
    <div class="flex items-center justify-between px-1">
        <div class="text-xs text-slate-500 dark:text-slate-400 font-medium">
            Showing lectures for: <strong class="text-slate-900 dark:text-white">{{ $selectedClass->display_name ?? 'Selected Class' }}</strong>
            @if($selectedSubjectId)
            &bull; Subject: <strong class="text-emerald-600 dark:text-emerald-400">{{ $subjects->firstWhere('id', (int)$selectedSubjectId)?->name }}</strong>
            @endif
        </div>
        <span class="text-[11px] text-slate-400 font-semibold">{{ $videos->total() }} Videos</span>
    </div>

    <!-- Video Grid -->
    @if($videos->isEmpty())
    <div class="bg-white dark:bg-slate-900 rounded-2xl p-10 text-center border border-slate-200 dark:border-slate-800 shadow-xs">
        <div class="w-10 h-10 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400 mx-auto flex items-center justify-center text-lg mb-2">
            🎬
        </div>
        <h3 class="text-xs font-bold text-slate-800 dark:text-slate-200">No Lectures Found</h3>
        <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1">Try selecting another subject or class.</p>
    </div>
    @else
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3.5 sm:gap-4">
        @foreach($videos as $video)
        @php
            $isWatched = $video->userProgress->first()?->is_completed ?? false;
        @endphp
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:border-emerald-500 dark:hover:border-emerald-500 rounded-xl overflow-hidden shadow-2xs hover:shadow-sm transition flex flex-col justify-between">
            <div>
                <!-- Video Thumbnail Area with Direct Embed Trigger -->
                <div class="relative aspect-video bg-slate-950 flex items-center justify-center group overflow-hidden">
                    <img src="https://img.youtube.com/vi/{{ $video->youtube_id ?? 'Ytkbvd-iB0w' }}/hqdefault.jpg" 
                         alt="{{ $video->title }}" 
                         class="w-full h-full object-cover opacity-80 group-hover:scale-105 transition duration-300">
                    <a href="{{ route('videos.show', $video->id) }}"
                       class="absolute inset-0 flex items-center justify-center bg-black/30 group-hover:bg-black/10 transition">
                        <div class="w-10 h-10 rounded-full bg-emerald-600 text-white flex items-center justify-center shadow-lg group-hover:scale-110 transition">
                            ▶
                        </div>
                    </a>
                    @if($video->duration_formatted)
                    <span class="absolute bottom-2 right-2 px-1.5 py-0.5 rounded bg-black/80 text-[10px] font-mono font-bold text-white">
                        {{ $video->duration_formatted }}
                    </span>
                    @endif
                </div>

                <!-- Card Body -->
                <div class="p-3.5 space-y-1.5">
                    <div class="flex items-center justify-between text-[10px]">
                        <span class="font-bold text-emerald-600 dark:text-emerald-400">
                            {{ $video->subject->name ?? 'General' }}
                        </span>
                        @if($isWatched)
                        <span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300">
                            ✓ Watched
                        </span>
                        @endif
                    </div>
                    <h3 class="text-xs sm:text-sm font-extrabold text-slate-900 dark:text-white line-clamp-2 leading-snug">
                        {{ $video->title }}
                    </h3>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 line-clamp-1">
                        {{ $video->topic->title ?? $video->description }}
                    </p>
                </div>
            </div>

            <!-- Card Footer -->
            <div class="p-3.5 pt-0 flex items-center justify-between border-t border-slate-100 dark:border-slate-800 text-xs">
                <span class="text-[10px] text-slate-400">Class {{ $video->subject->academicClass->grade_level ?? $selectedGrade }}</span>
                <a href="{{ route('videos.show', $video->id) }}" class="font-bold text-emerald-600 dark:text-emerald-400 hover:underline flex items-center space-x-1 text-xs">
                    <span>Watch Now</span>
                    <span>&rarr;</span>
                </a>
            </div>
        </div>
        @endforeach
    </div>

    <div class="mt-4">
        {{ $videos->links() }}
    </div>
    @endif

</div>
@endsection
