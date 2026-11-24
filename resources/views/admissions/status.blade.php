@extends('layouts.public')

@section('title', 'Application Status Tracker — ' . ($school['name'] ?? config('app.name', 'School ERP')))

@section('content')
<div class="py-12 bg-slate-50 min-h-screen">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        
        <!-- Search Card -->
        <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-xs space-y-4">
            <div class="text-center space-y-1">
                <span class="text-xs font-bold uppercase tracking-wider text-emerald-700">Admissions Desk</span>
                <h1 class="text-2xl font-black text-slate-900">Track Application Status</h1>
                <p class="text-xs text-slate-500">Enter your official application reference number and registered guardian mobile number.</p>
            </div>

            <form action="{{ route('admissions.track') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-12 gap-3 pt-2">
                <div class="sm:col-span-6">
                    <label class="block text-xs font-bold text-slate-700 mb-1">Application Number</label>
                    <input type="text" name="app_no" required value="{{ $appNo ?? request('app_no') }}" placeholder="e.g. ADM-2026-0001-XXXX" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-mono font-bold focus:ring-2 focus:ring-emerald-500">
                </div>
                <div class="sm:col-span-4">
                    <label class="block text-xs font-bold text-slate-700 mb-1">Guardian Phone (Optional)</label>
                    <input type="tel" name="phone" value="{{ request('phone') }}" placeholder="e.g. 9876543210" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-emerald-500">
                </div>
                <div class="sm:col-span-2 flex items-end">
                    <button type="submit" class="w-full py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold transition shadow-xs">
                        Search
                    </button>
                </div>
            </form>
        </div>

        @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-50 text-emerald-800 border border-emerald-200 text-xs font-semibold">
            {{ session('success') }}
        </div>
        @endif

        @if($appNo && !$admission)
        <div class="bg-white rounded-3xl p-8 border border-slate-200 text-center space-y-2">
            <span class="text-3xl block">🔍</span>
            <h3 class="text-sm font-bold text-slate-900">Application Not Found</h3>
            <p class="text-xs text-slate-500">No application record matched reference number <strong>{{ $appNo }}</strong>. Please check your tracking number and retry.</p>
        </div>
        @endif

        <!-- Application Result Slip -->
        @if($admission)
        <div class="bg-white rounded-3xl border border-slate-200 shadow-xs overflow-hidden">
            <!-- Header Banner -->
            <div class="p-6 bg-slate-900 text-white flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <div class="text-[11px] text-slate-400 font-mono">REFERENCE: {{ $admission->application_number }}</div>
                    <h2 class="text-xl font-bold mt-0.5">{{ $admission->full_name }}</h2>
                    <span class="text-xs text-emerald-400 font-semibold">Applying for {{ $admission->targetClass?->display_name }}</span>
                </div>

                <div>
                    @if($admission->application_status === 'admitted')
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-500 text-white shadow-xs">
                        🎉 Admission Granted
                    </span>
                    @elseif($admission->application_status === 'shortlisted')
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-indigo-500 text-white">
                        ✓ Shortlisted for Interview
                    </span>
                    @elseif($admission->application_status === 'under_review')
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-amber-500 text-white">
                        ⏳ Under Verification
                    </span>
                    @elseif($admission->application_status === 'rejected')
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-rose-500 text-white">
                        ✕ Application Rejected
                    </span>
                    @else
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-slate-700 text-slate-200">
                        📄 Application Submitted
                    </span>
                    @endif
                </div>
            </div>

            <!-- Content Details Grid -->
            <div class="p-6 space-y-5 text-xs text-slate-700">
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 p-4 rounded-2xl bg-slate-50 border border-slate-100">
                    <div>
                        <span class="text-[10px] uppercase font-bold text-slate-400 block">Date of Birth</span>
                        <span class="font-bold text-slate-900">{{ $admission->dob->format('M d, Y') }}</span>
                    </div>
                    <div>
                        <span class="text-[10px] uppercase font-bold text-slate-400 block">Gender</span>
                        <span class="font-bold text-slate-900 capitalize">{{ $admission->gender }}</span>
                    </div>
                    <div>
                        <span class="text-[10px] uppercase font-bold text-slate-400 block">Category</span>
                        <span class="font-bold text-slate-900">{{ $admission->caste_category }}</span>
                    </div>
                    <div>
                        <span class="text-[10px] uppercase font-bold text-slate-400 block">Submission Date</span>
                        <span class="font-bold text-slate-900">{{ $admission->created_at->format('M d, Y') }}</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <span class="font-bold text-slate-900 block border-b pb-1">Parent &amp; Guardian Info</span>
                        <div><strong>Father:</strong> {{ $admission->father_name }} ({{ $admission->father_phone }})</div>
                        <div><strong>Mother:</strong> {{ $admission->mother_name }}</div>
                        <div><strong>Address:</strong> {{ $admission->guardian_address }}, {{ $admission->city }}, {{ $admission->state }} - {{ $admission->pincode }}</div>
                    </div>

                    <div class="space-y-1.5">
                        <span class="font-bold text-slate-900 block border-b pb-1">Academic Background</span>
                        <div><strong>Previous School:</strong> {{ $admission->previous_school_name ?? 'N/A' }}</div>
                        <div><strong>Previous Grade:</strong> {{ $admission->previous_class ?? 'N/A' }}</div>
                        <div><strong>Marks Percentage:</strong> {{ $admission->previous_marks_percentage ? $admission->previous_marks_percentage.'%' : 'N/A' }}</div>
                    </div>
                </div>

                @if($admission->review_notes)
                <div class="p-4 rounded-2xl bg-indigo-50 border border-indigo-200 text-indigo-900">
                    <span class="font-bold block mb-1">Admissions Office Remarks:</span>
                    <p>{{ $admission->review_notes }}</p>
                </div>
                @endif

                <div class="pt-4 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500">
                    <span>Keep this slip for physical verification at the campus admissions desk.</span>
                    <button type="button" onclick="window.print()" class="px-4 py-1.5 rounded-xl border border-slate-300 font-bold text-slate-700 hover:bg-slate-50">
                        🖨️ Print Acknowledgement
                    </button>
                </div>
            </div>
        </div>
        @endif

    </div>
</div>
@endsection
