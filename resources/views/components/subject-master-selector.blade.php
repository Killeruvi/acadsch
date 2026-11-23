@props([
    'classModel' => null,
    'stream' => 'All',
    'grade' => null,
    'nameField' => 'name',
    'codeField' => 'code',
    'periodsField' => 'weekly_periods',
    'selectedName' => '',
    'selectedCode' => '',
    'selectedPeriods' => 5,
    'required' => true,
])

@php
    $streamVal = $classModel?->stream ?: $stream;
    $gradeVal = $classModel?->grade_level ?: $grade;
    $masterList = $classModel 
        ? \App\Models\SubjectMaster::getAvailableForClass($classModel)
        : \App\Models\SubjectMaster::forStream($streamVal)->orderBy('category')->orderBy('name')->get()->map(function($m) use ($gradeVal) {
            $m->grade_code = $m->generateCodeForGrade($gradeVal);
            $m->is_already_assigned = false;
            return $m;
        });
@endphp

<div x-data="{
    masterSubjects: {{ Js::from($masterList) }},
    selName: '{{ $selectedName }}',
    selCode: '{{ $selectedCode }}',
    periods: {{ (int) $selectedPeriods }},
    onSelectName(name) {
        this.selName = name;
        const found = this.masterSubjects.find(s => s.name === name);
        if (found) {
            this.selCode = found.grade_code || found.code;
            this.periods = found.default_periods || 5;
        }
    },
    onSelectCode(code) {
        this.selCode = code;
        const found = this.masterSubjects.find(s => (s.grade_code === code || s.code === code));
        if (found) {
            this.selName = found.name;
            this.periods = found.default_periods || 5;
        }
    }
}" class="space-y-3">
    <div>
        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
            Subject Name *
            <span class="text-[10px] text-slate-400 font-normal">(Loaded from Subject Master)</span>
        </label>
        <select :name="'{{ $nameField }}'" x-model="selName" @change="onSelectName($event.target.value)" {{ $required ? 'required' : '' }}
                class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500 font-bold">
            <option value="">-- Select Subject from Master --</option>
            <template x-for="subj in masterSubjects" :key="subj.id">
                <option :value="subj.name" :disabled="subj.is_already_assigned"
                        x-text="subj.name + ' (' + subj.category + ')' + (subj.is_already_assigned ? ' — [Already Assigned]' : '')">
                </option>
            </template>
        </select>
    </div>

    <div class="grid grid-cols-2 gap-3">
        <div>
            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                Subject Code *
                <span class="text-[10px] text-slate-400 font-normal">(Auto-Filled / Select)</span>
            </label>
            <select :name="'{{ $codeField }}'" x-model="selCode" @change="onSelectCode($event.target.value)" {{ $required ? 'required' : '' }}
                    class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500 font-mono font-bold">
                <option value="">-- Select Code --</option>
                <template x-for="subj in masterSubjects" :key="'c-' + subj.id">
                    <option :value="subj.grade_code || subj.code" :disabled="subj.is_already_assigned"
                            x-text="(subj.grade_code || subj.code) + ' (' + subj.name + ')'">
                    </option>
                </template>
            </select>
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Weekly Periods</label>
            <input type="number" :name="'{{ $periodsField }}'" x-model="periods" min="1" max="20" required
                   class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500 font-bold">
        </div>
    </div>
</div>
