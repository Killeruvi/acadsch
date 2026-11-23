@extends('layouts.public')

@section('title', ($school['name'] ?? 'Prime Arc Academy') . ' — Tomorrow\'s World Needs Creators')
@section('meta_description', 'Don\'t just prepare students for the future. Prepare them to discover, build and create it. A next-generation educational institution founded on pure sciences, advanced technology, and real-world invention.')

@section('content')
@php
    $quote = \App\Services\MotivationService::getRandomQuote();
    $fact = \App\Services\MotivationService::getRandomFact();
    $collegeClass = \App\Models\AcademicClass::where('grade_level', '>=', 13)->first();
@endphp

<!-- ========================================================================= -->
<!-- 1. HERO SECTION: "Tomorrow's World Needs More Than Employees. It Needs Creators." -->
<!-- ========================================================================= -->
<section class="relative bg-white border-b border-slate-200/80 overflow-hidden pt-12 pb-20 lg:pt-20 lg:pb-28">
    <!-- Ambient Atmospheric Glows -->
    <div class="absolute top-0 right-1/4 w-[600px] h-[600px] bg-gradient-to-br from-indigo-100/50 via-purple-50/40 to-transparent rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-24 left-10 w-[500px] h-[500px] bg-gradient-to-tr from-blue-100/40 via-cyan-50/40 to-transparent rounded-full blur-3xl pointer-events-none"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-8 items-center">
            
            <!-- Left Editorial Text (7 cols) -->
            <div class="lg:col-span-7 space-y-6 text-left">
                
                <!-- Institutional Vision Badge -->
                <div class="inline-flex items-center space-x-2.5 px-3.5 py-1.5 rounded-full text-xs font-bold bg-slate-900 text-white shadow-sm">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span class="tracking-wide">Next-Generation Learning Ecosystem</span>
                    <span class="text-slate-400">&bull;</span>
                    <span class="text-emerald-300">Pure Sciences &amp; Creation</span>
                </div>

                <!-- Primary Vision Headline -->
                <h1 class="text-3xl sm:text-5xl lg:text-6xl font-black tracking-tight text-slate-900 leading-[1.12]">
                    Tomorrow's World Needs More Than Employees. <br>
                    <span class="bg-clip-text text-transparent bg-gradient-to-r from-purple-700 via-indigo-600 to-blue-600">
                        It Needs Creators.
                    </span>
                </h1>

                <!-- Alternative Motto & Central Statement -->
                <p class="text-base sm:text-lg text-slate-600 leading-relaxed font-normal max-w-2xl">
                    We are building an educational environment where students go beyond textbooks to explore scientific discoveries, understand emerging technologies, solve real-world problems, develop entrepreneurial thinking and create opportunities that don't exist yet.
                </p>

                <!-- Core Credo Callout -->
                <div class="p-3.5 sm:p-4 rounded-2xl bg-slate-50 border border-slate-200/90 text-xs sm:text-sm font-semibold text-slate-800 flex items-start space-x-3 shadow-2xs">
                    <span class="text-xl leading-none select-none">💡</span>
                    <div>
                        <span class="font-extrabold text-slate-900">The Central Philosophy:</span>
                        <span class="italic text-slate-600">"Don't just prepare students for the future. Prepare them to discover, build and create it."</span>
                    </div>
                </div>

                <!-- Call to Actions -->
                <div class="flex flex-wrap items-center gap-3 pt-2">
                    <a href="#vision" class="px-6 py-3.5 rounded-full font-bold text-xs sm:text-sm text-white bg-slate-900 hover:bg-slate-800 transition shadow-md flex items-center space-x-2 group">
                        <span>Explore Our Vision</span>
                        <i class="ti-arrow-down text-xs group-hover:translate-y-0.5 transition-transform"></i>
                    </a>
                    <a href="#philosophy" class="px-5 py-3.5 rounded-full font-bold text-xs sm:text-sm bg-white hover:bg-slate-50 text-slate-800 border border-slate-300 transition shadow-2xs flex items-center space-x-2">
                        <span>Discover Our Learning Approach</span>
                        <i class="ti-angle-right text-xs"></i>
                    </a>
                    <a href="{{ route('admissions') }}" class="px-5 py-3.5 rounded-full font-bold text-xs sm:text-sm text-purple-700 hover:text-purple-900 hover:bg-purple-50 transition flex items-center space-x-1.5">
                        <span>Admissions Portal &rarr;</span>
                    </a>
                </div>

                <!-- Key Ecosystem Metric Pills -->
                <div class="pt-6 border-t border-slate-200 grid grid-cols-3 gap-3 sm:gap-4 max-w-lg">
                    <div class="bg-white p-3 rounded-2xl border border-slate-200/80 shadow-2xs">
                        <div class="text-lg sm:text-2xl font-black text-slate-900">1–12 &bull; College</div>
                        <div class="text-[9px] sm:text-[10px] text-slate-500 font-bold uppercase tracking-wider mt-0.5">UNDERGRAD &bull; Research</div>
                    </div>
                    <div class="bg-white p-3 rounded-2xl border border-slate-200/80 shadow-2xs">
                        <div class="text-lg sm:text-2xl font-black text-purple-700">6 Pillars</div>
                        <div class="text-[9px] sm:text-[10px] text-slate-500 font-bold uppercase tracking-wider mt-0.5">Discovery &amp; Invention</div>
                    </div>
                    <div class="bg-white p-3 rounded-2xl border border-slate-200/80 shadow-2xs">
                        <div class="text-lg sm:text-2xl font-black text-blue-600">8 Stages</div>
                        <div class="text-[9px] sm:text-[10px] text-slate-500 font-bold uppercase tracking-wider mt-0.5">Curiosity to Impact</div>
                    </div>
                </div>

            </div>

            <!-- Right Visual Composition (5 cols) — Real High-Quality Research & Prototyping Photography -->
            <div class="lg:col-span-5 relative">
                <div class="relative mx-auto max-w-md lg:max-w-none">
                    
                    <!-- Main Authentic Photo Card -->
                    <div class="relative rounded-3xl overflow-hidden shadow-2xl border-4 border-white bg-slate-900 aspect-[4/5] group">
                        <img src="{{ asset('images/hero-students-learning.jpg') }}" 
                             alt="Students discovering, building prototypes and coding"
                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700">
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-slate-950/20 to-transparent"></div>
                        
                        <!-- Floating Discovery Telemetry Badge -->
                        <div class="absolute bottom-5 left-5 right-5 p-4 rounded-2xl bg-white/95 dark:bg-slate-900/95 backdrop-blur-md border border-slate-200/80 shadow-xl space-y-2">
                            <div class="flex items-center justify-between text-xs font-bold text-slate-900">
                                <span class="flex items-center space-x-1.5">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                    <span>Active Discovery Lab</span>
                                </span>
                                <span class="text-[10px] uppercase font-mono text-purple-600 bg-purple-50 px-2 py-0.5 rounded-full">Biotechnology &amp; AI</span>
                            </div>
                            <p class="text-[11px] text-slate-600 leading-tight">
                                Students investigating microbial bio-filters, training computer vision models, and testing physical hardware.
                            </p>
                        </div>
                    </div>

                    <!-- Floating Satellite Card 1: Pure Sciences & DNA Sequencing -->
                    <div class="hidden sm:flex absolute -top-6 -left-6 bg-white p-3.5 rounded-2xl shadow-xl border border-slate-200 items-center space-x-3 max-w-[210px] animate-bounce-slow">
                        <div class="w-10 h-10 rounded-xl bg-purple-100 text-purple-700 flex items-center justify-center font-bold text-base flex-shrink-0">
                            🧬
                        </div>
                        <div class="text-left">
                            <div class="text-xs font-bold text-slate-900 leading-tight">DNA &amp; Biotech</div>
                            <div class="text-[10px] text-slate-500">Synthetic biology models</div>
                        </div>
                    </div>

                    <!-- Floating Satellite Card 2: Robotics & Job Creation -->
                    <div class="hidden sm:flex absolute -bottom-6 -right-6 bg-white p-3.5 rounded-2xl shadow-xl border border-slate-200 items-center space-x-3 max-w-[220px]">
                        <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-base flex-shrink-0">
                            🤖
                        </div>
                        <div class="text-left">
                            <div class="text-xs font-bold text-slate-900 leading-tight">Invention &amp; Ventures</div>
                            <div class="text-[10px] text-slate-500">Job creators, not job seekers</div>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</section>

