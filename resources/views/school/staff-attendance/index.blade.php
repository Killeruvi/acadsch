@extends('layouts.app')

@section('title', 'Staff Attendance & Leave')

@section('content')
<div class="max-w-7xl mx-auto space-y-5 pb-20">
    <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-200 dark:border-slate-800 pb-4">
        <div>
            <div class="text-xs text-slate-500 mb-1"><a href="{{ route('dashboard') }}">Dashboard</a> / Human Resources</div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white">Staff Attendance & Leave</h1>
            <p class="text-xs text-slate-500 mt-1">Daily staff attendance, leave approvals and payroll-period attendance summaries.</p>
        </div>
        <a href="{{ route('school.payroll.index', ['month' => $month]) }}" class="px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 text-xs font-bold">Open payroll</a>
    </div>

    <form method="GET" class="flex flex-wrap items-end gap-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3">
        <div><label class="block text-[10px] font-bold uppercase text-slate-500 mb-1" for="staff-attendance-month">Month</label><input id="staff-attendance-month" type="month" name="month" value="{{ $month }}" class="rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent px-3 py-2 text-xs"></div>
        <button class="px-4 py-2 rounded-lg bg-slate-900 text-white text-xs font-bold">Apply</button>
    </form>

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        @foreach(['present' => 'Present', 'absent' => 'Absent', 'half_day' => 'Half day', 'leave' => 'Leave'] as $key => $label)
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4"><div class="text-[10px] uppercase font-bold text-slate-500">{{ $label }}</div><div class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ (int) ($summary[$key] ?? 0) }}</div></div>
        @endforeach
    </div>

    @if(Auth::user()->isAdmin())
    <section class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4">
        <h2 class="font-black text-sm mb-3">Record / correct daily attendance</h2>
        <form method="POST" action="{{ route('school.staff-attendance.mark') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end">
            @csrf
            <div><label class="block text-[10px] font-bold text-slate-500 mb-1">Staff member</label><select name="staff_id" required class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent px-3 py-2 text-xs"><option value="">Select staff</option>@foreach($staff as $person)<option value="{{ $person->id }}">{{ $person->name }} ({{ $person->role }})</option>@endforeach</select></div>
            <div><label class="block text-[10px] font-bold text-slate-500 mb-1">Date</label><input type="date" name="attendance_date" max="{{ now()->toDateString() }}" value="{{ now()->toDateString() }}" required class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent px-3 py-2 text-xs"></div>
            <div><label class="block text-[10px] font-bold text-slate-500 mb-1">Status</label><select name="status" required class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent px-3 py-2 text-xs"><option value="present">Present</option><option value="absent">Absent</option><option value="half_day">Half day</option></select></div>
            <div><label class="block text-[10px] font-bold text-slate-500 mb-1">Remarks</label><input name="remarks" maxlength="500" class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent px-3 py-2 text-xs" placeholder="Optional"></div>
            <button class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-xs font-bold">Save attendance</button>
        </form>
        <p class="text-[10px] text-slate-500 mt-2">Approved leave entries cannot be overwritten by this form. Changes to other existing attendance records are saved as the latest status; keep any required HR correction evidence in the remarks.</p>
    </section>
    @endif

    <section class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4">
        <h2 class="font-black text-sm mb-3">{{ Auth::user()->isAdmin() ? 'Staff attendance register' : 'My attendance' }}</h2>
        <div class="overflow-x-auto"><table class="w-full text-xs text-left"><thead class="text-[10px] uppercase text-slate-500 bg-slate-50 dark:bg-slate-800"><tr><th class="p-3">Date</th>@if(Auth::user()->isAdmin())<th class="p-3">Staff</th>@endif<th class="p-3">Status</th><th class="p-3">Remarks</th><th class="p-3">Recorded by</th></tr></thead><tbody class="divide-y divide-slate-100 dark:divide-slate-800">
        @forelse($records as $record)<tr><td class="p-3 font-mono">{{ $record->attendance_date->format('d M Y') }}</td>@if(Auth::user()->isAdmin())<td class="p-3 font-semibold">{{ $record->staff?->name }}</td>@endif<td class="p-3">{{ str_replace('_', ' ', ucfirst($record->status)) }}</td><td class="p-3">{{ $record->remarks ?: '—' }}</td><td class="p-3">{{ $record->marker?->name ?? 'System / unavailable' }}</td></tr>@empty<tr><td colspan="5" class="p-6 text-center text-slate-500">No staff attendance recorded for this month.</td></tr>@endforelse
        </tbody></table></div><div class="mt-3">{{ $records->links() }}</div>
    </section>

    <section class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4">
            <div class="flex items-center justify-between gap-2 mb-3"><h2 class="font-black text-sm">My leave balances</h2><form method="GET" class="flex gap-2"><input type="hidden" name="month" value="{{ $month }}"><select name="leave_year" class="rounded border bg-transparent px-2 py-1 text-xs">@for($y=now()->year-2;$y<=now()->year+1;$y++)<option value="{{ $y }}" @selected($leaveYear===$y)>{{ $y }}</option>@endfor</select>@if(Auth::user()->isAdmin())<select name="balance_staff_id" class="rounded border bg-transparent px-2 py-1 text-xs">@foreach($staff as $person)<option value="{{ $person->id }}" @selected($balanceStaffId===$person->id)>{{ $person->name }}</option>@endforeach</select>@endif<button class="rounded bg-slate-800 text-white px-2 py-1 text-xs">View</button></form></div>
            <div class="overflow-x-auto"><table class="w-full text-xs"><thead><tr class="text-left text-slate-500"><th class="p-2">Leave type</th><th class="p-2">Opening</th><th class="p-2">Accrued</th><th class="p-2">Used</th><th class="p-2">Encashed</th><th class="p-2">Available</th></tr></thead><tbody>@forelse($leaveBalances as $balance)<tr class="border-t border-slate-100 dark:border-slate-800"><td class="p-2 font-semibold">{{ ucfirst(str_replace('_',' ',$balance->leave_type)) }}</td><td class="p-2">{{ number_format($balance->opening_balance,2) }}</td><td class="p-2">{{ number_format($balance->accrued,2) }}</td><td class="p-2">{{ number_format($balance->used,2) }}</td><td class="p-2">{{ number_format($balance->encashed,2) }}</td><td class="p-2 font-bold">{{ number_format($balance->available,2) }}</td></tr>@empty<tr><td colspan="6" class="p-3 text-slate-500">No balances yet. An administrator must configure a policy and the accrual schedule must run.</td></tr>@endforelse</tbody></table></div>
            <form method="POST" action="{{ route('school.staff-attendance.leave.encashment.request') }}" class="mt-4 grid grid-cols-2 gap-2">@csrf
                <div><label class="block text-[10px] text-slate-500 mb-1">Leave type</label><select name="leave_type" required class="w-full rounded border bg-transparent p-2 text-xs">@foreach($leaveBalances as $balance)<option value="{{ $balance->leave_type }}">{{ ucfirst(str_replace('_',' ',$balance->leave_type)) }} ({{ number_format($balance->available,2) }} available)</option>@endforeach</select></div>
                <div><label class="block text-[10px] text-slate-500 mb-1">Days to encash</label><input type="number" name="days_requested" min="0.01" max="365" step="0.01" required class="w-full rounded border bg-transparent p-2 text-xs"></div>
                <input type="hidden" name="leave_year" value="{{ $leaveYear }}"><div><label class="block text-[10px] text-slate-500 mb-1">Payroll month</label><input type="month" name="payroll_month" min="{{ now()->addMonthNoOverflow()->format('Y-m') }}" required class="w-full rounded border bg-transparent p-2 text-xs"></div>
                <div><label class="block text-[10px] text-slate-500 mb-1">Reason (optional)</label><input name="reason" maxlength="1000" class="w-full rounded border bg-transparent p-2 text-xs"></div>
                <button class="col-span-2 rounded bg-indigo-600 text-white py-2 text-xs font-bold">Request leave encashment</button>
            </form>
        </div>
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4">
            <h2 class="font-black text-sm mb-3">Leave encashment requests</h2>
            <div class="space-y-3">@forelse($encashments as $item)<div class="border-b border-slate-100 dark:border-slate-800 pb-3"><div class="flex justify-between gap-2 text-xs"><strong>{{ Auth::user()->isAdmin() ? $item->staff_name : 'My request' }}</strong><span class="font-bold uppercase">{{ $item->status }}</span></div><p class="text-[11px] text-slate-500">{{ $item->leave_type }} · {{ $item->days_requested }} days · payroll {{ $item->payroll_month }} · {{ $item->leave_year }}</p>@if($item->approved_amount)<p class="text-xs">Approved amount: ₹{{ number_format($item->approved_amount,2) }}</p>@endif@if(Auth::user()->isAdmin() && $item->status==='pending' && (int)$item->staff_id !== (int)Auth::id())<form method="POST" action="{{ route('school.staff-attendance.leave.encashment.review',$item->id) }}" class="grid grid-cols-2 gap-2 mt-2">@csrf<input type="number" name="approved_amount" min="0.01" step="0.01" required placeholder="Approved ₹ amount" class="rounded border bg-transparent p-2 text-xs"><input name="review_remarks" maxlength="2000" placeholder="Remarks" class="rounded border bg-transparent p-2 text-xs"><button name="decision" value="approve" class="rounded bg-emerald-600 text-white p-2 text-xs font-bold">Approve</button><button name="decision" value="reject" class="rounded bg-rose-600 text-white p-2 text-xs font-bold">Reject</button></form>@endif</div>@empty<p class="text-xs text-slate-500">No encashment requests.</p>@endforelse</div>
        </div>
    </section>

    @if(Auth::user()->isAdmin())
    <section class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4">
        <h2 class="font-black text-sm mb-3">Configure a versioned leave policy</h2>
        <form method="POST" action="{{ route('school.staff-attendance.leave.policies.store') }}" class="grid grid-cols-2 md:grid-cols-4 gap-2">@csrf
            <input name="leave_type" required maxlength="40" pattern="[a-z][a-z0-9_]*" placeholder="e.g. annual, sick" class="rounded border bg-transparent p-2 text-xs">
            <input type="number" name="annual_entitlement" min="0" max="365" step="0.01" required placeholder="Annual entitlement" class="rounded border bg-transparent p-2 text-xs">
            <input type="number" name="monthly_accrual" min="0" max="30" step="0.0001" required placeholder="Monthly accrual" class="rounded border bg-transparent p-2 text-xs">
            <input type="number" name="maximum_carry_forward" min="0" max="365" step="0.01" required placeholder="Carry-forward cap" class="rounded border bg-transparent p-2 text-xs">
            <input type="number" name="maximum_encashment" min="0" max="365" step="0.01" required value="0" placeholder="Encashment cap" class="rounded border bg-transparent p-2 text-xs">
            <input type="hidden" name="allow_encashment" value="0"><label class="flex items-center gap-2 text-xs"><input type="checkbox" name="allow_encashment" value="1"> Allow encashment</label>
            <input type="date" name="effective_from" required value="{{ now()->toDateString() }}" class="rounded border bg-transparent p-2 text-xs">
            <input type="date" name="effective_to" class="rounded border bg-transparent p-2 text-xs" placeholder="Effective to">
            <input type="hidden" name="is_enabled" value="0"><label class="flex items-center gap-2 text-xs"><input type="checkbox" name="is_enabled" value="1"> Enable immediately</label>
            <button class="rounded bg-indigo-600 text-white p-2 text-xs font-bold">Create policy version</button>
        </form>
        <p class="text-[10px] text-slate-500 mt-2">Policy versions are retained. Enabled effective periods cannot overlap. Configure categories and entitlements only after HR/legal review.</p>
        <div class="mt-3 space-y-2">@foreach($leavePolicies as $policy)<div class="text-[11px] border-t border-slate-100 dark:border-slate-800 pt-2">{{ $policy->leave_type }} · {{ $policy->effective_from }} to {{ $policy->effective_to ?: 'open' }} · {{ $policy->annual_entitlement }} days/year · {{ $policy->is_enabled ? 'Enabled' : 'Disabled' }}@if(!$policy->effective_to)<form method="POST" action="{{ route('school.staff-attendance.leave.policies.close',$policy->id) }}" class="inline-flex gap-1 ml-2">@csrf<input type="date" name="effective_to" min="{{ $policy->effective_from }}" required class="rounded border bg-transparent p-1"><button class="rounded bg-slate-700 text-white px-2 py-1">Close period</button></form>@endif</div>@endforeach</div>
        <form method="POST" action="{{ route('school.staff-attendance.leave.balances.adjust') }}" class="mt-4 grid grid-cols-2 md:grid-cols-5 gap-2">@csrf
            <select name="staff_id" required class="rounded border bg-transparent p-2 text-xs">@foreach($staff as $person)<option value="{{ $person->id }}">{{ $person->name }}</option>@endforeach</select>
            <input name="leave_type" required maxlength="40" placeholder="Leave type" class="rounded border bg-transparent p-2 text-xs">
            <input type="number" name="leave_year" min="2020" max="2100" value="{{ $leaveYear }}" required class="rounded border bg-transparent p-2 text-xs">
            <input type="number" name="quantity" min="-365" max="365" step="0.01" required placeholder="+/- days" class="rounded border bg-transparent p-2 text-xs">
            <input name="notes" required minlength="5" maxlength="1000" placeholder="Reason for adjustment" class="rounded border bg-transparent p-2 text-xs">
            <button class="col-span-2 md:col-span-5 rounded bg-slate-800 text-white p-2 text-xs font-bold">Record audited balance adjustment</button>
        </form>
    </section>
    @endif

    <section class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4">
            <h2 class="font-black text-sm mb-3">Apply for leave</h2>
            <form method="POST" action="{{ route('school.staff-attendance.leave.apply') }}" class="space-y-2">@csrf
                <div class="grid grid-cols-2 gap-2"><div><label class="block text-[10px] font-bold text-slate-500 mb-1">From</label><input type="date" name="start_date" min="{{ now()->toDateString() }}" required class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent px-3 py-2 text-xs"></div><div><label class="block text-[10px] font-bold text-slate-500 mb-1">To</label><input type="date" name="end_date" min="{{ now()->toDateString() }}" required class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent px-3 py-2 text-xs"></div></div>
                <div><label class="block text-[10px] font-bold text-slate-500 mb-1">Leave category</label><select name="leave_type" required class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent px-3 py-2 text-xs"><option value="paid">Paid leave</option><option value="unpaid">Unpaid leave</option></select></div><div><label class="block text-[10px] font-bold text-slate-500 mb-1">Category</label><select name="leave_category" required class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent px-3 py-2 text-xs"><option value="annual">Annual</option><option value="casual">Casual</option><option value="sick">Sick</option><option value="earned">Earned</option><option value="compensatory">Compensatory</option><option value="maternity">Maternity</option><option value="paternity">Paternity</option><option value="other">Other</option></select></div>
                <div><label class="block text-[10px] font-bold text-slate-500 mb-1">Reason</label><textarea name="reason" minlength="5" maxlength="2000" required rows="3" class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent px-3 py-2 text-xs"></textarea></div>
                <button class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-xs font-bold">Submit leave request</button>
            </form>
        </div>
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4">
            <h2 class="font-black text-sm mb-3">Leave requests</h2>
            <div class="space-y-3">@forelse($leaves as $leave)<div class="border-b border-slate-100 dark:border-slate-800 pb-3">
                <div class="flex justify-between gap-2 text-xs"><strong>{{ Auth::user()->isAdmin() ? $leave->staff?->name : 'My request' }}</strong><span class="font-bold uppercase">{{ $leave->status }}</span></div>
                <p class="text-[11px] text-slate-500 mt-1">{{ $leave->start_date->format('d M Y') }} – {{ $leave->end_date->format('d M Y') }} · {{ ucfirst($leave->leave_type) }} · {{ ucfirst($leave->leave_category ?? 'annual') }} leave</p><p class="text-xs mt-1">{{ $leave->reason }}</p>
                @if(Auth::user()->isAdmin() && $leave->status === 'pending' && $leave->staff_id !== Auth::id())<form method="POST" action="{{ route('school.staff-attendance.leave.review', $leave->id) }}" class="mt-2 flex flex-wrap gap-2">@csrf<input name="review_remarks" maxlength="2000" placeholder="Optional review remarks" class="min-w-0 flex-1 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent px-2 py-1 text-xs"><button name="status" value="approved" class="px-3 py-1 rounded bg-emerald-600 text-white text-xs font-bold">Approve</button><button name="status" value="rejected" class="px-3 py-1 rounded bg-rose-600 text-white text-xs font-bold">Reject</button></form>@endif
                @if($leave->review_remarks)<p class="text-[10px] text-slate-500 mt-1">Review: {{ $leave->review_remarks }}</p>@endif
            </div>@empty<p class="text-xs text-slate-500">No leave requests yet.</p>@endforelse</div><div class="mt-3">{{ $leaves->links() }}</div>
        </div>
    </section>
    <p class="text-[10px] text-slate-500">Payroll integration is informational: attendance and leave totals are shown for the selected month, but salary is not automatically reduced or increased. Working-day calendars, leave entitlements, statutory rules and approved salary policies must be configured before automated payroll calculations.</p>
</div>
@endsection
