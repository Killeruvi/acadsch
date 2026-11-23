@extends('layouts.app')

@section('title', 'Student Report Card - ' . $reportCard->student->name)

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8 space-y-6">

    <!-- Top Action Row (Hidden during print) -->
    <div class="flex items-center justify-between no-print">
        <a href="{{ route('school.exams.index', ['term_id' => $reportCard->exam_term_id]) }}" class="text-xs text-slate-500 hover:text-emerald-500 flex items-center space-x-1">
            <span>←</span>
            <span>Back to Examinations Hub</span>
        </a>

        <div class="flex items-center space-x-2">
            <button onclick="window.print()" class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-md shadow-emerald-600/25 flex items-center space-x-1.5 transition">
                <span>🖨️</span>
                <span>Print Official Marksheet</span>
            </button>
        </div>
    </div>

    <!-- Official Report Card Sheet Container -->
    <div id="official-report-card" class="bg-white text-slate-900 p-8 sm:p-12 rounded-3xl shadow-2xl border-4 border-double border-slate-300 relative print:border-none print:shadow-none print:p-0">

        <!-- Top School Crest & Institutional Header (Customized School + PrimeOS Mark) -->
        <div class="text-center pb-6 border-b-2 border-slate-900">
            @if(!empty($school['logo_url']))
            <img src="{{ $school['logo_url'] }}" alt="{{ $school['name'] }}" class="w-16 h-16 rounded-2xl object-contain mx-auto shadow-md mb-2">
            @else
            <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-emerald-600 via-teal-500 to-cyan-500 text-white font-black text-2xl flex items-center justify-center mx-auto shadow-md mb-2">
                {{ $school['short_name'] ?? 'POS' }}
            </div>
            @endif
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight uppercase text-slate-900">
                {{ $school['name'] }}
            </h1>
            <p class="text-xs font-bold text-emerald-800 tracking-widest uppercase mt-0.5">
                Affiliation No: {{ $school['affiliation'] ?: 'Not configured' }} • {{ $school['campus'] ?: 'Campus not configured' }}
            </p>
            <p class="text-[11px] text-slate-500 mt-1">
                {{ $school['address'] ?: 'Address not configured' }} • Phone: {{ $school['phone'] ?: 'Not configured' }}
            </p>

            <div class="mt-4 inline-block px-4 py-1.5 rounded-full bg-slate-900 text-white font-black text-xs uppercase tracking-widest">
                Official Academic Performance &amp; Evaluation Transcript
            </div>
            <div class="text-xs font-bold text-slate-700 mt-1">
                {{ $reportCard->examTerm?->name ?? 'Exam term unavailable' }} • Academic Session: {{ $reportCard->examTerm?->academic_year ?? 'Not recorded' }}
            </div>
        </div>

        <!-- Student Identification Matrix -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 py-6 border-b border-slate-200 text-xs">
            <div>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Student Name:</span>
                <span class="text-sm font-black text-slate-900 block mt-0.5">{{ $reportCard->student->name }}</span>
            </div>
            <div>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Admission Number:</span>
                <span class="text-xs font-mono font-bold text-emerald-800 block mt-0.5">
                    {{ $reportCard->student->admission_number ?: 'Not assigned' }}
                </span>
            </div>
            <div>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Class &amp; Section:</span>
                <span class="text-xs font-bold text-slate-900 block mt-0.5">{{ $reportCard->academicClass?->display_name ?? 'Class unavailable' }}</span>
            </div>
            <div>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Roll Number:</span>
                <span class="text-xs font-mono font-black text-slate-900 block mt-0.5">#{{ $reportCard->student->roll_number ?? '-' }}</span>
            </div>
            <div>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Guardian Name:</span>
                <span class="text-xs font-bold text-slate-700 block mt-0.5">{{ $reportCard->student->guardian_name ?? 'N/A' }}</span>
            </div>
            <div>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Date of Birth:</span>
                <span class="text-xs font-medium text-slate-700 block mt-0.5">
                    {{ $reportCard->student->date_of_birth ? \Carbon\Carbon::parse($reportCard->student->date_of_birth)->format('d M Y') : 'N/A' }}
                </span>
            </div>
            <div>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Attendance Rate:</span>
                <span class="text-xs font-bold text-emerald-700 block mt-0.5">
                    {{ $reportCard->attendance_data_available ? $reportCard->attendance_percentage . '% (' . $reportCard->days_present . '/' . $reportCard->total_working_days . ' days)' : 'Attendance data unavailable for this term' }}
                </span>
            </div>
            <div>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Class Standing:</span>
                <span class="text-xs font-black text-amber-600 block mt-0.5">Rank #{{ $reportCard->class_rank ?? '-' }}</span>
            </div>
        </div>

        <!-- Subject Evaluation Marks Table -->
        <div class="py-6">
            <h3 class="text-xs font-black uppercase tracking-wider text-slate-900 mb-3">
                Part I: Scholastic Performance Breakdown
            </h3>

            <div class="border border-slate-300 rounded-xl overflow-hidden">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-100 uppercase font-black text-[10px] text-slate-600 border-b border-slate-300">
                        <tr>
                            <th class="p-3">Code</th>
                            <th class="p-3">Subject Name</th>
                            <th class="p-3 text-center">Max Marks</th>
                            <th class="p-3 text-center">Pass Marks</th>
                            <th class="p-3 text-center">Marks Obtained</th>
                            <th class="p-3 text-center">Letter Grade</th>
                            <th class="p-3">Performance Remarks</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($marks as $m)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="p-3 font-mono font-bold text-slate-500 uppercase">{{ $m->subject ? $m->subject->code : '-' }}</td>
                            <td class="p-3 font-bold text-slate-900">{{ $m->subject?->name ?? 'Subject not linked' }}</td>
                            <td class="p-3 text-center font-mono">{{ $m->max_marks }}</td>
                            <td class="p-3 text-center font-mono text-slate-500">{{ $m->pass_marks }}</td>
                            <td class="p-3 text-center font-mono font-black text-slate-900">{{ $m->marks_obtained }}</td>
                            <td class="p-3 text-center">
                                <span class="px-2 py-0.5 rounded font-black text-[11px] bg-slate-100 text-slate-800">
                                    {{ $m->letter_grade }}
                                </span>
                            </td>
                            <td class="p-3 text-[11px] text-slate-600 italic">{{ $m->teacher_remarks ?: 'No remarks recorded' }}</td>
                        </tr>
                        @if(Auth::user()->isAdmin())
                        <tr>
                            <td colspan="7" class="px-3 pb-3 bg-slate-50">
                                <form method="POST" action="{{ route('school.exams.marks.re-evaluate', $m->id) }}" class="grid grid-cols-1 sm:grid-cols-3 gap-2 items-end">
                                    @csrf
                                    <label class="text-[10px] font-bold">Revised marks
                                        <input type="number" name="revised_marks" min="0" max="{{ $m->max_marks }}" step="0.01" required class="mt-1 w-full rounded border p-2 text-xs" value="{{ $m->marks_obtained }}">
                                    </label>
                                    <label class="text-[10px] font-bold">Reason for re-evaluation
                                        <input type="text" name="reason" minlength="10" maxlength="1000" required class="mt-1 w-full rounded border p-2 text-xs" placeholder="Document the reason">
                                    </label>
                                    <button class="rounded bg-amber-600 px-3 py-2 text-xs font-bold text-white">Record correction</button>
                                </form>
                                @if($m->revisions->isNotEmpty())
                                <div class="mt-2 text-[10px] text-slate-600">
                                    <strong>Revision history</strong>
                                    @foreach($m->revisions as $revision)
                                        <div>{{ $revision->created_at?->format('d M Y H:i') }} · {{ $revision->old_marks }} → {{ $revision->new_marks }} · {{ $revision->reason }} · {{ $revision->changedBy?->name ?? 'Former user' }}</div>
                                    @endforeach
                                </div>
                                @endif
                            </td>
                        </tr>
                        @endif
                        @empty
                        <tr>
                            <td colspan="7" class="p-6 text-center text-slate-400">No subject marks recorded yet.</td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="bg-slate-100 font-bold border-t-2 border-slate-400">
                        <tr>
                            <td colspan="2" class="p-3 text-slate-900 uppercase font-black">Grand Total / Aggregate</td>
                            <td class="p-3 text-center font-black">{{ $reportCard->total_max_marks }}</td>
                            <td class="p-3 text-center text-slate-400">-</td>
                            <td class="p-3 text-center font-black text-emerald-800 text-sm">{{ $reportCard->total_marks_obtained }}</td>
                            <td class="p-3 text-center font-black text-emerald-800">{{ $reportCard->overall_grade }}</td>
                            <td class="p-3 font-bold text-emerald-800">{{ $reportCard->percentage }}% @if($reportCard->gpa_configured)(GPA: {{ $reportCard->gpa }})@else(GPA not configured)@endif</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Grading Legend Scale -->
        <div class="py-3 px-4 rounded-xl bg-slate-50 border border-slate-200 flex flex-wrap items-center justify-between text-[10px] text-slate-600 gap-2">
            <span class="font-bold text-slate-800 uppercase">Grading policy:</span>
            <span>Refer to the institution's configured grading policy for this academic session.</span>
        </div>

        <!-- Remarks & Assessment Summary -->
        <div class="py-6 border-b border-slate-200 grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
            <div class="p-4 rounded-xl border border-slate-200 bg-slate-50">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Class Teacher's Observations:</span>
                <p class="text-slate-800 italic leading-relaxed">
                    "{{ $reportCard->teacher_remarks ?: 'No teacher remarks recorded.' }}"
                </p>
            </div>
            <div class="p-4 rounded-xl border border-slate-200 bg-slate-50">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Final Evaluation Decision:</span>
                <div class="flex items-center space-x-2 mt-1">
                    <span class="text-xs font-black uppercase px-3 py-1 rounded-lg {{ $reportCard->result_status === 'PASS' ? 'bg-emerald-100 text-emerald-900 border border-emerald-300' : 'bg-rose-100 text-rose-900 border border-rose-300' }}">
                        Result: {{ $reportCard->result_status }}
                    </span>
                    <span class="text-[11px] text-slate-600">Decision recorded in the academic system.</span>
                </div>
            </div>
        </div>

        <!-- Formal Institutional Signatures Section -->
        <div class="pt-10 grid grid-cols-3 gap-6 text-center text-xs">
            <div>
                <div class="h-10 border-b border-slate-400 flex items-end justify-center font-serif italic text-sm font-bold text-slate-700">
                    {{ $reportCard->academicClass?->classTeacher?->name ?? 'Not assigned' }}
                </div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 block mt-1">Class Teacher</span>
            </div>

            <div>
                <div class="h-10 border-b border-slate-400 flex items-end justify-center">
                    <span class="text-[9px] text-slate-500">Seal not configured</span>
                </div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 block mt-1">Institutional Seal</span>
            </div>

            <div>
                <div class="h-10 border-b border-slate-400 flex items-end justify-center font-serif italic text-sm font-bold text-slate-800">
                    {{ $school['principal'] ?: 'Not configured' }}
                </div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 block mt-1">Principal / Head of School</span>
            </div>
        </div>

        <!-- Date of Issue & PrimeOS Mark -->
        <div class="mt-8 pt-3 border-t border-slate-100 flex items-center justify-between text-[10px] text-slate-400">
            <span>Issued on {{ ($reportCard->generated_date ?? $reportCard->created_at)->format('d F Y') }} • Official report card</span>
            <span class="font-extrabold text-emerald-700">⚡ Powered by PrimeOS</span>
        </div>

    </div>

</div>

<style>
@media print {
    body * {
        visibility: hidden;
    }
    #official-report-card, #official-report-card * {
        visibility: visible;
    }
    #official-report-card {
        position: absolute;
        left: 0;
        top: 0;
        width: 100% !important;
        margin: 0 !important;
        padding: 20px !important;
        border: none !important;
        box-shadow: none !important;
    }
    .no-print {
        display: none !important;
    }
}
</style>
@endsection
