@extends('layouts.app')
@section('title', 'Tax Declaration Review')
@section('content')
<div class="max-w-5xl mx-auto space-y-4"><header><h1 class="text-2xl font-black">Staff tax declaration review</h1><p class="text-sm text-slate-500">Internal review only. Approval does not file a tax return or authorize automatic payroll changes.</p></header>
@forelse($declarations as $declaration)
<section class="rounded-xl border bg-white dark:bg-slate-900 p-4 space-y-3"><div class="flex justify-between gap-3"><div><strong>{{ $declaration->staff_name }}</strong><div class="text-xs text-slate-500">{{ $declaration->staff_email }} · FY {{ $declaration->financial_year }} · {{ strtoupper($declaration->tax_regime) }} regime</div></div><span class="text-xs">Submitted {{ $declaration->submitted_at }}</span></div>
@if(isset($items[$declaration->id]))<div class="overflow-x-auto"><table class="w-full text-sm"><thead><tr class="text-left border-b"><th>Section</th><th>Description</th><th class="text-right">Amount</th></tr></thead><tbody>@foreach($items[$declaration->id] as $item)<tr class="border-b"><td>{{ $item->section_code }}</td><td>{{ $item->description }}</td><td class="text-right">₹{{ number_format($item->declared_amount,2) }}</td></tr>@endforeach</tbody></table></div>@endif
<p class="text-sm">{{ $declaration->declaration_note }}</p><form method="POST" action="{{ route('school.hr.tax-declarations.review',$declaration->id) }}" class="flex flex-wrap gap-2">@csrf<textarea name="review_note" maxlength="2000" placeholder="Review notes" class="border rounded-lg p-2 flex-1 min-w-48"></textarea><button name="decision" value="approved" class="rounded-lg bg-emerald-700 text-white px-4 py-2">Approve</button><button name="decision" value="rejected" class="rounded-lg bg-rose-700 text-white px-4 py-2">Reject</button></form></section>
@empty<div class="rounded-xl border p-6 text-slate-500">No submitted declarations are awaiting review.</div>@endforelse
{{ $declarations->links() }}</div>
@endsection
