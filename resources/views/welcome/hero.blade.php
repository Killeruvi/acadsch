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
