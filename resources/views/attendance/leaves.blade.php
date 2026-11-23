@extends('layouts.app')

@section('title', 'Leave Management' . ' — ' . ($school['name'] ?? 'PrimeOS Academy'))

@section('content')
<div class="max-w-[1400px] mx-auto px-3 sm:px-4 lg:px-6 py-4 pb-24 sm:pb-8 space-y-3" 
     x-data="{ 
         activeTab: '{{ request('tab', ($errors->any() ? 'apply' : 'view')) }}',
         activeStudentId: '{{ request('student_id', (isset($children) && $children->isNotEmpty() ? $children->first()->id : '')) }}'
     }">

    @if(Auth::user()->isParent())
        @include('school.parent._nav')
    @endif

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-1">
        <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight">
            Leave Management Portal
        </h1>

        <!-- Metric Badges -->
        <div class="flex items-center space-x-2">
            <span class="px-2.5 py-1 rounded-lg bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 font-bold text-[11px] border border-amber-200 dark:border-amber-800">
                ⏳ {{ $pendingCount }} Pending
            </span>
            <span class="px-2.5 py-1 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 font-bold text-[11px] border border-emerald-200 dark:border-emerald-800">
                ✓ {{ $approvedCount }} Approved
            </span>
        </div>
    </div>

    @if(session('success'))
    <div class="bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded-xl p-3 text-xs font-bold text-emerald-800 dark:text-emerald-200 flex items-center space-x-2 shadow-xs">
        <span>✓</span>
        <div>{{ session('success') }}</div>
    </div>
    @endif

    @if(session('error'))
    <div class="bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 rounded-xl p-3 text-xs font-bold text-rose-800 dark:text-rose-200 flex items-center space-x-2 shadow-xs">
        <span>⚠️</span>
        <div>{{ session('error') }}</div>
    </div>
    @endif

    <!-- Shallow Tab Navigation: Apply Leave | View Leaves -->
    <div class="flex items-center space-x-1 border-b border-slate-200 dark:border-slate-800 pb-1 text-xs">
        <button type="button" 
                @click="activeTab = 'view'" 
                :class="activeTab === 'view' ? 'border-b-2 border-emerald-600 text-emerald-600 font-bold' : 'text-slate-500 hover:text-slate-800 dark:hover:text-white font-semibold'"
                class="px-3.5 py-1.5 transition flex items-center space-x-1.5">
            <span>📋</span>
            <span>View Leaves ({{ $leaveRequests->total() }})</span>
        </button>

        @if(Auth::user()->isParent() || Auth::user()->isStudent())
        <button type="button" 
                @click="activeTab = 'apply'" 
                :class="activeTab === 'apply' ? 'border-b-2 border-emerald-600 text-emerald-600 font-bold' : 'text-slate-500 hover:text-slate-800 dark:hover:text-white font-semibold'"
                class="px-3.5 py-1.5 transition flex items-center space-x-1.5">
            <span>✍️</span>
            <span>Apply Leave</span>
        </button>
        @endif
    </div>

    <!-- TAB 1: VIEW LEAVES (Flat, Dense List) -->
    <div x-show="activeTab === 'view'" class="space-y-3">
        <!-- Compact Status Filter Pills -->
        <div class="flex items-center space-x-1.5 overflow-x-auto text-[11px]">
            <a href="{{ route('leaves.index', ['tab' => 'view', 'status' => 'all']) }}" 
               class="px-2.5 py-1 rounded-lg font-bold transition {{ request('status', 'all') === 'all' ? 'bg-slate-900 text-white dark:bg-emerald-600' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300' }}">
                All Leaves
            </a>
            <a href="{{ route('leaves.index', ['tab' => 'view', 'status' => 'pending']) }}" 
               class="px-2.5 py-1 rounded-lg font-bold transition {{ request('status') === 'pending' ? 'bg-slate-900 text-white dark:bg-emerald-600' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300' }}">
                Pending ({{ $pendingCount }})
            </a>
            <a href="{{ route('leaves.index', ['tab' => 'view', 'status' => 'approved']) }}" 
               class="px-2.5 py-1 rounded-lg font-bold transition {{ request('status') === 'approved' ? 'bg-slate-900 text-white dark:bg-emerald-600' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300' }}">
                Approved ({{ $approvedCount }})
            </a>
            <a href="{{ route('leaves.index', ['tab' => 'view', 'status' => 'rejected']) }}"
               class="px-2.5 py-1 rounded-lg font-bold transition {{ request('status') === 'rejected' ? 'bg-slate-900 text-white dark:bg-emerald-600' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300' }}">
                Rejected
            </a>
            <a href="{{ route('leaves.index', ['tab' => 'view', 'status' => 'cancelled']) }}"
               class="px-2.5 py-1 rounded-lg font-bold transition {{ request('status') === 'cancelled' ? 'bg-slate-900 text-white dark:bg-emerald-600' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300' }}">
                Cancelled
            </a>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-2xs divide-y divide-slate-100 dark:divide-slate-800">
            @forelse($leaveRequests as $leave)
            <div class="p-3 sm:p-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 text-xs">
                <div class="flex-1 space-y-1">
                    <div class="flex items-center space-x-2">
                        @if(Auth::user()->isParent() || Auth::user()->isTeacher() || Auth::user()->isAdmin())
                        <span class="font-bold text-slate-900 dark:text-white">
                            {{ $leave->student?->name ?? 'Student' }}
                        </span>
                        @if($leave->student?->currentClass)
                        <span class="text-[10px] text-slate-400 font-medium">({{ $leave->student->currentClass->display_name }})</span>
                        @endif
                        <span class="text-slate-300">&bull;</span>
                        @endif

                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase
                            {{ $leave->type === 'medical' ? 'bg-rose-50 text-rose-700 dark:bg-rose-950 dark:text-rose-300' : ($leave->type === 'family' ? 'bg-purple-50 text-purple-700 dark:bg-purple-950 dark:text-purple-300' : 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300') }}">
                            {{ ucfirst($leave->type) }}
                        </span>

                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase
                            {{ $leave->status === 'approved' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : ($leave->status === 'rejected' ? 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300' : ($leave->status === 'cancelled' ? 'bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300')) }}">
                            {{ ucfirst($leave->status) }}
                        </span>
                    </div>

                    <div class="text-[11px] text-slate-500 font-mono">
                        {{ \Carbon\Carbon::parse($leave->start_date)->format('d M Y') }} &rarr; {{ \Carbon\Carbon::parse($leave->end_date)->format('d M Y') }}
                        ({{ \Carbon\Carbon::parse($leave->start_date)->diffInDays(\Carbon\Carbon::parse($leave->end_date)) + 1 }} days)
                    </div>

                    <div class="flex items-start gap-1.5 text-xs pt-0.5">
                        <span class="px-1.5 py-0.2 rounded text-[10px] font-bold uppercase bg-slate-100 dark:bg-slate-800 text-slate-500 flex-shrink-0">
                            Reason
                        </span>
                        <span class="text-slate-700 dark:text-slate-300">{{ $leave->reason }}</span>
                    </div>

                    @if($leave->teacher_remarks)
                    <div class="flex items-start gap-1.5 text-[11px] pt-0.5">
                        <span class="px-1.5 py-0.2 rounded text-[9px] font-bold uppercase bg-indigo-100 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 flex-shrink-0">
                            Faculty Note
                        </span>
                        <span class="text-indigo-800 dark:text-indigo-200">{{ $leave->teacher_remarks }}</span>
                    </div>
                    @endif
                </div>

                <!-- Action Controls -->
                <div class="flex items-center space-x-1.5 self-end sm:self-center">
                    @if($leave->status === 'pending' && (Auth::user()->isAdmin() || (Auth::user()->isTeacher() && $leave->student?->currentClass && (int) $leave->student->currentClass->class_teacher_id === (int) Auth::id())))
                    <form method="POST" action="{{ route('leaves.review', $leave->id) }}" class="inline">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="approved">
                        <button type="submit" class="px-2.5 py-1 rounded bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-2xs">
                            ✓ Approve
                        </button>
                    </form>
                    <form method="POST" action="{{ route('leaves.review', $leave->id) }}" class="inline">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="rejected">
                        <button type="submit" class="px-2.5 py-1 rounded bg-rose-600 hover:bg-rose-500 text-white font-bold text-xs shadow-2xs">
                            ✕ Reject
                        </button>
                    </form>
                    @endif

                    @if(($leave->status === 'pending') && ((Auth::user()->isStudent() && (int) $leave->student_id === (int) Auth::id()) || (Auth::user()->isParent() && $children->contains('id', (int) $leave->student_id))))
                    <form method="POST" action="{{ route('leaves.destroy', $leave->id) }}" class="inline" onsubmit="return confirm('Cancel this leave request?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-2 py-1 rounded text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition text-xs font-bold" title="Cancel request">
                            Cancel
                        </button>
                    </form>
                    @endif
                </div>
            </div>
            @empty
            <div class="p-8 text-center text-xs text-slate-400">
                No leave requests found.
            </div>
            @endforelse
        </div>

        @if($leaveRequests->hasPages())
        <div class="pt-2">
            {{ $leaveRequests->links() }}
        </div>
        @endif
    </div>

    <!-- TAB 2: APPLY LEAVE (Compact, Flat, No Modals, Same Page) -->
    @if(Auth::user()->isParent() || Auth::user()->isStudent())
    <div x-show="activeTab === 'apply'" x-cloak class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-4 shadow-xs space-y-3 max-w-2xl">
        <div class="border-b border-slate-100 dark:border-slate-800 pb-2">
            <h3 class="text-sm font-black text-slate-900 dark:text-white">
                Submit Formal Leave Application
            </h3>
            <p class="text-[11px] text-slate-500">
                Authorized absences are recorded in the institutional attendance register.
            </p>
        </div>

        <form method="POST" action="{{ route('leaves.store') }}" class="space-y-3 text-xs">
            @csrf

            <!-- Parent Child Selector -->
            @if(Auth::user()->isParent())
                @if(isset($children) && $children->count() > 1)
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Select Child *</label>
                    <select name="student_id" required class="w-full px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-bold text-slate-900 dark:text-white">
                        @foreach($children as $child)
                        <option value="{{ $child->id }}" {{ old('student_id') == $child->id ? 'selected' : '' }}>
                            {{ $child->name }} ({{ $child->currentClass?->display_name ?? 'Class' }})
                        </option>
                        @endforeach
                    </select>
                </div>
                @elseif(isset($children) && $children->count() === 1)
                <input type="hidden" name="student_id" value="{{ $children->first()->id }}">
                <div class="text-[11px] font-semibold text-slate-600 dark:text-slate-400">
                    Applying for: <strong class="text-slate-900 dark:text-white">{{ $children->first()->name }}</strong> ({{ $children->first()->currentClass?->display_name ?? 'Enrolled' }})
                </div>
                @endif
            @endif

            <!-- Leave Type -->
            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Absence Category *</label>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                    <label class="border border-slate-200 dark:border-slate-700 rounded-lg p-2 flex items-center space-x-2 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800">
                        <input type="radio" name="type" value="medical" required class="text-emerald-600" {{ old('type', 'medical') === 'medical' ? 'checked' : '' }}>
                        <span class="font-bold">🏥 Medical</span>
                    </label>
                    <label class="border border-slate-200 dark:border-slate-700 rounded-lg p-2 flex items-center space-x-2 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800">
                        <input type="radio" name="type" value="family" class="text-emerald-600" {{ old('type') === 'family' ? 'checked' : '' }}>
                        <span class="font-bold">👨‍👩‍👧 Family</span>
                    </label>
                    <label class="border border-slate-200 dark:border-slate-700 rounded-lg p-2 flex items-center space-x-2 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800">
                        <input type="radio" name="type" value="academic" class="text-emerald-600" {{ old('type') === 'academic' ? 'checked' : '' }}>
                        <span class="font-bold">📚 Academic</span>
                    </label>
                    <label class="border border-slate-200 dark:border-slate-700 rounded-lg p-2 flex items-center space-x-2 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800">
                        <input type="radio" name="type" value="other" class="text-emerald-600" {{ old('type') === 'other' ? 'checked' : '' }}>
                        <span class="font-bold">📋 Other</span>
                    </label>
                </div>
            </div>

            <!-- Date Range -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Start Date *</label>
                    <input type="date" name="start_date" value="{{ old('start_date', now()->toDateString()) }}" required
                           class="w-full px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-bold text-slate-900 dark:text-white">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">End Date *</label>
                    <input type="date" name="end_date" value="{{ old('end_date', now()->toDateString()) }}" required
                           class="w-full px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-bold text-slate-900 dark:text-white">
                </div>
            </div>

            <!-- Reason Description -->
            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Formal Reason / Justification *</label>
                <textarea name="reason" rows="3" required placeholder="Provide clear explanation for the student absence..."
                          class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white leading-relaxed">{{ old('reason') }}</textarea>
            </div>

            <div class="flex items-center justify-end space-x-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                <button type="button" @click="activeTab = 'view'" class="px-3 py-1.5 text-xs font-bold text-slate-600 dark:text-slate-400">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-xs transition">
                    Submit Leave Application &rarr;
                </button>
            </div>
        </form>
    </div>
    @endif

</div>
@endsection