<!-- ========================================================================= -->
<!-- 2. BRAND POSITIONING: "Why Education Needs to Evolve"                     -->
<!-- ========================================================================= -->
<section id="vision" class="py-16 sm:py-24 bg-slate-50 border-b border-slate-200/80">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="max-w-3xl mx-auto text-center space-y-3 mb-16">
            <span class="text-xs font-extrabold uppercase tracking-widest text-purple-700 bg-purple-50 px-3 py-1 rounded-full border border-purple-200">
                A Fundamental Shift
            </span>
            <h2 class="text-2xl sm:text-4xl font-black text-slate-900 tracking-tight">
                Positioned for Creation, Not Just Competition.
            </h2>
            <p class="text-sm sm:text-base text-slate-600 leading-relaxed">
                Conventional education trains students to memorize existing knowledge and compete for pre-existing jobs. We empower students to discover unasked questions and create new possibilities.
            </p>
        </div>

        <!-- 8 Contrasting Positioning Pillars -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
            
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-2xs hover:shadow-md transition">
                <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-700 flex items-center justify-center text-lg mb-3">🔍</div>
                <h3 class="text-sm font-extrabold text-slate-900 mb-1">Discovery over Memorization</h3>
                <p class="text-xs text-slate-600 leading-relaxed">Rather than memorizing encyclopedias, students learn the empirical art of unearthing unknown answers.</p>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-2xs hover:shadow-md transition">
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center text-lg mb-3">⚙️</div>
                <h3 class="text-sm font-extrabold text-slate-900 mb-1">Creation over Consumption</h3>
                <p class="text-xs text-slate-600 leading-relaxed">Moving from passive users of technology and content into architects who write the code and design the tools.</p>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-2xs hover:shadow-md transition">
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center text-lg mb-3">🧠</div>
                <h3 class="text-sm font-extrabold text-slate-900 mb-1">Understanding over Rote Learning</h3>
                <p class="text-xs text-slate-600 leading-relaxed">Deep conceptual intuition that enables students to reconstruct solutions from first principles when conditions change.</p>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-2xs hover:shadow-md transition">
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center text-lg mb-3">🚀</div>
                <h3 class="text-sm font-extrabold text-slate-900 mb-1">Innovation over Repetition</h3>
                <p class="text-xs text-slate-600 leading-relaxed">Encouraging divergent experiments and novel methods rather than repeating identical prescribed steps.</p>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-2xs hover:shadow-md transition">
                <div class="w-10 h-10 rounded-xl bg-teal-50 text-teal-700 flex items-center justify-center text-lg mb-3">💼</div>
                <h3 class="text-sm font-extrabold text-slate-900 mb-1">Entrepreneurship over Dependency</h3>
                <p class="text-xs text-slate-600 leading-relaxed">Understanding how value is created, sustainable ventures are built, and employment is generated for others.</p>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-2xs hover:shadow-md transition">
                <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-700 flex items-center justify-center text-lg mb-3">🔬</div>
                <h3 class="text-sm font-extrabold text-slate-900 mb-1">Scientific Thinking over Blind Acceptance</h3>
                <p class="text-xs text-slate-600 leading-relaxed">Demanding evidence, testing hypotheses, and questioning claims with rigor rather than accepting authority blindly.</p>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-2xs hover:shadow-md transition">
                <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-700 flex items-center justify-center text-lg mb-3">💻</div>
                <h3 class="text-sm font-extrabold text-slate-900 mb-1">Technology Creation</h3>
                <p class="text-xs text-slate-600 leading-relaxed">Understanding Artificial Intelligence, biotechnology, software, and robotics to engineer solutions for society.</p>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-2xs hover:shadow-md transition">
                <div class="w-10 h-10 rounded-xl bg-cyan-50 text-cyan-700 flex items-center justify-center text-lg mb-3">🌐</div>
                <h3 class="text-sm font-extrabold text-slate-900 mb-1">Opportunity Creation</h3>
                <p class="text-xs text-slate-600 leading-relaxed">Rather than queueing up to compete for scarce existing jobs, students discover unmet needs and build new industries.</p>
            </div>

        </div>

    </div>
</section>

