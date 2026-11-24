<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, viewport-fit=cover">
    
    <title>@yield('title', ($school['name'] ?? config('app.name', 'School ERP')) . ' — Powered by PrimeOS')</title>
    <meta name="description" content="@yield('meta_description', ($school['name'] ?? config('app.name', 'School ERP')) . ' — ' . ($school['motto'] ?? 'Excellence in Education') . '. Powered by PrimeOS.')">
    <link rel="canonical" href="{{ url()->current() }}">

    <!-- Open Graph / Social Sharing -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="@yield('title', ($school['name'] ?? config('app.name', 'School ERP')) . ' — Powered by PrimeOS')">
    <meta property="og:description" content="@yield('meta_description', ($school['name'] ?? config('app.name', 'School ERP')) . ' — ' . ($school['motto'] ?? 'Excellence in Education') . '. Powered by PrimeOS.')">
    <meta property="og:image" content="{{ asset('icons/icon-180x180.png') }}">
    <meta name="twitter:card" content="summary_large_image">

    <!-- Progressive Web App (PWA) Manifest & Meta Tags -->
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#ffffff">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="{{ $school['short_name'] ?? 'PrimeOS' }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('icons/icon-180x180.png') }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('icons/icon.svg') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">

    <!-- Fonts: Plus Jakarta Sans / Space Grotesk -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">

    <!-- Infix LMS Dynamic Theme & Appearance Injection -->
    <x-theme-dynamic-styles />

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        bio: {
                            50: '#f5f3ff',
                            100: '#ede9fe',
                            200: '#ddd6fe',
                            500: 'var(--system_primery_color)',
                            600: 'var(--system_primery_color)',
                            700: 'var(--system_primery_color)',
                            800: 'var(--system_secendory_color)',
                            900: 'var(--system_secendory_color)',
                        },
                        cell: {
                            50: '#f5f3ff',
                            100: '#ede9fe',
                            500: 'var(--system_accent_color)',
                            600: 'var(--system_accent_color)',
                            700: 'var(--system_accent_color)',
                        }
                    }
                }
            }
        }
    </script>

    <!-- Alpine.js -->
    <script defer src="{{ asset('js/alpine.min.js') }}"></script>

    <style>
        [x-cloak] { display: none !important; }
        
        /* Biotechnology Grid Backdrop */
        .bio-grid-pattern {
            background-color: #f8fafc;
            background-image: 
                radial-gradient(circle at 1px 1px, rgba(16, 185, 129, 0.08) 1.5px, transparent 0);
            background-size: 28px 28px;
        }

        /* Subtle floating animations for DNA and cellular shapes */
        @keyframes floatSlow {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-8px) rotate(2deg); }
        }
        @keyframes floatReverse {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(8px) rotate(-2deg); }
        }
        .bio-float-1 { animation: floatSlow 6s ease-in-out infinite; }
        .bio-float-2 { animation: floatReverse 7s ease-in-out infinite; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen flex flex-col font-sans antialiased selection:bg-purple-100 selection:text-purple-900 overflow-x-hidden w-full max-w-full"
      x-data="{ mobileMenuOpen: false, scrolled: false }"
      @scroll.window="scrolled = (window.pageYOffset > 15)">

    <!-- Infix LMS Signature Preloader -->
    <x-infix-preloader />

    <!-- Top Navigation Bar (Infix LMS Header Style) -->
    <header :class="scrolled ? 'bg-white/95 border-slate-200 shadow-sm backdrop-blur-md' : 'bg-white/80 border-slate-200/60 backdrop-blur-xs'"
            class="sticky top-0 z-50 border-b transition-all duration-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-18 sm:h-20 flex items-center justify-between">
            
            <!-- Logo & Brand Identity (Infix LMS Theme Accent) -->
            <a href="{{ route('home') }}" class="flex items-center space-x-3 group focus:outline-hidden">
                <div class="w-10 h-10 rounded-xl p-[2px] shadow-sm group-hover:scale-105 transition-transform" style="background: var(--system_primary_gradient);">
                    <div class="w-full h-full bg-white rounded-[10px] flex items-center justify-center theme_color">
                        <i class="ti-book text-lg"></i>
                    </div>
                </div>
                <div>
                    <div class="flex items-center space-x-1.5">
                        <span class="font-heading text-base sm:text-xl font-black tracking-tight text-slate-900 group-hover:text-purple-700 transition-colors">
                            {{ $school['name'] ?? 'Infix LMS' }}
                        </span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold tracking-wider uppercase text-white shadow-xs" style="background: var(--system_primary_gradient);">
                            {{ $school['short_name'] ?? 'LMS' }}
                        </span>
                    </div>
                </div>
            </a>

            <!-- Desktop Navigation Links -->
            <nav class="hidden lg:flex items-center space-x-1 text-sm font-semibold text-slate-600">
                <a href="{{ route('home') }}" class="px-3 py-1.5 rounded-xl transition {{ request()->routeIs('home') ? 'text-emerald-700 bg-emerald-50 font-bold' : 'hover:text-slate-900 hover:bg-slate-100' }}">
                    Home
                </a>
                <a href="{{ route('dpp.index') }}" class="px-3 py-1.5 rounded-xl transition hover:text-slate-900 hover:bg-slate-100">
                    DPP &amp; Practice
                </a>
                <a href="{{ route('syllabus.index') }}" class="px-3 py-1.5 rounded-xl transition hover:text-slate-900 hover:bg-slate-100">
                    Syllabus
                </a>
                <a href="{{ route('courses.index') }}" class="px-3 py-1.5 rounded-xl transition {{ request()->routeIs('courses*') ? 'text-emerald-700 bg-emerald-50 font-bold' : 'hover:text-slate-900 hover:bg-slate-100' }}">
                    Courses
                </a>
                <a href="{{ route('about') }}" class="px-3 py-1.5 rounded-xl transition {{ request()->routeIs('about') ? 'text-emerald-700 bg-emerald-50 font-bold' : 'hover:text-slate-900 hover:bg-slate-100' }}">
                    About
                </a>
                <a href="{{ route('admissions') }}" class="px-3 py-1.5 rounded-xl transition {{ request()->routeIs('admissions') ? 'text-emerald-700 bg-emerald-50 font-bold' : 'hover:text-slate-900 hover:bg-slate-100' }}">
                    Admissions
                </a>
                <a href="{{ route('contact') }}" class="px-3 py-1.5 rounded-xl transition {{ request()->routeIs('contact') ? 'text-emerald-700 bg-emerald-50 font-bold' : 'hover:text-slate-900 hover:bg-slate-100' }}">
                    Contact
                </a>
            </nav>

            <!-- Actions Right -->
            <div class="flex items-center space-x-2 sm:space-x-2.5">
                <!-- PWA Install Button -->
                <button id="pwa-install-btn" style="display: none;" type="button" class="hidden sm:inline-flex items-center px-2 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                    Install App
                </button>

                <!-- Fullscreen Toggle Button -->
                <button id="fullscreen-toggle-btn" type="button" onclick="toggleAppFullscreen()" title="Toggle Fullscreen" class="p-1.5 rounded-lg text-slate-600 hover:text-slate-900 hover:bg-slate-100 border border-slate-200 transition">
                    <svg id="fs-expand-icon" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-5h-4m4 0v4m0-4l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
                    <svg id="fs-compress-icon" class="w-4 h-4 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>

                @auth
                    <a href="{{ route('dashboard') }}" class="primary-btn" style="padding: 0.45rem 1.25rem; font-size: 0.75rem;">
                        <span>LMS Portal</span>
                        <i class="ti-arrow-right text-[10px]"></i>
                    </a>
                @else
                    <a href="{{ route('login') }}" class="px-3.5 py-1.5 rounded-full text-xs font-bold text-slate-700 hover:text-slate-900 hover:bg-slate-100 border border-slate-200 transition">
                        Sign In
                    </a>
                    <a href="{{ route('register') }}" class="primary-btn" style="padding: 0.45rem 1.25rem; font-size: 0.75rem;">
                        <span>Enroll Now</span>
                        <i class="ti-arrow-right text-[10px]"></i>
                    </a>
                @endauth

                <!-- Mobile Hamburger Button -->
                <button @click="mobileMenuOpen = !mobileMenuOpen" 
                        type="button" 
                        class="lg:hidden p-2 rounded-xl text-slate-600 hover:text-slate-900 hover:bg-slate-100 border border-slate-200 transition"
                        aria-label="Open Navigation Menu">
                    <svg x-show="!mobileMenuOpen" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    <svg x-show="mobileMenuOpen" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>

        <!-- Mobile Navigation Drawer -->
        <div x-show="mobileMenuOpen" 
             x-cloak 
             @click.away="mobileMenuOpen = false" 
             @click="if ($event.target.closest('a, button')) mobileMenuOpen = false"
             class="lg:hidden bg-white border-b border-slate-200 px-4 pt-3 pb-5 space-y-1.5 text-sm font-semibold">
            <a href="{{ route('home') }}" @click="mobileMenuOpen = false" class="block px-3 py-2 rounded-lg {{ request()->routeIs('home') ? 'text-emerald-700 bg-emerald-50 font-bold' : 'text-slate-700 hover:bg-slate-100' }}">
                Home
            </a>
            <a href="{{ route('dpp.index') }}" @click="mobileMenuOpen = false" class="block px-3 py-2 rounded-lg text-slate-700 hover:bg-slate-100">
                DPP &amp; CBT Tests
            </a>
            <a href="{{ route('syllabus.index') }}" @click="mobileMenuOpen = false" class="block px-3 py-2 rounded-lg text-slate-700 hover:bg-slate-100">
                Chapterwise Syllabus
            </a>
            <a href="{{ route('courses.index') }}" @click="mobileMenuOpen = false" class="block px-3 py-2 rounded-lg {{ request()->routeIs('courses*') ? 'text-emerald-700 bg-emerald-50 font-bold' : 'text-slate-700 hover:bg-slate-100' }}">
                Courses
            </a>
            <a href="{{ route('about') }}" @click="mobileMenuOpen = false" class="block px-3 py-2 rounded-lg {{ request()->routeIs('about') ? 'text-emerald-700 bg-emerald-50 font-bold' : 'text-slate-700 hover:bg-slate-100' }}">
                About Us
            </a>
            <a href="{{ route('admissions') }}" @click="mobileMenuOpen = false" class="block px-3 py-2 rounded-lg {{ request()->routeIs('admissions') ? 'text-emerald-700 bg-emerald-50 font-bold' : 'text-slate-700 hover:bg-slate-100' }}">
                Admissions
            </a>
            <a href="{{ route('contact') }}" @click="mobileMenuOpen = false" class="block px-3 py-2 rounded-lg {{ request()->routeIs('contact') ? 'text-emerald-700 bg-emerald-50 font-bold' : 'text-slate-700 hover:bg-slate-100' }}">
                Contact
            </a>

            <div class="pt-3 mt-2 border-t border-slate-100 flex gap-2">
                @auth
                    <a href="{{ route('dashboard') }}" class="w-full text-center py-2 rounded-xl font-bold text-xs bg-emerald-600 text-white">
                        Enter LMS Portal
                    </a>
                @else
                    <a href="{{ route('login') }}" class="w-1/2 text-center py-2 rounded-xl font-bold text-xs bg-slate-100 text-slate-800 border border-slate-200">
                        Sign In
                    </a>
                    <a href="{{ route('register') }}" class="w-1/2 text-center py-2 rounded-xl font-bold text-xs bg-emerald-600 text-white">
                        Register
                    </a>
                @endauth
            </div>
        </div>
    </header>

    <!-- Main Content Slot -->
    <main class="flex-1">
        @yield('content')
    </main>

    <!-- Infix LMS Footer Style -->
    <footer class="pt-14 pb-8 mt-16 border-t border-slate-800 transition-colors" style="background-color: var(--footer_background_color); color: var(--footer_text_color);">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-10">
                
                <!-- Col 1: Identity & Infix Theme -->
                <div class="md:col-span-2 space-y-3">
                    <div class="flex items-center space-x-2.5">
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center text-white font-bold text-sm shadow-md" style="background: var(--system_primary_gradient);">
                            <i class="ti-book"></i>
                        </div>
                        <span class="font-heading text-lg font-black tracking-tight text-white">{{ strtoupper($school['name'] ?? 'INFIX LMS') }}</span>
                    </div>
                    <p class="text-xs opacity-80 leading-relaxed max-w-md">
                        Comprehensive learning management system and institutional school ERP. Empowering educators and students with concept mastery, Bloom's cognitive taxonomy, daily CBT assessments, and student lifecycle management.
                    </p>
                    <div class="flex items-center space-x-2 text-xs font-semibold theme_color">
                        <span class="w-2 h-2 rounded-full animate-ping" style="background-color: var(--system_primery_color);"></span>
                        <span>Institutional Core &bull; CBSE &amp; Science Curriculum</span>
                    </div>
                </div>

                <!-- Col 2: Academic Shortcuts -->
                <div class="space-y-2 text-xs">
                    <h3 class="font-bold uppercase tracking-wider text-white text-[11px]">Academic Portals</h3>
                    <ul class="space-y-1.5 opacity-80 font-medium">
                        <li><a href="{{ route('dpp.index') }}" class="hover:text-white transition">Daily DPPs &amp; Timed Tests</a></li>
                        <li><a href="{{ route('blooms.index') }}" class="hover:text-white transition">Bloom's Taxonomy Framework</a></li>
                        <li><a href="{{ route('syllabus.index') }}" class="hover:text-white transition">Chapterwise Syllabus</a></li>
                        <li><a href="{{ route('videos.index') }}" class="hover:text-white transition">Video Lecture Hub</a></li>
                        <li><a href="{{ route('my-batch') }}" class="hover:text-white transition">My Class &amp; Section</a></li>
                    </ul>
                </div>

                <!-- Col 3: Portal & Information -->
                <div class="space-y-2 text-xs">
                    <h3 class="font-bold uppercase tracking-wider text-white text-[11px]">Institutional</h3>
                    <ul class="space-y-1.5 opacity-80 font-medium">
                        <li><a href="{{ route('about') }}" class="hover:text-white transition">About School</a></li>
                        <li><a href="{{ route('admissions') }}" class="hover:text-white transition">Admissions &amp; Enrollment</a></li>
                        <li><a href="{{ route('contact') }}" class="hover:text-white transition">Contact &amp; Inquiries</a></li>
                        <li><a href="{{ route('privacy-policy') }}" class="hover:text-white transition">Privacy Policy</a></li>
                        <li><a href="{{ route('terms') }}" class="hover:text-white transition">Terms &amp; Conditions</a></li>
                    </ul>
                </div>

            </div>

            <!-- Bottom Line -->
            <div class="pt-6 border-t border-white/10 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs opacity-70">
                <div>
                    &copy; {{ date('Y') }} <strong class="text-white">{{ $school['name'] }}</strong>. All rights reserved.
                    <span class="block sm:inline sm:ml-2 font-semibold">{{ $school['motto'] }}</span>
                    <span class="block text-[11px] font-bold mt-0.5 theme_color">⚡ Powered by PrimeOS &bull; Infix Theme System</span>
                </div>
                <div class="flex items-center space-x-3 text-[11px]">
                    <a href="{{ route('login') }}" class="hover:text-white transition">Faculty &amp; Student Sign In</a>
                    <span>&bull;</span>
                    <a href="{{ route('register') }}" class="hover:text-white transition">Student Registration</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Progressive Web App (PWA) & Fullscreen Manager -->
    <script src="{{ asset('js/pwa-fullscreen.js') }}"></script>
</body>
</html>
