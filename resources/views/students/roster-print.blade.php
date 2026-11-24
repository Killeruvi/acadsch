<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Official Student Class Register &bull; {{ $school["name"] ?? config('app.name', 'School ERP') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; color: black !important; font-size: 11px; }
            table { page-break-inside: auto; }
            tr { page-break-inside: avoid; page-break-after: auto; }
        }
    </style>
</head>
<body class="bg-slate-100 text-slate-900 p-6 font-sans">

    <!-- Top Action Bar for Screen -->
    <div class="max-w-5xl mx-auto mb-6 flex items-center justify-between no-print bg-white p-4 rounded-2xl shadow-sm border border-slate-200">
        <div>
            <h2 class="text-sm font-bold text-slate-900">Institutional Print Sheet</h2>
            <p class="text-xs text-slate-500">Ready to print or save as PDF for physical attendance and classroom records.</p>
        </div>
        <div class="flex items-center space-x-2">
            <button onclick="window.print()" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-xl shadow-sm">
                🖨️ Print Sheet / Save PDF
            </button>
            <button onclick="window.close()" class="px-3 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-bold rounded-xl">
                Close
            </button>
        </div>
    </div>

    <!-- Printable Paper Sheet -->
    <div class="max-w-5xl mx-auto bg-white p-8 rounded-2xl shadow-sm border border-slate-200 print:border-none print:shadow-none print:p-0">
        
        <!-- Header -->
        <div class="flex items-center justify-between pb-6 border-b-2 border-slate-900 mb-6">
            <div class="flex items-center space-x-4">
                <div class="w-14 h-14 bg-emerald-700 text-white font-black text-2xl flex items-center justify-center rounded-xl">
                    PA
                </div>
                <div>
                    <h1 class="text-2xl font-black uppercase tracking-tight text-slate-900">{{ $school["name"] ?? config('app.name', 'School ERP') }}</h1>
                    <p class="text-xs text-slate-600 font-semibold">Center for Academic Excellence &bull; Aim, Ascend, Achieve</p>
                    <p class="text-xs text-slate-500">Official Student Roster &amp; Roll Call Register &bull; Academic Year 2026-2027</p>
                </div>
            </div>

            <div class="text-right text-xs space-y-0.5">
                <div><strong class="text-slate-800">Target Class:</strong> {{ $class ? $class->name : 'All Classes / Campus-wide' }}</div>
                <div><strong class="text-slate-800">Total Enrolled:</strong> {{ $students->count() }} Students</div>
                <div><strong class="text-slate-800">Generated On:</strong> {{ now()->format('d M Y, h:i A') }}</div>
            </div>
        </div>

        <!-- Table -->
        <table class="w-full text-left text-xs border-collapse">
            <thead>
                <tr class="border-b-2 border-slate-800 bg-slate-50 font-black uppercase text-[10px] tracking-wider text-slate-700">
                    <th class="py-2.5 px-2 w-12 text-center border border-slate-300">Roll #</th>
                    <th class="py-2.5 px-2 border border-slate-300">Student ID</th>
                    <th class="py-2.5 px-2 border border-slate-300">Student Full Name</th>
                    <th class="py-2.5 px-2 border border-slate-300">Class &amp; Batch</th>
                    <th class="py-2.5 px-2 border border-slate-300">Student Phone</th>
                    <th class="py-2.5 px-2 border border-slate-300">Guardian Contact</th>
                    <th class="py-2.5 px-2 w-20 text-center border border-slate-300">Attendance</th>
                    <th class="py-2.5 px-2 w-28 text-center border border-slate-300">Teacher Signature</th>
                </tr>
            </thead>
            <tbody>
                @forelse($students as $idx => $s)
                @php
                    $enrollment = $s->classEnrollments->first();
                    $academicClass = $enrollment?->academicClass ?? $s->batch?->academicClass;
                    $rollNumber = $enrollment?->roll_number ?? ($idx + 1);
                @endphp
                <tr class="border-b border-slate-200">
                    <td class="py-2 px-2 text-center font-bold font-mono border border-slate-300">
                        {{ $rollNumber }}
                    </td>
                    <td class="py-2 px-2 font-mono text-[11px] font-semibold border border-slate-300">
                        {{ $s->student_id_number }}
                    </td>
                    <td class="py-2 px-2 font-bold text-slate-900 border border-slate-300">
                        {{ $s->name }}
                    </td>
                    <td class="py-2 px-2 border border-slate-300">
                        {{ $academicClass?->name ?? '-' }} ({{ $s->batch?->name ?? 'Standard' }})
                    </td>
                    <td class="py-2 px-2 font-mono text-[11px] border border-slate-300">
                        {{ $s->phone ?? '-' }}
                    </td>
                    <td class="py-2 px-2 border border-slate-300">
                        <span class="font-semibold">{{ $s->guardian_name ?? '-' }}</span>
                        @if($s->guardian_phone)
                        <span class="block text-[10px] text-slate-600 font-mono">{{ $s->guardian_phone }}</span>
                        @endif
                    </td>
                    <td class="py-2 px-2 text-center border border-slate-300">
                        <div class="w-4 h-4 border border-slate-400 mx-auto rounded-xs"></div>
                    </td>
                    <td class="py-2 px-2 text-center border border-slate-300 text-slate-300 text-[10px]">
                        ________________
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="py-6 text-center text-slate-500 italic">
                        No students enrolled in this selection.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Footer -->
        <div class="mt-8 pt-4 border-t border-slate-300 flex items-center justify-between text-xs text-slate-500">
            <div>{{ $school["name"] ?? config('app.name', 'School ERP') }} Institutional Management &bull; confidential records</div>
            <div>Authorized Faculty Signature: ___________________________</div>
        </div>
    </div>

</body>
</html>
