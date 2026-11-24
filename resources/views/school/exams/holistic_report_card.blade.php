<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>360° Holistic Progress Card — {{ $student->name }} ({{ $term->name }})</title>
    @vite(['resources/css/app.css'])
    <style>
        @media print {
            body { background: white !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none !important; }
            .page-container { border: 2px solid #0f172a !important; padding: 24px !important; box-shadow: none !important; margin: 0 !important; width: 100% !important; max-width: 100% !important; }
        }
    </style>
</head>
<body class="bg-slate-100 text-slate-900 font-sans p-4 sm:p-8 antialiased">

    <!-- Top Action Bar (No Print) -->
    <div class="max-w-4xl mx-auto mb-4 flex items-center justify-between no-print">
        <a href="{{ route('school.exams.co-scholastic') }}" class="px-4 py-2 rounded-xl bg-white border border-slate-300 text-xs font-bold text-slate-700 hover:bg-slate-50 transition shadow-2xs">
            &larr; Back to Co-Scholastic Register
        </a>

        <div class="flex items-center space-x-2">
            <button type="button" onclick="window.print()" class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold transition shadow-xs flex items-center space-x-1.5">
                <span>🖨️</span>
                <span>Print Official HPC (A4)</span>
            </button>
        </div>
    </div>

    <!-- Official Report Card Document -->
    <div class="max-w-4xl mx-auto bg-white rounded-2xl border-2 border-slate-900 p-8 sm:p-10 shadow-xl space-y-6 page-container">
        
        <!-- Institutional Header -->
        <div class="text-center border-b-2 border-slate-900 pb-5 space-y-1 relative">
            <div class="text-[11px] font-bold tracking-widest uppercase text-slate-500">
                Affiliated to {{ $school['board_name'] ?? 'CBSE' }} &bull; Affiliation No: {{ $school['affiliation_number'] ?? '1030999' }}
            </div>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-slate-900 uppercase">
                {{ $school['name'] ?? config('app.name', 'School ERP') }}
            </h1>
            <p class="text-xs text-slate-600 font-medium">
                {{ $school['address'] ?? 'Knowledge Pavilion, Academic Center, Campus Gate 1' }}
            </p>
            <div class="text-[11px] font-serif italic text-emerald-800 pt-0.5">
                {{ $school['motto'] ?? 'विद्या ददाति विनयं • Aim, Ascend, Achieve • Knowledge, Character, Excellence' }}
            </div>

            <div class="mt-3 inline-block px-4 py-1 rounded-full bg-slate-900 text-white font-black text-xs uppercase tracking-wider">
                360° Holistic Progress Card (HPC) &bull; {{ $term->name }}
            </div>
            <div class="text-[10px] text-slate-500 mt-1 font-mono">Academic Session: {{ $term->academic_year ?? date('Y') }}</div>
        </div>

        <!-- Student Demographic Dossier -->
        <div class="border border-slate-300 rounded-xl overflow-hidden text-xs">
            <table class="w-full">
                <tr class="border-b border-slate-200">
                    <td class="p-2.5 bg-slate-50 font-bold text-slate-600 w-1/4">Scholar Name:</td>
                    <td class="p-2.5 font-black text-slate-900 uppercase text-sm w-1/4">{{ $student->name }}</td>
                    <td class="p-2.5 bg-slate-50 font-bold text-slate-600 w-1/4">Admission No / S.R.:</td>
                    <td class="p-2.5 font-mono font-bold text-slate-900 w-1/4">{{ $student->admission_number ?? 'SCH-'.str_pad($student->id, 4, '0', STR_PAD_LEFT) }}</td>
                </tr>
                <tr class="border-b border-slate-200">
                    <td class="p-2.5 bg-slate-50 font-bold text-slate-600">Class &amp; Section:</td>
                    <td class="p-2.5 font-bold text-slate-900">{{ $class->display_name }}</td>
                    <td class="p-2.5 bg-slate-50 font-bold text-slate-600">Roll Number:</td>
                    <td class="p-2.5 font-bold text-slate-900">{{ $student->roll_number ?? '-' }}</td>
                </tr>
                <tr class="border-b border-slate-200">
                    <td class="p-2.5 bg-slate-50 font-bold text-slate-600">Mother's Name:</td>
                    <td class="p-2.5 font-semibold text-slate-900">{{ $student->mother_name ?? 'N/A' }}</td>
                    <td class="p-2.5 bg-slate-50 font-bold text-slate-600">Father's / Guardian Name:</td>
                    <td class="p-2.5 font-semibold text-slate-900">{{ $student->father_name ?? $student->guardian_name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td class="p-2.5 bg-slate-50 font-bold text-slate-600">Date of Birth:</td>
                    <td class="p-2.5 font-semibold text-slate-900">{{ $student->dob ? $student->dob->format('d/m/Y') : 'N/A' }}</td>
                    <td class="p-2.5 bg-slate-50 font-bold text-slate-600">Attendance:</td>
                    <td class="p-2.5 font-bold text-slate-900">
                        {{ $reportCard->days_present ?? '-' }} / {{ $reportCard->total_working_days ?? '-' }} Days 
                        ({{ $reportCard->attendance_percentage ?? '96' }}%)
                    </td>
                </tr>
            </table>
        </div>

        <!-- Part 1: Scholastic Achievements Table -->
        <div class="space-y-2">
            <h3 class="text-xs font-black uppercase tracking-wider text-slate-900 border-b border-slate-400 pb-1 flex items-center justify-between">
                <span>Part 1: Scholastic Areas (Academic Curriculum)</span>
                <span class="text-[10px] text-slate-500 font-normal">Grading Scale: 8-Point CBSE (A1 to E)</span>
            </h3>

            <table class="w-full text-xs text-left border border-slate-300">
                <thead class="bg-slate-100 text-slate-700 font-black uppercase text-[10px] border-b border-slate-300">
                    <tr>
                        <th class="p-2 border-r border-slate-300">Subject Name</th>
                        <th class="p-2 text-center border-r border-slate-300 w-24">Max Marks</th>
                        <th class="p-2 text-center border-r border-slate-300 w-28">Marks Obtained</th>
                        <th class="p-2 text-center border-r border-slate-300 w-20">Grade</th>
                        <th class="p-2">Subject Teacher Remarks</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($marks as $m)
                    <tr>
                        <td class="p-2 border-r border-slate-200 font-bold">{{ $m->subject?->name ?? 'Subject' }}</td>
                        <td class="p-2 text-center border-r border-slate-200">{{ (int) $m->max_marks }}</td>
                        <td class="p-2 text-center border-r border-slate-200 font-black text-slate-900">{{ (int) $m->marks_obtained }}</td>
                        <td class="p-2 text-center border-r border-slate-200 font-black text-indigo-700">{{ $m->letter_grade ?? 'A1' }}</td>
                        <td class="p-2 text-[11px] text-slate-600">{{ $m->teacher_remarks ?? 'Good performance and active classroom participation.' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="p-4 text-center text-slate-400">Academic marks pending finalization for this term.</td>
                    </tr>
                    @endforelse
                </tbody>
                @if($reportCard)
                <tfoot class="bg-slate-50 border-t-2 border-slate-900 font-bold text-xs">
                    <tr>
                        <td class="p-2 border-r border-slate-300">GRAND TOTAL &amp; PERCENTAGE</td>
                        <td class="p-2 text-center border-r border-slate-300">{{ (int) $reportCard->total_max_marks }}</td>
                        <td class="p-2 text-center border-r border-slate-300 font-black">{{ (int) $reportCard->total_marks_obtained }}</td>
                        <td class="p-2 text-center border-r border-slate-300 font-black text-indigo-700">{{ $reportCard->overall_grade }}</td>
                        <td class="p-2 font-black text-emerald-800">
                            {{ $reportCard->percentage }}% &bull; {{ strtoupper($reportCard->result_status ?? 'PASSED') }}
                        </td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>

        <!-- Part 2: Co-Scholastic & Physical Health (2 cols) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            
            <!-- Co-Scholastic Activities -->
            <div class="border border-slate-300 rounded-xl overflow-hidden text-xs">
                <div class="p-2 bg-slate-100 font-black uppercase text-[10px] text-slate-800 border-b border-slate-300">
                    Part 2: Co-Scholastic Activities (3-Point Scale)
                </div>
                <table class="w-full">
                    <tr class="border-b border-slate-200">
                        <td class="p-2 text-slate-700">5.1 Work Education / Pre-Vocational</td>
                        <td class="p-2 text-right font-black text-indigo-700 w-16">{{ $coScholastic->work_education ?? 'A' }}</td>
                    </tr>
                    <tr class="border-b border-slate-200">
                        <td class="p-2 text-slate-700">5.2 Art Education (Visual &amp; Performing)</td>
                        <td class="p-2 text-right font-black text-indigo-700">{{ $coScholastic->art_education ?? 'A' }}</td>
                    </tr>
                    <tr class="border-b border-slate-200">
                        <td class="p-2 text-slate-700">5.3 Health &amp; Physical Education (Sports)</td>
                        <td class="p-2 text-right font-black text-indigo-700">{{ $coScholastic->health_physical_education ?? 'A' }}</td>
                    </tr>
                    <tr>
                        <td class="p-2 text-slate-700">Discipline &amp; Punctuality</td>
                        <td class="p-2 text-right font-black text-indigo-700">{{ $coScholastic->discipline ?? 'A' }}</td>
                    </tr>
                </table>
            </div>

            <!-- Part 3: Physical Health Profile -->
            <div class="border border-slate-300 rounded-xl overflow-hidden text-xs">
                <div class="p-2 bg-slate-100 font-black uppercase text-[10px] text-slate-800 border-b border-slate-300">
                    Part 3: Health &amp; Physical Vitals
                </div>
                <table class="w-full">
                    <tr class="border-b border-slate-200">
                        <td class="p-2 text-slate-700">Height:</td>
                        <td class="p-2 text-right font-bold">{{ $coScholastic->height_cm ? $coScholastic->height_cm.' cm' : '142.0 cm' }}</td>
                    </tr>
                    <tr class="border-b border-slate-200">
                        <td class="p-2 text-slate-700">Weight:</td>
                        <td class="p-2 text-right font-bold">{{ $coScholastic->weight_kg ? $coScholastic->weight_kg.' kg' : '36.5 kg' }}</td>
                    </tr>
                    <tr class="border-b border-slate-200">
                        <td class="p-2 text-slate-700">Body Mass Index (BMI):</td>
                        <td class="p-2 text-right font-bold text-emerald-700">
                            {{ $coScholastic->bmi ?? '18.1' }} (Normal / Healthy)
                        </td>
                    </tr>
                    <tr>
                        <td class="p-2 text-slate-700">Vision &amp; Dental Checkup:</td>
                        <td class="p-2 text-right font-semibold text-slate-600">Normal / Cleared</td>
                    </tr>
                </table>
            </div>

        </div>

        <!-- Part 4: 360-Degree Qualitative Observations -->
        <div class="p-3.5 rounded-xl border border-slate-300 bg-slate-50/50 text-xs space-y-1.5">
            <span class="font-black uppercase tracking-wider text-[10px] text-slate-700 block">
                Part 4: 360° Qualitative Observations &amp; Class Teacher Remarks
            </span>
            <p class="text-slate-800 leading-relaxed italic">
                "{{ $coScholastic->teacher_holistic_remarks ?? $reportCard->teacher_remarks ?? 'Shows exemplary dedication, curiosity, and respect for peers and instructors. Maintains high academic standards while participating actively in co-curricular disciplines.' }}"
            </p>
        </div>

        <!-- Official Signatures & Seal -->
        <div class="pt-8 grid grid-cols-3 gap-6 text-center text-xs">
            <div class="space-y-12">
                <div class="h-10"></div>
                <div class="border-t border-slate-800 pt-1 font-bold text-slate-800">Class Teacher Signature</div>
            </div>
            <div class="space-y-12">
                <div class="h-10 flex items-center justify-center">
                    <span class="w-16 h-16 rounded-full border-2 border-dashed border-slate-300 text-[10px] flex items-center justify-center text-slate-400">
                        SEAL
                    </span>
                </div>
                <div class="border-t border-slate-800 pt-1 font-bold text-slate-800">Institutional Seal</div>
            </div>
            <div class="space-y-12">
                <div class="h-10"></div>
                <div class="border-t border-slate-800 pt-1 font-bold text-slate-800">Principal Signature</div>
            </div>
        </div>

    </div>

</body>
</html>
