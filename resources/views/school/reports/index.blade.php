@extends('layouts.app')

@section('title', 'Institutional Reports & Analytics')

@section('content')
<div class="space-y-6">

    <!-- 1. HEADER & ACTIONS ROW -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white dark:bg-slate-900/80 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
        <div>
            <div class="flex items-center space-x-2 text-xs text-slate-500 dark:text-slate-400 mb-1">
                <span>Institutional Administration</span>
                <span>/</span>
                <span class="text-emerald-600 dark:text-emerald-400 font-semibold">Reports &amp; Intelligence Hub</span>
            </div>
            <h1 class="text-2xl font-black tracking-tight text-slate-900 dark:text-white flex items-center space-x-2">
                <span class="text-emerald-600 dark:text-emerald-400"><i class="ti-pie-chart"></i></span>
                <span>Institutional Reports Center</span>
            </h1>
            <p class="text-xs text-slate-600 dark:text-slate-400 mt-0.5">
                Comprehensive data intelligence, auditing records, and exportable regulatory registers for {{ $school['name'] ?? 'PrimeArc Public School' }}.
            </p>
        </div>

        <!-- Quick Export & Print Actions -->
        <div class="flex items-center space-x-2 flex-wrap">
            <a href="{{ route('school.reports.export', request()->query()) }}" 
               class="inline-flex items-center space-x-1.5 px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold border border-slate-300 dark:border-slate-700 transition shadow-xs">
                <i class="ti-download"></i>
                <span>Export CSV / Excel</span>
            </a>

            <a href="{{ route('school.reports.print', request()->query()) }}" target="_blank"
               class="inline-flex items-center space-x-1.5 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-md shadow-emerald-600/25 transition">
                <i class="ti-printer"></i>
                <span>Print Official Report</span>
            </a>
        </div>
    </div>

    <!-- 2. REPORT CATEGORY NAVIGATION PILLS -->
    <div class="flex items-center space-x-2 overflow-x-auto pb-1 border-b border-slate-200 dark:border-slate-800">
        @foreach($categories as $catKey => $cat)
        @php
            $isActiveCat = ($activeCategory === $catKey);
        @endphp
        <a href="{{ route('school.reports.index', ['category' => $catKey]) }}"
           class="inline-flex items-center space-x-2 px-4 py-2.5 rounded-xl text-xs font-bold whitespace-nowrap transition {{ $isActiveCat ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/25' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white border border-slate-200 dark:border-slate-800' }}">
            <i class="{{ $cat['icon'] }}"></i>
            <span>{{ $cat['name'] }}</span>
        </a>
        @endforeach
    </div>

    <!-- 3. REPORT SELECTOR & FILTER CARD -->
    <div class="bg-white dark:bg-slate-900/80 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-sm space-y-4">
        
        <form method="GET" action="{{ route('school.reports.index') }}" id="report-filter-form" class="space-y-4">
            <input type="hidden" name="category" value="{{ $activeCategory }}">

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                <!-- Report Type Dropdown -->
                <div class="md:col-span-1">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                        Selected Report Type
                    </label>
                    <select name="report_type" onchange="document.getElementById('report-filter-form').submit()" 
                            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white text-xs font-semibold focus:ring-2 focus:ring-emerald-500">
                        @foreach($reports as $repKey => $rep)
                        <option value="{{ $repKey }}" {{ $activeReportType === $repKey ? 'selected' : '' }}>
                            {{ $rep['name'] }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <!-- Academic Class Filter -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                        Academic Class Filter
                    </label>
                    <select name="class_id" onchange="document.getElementById('report-filter-form').submit()"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white text-xs font-semibold focus:ring-2 focus:ring-emerald-500">
                        <option value="">All Institutional Classes</option>
                        @foreach($classes as $c)
                        <option value="{{ $c->id }}" {{ (string)$filters['class_id'] === (string)$c->id ? 'selected' : '' }}>
                            {{ $c->name }} ({{ $c->section }}) • Grade {{ $c->grade_level }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <!-- Term / Gateway / Threshold Contextual Filter -->
                <div>
                    @if($activeCategory === 'academic')
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                        Examination Term
                    </label>
                    <select name="term_id" onchange="document.getElementById('report-filter-form').submit()"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white text-xs font-semibold focus:ring-2 focus:ring-emerald-500">
                        <option value="">All Exam Terms</option>
                        @foreach($terms as $term)
                        <option value="{{ $term->id }}" {{ (string)$filters['term_id'] === (string)$term->id ? 'selected' : '' }}>
                            {{ $term->name }} ({{ $term->academic_year }})
                        </option>
                        @endforeach
                    </select>
                    @elseif($activeReportType === 'fee_collection')
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                        Settlement Gateway
                    </label>
                    <select name="gateway" onchange="document.getElementById('report-filter-form').submit()"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white text-xs font-semibold focus:ring-2 focus:ring-emerald-500">
                        <option value="">All Channels &amp; Gateways</option>
                        @foreach($gateways as $gwKey => $gwName)
                        <option value="{{ $gwKey }}" {{ (string)$filters['gateway'] === (string)$gwKey ? 'selected' : '' }}>
                            {{ $gwName }}
                        </option>
                        @endforeach
                    </select>
                    @elseif($activeReportType === 'chronic_absenteeism')
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                        Attendance Cutoff Threshold (%)
                    </label>
                    <input type="number" name="threshold" value="{{ $filters['threshold'] ?? 75 }}" min="10" max="99"
                           class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white text-xs font-semibold focus:ring-2 focus:ring-emerald-500">
                    @else
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                        Audit Scope
                    </label>
                    <div class="px-3.5 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 text-xs font-semibold">
                        Institutional Full Directory
                    </div>
                    @endif
                </div>
            </div>

            <!-- Date Range Filter Row -->
            <div class="pt-2 border-t border-slate-100 dark:border-slate-800 flex flex-col md:flex-row md:items-center justify-between gap-3">
                <div class="flex items-center space-x-2 flex-wrap gap-y-2">
                    <span class="text-xs font-bold text-slate-500 dark:text-slate-400">Date Range:</span>
                    <input type="date" name="from_date" value="{{ $filters['from_date'] ?? '' }}" 
                           class="px-3 py-1.5 rounded-lg bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white">
                    <span class="text-xs text-slate-400">to</span>
                    <input type="date" name="to_date" value="{{ $filters['to_date'] ?? '' }}" 
                           class="px-3 py-1.5 rounded-lg bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white">

                    <!-- Quick Preset Buttons -->
                    <button type="button" onclick="setDateRange('today')" class="px-2.5 py-1 rounded-md text-[11px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200">Today</button>
                    <button type="button" onclick="setDateRange('this_month')" class="px-2.5 py-1 rounded-md text-[11px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200">This Month</button>
                    <button type="button" onclick="setDateRange('this_year')" class="px-2.5 py-1 rounded-md text-[11px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200">This Year</button>
                    <button type="button" onclick="setDateRange('clear')" class="px-2.5 py-1 rounded-md text-[11px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200">All Time</button>
                </div>

                <div class="flex items-center space-x-2">
                    <a href="{{ route('school.reports.index', ['category' => $activeCategory, 'report_type' => $activeReportType]) }}"
                       class="px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-500 hover:text-slate-700 dark:hover:text-slate-200">
                        Reset Filters
                    </a>
                    <button type="submit" 
                            class="px-4 py-2 rounded-xl bg-slate-900 dark:bg-slate-100 text-white dark:text-slate-900 text-xs font-bold hover:bg-slate-800 dark:hover:bg-white transition shadow-xs">
                        Apply Filter
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- 4. REPORT DESCRIPTION & METADATA -->
    <div class="bg-emerald-500/10 border border-emerald-500/20 rounded-2xl p-4 flex items-center justify-between gap-4">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 rounded-xl bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-lg font-bold">
                <i class="{{ $categories[$activeCategory]['icon'] ?? 'ti-file' }}"></i>
            </div>
            <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">
                    {{ $reportData['title'] }}
                </h3>
                <p class="text-xs text-slate-600 dark:text-slate-400">
                    {{ $reportData['description'] }}
                </p>
            </div>
        </div>
        <div class="text-right text-[11px] text-slate-500 dark:text-slate-400 flex-shrink-0">
            <span>Generated: <strong>{{ $reportData['generated_at'] }}</strong></span>
        </div>
    </div>

    <!-- 5. DYNAMIC EXECUTIVE KPI SUMMARY CARDS -->
    @if(!empty($reportData['kpis']))
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-{{ min(4, count($reportData['kpis'])) }} gap-4">
        @foreach($reportData['kpis'] as $kpi)
        @php
            $color = $kpi['color'] ?? 'emerald';
            $bgClasses = [
                'emerald' => 'from-emerald-500/10 to-teal-500/5 border-emerald-500/20 text-emerald-600 dark:text-emerald-400',
                'indigo' => 'from-indigo-500/10 to-blue-500/5 border-indigo-500/20 text-indigo-600 dark:text-indigo-400',
                'blue' => 'from-blue-500/10 to-cyan-500/5 border-blue-500/20 text-blue-600 dark:text-blue-400',
                'amber' => 'from-amber-500/10 to-yellow-500/5 border-amber-500/20 text-amber-600 dark:text-amber-400',
                'rose' => 'from-rose-500/10 to-pink-500/5 border-rose-500/20 text-rose-600 dark:text-rose-400',
            ][$color] ?? 'from-slate-500/10 to-slate-500/5 border-slate-500/20 text-slate-600 dark:text-slate-400';
        @endphp
        <div class="p-4 rounded-2xl bg-gradient-to-br {{ $bgClasses }} border shadow-xs">
            <span class="text-xs font-bold uppercase tracking-wider block text-slate-500 dark:text-slate-400 mb-1">
                {{ $kpi['label'] }}
            </span>
            <div class="text-2xl font-black tracking-tight text-slate-900 dark:text-white">
                {{ $kpi['value'] }}
            </div>
            @if(!empty($kpi['subtext']))
            <span class="text-[11px] font-semibold opacity-85 block mt-1">
                {{ $kpi['subtext'] }}
            </span>
            @endif
        </div>
        @endforeach
    </div>
    @endif

    <!-- 6. DETAILED DATA TABLE CARD -->
    <div class="bg-white dark:bg-slate-900/80 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <span class="font-bold text-xs uppercase tracking-wider text-slate-500 dark:text-slate-400">
                    Institutional Record Ledger
                </span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                    {{ count($reportData['rows']) }} Records Listed
                </span>
            </div>
            <span class="text-xs text-slate-500 dark:text-slate-400">
                Sorted by institutional priority
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-950/60 border-b border-slate-200 dark:border-slate-800">
                        @foreach($reportData['headers'] as $head)
                        <th class="py-3 px-4 font-bold text-slate-600 dark:text-slate-400 whitespace-nowrap uppercase tracking-wider text-[11px]">
                            {{ $head }}
                        </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                    @forelse($reportData['rows'] as $row)
                    <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition">
                        @foreach($row as $colKey => $cell)
                        <td class="py-3 px-4 text-slate-800 dark:text-slate-200 whitespace-nowrap font-medium">
                            @if(in_array(strtolower($cell), ['passed', 'full', 'active & approved', 'approved', 'cleared', 'verified & reconciled', 'optimal']))
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800">
                                    {{ $cell }}
                                </span>
                            @elseif(in_array(strtolower($cell), ['needs support', 'failed', 'seats open', 'critical', 'warning', 'intervention required', 'heavy']))
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-rose-100 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 border border-rose-300 dark:border-rose-800">
                                    {{ $cell }}
                                </span>
                            @elseif(in_array(strtolower($cell), ['pending', 'current', 'light']))
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-300 dark:border-amber-800">
                                    {{ $cell }}
                                </span>
                            @elseif(str_starts_with((string)$cell, '₹'))
                                <span class="font-mono font-bold text-slate-900 dark:text-emerald-400">
                                    {{ $cell }}
                                </span>
                            @elseif(str_ends_with((string)$cell, '%'))
                                <span class="font-bold text-slate-800 dark:text-slate-200">
                                    {{ $cell }}
                                </span>
                            @else
                                {{ $cell }}
                            @endif
                        </td>
                        @endforeach
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ count($reportData['headers']) }}" class="py-12 text-center text-slate-400">
                            <div class="max-w-xs mx-auto space-y-2">
                                <div class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 mx-auto text-xl">
                                    <i class="ti-search"></i>
                                </div>
                                <h4 class="font-bold text-slate-700 dark:text-slate-300 text-sm">No Records Found</h4>
                                <p class="text-xs text-slate-500">
                                    No records match the selected filter parameters. Try broadening your date range or class selection.
                                </p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Table Footer Summary -->
        <div class="p-4 bg-slate-50 dark:bg-slate-950/40 border-t border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500 dark:text-slate-400 gap-2">
            <span>Official Institutional Document • {{ $school['name'] ?? 'PrimeArc Public School' }}</span>
            <span>Printed copies without official seal are provisional only</span>
        </div>
    </div>

</div>

<!-- Preset Dates Javascript -->
<script>
    function setDateRange(range) {
        const fromInput = document.querySelector('input[name="from_date"]');
        const toInput = document.querySelector('input[name="to_date"]');
        const today = new Date();

        const formatDate = (d) => {
            return d.toISOString().split('T')[0];
        };

        if (range === 'today') {
            fromInput.value = formatDate(today);
            toInput.value = formatDate(today);
        } else if (range === 'this_month') {
            const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
            fromInput.value = formatDate(firstDay);
            toInput.value = formatDate(today);
        } else if (range === 'this_year') {
            const firstDay = new Date(today.getFullYear(), 0, 1);
            fromInput.value = formatDate(firstDay);
            toInput.value = formatDate(today);
        } else if (range === 'clear') {
            fromInput.value = '';
            toInput.value = '';
        }

        document.getElementById('report-filter-form').submit();
    }
</script>
@endsection
