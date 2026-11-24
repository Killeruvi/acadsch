@extends('layouts.public')

@section('title', 'Online Student Admission Application — ' . ($school['name'] ?? config('app.name', 'School ERP')))

@section('content')
<div class="py-12 bg-slate-50 min-h-screen" x-data="{
    step: 1,
    totalSteps: 4,
    nextStep() { if(this.step < this.totalSteps) this.step++; window.scrollTo({top: 0, behavior: 'smooth'}); },
    prevStep() { if(this.step > 1) this.step--; window.scrollTo({top: 0, behavior: 'smooth'}); }
}">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="text-center space-y-2 mb-8">
            <span class="inline-flex items-center space-x-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                <span>Academic Session {{ $academicSession }}</span>
            </span>
            <h1 class="font-display text-3xl sm:text-4xl font-black text-slate-900 tracking-tight">
                Online Admission Application
            </h1>
            <p class="text-sm text-slate-600 max-w-xl mx-auto">
                {{ $school['name'] ?? config('app.name', 'School ERP') }} &bull; Complete the digital application form to initiate scholar enrollment.
            </p>
        </div>

        @if($errors->any())
        <div class="mb-6 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold">
            <div class="font-bold mb-1">Please address the following requirements:</div>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <!-- Stepper Progress Bar -->
        <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-2xs mb-8">
            <div class="grid grid-cols-4 gap-2 text-center text-xs font-bold">
                <div :class="step >= 1 ? 'text-emerald-700' : 'text-slate-400'">
                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full mb-1 text-[11px]" :class="step >= 1 ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-500'">1</span>
                    <span class="hidden sm:inline block">Scholar Details</span>
                </div>
                <div :class="step >= 2 ? 'text-emerald-700' : 'text-slate-400'">
                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full mb-1 text-[11px]" :class="step >= 2 ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-500'">2</span>
                    <span class="hidden sm:inline block">Academic History</span>
                </div>
                <div :class="step >= 3 ? 'text-emerald-700' : 'text-slate-400'">
                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full mb-1 text-[11px]" :class="step >= 3 ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-500'">3</span>
                    <span class="hidden sm:inline block">Parent / Guardian</span>
                </div>
                <div :class="step >= 4 ? 'text-emerald-700' : 'text-slate-400'">
                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full mb-1 text-[11px]" :class="step >= 4 ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-500'">4</span>
                    <span class="hidden sm:inline block">Documents &amp; Submit</span>
                </div>
            </div>
        </div>

        <form action="{{ route('admissions.apply.store') }}" method="POST" enctype="multipart/form-data" class="bg-white rounded-3xl border border-slate-200 shadow-xs p-6 sm:p-10 space-y-6">
            @csrf

            <!-- STEP 1: SCHOLAR DETAILS -->
            <div x-show="step === 1" class="space-y-4">
                <h2 class="text-lg font-bold text-slate-900 border-b pb-3">Step 1: Scholar Information</h2>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Applying for Grade / Class <span class="text-rose-500">*</span></label>
                    <select name="target_class_id" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold focus:ring-2 focus:ring-emerald-500">
                        <option value="">-- Select Grade Level --</option>
                        @foreach($classes as $c)
                        <option value="{{ $c->id }}" {{ old('target_class_id') == $c->id ? 'selected' : '' }}>{{ $c->display_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">First Name <span class="text-rose-500">*</span></label>
                        <input type="text" name="student_first_name" required value="{{ old('student_first_name') }}" placeholder="e.g. Aarav" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Last Name <span class="text-rose-500">*</span></label>
                        <input type="text" name="student_last_name" required value="{{ old('student_last_name') }}" placeholder="e.g. Sharma" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Date of Birth <span class="text-rose-500">*</span></label>
                        <input type="date" name="dob" required value="{{ old('dob') }}" max="{{ date('Y-m-d') }}" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Gender <span class="text-rose-500">*</span></label>
                        <select name="gender" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold">
                            <option value="male" {{ old('gender') === 'male' ? 'selected' : '' }}>Male</option>
                            <option value="female" {{ old('gender') === 'female' ? 'selected' : '' }}>Female</option>
                            <option value="other" {{ old('gender') === 'other' ? 'selected' : '' }}>Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Blood Group</label>
                        <input type="text" name="blood_group" value="{{ old('blood_group') }}" placeholder="e.g. O+, B+, A+" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Nationality <span class="text-rose-500">*</span></label>
                        <input type="text" name="nationality" required value="{{ old('nationality', 'Indian') }}" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Religion</label>
                        <input type="text" name="religion" value="{{ old('religion') }}" placeholder="e.g. Hindu, Muslim, Christian, Sikh" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Category <span class="text-rose-500">*</span></label>
                        <select name="caste_category" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold">
                            <option value="General" {{ old('caste_category') === 'General' ? 'selected' : '' }}>General</option>
                            <option value="OBC" {{ old('caste_category') === 'OBC' ? 'selected' : '' }}>OBC</option>
                            <option value="SC" {{ old('caste_category') === 'SC' ? 'selected' : '' }}>SC</option>
                            <option value="ST" {{ old('caste_category') === 'ST' ? 'selected' : '' }}>ST</option>
                            <option value="EWS" {{ old('caste_category') === 'EWS' ? 'selected' : '' }}>EWS</option>
                        </select>
                    </div>
                </div>

                <div class="pt-4 flex justify-end">
                    <button type="button" @click="nextStep()" class="px-6 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold transition">
                        Next: Academic History &rarr;
                    </button>
                </div>
            </div>

            <!-- STEP 2: PREVIOUS ACADEMIC BACKGROUND -->
            <div x-show="step === 2" class="space-y-4" style="display: none;">
                <h2 class="text-lg font-bold text-slate-900 border-b pb-3">Step 2: Previous Schooling Record</h2>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Last Attended School Name</label>
                    <input type="text" name="previous_school_name" value="{{ old('previous_school_name') }}" placeholder="e.g. St. Xavier Senior Secondary School" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Last Completed Grade</label>
                        <input type="text" name="previous_class" value="{{ old('previous_class') }}" placeholder="e.g. Class 9 / Grade 9" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Final Marks Percentage (%)</label>
                        <input type="number" step="0.01" min="0" max="100" name="previous_marks_percentage" value="{{ old('previous_marks_percentage') }}" placeholder="e.g. 88.50" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold">
                    </div>
                </div>

                <div class="pt-4 flex justify-between">
                    <button type="button" @click="prevStep()" class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-700 text-xs font-bold hover:bg-slate-50 transition">
                        &larr; Back
                    </button>
                    <button type="button" @click="nextStep()" class="px-6 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold transition">
                        Next: Parent &amp; Address &rarr;
                    </button>
                </div>
            </div>

            <!-- STEP 3: PARENTS & RESIDENTIAL ADDRESS -->
            <div x-show="step === 3" class="space-y-4" style="display: none;">
                <h2 class="text-lg font-bold text-slate-900 border-b pb-3">Step 3: Parents &amp; Guardian Information</h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="space-y-3 bg-slate-50 p-4 rounded-2xl border border-slate-200">
                        <span class="text-xs font-bold uppercase tracking-wider text-emerald-800 block">Father's Details</span>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Father's Full Name <span class="text-rose-500">*</span></label>
                            <input type="text" name="father_name" required value="{{ old('father_name') }}" placeholder="e.g. Rajesh Sharma" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs bg-white font-semibold">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Primary Mobile Number <span class="text-rose-500">*</span></label>
                            <input type="tel" name="father_phone" required value="{{ old('father_phone') }}" placeholder="e.g. +91 98765 43210" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs bg-white font-semibold">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Email Address</label>
                            <input type="email" name="father_email" value="{{ old('father_email') }}" placeholder="e.g. rajesh@example.com" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs bg-white">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Occupation</label>
                            <input type="text" name="father_occupation" value="{{ old('father_occupation') }}" placeholder="e.g. Software Engineer / Business" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs bg-white">
                        </div>
                    </div>

                    <div class="space-y-3 bg-slate-50 p-4 rounded-2xl border border-slate-200">
                        <span class="text-xs font-bold uppercase tracking-wider text-emerald-800 block">Mother's Details</span>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Mother's Full Name <span class="text-rose-500">*</span></label>
                            <input type="text" name="mother_name" required value="{{ old('mother_name') }}" placeholder="e.g. Sunita Sharma" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs bg-white font-semibold">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Mobile Number</label>
                            <input type="tel" name="mother_phone" value="{{ old('mother_phone') }}" placeholder="e.g. +91 98765 12345" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs bg-white font-semibold">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Email Address</label>
                            <input type="email" name="mother_email" value="{{ old('mother_email') }}" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs bg-white">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Occupation</label>
                            <input type="text" name="mother_occupation" value="{{ old('mother_occupation') }}" placeholder="e.g. Teacher / Homemaker" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs bg-white">
                        </div>
                    </div>
                </div>

                <div class="space-y-3 pt-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-700 block">Residential Address</span>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Permanent Residential Address <span class="text-rose-500">*</span></label>
                        <textarea name="guardian_address" rows="2" required placeholder="House number, street, landmark..." class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs">{{ old('guardian_address') }}</textarea>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">City <span class="text-rose-500">*</span></label>
                            <input type="text" name="city" required value="{{ old('city') }}" placeholder="e.g. Jaipur" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-semibold">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">State <span class="text-rose-500">*</span></label>
                            <input type="text" name="state" required value="{{ old('state') }}" placeholder="e.g. Rajasthan" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-semibold">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Postal PIN Code <span class="text-rose-500">*</span></label>
                            <input type="text" name="pincode" required value="{{ old('pincode') }}" placeholder="e.g. 302001" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-semibold">
                        </div>
                    </div>
                </div>

                <div class="pt-4 flex justify-between">
                    <button type="button" @click="prevStep()" class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-700 text-xs font-bold hover:bg-slate-50 transition">
                        &larr; Back
                    </button>
                    <button type="button" @click="nextStep()" class="px-6 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold transition">
                        Next: Upload Documents &rarr;
                    </button>
                </div>
            </div>

            <!-- STEP 4: DOCUMENT UPLOADS & FINAL SUBMIT -->
            <div x-show="step === 4" class="space-y-5" style="display: none;">
                <h2 class="text-lg font-bold text-slate-900 border-b pb-3">Step 4: Verification Documents (Optional / Recommended)</h2>
                <p class="text-xs text-slate-500">Supported formats: PDF, JPG, PNG (Max 5MB each). Documents can also be submitted physically during campus verification.</p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="p-4 rounded-2xl border border-dashed border-slate-300 hover:border-emerald-500 transition space-y-1">
                        <label class="block text-xs font-bold text-slate-900">Scholar Passport Photo</label>
                        <span class="text-[11px] text-slate-500 block">Recent front-facing colored photograph</span>
                        <input type="file" name="student_photo" accept="image/*" class="mt-2 text-xs text-slate-600 file:mr-2 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700">
                    </div>

                    <div class="p-4 rounded-2xl border border-dashed border-slate-300 hover:border-emerald-500 transition space-y-1">
                        <label class="block text-xs font-bold text-slate-900">Birth Certificate</label>
                        <span class="text-[11px] text-slate-500 block">Municipal / Gram Panchayat certificate</span>
                        <input type="file" name="birth_certificate" accept=".pdf,image/*" class="mt-2 text-xs text-slate-600 file:mr-2 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700">
                    </div>

                    <div class="p-4 rounded-2xl border border-dashed border-slate-300 hover:border-emerald-500 transition space-y-1">
                        <label class="block text-xs font-bold text-slate-900">Transfer Certificate (TC)</label>
                        <span class="text-[11px] text-slate-500 block">From previous recognized school</span>
                        <input type="file" name="transfer_certificate" accept=".pdf,image/*" class="mt-2 text-xs text-slate-600 file:mr-2 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700">
                    </div>

                    <div class="p-4 rounded-2xl border border-dashed border-slate-300 hover:border-emerald-500 transition space-y-1">
                        <label class="block text-xs font-bold text-slate-900">Previous Report Card / Marksheet</label>
                        <span class="text-[11px] text-slate-500 block">Last academic year final result</span>
                        <input type="file" name="previous_marksheet" accept=".pdf,image/*" class="mt-2 text-xs text-slate-600 file:mr-2 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700">
                    </div>
                </div>

                <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200 text-xs text-amber-900 space-y-1">
                    <span class="font-bold block">Parent Declaration:</span>
                    <p>I hereby certify that all information submitted in this application is true and correct to the best of my knowledge. I understand that admission is subject to verification of original certificates by {{ $school['name'] ?? 'the institution' }}.</p>
                </div>

                <div class="pt-4 flex justify-between">
                    <button type="button" @click="prevStep()" class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-700 text-xs font-bold hover:bg-slate-50 transition">
                        &larr; Back
                    </button>
                    <button type="submit" class="px-8 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold transition shadow-md">
                        Submit Official Application
                    </button>
                </div>
            </div>

        </form>

        <!-- Track Link -->
        <div class="mt-6 text-center text-xs text-slate-500">
            Already submitted an application?
            <a href="{{ route('admissions.track') }}" class="font-bold text-emerald-700 hover:underline">Track Application Status &rarr;</a>
        </div>
    </div>
</div>
@endsection
