@extends('layouts.app')

@section('title', $video->title . ' — ' . $video->subject->name)

@section('content')
<div class="max-w-6xl mx-auto space-y-6">

    <!-- Breadcrumb -->
    <div class="flex items-center justify-between text-xs text-slate-500">
        <div class="flex items-center space-x-2">
            <a href="{{ route('videos.index', ['grade' => $video->subject->academicClass->grade_level]) }}" class="hover:text-indigo-600 font-medium">
                Video Lectures
            </a>
            <span>/</span>
            <span>{{ $video->subject->academicClass->name }}</span>
            <span>/</span>
            <a href="{{ route('videos.index', ['grade' => $video->subject->academicClass->grade_level, 'subject_id' => $video->subject->id]) }}" class="hover:text-indigo-600 font-medium">
                {{ $video->subject->name }}
            </a>
            <span>/</span>
            <span class="font-semibold text-slate-700 truncate max-w-xs">{{ $video->title }}</span>
        </div>
        <a href="{{ route('syllabus.show', $video->topic->id) }}" 
           class="inline-flex items-center space-x-1 font-bold text-indigo-600 hover:text-indigo-800">
            <span>&larr; View Chapter Syllabus &amp; DPPs</span>
        </a>
    </div>

    <!-- Main Player and Playlist Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Left Column: Video Player and Lecture Details (2 Cols) -->
        <div class="lg:col-span-2 space-y-5">
            <!-- 16:9 Video Player Container -->
            <div class="bg-black rounded-3xl overflow-hidden shadow-2xl aspect-video border border-slate-800 relative">
                <iframe src="{{ $video->embed_url }}" 
                        class="w-full h-full" 
                        frameborder="0" 
                        allowfullscreen 
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture">
                </iframe>
            </div>

            <!-- Video Information Card -->
            <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="space-y-1">
                        <div class="flex items-center space-x-2">
                            <span class="px-2.5 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider bg-indigo-100 text-indigo-800 border border-indigo-200">
                                {{ $video->subject->academicClass->name }} &bull; {{ $video->subject->name }}
                            </span>
                            <span class="px-2.5 py-0.5 rounded-md text-[10px] font-extrabold bg-amber-100 text-amber-900 border border-amber-200">
                                Chapter {{ $video->topic->chapter_number }}
                            </span>
                            <span class="text-xs text-slate-400 font-semibold">
                                ⏱ {{ $video->duration_formatted }}
                            </span>
                        </div>
                        <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                            {{ $video->title }}
                        </h1>
                    </div>

                    <!-- Mark as Watched Button -->
                    <form action="{{ route('videos.toggle-watched', $video->id) }}" method="POST" class="shrink-0">
                        @csrf
                        <button type="submit" 
                                class="inline-flex items-center space-x-2 px-4 py-2.5 rounded-xl text-xs font-bold transition {{ $isWatched ? 'bg-slate-100 text-slate-700 hover:bg-slate-200 border border-slate-300' : 'bg-emerald-600 hover:bg-emerald-500 text-white shadow-md shadow-emerald-600/30' }}">
                            @if($isWatched)
                            <span>✓ Watched (Click to Unmark)</span>
                            @else
                            <span>Mark as Completed</span>
                            @endif
                        </button>
                    </form>
                </div>

                <div class="text-xs text-slate-700 leading-relaxed pt-2 border-t border-slate-100">
                    <p>{{ $video->description }}</p>
                </div>

                @if($video->lecture_notes_url)
                <div class="p-4 rounded-2xl bg-indigo-50 border border-indigo-100 flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <span class="text-lg">📄</span>
                        <div>
                            <span class="font-bold text-xs text-indigo-900 block">Official Lecture Notes &amp; Textbook Reference</span>
                            <span class="text-[11px] text-indigo-700">Download supplementary study notes and derivation worksheets.</span>
                        </div>
                    </div>
                    <a href="{{ $video->lecture_notes_url }}" target="_blank" rel="noopener noreferrer" 
                       class="px-4 py-2 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-500 text-white transition shadow-sm">
                        Download PDF ↗
                    </a>
                </div>
                @endif
            </div>
        </div>

        <!-- Right Column: Playlist of Lectures in This Chapter -->
        <div class="space-y-4">
            <div class="bg-white rounded-3xl p-5 border border-slate-200 shadow-sm space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <span class="text-[10px] font-black uppercase tracking-wider text-indigo-600 block">Chapter Playlist</span>
                        <h2 class="text-sm font-bold text-slate-900 truncate max-w-[200px]" title="{{ $video->topic->title }}">
                            {{ $video->topic->title }}
                        </h2>
                    </div>
                    <span class="text-xs font-bold text-slate-500">
                        {{ $video->topic->videos->count() }} Lectures
                    </span>
                </div>

                <div class="space-y-2.5 max-h-[500px] overflow-y-auto scrollbar-thin">
                    @foreach($video->topic->videos as $idx => $playItem)
                    @php 
                        $active = $playItem->id === $video->id;
                        $watchedItem = $playItem->isWatchedBy(Auth::id());
                    @endphp
                    <a href="{{ route('videos.show', $playItem->id) }}" 
                       class="flex items-center space-x-3 p-3 rounded-2xl transition border {{ $active ? 'bg-indigo-50/80 border-indigo-300 ring-1 ring-indigo-300' : 'hover:bg-slate-50 border-slate-100' }}">
                        <span class="w-6 h-6 rounded-lg text-[11px] font-extrabold flex items-center justify-center shrink-0 {{ $active ? 'bg-indigo-600 text-white' : ($watchedItem ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600') }}">
                            @if($watchedItem)
                            ✓
                            @else
                            {{ $idx + 1 }}
                            @endif
                        </span>

                        <div class="min-w-0 flex-1">
                            <span class="text-xs font-bold block truncate {{ $active ? 'text-indigo-900' : 'text-slate-800' }}">
                                {{ $playItem->title }}
                            </span>
                            <span class="text-[10px] text-slate-400 block mt-0.5">
                                ⏱ {{ $playItem->duration_formatted }}
                            </span>
                        </div>
                    </a>
                    @endforeach
                </div>

                <!-- Link to Chapter Syllabus & DPPs -->
                <div class="pt-3 border-t border-slate-100">
                    <a href="{{ route('syllabus.show', $video->topic->id) }}" 
                       class="w-full py-2.5 px-4 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-800 transition flex items-center justify-center space-x-1">
                        <span>Chapter Syllabus &amp; DPPs</span>
                        <span>&rarr;</span>
                    </a>
                </div>
            </div>
        </div>

    </div>

</div>
@endsection
