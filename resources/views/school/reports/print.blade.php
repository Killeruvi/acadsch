<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $reportData['title'] }} • {{ $school['name'] ?? 'Prime Arc Academy' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { 
                background: #ffffff !important; 
                color: #000000 !important; 
                padding: 0 !important;
                margin: 0 !important;
            }
            .page-break { page-break-after: always; }
            table { page-break-inside: auto; }
            tr { page-break-inside: avoid; page-break-after: auto; }
            thead { display: table-header-group; }
            tfoot { display: table-footer-group; }
        }
    </style>
</head>
<body class="bg-slate-100 text-slate-900 font-sans p-4 sm:p-8 min-h-screen">

    <!-- Action Bar (Hidden in Print) -->
    <div class="max-w-5xl mx-auto mb-6 flex items-center justify-between no-print">
        <a href="{{ route('school.reports.index', $filters) }}" 
           class="px-4 py-2 rounded-xl bg-white text-slate-700 text-xs font-bold border border-slate-300 shadow-sm hover:bg-slate-50 transition flex items-center space-x-1">
            <span>←</span>
            <span>Back to Reports Hub</span>
        </a>

        <div class="flex items-center space-x-2">
            <button onclick="window.print()" 
                    class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-md shadow-emerald-600/25 flex items-center space-x-2 transition cursor-pointer">
                <span>🖨️</span>
                <span>Print Document / Save as PDF</span>
            </button>
        </div>
    </div>

    <!-- Official Document Sheet -->
    <div class="max-w-5xl mx-auto bg-white p-8 sm:p-12 rounded-3xl shadow-xl border border-slate-200 print:border-none print:shadow-none print:p-0 print:max-w-full">
        
        <!-- 1. INSTITUTIONAL LETTERHEAD -->
        <div class="border-b-2 border-slate-900 pb-5 mb-6 text-center">
            @if(!empty($school['logo_url']))
            <img src="{{ $school['logo_url'] }}" alt="{{ $school['name'] }}" class="w-14 h-14 object-contain mx-auto mb-2">
            @endif
            <h1 class="text-2xl font-black uppercase tracking-tight text-slate-900">
                {{ $school['name'] ?? 'PRIME ARC ACADEMY' }}
            </h1>
            <p class="text-[11px] font-bold text-emerald-800 tracking-wider uppercase mt-0.5">
                {{ $school['motto'] ?? 'Aim, Ascend, Achieve • Center for Academic & Technological Excellence' }}
            </p>
            <p class="text-[10px] text-slate-500 mt-1">
                {{ $school['campus'] ?: 'Campus not configured' }} • {{ $school['address'] ?: 'Address not configured' }} • Contact: {{ $school['phone'] ?: 'Not configured' }} • Email: {{ $school['email'] ?: 'Not configured' }}
            </p>
        </div>

        <!-- 2. REPORT TITLE & AUDIT METADATA -->
        <div class="bg-slate-50 rounded-2xl p-4 border border-slate-200 mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-700 block">Official Institutional Report</span>
                <h2 class="text-base font-extrabold text-slate-900">
                    {{ $reportData['title'] }}
                </h2>
                <p class="text-slate-600 text-[11px] mt-0.5">
                    {{ $reportData['description'] }}
                </p>
            </div>
            <div class="sm:text-right border-t sm:border-t-0 pt-2 sm:pt-0 border-slate-200 text-[11px] text-slate-600 space-y-0.5">
                <div>Date Generated: <span class="font-bold text-slate-900">{{ $reportData['generated_at'] }}</span></div>
                <div>Document Officer: <span class="font-bold text-slate-900">{{ $printedBy }}</span></div>
                @if($selectedClassName)
                <div>Filtered Class: <span class="font-bold text-emerald-700">{{ $selectedClassName }}</span></div>
                @endif
            </div>
        </div>

        <!-- 3. EXECUTIVE SUMMARY METRICS (KPIS) -->
        @if(!empty($reportData['kpis']))
        <div class="grid grid-cols-2 sm:grid-cols-{{ min(4, count($reportData['kpis'])) }} gap-3 mb-6">
            @foreach($reportData['kpis'] as $kpi)
            <div class="p-3 rounded-xl border border-slate-200 bg-slate-50 text-center">
                <span class="text-[10px] font-bold uppercase text-slate-500 block mb-0.5">{{ $kpi['label'] }}</span>
                <div class="text-lg font-black text-slate-900">{{ $kpi['value'] }}</div>
                @if(!empty($kpi['subtext']))
                <span class="text-[9px] text-slate-500 block">{{ $kpi['subtext'] }}</span>
                @endif
            </div>
            @endforeach
        </div>
        @endif

        <!-- 4. RECORD TABLE -->
        <div class="mb-8 overflow-hidden rounded-xl border border-slate-300">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-100 border-b border-slate-300 text-slate-800 text-[10px] uppercase font-bold tracking-wider">
                        @foreach($reportData['headers'] as $h)
                        <th class="py-2.5 px-3 border-r border-slate-200 last:border-r-0">
                            {{ $h }}
                        </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($reportData['rows'] as $idx => $r)
                    <tr class="{{ $idx % 2 === 0 ? 'bg-white' : 'bg-slate-50/50' }}">
                        @foreach($r as $val)
                        <td class="py-2 px-3 text-slate-800 border-r border-slate-200 last:border-r-0 font-medium">
                            {{ $val }}
                        </td>
                        @endforeach
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ count($reportData['headers']) }}" class="py-8 text-center text-slate-400 italic">
                            No matching records found for this reporting cycle.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- 5. REGULATORY DISCLAIMER & OFFICIAL SIGN-OFF -->
        <div class="pt-6 border-t border-slate-300 mt-12">
            <p class="text-[10px] text-slate-500 text-center mb-8">
                This document is generated directly from the verified institutional database of {{ $school['name'] ?? 'Prime Arc Academy' }}. Any unauthorized alteration, erasure, or duplication renders this report invalid.
            </p>

            <div class="grid grid-cols-3 gap-6 text-center text-xs">
                <div>
                    <div class="h-14 border-b border-dashed border-slate-400 flex items-end justify-center pb-1 font-bold text-slate-800">
                        {{ $printedBy }}
                    </div>
                    <span class="text-[10px] font-bold text-slate-600 uppercase tracking-wider block mt-1">Prepared By (Reporting Officer)</span>
                </div>

                <div>
                    <div class="h-14 border-b border-dashed border-slate-400 flex items-end justify-center pb-1 text-slate-400 italic">
                        Verified Signature
                    </div>
                    <span class="text-[10px] font-bold text-slate-600 uppercase tracking-wider block mt-1">Verified By (Registrar / HOD)</span>
                </div>

                <div>
                    <div class="h-14 border-b border-dashed border-slate-400 flex items-end justify-center pb-1 text-slate-400 italic">
                        Authorized Seal &amp; Signature
                    </div>
                    <span class="text-[10px] font-bold text-slate-600 uppercase tracking-wider block mt-1">Principal / Head of Institution</span>
                </div>
            </div>
        </div>

    </div>

</body>
</html>