<!-- ========================================================================= -->
<!-- 3. THE SIX PILLARS OF EDUCATIONAL PHILOSOPHY                              -->
<!-- ========================================================================= -->
<section id="philosophy" class="py-16 sm:py-24 bg-white border-b border-slate-200/80">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-16">
            <div class="max-w-2xl space-y-3">
                <span class="text-xs font-extrabold uppercase tracking-widest text-emerald-700 bg-emerald-50 px-3 py-1 rounded-full border border-emerald-200">
                    The Educational Architecture
                </span>
                <h2 class="text-2xl sm:text-4xl font-black text-slate-900 tracking-tight">
                    Six Pillars of the Next-Generation Learner
                </h2>
                <p class="text-sm sm:text-base text-slate-600">
                    Academic excellence is our non-negotiable bedrock, but it serves as the launching pad for scientific discovery, engineering invention, and societal contribution.
                </p>
            </div>
            <div class="text-left md:text-right">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider block">Institutional Framework</span>
                <span class="text-lg font-black text-slate-900">Comprehensive K-12 to Undergrad</span>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8">
            
            <!-- Pillar A: Discovery and Scientific Thinking -->
            <div class="rounded-3xl border border-slate-200 p-6 sm:p-7 bg-slate-50/60 hover:bg-white hover:shadow-xl transition-all duration-300 flex flex-col justify-between group">
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="w-12 h-12 rounded-2xl bg-purple-100 text-purple-700 flex items-center justify-center font-black text-xl">01</span>
                        <span class="text-[10px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-full bg-purple-50 text-purple-700">Discovery</span>
                    </div>
                    <h3 class="text-lg font-black text-slate-900 group-hover:text-purple-700 transition-colors">
                        Discovery &amp; Scientific Thinking
                    </h3>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Nurturing deep curiosity, asking meaningful questions, evaluating empirical evidence, and identifying research gaps to develop fearless, independent thinkers.
                    </p>
                    <ul class="space-y-2 pt-2 border-t border-slate-200/80 text-xs text-slate-700">
                        <li class="flex items-center space-x-2">
                            <i class="ti-check text-purple-600 text-xs"></i>
                            <span>Investigate unexplored possibilities</span>
                        </li>
                        <li class="flex items-center space-x-2">
                            <i class="ti-check text-purple-600 text-xs"></i>
                            <span>Conduct hypothesis-driven experiments</span>
                        </li>
                        <li class="flex items-center space-x-2">
                            <i class="ti-check text-purple-600 text-xs"></i>
                            <span>Evaluate empirical evidence critically</span>
                        </li>
                    </ul>
                </div>
                <div class="pt-5 mt-6 border-t border-slate-200/60 flex items-center text-[11px] font-bold text-purple-700">
                    <span>Scientific Rigor &amp; Investigation &rarr;</span>
                </div>
            </div>

            <!-- Pillar B: Innovation and Invention -->
            <div class="rounded-3xl border border-slate-200 p-6 sm:p-7 bg-slate-50/60 hover:bg-white hover:shadow-xl transition-all duration-300 flex flex-col justify-between group">
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="w-12 h-12 rounded-2xl bg-blue-100 text-blue-700 flex items-center justify-center font-black text-xl">02</span>
                        <span class="text-[10px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-full bg-blue-50 text-blue-700">Invention</span>
                    </div>
                    <h3 class="text-lg font-black text-slate-900 group-hover:text-blue-700 transition-colors">
                        Innovation &amp; Invention
                    </h3>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Identifying real-world friction, exploring unconventional solutions, developing working prototypes, iterating through failure, and translating ideas into physical solutions.
                    </p>
                    <ul class="space-y-2 pt-2 border-t border-slate-200/80 text-xs text-slate-700">
                        <li class="flex items-center space-x-2">
                            <i class="ti-check text-blue-600 text-xs"></i>
                            <span>Rapid hardware &amp; software prototyping</span>
                        </li>
                        <li class="flex items-center space-x-2">
                            <i class="ti-check text-blue-600 text-xs"></i>
                            <span>Iterative testing and resilience</span>
                        </li>
                        <li class="flex items-center space-x-2">
                            <i class="ti-check text-blue-600 text-xs"></i>
                            <span>Transforming concepts into working reality</span>
                        </li>
                    </ul>
                </div>
                <div class="pt-5 mt-6 border-t border-slate-200/60 flex items-center text-[11px] font-bold text-blue-700">
                    <span>Engineering Mindset &rarr;</span>
                </div>
            </div>

            <!-- Pillar C: Entrepreneurship & Job Creation -->
            <div class="rounded-3xl border border-slate-200 p-6 sm:p-7 bg-slate-50/60 hover:bg-white hover:shadow-xl transition-all duration-300 flex flex-col justify-between group">
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="w-12 h-12 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-black text-xl">03</span>
                        <span class="text-[10px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700">Enterprise</span>
                    </div>
                    <h3 class="text-lg font-black text-slate-900 group-hover:text-emerald-700 transition-colors">
                        Entrepreneurship &amp; Job Creation
                    </h3>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Understanding genuine human needs, studying markets, testing business viability, and learning how sustainable enterprises build economic value and provide employment.
                    </p>
                    <ul class="space-y-2 pt-2 border-t border-slate-200/80 text-xs text-slate-700">
                        <li class="flex items-center space-x-2">
                            <i class="ti-check text-emerald-600 text-xs"></i>
                            <span>Identifying real market opportunities</span>
                        </li>
                        <li class="flex items-center space-x-2">
                            <i class="ti-check text-emerald-600 text-xs"></i>
                            <span>Designing sustainable business models</span>
                        </li>
                        <li class="flex items-center space-x-2">
                            <i class="ti-check text-emerald-600 text-xs"></i>
                            <span>Creating jobs rather than competing for them</span>
                        </li>
                    </ul>
                </div>
                <div class="pt-5 mt-6 border-t border-slate-200/60 flex items-center text-[11px] font-bold text-emerald-700">
                    <span>Value &amp; Venture Creation &rarr;</span>
                </div>
            </div>

            <!-- Pillar D: Advanced Technology -->
            <div class="rounded-3xl border border-slate-200 p-6 sm:p-7 bg-slate-50/60 hover:bg-white hover:shadow-xl transition-all duration-300 flex flex-col justify-between group">
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="w-12 h-12 rounded-2xl bg-indigo-100 text-indigo-700 flex items-center justify-center font-black text-xl">04</span>
                        <span class="text-[10px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-full bg-indigo-50 text-indigo-700">Technology</span>
                    </div>
                    <h3 class="text-lg font-black text-slate-900 group-hover:text-indigo-700 transition-colors">
                        Advanced Technology
                    </h3>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Students do not merely use technology. They learn Artificial Intelligence, software engineering, robotics, biotechnology, data science, and emerging disciplines.
                    </p>
                    <ul class="space-y-2 pt-2 border-t border-slate-200/80 text-xs text-slate-700">
                        <li class="flex items-center space-x-2">
                            <i class="ti-check text-indigo-600 text-xs"></i>
                            <span>Artificial Intelligence &amp; Machine Learning</span>
                        </li>
                        <li class="flex items-center space-x-2">
                            <i class="ti-check text-indigo-600 text-xs"></i>
                            <span>Biotechnology &amp; Genetic Engineering</span>
                        </li>
                        <li class="flex items-center space-x-2">
                            <i class="ti-check text-indigo-600 text-xs"></i>
                            <span>Robotics, Automation &amp; Embedded Systems</span>
                        </li>
                    </ul>
                </div>
                <div class="pt-5 mt-6 border-t border-slate-200/60 flex items-center text-[11px] font-bold text-indigo-700">
                    <span>Building With Emerging Tech &rarr;</span>
                </div>
            </div>

            <!-- Pillar E: Problem-Solving & Critical Thinking -->
            <div class="rounded-3xl border border-slate-200 p-6 sm:p-7 bg-slate-50/60 hover:bg-white hover:shadow-xl transition-all duration-300 flex flex-col justify-between group">
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="w-12 h-12 rounded-2xl bg-amber-100 text-amber-800 flex items-center justify-center font-black text-xl">05</span>
                        <span class="text-[10px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-full bg-amber-50 text-amber-800">Reasoning</span>
                    </div>
                    <h3 class="text-lg font-black text-slate-900 group-hover:text-amber-800 transition-colors">
                        Problem-Solving &amp; Critical Thinking
                    </h3>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Deconstructing tangled problems into manageable components, challenging unspoken assumptions, synthesizing cross-subject insights, and making evidence-based decisions.
                    </p>
                    <ul class="space-y-2 pt-2 border-t border-slate-200/80 text-xs text-slate-700">
                        <li class="flex items-center space-x-2">
                            <i class="ti-check text-amber-700 text-xs"></i>
                            <span>First-principles logical reasoning</span>
                        </li>
                        <li class="flex items-center space-x-2">
                            <i class="ti-check text-amber-700 text-xs"></i>
                            <span>Cross-disciplinary knowledge synthesis</span>
                        </li>
                        <li class="flex items-center space-x-2">
                            <i class="ti-check text-amber-700 text-xs"></i>
                            <span>Evaluation of alternative explanations</span>
                        </li>
                    </ul>
                </div>
                <div class="pt-5 mt-6 border-t border-slate-200/60 flex items-center text-[11px] font-bold text-amber-800">
                    <span>Cognitive Rigor &rarr;</span>
                </div>
            </div>

            <!-- Pillar F: Communication & Collaboration -->
            <div class="rounded-3xl border border-slate-200 p-6 sm:p-7 bg-slate-50/60 hover:bg-white hover:shadow-xl transition-all duration-300 flex flex-col justify-between group">
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="w-12 h-12 rounded-2xl bg-rose-100 text-rose-700 flex items-center justify-center font-black text-xl">06</span>
                        <span class="text-[10px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-full bg-rose-50 text-rose-700">Leadership</span>
                    </div>
                    <h3 class="text-lg font-black text-slate-900 group-hover:text-rose-700 transition-colors">
                        Communication &amp; Collaboration
                    </h3>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Articulating complex discoveries with conviction, defending arguments with solid data, thriving in diverse multidisciplinary teams, and leading collaborative projects.
                    </p>
                    <ul class="space-y-2 pt-2 border-t border-slate-200/80 text-xs text-slate-700">
                        <li class="flex items-center space-x-2">
                            <i class="ti-check text-rose-600 text-xs"></i>
                            <span>Defending findings before expert panels</span>
                        </li>
                        <li class="flex items-center space-x-2">
                            <i class="ti-check text-rose-600 text-xs"></i>
                            <span>Team leadership &amp; project orchestration</span>
                        </li>
                        <li class="flex items-center space-x-2">
                            <i class="ti-check text-rose-600 text-xs"></i>
                            <span>Writing precise technical &amp; pitch briefs</span>
                        </li>
                    </ul>
                </div>
                <div class="pt-5 mt-6 border-t border-slate-200/60 flex items-center text-[11px] font-bold text-rose-700">
                    <span>Expressive Mastery &rarr;</span>
                </div>
            </div>

        </div>

    </div>
