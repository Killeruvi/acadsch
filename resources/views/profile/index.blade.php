@extends('layouts.app')

@section('title', 'My Profile' . ' — ' . ($school['name'] ?? 'PrimeOS Academy'))

@section('content')
<div class="space-y-4">

    <!-- Profile Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-2 border-b border-slate-100 dark:border-slate-800">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 rounded-xl bg-emerald-600 text-white font-black text-base flex items-center justify-center flex-shrink-0 shadow-xs">
                {{ substr($user->name, 0, 1) }}
            </div>
            <div>
                <div class="flex items-center space-x-2">
                    <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight">{{ $user->name }}</h1>
                    <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-wider
                        @if($user->isAdmin()) bg-indigo-100 text-indigo-800 dark:bg-indigo-950/60 dark:text-indigo-300
                        @elseif($user->isTeacher()) bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300
                        @else bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300 @endif">
                        {{ $user->role }}
                    </span>
                </div>
                <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 flex items-center space-x-2">
                    <span>ID: <strong class="font-mono text-slate-800 dark:text-slate-200">{{ $user->student_id_number }}</strong></span>
                    <span>&bull;</span>
                    <span>{{ $user->email }}</span>
                </div>
            </div>
        </div>

        <div class="flex items-center space-x-2">
            <a href="{{ route('idcard.show') }}" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-bold shadow-xs transition flex items-center space-x-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"></path></svg>
                <span>Virtual ID Card</span>
            </a>
            <a href="{{ route('school.fees.index') }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg text-xs font-bold border border-slate-200 dark:border-slate-700 transition">
                Fees &amp; Billing
            </a>
        </div>
    </div>

    <!-- Profile Edit Form -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-6">
            <div>
                <h2 class="text-lg font-bold text-slate-900">Personal &amp; Academic Information</h2>
                <p class="text-xs text-slate-500">Update your contact information, address, and guardian records</p>
            </div>
            <span class="text-xs text-slate-400 font-mono">Reg # {{ $user->student_id_number }}</span>
        </div>

        <form method="POST" action="{{ route('profile.update') }}" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Row 1: Core details -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Full Legal Name *</label>
                    <input type="text" name="name" required value="{{ old('name', $user->name) }}" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Email Address (Immutable)</label>
                    <input type="email" disabled value="{{ $user->email }}" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-500 text-sm">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Permanent Phone Number *</label>
                    <input type="tel" name="phone" required value="{{ old('phone', $user->phone) }}" placeholder="+91 98765 43210" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Date of Birth</label>
                    <input type="date" name="date_of_birth" value="{{ old('date_of_birth', $user->date_of_birth?->format('Y-m-d')) }}" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Blood Group</label>
                    <select name="blood_group" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm bg-white">
                        <option value="">Select...</option>
                        @foreach(['O+', 'O-', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-'] as $bg)
                            <option value="{{ $bg }}" {{ old('blood_group', $user->blood_group) === $bg ? 'selected' : '' }}>{{ $bg }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Campus ID Badge Valid Until</label>
                    <input type="text" disabled value="{{ $user->card_valid_until?->format('M d, Y') ?? 'Active Term' }}" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-500 text-sm">
                </div>
            </div>

            <!-- Address & Bio -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Residential Address</label>
                    <input type="text" name="address" value="{{ old('address', $user->address) }}" placeholder="Street, Apartment, City" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Biographical Note / Specialization</label>
                    <input type="text" name="bio" value="{{ old('bio', $user->bio) }}" placeholder="Brief summary, interests, major" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm">
                </div>
            </div>

            <!-- Guardian Information (For Students) -->
            @if($user->isStudent())
            <div class="pt-6 border-t border-slate-100">
                <h3 class="text-sm font-bold text-slate-900 mb-3">Guardian / Emergency Contact Information</h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Guardian Name</label>
                        <input type="text" name="guardian_name" value="{{ old('guardian_name', $user->guardian_name) }}" placeholder="Parent / Sponsor Name" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Guardian Emergency Phone</label>
                        <input type="text" name="guardian_phone" value="{{ old('guardian_phone', $user->guardian_phone) }}" placeholder="+91 98765 43210" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Guardian Email</label>
                        <input type="email" name="guardian_email" value="{{ old('guardian_email', $user->guardian_email) }}" placeholder="guardian@school.edu" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm">
                    </div>
                </div>
            </div>
            @endif

            <!-- Security / Password Change -->
            <div class="pt-6 border-t border-slate-100">
                <h3 class="text-sm font-bold text-slate-900 mb-1">Security &amp; Password</h3>
                <p class="text-xs text-slate-500 mb-3">Leave blank if you do not wish to change your password</p>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Current Password</label>
                        <input type="password" name="current_password" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">New Password</label>
                        <input type="password" name="new_password" minlength="6" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Confirm New Password</label>
                        <input type="password" name="new_password_confirmation" minlength="6" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm">
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="pt-4 border-t border-slate-100 flex justify-end">
                <button type="submit" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm rounded-xl shadow-md shadow-emerald-600/20 transition">
                    Save Profile Changes
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
