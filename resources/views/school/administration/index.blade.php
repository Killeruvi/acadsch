@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6 space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div><h1 class="text-2xl font-bold text-slate-900 dark:text-white">School Administration</h1><p class="text-sm text-slate-500">Operational overview, configuration checks and administrator activity.</p></div>
        <a href="{{ route('settings.index') }}" class="px-4 py-2 rounded-lg bg-indigo-600 text-white">School settings</a>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        @foreach(['students' => 'Students', 'parents' => 'Parents', 'teachers' => 'Teachers', 'admins' => 'Administrators'] as $key => $label)
            <div class="rounded-xl border border-slate-200 dark:border-slate-700 p-4 bg-white dark:bg-slate-900"><div class="text-sm text-slate-500">{{ $label }}</div><div class="text-2xl font-bold">{{ number_format($counts[$key]) }}</div></div>
        @endforeach
    </div>

    <section class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-5">
        <h2 class="font-semibold text-lg mb-3">School setup checklist</h2>
        <div class="grid md:grid-cols-2 gap-3">
            @foreach($checks as $label => $complete)
                <div class="flex items-center gap-2 text-sm"><span class="{{ $complete ? 'text-emerald-600' : 'text-amber-600' }}">{{ $complete ? '✓' : '!' }}</span><span>{{ $label }}</span><span class="ml-auto text-xs {{ $complete ? 'text-emerald-600' : 'text-amber-600' }}">{{ $complete ? 'Configured' : 'Needs attention' }}</span></div>
            @endforeach
        </div>
        <p class="text-xs text-slate-500 mt-4">This checklist checks basic profile completeness only; it does not certify regulatory or operational compliance.</p>
    </section>

    <section class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-5 space-y-4">
        <div class="flex flex-wrap justify-between gap-3"><div><h2 class="font-semibold text-lg">Administrator activity log</h2><p class="text-sm text-slate-500">Records school configuration changes made after this audit feature is installed.</p></div>
            <form method="GET" action="{{ route('school.administration.audit.export') }}" class="flex flex-wrap gap-2 items-end">
                <label class="text-xs">From <input type="date" name="from" class="block rounded border-slate-300 dark:bg-slate-800"></label>
                <label class="text-xs">To <input type="date" name="to" class="block rounded border-slate-300 dark:bg-slate-800"></label>
                <button class="px-3 py-2 rounded bg-slate-800 text-white text-sm">Export CSV</button>
            </form>
        </div>
        <div class="overflow-x-auto"><table class="min-w-full text-sm"><thead><tr class="text-left border-b"><th class="p-2">Date</th><th class="p-2">Administrator</th><th class="p-2">Action</th><th class="p-2">Details</th></tr></thead><tbody>
            @forelse($auditLogs as $log)<tr class="border-b border-slate-100 dark:border-slate-800"><td class="p-2 whitespace-nowrap">{{ $log->created_at?->format('d M Y, h:i A') }}</td><td class="p-2">{{ $log->user?->name ?? 'Deleted user' }}</td><td class="p-2">{{ $log->action }}</td><td class="p-2">{{ $log->description }}</td></tr>
            @empty<tr><td colspan="4" class="p-4 text-center text-slate-500">No administrator activity has been recorded yet.</td></tr>@endforelse
        </tbody></table></div>
        {{ $auditLogs->links() }}
    </section>

    <section class="rounded-xl border border-amber-200 dark:border-amber-900 bg-amber-50 dark:bg-amber-950/20 p-5">
        <h2 class="font-semibold">Database backup reminder</h2>
        <p class="text-sm mt-1">This ERP does not currently run or verify automated database backups. Configure scheduled database and private-file backups with your hosting provider, keep an off-server copy, and test restoration before relying on the system for live school records.</p>
    </section>
</div>
@endsection
