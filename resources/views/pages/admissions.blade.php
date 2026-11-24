@extends('layouts.public')

@section('title', 'Admissions & Batches — ' . ($school['name'] ?? config('app.name', 'School ERP')))
@section('meta_description', 'Guide to enrollment, Classes 1–12 batch scheduling, morning and evening shifts, and transparent admission procedures. Powered by PrimeOS.')

@section('content')
<!-- Hero Header -->
<section class="py-16 bg-white border-b border-slate-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl space-y-3">
            <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                <span>Admissions &amp; Batches</span>
            </div>
            <h1 class="font-display text-3xl sm:text-5xl font-black tracking-tight text-slate-900 leading-tight">
                Join {{ $school['name'] ?? config('app.name', 'School ERP') }} <br>
                <span class="text-emerald-700">Student Community.</span>
            </h1>
            <p class="text-sm sm:text-base text-slate-600 leading-relaxed">
                Structured, concept-first education from Classes 1 to 12. Morning and evening shifts, and individual mentorship.
            </p>
        </div>
    </div>
</section>

<!-- 4-Step Process -->
<section class="py-16 bg-slate-50 border-b border-slate-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-2xl mb-10">
            <span class="text-xs font-bold uppercase tracking-wider text-emerald-700 block mb-1">Step-by-Step Path</span>
            <h2 class="font-display text-2xl sm:text-3xl font-black tracking-tight text-slate-900">
                How to Enroll.
            </h2>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                A simple, transparent 4-stage admission process designed to allocate the ideal learning shift for your child.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-5">
            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-2xs space-y-2">
                <span class="text-xs font-mono font-bold text-emerald-600 block">STEP 01</span>
                <h3 class="font-bold text-slate-900 text-sm">Select Academic Class</h3>
                <p class="text-xs text-slate-600 leading-relaxed">
                    Identify the student's standard (Classes 1 through 12). Review curriculum aligned to CBSE guidelines.
                </p>
            </div>

            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-2xs space-y-2">
                <span class="text-xs font-mono font-bold text-teal-600 block">STEP 02</span>
                <h3 class="font-bold text-slate-900 text-sm">Choose Preferred Shift</h3>
                <p class="text-xs text-slate-600 leading-relaxed">
                    Choose between Morning Shift (08:00 AM – 11:30 AM) and Evening Shift (04:00 PM – 07:30 PM).
                </p>
            </div>

            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-2xs space-y-2">
                <span class="text-xs font-mono font-bold text-indigo-600 block">STEP 03</span>
                <h3 class="font-bold text-slate-900 text-sm">Register Guardian &amp; Student</h3>
                <p class="text-xs text-slate-600 leading-relaxed">
                    Submit verified guardian contact information for attendance alerts, fee receipts, and digital portal credentials.
                </p>
            </div>

            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-2xs space-y-2">
                <span class="text-xs font-mono font-bold text-emerald-700 block">STEP 04</span>
                <h3 class="font-bold text-slate-900 text-sm">Access Learning Portal</h3>
                <p class="text-xs text-slate-600 leading-relaxed">
                    Sign in to the LMS portal to receive your class schedule, daily DPP sets, and Bloom's cognitive challenges.
                </p>
            </div>
        </div>

        <div id="admission-enquiry" class="mt-12 max-w-3xl mx-auto bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 shadow-sm">
            <h2 class="text-xl font-black text-slate-900">Admission Enquiry</h2>
            <p class="text-sm text-slate-600 mt-1">Share basic details. The school team will contact the parent or guardian. This is an enquiry, not a confirmed admission.</p>
            @if(session('status'))<div class="mt-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 p-3 text-sm">{{ session('status') }}</div>@endif
            @if($errors->any())<div class="mt-4 rounded-lg bg-red-50 border border-red-200 text-red-800 p-3 text-sm">Please check the form details and try again.</div>@endif
            <form method="POST" action="{{ route('admissions.enquiry.store') }}" class="mt-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                @csrf
                <div class="hidden" aria-hidden="true"><label>Website<input name="website" tabindex="-1" autocomplete="off"></label></div>
                <div><label class="block text-xs font-bold mb-1 text-slate-700">Student name *</label><input required name="student_name" maxlength="120" value="{{ old('student_name') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" autocomplete="off"></div>
                <div><label class="block text-xs font-bold mb-1 text-slate-700">Applying for class *</label><select required name="applying_for_class" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"><option value="">Select class</option>@foreach(range(1,12) as $grade)<option value="{{ $grade }}" @selected(old('applying_for_class') == $grade)>Class {{ $grade }}</option>@endforeach</select></div>
                <div><label class="block text-xs font-bold mb-1 text-slate-700">Parent / guardian name *</label><input required name="guardian_name" maxlength="120" value="{{ old('guardian_name') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></div>
                <div><label class="block text-xs font-bold mb-1 text-slate-700">Guardian phone *</label><input required name="guardian_phone" maxlength="25" value="{{ old('guardian_phone') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" inputmode="tel"></div>
                <div><label class="block text-xs font-bold mb-1 text-slate-700">Guardian email</label><input type="email" name="guardian_email" maxlength="150" value="{{ old('guardian_email') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></div>
                <div><label class="block text-xs font-bold mb-1 text-slate-700">Student date of birth</label><input type="date" name="date_of_birth" max="{{ now()->subDay()->toDateString() }}" value="{{ old('date_of_birth') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></div>
                <div><label class="block text-xs font-bold mb-1 text-slate-700">Previous school</label><input name="previous_school" maxlength="150" value="{{ old('previous_school') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></div>
                <div><label class="block text-xs font-bold mb-1 text-slate-700">Preferred contact time</label><input name="preferred_contact_time" maxlength="80" value="{{ old('preferred_contact_time') }}" placeholder="e.g. After 4 PM" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></div>
                <div class="sm:col-span-2"><label class="block text-xs font-bold mb-1 text-slate-700">Message (optional)</label><textarea name="message" maxlength="1500" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">{{ old('message') }}</textarea></div>
                <div class="sm:col-span-2"><button class="rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-3 font-bold text-sm">Submit admission enquiry</button></div>
            </form>
        </div>
    </div>
</section>

@endsection
