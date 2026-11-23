@extends('layouts.app')
@section('title', 'Academic Streams')
@section('content')
<div class="max-w-5xl mx-auto space-y-4 p-4">
    <div class="flex items-center justify-between gap-3">
        <div><h1 class="text-xl font-bold text-slate-900 dark:text-white">Academic Streams</h1><p class="text-sm text-slate-500">Create, rename only when unused, activate and deactivate stream master records.</p></div>
        <a href="{{ route('school.sections.index') }}" class="text-sm underline">Sections</a>
    </div>
    @if($errors->any())<div class="rounded-lg bg-rose-50 text-rose-700 p-3 text-sm">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('school.streams.store') }}" class="rounded-xl border p-4 bg-white dark:bg-slate-900 flex gap-2">@csrf
        <input name="name" required maxlength="50" placeholder="New stream name" class="flex-1 rounded-lg border p-2 text-sm bg-transparent">
        <button class="rounded-lg px-4 py-2 bg-indigo-600 text-white text-sm font-semibold">Add stream</button>
    </form>
    <div class="rounded-xl border bg-white dark:bg-slate-900 overflow-x-auto">
        <table class="w-full text-sm"><thead><tr class="text-left bg-slate-50 dark:bg-slate-800"><th class="p-3">Stream</th><th class="p-3">Sections</th><th class="p-3">Classes</th><th class="p-3">Status</th><th class="p-3">Actions</th></tr></thead><tbody>
        @foreach($streams as $stream)
            <tr class="border-t border-slate-100 dark:border-slate-800"><td class="p-3 font-semibold">{{ $stream->name }}</td><td class="p-3">{{ $stream->sections_count }}</td><td class="p-3">{{ $classCounts[$stream->name] ?? 0 }}</td><td class="p-3">{{ $stream->is_active ? 'Active' : 'Inactive' }}</td><td class="p-3"><div class="flex gap-2">
                @if(($classCounts[$stream->name] ?? 0) === 0 && $stream->sections_count === 0)
                <form method="POST" action="{{ route('school.streams.update', $stream->id) }}">@csrf @method('PATCH')<input name="name" value="{{ $stream->name }}" required maxlength="50" class="w-32 rounded border p-1 text-xs"><button class="underline text-xs">Rename</button></form>
                @endif
                <form method="POST" action="{{ route('school.streams.toggle', $stream->id) }}">@csrf @method('PATCH')<button class="underline text-xs {{ $stream->is_active ? 'text-rose-600' : 'text-emerald-600' }}">{{ $stream->is_active ? 'Deactivate' : 'Reactivate' }}</button></form>
            </div></td></tr>
        @endforeach
        </tbody></table>
        <div class="p-3">{{ $streams->links() }}</div>
    </div>
</div>
@endsection
