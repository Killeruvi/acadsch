@extends('install.layout')

@section('title', 'Server Prerequisites & Permissions')

@section('content')
<div>
    <div class="mb-6">
        <h2 class="text-xl sm:text-2xl font-black text-white tracking-tight">1. Server Prerequisites & Permissions</h2>
        <p class="text-sm text-slate-400 mt-1">
            Verifying your hosting environment meets the required PHP extensions and directory write permissions.
        </p>
    </div>

    <!-- PHP Version -->
    <div class="mb-6 p-4 rounded-xl border {{ $requirements['php_satisfied'] ? 'bg-emerald-500/5 border-emerald-500/20' : 'bg-rose-500/5 border-rose-500/20' }} flex items-center justify-between">
        <div class="flex items-center gap-3">
            <span class="w-8 h-8 rounded-lg {{ $requirements['php_satisfied'] ? 'bg-emerald-500/20 text-emerald-400' : 'bg-rose-500/20 text-rose-400' }} flex items-center justify-center font-bold">
                @if($requirements['php_satisfied'])
                    ✓
                @else
                    ✕
                @endif
            </span>
            <div>
                <div class="text-sm font-bold text-white">PHP Runtime Version</div>
                <div class="text-xs text-slate-400">Required: PHP 8.2 or newer (Detected: PHP {{ $requirements['php_version'] }})</div>
            </div>
        </div>
        <span class="text-xs font-bold px-2.5 py-1 rounded-md {{ $requirements['php_satisfied'] ? 'bg-emerald-500/20 text-emerald-300' : 'bg-rose-500/20 text-rose-300' }}">
            {{ $requirements['php_satisfied'] ? 'Pass' : 'Upgrade Required' }}
        </span>
    </div>

    <!-- PHP Extensions -->
    <div class="mb-6">
        <h3 class="text-sm font-bold text-slate-300 uppercase tracking-wider mb-3">PHP Extensions</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
            @foreach($requirements['extensions'] as $extKey => $ext)
                <div class="p-3 rounded-lg border {{ $ext['satisfied'] ? 'bg-slate-900/60 border-slate-800' : 'bg-rose-500/10 border-rose-500/30' }} flex items-center justify-between">
                    <span class="text-xs font-medium text-slate-200">{{ $ext['name'] }}</span>
                    <span class="text-xs font-bold {{ $ext['satisfied'] ? 'text-emerald-400' : 'text-rose-400' }}">
                        {{ $ext['satisfied'] ? 'Enabled' : 'Missing' }}
                    </span>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Directory Permissions -->
    <div class="mb-8">
        <h3 class="text-sm font-bold text-slate-300 uppercase tracking-wider mb-3">Directory Write Permissions</h3>
        <div class="space-y-2">
            @foreach($requirements['directories'] as $label => $dir)
                <div class="p-3 rounded-lg border {{ $dir['satisfied'] ? 'bg-slate-900/60 border-slate-800' : 'bg-rose-500/10 border-rose-500/30' }} flex items-center justify-between">
                    <span class="text-xs font-mono text-slate-300">{{ $dir['path'] }}</span>
                    <span class="text-xs font-bold {{ $dir['satisfied'] ? 'text-emerald-400' : 'text-rose-400' }}">
                        {{ $dir['satisfied'] ? 'Writable (0775)' : 'Not Writable' }}
                    </span>
                </div>
            @endforeach

            <div class="p-3 rounded-lg border {{ $requirements['env_writable'] ? 'bg-slate-900/60 border-slate-800' : 'bg-rose-500/10 border-rose-500/30' }} flex items-center justify-between">
                <span class="text-xs font-mono text-slate-300">.env (Configuration File)</span>
                <span class="text-xs font-bold {{ $requirements['env_writable'] ? 'text-emerald-400' : 'text-rose-400' }}">
                    {{ $requirements['env_writable'] ? 'Writable' : 'Not Writable' }}
                </span>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div class="flex items-center justify-between pt-6 border-t border-slate-800">
        <a href="{{ route('install.requirements') }}" class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-400 hover:text-white bg-slate-900 hover:bg-slate-800 transition">
            ↻ Re-Check Environment
        </a>
        @if($requirements['all_passed'])
            <a href="{{ route('install.database') }}" class="px-6 py-2.5 rounded-xl text-xs font-extrabold text-white bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 shadow-lg shadow-purple-500/25 transition">
                Proceed to Database Configuration &rarr;
            </a>
        @else
            <button disabled class="px-6 py-2.5 rounded-xl text-xs font-extrabold text-slate-500 bg-slate-800 cursor-not-allowed">
                Fix Missing Requirements to Proceed
            </button>
        @endif
    </div>
</div>
@endsection
