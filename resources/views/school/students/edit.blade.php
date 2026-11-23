@extends('layouts.app')

@section('title', 'Edit Student: ' . $student->name . ' — ' . ($school['name'] ?? 'PrimeArc Public School'))

@section('content')
<div class="max-w-4xl mx-auto space-y-4">

    <!-- Header & Breadcrumbs -->
    <div class="flex items-center justify-between pb-2 border-b border-slate-200 dark:border-slate-800 text-xs">
        <a href="{{ route('school.students.show', $student->id) }}" class="font-bold text-slate-500 hover:text-emerald-600 dark:text-slate-400 dark:hover:text-emerald-400 flex items-center space-x-1.5 transition">
            <span>&larr;</span>
            <span>Back to Student Profile</span>
        </a>

        <div class="flex items-center space-x-2">
            <span class="text-xs text-slate-400 font-mono">Admission: {{ $student->admission_number ?? $student->student_id_number }}</span>
        </div>
    </div>

    <!-- Edit Student Card -->
    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-5 sm:p-7 shadow-xs space-y-6">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
            <div>
                <h2 class="text-lg font-black text-slate-900 dark:text-white">Edit Student Profile</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Update student and guardian profile details. Academic placement, admission identity, and parent linkage are managed separately.</p>
            </div>
            <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                School Student File
            </span>
        </div>

        @if($errors->any())
        <div class="p-4 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-300 text-xs">
            <ul class="list-disc pl-5 space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form method="POST" action="{{ route('school.students.update', $student->id) }}" class="space-y-6 text-xs">
            @csrf
            @method('PUT')

            <!-- Section 1: Read-only academic identity -->
            <div class="space-y-3">
                <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-500 block">1. Academic Identity (Read-only)</span>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Admission Number</label>
                        <input type="text" value="{{ $student->admission_number ?? $student->student_id_number ?? 'Not assigned' }}" readonly aria-readonly="true"
                               class="w-full px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 text-xs font-mono text-slate-600 dark:text-slate-300 cursor-not-allowed">
                        <span class="text-[10px] text-slate-500 mt-1 block">Admission identity is managed through the admission correction workflow.</span>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Current Class &amp; Section</label>
                        <input type="text" value="{{ $student->currentClass?->display_name ?? 'Not enrolled' }}" readonly aria-readonly="true"
                               class="w-full px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 text-xs text-slate-600 dark:text-slate-300 cursor-not-allowed">
                        <span class="text-[10px] text-slate-500 mt-1 block">Class transfers must use the dedicated transfer workflow.</span>
                    </div>
                    <div>
                        <label for="roll_number" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Roll Number</label>
                        <input id="roll_number" type="number" name="roll_number" min="1" value="{{ old('roll_number', $activeEnrollment?->roll_number ?? $student->roll_number) }}" {{ $activeEnrollment ? '' : 'disabled' }}
                               class="w-full px-3.5 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500 disabled:bg-slate-100 disabled:text-slate-400">
                        <span class="text-[10px] text-slate-500 dark:text-slate-400 mt-1 block">Must be unique within the active enrollment. If no active enrollment exists, resolve enrollment first.</span>
                    </div>
                </div>
            </div>

            <!-- Section 2: Student Personal Info -->
            <div class="pt-4 border-t border-slate-100 dark:border-slate-800 space-y-3">
                <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-500 block">2. Student Personal Info</span>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Full Legal Name *</label>
                        <input type="text" name="name" value="{{ old('name', $student->name) }}" required
                               class="w-full px-3.5 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-emerald-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Email Address *</label>
                        <input type="email" value="{{ $student->email }}" readonly aria-readonly="true"
                               class="w-full px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 text-xs text-slate-600 dark:text-slate-300 font-mono cursor-not-allowed">
                        <span class="text-[10px] text-slate-500 mt-1 block">Login email changes require a verified account-security workflow.</span>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Phone Number</label>
                        <input type="text" name="phone" value="{{ old('phone', $student->phone) }}" placeholder="+91 98765 43210"
                               class="w-full px-3.5 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Date of Birth</label>
                        <input type="date" name="date_of_birth" value="{{ old('date_of_birth', $student->date_of_birth?->format('Y-m-d')) }}"
                               class="w-full px-3.5 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Blood Group</label>
                        <select name="blood_group" class="w-full px-3.5 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500">
                            <option value="">Select Blood Group</option>
                            @foreach(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $bg)
                            <option value="{{ $bg }}" {{ old('blood_group', $student->blood_group) === $bg ? 'selected' : '' }}>{{ $bg }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Residential Address</label>
                        <input type="text" name="address" value="{{ old('address', $student->address) }}" placeholder="Flat, Street, City"
                               class="w-full px-3.5 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500">
                    </div>
                </div>
            </div>

            <!-- Section 3: Guardian & Parent Info -->
            <div class="pt-4 border-t border-slate-100 dark:border-slate-800 space-y-3">
                <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-500 block">3. Parent &amp; Guardian Info</span>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Linked Parent Account (Read-only)</label>
                        <input type="text" value="{{ $student->parent ? $student->parent->name . ' (' . $student->parent->email . ')' : 'No parent account linked' }}" readonly aria-readonly="true"
                               class="w-full px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 text-xs text-slate-600 dark:text-slate-300 cursor-not-allowed">
                        <span class="text-[10px] text-slate-500 mt-1 block">Parent account ownership cannot be changed from the profile editor.</span>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Guardian Name</label>
                        <input type="text" name="guardian_name" value="{{ old('guardian_name', $student->guardian_name) }}"
                               class="w-full px-3.5 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Guardian Phone</label>
                        <input type="text" name="guardian_phone" value="{{ old('guardian_phone', $student->guardian_phone) }}"
                               class="w-full px-3.5 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Guardian Email</label>
                        <input type="email" name="guardian_email" value="{{ old('guardian_email', $student->guardian_email) }}"
                               class="w-full px-3.5 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500">
                    </div>
                </div>
            </div>

            <!-- Section 4: Previous School Details (Rule 7) -->
            <div class="pt-4 border-t border-slate-100 dark:border-slate-800 space-y-3">
                <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-500 block">4. Previous School &amp; Transfer Record</span>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Previous School Name</label>
                        <input type="text" name="previous_school_name" value="{{ old('previous_school_name', $student->previous_school_name) }}" placeholder="e.g. St. Xavier's High School"
                               class="w-full px-3.5 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Education Board / Affiliation</label>
                        <input type="text" name="previous_school_board" value="{{ old('previous_school_board', $student->previous_school_board) }}" placeholder="e.g. CBSE, ICSE, State Board"
                               class="w-full px-3.5 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Last Year / Grade Completed</label>
                        <input type="text" name="previous_school_year" value="{{ old('previous_school_year', $student->previous_school_year) }}" placeholder="e.g. Grade 9 (2025-26)"
                               class="w-full px-3.5 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Transfer Certificate (TC) Number</label>
                        <input type="text" name="previous_school_tc_number" value="{{ old('previous_school_tc_number', $student->previous_school_tc_number) }}" placeholder="e.g. TC-2026/8941"
                               class="w-full px-3.5 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Previous School Remarks / Conduct</label>
                        <textarea name="previous_school_remarks" rows="2" placeholder="Academic standing, extracurricular participation, conduct assessment..."
                                  class="w-full px-3.5 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500">{{ old('previous_school_remarks', $student->previous_school_remarks) }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Submit Buttons -->
            <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                <a href="{{ route('school.students.show', $student->id) }}" class="px-5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold shadow-xs hover:shadow transition flex items-center space-x-2">
                    <span>💾</span>
                    <span>Save Changes</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
