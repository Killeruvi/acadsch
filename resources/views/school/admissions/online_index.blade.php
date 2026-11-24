@extends('layouts.app')

@section('title', 'Online Admissions Review Register — PrimeOS')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2">
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-100 text-indigo-800 dark:bg-indigo-900/50 dark:text-indigo-300">
                    Admissions Desk
                </span>
                <span class="text-xs text-slate-500">Student Enrollment Workflow</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight mt-1">
                Online Admissions Review Register
            </h1>
            <p class="text-sm text-slate-600 dark:text-slate-400">
                Process submitted digital applications, verify uploaded documents, and convert candidates into enrolled scholars.
            </p>
        </div>

        <div class="flex items-center space-x-3">
            <a href="{{ route('admissions.apply') }}" target="_blank" class="inline-flex items-center space-x-1.5 px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold transition">
                <span>🌐</span>
                <span>Open Public Portal</span>
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="p-4 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800 text-xs font-semibold flex items-center justify-between">
        <span>{{ session('success') }}</span>
        <button type="button" @click="$el.parentElement.remove()" class="text-emerald-600 hover:text-emerald-800">&times;</button>
    </div>
    @endif

    <!-- Metrics Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <div class="bg-white dark:bg-slate-900 p-3.5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xs">
            <span class="text-[10px] font-bold uppercase text-slate-400 block">Total Received</span>
            <div class="text-xl font-black text-slate-900 dark:text-white mt-0.5">{{ number_format($stats['total']) }}</div>
        </div>
        <div class="bg-white dark:bg-slate-900 p-3.5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xs">
            <span class="text-[10px] font-bold uppercase text-slate-400 block">Submitted</span>
            <div class="text-xl font-black text-slate-600 dark:text-slate-300 mt-0.5">{{ number_format($stats['submitted']) }}</div>
        </div>
        <div class="bg-white dark:bg-slate-900 p-3.5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xs">
            <span class="text-[10px] font-bold uppercase text-slate-400 block">Under Review</span>
            <div class="text-xl font-black text-amber-500 mt-0.5">{{ number_format($stats['under_review']) }}</div>
        </div>
        <div class="bg-white dark:bg-slate-900 p-3.5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xs">
            <span class="text-[10px] font-bold uppercase text-slate-400 block">Shortlisted</span>
            <div class="text-xl font-black text-indigo-600 dark:text-indigo-400 mt-0.5">{{ number_format($stats['shortlisted']) }}</div>
        </div>
        <div class="bg-white dark:bg-slate-900 p-3.5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xs">
            <span class="text-[10px] font-bold uppercase text-slate-400 block">Admitted</span>
            <div class="text-xl font-black text-emerald-600 dark:text-emerald-400 mt-0.5">{{ number_format($stats['admitted']) }}</div>
        </div>
        <div class="bg-white dark:bg-slate-900 p-3.5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xs">
            <span class="text-[10px] font-bold uppercase text-slate-400 block">Rejected</span>
            <div class="text-xl font-black text-rose-500 mt-0.5">{{ number_format($stats['rejected']) }}</div>
        </div>
    </div>

    <!-- Filters & Table -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-xs space-y-4">
        <form action="{{ route('school.admissions.online.index') }}" method="GET" class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-100 dark:border-slate-800">
            <div class="flex items-center space-x-2">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name, app no, phone..." class="px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-xs font-semibold w-64">
                <select name="status" class="px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-xs font-semibold">
                    <option value="all">All Statuses</option>
                    <option value="submitted" {{ request('status') === 'submitted' ? 'selected' : '' }}>Submitted</option>
                    <option value="under_review" {{ request('status') === 'under_review' ? 'selected' : '' }}>Under Review</option>
                    <option value="shortlisted" {{ request('status') === 'shortlisted' ? 'selected' : '' }}>Shortlisted</option>
                    <option value="admitted" {{ request('status') === 'admitted' ? 'selected' : '' }}>Admitted</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
                <select name="class_id" class="px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-xs font-semibold">
                    <option value="">All Classes</option>
                    @foreach($classes as $c)
                    <option value="{{ $c->id }}" {{ request('class_id') == $c->id ? 'selected' : '' }}>{{ $c->display_name }}</option>
                    @endforeach
                </select>
                <button type="submit" class="px-3 py-1.5 rounded-xl bg-slate-900 text-white dark:bg-white dark:text-slate-900 text-xs font-bold">Filter</button>
            </div>

            <span class="text-xs text-slate-500 font-semibold">{{ $applications->total() }} applications</span>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left">
                <thead class="bg-slate-50 dark:bg-slate-800/60 text-slate-600 dark:text-slate-300 font-bold uppercase tracking-wider text-[10px]">
                    <tr>
                        <th class="p-3">App Reference</th>
                        <th class="p-3">Scholar Name</th>
                        <th class="p-3">Applying Grade</th>
                        <th class="p-3">Parent / Guardian</th>
                        <th class="p-3">City</th>
                        <th class="p-3">Status</th>
                        <th class="p-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($applications as $app)
                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                        <td class="p-3 font-mono font-bold text-slate-900 dark:text-white">
                            {{ $app->application_number }}
                        </td>
                        <td class="p-3">
                            <div class="font-bold text-slate-900 dark:text-white">{{ $app->full_name }}</div>
                            <div class="text-[10px] text-slate-400">DOB: {{ $app->dob->format('d/m/Y') }} &bull; {{ ucfirst($app->gender) }}</div>
                        </td>
                        <td class="p-3 font-semibold text-emerald-700 dark:text-emerald-400">
                            {{ $app->targetClass?->display_name }}
                        </td>
                        <td class="p-3 text-slate-600 dark:text-slate-400">
                            <div>{{ $app->father_name }}</div>
                            <div class="text-[10px] font-mono text-slate-400">📞 {{ $app->father_phone }}</div>
                        </td>
                        <td class="p-3 text-slate-600 dark:text-slate-400">
                            {{ $app->city }}, {{ $app->state }}
                        </td>
                        <td class="p-3">
                            @if($app->application_status === 'admitted')
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300">
                                Admitted
                            </span>
                            @elseif($app->application_status === 'shortlisted')
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-indigo-100 text-indigo-800 dark:bg-indigo-900/50 dark:text-indigo-300">
                                Shortlisted
                            </span>
                            @elseif($app->application_status === 'under_review')
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300">
                                Under Review
                            </span>
                            @elseif($app->application_status === 'rejected')
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 dark:bg-rose-900/50 dark:text-rose-300">
                                Rejected
                            </span>
                            @else
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                Submitted
                            </span>
                            @endif
                        </td>
                        <td class="p-3 text-right">
                            <a href="{{ route('school.admissions.online.show', $app->id) }}" class="px-3 py-1.5 rounded-xl bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/50 dark:hover:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 font-bold text-xs transition">
                                Review Dossier &rarr;
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="p-8 text-center text-slate-400">
                            No online admission applications found matching the selected filters.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pt-2">
            {{ $applications->links() }}
        </div>
    </div>
</div>
@endsection
