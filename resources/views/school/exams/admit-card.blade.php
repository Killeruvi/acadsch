@extends('layouts.app')

@section('title', 'Examination Admit Card - ' . $student->name)

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8 space-y-6">

    <!-- Top Action Row (Hidden during print) -->
    <div class="flex items-center justify-between no-print">
        <a href="{{ route('school.exams.index', ['term_id' => $term->id]) }}" class="text-xs text-slate-500 hover:text-emerald-500 flex items-center space-x-1">
            <span>←</span>
            <span>Back to Examinations Hub</span>
        </a>

        <div class="flex items-center space-x-2">
            <button onclick="window.print()" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold shadow-md shadow-indigo-600/25 flex items-center space-x-1.5 transition">
                <span>🖨️</span>
                <span>Print Official Admit Card</span>
            </button>
        </div>
    </div>

    <!-- Official Admit Card Sheet Container -->
    <div id="official-admit-card" class="bg-white text-slate-900 p-8 sm:p-12 rounded-3xl shadow-2xl border-4 border-double border-slate-300 relative print:border-none print:shadow-none print:p-0">

        <!-- Top School Crest & Institutional Header -->
        <div class="text-center pb-6 border-b-2 border-slate-900">
            @if(!empty($school['logo_url']))
            <img src="{{ $school['logo_url'] }}" alt="{{ $school['name'] }}" class="w-16 h-16 rounded-2xl object-contain mx-auto shadow-md mb-2">
            @else
            <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-indigo-600 via-blue-500 to-cyan-500 text-white font-black text-2xl flex items-center justify-center mx-auto shadow-md mb-2">
                {{ $school['short_name'] ?? 'POS' }}
            </div>
            @endif
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight uppercase text-slate-900">
                {{ $school['name'] }}
            </h1>
            <p class="text-xs font-bold text-indigo-800 tracking-widest uppercase mt-0.5">
                Affiliation No: {{ $school['affiliation'] ?: 'Not configured' }} • {{ $school['campus'] ?: 'Main Campus' }}
            </p>
            <p class="text-[11px] text-slate-500 mt-1">
                {{ $school['address'] ?: 'Campus Address' }} • Phone: {{ $school['phone'] ?: 'N/A' }}
            </p>

            <div class="mt-4 inline-block px-5 py-1.5 rounded-full bg-slate-900 text-white font-black text-xs uppercase tracking-widest">
                Official Examination Admit Card / Hall Ticket
            </div>
            <div class="text-xs font-bold text-slate-700 mt-1">
                {{ $term->name }} • Academic Session: {{ $term->academic_year }}
            </div>
        </div>

        <!-- Candidate Identification Matrix -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 py-6 border-b border-slate-200 text-xs">
            <div>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Candidate Name:</span>
                <span class="text-sm font-black text-slate-900 block mt-0.5">{{ $student->name }}</span>
            </div>
            <div>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Admission Number:</span>
                <span class="text-xs font-mono font-bold text-indigo-800 block mt-0.5">
                    {{ $student->admission_number ?: 'Not assigned' }}
                </span>
            </div>
            <div>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Class &amp; Section:</span>
                <span class="text-xs font-bold text-slate-900 block mt-0.5">{{ $class->display_name }}</span>
            </div>
            <div>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Roll Number:</span>
                <span class="text-xs font-mono font-black text-slate-900 block mt-0.5">#{{ $student->roll_number ?? '-' }}</span>
            </div>
            <div>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Guardian Name:</span>
                <span class="text-xs font-bold text-slate-700 block mt-0.5">{{ $student->guardian_name ?? 'N/A' }}</span>
            </div>
            <div>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Date of Birth:</span>
                <span class="text-xs font-medium text-slate-700 block mt-0.5">
                    {{ $student->date_of_birth ? \Carbon\Carbon::parse($student->date_of_birth)->format('d M Y') : 'N/A' }}
                </span>
            </div>
            <div>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Term Duration:</span>
                <span class="text-xs font-bold text-slate-700 block mt-0.5">
                    {{ \Carbon\Carbon::parse($term->start_date)->format('d M') }} — {{ \Carbon\Carbon::parse($term->end_date)->format('d M Y') }}
                </span>
            </div>
            <div>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Candidate Status:</span>
                <span class="text-xs font-bold text-emerald-700 block mt-0.5">Eligible / Registered</span>
            </div>
        </div>

        <!-- Examination Datesheet Table -->
        <div class="py-6">
            <h3 class="text-xs font-black uppercase tracking-wider text-slate-900 mb-3">
                Examination Schedule &amp; Timetable
            </h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border border-slate-300 rounded-xl overflow-hidden">
                    <thead class="bg-slate-100 text-slate-700 uppercase font-black text-[10px] tracking-wider border-b border-slate-300">
                        <tr>
                            <th class="p-3 border-r border-slate-300">Date &amp; Day</th>
                            <th class="p-3 border-r border-slate-300">Timings</th>
                            <th class="p-3 border-r border-slate-300">Subject</th>
                            <th class="p-3 border-r border-slate-300">Room / Hall</th>
                            <th class="p-3 border-r border-slate-300 text-center">Max Marks</th>
                            <th class="p-3 text-center">Invigilator Sign</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($schedules as $sched)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="p-3 font-bold border-r border-slate-200 whitespace-nowrap">
                                {{ \Carbon\Carbon::parse($sched->exam_date)->format('d M Y (D)') }}
                            </td>
                            <td class="p-3 font-mono border-r border-slate-200 whitespace-nowrap">
                                {{ \Carbon\Carbon::parse($sched->start_time)->format('h:i A') }} — {{ \Carbon\Carbon::parse($sched->end_time)->format('h:i A') }}
                            </td>
                            <td class="p-3 font-bold text-slate-900 border-r border-slate-200">
                                {{ $sched->subject?->name ?? 'Subject' }}
                                <span class="text-[10px] font-mono text-slate-500 block">Code: {{ $sched->subject?->code ?? '-' }}</span>
                            </td>
                            <td class="p-3 border-r border-slate-200 font-medium">
                                {{ $sched->room_number ?: 'Exam Hall 1' }}
                            </td>
                            <td class="p-3 text-center font-mono font-bold border-r border-slate-200">
                                {{ $sched->max_marks }}
                            </td>
                            <td class="p-3 text-center">
                                <span class="inline-block w-20 border-b border-slate-400"></span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="p-6 text-center text-slate-500 italic">
                                No exam schedules posted for this term and class yet.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Security Verification Badge & Rules -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 py-4 border-t border-b border-slate-200 text-xs">
            <div class="md:col-span-2 space-y-2">
                <h4 class="font-bold text-slate-900 uppercase text-[11px] tracking-wider">Important Instructions for Candidates:</h4>
                <ul class="list-decimal pl-4 space-y-1 text-[11px] text-slate-600">
                    <li>Candidates must bring this original Admit Card and School Identity Card to every examination session.</li>
                    <li>Candidates must report to the examination hall at least 15 minutes prior to scheduled start time.</li>
                    <li>Electronic devices, smart watches, mobile phones, or bags are strictly prohibited inside the hall.</li>
                    <li>Borrowing stationery during the examination is strictly forbidden.</li>
                    <li>No candidate may leave the examination hall before half time.</li>
                </ul>
            </div>

            <!-- Verification QR Box -->
            <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200 text-center flex flex-col items-center justify-center space-y-2">
                <div class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Tamper-Proof Verification</div>
                <div class="w-24 h-24 bg-white border border-slate-300 rounded-xl flex items-center justify-center shadow-inner p-1">
                    <svg class="w-20 h-20 text-slate-900" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <rect x="3" y="3" width="7" height="7" rx="1"/>
                        <rect x="14" y="3" width="7" height="7" rx="1"/>
                        <rect x="3" y="14" width="7" height="7" rx="1"/>
                        <rect x="6" y="6" width="1" height="1"/>
                        <rect x="17" y="6" width="1" height="1"/>
                        <rect x="6" y="17" width="1" height="1"/>
                        <path d="M14 14h2v2h-2zM18 14h3v3h-3zM14 18h3v3h-3zM19 19h2v2h-2z"/>
                    </svg>
                </div>
                <a href="{{ route('school.exams.admit-card.verify', ['token' => $token]) }}" target="_blank" class="text-[10px] font-mono text-indigo-600 hover:underline break-all block max-w-full truncate">
                    Verify Authenticity
                </a>
                <span class="text-[9px] text-emerald-700 font-bold bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                    Cryptographically Signed
                </span>
            </div>
        </div>

        <!-- Official Signatures Row -->
        <div class="grid grid-cols-3 gap-6 pt-12 pb-4 text-center text-xs">
            <div>
                <div class="border-b border-slate-400 pb-1 mb-2"></div>
                <span class="font-bold text-slate-700 block">Candidate's Signature</span>
                <span class="text-[10px] text-slate-400 block">To be signed in exam hall</span>
            </div>
            <div>
                <div class="border-b border-slate-400 pb-1 mb-2"></div>
                <span class="font-bold text-slate-700 block">Class Teacher</span>
                <span class="text-[10px] text-slate-400 block">{{ $class->classTeacher?->name ?? 'Verified' }}</span>
            </div>
            <div>
                <div class="border-b border-slate-400 pb-1 mb-2"></div>
                <span class="font-bold text-slate-700 block">Principal / Controller of Exams</span>
                <span class="text-[10px] text-slate-400 block">Official Seal &amp; Signature</span>
            </div>
        </div>

    </div>

</div>

<style>
@media print {
    .no-print { display: none !important; }
    body { background: white !important; color: black !important; margin: 0; padding: 0; }
    #official-admit-card { border: none !important; box-shadow: none !important; padding: 0 !important; }
}
</style>
@endsection
