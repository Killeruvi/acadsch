@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8 space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div><h1 class="text-2xl font-black text-slate-900 dark:text-white">Admission Enquiries</h1><p class="text-sm text-slate-500">Review enquiries and record follow-up.</p></div>
        <a href="{{ route('school.students.create') }}" class="px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-semibold">Admit a student</a>
    </div>
    @if(session('success'))<div class="p-3 rounded-lg bg-emerald-50 text-emerald-800">{{ session('success') }}</div>@endif
    <div class="grid grid-cols-2 md:grid-cols-6 gap-2">
        @foreach(['new'=>'New','contacted'=>'Contacted','visit_scheduled'=>'Visit scheduled','approved'=>'Approved','rejected'=>'Rejected','converted'=>'Converted'] as $key=>$label)
            <div class="rounded-xl border border-slate-200 dark:border-slate-700 p-3"><div class="text-xs text-slate-500">{{ $label }}</div><div class="text-xl font-bold">{{ $counts[$key] ?? 0 }}</div></div>
        @endforeach
    </div>
    <form method="GET" class="flex flex-wrap gap-2">
        <input name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search student, guardian or phone" class="rounded-lg border px-3 py-2 text-sm dark:bg-slate-900 dark:border-slate-700">
        <select name="status" class="rounded-lg border px-3 py-2 text-sm dark:bg-slate-900 dark:border-slate-700"><option value="">All statuses</option>@foreach(['new','contacted','visit_scheduled','approved','rejected','converted'] as $status)<option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucwords(str_replace('_',' ',$status)) }}</option>@endforeach</select>
        <button class="rounded-lg bg-slate-800 text-white px-4 py-2 text-sm">Filter</button>
    </form>
    <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-700">
        <table class="min-w-full text-sm"><thead class="bg-slate-50 dark:bg-slate-800"><tr><th class="p-3 text-left">Student / class</th><th class="p-3 text-left">Guardian</th><th class="p-3 text-left">Enquiry</th><th class="p-3 text-left">Follow-up</th></tr></thead><tbody>
        @forelse($enquiries as $enquiry)
            <tr class="border-t border-slate-200 dark:border-slate-700 align-top"><td class="p-3"><strong>{{ $enquiry->student_name }}</strong><div class="text-xs text-slate-500">Class {{ $enquiry->applying_for_class }}</div><div class="text-xs text-slate-500">{{ $enquiry->created_at->format('d M Y') }}</div></td><td class="p-3">{{ $enquiry->guardian_name }}<div>{{ $enquiry->guardian_phone }}</div><div class="text-xs text-slate-500">{{ $enquiry->guardian_email }}</div></td><td class="p-3 max-w-xs">{{ $enquiry->message }}@if($enquiry->previous_school)<div class="text-xs text-slate-500 mt-1">Previous: {{ $enquiry->previous_school }}</div>@endif</td><td class="p-3 min-w-64"><form method="POST" action="{{ route('school.admissions.enquiries.update',$enquiry) }}" class="space-y-2">@csrf @method('PATCH')<select name="status" class="w-full rounded-lg border px-2 py-2 dark:bg-slate-900 dark:border-slate-700">@foreach(['new','contacted','visit_scheduled','approved','rejected','converted'] as $status)<option value="{{ $status }}" @selected($enquiry->status === $status)>{{ ucwords(str_replace('_',' ',$status)) }}</option>@endforeach</select><textarea name="admin_notes" rows="2" placeholder="Internal notes" class="w-full rounded-lg border px-2 py-2 dark:bg-slate-900 dark:border-slate-700">{{ $enquiry->admin_notes }}</textarea><button class="rounded-lg bg-emerald-600 text-white px-3 py-2 text-xs font-semibold">Save</button><a class="ml-2 text-xs text-emerald-700 underline" href="{{ route('school.students.create', ['name'=>$enquiry->student_name,'guardian_name'=>$enquiry->guardian_name,'guardian_phone'=>$enquiry->guardian_phone,'guardian_email'=>$enquiry->guardian_email,'date_of_birth'=>$enquiry->date_of_birth?->format('Y-m-d'),'previous_school_name'=>$enquiry->previous_school]) }}">Start admission</a></form></td></tr>
        @empty<tr><td colspan="4" class="p-8 text-center text-slate-500">No admission enquiries found.</td></tr>@endforelse
        </tbody></table>
    </div>
    {{ $enquiries->links() }}
</div>
@endsection
