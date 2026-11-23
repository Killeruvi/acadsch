@extends('layouts.app')

@section('title', 'Student Promotion & Rollover — ' . ($school['name'] ?? 'Infix LMS'))

@section('content')
<div class="max-w-[1600px] mx-auto space-y-5 pb-24 sm:pb-8">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="text-[11px] text-slate-500 font-semibold mb-1">Dashboard / Academic Hub / Student Progression</div>
            <h1 class="text-lg sm:text-xl font-black text-slate-900 dark:text-white">Student Promotion, Transfers & Re-admission</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Each operation creates a new enrollment record and preserves the student's previous academic history.</p>
            <a href="{{ route('school.academic-years.index') }}" class="inline-flex mt-2 text-xs font-bold text-indigo-600 hover:underline">Manage academic sessions →</a>
            <a href="{{ route('school.student-exits.index') }}" class="inline-flex mt-2 ml-3 text-xs font-bold text-indigo-600 hover:underline">Student exit register →</a>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-800 p-3 text-sm">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 text-rose-800 p-3 text-sm" role="alert">
            <p class="font-bold">Please correct the following:</p>
            <ul class="list-disc pl-5 mt-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <section class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 shadow-2xs">
        <form method="GET" action="{{ route('school.promotion.index') }}" class="flex flex-col sm:flex-row items-end gap-3 text-xs">
            <div class="flex-1 w-full">
                <label for="source_class_id" class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Current class *</label>
                <select id="source_class_id" name="source_class_id" required class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                    <option value="">Choose class</option>
                    @foreach($classes as $class)
                        <option value="{{ $class->id }}" @selected((string)$sourceClassId === (string)$class->id)>{{ $class->display_name }} — {{ $class->academic_year }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="primary-btn" style="padding:.55rem 1.2rem;font-size:.75rem">Load active enrollments</button>
        </form>
    </section>

    @if($sourceClass)
    <form method="POST" action="{{ route('school.promotion.promote') }}" id="progression-form" class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 shadow-2xs space-y-4">
        @csrf
        <input type="hidden" name="source_class_id" value="{{ $sourceClass->id }}">
        <p class="rounded-lg bg-amber-50 dark:bg-amber-950/30 text-amber-800 dark:text-amber-200 p-3 text-xs">Attendance and outstanding fees are shown for review only. Fee debt must not automatically prevent academic promotion; the administrator makes the academic decision.</p>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
            <div>
                <label for="target_class_id" class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Target class *</label>
                <select id="target_class_id" name="target_class_id" required class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                    <option value="">Choose target class</option>
                    @foreach($classes as $class)
                        @if($class->id !== $sourceClass->id)
                            <option value="{{ $class->id }}" data-year="{{ $class->academic_year }}" data-grade="{{ $class->grade_level }}">{{ $class->display_name }} — {{ $class->academic_year }}</option>
                        @endif
                    @endforeach
                </select>
                <p class="text-[11px] text-slate-500 mt-1">The target class master controls the new academic session. No session is typed or guessed here.</p>
            </div>
            <div class="rounded-xl bg-slate-50 dark:bg-slate-800 p-3">
                <div class="text-slate-500">Source enrollment</div>
                <div class="font-bold text-slate-900 dark:text-white">{{ $sourceClass->display_name }} · {{ $sourceClass->academic_year }}</div>
                <div class="text-slate-500 mt-2">Minimum attendance: {{ number_format($minimumAttendance, 1) }}%</div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600 dark:text-slate-400">
                <thead class="bg-slate-50 dark:bg-slate-800/60 uppercase font-black text-[10px] text-slate-500">
                    <tr>
                        <th class="p-3"><input type="checkbox" id="select-eligible" aria-label="Select eligible students"></th>
                        <th class="p-3">Admission #</th><th class="p-3">Student</th><th class="p-3">Attendance</th><th class="p-3">Outstanding fees</th><th class="p-3 text-right">Eligibility</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($students as $student)
                    <tr>
                        <td class="p-3"><input type="checkbox" name="student_ids[]" value="{{ $student->id }}" class="student-checkbox rounded border-slate-300" data-eligible="{{ $student->is_promotion_eligible ? 'true' : 'false' }}"></td>
                        <td class="p-3 font-mono font-bold">{{ $student->admission_number ?: 'Not assigned' }}</td>
                        <td class="p-3"><span class="font-bold text-slate-900 dark:text-white">{{ $student->name }}</span><span class="block text-[10px] text-slate-500">Roll #{{ $student->effective_roll_number ?? 'Not assigned' }}</span></td>
                        <td class="p-3 font-mono">{{ $student->attendance_percentage === null ? 'No data' : number_format($student->attendance_percentage, 1) . '%' }}</td>
                        <td class="p-3">₹{{ number_format($student->total_pending_dues, 2) }}</td>
                        <td class="p-3 text-right">
                            @if($student->is_promotion_eligible)
                                <span class="rounded-full px-2 py-1 bg-emerald-100 text-emerald-800">Eligible</span>
                            @else
                                <span class="rounded-full px-2 py-1 bg-rose-100 text-rose-800">Not eligible</span>
                                <span class="block text-[10px] mt-1">Account active; review attendance and fee balance separately</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="p-6 text-center">No active enrollments found for this class and session.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($students->isNotEmpty())
        <div class="flex flex-wrap justify-end gap-2 border-t border-slate-100 dark:border-slate-800 pt-3">
            <button type="submit" class="primary-btn" style="padding:.55rem 1rem;font-size:.75rem" onclick="return validatePromotionSelection() && confirm('Promote selected eligible students to the target class and academic session?')">Promote to new session</button>
            <button type="submit" formaction="{{ route('school.promotion.transfer') }}" formmethod="POST" class="px-4 py-2 rounded-xl border border-slate-300 dark:border-slate-700 text-xs font-bold" onclick="return confirm('Transfer selected students within the same academic session?')">Transfer within session</button>
        </div>
        @endif
    </form>
    @endif

    <section class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 shadow-2xs space-y-3">
        <div><h2 class="font-black text-slate-900 dark:text-white">Re-admit a withdrawn student</h2><p class="text-xs text-slate-500 mt-1">Re-admission closes the open withdrawal episode and creates a fresh enrollment record. Earlier attendance, exam and fee history remains unchanged.</p></div>
        <form method="POST" action="{{ route('school.promotion.readmit') }}" class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
            @csrf
            <div><label for="readmit_student_id" class="block font-bold mb-1">Withdrawn student *</label><select id="readmit_student_id" name="student_id" required class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800"><option value="">Choose student</option>@foreach($withdrawnStudents as $student)<option value="{{ $student->id }}">{{ $student->name }} — {{ $student->admission_number ?: 'No admission number' }}</option>@endforeach</select></div>
            <div><label for="readmit_target_class_id" class="block font-bold mb-1">New class / session *</label><select id="readmit_target_class_id" name="target_class_id" required class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800"><option value="">Choose class</option>@foreach($classes as $class)<option value="{{ $class->id }}">{{ $class->display_name }} — {{ $class->academic_year }}</option>@endforeach</select></div>
            <div class="sm:col-span-2 flex justify-end"><button type="submit" class="primary-btn" style="padding:.55rem 1.2rem;font-size:.75rem" onclick="return confirm('Re-admit this student and reactivate their account?')">Re-admit student</button></div>
        </form>
        @if($withdrawnStudents->isEmpty())<p class="text-xs text-slate-500">There are no students with an open withdrawal record.</p>@endif
    </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const selectAll = document.getElementById('select-eligible');
    if (selectAll) {
        selectAll.addEventListener('change', () => {
            document.querySelectorAll('.student-checkbox[data-eligible="true"]').forEach((checkbox) => {
                checkbox.checked = selectAll.checked;
            });
        });
    }
});
function validatePromotionSelection() {
    const selected = Array.from(document.querySelectorAll('.student-checkbox:checked'));
    if (!selected.length) { alert('Select at least one student.'); return false; }
    if (selected.some((checkbox) => checkbox.dataset.eligible !== 'true')) {
        alert('Only active student accounts can be promoted.');
        return false;
    }
    return true;
}
</script>
@endsection
