@props([
    'school' => null,
])

@php
    $school = $school ?? \App\Services\SchoolBrandingService::get();
@endphp

<header class="bg-white/95 dark:bg-slate-900/95 backdrop-blur-md border-b border-slate-200/80 dark:border-slate-800 sticky top-0 z-40 transition-colors">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <!-- Brand -->
            <div class="flex items-center gap-3">
                <a href="{{ route('home') }}" class="flex items-center gap-2.5 group">
                    @if(!empty($school['logo_url']))
                        <img src="{{ $school['logo_url'] }}" alt="{{ $school['name'] }}" class="h-9 w-auto rounded-lg">
                    @else
                        <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-purple-600 to-indigo-600 flex items-center justify-center text-white font-black shadow-md shadow-purple-500/20">
                            {{ substr($school['name'] ?? 'P', 0, 1) }}
                        </div>
                    @endif
                    <span class="font-black text-lg text-slate-900 dark:text-white tracking-tight group-hover:text-purple-600 transition">{{ $school['name'] ?? 'PrimeOS Academy' }}</span>
                </a>
            </div>

            <!-- Public Nav Links -->
            <nav class="hidden md:flex items-center gap-6 text-sm font-semibold text-slate-600 dark:text-slate-300">
                <a href="{{ route('home') }}" class="hover:text-purple-600 transition {{ request()->routeIs('home') ? 'text-purple-600' : '' }}">Home</a>
                <a href="{{ route('courses.index') }}" class="hover:text-purple-600 transition {{ request()->routeIs('courses.*') ? 'text-purple-600' : '' }}">Courses</a>
                <a href="{{ route('about') }}" class="hover:text-purple-600 transition {{ request()->routeIs('about') ? 'text-purple-600' : '' }}">About</a>
                <a href="{{ route('admissions') }}" class="hover:text-purple-600 transition {{ request()->routeIs('admissions') ? 'text-purple-600' : '' }}">Admissions</a>
                <a href="{{ route('contact') }}" class="hover:text-purple-600 transition {{ request()->routeIs('contact') ? 'text-purple-600' : '' }}">Contact</a>
            </nav>

            <!-- Actions / Auth -->
            <div class="flex items-center gap-3">
                @auth
                    <a href="{{ route('dashboard') }}" class="px-4 py-2 text-xs font-bold uppercase tracking-wider text-white bg-purple-600 hover:bg-purple-700 rounded-xl shadow-md shadow-purple-600/20 transition">
                        Portal Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}" class="text-sm font-semibold text-slate-700 dark:text-slate-200 hover:text-purple-600 transition">
                        Log In
                    </a>
                    <a href="{{ route('register') }}" class="px-4 py-2 text-xs font-bold uppercase tracking-wider text-white bg-purple-600 hover:bg-purple-700 rounded-xl shadow-md shadow-purple-600/20 transition">
                        Enroll Now
                    </a>
                @endauth
            </div>
        </div>
    </div>
</header>
