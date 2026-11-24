@extends('layouts.app')

@section('title', 'Admission Dossier: ' . $admission->full_name . ' — PrimeOS')

@section('content')
<div class="space-y-6" x-data="{ enrollModal: false }">
    <!-- Breadcrumb & Top Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2 text-xs text-slate-500 mb-1">
                <a href="{{ route('school.admissions.online.index') }}" class="hover:text-indigo-600">Online Admissions Register</a>
                <span>&bull;</span>
                <span class="font-mono">{{ $admission->application_number }}</span>
            </div>
            <div class="flex items-center space-x-3">
                <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                    {{ $admission->full_name }}
                </h1>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase
                    {{ $admission->application_status === 'admitted' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300' : 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/50 dark:text-indigo-300' }}">
                    {{ str_replace('_', ' ', $admission->application_status) }}
                </span>
            </div>
            <p class="text-xs text-slate-600 dark:text-slate-400 mt-0.5">
                Target Grade: <strong>{{ $admission->targetClass?->display_name }}</strong> &bull; Applied: {{ $admission->created_at->format('M d, Y') }}
            </p>
        </div>

        <div class="flex items-center space-x-2">
            <a href="{{ route('school.admissions.online.index') }}" class="px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                &larr; Back to Register
            </a>

            @if(!$admission->enrolled_user_id)
            <button @click="enrollModal = true" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold transition shadow-xs flex items-center space-x-1.5">
                <span>🎓</span>
                <span>Convert to Enrolled Scholar</span>
            </button>
            @else
            <a href="{{ route('school.students.show', $admission->enrolled_user_id) }}" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold transition shadow-xs flex items-center space-x-1.5">
                <span>👤</span>
                <span>View Enrolled Student Profile</span>
            </a>
            @endif
        </div>
    </div>

    @if(session('success'))
    <div class="p-4 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800 text-xs font-semibold">
        {{ session('success') }}
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Left: Dossier Information (8 cols) -->
        <div class="lg:col-span-8 space-y-6">
            
            <!-- Scholar & Academic Card -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-xs space-y-4">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white border-b pb-2 flex items-center space-x-1.5">
                    <span>👦</span>
                    <span>Scholar Bio &amp; Academic Background</span>
                </h3>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
                    <div>
                        <span class="text-slate-400 block text-[10px] uppercase font-bold">Date of Birth</span>
                        <span class="font-bold text-slate-900 dark:text-white">{{ $admission->dob->format('d/m/Y') }} ({{ $admission->dob->age }} yrs)</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[10px] uppercase font-bold">Gender</span>
                        <span class="font-bold text-slate-900 dark:text-white capitalize">{{ $admission->gender }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[10px] uppercase font-bold">Blood Group</span>
                        <span class="font-bold text-slate-900 dark:text-white">{{ $admission->blood_group ?? 'Not specified' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[10px] uppercase font-bold">Nationality</span>
                        <span class="font-bold text-slate-900 dark:text-white">{{ $admission->nationality }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[10px] uppercase font-bold">Religion</span>
                        <span class="font-bold text-slate-900 dark:text-white">{{ $admission->religion ?? 'N/A' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[10px] uppercase font-bold">Social Category</span>
                        <span class="font-bold text-slate-900 dark:text-white">{{ $admission->caste_category }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[10px] uppercase font-bold">Previous School</span>
                        <span class="font-bold text-slate-900 dark:text-white">{{ $admission->previous_school_name ?? 'None' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[10px] uppercase font-bold">Previous Grade &amp; Score</span>
                        <span class="font-bold text-slate-900 dark:text-white">{{ $admission->previous_class ?? 'N/A' }} {{ $admission->previous_marks_percentage ? '('.$admission->previous_marks_percentage.'%)' : '' }}</span>
                    </div>
                </div>
            </div>

            <!-- Parents & Residential Address Card -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-xs space-y-4">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white border-b pb-2 flex items-center space-x-1.5">
                    <span>👨‍👩‍👧</span>
                    <span>Parent &amp; Residential Information</span>
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/40 space-y-1">
                        <span class="font-bold text-emerald-800 dark:text-emerald-400 block">Father / Primary Guardian</span>
                        <div><strong>Name:</strong> {{ $admission->father_name }}</div>
                        <div><strong>Phone:</strong> {{ $admission->father_phone }}</div>
                        <div><strong>Email:</strong> {{ $admission->father_email ?? 'Not provided' }}</div>
                        <div><strong>Occupation:</strong> {{ $admission->father_occupation ?? 'Not provided' }}</div>
                    </div>

                    <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/40 space-y-1">
                        <span class="font-bold text-emerald-800 dark:text-emerald-400 block">Mother Details</span>
                        <div><strong>Name:</strong> {{ $admission->mother_name }}</div>
                        <div><strong>Phone:</strong> {{ $admission->mother_phone ?? 'Same as father' }}</div>
                        <div><strong>Email:</strong> {{ $admission->mother_email ?? 'Not provided' }}</div>
                        <div><strong>Occupation:</strong> {{ $admission->mother_occupation ?? 'Not provided' }}</div>
                    </div>
                </div>

                <div class="text-xs pt-1">
                    <span class="text-slate-400 block text-[10px] uppercase font-bold">Residential Address</span>
                    <p class="font-semibold text-slate-900 dark:text-white mt-0.5">
                        {{ $admission->guardian_address }}, {{ $admission->city }}, {{ $admission->state }} &bull; PIN: {{ $admission->pincode }}
                    </p>
                </div>
            </div>

            <!-- Uploaded Documents Card -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-xs space-y-4">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white border-b pb-2 flex items-center space-x-1.5">
                    <span>📎</span>
                    <span>Verification Documents</span>
                </h3>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    @php
                        $docs = [
                            ['label' => 'Scholar Photo', 'path' => $admission->student_photo_path, 'icon' => '🖼️'],
                            ['label' => 'Birth Certificate', 'path' => $admission->birth_certificate_path, 'icon' => '📜'],
                            ['label' => 'Transfer Certificate', 'path' => $admission->transfer_certificate_path, 'icon' => '📄'],
                            ['label' => 'Previous Marksheet', 'path' => $admission->previous_marksheet_path, 'icon' => '📊'],
                        ];
                    @endphp

                    @foreach($docs as $doc)
                    <div class="p-3 rounded-xl border border-slate-200 dark:border-slate-800 text-center space-y-2">
                        <span class="text-2xl block">{{ $doc['icon'] }}</span>
                        <div class="text-[11px] font-bold text-slate-900 dark:text-white">{{ $doc['label'] }}</div>
                        @if($doc['path'])
                        <a href="{{ asset('storage/' . $doc['path']) }}" target="_blank" class="inline-block px-2.5 py-1 rounded-lg bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 text-[10px] font-bold hover:bg-indigo-100 transition">
                            View File &nearr;
                        </a>
                        @else
                        <span class="text-[10px] text-slate-400 block">Not uploaded</span>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>

        </div>

        <!-- Right: Administrative Review Action Panel (4 cols) -->
        <div class="lg:col-span-4 space-y-6">
            
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-xs space-y-4">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white border-b pb-2">
                    Review Decision &amp; Notes
                </h3>

                <form action="{{ route('school.admissions.online.status', $admission->id) }}" method="POST" class="space-y-3">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Application Status</label>
                        <select name="application_status" required class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs font-semibold">
                            <option value="submitted" {{ $admission->application_status === 'submitted' ? 'selected' : '' }}>Submitted</option>
                            <option value="under_review" {{ $admission->application_status === 'under_review' ? 'selected' : '' }}>Under Review</option>
                            <option value="shortlisted" {{ $admission->application_status === 'shortlisted' ? 'selected' : '' }}>Shortlisted for Interview</option>
                            <option value="admitted" {{ $admission->application_status === 'admitted' ? 'selected' : '' }}>Admitted</option>
                            <option value="rejected" {{ $admission->application_status === 'rejected' ? 'selected' : '' }}>Rejected</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Interview / Evaluation Notes</label>
                        <textarea name="review_notes" rows="4" placeholder="Record interview date, verification remarks, or grounds for decision..." class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs">{{ $admission->review_notes }}</textarea>
                    </div>

                    <button type="submit" class="w-full py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white dark:bg-white dark:text-slate-900 text-xs font-bold transition">
                        Save Review Notes
                    </button>
                </form>

                @if($admission->reviewer)
                <div class="text-[11px] text-slate-400 pt-2 border-t border-slate-100 dark:border-slate-800">
                    Last reviewed by: <strong>{{ $admission->reviewer->name }}</strong> on {{ $admission->updated_at->format('M d, Y h:i A') }}
                </div>
                @endif
            </div>

            <!-- Quick Action Card -->
            @if(!$admission->enrolled_user_id)
            <div class="bg-emerald-50 dark:bg-emerald-950/40 rounded-2xl border border-emerald-200 dark:border-emerald-800 p-5 space-y-3">
                <span class="text-xs font-bold uppercase tracking-wider text-emerald-800 dark:text-emerald-300 block">Finalize Enrollment</span>
                <p class="text-xs text-emerald-900 dark:text-emerald-200">
                    Once certificates are verified, click below to automatically generate scholar records, issue admission numbers, and link guardian credentials.
                </p>
                <button @click="enrollModal = true" class="w-full py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold transition shadow-xs">
                    Enroll Student Now &rarr;
                </button>
            </div>
            @endif

        </div>
    </div>

    <!-- 1-Click Enrollment Conversion Modal -->
    <div x-show="enrollModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-lg w-full p-6 shadow-2xl relative" @click.away="enrollModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800 mb-4">
                <div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Convert to Enrolled Scholar</h3>
                    <p class="text-xs text-slate-500">Applicant: {{ $admission->full_name }}</p>
                </div>
                <button type="button" @click="enrollModal = false" class="text-slate-400 hover:text-slate-600 text-lg">&times;</button>
            </div>

            <form action="{{ route('school.admissions.online.convert', $admission->id) }}" method="POST" class="space-y-3.5">
                @csrf

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Enrolling Class <span class="text-rose-500">*</span></label>
                    <select name="class_id" required class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs font-semibold">
                        @foreach($classes as $c)
                        <option value="{{ $c->id }}" {{ $admission->target_class_id == $c->id ? 'selected' : '' }}>{{ $c->display_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Admission Number</label>
                        <input type="text" name="admission_number" placeholder="Leave blank to auto-generate" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs font-mono">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Class Roll Number</label>
                        <input type="text" name="roll_number" placeholder="e.g. 01" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs font-bold">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Student Portal Email</label>
                        <input type="email" name="student_email" placeholder="Leave blank for auto-generated" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Initial Password</label>
                        <input type="text" name="password" placeholder="Default: Student DOB (DDMMYYYY)" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs font-mono">
                    </div>
                </div>

                <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/50 text-[11px] text-slate-600 dark:text-slate-400 space-y-1">
                    <span class="font-bold block text-slate-700 dark:text-slate-300">Automated Actions on Enrollment:</span>
                    <div>✓ Active student account created &amp; enrolled in selected class.</div>
                    <div>✓ Guardian account linked or created with phone <strong>{{ $admission->father_phone }}</strong>.</div>
                    <div>✓ Uploaded certificates archived to student permanent records.</div>
                </div>

                <div class="pt-3 flex justify-end space-x-2">
                    <button type="button" @click="enrollModal = false" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800">Cancel</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold">Confirm Enrollment</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