</section>

<!-- ========================================================================= -->
<!-- 4. SHOW THE LEARNING TRANSFORMATION (The 8-Stage Cycle)                   -->
<!-- ========================================================================= -->
<section class="py-16 sm:py-24 bg-slate-900 text-white relative overflow-hidden">
    <!-- Background Glows -->
    <div class="absolute top-1/2 left-1/4 w-[500px] h-[500px] bg-purple-600/10 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute bottom-0 right-10 w-[450px] h-[450px] bg-blue-600/10 rounded-full blur-3xl pointer-events-none"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        
        <div class="max-w-3xl mx-auto text-center space-y-3 mb-16">
            <span class="text-xs font-extrabold uppercase tracking-widest text-emerald-400 bg-emerald-950/80 px-3 py-1 rounded-full border border-emerald-800">
                The Student Evolution
            </span>
            <h2 class="text-2xl sm:text-4xl font-black tracking-tight text-white">
                The Discovery-to-Creation Cycle
            </h2>
            <p class="text-sm sm:text-base text-slate-300">
                How an inquisitive young mind develops from asking raw questions into a mature builder who creates tangible impact for humanity.
            </p>
        </div>

        <!-- 8-Stage Interactive Progression Flow -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
            
            <!-- Stage 1 -->
            <div class="p-5 rounded-2xl bg-slate-800/80 border border-slate-700 hover:border-purple-500 transition-all duration-200 space-y-2">
                <div class="flex items-center justify-between text-xs font-mono text-purple-400">
                    <span>STAGE 01</span>
                    <span>🌱</span>
                </div>
                <h4 class="text-base font-black text-white">CURIOSITY</h4>
                <p class="text-xs text-slate-300 leading-relaxed">
                    Observing everyday anomalies, natural phenomena, and friction in the physical and digital world.
                </p>
            </div>

            <!-- Stage 2 -->
            <div class="p-5 rounded-2xl bg-slate-800/80 border border-slate-700 hover:border-purple-500 transition-all duration-200 space-y-2">
                <div class="flex items-center justify-between text-xs font-mono text-purple-400">
                    <span>STAGE 02</span>
                    <span>❓</span>
                </div>
                <h4 class="text-base font-black text-white">QUESTIONING</h4>
                <p class="text-xs text-slate-300 leading-relaxed">
                    Formulating precise, testable questions that move beyond "what is" into "why is it this way and how could it be better?"
                </p>
            </div>

            <!-- Stage 3 -->
            <div class="p-5 rounded-2xl bg-slate-800/80 border border-slate-700 hover:border-purple-500 transition-all duration-200 space-y-2">
                <div class="flex items-center justify-between text-xs font-mono text-blue-400">
                    <span>STAGE 03</span>
                    <span>🔎</span>
                </div>
                <h4 class="text-base font-black text-white">DISCOVERY</h4>
                <p class="text-xs text-slate-300 leading-relaxed">
                    Gathering data, reviewing past research, exploring scientific principles, and finding unexplored territory.
                </p>
            </div>

            <!-- Stage 4 -->
            <div class="p-5 rounded-2xl bg-slate-800/80 border border-slate-700 hover:border-blue-500 transition-all duration-200 space-y-2">
                <div class="flex items-center justify-between text-xs font-mono text-blue-400">
                    <span>STAGE 04</span>
                    <span>🧩</span>
                </div>
                <h4 class="text-base font-black text-white">UNDERSTANDING</h4>
                <p class="text-xs text-slate-300 leading-relaxed">
                    Deep mastery of underlying mathematical laws, physics mechanics, biochemical processes, and system constraints.
                </p>
            </div>

            <!-- Stage 5 -->
            <div class="p-5 rounded-2xl bg-slate-800/80 border border-slate-700 hover:border-emerald-500 transition-all duration-200 space-y-2">
                <div class="flex items-center justify-between text-xs font-mono text-emerald-400">
                    <span>STAGE 05</span>
                    <span>🧪</span>
                </div>
                <h4 class="text-base font-black text-white">EXPERIMENTATION</h4>
                <p class="text-xs text-slate-300 leading-relaxed">
                    Testing hypotheses in physical and simulation laboratories; capturing data, measuring variables, and learning from failure.
                </p>
            </div>

            <!-- Stage 6 -->
            <div class="p-5 rounded-2xl bg-slate-800/80 border border-slate-700 hover:border-emerald-500 transition-all duration-200 space-y-2">
                <div class="flex items-center justify-between text-xs font-mono text-emerald-400">
                    <span>STAGE 06</span>
                    <span>🛠️</span>
                </div>
                <h4 class="text-base font-black text-white">INVENTION</h4>
                <p class="text-xs text-slate-300 leading-relaxed">
                    Synthesizing knowledge into novel hardware prototypes, software solutions, algorithms, and biological devices.
                </p>
            </div>

            <!-- Stage 7 -->
            <div class="p-5 rounded-2xl bg-slate-800/80 border border-slate-700 hover:border-teal-500 transition-all duration-200 space-y-2">
                <div class="flex items-center justify-between text-xs font-mono text-teal-400">
                    <span>STAGE 07</span>
                    <span>📈</span>
                </div>
                <h4 class="text-base font-black text-white">ENTREPRENEURSHIP</h4>
                <p class="text-xs text-slate-300 leading-relaxed">
                    Packaging solutions into viable products; understanding unit economics, user psychology, supply chains, and market adoption.
                </p>
            </div>

            <!-- Stage 8 -->
            <div class="p-5 rounded-2xl bg-slate-800/80 border border-slate-700 hover:border-teal-500 transition-all duration-200 space-y-2">
                <div class="flex items-center justify-between text-xs font-mono text-teal-400">
                    <span>STAGE 08</span>
                    <span>🌍</span>
                </div>
                <h4 class="text-base font-black text-white">IMPACT</h4>
                <p class="text-xs text-slate-300 leading-relaxed">
                    Deploying the solution in real communities to improve human health, clean the environment, and create sustainable livelihoods.
                </p>
            </div>

        </div>

        <!-- Visual Flow Line Indicator -->
        <div class="mt-10 p-4 rounded-2xl bg-slate-800/50 border border-slate-700/80 flex items-center justify-between overflow-x-auto text-[11px] font-mono text-slate-300">
            <span class="text-purple-400 font-bold">CURIOSITY</span>
            <span>&rarr;</span>
            <span>QUESTIONING</span>
            <span>&rarr;</span>
            <span class="text-blue-400 font-bold">DISCOVERY</span>
            <span>&rarr;</span>
            <span>UNDERSTANDING</span>
            <span>&rarr;</span>
            <span class="text-emerald-400 font-bold">EXPERIMENTATION</span>
            <span>&rarr;</span>
            <span>INVENTION</span>
            <span>&rarr;</span>
            <span class="text-teal-400 font-bold">ENTREPRENEURSHIP</span>
            <span>&rarr;</span>
            <span class="text-amber-400 font-black">IMPACT</span>
        </div>

    </div>
</section>

