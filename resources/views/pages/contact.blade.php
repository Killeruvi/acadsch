@extends('layouts.public')

@section('title', 'Contact Us — ' . ($school['name'] ?? config('app.name', 'School ERP')))
@section('meta_description', 'Connect with Academic Relations, admissions advisors, and student support. Powered by PrimeOS.')

@section('content')
<!-- Hero Header -->
<section class="py-16 bg-white border-b border-slate-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl space-y-3">
            <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                <span>Academic Relations</span>
            </div>
            <h1 class="font-display text-3xl sm:text-5xl font-black tracking-tight text-slate-900 leading-tight">
                Connect With <br>
                <span class="text-emerald-700">{{ $school['name'] ?? config('app.name', 'School ERP') }}.</span>
            </h1>
            <p class="text-sm sm:text-base text-slate-600 leading-relaxed">
                Whether you have questions regarding syllabus coverage, class scheduling & timetables, admissions, or student verification, we are here to assist.
            </p>
        </div>
    </div>
</section>

<!-- Content Grid -->
<section class="py-16 bg-slate-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">
            
            <!-- Left Info Panel (5 cols) -->
            <div class="lg:col-span-5 space-y-6">
                
                <div class="space-y-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-emerald-700 block">Institutional Office</span>
                    <h2 class="font-display text-xl font-bold text-slate-900">Campus Center</h2>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        {{ $school['name'] ?? 'Main Academic Campus' }} &bull; Knowledge Pavilion <br>
                        Center of Academic Excellence <br>
                        Affiliated to {{ $school['board_name'] ?? 'CBSE' }} Standard Guidelines
                    </p>
                </div>

                <div class="space-y-3 text-xs text-slate-600">
                    <div class="flex items-start space-x-3 bg-white p-4 rounded-xl border border-slate-200 shadow-2xs">
                        <span class="text-base text-emerald-600">📧</span>
                        <div>
                            <span class="font-bold text-slate-900 block">Email Inquiries</span>
                            <span class="text-slate-500">{{ $school['email'] ?? 'admissions@school.edu' }}</span>
                        </div>
                    </div>

                    <div class="flex items-start space-x-3 bg-white p-4 rounded-xl border border-slate-200 shadow-2xs">
                        <span class="text-base text-teal-600">📞</span>
                        <div>
                            <span class="font-bold text-slate-900 block">Academic Helpline</span>
                            <span class="text-slate-500">{{ $school['phone'] ?? '+91 98000 00000' }} &bull; Mon–Sat, 8 AM – 7 PM</span>
                        </div>
                    </div>

                    <div class="flex items-start space-x-3 bg-white p-4 rounded-xl border border-slate-200 shadow-2xs">
                        <span class="text-base text-indigo-600">🏛️</span>
                        <div>
                            <span class="font-bold text-slate-900 block">Campus Admissions Desk</span>
                            <span class="text-slate-500">Open for in-person parent consultations and classroom section allocations</span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Right Contact Form (7 cols) -->
            <div class="lg:col-span-7">
                <div class="bg-white rounded-2xl p-6 sm:p-8 border border-slate-200 shadow-xs space-y-4">
                    <h3 class="font-bold text-slate-900 text-base">Send an Inquiry</h3>
                    @if(session('success'))
                    <div class="p-3 bg-emerald-50 text-emerald-800 rounded-xl text-xs font-bold border border-emerald-200">
                        {{ session('success') }}
                    </div>
                    @endif
                    <form action="{{ route('contact.submit') }}" method="POST" class="space-y-4 text-xs">
                        @csrf
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Your Full Name *</label>
                                <input type="text" name="name" required class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs">
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Email Address *</label>
                                <input type="email" name="email" required class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Contact Phone Number *</label>
                                <input type="tel" name="phone" required class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs">
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Inquiry Type *</label>
                                <select name="inquiry_type" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs bg-white font-medium">
                                    <option value="admissions">Admissions &amp; Batches</option>
                                    <option value="curriculum">Curriculum &amp; DPPs</option>
                                    <option value="general">General Information</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Inquiry Details *</label>
                            <textarea name="message" rows="4" required placeholder="Tell us about the student's background, current school, and questions regarding DPPs or batch schedules..." class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs"></textarea>
                        </div>

                        <button type="submit" class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-xs transition">
                            Submit Inquiry &rarr;
                        </button>
                    </form>
                </div>
            </div>

        </div>

    </div>
</section>

@endsection
