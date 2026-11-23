@extends('layouts.app')
@section('title','Promotion Policy')
@section('content')
<div class="max-w-4xl mx-auto space-y-4 p-4">
 <h1 class="text-xl font-black">Examination-based promotion policy</h1>
 @if(session('success'))<div class="p-3 rounded bg-emerald-50 text-emerald-800">{{ session('success') }}</div>@endif
 <form method="GET" class="bg-white dark:bg-slate-900 p-4 rounded-xl border"><label class="block text-sm font-bold mb-2">Source class and session</label><select name="class_id" onchange="this.form.submit()" class="w-full rounded border p-2"><option value="">Select class</option>@foreach($classes as $c)<option value="{{ $c->id }}" @selected($class?->id===$c->id)>{{ $c->display_name }} — {{ $c->academic_year }}</option>@endforeach</select></form>
 @if($class)<form method="POST" action="{{ route('school.progression.policy.save') }}" class="bg-white dark:bg-slate-900 p-4 rounded-xl border space-y-4">@csrf<input type="hidden" name="class_id" value="{{ $class->id }}"><p class="text-sm">Policy applies only to {{ $class->display_name }} in {{ $class->academic_year }}. Defaults: 35% overall and 35% per subject.</p>
 <div><label class="block text-sm font-semibold">Minimum overall percentage</label><input type="number" min="0" max="100" step="0.01" name="minimum_overall_percentage" value="{{ $policy->minimum_overall_percentage ?? 35 }}" required class="border rounded p-2 w-full"></div>
 <div><label class="block text-sm font-semibold">Minimum subject percentage</label><input type="number" min="0" max="100" step="0.01" name="minimum_subject_percentage" value="{{ $policy->minimum_subject_percentage ?? 35 }}" required class="border rounded p-2 w-full"></div>
 <label class="flex gap-2"><input type="checkbox" name="require_all_subject_marks" value="1" @checked($policy->require_all_subject_marks ?? true)> Require marks for every configured subject</label><label class="flex gap-2"><input type="checkbox" name="require_published_result" value="1" @checked($policy->require_published_result ?? true)> Require published final report card</label>
 <button class="primary-btn">Save promotion policy</button></form>@endif
 <a class="text-indigo-600 underline" href="{{ route('school.progression.preview') }}">Open bulk promotion preview</a>
</div>
@endsection
