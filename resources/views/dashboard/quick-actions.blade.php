@extends('layouts.app')

@section('title', 'Dashboard' . ' — ' . ($school['name'] ?? 'PrimeOS Academy'))

@section('content')
<div class="space-y-4">

    <!-- Infix LMS Signature Dashboard Title & Action Row -->
    <div class="white_box p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-3">
        <div>
            <div class="flex items-center space-x-2 text-xs text-slate-400 font-semibold mb-1">
                <i class="ti-calendar"></i>
                <span>{{ now()->format('l, F j, Y') }}</span>
                <span>&bull;</span>
                <span class="text-slate-500 font-mono">{{ now()->format('h:i A') }}</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight flex items-center space-x-2">
                <span>Welcome To — {{ $school['name'] }}</span>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider text-white shadow-xs" style="background: var(--system_primary_gradient);">
                    