<!-- ========================================================================= -->
<!-- 5. REAL-WORLD LEARNING: "From Classroom Knowledge to Real-World Impact"   -->
<!-- ========================================================================= -->
<section class="py-16 sm:py-24 bg-white border-b border-slate-200/80">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="max-w-3xl mx-auto text-center space-y-3 mb-16">
            <span class="text-xs font-extrabold uppercase tracking-widest text-blue-700 bg-blue-50 px-3 py-1 rounded-full border border-blue-200">
                Applied Horizon
            </span>
            <h2 class="text-2xl sm:text-4xl font-black text-slate-900 tracking-tight">
                From Classroom Knowledge to Real-World Impact
            </h2>
            <p class="text-sm sm:text-base text-slate-600">
                These represent the concrete frontiers our students explore — testing scientific theory against real planetary, medical, and technological challenges.
            </p>
            <div class="text-[11px] font-semibold text-slate-500 italic">
                * Examples of the institution's intended learning direction and exploration tracks.
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8">
            
            <!-- Project 1: Environmental Biology -->
            <div class="rounded-3xl border border-slate-200 overflow-hidden shadow-xs hover:shadow-xl transition-all duration-300 flex flex-col bg-white">
                <div class="h-48 overflow-hidden relative">
                    <img src="{{ asset('images/action-science-experiment.jpg') }}" alt="Biological research" class="w-full h-full object-cover hover:scale-105 transition-transform duration-500">
                    <span class="absolute top-3 right-3 text-[10px] font-bold uppercase tracking-wider bg-slate-900/90 text-white px-2.5 py-1 rounded-full backdrop-blur-xs">
                        Biotechnology
                    </span>
                </div>
                <div class="p-6 flex-1 flex flex-col justify-between space-y-4">
                    <div>
                        <div class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider mb-1">Ecological Science</div>
                        <h3 class="text-base font-extrabold text-slate-900 leading-snug">
                            Biological Solutions to Environmental Waste
                        </h3>
                        <p class="text-xs text-slate-600 mt-2 leading-relaxed">
                            Investigating microbial degradation of plastic residues, enzymatic wastewater filtration, and Bacillus Cell Model bio-absorbents.
                        </p>
                    </div>
                    <div class="pt-3 border-t border-slate-100 text-[11px] font-semibold text-slate-500 flex items-center justify-between">
                        <span>Pillar: Discovery &amp; Bio-Sciences</span>
                        <span class="text-emerald-600 font-bold">DNA Analysis</span>
                    </div>
                </div>
            </div>

            <!-- Project 2: AI in Healthcare & Agriculture -->
            <div class="rounded-3xl border border-slate-200 overflow-hidden shadow-xs hover:shadow-xl transition-all duration-300 flex flex-col bg-white">
                <div class="h-48 overflow-hidden relative">
                    <img src="{{ asset('images/action-technology-coding.jpg') }}" alt="AI and software coding" class="w-full h-full object-cover hover:scale-105 transition-transform duration-500">
                    <span class="absolute top-3 right-3 text-[10px] font-bold uppercase tracking-wider bg-slate-900/90 text-white px-2.5 py-1 rounded-full backdrop-blur-xs">
                        Artificial Intelligence
                    </span>
                </div>
                <div class="p-6 flex-1 flex flex-col justify-between space-y-4">
                    <div>
                        <div class="text-[10px] font-bold text-blue-600 uppercase tracking-wider mb-1">Applied Machine Learning</div>
                        <h3 class="text-base font-extrabold text-slate-900 leading-snug">
                            AI Diagnostic Models in Healthcare &amp; Crops
                        </h3>
                        <p class="text-xs text-slate-600 mt-2 leading-relaxed">
                            Training convolutional neural networks on crop leaf disease datasets and exploring computer-vision assistance for rural clinics.
                        </p>
                    </div>
                    <div class="pt-3 border-t border-slate-100 text-[11px] font-semibold text-slate-500 flex items-center justify-between">
                        <span>Pillar: Advanced Technology</span>
                        <span class="text-blue-600 font-bold">PyTorch &amp; Sensors</span>
                    </div>
                </div>
            </div>

            <!-- Project 3: Clean Energy Systems -->
            <div class="rounded-3xl border border-slate-200 overflow-hidden shadow-xs hover:shadow-xl transition-all duration-300 flex flex-col bg-white">
                <div class="h-48 overflow-hidden relative">
                    <img src="{{ asset('images/action-creative-project.jpg') }}" alt="Clean energy prototypes" class="w-full h-full object-cover hover:scale-105 transition-transform duration-500">
                    <span class="absolute top-3 right-3 text-[10px] font-bold uppercase tracking-wider bg-slate-900/90 text-white px-2.5 py-1 rounded-full backdrop-blur-xs">
                        Renewables
                    </span>
                </div>
                <div class="p-6 flex-1 flex flex-col justify-between space-y-4">
                    <div>
                        <div class="text-[10px] font-bold text-amber-600 uppercase tracking-wider mb-1">Energy Physics</div>
                        <h3 class="text-base font-extrabold text-slate-900 leading-snug">
                            Renewable Micro-Grids &amp; Storage Chemistry
                        </h3>
                        <p class="text-xs text-slate-600 mt-2 leading-relaxed">
                            Testing decentralized solar capture efficiency, solid-state battery thermodynamics, and intelligent load-distribution microcontrollers.
                        </p>
                    </div>
                    <div class="pt-3 border-t border-slate-100 text-[11px] font-semibold text-slate-500 flex items-center justify-between">
                        <span>Pillar: Physics &amp; Engineering</span>
                        <span class="text-amber-600 font-bold">Thermodynamics</span>
                    </div>
                </div>
            </div>

            <!-- Project 4: Everyday Software Solutions -->
            <div class="rounded-3xl border border-slate-200 overflow-hidden shadow-xs hover:shadow-xl transition-all duration-300 flex flex-col bg-white">
                <div class="h-48 overflow-hidden relative">
                    <img src="{{ asset('images/action-collaborative.jpg') }}" alt="Collaborative software design" class="w-full h-full object-cover hover:scale-105 transition-transform duration-500">
                    <span class="absolute top-3 right-3 text-[10px] font-bold uppercase tracking-wider bg-slate-900/90 text-white px-2.5 py-1 rounded-full backdrop-blur-xs">
                        Software Systems
                    </span>
                </div>
                <div class="p-6 flex-1 flex flex-col justify-between space-y-4">
                    <div>
                        <div class="text-[10px] font-bold text-indigo-600 uppercase tracking-wider mb-1">Civic Tech</div>
                        <h3 class="text-base font-extrabold text-slate-900 leading-snug">
                            Decentralized Software for Local Logistics
                        </h3>
                        <p class="text-xs text-slate-600 mt-2 leading-relaxed">
                            Building real-time web applications for community tool libraries, clean water delivery tracking, and transparent municipal feedback.
                        </p>
                    </div>
                    <div class="pt-3 border-t border-slate-100 text-[11px] font-semibold text-slate-500 flex items-center justify-between">
                        <span>Pillar: Invention &amp; Code</span>
                        <span class="text-indigo-600 font-bold">Full-Stack Web</span>
                    </div>
                </div>
            </div>

            <!-- Project 5: Food Science & Cellular Agriculture -->
            <div class="rounded-3xl border border-slate-200 overflow-hidden shadow-xs hover:shadow-xl transition-all duration-300 flex flex-col bg-white">
                <div class="h-48 overflow-hidden relative">
                    <img src="{{ asset('images/division-science.jpg') }}" alt="Food science lab" class="w-full h-full object-cover hover:scale-105 transition-transform duration-500">
                    <span class="absolute top-3 right-3 text-[10px] font-bold uppercase tracking-wider bg-slate-900/90 text-white px-2.5 py-1 rounded-full backdrop-blur-xs">
                        Nutritional Bio
                    </span>
                </div>
                <div class="p-6 flex-1 flex flex-col justify-between space-y-4">
                    <div>
                        <div class="text-[10px] font-bold text-teal-600 uppercase tracking-wider mb-1">Biotechnology</div>
                        <h3 class="text-base font-extrabold text-slate-900 leading-snug">
                            Food Science &amp; Controlled Agriculture
                        </h3>
                        <p class="text-xs text-slate-600 mt-2 leading-relaxed">
                            Investigating nutrient formulation, hydroponic vertical automation, and cellular food preservation without synthetic toxins.
                        </p>
                    </div>
                    <div class="pt-3 border-t border-slate-100 text-[11px] font-semibold text-slate-500 flex items-center justify-between">
                        <span>Pillar: Pure Sciences &amp; Nutrition</span>
                        <span class="text-teal-600 font-bold">Biochemistry</span>
                    </div>
                </div>
            </div>

            <!-- Project 6: Hardware Inventions & Market Testing -->
            <div class="rounded-3xl border border-slate-200 overflow-hidden shadow-xs hover:shadow-xl transition-all duration-300 flex flex-col bg-white">
                <div class="h-48 overflow-hidden relative">
                    <img src="{{ asset('images/action-student-presentation.jpg') }}" alt="Student product presentation" class="w-full h-full object-cover hover:scale-105 transition-transform duration-500">
                    <span class="absolute top-3 right-3 text-[10px] font-bold uppercase tracking-wider bg-slate-900/90 text-white px-2.5 py-1 rounded-full backdrop-blur-xs">
                        Market Ventures
                    </span>
                </div>
                <div class="p-6 flex-1 flex flex-col justify-between space-y-4">
                    <div>
                        <div class="text-[10px] font-bold text-purple-600 uppercase tracking-wider mb-1">Venture Formulation</div>
                        <h3 class="text-base font-extrabold text-slate-900 leading-snug">
                            Hardware Inventions to Market Testing
                        </h3>
                        <p class="text-xs text-slate-600 mt-2 leading-relaxed">
                            Prototyping automated environmental sensor arrays and presenting unit economics, target user segments, and venture viability.
                        </p>
                    </div>
                    <div class="pt-3 border-t border-slate-100 text-[11px] font-semibold text-slate-500 flex items-center justify-between">
                        <span>Pillar: Entrepreneurship</span>
                        <span class="text-purple-600 font-bold">Venture Pitch</span>
                    </div>
                </div>
            </div>

        </div>

    </div>
