@extends('layouts.app')

@section('title', $staff->name . ' — Faculty Dossier' . ' — ' . ($school['name'] ?? 'PrimeArc Public School'))

@section('content')
<div class="max-w-4xl mx-auto space-y-4">

    <!-- Top Action Breadcrumb -->
    <div class="flex items-center justify-between pb-2 border-b border-slate-200 dark:border-slate-800 text-xs">
        <a href="{{ route('staff.show', $staff->id) }}" class="font-bold text-slate-500 hover:text-emerald-600 dark:text-slate-400 dark:hover:text-emerald-400 flex items-center space-x-1.5 transition">
            <span>&larr;</span>
            <span>Back to Staff Profile</span>
        </a>

        <span class="text-xs text-slate-400 font-mono">Faculty ID: {{ $staff->id }}</span>
    </div>

    <!-- Edit Staff Form Card -->
    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-5 sm:p-7 shadow-xs space-y-5">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
            <div>
                <h2 class="text-lg font-black text-slate-900 dark:text-white">Edit Faculty / Staff Member</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Update credentials, organizational role, contact details, and approval status.</p>
            </div>
            <span class="px-3 py-1 rounded-full text-xs font-bold bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                Staff Management
            </span>
        </div>

        <form method="POST" action="{{ route('staff.update', $staff->id) }}" class="space-y-4 text-xs">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1">Full Name *</label>
                    <input type="text" name="name" value="{{ old('name', $staff->name) }}" required class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-bold">
                </div>

                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1">Email Address *</label>
                    <input type="email" name="email" value="{{ old('email', $staff->email) }}" required class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-mono">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1">Phone Number</label>
                    <input type="text" name="phone" value="{{ old('phone', $staff->phone) }}" placeholder="+91..." class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-mono">
                </div>

                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1">Assigned Role *</label>
                    <select name="role" required class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-bold">
                        <option value="teacher" {{ old('role', $staff->role) === 'teacher' ? 'selected' : '' }}>Instructional Faculty / Teacher</option>
                        <option value="admin" {{ old('role', $staff->role) === 'admin' ? 'selected' : '' }}>Institutional Administrator</option>
                    </select>
                </div>

                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1">Approval Status</label>
                    <select name="is_approved" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-bold">
                        <option value="1" {{ old('is_approved', $staff->is_approved) ? 'selected' : '' }}>Approved Active Staff</option>
                        <option value="0" {{ !old('is_approved', $staff->is_approved) ? 'selected' : '' }}>Pending Administrative Approval</option>
                    </select>
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end space-x-2.5">
                <a href="{{ route('staff.show', $staff->id) }}" class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold transition">
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
