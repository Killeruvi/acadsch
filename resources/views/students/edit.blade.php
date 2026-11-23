@extends('layouts.app')

@section('title', $student->name . ' — Student Dossier' . ' — ' . ($school['name'] ?? 'PrimeArc Public School'))

@section('content')
<div class="max-w-4xl mx-auto space-y-4">

    <!-- Top Action Breadcrumb -->
    <div class="flex items-center justify-between pb-2 border-b border-slate-200 dark:border-slate-800 text-xs">
        <a href="{{ route('students.show', $student->id) }}" class="font-bold text-slate-500 hover:text-emerald-600 dark:text-slate-400 dark:hover:text-emerald-400 flex items-center space-x-1.5 transition">
            <span>&larr;</span>
            <span>Back to Student Profile</span>
        </a>

        <span class="text-xs text-slate-400 font-mono">Editing ID: {{ $student->student_id_number }}</span>
    </div>

    <!-- Edit Student Master Form Card -->
    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-5 sm:p-7 shadow-xs space-y-5">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
            <div>
                <h2 class="text-lg font-black text-slate-900 dark:text-white">Edit Student Information</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Update student demographics and guardian contacts. Academic placement and admission identity are managed separately.</p>
            </div>
            <span class="px-3 py-1 rounded-full text-xs font-bold bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                Student Management
            </span>
        </div>

        <form method="POST" action="{{ route('students.update', $student->id) }}" class="space-y-4 text-xs">
            @csrf
            @method('PUT')

            <!-- Section 1: Core Demographics -->
            <div class="space-y-3">
                <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-500 block">1. Core Demographics</span>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1">Full Legal Name *</label>
                        <input type="text" name="name" value="{{ old('name', $student->name) }}" required class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-bold">
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1">Email Address *</label>
                        <input type="email" value="{{ $student->email }}" readonly aria-readonly="true" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-mono cursor-not-allowed">
                        <span class="text-[10px] text-slate-500 mt-1 block">Login email changes require a verified account-security workflow.</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1">Phone Number</label>
                        <input type="text" name="phone" value="{{ old('phone', $student->phone) }}" placeholder="+91..." class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-mono">
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1">Student ID Number</label>
                        <input type="text" value="{{ $student->student_id_number ?? $student->admission_number ?? 'Not assigned' }}" readonly aria-readonly="true" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-mono font-bold cursor-not-allowed">
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1">Blood Group</label>
                        <select name="blood_group" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                            <option value="">Select Blood Group</option>
                            @foreach(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $bg)
                            <option value="{{ $bg }}" {{ old('blood_group', $student->blood_group) === $bg ? 'selected' : '' }}>{{ $bg }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- Section 2: Academic Program & Batch Enrollment -->
            <div class="pt-3 border-t border-slate-100 dark:border-slate-800 space-y-3">
                <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-500 block">2. Program &amp; Class Enrollment</span>
                
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1">Academic Class (Read-only)</label>
                        <input type="text" value="{{ $student->currentClass?->display_name ?? 'Not enrolled' }}" readonly aria-readonly="true" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 cursor-not-allowed">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1">Class Roll Number (Read-only)</label>
                        <input type="text" value="{{ $student->roll_number ?? 'Not assigned' }}" readonly aria-readonly="true" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-mono cursor-not-allowed">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1">Assigned Section (Read-only)</label>
                        <input type="text" value="Section {{ $student->currentClass?->section ?? 'A' }}" readonly aria-readonly="true" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 cursor-not-allowed">
                    </div>
                </div>
                <p class="text-[10px] text-slate-500 dark:text-slate-400">Class transfers, roll allocation, and section assignment must use their dedicated workflows to preserve enrollment history.</p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1">Academic Tier (Read-only)</label>
                        <input type="text" value="{{ $student->academic_tier ?: 'Not assigned' }}" readonly aria-readonly="true" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 cursor-not-allowed">
                    </div>

                    @if(auth()->user()->isAdmin())
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1">Account Approval Status</label>
                        <select name="is_approved" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-bold">
                            <option value="1" {{ old('is_approved', $student->is_approved) ? 'selected' : '' }}>Active Approved Account</option>
                            <option value="0" {{ !old('is_approved', $student->is_approved) ? 'selected' : '' }}>Pending Administrative Review</option>
                        </select>
                    </div>
                    @else
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1">Account Approval Status</label>
                        <input type="text" value="{{ $student->is_approved ? 'Approved' : 'Pending review' }}" readonly class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 cursor-not-allowed">
                    </div>
                    @endif
                </div>
            </div>

            <!-- Section 3: Guardian Details -->
            <div class="pt-3 border-t border-slate-100 dark:border-slate-800 space-y-3">
                <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-500 block">3. Guardian Contacts &amp; Address</span>
                
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1">Guardian Name</label>
                        <input type="text" name="guardian_name" value="{{ old('guardian_name', $student->guardian_name) }}" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1">Guardian Phone</label>
                        <input type="text" name="guardian_phone" value="{{ old('guardian_phone', $student->guardian_phone) }}" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-mono">
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1">Guardian Email</label>
                        <input type="email" name="guardian_email" value="{{ old('guardian_email', $student->guardian_email) }}" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1">Residential Address</label>
                    <textarea name="address" rows="2" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">{{ old('address', $student->address) }}</textarea>
                </div>
            </div>

            <!-- Action Controls -->
            <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end space-x-2.5">
                <a href="{{ route('students.show', $student->id) }}" class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold transition">
                    Cancel
                </a>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold shadow-md shadow-emerald-600/20 transition flex items-center space-x-1.5">
                    <span>💾 Save Changes</span>
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
