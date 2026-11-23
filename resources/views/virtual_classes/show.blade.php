@extends('layouts.app')

@section('title', $virtualClass->title . ' — Virtual Class')

@section('content')
<div class="max-w-[1200px] mx-auto space-y-4">

    <!-- Breadcrumb -->
    <div class="flex items-center space-x-2 text-xs text-slate-500">
        <a href="{{ route('virtual-classes.index') }}" class="hover:text-indigo-600 dark:hover:text-indigo-400 font-semibold flex items-center space-x-1">
            <i class="ti-arrow-left text-[10px]"></i>
            <span>Virtual Classes</span>
        </a>
        <span>/</span>
        <span class="text-slate-900 dark:text-white font-bold truncate max-w-sm">{{ $virtualClass->title }}</span>
    </div>

    <!-- Main Hero Card -->
    <div class="p-6 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-slate-100 dark:border-slate-800">
            <div class="flex items-start space-x-3">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white text-xl shadow-xs shrink-0" style="background: var(--system_primary_gradient);">
                    <i class="{{ $virtualClass->host_icon }}"></i>
                </div>
                <div>
                    <div class="flex items-center space-x-2">
                        <span class="px-2.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider
                            @if($virtualClass->host === 'zoom') bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300
                            @elseif($virtualClass->host === 'google_meet') bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300
                            @else bg-purple-50 text-purple-700 dark:bg-purple-950 dark:text-purple-300 @endif">
                            {{ $virtualClass->host_label }}
                        </span>
                        @if($virtualClass->isLiveNow())
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500 text-white animate-pulse">
                            🔴 Live Now
                        </span>
                        @endif
                    </div>
                    <h1 class="text-xl font-black text-slate-900 dark:text-white mt-1">{{ $virtualClass->title }}</h1>
                </div>
            </div>

            @if($virtualClass->join_url)
            <a href="{{ route('virtual-classes.join', $virtualClass->id) }}" target="_blank" 
               class="primary-btn inline-flex items-center space-x-2 shadow-md px-6 py-2.5 text-sm font-bold">
                <i class="ti-control-play"></i>
                <span>Join Classroom Meeting</span>
            </a>
            @endif
        </div>

        <!-- Session Metadata Strip -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 py-2">
            <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Date &amp; Time</span>
                <span class="text-xs font-bold text-slate-800 dark:text-slate-200 block mt-0.5">{{ $virtualClass->start_time->format('D, M j, Y') }}</span>
                <span class="text-[11px] font-mono font-bold text-indigo-600 dark:text-indigo-400">{{ $virtualClass->start_time->format('h:i A') }}</span>
            </div>

            <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Duration</span>
                <span class="text-xs font-bold text-slate-800 dark:text-slate-200 block mt-0.5">{{ $virtualClass->duration }} Minutes</span>
                <span class="text-[11px] text-slate-400">Ends ~{{ $virtualClass->end_time ? $virtualClass->end_time->format('h:i A') : '' }}</span>
            </div>

            <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Target Audience</span>
                <span class="text-xs font-bold text-slate-800 dark:text-slate-200 block mt-0.5">
                    {{ $virtualClass->academicClass?->display_name ?? ($virtualClass->batch?->name ?? 'Open to All') }}
                </span>
                <span class="text-[11px] text-slate-400">Capacity: {{ $virtualClass->capacity }}</span>
            </div>

            <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Instructor</span>
                <span class="text-xs font-bold text-slate-800 dark:text-slate-200 block mt-0.5">{{ $virtualClass->instructor?->name ?? 'Faculty' }}</span>
                <span class="text-[11px] text-slate-400">{{ $virtualClass->instructor?->email }}</span>
            </div>
        </div>

        @if($virtualClass->meeting_id || $virtualClass->password)
        <!-- Access Credentials -->
        <div class="p-4 rounded-xl bg-indigo-50 dark:bg-indigo-950/40 border border-indigo-200 dark:border-indigo-800/60 flex flex-wrap items-center gap-4 text-xs">
            @if($virtualClass->meeting_id)
            <div>
                <span class="text-slate-500 text-[10px] block uppercase font-bold">Meeting ID</span>
                <code class="font-bold text-indigo-700 dark:text-indigo-300 font-mono text-sm">{{ $virtualClass->meeting_id }}</code>
            </div>
            @endif
            @if($virtualClass->password)
            <div>
                <span class="text-slate-500 text-[10px] block uppercase font-bold">Passcode</span>
                <code class="font-bold text-indigo-700 dark:text-indigo-300 font-mono text-sm">{{ $virtualClass->password }}</code>
            </div>
            @endif
        </div>
        @endif

        @if($virtualClass->description)
        <div>
            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Session Agenda &amp; Prerequisites</h3>
            <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800 text-xs text-slate-700 dark:text-slate-300 leading-relaxed whitespace-pre-line">
                {{ $virtualClass->description }}
            </div>
        </div>
        @endif
    </div>

</div>
@endsection
