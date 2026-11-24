@extends('layouts.public')

@section('title', 'About Us — ' . ($school['name'] ?? config('app.name', 'School ERP')))
@section('meta_description', 'Learn about ' . ($school['name'] ?? config('app.name', 'School ERP')) . ', our philosophy of conceptual mastery over memorisation, core scientific inquiry, and long-term academic excellence.')

@section('content')
<!-- About Hero -->
<section class="py-16 bg-white border-b border-slate-200 relative overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="max-w-3xl space-y-3">
            <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                <span>About {{ $school['name'] ?? config('app.name', 'School ERP') }}</span>
            </div>
            <h1 class="font-display text-3xl sm:text-5xl font-black tracking-tight text-slate-900 leading-tight">
                Building Minds. <br>
                <span class="text-emerald-700">Enabling Futures.</span>
            </h1>
            <p class="text-sm sm:text-base text-slate-600 leading-relaxed">
                A premier educational institution and school designed to cultivate deep conceptual understanding, scientific curiosity, and enduring practical capability across Classes 1 to 12.
            </p>
        </div>
    </div>
</section>

<!-- Section A: Our Story -->
<section class="py-16 bg-slate-50 border-b border-slate-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-center">
            
            <div class="lg:col-span-7 space-y-4">
                <span class="text-xs font-bold uppercase tracking-wider text-emerald-700 block">Our Origin &amp; Purpose</span>
                <h2 class="font-display text-2xl sm:text-3xl font-black tracking-tight text-slate-900">
                    Why {{ $school['name'] ?? config('app.name', 'School ERP') }} Exists.
                </h2>
                <div class="space-y-3 text-xs sm:text-sm text-slate-600 leading-relaxed">
                    <p>
                        {{ $school['name'] ?? 'Our Institution' }} is founded on the fundamental conviction that education should develop genuine understanding, curiosity, practical intelligence, and the capacity to create meaningful things.
                    </p>
                    <p>
                        Too often, conventional rote schooling reduces the magnificent human enterprise of learning to mechanical memorisation and anxiety-driven test preparation. In that process, the wonder of science, the beauty of mathematical reasoning, and the thrill of discovery are frequently lost.
                    </p>
                    <p>
                        We seek to move beyond these passive methods. Here, students are not passive consumers of lecture slides; they are active investigators, problem-solvers, and learners. We structure our curriculum, tools, and mentors to help young learners build robust intellectual foundations that will serve them for a lifetime.
                    </p>
                </div>
            </div>

            <div class="lg:col-span-5">
                <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-4">
                    <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-2xl">
                        🧬
                    </div>
                    <h3 class="font-bold text-slate-900 text-base">Active Participation &amp; Inquiry</h3>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Transforming students from passive note-takers into independent thinkers through Bloom's 6-tier cognitive progression, daily CBT drills, and peer debate societies.
                    </p>
                    <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-xs font-semibold text-emerald-700">
                        <span>CBSE &bull; Classes 1–12</span>
                        <span>Concept-First</span>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- Section B: Vision, Mission & Core Values -->
<section class="py-16 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-12">
            <div class="p-6 rounded-2xl bg-emerald-50/50 border border-emerald-200 space-y-2">
                <span class="text-xs font-bold text-emerald-700 uppercase tracking-wider">Direction</span>
                <h3 class="font-display text-xl font-black text-slate-900">Our Vision</h3>
                <p class="text-xs text-slate-600 leading-relaxed">
                    To cultivate students who possess profound conceptual mastery, intellectual independence, and genuine scientific curiosity.
                </p>
            </div>

            <div class="p-6 rounded-2xl bg-teal-50/50 border border-teal-200 space-y-2">
                <span class="text-xs font-bold text-teal-700 uppercase tracking-wider">Purpose</span>
                <h3 class="font-display text-xl font-black text-slate-900">Our Mission</h3>
                <p class="text-xs text-slate-600 leading-relaxed">
                    We are not here merely to help students complete a syllabus. We empower learners to build lasting understanding through Bloom's cognitive taxonomy, daily problem practice, and authentic mentoring.
                </p>
            </div>
        </div>

        <div class="text-center max-w-2xl mx-auto mb-10">
            <span class="text-xs font-bold text-emerald-700 uppercase tracking-wider">Our DNA</span>
            <h2 class="font-display text-2xl sm:text-3xl font-black text-slate-900 mt-1">Our Core Values</h2>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-4 text-center">
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                <div class="text-lg">📚</div>
                <h4 class="font-bold text-slate-900 text-xs mt-1">Knowledge</h4>
            </div>
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                <div class="text-lg">🎯</div>
                <h4 class="font-bold text-slate-900 text-xs mt-1">Mastery</h4>
            </div>
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                <div class="text-lg">🔍</div>
                <h4 class="font-bold text-slate-900 text-xs mt-1">Curiosity</h4>
            </div>
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                <div class="text-lg">💡</div>
                <h4 class="font-bold text-slate-900 text-xs mt-1">Innovation</h4>
            </div>
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                <div class="text-lg">🛡️</div>
                <h4 class="font-bold text-slate-900 text-xs mt-1">Integrity</h4>
            </div>
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                <div class="text-lg">🌐</div>
                <h4 class="font-bold text-slate-900 text-xs mt-1">Accessibility</h4>
            </div>
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                <div class="text-lg">🌱</div>
                <h4 class="font-bold text-slate-900 text-xs mt-1">Growth</h4>
            </div>
        </div>
    </div>
</section>

@endsection
