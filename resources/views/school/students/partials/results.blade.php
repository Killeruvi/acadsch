<div id="student-directory-results" aria-live="polite">
    <!-- Infix Cardview Grid Mode -->
    <div x-show="viewMode === 'cards'" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3.5">
        @forelse($students as $st)
        <div class="single_member_card flex flex-col justify-between">
            <div>
                <!-- Top Row: Avatar & Status Badge -->
                <div class="flex items-start justify-between gap-2 mb-3">
                    <div class="flex items-center space-x-2.5">
                        <div class="w-11 h-11 rounded-xl text-white font-black text-sm flex items-center justify-center shadow-xs shrink-0" style="background: var(--system_primary_gradient);">
                            {{ strtoupper(substr($st->name, 0, 2)) }}
                        </div>
                        <div>
                            <span class="text-xs font-mono font-bold text-theme-primary block">
                                {{ $st->admission_number ?? $st->student_id_number ?? 'Not assigned' }}
                            </span>
                            <span class="text-[10px] font-bold text-slate-400">
                                Roll #{{ $st->roll_number ?? '-' }}
                            </span>
                        </div>
                    </div>

                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold shrink-0 {{ $st->enrollment_status === 'active' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/80 dark:text-emerald-300' : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300' }}">
                        {{ $st->enrollment_status ? ucfirst($st->enrollment_status) : 'Unenrolled' }}
                    </span>
                </div>

                <!-- Student Name & Email -->
                <h3 class="text-sm font-bold text-slate-900 dark:text-white truncate" title="{{ $st->name }}">
                    {{ $st->name }}
                </h3>
                <p class="text-[11px] text-slate-400 truncate mb-2.5">{{ $st->email }}</p>

                <!-- Class & Division Badge -->
                <div class="p-2 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800 space-y-1.5 text-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-bold text-slate-400 uppercase">Class:</span>
                        @if($st->currentClass)
                        <span class="font-bold text-slate-800 dark:text-slate-200">
                            {{ $st->currentClass->display_name }}
                        </span>
                        @else
                        <span class="text-slate-400 italic text-[11px]">Unassigned</span>
                        @endif
                    </div>
                    @if($st->guardian_name)
                    <div class="flex items-center justify-between text-[11px]">
                        <span class="text-[10px] font-bold text-slate-400 uppercase">Guardian:</span>
                        <span class="font-medium text-slate-700 dark:text-slate-300 truncate max-w-[120px]">
                            {{ $st->guardian_name }}
                        </span>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Card Actions Footer -->
            <div class="mt-3.5 pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-1.5">
                <a href="{{ route('school.students.show', $st->id) }}" 
                   class="px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 font-bold text-xs inline-flex items-center space-x-1 transition"
                   title="View Academic Record">
                    <i class="ti-user text-[11px]"></i>
                    <span>Profile</span>
                </a>
                <a href="{{ route('school.students.id-card', $st->id) }}" 
                   class="px-2.5 py-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/80 dark:hover:bg-emerald-900 text-emerald-700 dark:text-emerald-300 font-bold text-xs inline-flex items-center space-x-1 transition"
                   title="Generate Student ID Card">
                    <i class="ti-id-badge text-[11px]"></i>
                    <span>ID</span>
                </a>
                @if(Auth::user()->isAdmin() || Auth::user()->isTeacher())
                <a href="{{ route('school.students.edit', $st->id) }}" 
                   class="px-2.5 py-1.5 rounded-lg bg-blue-50 hover:bg-blue-100 dark:bg-blue-950/80 dark:hover:bg-blue-900 text-blue-700 dark:text-blue-300 font-bold text-xs inline-flex items-center space-x-1 transition">
                    <i class="ti-pencil text-[11px]"></i>
                    <span>Edit</span>
                </a>
                @endif
            </div>
        </div>
        @empty
        <div class="col-span-full white-box p-8 text-center text-slate-500 text-xs">
            <p class="font-medium">No students match the selected criteria.</p>
        </div>
        @endforelse
    </div>

    <!-- Students Table (View Mode Table) -->
    <div x-show="viewMode === 'table'" class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600 dark:text-slate-400">
                <thead class="bg-slate-50 dark:bg-slate-800/70 uppercase font-black text-[10px] text-slate-500 tracking-wider border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="p-3.5">Admission No</th>
                        <th class="p-3.5">Roll No</th>
                        <th class="p-3.5">Student Details</th>
                        <th class="p-3.5">Class &amp; Section</th>
                        <th class="p-3.5">Guardian Details</th>
                        <th class="p-3.5">Status</th>
                        <th class="p-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($students as $st)
                    <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                        <td class="p-3.5 font-mono font-bold text-emerald-600 dark:text-emerald-400">
                            {{ $st->admission_number ?? $st->student_id_number ?? 'Not assigned' }}
                        </td>
                        <td class="p-3.5 font-mono font-bold text-slate-900 dark:text-white">
                            #{{ $st->roll_number ?? '-' }}
                        </td>
                        <td class="p-3.5">
                            <div class="flex items-center space-x-2.5">
                                <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-500 text-white font-black text-xs flex items-center justify-center flex-shrink-0">
                                    {{ strtoupper(substr($st->name, 0, 2)) }}
                                </div>
                                <div>
                                    <span class="font-bold text-slate-900 dark:text-white block">{{ $st->name }}</span>
                                    <span class="text-[11px] text-slate-400">{{ $st->email }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="p-3.5">
                            @if($st->currentClass)
                            <span class="px-2.5 py-1 rounded-lg bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 font-bold text-[11px] inline-block">
                                {{ $st->currentClass->display_name }}
                            </span>
                            @else
                            <span class="text-slate-400 italic">Unassigned</span>
                            @endif
                        </td>
                        <td class="p-3.5 text-[11px]">
                            <div class="font-bold text-slate-800 dark:text-slate-200">{{ $st->guardian_name ?? 'N/A' }}</div>
                            <div class="text-slate-400 text-[10px]">{{ $st->guardian_phone ?? $st->phone ?? '-' }}</div>
                        </td>
                        <td class="p-3.5">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold {{ $st->enrollment_status === 'active' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300' }}">
                                {{ $st->enrollment_status ? ucfirst($st->enrollment_status) : 'Unenrolled' }}
                            </span>
                        </td>
                        <td class="p-3.5 text-right space-x-1.5 whitespace-nowrap">
                            <a href="{{ route('school.students.show', $st->id) }}" 
                               class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 font-bold text-[11px] inline-block"
                               title="View Complete Academic File">
                                File
                            </a>
                            <a href="{{ route('school.students.id-card', $st->id) }}" 
                               class="px-2.5 py-1 rounded-lg bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/80 dark:hover:bg-emerald-900 text-emerald-700 dark:text-emerald-300 font-bold text-[11px] inline-block"
                               title="Generate ID Card">
                                🪪 ID
                            </a>
                            @if(Auth::user()->isAdmin() || Auth::user()->isTeacher())
                            <a href="{{ route('school.students.edit', $st->id) }}" 
                               class="px-2.5 py-1 rounded-lg bg-blue-50 hover:bg-blue-100 dark:bg-blue-950/80 dark:hover:bg-blue-900 text-blue-700 dark:text-blue-300 font-bold text-[11px] inline-block">
                                Edit
                            </a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="p-6 text-center text-xs text-slate-400 font-medium">
                            No students match the selected criteria.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-200 dark:border-slate-800">
            {{ $students->withQueryString()->links() }}
        </div>
    </div>

</div>