</section>

<!-- ========================================================================= -->
<!-- 6. ACADEMIC FOUNDATIONS: Strong Fundamentals as Tools for Creation        -->
<!-- ========================================================================= -->
<section class="py-16 sm:py-24 bg-slate-50 border-b border-slate-200/80">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="max-w-3xl mx-auto text-center space-y-3 mb-16">
            <span class="text-xs font-extrabold uppercase tracking-widest text-indigo-700 bg-indigo-50 px-3 py-1 rounded-full border border-indigo-200">
                Rigorous Foundations
            </span>
            <h2 class="text-2xl sm:text-4xl font-black text-slate-900 tracking-tight">
                Academic Excellence Reimagined as Tools of Power
            </h2>
            <p class="text-sm sm:text-base text-slate-600">
                True innovation requires unyielding mastery of core fundamentals. At our institution, traditional disciplines are not dead formulas — they are instruments to decode and alter reality.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 sm:gap-5">
            
            <!-- Math -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-2xs space-y-3">
                <div class="text-2xl">📐</div>
                <h4 class="text-sm font-black text-slate-900">Mathematics</h4>
                <div class="text-[10px] font-bold text-purple-700 uppercase tracking-wider">Modelling &amp; Algorithms</div>
                <p class="text-xs text-slate-600 leading-relaxed">
                    Calculus, linear algebra, and discrete math become engines for machine learning, cryptographic trust, and financial modeling.
                </p>
            </div>

            <!-- Physics -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-2xs space-y-3">
                <div class="text-2xl">⚡</div>
                <h4 class="text-sm font-black text-slate-900">Physics</h4>
                <div class="text-[10px] font-bold text-blue-700 uppercase tracking-wider">Energy &amp; Mechanics</div>
                <p class="text-xs text-slate-600 leading-relaxed">
                    Electromagnetism, quantum mechanics, and fluid dynamics transform into aerospace engineering, robotics, and clean power.
                </p>
            </div>

            <!-- Chemistry -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-2xs space-y-3">
                <div class="text-2xl">🧪</div>
                <h4 class="text-sm font-black text-slate-900">Chemistry</h4>
                <div class="text-[10px] font-bold text-teal-700 uppercase tracking-wider">Materials &amp; Medicine</div>
                <p class="text-xs text-slate-600 leading-relaxed">
                    Organic synthesis and molecular kinetics unlock new battery chemistries, sustainable bio-polymers, and therapeutics.
                </p>
            </div>

            <!-- Biology -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-2xs space-y-3">
                <div class="text-2xl">🧬</div>
                <h4 class="text-sm font-black text-slate-900">Biology</h4>
                <div class="text-[10px] font-bold text-emerald-700 uppercase tracking-wider">Biotechnology &amp; Life</div>
                <p class="text-xs text-slate-600 leading-relaxed">
                    Cellular physiology, genetics, and ecology fuel recombinant DNA technologies, synthetic biology, and regenerative medicine.
                </p>
            </div>

            <!-- Computer Science -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-2xs space-y-3">
                <div class="text-2xl">💻</div>
                <h4 class="text-sm font-black text-slate-900">Computer Science</h4>
                <div class="text-[10px] font-bold text-indigo-700 uppercase tracking-wider">AI &amp; Automation</div>
                <p class="text-xs text-slate-600 leading-relaxed">
                    Data structures, computational logic, and distributed computing enable students to build systems that automate friction away.
                </p>
            </div>

        </div>

    </div>
</section>

<!-- ========================================================================= -->
<!-- 7. THE FUTURE WE WANT TO CREATE (Diverse Pathways)                        -->
<!-- ========================================================================= -->
<section class="py-16 sm:py-24 bg-white border-b border-slate-200/80">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="max-w-3xl mx-auto text-center space-y-3 mb-16">
            <span class="text-xs font-extrabold uppercase tracking-widest text-purple-700 bg-purple-50 px-3 py-1 rounded-full border border-purple-200">
                Diverse Destinies
            </span>
            <h2 class="text-2xl sm:text-4xl font-black text-slate-900 tracking-tight">
                Not Everyone Must Be an Entrepreneur. <br>
                Everyone Must Discover Their Own Path.
            </h2>
            <p class="text-sm sm:text-base text-slate-600 leading-relaxed">
                "We don't want every student to follow the same path. We want every student to discover their abilities, explore possibilities and develop the confidence to create their own path."
            </p>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 sm:gap-6">
            
            <div class="p-5 rounded-2xl border border-slate-200 bg-slate-50/50 hover:bg-white hover:shadow-md transition text-center space-y-2">
                <div class="text-3xl">🔬</div>
                <h4 class="text-xs sm:text-sm font-black text-slate-900">Scientists &amp; Researchers</h4>
                <p class="text-[11px] text-slate-500">Investigating the cosmos, genomics, and fundamental laws.</p>
            </div>

            <div class="p-5 rounded-2xl border border-slate-200 bg-slate-50/50 hover:bg-white hover:shadow-md transition text-center space-y-2">
                <div class="text-3xl">🛠️</div>
                <h4 class="text-xs sm:text-sm font-black text-slate-900">Engineers &amp; Inventors</h4>
                <p class="text-[11px] text-slate-500">Designing machines, robotics, and clean physical infrastructure.</p>
            </div>

            <div class="p-5 rounded-2xl border border-slate-200 bg-slate-50/50 hover:bg-white hover:shadow-md transition text-center space-y-2">
                <div class="text-3xl">🌐</div>
                <h4 class="text-xs sm:text-sm font-black text-slate-900">Technology Developers</h4>
                <p class="text-[11px] text-slate-500">Writing world-scale distributed software and operating systems.</p>
            </div>

            <div class="p-5 rounded-2xl border border-slate-200 bg-slate-50/50 hover:bg-white hover:shadow-md transition text-center space-y-2">
                <div class="text-3xl">🚀</div>
                <h4 class="text-xs sm:text-sm font-black text-slate-900">Founders &amp; Entrepreneurs</h4>
                <p class="text-[11px] text-slate-500">Building sustainable companies that create livelihoods and products.</p>
            </div>

            <div class="p-5 rounded-2xl border border-slate-200 bg-slate-50/50 hover:bg-white hover:shadow-md transition text-center space-y-2">
                <div class="text-3xl">🧬</div>
                <h4 class="text-xs sm:text-sm font-black text-slate-900">Biotechnology Innovators</h4>
                <p class="text-[11px] text-slate-500">Engineering bio-pharmaceuticals and synthetic biological tools.</p>
            </div>

            <div class="p-5 rounded-2xl border border-slate-200 bg-slate-50/50 hover:bg-white hover:shadow-md transition text-center space-y-2">
                <div class="text-3xl">🤖</div>
                <h4 class="text-xs sm:text-sm font-black text-slate-900">AI Researchers</h4>
                <p class="text-[11px] text-slate-500">Developing artificial intelligence architectures safely and ethically.</p>
            </div>

            <div class="p-5 rounded-2xl border border-slate-200 bg-slate-50/50 hover:bg-white hover:shadow-md transition text-center space-y-2">
                <div class="text-3xl">🎨</div>
                <h4 class="text-xs sm:text-sm font-black text-slate-900">Product Designers</h4>
                <p class="text-[11px] text-slate-500">Harmonizing ergonomics, human empathy, and hardware form factors.</p>
            </div>

            <div class="p-5 rounded-2xl border border-slate-200 bg-slate-50/50 hover:bg-white hover:shadow-md transition text-center space-y-2">
                <div class="text-3xl">⚡</div>
                <h4 class="text-xs sm:text-sm font-black text-slate-900">Industry Creators</h4>
                <p class="text-[11px] text-slate-500">Leading multidisciplinary breakthroughs in fields that don't exist yet.</p>
            </div>

        </div>

    </div>
