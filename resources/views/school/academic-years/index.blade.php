@extends('layouts.app')

@section('title', 'Academic Year Management')

@section('content')
<div class="max-w-5xl mx-auto space-y-5 pb-24 sm:pb-8">
    <div>
        <div class="text-[11px] text-slate-500 font-semibold mb-1">Dashboard / School Administration</div>
        <h1 class="text-xl font-black text-slate-900 dark:text-white">Academic Year Management</h1>
        <p class="text-sm text-slate-500 mt-1">Prepare the next session, create its class structure and activate it when the school is ready.</p>
    </div>

    @if(session('success'))<div class="rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-800 p-3 text-sm">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="rounded-xl border border-rose-200 bg-rose-50 text-rose-800 p-3 text-sm"><ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <section class="rounded-2xl border border-indigo-200 bg-indigo-50 dark:bg-indigo-950/30 dark:border-indigo-900 p-4">
        <div class="text-xs font-bold text-indigo-700 dark:text-indigo-300">CURRENT ACTIVE SESSION</div>
        <div class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ $activeSession ?: 'Not configured' }}</div>
        <p class="text-xs text-slate-600 dark:text-slate-300 mt-1">New admissions, subject defaults and academic workflows may use this session.</p>
    </section>

    <section class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 space-y-3">
        <div><h2 class="font-black text-slate-900 dark:text-white">Prepare a new academic session</h2><p class="text-xs text-slate-500 mt-1">Copies class/subject structure plus session-specific fee structures, timetable periods and examination terms/dates. Teacher and room assignments are cleared. Student results, payments and report cards are never copied.</p></div>
        <form method="POST" action="{{ route('school.academic-years.clone') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm">
            @csrf
            <div><label class="block text-xs font-bold mb-1" for="source_year">Copy structure from</label><select id="source_year" name="source_year" required class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800">@foreach($sessions as $session)<option value="{{ $session->academic_year }}">{{ $session->academic_year }} ({{ $session->class_count }} classes)</option>@endforeach</select></div>
            <div><label class="block text-xs font-bold mb-1" for="target_year">New session</label><input id="target_year" name="target_year" required pattern="[0-9]{4}-[0-9]{4}" placeholder="2027-2028" class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800"></div>
            <div class="flex items-end"><button class="primary-btn w-full" type="submit" onclick="return confirm('Create class and subject structure for the new session? No student records will be copied.')">Create session structure</button></div>
        </form>
    </section>

    <section class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 space-y-3">
        <h2 class="font-black text-slate-900 dark:text-white">Available academic sessions</h2>
        @forelse($sessions as $session)
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b last:border-0 border-slate-100 dark:border-slate-800 py-3">
                <div><div class="font-bold text-slate-900 dark:text-white">{{ $session->academic_year }} @if($activeSession === $session->academic_year)<span class="ml-2 text-[10px] rounded-full bg-emerald-100 text-emerald-800 px-2 py-1">Active</span>@endif</div><div class="text-xs text-slate-500">{{ $session->class_count }} classes · {{ $sessionSetup[$session->academic_year]['fees'] ?? 0 }} fee items · {{ $sessionSetup[$session->academic_year]['timetable'] ?? 0 }} timetable periods · {{ $sessionSetup[$session->academic_year]['exams'] ?? 0 }} exam terms</div></div>
                @if($activeSession !== $session->academic_year)
                    <form method="POST" action="{{ route('school.academic-years.activate') }}">@csrf<input type="hidden" name="academic_year" value="{{ $session->academic_year }}"><button class="px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 text-xs font-bold" onclick="return confirm('Make {{ $session->academic_year }} the active academic session?')">Set as active session</button></form>
                @endif
            </div>
        @empty
            <p class="text-sm text-slate-500">No academic sessions found. Create classes first or configure the academic session in school settings.</p>
        @endforelse
    </section>

    <section class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 flex flex-wrap gap-4 text-sm"><a class="text-indigo-600 underline" href="{{ route('school.progression.policy') }}">Configure examination promotion policy</a><a class="text-indigo-600 underline" href="{{ route('school.progression.preview') }}">Bulk promotion preview & audit</a></section>

    <section class="rounded-2xl border border-amber-200 bg-amber-50 dark:bg-amber-950/20 dark:border-amber-900 p-4 text-xs text-amber-900 dark:text-amber-200 space-y-1">
        <strong>Before opening the new session:</strong>
        <ul class="list-disc pl-5"><li>Assign class teachers and subject teachers.</li><li>Review capacity and roll numbers.</li><li>Review copied fees, timetable periods and exam dates; assign teachers and rooms.</li><li>Use Student Promotion to create new-year enrollment records.</li><li>Check previous-year attendance, fee ledgers and report cards remain available.</li></ul>
    </section>
</div>
@endsection
