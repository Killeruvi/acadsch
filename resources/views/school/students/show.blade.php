@extends('layouts.app')

@section('title', $student->name . ' - Student Dossier')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-4 space-y-3.5">

    <!-- Breadcrumb -->
    <div class="flex items-center space-x-2 text-[11px] text-slate-500">
        <a href="{{ route('school.students.index') }}" class="hover:text-emerald-500">Students</a>
        <span>/</span>
        <span class="text-slate-900 dark:text-white font-bold">{{ $student->name }}</span>
    </div>

    @if($errors->has('account_reset'))
    <div class="p-3 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-xs text-rose-700 dark:text-rose-300" role="alert">
        {{ $errors->first('account_reset') }}
    </div>
    @endif

    @if(session('account_reset_result'))
    @php $res = session('account_reset_result'); @endphp
    <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800" role="status">
        <p class="text-xs font-bold text-emerald-800 dark:text-emerald-300">{{ $res['message'] ?? 'Password reset instructions were processed.' }}</p>
        @if(!empty($res['email']))
            <p class="text-xs text-emerald-700 dark:text-emerald-400 mt-1">Account email: {{ $res['email'] }}</p>
        @endif
    </div>
    @endif
    <!-- Student Header Dossier Card (Compact, structured, scannable) -->
    <div class="bg-white dark:bg-slate-900 p-3.5 sm:p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-2xs flex flex-col md:flex-row md:items-center justify-between gap-3">
        <div class="flex items-center space-x-3">
            <div class="w-12 h-12 rounded-xl bg-emerald-600 text-white font-black text-lg flex items-center justify-center font-mono">
                {{ strtoupper(substr($student->name, 0, 2)) }}
            </div>
            <div>
                <div class="flex items-center space-x-2">
                    <h1 class="text-lg sm:text-xl font-black text-slate-900 dark:text-white">{{ $student->name }}</h1>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                        {{ $student->currentClass ? $student->currentClass->display_name : 'Unassigned Class' }}
                    </span>
                </div>
                <div class="flex items-center flex-wrap gap-1.5 mt-1 text-[11px]">
                    <span class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 font-mono">
                        Admission: <strong class="text-emerald-600 dark:text-emerald-400 font-bold">{{ $student->admission_number ?: 'Not assigned' }}</strong>
                    </span>
                    <span class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 font-mono">
                        Roll: <strong class="text-slate-900 dark:text-white font-bold">#{{ $student->roll_number ?? '-' }}</strong>
                    </span>
                    <span class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400">
                        DOB: <strong class="text-slate-800 dark:text-slate-200">{{ $student->date_of_birth ? \Carbon\Carbon::parse($student->date_of_birth)->format('d M Y') : 'N/A' }}</strong>
                    </span>
                    <span class="px-1.5 py-0.5 rounded bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 font-bold">
                        Blood: {{ $student->blood_group ?: 'Not recorded' }}
                    </span>
                </div>
            </div>
        </div>

        <div class="flex items-center flex-wrap gap-2">
            <a href="{{ route('school.students.id-card', $student->id) }}" 
               class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold transition shadow-2xs flex items-center space-x-1.5">
                <span>🪪</span>
                <span>Print Official ID Card</span>
            </a>
            @if(Auth::user()->isAdmin() || Auth::user()->isTeacher())
            <a href="{{ route('school.students.edit', $student->id) }}" 
               class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 text-xs font-bold transition">
                Edit Profile
            </a>
            @endif
            @if(Auth::user()->isAdmin())
            <button type="button" onclick="document.getElementById('reset-account-modal').classList.remove('hidden')"
                    class="px-3 py-1.5 rounded-lg bg-amber-50 hover:bg-amber-100 dark:bg-amber-950/60 dark:hover:bg-amber-900/60 text-amber-700 dark:text-amber-300 border border-amber-300 dark:border-amber-800 text-xs font-bold transition flex items-center space-x-1">
                <span>🔑</span>
                <span>Reset Password</span>
            </button>
            @endif
        </div>
    </div>

    @if(isset($admissionDocuments) && $admissionDocuments->isNotEmpty())
    <section class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 space-y-3">
        <h2 class="font-bold text-sm text-slate-900 dark:text-white">Admission Documents</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
            @foreach($admissionDocuments as $document)
                <a href="{{ route('school.students.admission-documents.download', $document->id) }}" class="flex items-center justify-between gap-3 rounded-lg border border-slate-200 dark:border-slate-700 p-3 text-xs hover:border-emerald-500">
                    <span><strong>{{ ucwords(str_replace('_', ' ', $document->document_type)) }}</strong><span class="block text-slate-500">{{ $document->original_filename }}</span></span>
                    <span class="text-emerald-600 font-bold">Download</span>
                </a>
            @endforeach
        </div>
    </section>
    @endif

    <!-- 4-Column Info Overview (Demographics, Guardian, Previous School, Placement) -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
        <!-- Attendance Performance -->
        <div class="bg-white dark:bg-slate-900 p-3.5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-2xs">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Attendance Summary</span>
            <div class="flex items-baseline space-x-2 mt-1">
                <span class="text-2xl font-black text-slate-900 dark:text-white font-mono">{{ $attendanceRate !== null ? number_format($attendanceRate, 1) . '%' : 'No records' }}</span>
                <span class="text-xs text-slate-500">Overall</span>
            </div>
            <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-2 mt-2 overflow-hidden">
                <div class="bg-emerald-500 h-2 rounded-full" style="width: {{ $attendanceRate !== null ? $attendanceRate : 0 }}%"></div>
            </div>
            <span class="text-[11px] text-slate-500 mt-2 block">
                {{ $presentCount }} attended · {{ $absentCount }} absent · {{ $excusedCount }} excused · {{ $totalSessions }} counted sessions
            </span>
        </div>

        <!-- Guardian & Contact -->
        <div class="bg-white dark:bg-slate-900 p-3.5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-2xs">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Guardian / Parent</span>
            <div class="text-sm font-bold text-slate-900 dark:text-white mt-1 truncate">{{ $student->guardian_name ?? 'Not Listed' }}</div>
            <p class="text-xs text-slate-500 mt-1">📞 {{ $student->guardian_phone ?: 'Not provided' }}</p>
            <p class="text-xs text-slate-500 truncate">✉️ {{ $student->guardian_email ?: 'Not provided' }}</p>
            <span class="text-[10px] text-emerald-600 dark:text-emerald-400 mt-1 block">
                Parent Account: {{ $student->parent ? 'Linked' : 'Not linked' }}
            </span>
        </div>

        <!-- Previous School Details (Rule 7) -->
        <div class="bg-white dark:bg-slate-900 p-3.5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-2xs">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Previous School Record</span>
            <div class="text-sm font-bold text-slate-900 dark:text-white mt-1 truncate" title="{{ $student->previous_school_name ?: 'Not recorded' }}">
                {{ $student->previous_school_name ?: 'Not recorded' }}
            </div>
            <p class="text-xs text-slate-500 mt-1">Board: {{ $student->previous_school_board ?? 'N/A' }} • {{ $student->previous_school_year ?? 'N/A' }}</p>
            <p class="text-xs text-slate-500 font-mono">TC #: {{ $student->previous_school_tc_number ?? 'N/A' }}</p>
            @if($student->previous_school_remarks)
            <span class="text-[10px] text-slate-400 mt-1 block italic truncate" title="{{ $student->previous_school_remarks }}">
                &ldquo;{{ $student->previous_school_remarks }}&rdquo;
            </span>
            @endif
        </div>

        <!-- Academic Placement -->
        <div class="bg-white dark:bg-slate-900 p-3.5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-2xs">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Class &amp; Homeroom</span>
            <div class="text-sm font-bold text-slate-900 dark:text-white mt-1">
                {{ $student->currentClass ? $student->currentClass->display_name : 'No Class' }}
            </div>
            <p class="text-xs text-slate-500 mt-1">Class Teacher: {{ $student->currentClass?->classTeacher?->name ?? 'Unassigned' }}</p>
            <p class="text-xs text-slate-500">Room: {{ $student->currentClass?->room_number ?: 'Not configured' }}</p>
            <span class="text-[10px] text-slate-400 mt-1 block">Academic Year: {{ $student->currentClass?->academic_year ?: 'Not configured' }}</span>
        </div>
    </div>

    <!-- Academic Enrollment History -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 sm:p-5 shadow-xs space-y-3">
        <div class="flex items-center justify-between gap-3">
            <h2 class="text-base font-black text-slate-900 dark:text-white">Academic Enrollment History</h2>
            <span class="text-[11px] text-slate-500">{{ $student->classEnrollments->count() }} records</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600 dark:text-slate-400">
                <thead class="bg-slate-50 dark:bg-slate-800/60 uppercase text-[10px] font-black text-slate-500">
                    <tr>
                        <th class="p-3">Class</th>
                        <th class="p-3">Academic Year</th>
                        <th class="p-3">Roll No.</th>
                        <th class="p-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($student->classEnrollments as $enrollment)
                    <tr>
                        <td class="p-3 font-semibold text-slate-800 dark:text-slate-200">{{ $enrollment->academicClass?->display_name ?? 'Class record unavailable' }}</td>
                        <td class="p-3">{{ $enrollment->academic_year ?: ($enrollment->academicClass?->academic_year ?: 'Not recorded') }}</td>
                        <td class="p-3 font-mono">{{ $enrollment->roll_number ?? '—' }}</td>
                        <td class="p-3">{{ ucfirst($enrollment->status ?: 'unknown') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="p-5 text-center text-slate-400">No enrollment history recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Transport Assignment Card (Direct Driver/Bus Linking) -->
    @if($student->activeTransportAssignment)
    @php
        $trn = $student->activeTransportAssignment;
        $tRoute = $trn->route;
        $tVehicle = $trn->vehicle;
        $tDriver = $tVehicle?->driver;
    @endphp
    <div class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-2xs flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="flex items-center space-x-3">
            <span class="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-500 flex items-center justify-center text-lg">
                🚌
            </span>
            <div>
                <div class="flex items-center space-x-2">
                    <span class="text-xs font-black text-slate-900 dark:text-white">{{ $tRoute?->route_title ?? 'Route details unavailable' }}</span>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">Active Rider</span>
                </div>
                <p class="text-xs text-slate-500 mt-0.5">
                    Stop: <span class="font-bold text-slate-700 dark:text-slate-300">{{ $trn->stop?->stop_name ?? 'Stop not assigned' }}</span>
                    @if($trn->stop?->pickup_time) (Pickup: {{ $trn->stop->pickup_time }}) @endif
                    • Bus #: <span class="font-mono font-bold text-blue-600 dark:text-blue-400">{{ $tVehicle?->vehicle_number ?? 'Vehicle not assigned' }}</span>
                </p>
            </div>
        </div>
        <div class="flex items-center space-x-4 text-xs">
            <div>
                <span class="text-[10px] uppercase font-bold text-slate-400 block">Driver Contact</span>
                <span class="font-bold text-slate-800 dark:text-slate-200">{{ $tDriver?->name ?? $tVehicle?->driver_name ?? 'Driver not assigned' }}</span>
                <span class="text-slate-500 font-mono ml-1">📞 {{ $tDriver?->contact_number ?? $tVehicle?->driver_phone ?? 'Not available' }}</span>
            </div>
            <div class="text-right">
                <span class="text-[10px] uppercase font-bold text-slate-400 block">Monthly Bus Fare</span>
                <span class="font-bold font-mono text-emerald-600 dark:text-emerald-400">₹{{ number_format($trn->effective_fare, 2) }}</span>
            </div>
        </div>
    </div>
    @endif

    <!-- Official Report Cards & Marksheets Section -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 shadow-xs space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-base font-black text-slate-900 dark:text-white flex items-center space-x-2">
                    <span>📜</span>
                    <span>Official Examination Marksheets &amp; Report Cards</span>
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">Formal report cards generated by class teachers for term and annual examinations.</p>
            </div>
        </div>

        <div class="divide-y divide-slate-100 dark:divide-slate-800">
            @forelse($student->reportCards as $rc)
            <div class="py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-purple-50 text-purple-700 dark:bg-purple-950 dark:text-purple-400 border border-purple-200 dark:border-purple-800 mb-1">
                        {{ $rc->examTerm?->academic_year ?? 'Academic year unavailable' }}
                    </span>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">
                        {{ $rc->examTerm?->name ?? 'Exam term unavailable' }}
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5 font-mono">
                        Score: <span class="font-bold text-slate-800 dark:text-slate-200">{{ $rc->total_marks_obtained }} / {{ $rc->total_max_marks }}</span> • 
                        Percentage: <span class="font-bold text-emerald-600 dark:text-emerald-400">{{ $rc->percentage }}%</span> • 
                        Grade: <span class="font-bold text-purple-600 dark:text-purple-400">{{ $rc->overall_grade }}</span> • 
                        Class Rank: <span class="font-bold text-amber-500">#{{ $rc->class_rank ?? '-' }}</span> • 
                        Status: <span class="font-bold {{ $rc->result_status === 'PASS' ? 'text-emerald-600' : 'text-rose-600' }}">{{ $rc->result_status }}</span>
                    </p>
                </div>

                <div>
                    <a href="{{ route('school.report-cards.show', $rc->id) }}" 
                       class="px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-sm flex items-center space-x-1.5 transition">
                        <span>🖨️</span>
                        <span>View / Print Official Marksheet</span>
                    </a>
                </div>
            </div>
            @empty
            <div class="p-8 text-center text-slate-400 text-xs">
                No official report cards generated for this student yet. Marks will appear once examination terms are graded.
            </div>
            @endforelse
        </div>
    </div>

    <!-- School Fee History -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 shadow-xs space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-base font-black text-slate-900 dark:text-white flex items-center space-x-2">
                <span>💳</span>
                <span>School Fee Ledger</span>
            </h2>
            <a href="{{ route('school.fees.index') }}" class="text-xs font-bold text-emerald-600 hover:underline">Fee Department</a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
            <div class="rounded-xl bg-slate-50 dark:bg-slate-800/60 p-3">
                <span class="block text-[10px] uppercase font-bold text-slate-500">Total invoiced</span>
                <strong class="text-sm text-slate-900 dark:text-white">₹{{ number_format($totalInvoiced, 2) }}</strong>
            </div>
            <div class="rounded-xl bg-slate-50 dark:bg-slate-800/60 p-3">
                <span class="block text-[10px] uppercase font-bold text-slate-500">Total paid</span>
                <strong class="text-sm text-emerald-700 dark:text-emerald-400">₹{{ number_format($totalPaid, 2) }}</strong>
            </div>
            <div class="rounded-xl bg-slate-50 dark:bg-slate-800/60 p-3">
                <span class="block text-[10px] uppercase font-bold text-slate-500">Outstanding balance</span>
                <strong class="text-sm text-amber-700 dark:text-amber-400">₹{{ number_format($totalOutstanding, 2) }}</strong>
            </div>
        </div>
        <p class="text-[10px] text-slate-500">Showing the 20 most recent invoices below. Summary totals include all invoices.</p>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600 dark:text-slate-400">
                <thead class="bg-slate-50 dark:bg-slate-800/60 uppercase font-black text-[10px] text-slate-500">
                    <tr>
                        <th class="p-3">Invoice No</th>
                        <th class="p-3">Title / Month</th>
                        <th class="p-3">Total Amount</th>
                        <th class="p-3">Paid Amount</th>
                        <th class="p-3">Due Date</th>
                        <th class="p-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($student->feeInvoices as $inv)
                    <tr>
                        <td class="p-3 font-mono text-emerald-600 dark:text-emerald-400 font-bold">{{ $inv->invoice_number }}</td>
                        <td class="p-3 font-bold text-slate-800 dark:text-slate-200">{{ $inv->title }}</td>
                        <td class="p-3 font-bold text-slate-900 dark:text-white">₹{{ number_format($inv->total_due, 2) }}</td>
                        <td class="p-3 text-slate-600 dark:text-slate-400">₹{{ number_format($inv->paid_amount, 2) }}</td>
                        <td class="p-3 text-slate-500">{{ $inv->due_date ? \Carbon\Carbon::parse($inv->due_date)->format('d M Y') : 'N/A' }}</td>
                        <td class="p-3">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $inv->status === 'paid' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300' }}">
                                {{ strtoupper($inv->status) }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="p-6 text-center text-slate-400">No fee invoices on file.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Admin Password Reset Modal -->
@if(Auth::user()->isAdmin())
<div id="reset-account-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs hidden p-4">
    <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200 dark:border-slate-800 max-w-md w-full shadow-2xl">
        <div class="flex items-center justify-between mb-4 pb-2 border-b border-slate-100 dark:border-slate-800">
            <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <span>🔑</span> Reset Student Credentials
            </h3>
            <button onclick="document.getElementById('reset-account-modal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 dark:hover:text-white">✕</button>
        </div>
        <form method="POST" action="{{ route('school.users.reset-account', $student->id) }}" class="space-y-4">
            @csrf
            <p class="text-xs text-slate-600 dark:text-slate-300">
                Send a single-use password reset link to <strong>{{ $student->email ?: 'the student account' }}</strong>. The current password will not be displayed or replaced unless the account holder completes the reset.
            </p>
            @if(empty($student->email))
                <p class="text-xs text-rose-600">This account has no email address. Add and verify an email address before requesting a reset link.</p>
            @endif
            <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                <button type="button" onclick="document.getElementById('reset-account-modal').classList.add('hidden')"
                        class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-bold">
                    Cancel
                </button>
                <button type="submit" {{ empty($student->email) ? 'disabled' : '' }} class="px-4 py-2 rounded-xl bg-amber-600 hover:bg-amber-500 disabled:opacity-50 disabled:cursor-not-allowed text-white text-xs font-bold shadow-xs">
                    Send Reset Link
                </button>
            </div>
        </form>
    </div>
</div>
@endif
@endsection