</section>

<!-- ========================================================================= -->
<!-- 8. THE FOUNDER'S VISION (Editorial Style)                                 -->
<!-- ========================================================================= -->
<section class="py-16 sm:py-24 bg-slate-900 text-white relative">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="bg-slate-800/80 rounded-3xl p-8 sm:p-12 border border-slate-700 shadow-2xl space-y-6">
            <span class="text-xs font-bold font-mono tracking-widest text-emerald-400 uppercase">
                THE FOUNDER'S VISION
            </span>
            <h2 class="text-2xl sm:text-4xl font-black text-white tracking-tight">
                An Education System That Creates Possibilities.
            </h2>
            
            <div class="space-y-4 text-sm sm:text-base text-slate-300 leading-relaxed font-normal">
                <p>
                    For over a century, mass education has operated on an industrial paradigm: standardize children into predictable employee profiles, measure them primarily by their ability to recall static facts under timed stress, and congratulate them when they fit neatly into pre-determined career ladders.
                </p>
                <p>
                    We built this institution because that century is over. In a world reshaped by machine intelligence, ecological urgency, and accelerating technical innovation, the greatest danger is not that a student fails to memorize a formula — it is that they spend twelve years learning never to ask an unscripted question.
                </p>
                <p class="italic text-emerald-300 font-medium">
                    "We do not view education as a conveyor belt to produce employees. We view it as an intellectual forge to nurture discoverers, inventors, and job creators."
                </p>
                <p>
                    Our students master the rigorous fundamentals of mathematics, physics, chemistry, biology, and computer science. But from day one, those subjects become active instruments to build prototypes, evaluate scientific evidence, and design solutions that create economic and societal value.
                </p>
            </div>

            <div class="pt-6 border-t border-slate-700 flex items-center justify-between">
                <div>
                    <div class="text-sm font-bold text-white">{{ $school['principal'] ?? 'Prithviraj & Academic Faculty Council' }}</div>
                    <div class="text-xs text-slate-400">Founders &bull; {{ $school['name'] ?? 'Prime Arc Academy' }}</div>
                </div>
                <div class="text-xs font-mono text-emerald-400 bg-emerald-950/60 px-3 py-1 rounded-full border border-emerald-800">
                    A Non-Conventional Commitment
                </div>
            </div>
        </div>

    </div>
</section>

<!-- ========================================================================= -->
<!-- 9. TRUST, SAFETY & PARENT CONFIDENCE                                      -->
<!-- ========================================================================= -->
<section class="py-16 sm:py-24 bg-white border-b border-slate-200/80">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-center">
            
            <!-- Left 6 cols: Trust Narrative -->
            <div class="lg:col-span-6 space-y-6">
                <span class="text-xs font-extrabold uppercase tracking-widest text-emerald-700 bg-emerald-50 px-3 py-1 rounded-full border border-emerald-200">
                    Parent Confidence &amp; Security
                </span>
                <h2 class="text-2xl sm:text-4xl font-black text-slate-900 tracking-tight">
                    Ambitious Vision Built on Responsible Discipline.
                </h2>
                <p class="text-sm text-slate-600 leading-relaxed">
                    We believe bold innovation and responsible academic governance must reinforce each other. Parents can trust our disciplined environment, verified curricula, transparent communication, and student safety protocols.
                </p>

                <div class="space-y-4">
                    <div class="flex items-start space-x-3.5">
                        <div class="w-8 h-8 rounded-xl bg-purple-100 text-purple-700 flex items-center justify-center font-bold text-sm flex-shrink-0 mt-0.5">
                            🛡️
                        </div>
                        <div>
                            <h4 class="text-xs font-extrabold text-slate-900">Zero-Compromise Campus Safety &amp; Role Security</h4>
                            <p class="text-xs text-slate-600 mt-0.5">Automated roll-call attendance gates verify presence with instant parent notification telemetry.</p>
                        </div>
                    </div>

                    <div class="flex items-start space-x-3.5">
                        <div class="w-8 h-8 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-sm flex-shrink-0 mt-0.5">
                            📊
                        </div>
                        <div>
                            <h4 class="text-xs font-extrabold text-slate-900">Transparent Academic Telemetry</h4>
                            <p class="text-xs text-slate-600 mt-0.5">Real-time guardian portal tracking daily DPP progress, cognitive Bloom's mastery, and continuous feedback.</p>
                        </div>
                    </div>

                    <div class="flex items-start space-x-3.5">
                        <div class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-sm flex-shrink-0 mt-0.5">
                            👨‍🏫
                        </div>
                        <div>
                            <h4 class="text-xs font-extrabold text-slate-900">World-Class Faculty Mentors</h4>
                            <p class="text-xs text-slate-600 mt-0.5">Researchers, scientists, and master teachers who guide students through rigorous intellectual inquiries.</p>
                        </div>
                    </div>
                </div>

                <div class="pt-4 flex items-center space-x-3 text-xs font-bold text-slate-800">
                    <span class="text-emerald-600">✓ Transparent Admissions</span>
                    <span>&bull;</span>
                    <span class="text-blue-600">✓ Verified Credentials</span>
                    <span>&bull;</span>
                    <span class="text-purple-600">✓ Safe Environment</span>
                </div>
            </div>

            <!-- Right 6 cols: Photo of Genuine Parent & Student Mentorship -->
            <div class="lg:col-span-6">
                <div class="rounded-3xl overflow-hidden shadow-xl border-4 border-white bg-slate-100 aspect-[4/3] relative">
                    <img src="{{ asset('images/parent-confidence.jpg') }}" alt="Mentorship, trust, and guidance" class="w-full h-full object-cover">
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-900/60 to-transparent"></div>
                    <div class="absolute bottom-6 left-6 right-6 text-white text-xs font-medium">
                        "Here, your child is never a test statistic. They are a thinker, builder, and future leader."
                    </div>
                </div>
            </div>

        </div>

    </div>
</section>

