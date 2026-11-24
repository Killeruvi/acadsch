@extends('layouts.app')

@section('title', 'Co-Scholastic & Holistic Assessment Register — PrimeOS')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2 text-xs text-slate-500 mb-1">
                <a href="{{ route('school.exams.index') }}" class="hover:text-indigo-600">Examinations</a>
                <span>&bull;</span>
                <span>Co-Scholastic &amp; NEP 2020 Rubrics</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                Co-Scholastic Assessment Register
            </h1>
            <p class="text-sm text-slate-600 dark:text-slate-400">
                Record CBSE 3-point co-scholastic grades (Work Education, Art, Health &amp; PE, Discipline) and physical vitals.
            </p>
        </div>

        <div class="flex items-center space-x-2">
            <a href="{{ route('school.exams.index') }}" class="px-3.5 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                &larr; Exams Hub
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="p-4 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800 text-xs font-semibold flex items-center justify-between">
        <span>{{ session('success') }}</span>
        <button type="button" @click="$el.parentElement.remove()" class="text-emerald-600 hover:text-emerald-800">&times;</button>
    </div>
    @endif

    <!-- Selection Bar -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-xs">
        <form action="{{ route('school.exams.co-scholastic') }}" method="GET" class="flex flex-wrap items-center gap-3">
            <div>
                <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Examination Term</label>
                <select name="term_id" class="px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-xs font-semibold">
                    @foreach($terms as $t)
                    <option value="{{ $t->id }}" {{ $selectedTerm && $selectedTerm->id == $t->id ? 'selected' : '' }}>{{ $t->name }} ({{ $t->academic_year }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Target Class &amp; Section</label>
                <select name="class_id" class="px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-xs font-semibold">
                    @foreach($classes as $c)
                    <option value="{{ $c->id }}" {{ $selectedClass && $selectedClass->id == $c->id ? 'selected' : '' }}>{{ $c->display_name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="self-end pt-1">
                <button type="submit" class="px-4 py-2 rounded-xl bg-slate-900 text-white dark:bg-white dark:text-slate-900 text-xs font-bold transition">
                    Load Class Register
                </button>
            </div>
        </form>
    </div>

    <!-- Co-Scholastic Assessment Register Grid -->
    @if($selectedClass && $selectedTerm)
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-xs space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
            <div>
                <h2 class="text-base font-bold text-slate-900 dark:text-white">
                    {{ $selectedClass->display_name }} &bull; {{ $selectedTerm->name }}
                </h2>
                <p class="text-xs text-slate-500">Grading Scale: A (Outstanding), B (Very Good), C (Fair).</p>
            </div>

            <span class="text-xs font-semibold text-slate-500">{{ $students->count() }} enrolled scholars</span>
        </div>

        <form action="{{ route('school.exams.co-scholastic.store') }}" method="POST">
            @csrf
            <input type="hidden" name="term_id" value="{{ $selectedTerm->id }}">
            <input type="hidden" name="class_id" value="{{ $selectedClass->id }}">

            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left">
                    <thead class="bg-slate-50 dark:bg-slate-800/60 text-slate-600 dark:text-slate-300 font-bold uppercase tracking-wider text-[10px]">
                        <tr>
                            <th class="p-3 w-12 text-center">Roll</th>
                            <th class="p-3">Scholar Name</th>
                            <th class="p-3 text-center">Work Ed (5.1)</th>
                            <th class="p-3 text-center">Art Ed (5.2)</th>
                            <th class="p-3 text-center">Health/PE (5.3)</th>
                            <th class="p-3 text-center">Discipline</th>
                            <th class="p-3 text-center">Height (cm)</th>
                            <th class="p-3 text-center">Weight (kg)</th>
                            <th class="p-3">Teacher Holistic Remarks</th>
                            <th class="p-3 text-right">360° HPC</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($students as $st)
                        @php
                            $a = $assessments[$st->id] ?? null;
                        @endphp
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                            <td class="p-3 text-center font-bold text-slate-400">
                                {{ $st->roll_number ?? '-' }}
                            </td>
                            <td class="p-3 font-bold text-slate-900 dark:text-white whitespace-nowrap">
                                <div>{{ $st->name }}</div>
                                <div class="text-[10px] text-slate-400 font-mono">{{ $st->admission_number }}</div>
                            </td>

                            <!-- Work Ed -->
                            <td class="p-2 text-center">
                                <select name="scores[{{ $st->id }}][work_education]" class="px-2 py-1 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-xs font-bold text-center">
                                    <option value="A" {{ ($a?->work_education ?? 'A') === 'A' ? 'selected' : '' }}>A</option>
                                    <option value="B" {{ ($a?->work_education ?? 'A') === 'B' ? 'selected' : '' }}>B</option>
                                    <option value="C" {{ ($a?->work_education ?? 'A') === 'C' ? 'selected' : '' }}>C</option>
                                </select>
                            </td>

                            <!-- Art Ed -->
                            <td class="p-2 text-center">
                                <select name="scores[{{ $st->id }}][art_education]" class="px-2 py-1 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-xs font-bold text-center">
                                    <option value="A" {{ ($a?->art_education ?? 'A') === 'A' ? 'selected' : '' }}>A</option>
                                    <option value="B" {{ ($a?->art_education ?? 'A') === 'B' ? 'selected' : '' }}>B</option>
                                    <option value="C" {{ ($a?->art_education ?? 'A') === 'C' ? 'selected' : '' }}>C</option>
                                </select>
                            </td>

                            <!-- Health & PE -->
                            <td class="p-2 text-center">
                                <select name="scores[{{ $st->id }}][health_physical_education]" class="px-2 py-1 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-xs font-bold text-center">
                                    <option value="A" {{ ($a?->health_physical_education ?? 'A') === 'A' ? 'selected' : '' }}>A</option>
                                    <option value="B" {{ ($a?->health_physical_education ?? 'A') === 'B' ? 'selected' : '' }}>B</option>
                                    <option value="C" {{ ($a?->health_physical_education ?? 'A') === 'C' ? 'selected' : '' }}>C</option>
                                </select>
                            </td>

                            <!-- Discipline -->
                            <td class="p-2 text-center">
                                <select name="scores[{{ $st->id }}][discipline]" class="px-2 py-1 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-xs font-bold text-center">
                                    <option value="A" {{ ($a?->discipline ?? 'A') === 'A' ? 'selected' : '' }}>A</option>
                                    <option value="B" {{ ($a?->discipline ?? 'A') === 'B' ? 'selected' : '' }}>B</option>
                                    <option value="C" {{ ($a?->discipline ?? 'A') === 'C' ? 'selected' : '' }}>C</option>
                                </select>
                            </td>

                            <!-- Height -->
                            <td class="p-2 text-center">
                                <input type="number" step="0.1" name="scores[{{ $st->id }}][height_cm]" value="{{ $a?->height_cm }}" placeholder="cm" class="w-16 px-1.5 py-1 text-center rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-xs font-bold">
                            </td>

                            <!-- Weight -->
                            <td class="p-2 text-center">
                                <input type="number" step="0.1" name="scores[{{ $st->id }}][weight_kg]" value="{{ $a?->weight_kg }}" placeholder="kg" class="w-16 px-1.5 py-1 text-center rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-xs font-bold">
                            </td>

                            <!-- Teacher Holistic Remarks -->
                            <td class="p-2">
                                <input type="text" name="scores[{{ $st->id }}][teacher_holistic_remarks]" value="{{ $a?->teacher_holistic_remarks }}" placeholder="Conduct, teamwork, leadership..." class="w-full px-2 py-1 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-xs">
                            </td>

                            <td class="p-3 text-right whitespace-nowrap">
                                <a href="{{ route('school.exams.holistic-report-card', [$st->id, $selectedTerm->id]) }}" target="_blank" class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 font-bold text-xs underline">
                                    View HPC &nearr;
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="p-8 text-center text-slate-400">
                                No active students found in this class section.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($students->isNotEmpty())
            <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex justify-end">
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold transition shadow-xs">
                    Save Co-Scholastic &amp; Holistic Grades
                </button>
            </div>
            @endif
        </form>
    </div>
    @endif
</div>
@endsection