<!-- ========================================================================= -->
<!-- 10. INTERACTIVE MOTIVATION & DAILY SCIENCE SPARK                          -->
<!-- ========================================================================= -->
<section class="py-12 bg-slate-50 border-b border-slate-200/80">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="p-6 sm:p-8 rounded-3xl bg-white border border-slate-200/80 shadow-md flex flex-col md:flex-row items-center justify-between gap-6" id="spark-container">
            <div class="space-y-2 text-left">
                <div class="inline-flex items-center space-x-2 text-xs font-extrabold text-amber-600 bg-amber-50 px-2.5 py-0.5 rounded-full">
                    <span>⚡ Daily Science Spark</span>
                    <span>&bull;</span>
                    <span>Sparks of Genius</span>
                </div>
                <h3 class="text-base sm:text-lg font-black text-slate-900" id="spark-quote">
                    "{{ $quote['quote'] ?? 'The important thing is not to stop questioning. Curiosity has its own reason for existence.' }}"
                </h3>
                <div class="text-xs text-slate-500 font-semibold" id="spark-author">
                    &mdash; {{ $quote['author'] ?? 'Albert Einstein' }} &bull; Aim, Ascend, Achieve
                </div>
                <div class="text-[11px] text-slate-600 bg-slate-50 p-2.5 rounded-xl border border-slate-200/60 mt-2 font-mono" id="spark-fact">
                    <strong>Did you know?</strong> {{ $fact['fact'] ?? 'Bacillus subtilis can survive in extreme space radiation, unlocking microbial bio-remediation.' }}
                </div>
            </div>

            <div class="flex-shrink-0">
                <button type="button" 
                        onclick="fetchNextSpark()" 
                        class="px-4 py-2.5 rounded-xl font-bold text-xs bg-amber-500 hover:bg-amber-600 text-white shadow-xs transition flex items-center space-x-1.5 flame-active">
                    <span>Ignite Next Spark 🔥</span>
                </button>
            </div>
        </div>

    </div>
</section>

<!-- ========================================================================= -->
<!-- 11. ADMISSIONS & WORKING CONTACT ENQUIRY FORM                             -->
<!-- ========================================================================= -->
<section id="admissions" class="py-16 sm:py-24 bg-white border-b border-slate-200/80">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
            
            <div class="lg:col-span-5 space-y-5">
                <span class="text-xs font-extrabold uppercase tracking-widest text-purple-700 bg-purple-50 px-3 py-1 rounded-full border border-purple-200">
                    Admissions Now Open
                </span>
                <h2 class="text-2xl sm:text-4xl font-black text-slate-900 tracking-tight">
                    Start Your Child's Journey of Discovery.
                </h2>
                <p class="text-sm text-slate-600 leading-relaxed">
                    Admissions for the upcoming academic session are conducted through personal discovery interviews and creative problem-solving assessments.
                </p>

                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-2 text-xs">
                    <div class="font-bold text-slate-900">Admissions Criteria:</div>
                    <ul class="space-y-1 text-slate-600 list-disc list-inside">
                        <li>Genuine curiosity and willingness to learn</li>
                        <li>Respect for scientific inquiry and collaboration</li>
                        <li>Commitment to academic fundamentals</li>
                        <li>Non-conventional, creative thinking welcome</li>
                    </ul>
                </div>

                <div class="text-xs text-slate-500">
                    Official Admissions Desk: <strong class="text-slate-800">{{ $school['email'] ?: 'Not configured' }}</strong>
                </div>
            </div>

            <!-- Working Admissions / Contact Form -->
            <div class="lg:col-span-7">
                <div class="p-6 sm:p-8 rounded-3xl bg-slate-50 border border-slate-200 shadow-lg space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-200">
                        <h3 class="text-sm sm:text-base font-extrabold text-slate-900">
                            Apply or Inquire for Admission
                        </h3>
                        <span class="text-[10px] font-mono text-purple-700 font-bold bg-purple-100 px-2 py-0.5 rounded-full">
                            Session 2026-2027
                        </span>
                    </div>

                    <form action="{{ route('contact') }}" method="POST" class="space-y-3.5">
                        @csrf

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-600 uppercase tracking-wider mb-1">Applicant / Parent Name *</label>
                                <input type="text" name="name" required placeholder="Full Name" class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white text-xs text-slate-900 focus:ring-2 focus:ring-purple-500 focus:outline-hidden">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-600 uppercase tracking-wider mb-1">Email Address *</label>
                                <input type="email" name="email" required placeholder="email@address.com" class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white text-xs text-slate-900 focus:ring-2 focus:ring-purple-500 focus:outline-hidden">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-600 uppercase tracking-wider mb-1">Contact Phone *</label>
                                <input type="tel" name="phone" required placeholder="+91 98765 43210" class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white text-xs text-slate-900 focus:ring-2 focus:ring-purple-500 focus:outline-hidden">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-600 uppercase tracking-wider mb-1">Inquiry Track *</label>
                                <select name="inquiry_type" required class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white text-xs text-slate-900 focus:ring-2 focus:ring-purple-500 focus:outline-hidden">
                                    <option value="admissions">New Student Admission (Grades 1–12)</option>
                                    <option value="academics">Undergraduate &amp; Advanced Research</option>
                                    <option value="curriculum">Discovery &amp; Invention Curriculum</option>
                                    <option value="general">Campus Tour &amp; General Question</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold text-slate-600 uppercase tracking-wider mb-1">Child's Interests, Aspirations &amp; Questions *</label>
                            <textarea name="message" required rows="3" placeholder="Tell us about what your child loves exploring, inventing, or questioning..." class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white text-xs text-slate-900 focus:ring-2 focus:ring-purple-500 focus:outline-hidden"></textarea>
                        </div>

                        <div class="pt-2 flex items-center justify-between">
                            <span class="text-[10px] text-slate-500">🔒 Information kept strictly confidential.</span>
                            <button type="submit" class="px-6 py-2.5 rounded-full font-bold text-xs text-white bg-purple-700 hover:bg-purple-800 transition shadow-sm">
                                Submit Admission Inquiry
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>

    </div>
</section>

<!-- ========================================================================= -->
<!-- 12. POWERFUL CLOSING MANIFESTO                                            -->
<!-- ========================================================================= -->
<section class="py-20 sm:py-28 bg-slate-950 text-white text-center relative overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-b from-slate-900 to-slate-950 opacity-90 pointer-events-none"></div>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 space-y-6">
        <span class="text-xs font-mono font-bold tracking-widest text-emerald-400 uppercase">
            THE FINAL CALL TO CREATION
        </span>
        <h2 class="text-3xl sm:text-5xl font-black text-white tracking-tight leading-tight">
            Learn what exists. <br>
            <span class="text-purple-400">Discover what is possible.</span> <br>
            <span class="text-emerald-400">Create what comes next.</span>
        </h2>
        <p class="text-sm sm:text-base text-slate-400 max-w-2xl mx-auto leading-relaxed">
            Join an institution where young minds learn to question, invent, code, and lead. We are preparing students to build the world of tomorrow.
        </p>
        <div class="pt-4 flex flex-wrap items-center justify-center gap-3">
            <a href="{{ route('register') }}" class="px-8 py-4 rounded-full font-black text-sm text-slate-950 bg-emerald-400 hover:bg-emerald-300 transition shadow-lg hover:shadow-emerald-500/20">
                Enroll Your Scholar Today
            </a>
            <a href="{{ route('courses.index') }}" class="px-7 py-4 rounded-full font-bold text-sm text-white bg-slate-800 hover:bg-slate-700 border border-slate-700 transition">
                Explore Curriculum Handouts
            </a>
        </div>
    </div>
</section>

<!-- Script to handle interactive spark generator -->
<script>
    async function fetchNextSpark() {
        try {
            const res = await fetch('{{ route('motivation.spark') }}');
            const data = await res.json();
            if (data && data.quote) {
                document.getElementById('spark-quote').textContent = '"' + data.quote.quote + '"';
                document.getElementById('spark-author').innerHTML = '&mdash; ' + data.quote.author + ' &bull; Aim, Ascend, Achieve';
                document.getElementById('spark-fact').innerHTML = '<strong>Did you know?</strong> ' + data.fact.fact;
            }
        } catch(e) {
            console.log('Spark generation note:', e);
        }
    }
</script>
@endsection
