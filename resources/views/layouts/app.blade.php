<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', ($school['name'] ?? 'Infix LMS') . ' — Academic ERP')</title>

    <!-- Progressive Web App (PWA) Manifest & Meta Tags -->
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#7c32ff">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="{{ $school['short_name'] ?? 'Infix' }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('icons/icon-180x180.png') }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('icons/icon.svg') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">

    <!-- Infix LMS Dynamic Theme & Appearance Injection -->
    <x-theme-dynamic-styles />

    <!-- Tailwind CSS CDN for instant responsive styling with Infix tokens -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: 'var(--system_primery_color_05)',
                            100: 'var(--system_primery_color_10)',
                            200: 'var(--system_primery_color_20)',
                            500: 'var(--system_primery_color)',
                            600: 'var(--system_primery_color)',
                            700: 'var(--system_primery_color)',
                            900: 'var(--system_secendory_color)',
                        },
                        emerald: {
                            50: 'var(--system_primery_color_05)',
                            100: 'var(--system_primery_color_10)',
                            200: 'var(--system_primery_color_20)',
                            300: 'var(--system_primery_color_50)',
                            400: 'var(--system_accent_color)',
                            500: 'var(--system_primery_color)',
                            600: 'var(--system_primery_color)',
                            700: 'var(--system_primery_color)',
                            800: 'var(--system_secendory_color)',
                            900: 'var(--system_secendory_color)',
                            950: 'var(--system_secendory_color_10)',
                        },
                        teal: {
                            50: 'var(--system_primery_color_05)',
                            100: 'var(--system_primery_color_10)',
                            400: 'var(--system_accent_color)',
                            500: 'var(--system_accent_color)',
                            600: 'var(--system_primery_color)',
                            700: 'var(--system_primery_color)',
                        },
                        navy: {
                            800: '#1b223c',
                            900: '#121729',
                            950: '#0b0f19',
                        }
                    }
                }
            }
        }
    </script>

    <!-- Instant Synchronous Light/Dark Theme Initializer -->
    <script>
        (function() {
            var isDark = localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches);
            if (isDark) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        })();

        function applyThemeModeUI(isDark) {
            document.querySelectorAll('.theme-mode-label').forEach(function(el) {
                el.textContent = isDark ? 'Dark' : 'Light';
            });
            document.querySelectorAll('[data-theme-toggle]').forEach(function(btn) {
                btn.setAttribute('aria-checked', isDark ? 'true' : 'false');
                btn.setAttribute('title', isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode');
            });
            window.dispatchEvent(new CustomEvent('theme-mode-changed', {
                detail: { mode: isDark ? 'dark' : 'light', isDark: isDark }
            }));
        }

        window.__syncThemeUI = applyThemeModeUI;

        function toggleThemeMode() {
            document.documentElement.classList.add('theme-transitioning');
            var isCurrentlyDark = document.documentElement.classList.contains('dark');
            var willBeDark = !isCurrentlyDark;
            
            if (willBeDark) {
                document.documentElement.classList.add('dark');
                localStorage.theme = 'dark';
            } else {
                document.documentElement.classList.remove('dark');
                localStorage.theme = 'light';
            }
            
            applyThemeModeUI(willBeDark);
            
            setTimeout(function() {
                document.documentElement.classList.remove('theme-transitioning');
            }, 300);
        }

        document.addEventListener('DOMContentLoaded', function() {
            var isDark = document.documentElement.classList.contains('dark');
            applyThemeModeUI(isDark);
        });

        // Listen for OS system theme changes if user hasn't explicitly saved preference
        if (window.matchMedia) {
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
                if (!('theme' in localStorage)) {
                    if (e.matches) {
                        document.documentElement.classList.add('dark');
                    } else {
                        document.documentElement.classList.remove('dark');
                    }
                    applyThemeModeUI(e.matches);
                }
            });
        }
    </script>

    <!-- Local Alpine.js for offline stability & instant interaction -->
    <script defer src="{{ asset('js/alpine.min.js') }}"></script>

    <!-- Fullscreen & Notch Safe-Area Support -->
    <style>
        :fullscreen { background-color: #0b0f19; }
        :-webkit-full-screen { background-color: #0b0f19; }
        @supports (padding: max(0px)) {
            body {
                padding-left: env(safe-area-inset-left);
                padding-right: env(safe-area-inset-right);
            }
        }
        [x-cloak] { display: none !important; }
    </style>
</head>

<body class="admin antialiased min-h-screen text-slate-800 dark:text-slate-100 overflow-x-hidden w-full max-w-full"
      x-data="{ sidebarOpen: false, profileOpen: false, searchOpen: false }">

    <!-- Infix LMS Signature Preloader (Speed Rocket animation) -->
    <x-infix-preloader />

    <!-- SPA Top Loading Progress Bar -->
    <div id="spa-progress" class="fixed top-0 left-0 h-1 z-50 transition-all duration-200 ease-out opacity-0 pointer-events-none" style="width: 0%; background: var(--system_primary_gradient);"></div>

    <div class="infix-layout-wrapper">

        <!-- ========================================================================= -->
        <!-- 1. INFIX DESKTOP & MOBILE SIDEBAR (#sidebar)                              -->
        <!-- ========================================================================= -->
        @auth
        <nav id="sidebar" class="sidebar" :class="sidebarOpen ? 'sidebar-open' : ''">
            
            <!-- Sidebar Header: Brand Logo & Title -->
            <div class="sidebar-header update_sidebar">
                <a href="{{ route('dashboard') }}" class="flex items-center space-x-2.5 group overflow-hidden">
                    @if(!empty($school['logo_url']))
                        <img src="{{ $school['logo_url'] }}" alt="{{ $school['name'] }}" class="w-8 h-8 rounded-lg object-contain">
                    @else
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white font-bold text-xs shadow-sm" style="background: var(--system_primary_gradient);">
                            <i class="ti-book"></i>
                        </div>
                    @endif
                    <div class="flex flex-col overflow-hidden text-left">
                        <span class="font-bold text-slate-900 dark:text-white text-xs truncate max-w-[155px]">
                            {{ $school['name'] }}
                        </span>
                        <span class="text-[9px] font-bold tracking-wider uppercase theme_color">
                            {{ $school['short_name'] ?? 'INFIX' }} &bull; LMS
                        </span>
                    </div>
                </a>
                <button type="button" @click="sidebarOpen = false" class="lg:hidden p-1.5 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-white">
                    <i class="ti-close text-xs"></i>
                </button>
            </div>

            <!-- Infix Sidebar User Profile Card (.sidebar-user) -->
            <div class="sidebar-user">
                <div class="sidebar-profile">
                    {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                </div>
                <h4 class="text-xs font-bold text-slate-900 dark:text-white truncate px-2 mb-0.5">
                    {{ Auth::user()->name }}
                </h4>
                <div class="flex items-center justify-center gap-1.5 mt-1">
                    <span class="px-2.5 py-0.5 rounded-full text-[9px] font-extrabold uppercase tracking-wider text-white shadow-xs" style="background: var(--system_primary_gradient);">
                        {{ Auth::user()->isAdmin() ? 'ADMIN' : (Auth::user()->isTeacher() ? 'FACULTY' : (Auth::user()->isParent() ? 'GUARDIAN' : (Auth::user()->isCashier() ? 'CASHIER' : 'STUDENT'))) }}
                    </span>
                    @if(Auth::user()->currentClass)
                    <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                        {{ Auth::user()->currentClass->display_name }}
                    </span>
                    @elseif(Auth::user()->batch)
                    <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                        {{ $user->batch->name ?? Auth::user()->batch->name }}
                    </span>
                    @endif
                </div>
            </div>

            <!-- Infix Sidebar Navigation Menu List (#sidebar_menu) -->
            <ul id="sidebar_menu">

                <!-- ================= 1. OVERVIEW ================= -->
                <span class="menu_seperator">OVERVIEW</span>
                
                @if(Auth::user()->isParent())
                    <li>
                        <a href="{{ route('school.parent.dashboard') }}" class="{{ request()->routeIs('school.parent.*') ? 'active infix-nav-active' : '' }}">
                            <div class="infix-nav-icon"><i class="ti-dashboard"></i></div>
                            <div class="nav_title"><span>Parent Portal</span></div>
                        </a>
                    </li>
                @else
                    <li>
                        <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active infix-nav-active' : '' }}">
                            <div class="infix-nav-icon"><i class="ti-dashboard"></i></div>
                            <div class="nav_title"><span>Dashboard</span></div>
                        </a>
                    </li>
                @endif

                @if(Auth::user()->isAdmin() || Auth::user()->isTeacher())
                <li>
                    <a href="{{ route('analytics.index') }}" class="{{ request()->routeIs('analytics.*') ? 'active infix-nav-active' : '' }}">
                        <div class="infix-nav-icon"><i class="ti-bar-chart"></i></div>
                        <div class="nav_title"><span>Academic Analytics</span></div>
                    </a>
                </li>
                @endif

                @if(Auth::user()->isAdmin() || Auth::user()->isTeacher())
                <!-- ================= 2. ACADEMIC HUB ================= -->
                <span class="menu_seperator">ACADEMIC HUB</span>

                @php
                    $isAcademicOpen = request()->routeIs(
                        'school.classes.*', 'school.sections.*', 'school.subjects.*', 
                        'my-batch*', 'batches.*', 'syllabus.*', 
                        'school.timetable.*', 'schedules.*', 'school.holidays.*',
                        'school.assignments.*', 'blooms.*'
                    );
                @endphp
                <li class="sidebar-dropdown {{ $isAcademicOpen ? 'dropdown-open' : '' }}">
                    <a href="javascript:void(0)" class="has-arrow {{ $isAcademicOpen ? 'active-category' : '' }}" aria-expanded="{{ $isAcademicOpen ? 'true' : 'false' }}">
                        <div class="infix-nav-icon"><i class="ti-layers-alt"></i></div>
                        <div class="nav_title"><span>Academics</span></div>
                        <i class="ti-angle-down dropdown-chevron"></i>
                    </a>
                    <ul class="sidebar-submenu" style="{{ $isAcademicOpen ? 'display: block;' : 'display: none;' }}">
                        @if(Auth::user()->isAdmin())
                        <li>
                            <a href="{{ route('school.classes.index') }}" class="{{ request()->routeIs('school.classes.*') ? 'active infix-nav-active' : '' }}">
                                <span class="sub-bullet"></span>
                                <span>Classes &amp; Streams</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('school.sections.index') }}" class="{{ request()->routeIs('school.sections.*') ? 'active infix-nav-active' : '' }}">
                                <span class="sub-bullet"></span>
                                <span>Sections Hub</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('school.subjects.index') }}" class="{{ request()->routeIs('school.subjects.*') ? 'active infix-nav-active' : '' }}">
                                <span class="sub-bullet"></span>
                                <span>Curriculum Subjects</span>
                            </a>
                        </li>
                        @endif
                        <li>
                            <a href="{{ route('school.sections.index') }}" class="{{ request()->routeIs('school.sections.*') ? 'active infix-nav-active' : '' }}">
                                <span class="sub-bullet"></span>
                                <span>Class Sections Hub</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('syllabus.index') }}" class="{{ request()->routeIs('syllabus.*') ? 'active infix-nav-active' : '' }}">
                                <span class="sub-bullet"></span>
                                <span>Chapterwise Syllabus</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('school.timetable.index') }}" class="{{ request()->routeIs('school.timetable.*', 'schedules.*') ? 'active infix-nav-active' : '' }}">
                                <span class="sub-bullet"></span>
                                <span>Timetable &amp; Schedule</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('school.holidays.index') }}" class="{{ request()->routeIs('school.holidays.*') ? 'active infix-nav-active' : '' }}">
                                <span class="sub-bullet"></span>
                                <span>School Holidays</span>
                            </a>
                        </li>
                        @if(Auth::user()->isAdmin())
                        <li>
                            <a href="{{ route('school.assignments.index') }}" class="{{ request()->routeIs('school.assignments.*') ? 'active infix-nav-active' : '' }}">
                                <span class="sub-bullet"></span>
                                <span>Faculty Assignments</span>
                            </a>
                        </li>
                        @endif
                        <li>
                            <a href="{{ route('blooms.index') }}" class="{{ request()->routeIs('blooms.*') ? 'active infix-nav-active' : '' }}">
                                <span class="sub-bullet"></span>
                                <span>Bloom's Taxonomy</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('school.study-materials.index') }}" class="{{ request()->routeIs('school.study-materials.*') ? 'active infix-nav-active' : '' }}">
                                <span class="sub-bullet"></span>
                                <span>Download Center</span>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- ================= 3. STUDENT INFORMATION ================= -->
                @php
                    $isStudentOpen = request()->routeIs('school.students.*', 'students.*', 'idcard.*', 'admissions');
                @endphp
                <li class="sidebar-dropdown {{ $isStudentOpen ? 'dropdown-open' : '' }}">
                    <a href="javascript:void(0)" class="has-arrow {{ $isStudentOpen ? 'active-category' : '' }}" aria-expanded="{{ $isStudentOpen ? 'true' : 'false' }}">
                        <div class="infix-nav-icon"><i class="ti-id-badge"></i></div>
                        <div class="nav_title"><span>Student Info</span></div>
                        <i class="ti-angle-down dropdown-chevron"></i>
                    </a>
                    <ul class="sidebar-submenu" style="{{ $isStudentOpen ? 'display: block;' : 'display: none;' }}">
                        <li>
                            <a href="{{ route('school.students.index') }}" class="{{ request()->routeIs('school.students.*') ? 'active infix-nav-active' : '' }}">
                                <span class="sub-bullet"></span>
                                <span>Student Directory</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('students.index') }}" class="{{ request()->routeIs('students.*') ? 'active infix-nav-active' : '' }}">
                                <span class="sub-bullet"></span>
                                <span>Student Records</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('idcard.show') }}" class="{{ request()->routeIs('idcard.*') ? 'active infix-nav-active' : '' }}">
                                <span class="sub-bullet"></span>
                                <span>Digital ID Cards</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admissions') }}" class="{{ request()->routeIs('admissions') ? 'active infix-nav-active' : '' }}">
                                <span class="sub-bullet"></span>
                                <span>Admissions Hub</span>
                            </a>
                        </li>
                        @if(Auth::user()->isAdmin())
                        <li>
                            <a href="{{ route('school.admissions.enquiries.index') }}" class="{{ request()->routeIs('school.admissions.enquiries.*') ? 'active infix-nav-active' : '' }}">
                                <span class="sub-bullet"></span>
                                <span>Admission Enquiries</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('school.promotion.index') }}" class="{{ request()->routeIs('school.promotion.*') ? 'active infix-nav-active' : '' }}">
                                <span class="sub-bullet"></span>
                                <span>Student Promotion Hub</span>
                            </a>
                        </li>
                        @endif
                    </ul>
                </li>

                <!-- ================= 4. STAFF & FACULTY ================= -->
                @php
                    $isStaffOpen = request()->routeIs('staff.*', 'admin.faculty-codes.*', 'school.staff-attendance.*', 'school.payroll.*');
                @endphp
                <li class="sidebar-dropdown {{ $isStaffOpen ? 'dropdown-open' : '' }}">
                    <a href="javascript:void(0)" class="has-arrow {{ $isStaffOpen ? 'active-category' : '' }}" aria-expanded="{{ $isStaffOpen ? 'true' : 'false' }}">
                        <div class="infix-nav-icon"><i class="ti-user"></i></div>
                        <div class="nav_title"><span>Staff &amp; Faculty</span></div>
                        <i class="ti-angle-down dropdown-chevron"></i>
                    </a>
                    <ul class="sidebar-submenu" style="{{ $isStaffOpen ? 'display: block;' : 'display: none;' }}">
                        <li>
                            <a href="{{ route('staff.index') }}" class="{{ request()->routeIs('staff.*') ? 'active infix-nav-active' : '' }}">
                                <span class="sub-bullet"></span>
                                <span>Staff Directory</span>
                            </a>
                        </li>
                        @if(Auth::user()->isAdmin())
                        <li>
                            <a href="{{ route('admin.faculty-codes.index') }}" class="{{ request()->routeIs('admin.faculty-codes.*') ? 'active infix-nav-active' : '' }}">
                                <span class="sub-bullet"></span>
                                <span>Faculty Auth Codes</span>
                            </a>
                        </li>
                        @endif
                        <li>
                            <a href="{{ route('school.staff-attendance.index') }}" class="{{ request()->routeIs('school.staff-attendance.*') ? 'active infix-nav-active' : '' }}">
                                <span class="sub-bullet"></span>
                                <span>Staff Attendance &amp; Leave</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('school.payroll.index') }}" class="{{ request()->routeIs('school.payroll.*') ? 'active infix-nav-active' : '' }}">
                                <span class="sub-bullet"></span>
                                <span>Staff Payroll</span>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- ================= 5. ATTENDANCE & LEAVES ================= -->
                @php
                    $isAttendanceOpen = request()->routeIs('school.attendance.*', 'attendance.*', 'leaves.*');
                @endphp
                <li class="sidebar-dropdown {{ $isAttendanceOpen ? 'dropdown-open' : '' }}">
                    <a href="javascript:void(0)" class="has-arrow {{ $isAttendanceOpen ? 'active-category' : '' }}" aria-expanded="{{ $isAttendanceOpen ? 'true' : 'false' }}">
                        <div class="infix-nav-icon"><i class="ti-check-box"></i></div>
                        <div class="nav_title"><span>Attendance</span></div>
                        <i class="ti-angle-down dropdown-chevron"></i>
                    </a>
                    <ul class="sidebar-submenu" style="{{ $isAttendanceOpen ? 'display: block;' : 'display: none;' }}">
                        <li>
                            <a href="{{ route('school.attendance.index') }}" class="{{ request()->routeIs('school.attendance.*', 'attendance.*') ? 'active infix-nav-active' : '' }}">
                                <span class="sub-bullet"></span>
                                <span>Daily Attendance</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('leaves.index') }}" class="{{ request()->routeIs('leaves.*') ? 'active infix-nav-active' : '' }}">
                                <span class="sub-bullet"></span>
                                <span>Leave Applications</span>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- ================= 6. FEES & FINANCE ================= -->
                @if(Auth::user()->isAdmin() || Auth::user()->isCashier())
                @php
                    $isFeeOpen = request()->routeIs('school.fees.*', 'fees.*');
                @endphp
                <li class="sidebar-dropdown {{ $isFeeOpen ? 'dropdown-open' : '' }}">
                    <a href="javascript:void(0)" class="has-arrow {{ $isFeeOpen ? 'active-category' : '' }}" aria-expanded="{{ $isFeeOpen ? 'true' : 'false' }}">
                        <div class="infix-nav-icon"><i class="ti-wallet"></i></div>
                        <div class="nav_title"><span>Fees &amp; Finance</span></div>
                        <i class="ti-angle-down dropdown-chevron"></i>
                    </a>
                    <ul class="sidebar-submenu" style="{{ $isFeeOpen ? 'display: block;' : 'display: none;' }}">
                        <li>
                            <a href="{{ route('school.fees.index') }}" class="{{ request()->routeIs('school.fees.*', 'fees.*') ? 'active infix-nav-active' : '' }}">
                                <span class="sub-bullet"></span>
                                <span>Fee Management Hub</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('school.accounts.index') }}" class="{{ request()->routeIs('school.accounts.*') ? 'active infix-nav-active' : '' }}">
                                <span class="sub-bullet"></span>
                                <span>Accounts &amp; Treasury</span>
                            </a>
                        </li>
                    </ul>
                </li>
                @endif

                <!-- ================= INFIX SERVICES & OPERATIONS ================= -->
                @php
                    $isOperationsOpen = request()->routeIs('school.library.*', 'school.transport.*', 'school.dormitory.*', 'school.front-office.*');
                @endphp
                <li class="sidebar-dropdown {{ $isOperationsOpen ? 'dropdown-open' : '' }}">
                    <a href="javascript:void(0)" class="has-arrow {{ $isOperationsOpen ? 'active-category' : '' }}" aria-expanded="{{ $isOperationsOpen ? 'true' : 'false' }}">
                        <div class="infix-nav-icon"><i class="ti-package"></i></div>
                        <div class="nav_title"><span>Infix Operations</span></div>
                        <i class="ti-angle-down dropdown-chevron"></i>
                    </a>
                    <ul class="sidebar-submenu" style="{{ $isOperationsOpen ? 'display: block;' : 'display: none;' }}">
                        <li>
                            <a href="{{ route('school.library.index') }}" class="{{ request()->routeIs('school.library.*') ? 'active infix-nav-active' : '' }}">
                                <span class="sub-bullet"></span>
                                <span>Library Catalog</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('school.transport.index') }}" class="{{ request()->routeIs('school.transport.*') ? 'active infix-nav-active' : '' }}">
                                <span class="sub-bullet"></span>
                                <span>Transport &amp; Bus Fleet</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('school.dormitory.index') }}" class="{{ request()->routeIs('school.dormitory.*') ? 'active infix-nav-active' : '' }}">
                                <span class="sub-bullet"></span>
                                <span>Hostel &amp; Dormitory</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('school.front-office.index') }}" class="{{ request()->routeIs('school.front-office.*') ? 'active infix-nav-active' : '' }}">
                                <span class="sub-bullet"></span>
                                <span>Front Office Desk</span>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- ================= 7. EXAMINATIONS & ASSESSMENTS ================= -->
                @php
                    $isExamOpen = request()->routeIs('school.exams.*', 'school.report-cards.*', 'certificates.*');
                @endphp
                <li class="sidebar-dropdown {{ $isExamOpen ? 'dropdown-open' : '' }}">
                    <a href="javascript:void(0)" class="has-arrow {{ $isExamOpen ? 'active-category' : '' }}" aria-expanded="{{ $isExamOpen ? 'true' : 'false' }}">
                        <div class="infix-nav-icon"><i class="ti-medall"></i></div>
                        <div class="nav_title"><span>Examinations</span></div>
                        <i class="ti-angle-down dropdown-chevron"></i>
                    </a>
                    <ul class="sidebar-submenu" style="{{ $isExamOpen ? 'display: block;' : 'display: none;' }}">
                        <li>
                            <a href="{{ route('school.exams.index') }}" class="{{ request()->routeIs('school.exams.index', 'school.report-cards.*') ? 'active infix-nav-active' : '' }}">
                                <span class="sub-bullet"></span>
                                <span>Exams &amp; Grades</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('school.exams.marks-entry') }}" class="{{ request()->routeIs('school.exams.marks-entry') ? 'active infix-nav-active' : '' }}">
                                <span class="sub-bullet"></span>
                                <span>Marks Entry Register</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('certificates.index') }}" class="{{ request()->routeIs('certificates.*') ? 'active infix-nav-active' : '' }}">
                                <span class="sub-bullet"></span>
                                <span>Certificates &amp; Badges</span>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- ================= 8. REPORTS & INSTITUTIONAL INTELLIGENCE ================= -->
                @php
                    $isReportsOpen = request()->routeIs('school.reports.*');
                @endphp
                <li class="sidebar-dropdown {{ $isReportsOpen ? 'dropdown-open' : '' }}">
                    <a href="javascript:void(0)" class="has-arrow {{ $isReportsOpen ? 'active-category' : '' }}" aria-expanded="{{ $isReportsOpen ? 'true' : 'false' }}">
                        <div class="infix-nav-icon"><i class="ti-pie-chart"></i></div>
                        <div class="nav_title"><span>Reports Center</span></div>
                        <i class="ti-angle-down dropdown-chevron"></i>
                    </a>
                    <ul class="sidebar-submenu" style="{{ $isReportsOpen ? 'display: block;' : 'display: none;' }}">
                        <li>
                            <a href="{{ route('school.reports.index') }}" class="{{ request()->routeIs('school.reports.index') && !request()->query('category') ? 'active infix-nav-active' : '' }}">
                                <span class="sub-bullet"></span>
                                <span>Reports Overview</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('school.reports.index', ['category' => 'academic']) }}" class="{{ request()->query('category') === 'academic' ? 'active infix-nav-active' : '' }}">
                                <span class="sub-bullet"></span>
                                <span>Academic &amp; Exams</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('school.reports.index', ['category' => 'attendance']) }}" class="{{ request()->query('category') === 'attendance' ? 'active infix-nav-active' : '' }}">
                                <span class="sub-bullet"></span>
                                <span>Attendance Registers</span>
                            </a>
                        </li>
                        @if(Auth::user()->isAdmin() || Auth::user()->isCashier())
                        <li>
                            <a href="{{ route('school.reports.index', ['category' => 'finance']) }}" class="{{ request()->query('category') === 'finance' ? 'active infix-nav-active' : '' }}">
                                <span class="sub-bullet"></span>
                                <span>Fees &amp; Treasury</span>
                            </a>
                        </li>
                        @endif
                        <li>
                            <a href="{{ route('school.reports.index', ['category' => 'students']) }}" class="{{ request()->query('category') === 'students' ? 'active infix-nav-active' : '' }}">
                                <span class="sub-bullet"></span>
                                <span>Student Demographics</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('school.reports.index', ['category' => 'faculty']) }}" class="{{ request()->query('category') === 'faculty' ? 'active infix-nav-active' : '' }}">
                                <span class="sub-bullet"></span>
                                <span>Faculty &amp; Workload</span>
                            </a>
                        </li>
                    </ul>
                </li>
                @endif

                <!-- ================= 8. LEARNING & CBT ARENA ================= -->
                <span class="menu_seperator">LEARNING ARENA</span>

                @php
                    $isLearningOpen = request()->routeIs('courses.*', 'dpp.*', 'quizzes.*', 'homework.*', 'assignments.*', 'projects.*', 'leaderboard.*');
                @endphp
                <li class="sidebar-dropdown {{ $isLearningOpen ? 'dropdown-open' : '' }}">
                    <a href="javascript:void(0)" class="has-arrow {{ $isLearningOpen ? 'active-category' : '' }}" aria-expanded="{{ $isLearningOpen ? 'true' : 'false' }}">
                        <div class="infix-nav-icon"><i class="ti-desktop"></i></div>
                        <div class="nav_title"><span>Academic Arena</span></div>
                        <i class="ti-angle-down dropdown-chevron"></i>
                    </a>
                    <ul class="sidebar-submenu" style="{{ $isLearningOpen ? 'display: block;' : 'display: none;' }}">
                        <li>
                            <a href="{{ route('courses.index') }}" class="{{ request()->routeIs('courses.*') ? 'active infix-nav-active' : '' }}">
                                <span class="sub-bullet"></span>
                                <span>Courses &amp; Handouts</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('dpp.index') }}" class="{{ request()->routeIs('dpp.*') ? 'active infix-nav-active' : '' }}">
                                <span class="sub-bullet"></span>
                                <span>Daily DPPs &amp; CBT</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('quizzes.index') }}" class="{{ request()->routeIs('quizzes.*') ? 'active infix-nav-active' : '' }}">
                                <span class="sub-bullet"></span>
                                <span>Live Quizzes</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('homework.index') }}" class="{{ request()->routeIs('homework.*') ? 'active infix-nav-active' : '' }}">
                                <span class="sub-bullet"></span>
                                <span>Homework &amp; Tasks</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('assignments.index') }}" class="{{ request()->routeIs('assignments.*') ? 'active infix-nav-active' : '' }}">
                                <span class="sub-bullet"></span>
                                <span>Assignments</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('projects.index') }}" class="{{ request()->routeIs('projects.*') ? 'active infix-nav-active' : '' }}">
                                <span class="sub-bullet"></span>
                                <span>Projects &amp; Case Studies</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('leaderboard.index') }}" class="{{ request()->routeIs('leaderboard.*') ? 'active infix-nav-active' : '' }}">
                                <span class="sub-bullet"></span>
                                <span>Leaderboard &amp; Ranks</span>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- ================= 9. DIGITAL CAMPUS & MEDIA ================= -->
                @php
                    $isCampusOpen = request()->routeIs('virtual-classes.*', 'videos.*', 'events.*');
                @endphp
                <li class="sidebar-dropdown {{ $isCampusOpen ? 'dropdown-open' : '' }}">
                    <a href="javascript:void(0)" class="has-arrow {{ $isCampusOpen ? 'active-category' : '' }}" aria-expanded="{{ $isCampusOpen ? 'true' : 'false' }}">
                        <div class="infix-nav-icon"><i class="ti-video-camera"></i></div>
                        <div class="nav_title"><span>Virtual Campus</span></div>
                        <i class="ti-angle-down dropdown-chevron"></i>
                    </a>
                    <ul class="sidebar-submenu" style="{{ $isCampusOpen ? 'display: block;' : 'display: none;' }}">
                        <li>
                            <a href="{{ route('virtual-classes.index') }}" class="{{ request()->routeIs('virtual-classes.*') ? 'active infix-nav-active' : '' }}">
                                <span class="sub-bullet"></span>
                                <span>Virtual Live Classes</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('videos.index') }}" class="{{ request()->routeIs('videos.*') ? 'active infix-nav-active' : '' }}">
                                <span class="sub-bullet"></span>
                                <span>Video Lecture Library</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('events.index') }}" class="{{ request()->routeIs('events.*') ? 'active infix-nav-active' : '' }}">
                                <span class="sub-bullet"></span>
                                <span>Academy Events</span>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- ================= 10. COMMUNICATION ================= -->
                <li>
                    <a href="{{ route('notices.index') }}" class="{{ request()->routeIs('notices.*') ? 'active infix-nav-active' : '' }}">
                        <div class="infix-nav-icon"><i class="ti-bell"></i></div>
                        <div class="nav_title"><span>Circulars &amp; Notices</span></div>
                    </a>
                </li>
                <li>
                    <a href="{{ route('notifications.index') }}" class="{{ request()->routeIs('notifications.*') ? 'active infix-nav-active' : '' }}">
                        <div class="infix-nav-icon"><i class="ti-email"></i></div>
                        <div class="nav_title"><span>Notifications Hub</span></div>
                    </a>
                </li>

                <!-- ================= 11. SYSTEM & SETTINGS (Admin Only) ================= -->
                @if(Auth::user()->isAdmin())
                <span class="menu_seperator">SYSTEM ADMIN</span>

                @php
                    $isSystemOpen = request()->routeIs('appearance.*', 'settings.*', 'school.bulk.*', 'school.onboarding.*');
                @endphp
                <li class="sidebar-dropdown {{ $isSystemOpen ? 'dropdown-open' : '' }}">
                    <a href="javascript:void(0)" class="has-arrow {{ $isSystemOpen ? 'active-category' : '' }}" aria-expanded="{{ $isSystemOpen ? 'true' : 'false' }}">
                        <div class="infix-nav-icon"><i class="ti-settings"></i></div>
                        <div class="nav_title"><span>System Settings</span></div>
                        <i class="ti-angle-down dropdown-chevron"></i>
                    </a>
                    <ul class="sidebar-submenu" style="{{ $isSystemOpen ? 'display: block;' : 'display: none;' }}">
                        <li>
                            <a href="{{ route('school.administration.index') }}" class="{{ request()->routeIs('school.administration.*') ? 'active infix-nav-active' : '' }}">
                                <i class="ti ti-building-community"></i><span>School Administration</span>
                            </a>
                            <a href="{{ route('settings.index') }}" class="{{ request()->routeIs('settings.*') ? 'active infix-nav-active' : '' }}">
                                <span class="sub-bullet"></span>
                                <span>Institution Settings</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('appearance.index') }}" class="{{ request()->routeIs('appearance.*') ? 'active infix-nav-active' : '' }}">
                                <span class="sub-bullet"></span>
                                <span>Themes &amp; Appearance</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('school.bulk.index') }}" class="{{ request()->routeIs('school.bulk.*') ? 'active infix-nav-active' : '' }}">
                                <span class="sub-bullet"></span>
                                <span>Bulk Data Hub</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('school.onboarding.index') }}" class="{{ request()->routeIs('school.onboarding.*') ? 'active infix-nav-active' : '' }}">
                                <span class="sub-bullet"></span>
                                <span>Onboarding Wizard</span>
                            </a>
                        </li>
                    </ul>
                </li>
                @endif

                @if(Auth::user()->isStudent())
                <!-- STUDENT SPECIFIC SHORTCUTS -->
                <span class="menu_seperator">MY ACADEMICS</span>
                <li>
                    <a href="{{ route('my-batch') }}" class="{{ request()->routeIs('my-batch*') ? 'active infix-nav-active' : '' }}">
                        <div class="infix-nav-icon"><i class="ti-bookmark-alt"></i></div>
                        <div class="nav_title"><span>My Class &amp; Section</span></div>
                    </a>
                </li>
                <li>
                    <a href="{{ route('school.timetable.index') }}" class="{{ request()->routeIs('school.timetable.*') ? 'active infix-nav-active' : '' }}">
                        <div class="infix-nav-icon"><i class="ti-calendar"></i></div>
                        <div class="nav_title"><span>Class Timetable</span></div>
                    </a>
                </li>
                <li>
                    <a href="{{ route('school.exams.index') }}" class="{{ request()->routeIs('school.exams.*', 'school.report-cards.*') ? 'active infix-nav-active' : '' }}">
                        <div class="infix-nav-icon"><i class="ti-medall"></i></div>
                        <div class="nav_title"><span>Report Cards</span></div>
                    </a>
                </li>
                <li>
                    <a href="{{ route('attendance.index') }}" class="{{ request()->routeIs('attendance.*') ? 'active infix-nav-active' : '' }}">
                        <div class="infix-nav-icon"><i class="ti-check-box"></i></div>
                        <div class="nav_title"><span>My Attendance</span></div>
                    </a>
                </li>
                <li>
                    <a href="{{ route('school.fees.index') }}" class="{{ request()->routeIs('school.fees.*') ? 'active infix-nav-active' : '' }}">
                        <div class="infix-nav-icon"><i class="ti-wallet"></i></div>
                        <div class="nav_title"><span>My Fee Invoices</span></div>
                    </a>
                </li>
                <li>
                    <a href="{{ route('idcard.show') }}" class="{{ request()->routeIs('idcard.*') ? 'active infix-nav-active' : '' }}">
                        <div class="infix-nav-icon"><i class="ti-id-badge"></i></div>
                        <div class="nav_title"><span>Digital ID Card</span></div>
                    </a>
                </li>
                <li>
                    <a href="{{ route('school.library.index') }}" class="{{ request()->routeIs('school.library.*') ? 'active infix-nav-active' : '' }}">
                        <div class="infix-nav-icon"><i class="ti-book"></i></div>
                        <div class="nav_title"><span>Library Books</span></div>
                    </a>
                </li>
                <li>
                    <a href="{{ route('school.study-materials.index') }}" class="{{ request()->routeIs('school.study-materials.*') ? 'active infix-nav-active' : '' }}">
                        <div class="infix-nav-icon"><i class="ti-download"></i></div>
                        <div class="nav_title"><span>Download Center</span></div>
                    </a>
                </li>
                @endif

            </ul>

            <!-- Sidebar Bottom: Theme Mode Switcher -->
            <div class="p-3 border-t border-slate-200 dark:border-slate-800 mt-auto bg-slate-50/60 dark:bg-slate-900/40">
                <button type="button" 
                        onclick="toggleThemeMode()" 
                        class="w-full flex items-center justify-between p-2 rounded-xl text-slate-700 dark:text-slate-300 hover:bg-slate-200/60 dark:hover:bg-slate-800 transition">
                    <div class="flex items-center space-x-2 text-xs font-semibold">
                        <span class="w-6 h-6 rounded-lg bg-amber-100 dark:bg-purple-950/60 text-amber-600 dark:text-purple-400 flex items-center justify-center">
                            <i class="ti-sun dark:hidden text-xs"></i>
                            <i class="ti-moon hidden dark:block text-xs"></i>
                        </span>
                        <span>Theme Mode</span>
                    </div>
                    <div class="flex items-center space-x-1.5">
                        <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 theme-mode-label">Light</span>
                        <div class="relative w-7 h-4 bg-slate-300 dark:bg-purple-950/80 rounded-full transition-colors flex items-center p-0.5 border border-slate-300/80 dark:border-purple-600/50">
                            <div class="w-3 h-3 rounded-full bg-white dark:bg-purple-400 shadow-xs transform transition-transform translate-x-0 dark:translate-x-3"></div>
                        </div>
                    </div>
                </button>
            </div>
        </nav>

        <!-- Sidebar Mobile Backdrop Overlay -->
        <div x-show="sidebarOpen" 
             x-cloak 
             @click="sidebarOpen = false" 
             class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-45 lg:hidden">
        </div>
        @endauth

        <!-- ========================================================================= -->
        <!-- 2. INFIX MAIN CONTENT AREA (#main-content)                                -->
        <!-- ========================================================================= -->
        <div id="main-content" style="{{ !Auth::check() ? 'margin-left: 0; width: 100%;' : '' }}">

            <!-- Infix Top Header (.header_iner) -->
            <div class="header_iner flex items-center justify-between gap-2 sm:gap-4 px-2 sm:px-6 w-full" id="top-nav-container">
                
                <!-- Left: Sidebar Toggle & Logo Shortcut -->
                <div class="flex items-center space-x-1 sm:space-x-2 flex-shrink-0">
                    @auth
                    <button type="button" 
                            @click="sidebarOpen = !sidebarOpen" 
                            class="p-2 rounded-xl text-slate-500 hover:text-slate-800 dark:hover:text-white transition focus:outline-none"
                            title="Toggle Sidebar">
                        <i class="ti-menu text-base"></i>
                    </button>
                    @endauth
                    <a href="{{ route('dashboard') }}" class="lg:hidden flex items-center flex-shrink-0" title="Home">
                        <div class="w-7 h-7 rounded-lg flex items-center justify-center text-white font-bold text-[10px] shadow-xs" style="background: var(--system_primary_gradient);">
                            <i class="ti-book"></i>
                        </div>
                    </a>
                </div>

                <!-- Center: Global Search Bar (Responsive Auto-Spacing on Android & Desktop) -->
                <div class="flex-1 min-w-0 max-w-xl mx-1 sm:mx-4 relative" id="global-search-container">
                    <div class="search_field w-full relative flex items-center">
                        <i class="ti-search absolute left-3 text-slate-400 text-xs sm:text-sm pointer-events-none"></i>
                        <input type="text" 
                               id="infixGlobalSearch" 
                               autocomplete="off"
                               placeholder="Search anything... (Ctrl + K)" 
                               class="w-full h-9 sm:h-10 pl-8 sm:pl-9 pr-7 sm:pr-8 text-xs rounded-full border border-slate-200 dark:border-slate-700 bg-slate-50/80 dark:bg-slate-800/80 text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-purple-500/30 focus:border-purple-500 transition-all shadow-2xs"
                               oninput="handleGlobalSearch(this.value)"
                               onfocus="handleGlobalSearchFocus()"
                               onkeydown="handleGlobalSearchKeydown(event)">
                        <button type="button" 
                                id="clearSearchBtn" 
                                onclick="clearGlobalSearch()" 
                                class="hidden absolute right-2.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-xs p-1"
                                title="Clear Search">
                            <i class="ti-close"></i>
                        </button>
                    </div>

                    <!-- Global Search Results Dropdown Modal -->
                    <div id="globalSearchResults" 
                         class="hidden absolute left-0 right-0 top-full mt-2 bg-white/95 dark:bg-slate-900/95 backdrop-blur-xl border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl overflow-hidden z-50 max-h-[75vh] flex flex-col transition-all">
                        <div class="p-3 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs text-slate-400">
                            <span class="font-bold tracking-wider uppercase text-[10px]" id="searchResultCount">Quick Search</span>
                            <span class="text-[10px] hidden sm:inline">ESC to close &bull; &uarr;&darr; to navigate</span>
                        </div>
                        <div id="searchResultItems" class="overflow-y-auto p-2 space-y-1">
                            <!-- Populated dynamically via JS -->
                        </div>
                    </div>
                </div>

                <!-- Middle-Right: Website & Themes Quick Shortcut Pill Buttons (Desktop) -->
                <div class="hidden md:flex items-center space-x-2 flex-shrink-0">
                    <a href="{{ route('home') }}" target="_blank" class="primary-btn white" style="padding: 0.4rem 1.1rem; font-size: 0.75rem;">
                        <i class="ti-world text-xs"></i>
                        <span>Website</span>
                    </a>
                    @if(Auth::check() && Auth::user()->isAdmin())
                    <a href="{{ route('appearance.index') }}" class="primary-btn" style="padding: 0.4rem 1.1rem; font-size: 0.75rem;">
                        <i class="ti-palette text-xs"></i>
                        <span>Themes</span>
                    </a>
                    @endif
                </div>

                <!-- Right: Light/Dark Mode, Notifications Pulse, Profile Dropdown -->
                <div class="flex items-center space-x-1.5 sm:space-x-2.5" id="navbar-auth-actions">
                    
                    <!-- Fullscreen Toggle Button -->
                    <button id="fullscreen-toggle-btn" type="button" onclick="toggleAppFullscreen()" title="Toggle Fullscreen" class="p-1.5 sm:p-2 rounded-xl text-slate-500 hover:text-slate-800 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                        <svg id="fs-expand-icon" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-5h-4m4 0v4m0-4l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
                        <svg id="fs-compress-icon" class="w-4 h-4 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>

                    <!-- PWA Hidden Triggers -->
                    <button id="pwa-install-btn" style="display: none;" type="button"></button>
                    <button id="pwa-mobile-install-btn" style="display: none;" type="button"></button>

                    <!-- Light / Dark Mode Toggle Button -->
                    <button id="theme-toggle-btn" 
                            data-theme-toggle
                            type="button" 
                            onclick="toggleThemeMode()" 
                            class="inline-flex items-center gap-1.5 sm:gap-2 px-2 sm:px-2.5 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 border border-slate-200/80 dark:border-slate-700/80 text-slate-700 dark:text-slate-200 transition shadow-xs focus:outline-none"
                            title="Toggle Light / Dark Mode"
                            role="switch"
                            aria-checked="false">
                        <div class="relative w-8 h-4.5 bg-slate-300 dark:bg-purple-950/80 rounded-full transition-colors duration-200 flex items-center p-0.5 border border-slate-300/80 dark:border-purple-600/50 pointer-events-none">
                            <div class="w-3.5 h-3.5 rounded-full bg-white dark:bg-purple-400 shadow-xs flex items-center justify-center transform transition-transform duration-200 translate-x-0 dark:translate-x-3.5">
                                <i class="ti-sun text-[8px] text-amber-500 dark:hidden"></i>
                                <i class="ti-moon text-[8px] text-purple-950 hidden dark:block"></i>
                            </div>
                        </div>
                        <span class="text-xs font-bold hidden sm:inline-block theme-mode-label">Light</span>
                    </button>

                    @auth
                    <!-- Infix Notification Bell with animated pulse ring -->
                    @php $unreadCount = Auth::user()->unreadNotifications()->count(); @endphp
                    <a href="{{ route('notifications.index') }}" class="relative p-2 rounded-xl text-slate-500 hover:text-slate-800 dark:hover:text-white transition {{ $unreadCount > 0 ? 'pulse theme_color' : '' }}" title="Notifications">
                        <i class="ti-bell text-base"></i>
                        @if($unreadCount > 0)
                        <span class="absolute top-1 right-1 w-4 h-4 bg-rose-500 text-white rounded-full text-[9px] font-black flex items-center justify-center">
                            {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                        </span>
                        @endif
                    </a>

                    <!-- Infix User Profile Dropdown (.profile_info) -->
                    <div class="relative" x-data="{ profileDropdown: false }" @click.away="profileDropdown = false">
                        <button type="button" id="user-profile-menu-button" data-testid="user-profile-menu-button" class="profile_info flex items-center space-x-2 cursor-pointer focus:outline-hidden" @click="profileDropdown = !profileDropdown">
                            <div class="profileThumb">
                                {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                            </div>
                            <div class="hidden xl:block text-left leading-tight">
                                <span class="text-xs font-bold text-slate-800 dark:text-white block truncate max-w-[120px]">{{ Auth::user()->name }}</span>
                                <span class="text-[10px] text-slate-400 capitalize block">{{ Auth::user()->role }}</span>
                            </div>
                            <i class="ti-angle-down text-[10px] text-slate-400 hidden xl:block transition-transform duration-200" :class="profileDropdown ? 'rotate-180' : ''"></i>
                        </button>

                        <!-- Dropdown Card -->
                        <div x-show="profileDropdown" 
                             x-cloak 
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 translate-y-1"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             class="absolute right-0 mt-2 w-56 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xl p-2 z-50 space-y-1 text-xs">
                            <div class="p-3 bg-slate-50 dark:bg-slate-800/60 rounded-xl mb-1">
                                <div class="font-bold text-slate-800 dark:text-white truncate">{{ Auth::user()->name }}</div>
                                <div class="text-[10px] text-slate-400 truncate">{{ Auth::user()->email }}</div>
                            </div>
                            <a href="{{ route('profile.show') }}" class="flex items-center space-x-2 px-3 py-2 rounded-xl font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                                <i class="ti-user theme_color"></i>
                                <span>My Profile</span>
                            </a>
                            @if(Auth::user()->isAdmin())
                            <a href="{{ route('appearance.index') }}" class="flex items-center space-x-2 px-3 py-2 rounded-xl font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                                <i class="ti-palette theme_color"></i>
                                <span>Themes &amp; Appearance</span>
                            </a>
                            <a href="{{ route('settings.index') }}" class="flex items-center space-x-2 px-3 py-2 rounded-xl font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                                <i class="ti-settings theme_color"></i>
                                <span>Institution Settings</span>
                            </a>
                            @endif
                            <!-- Quick Dark Mode Toggle Option -->
                            <div class="px-3 py-2 rounded-xl flex items-center justify-between hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer select-none" onclick="toggleThemeMode()">
                                <div class="flex items-center space-x-2 font-semibold text-slate-600 dark:text-slate-300">
                                    <i class="ti-shine theme_color"></i>
                                    <span>Dark Mode</span>
                                </div>
                                <div class="flex items-center space-x-1.5">
                                    <span class="text-[10px] font-bold text-slate-400 theme-mode-label">Light</span>
                                    <div class="relative w-7 h-4 bg-slate-300 dark:bg-purple-950/80 rounded-full transition-colors flex items-center p-0.5 border border-slate-300/80 dark:border-purple-600/50">
                                        <div class="w-3 h-3 rounded-full bg-white dark:bg-purple-400 shadow-xs transform transition-transform translate-x-0 dark:translate-x-3"></div>
                                    </div>
                                </div>
                            </div>
                            <form method="POST" action="{{ route('logout') }}" class="pt-1 border-t border-slate-100 dark:border-slate-800">
                                @csrf
                                <button type="submit" class="w-full flex items-center space-x-2 px-3 py-2 rounded-xl font-semibold text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition">
                                    <i class="ti-power-off"></i>
                                    <span>Sign Out</span>
                                </button>
                            </form>
                        </div>
                    </div>
                    @else
                    <a href="{{ route('login') }}" class="primary-btn" style="padding: 0.4rem 1.1rem; font-size: 0.75rem;">
                        Sign In
                    </a>
                    @endauth

                </div>
            </div>

            <!-- Global Flash Messages & Error Bags -->
            <div class="px-4 sm:px-6 lg:px-8 pt-4">
                @if(session('success'))
                <div class="p-3.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-xs font-semibold flex items-center justify-between shadow-xs mb-3">
                    <div class="flex items-center space-x-2">
                        <i class="ti-check-box text-sm"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-emerald-600 hover:text-emerald-900 text-sm">✕</button>
                </div>
                @endif

                @if(session('error'))
                <div class="p-3.5 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-300 text-xs font-semibold flex items-center justify-between shadow-xs mb-3">
                    <div class="flex items-center space-x-2">
                        <i class="ti-alert text-sm"></i>
                        <span>{{ session('error') }}</span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-rose-600 hover:text-rose-900 text-sm">✕</button>
                </div>
                @endif

                @if(isset($errors) && $errors->any())
                <div class="p-3.5 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 text-amber-800 dark:text-amber-300 text-xs font-semibold shadow-xs mb-3">
                    <div class="flex items-center space-x-2 mb-1">
                        <i class="ti-info-alt text-sm"></i>
                        <span>Please correct the following errors:</span>
                    </div>
                    <ul class="list-disc list-inside space-y-0.5 text-[11px] opacity-90 pl-4">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif
            </div>

            <!-- Inline Content Spinner Animation (Active during transitions with pure glass blur) -->
            <div id="spa-inline-spinner" class="fixed inset-0 z-40 pointer-events-none hidden transition-opacity duration-200 flex items-center justify-center spa-spinner-backdrop">
                <div class="spa-spinner-card">
                    <div class="infix-spinner-wrapper infix-spinner">
                        <div class="infix-spinner-ring outer-ring"></div>
                        <div class="infix-spinner-ring middle-ring"></div>
                        <div class="infix-spinner-ring inner-ring"></div>
                        <div class="infix-spinner-core"></div>
                        <div class="infix-spinner-orbit-dot"></div>
                        <div class="infix-spinner-orbit-dot-2"></div>
                    </div>
                    <span class="spa-spinner-title tracking-wider">Loading...</span>
                </div>
            </div>

            <!-- Page Main Content View -->
            <main id="spa-content" class="flex-1 p-4 sm:p-6 lg:p-8 w-full max-w-[1720px] mx-auto pb-24 md:pb-8">
                @yield('content')
            </main>

            <!-- Infix LMS System Footer -->
            <footer class="py-4 px-6 border-t border-slate-200 dark:border-slate-800 text-xs text-slate-500 flex flex-col sm:flex-row items-center justify-between gap-2 bg-white dark:bg-slate-900 mt-auto pb-20 md:pb-4">
                <div>
                    &copy; {{ date('Y') }} <strong>{{ $school['name'] }}</strong> &bull; {{ $school['motto'] }} &bull; Developed by Prithviraj with love for India 🇮🇳
                </div>
                <div class="flex items-center space-x-2">
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold text-white shadow-xs" style="background: var(--system_primary_gradient);">
                        {{ $appearance['theme']['name'] ?? 'Infix LMS Classic' }}
                    </span>
                    <span class="text-[11px] font-semibold text-slate-400">⚡ Powered by PrimeOS &bull; Infix Design</span>
                </div>
            </footer>

        </div>
    </div>

    @auth
    <!-- Role-Based Bottom Navigation Tabs (Mobile/Tablet Max 5 Tabs) -->
    <x-bottom-tabs />
    @endauth

    <!-- Progressive Web App (PWA) & Fullscreen Manager -->
    <script src="{{ asset('js/pwa-fullscreen.js') }}"></script>

    @auth
    <!-- Single Active Session Guard -->
    <script>
        (function() {
            const userId = {{ Auth::id() }};
            const tabId = 'tab_' + Math.random().toString(36).substring(2, 10) + '_' + Date.now();
            const channelName = 'pa_active_session_' + userId;
            const storageKey = 'pa_active_tab_' + userId;

            localStorage.setItem(storageKey, tabId);
            window.addEventListener('storage', function(e) {
                if (e.key === storageKey && e.newValue && e.newValue !== tabId) {
                    console.log('Another tab activated');
                }
            });
        })();
    </script>
    @endauth

    <!-- Global Live Search & Sidebar Dropdown Navigation Engine -->
    <script>
        // 1. Client-Side Global Search Index
        const GLOBAL_SEARCH_INDEX = [
            // Academics
            { title: 'Classes & Streams', category: 'Academics', icon: 'ti-layers-alt', url: '{{ route('school.classes.index') }}', keywords: 'class grade stream section academic setup' },
            { title: 'Sections Hub', category: 'Academics', icon: 'ti-layout-grid2', url: '{{ route('school.sections.index') }}', keywords: 'sections division class room' },
            { title: 'Curriculum Subjects', category: 'Academics', icon: 'ti-book', url: '{{ route('school.subjects.index') }}', keywords: 'subjects courses curriculum books syllabus' },
            { title: 'Chapterwise Syllabus', category: 'Academics', icon: 'ti-view-list', url: '{{ route('syllabus.index') }}', keywords: 'syllabus chapters topics lesson plan progress' },
            { title: 'Class Timetable', category: 'Academics', icon: 'ti-calendar', url: '{{ route('school.timetable.index') }}', keywords: 'timetable schedule routine periods slots' },
            { title: 'School Holidays', category: 'Academics', icon: 'ti-calendar', url: '{{ route('school.holidays.index') }}', keywords: 'holidays vacation calendar festival leaves' },
            { title: 'Faculty Assignments', category: 'Academics', icon: 'ti-user', url: '{{ route('school.assignments.index') }}', keywords: 'faculty teacher class teacher assignment' },
            { title: "Bloom's Taxonomy", category: 'Academics', icon: 'ti-thought', url: '{{ route('blooms.index') }}', keywords: 'blooms taxonomy cognitive levels tasks' },
            
            // Students & Faculty
            { title: 'Student Directory', category: 'Students', icon: 'ti-id-badge', url: '{{ route('school.students.index') }}', keywords: 'student directory admission enroll roster' },
            { title: 'Student Records', category: 'Students', icon: 'ti-user', url: '{{ route('students.index') }}', keywords: 'student records profile details contact' },
            { title: 'Staff Directory', category: 'Staff', icon: 'ti-user', url: '{{ route('staff.index') }}', keywords: 'staff faculty teacher employees directory' },
            { title: 'Digital ID Cards', category: 'Students', icon: 'ti-id-badge', url: '{{ route('idcard.show') }}', keywords: 'id card digital virtual barcode qr' },
            { title: 'Admissions Hub', category: 'Students', icon: 'ti-agenda', url: '{{ route('admissions') }}', keywords: 'admissions application new admission enquiry' },
            @if(Auth::check() && Auth::user()->isAdmin())
            { title: 'Admission Enquiries', category: 'Students', icon: 'ti-agenda', url: '{{ route('school.admissions.enquiries.index') }}', keywords: 'admission enquiries leads follow up applicant' },
            @endif
            @if(Auth::check() && Auth::user()->isAdmin())
            { title: 'Faculty Auth Codes', category: 'Staff', icon: 'ti-key', url: '{{ route('admin.faculty-codes.index') }}', keywords: 'faculty codes locks devices security' },
            @endif

            // Attendance & Leaves
            { title: 'Daily Attendance', category: 'Attendance', icon: 'ti-check-box', url: '{{ route('school.attendance.index') }}', keywords: 'attendance daily mark register present absent' },
            { title: 'Leave Applications', category: 'Attendance', icon: 'ti-notepad', url: '{{ route('leaves.index') }}', keywords: 'leaves application request sick casual' },

            // Fees & Finance
            { title: 'Fee Management Hub', category: 'Finance', icon: 'ti-wallet', url: '{{ route('school.fees.index') }}', keywords: 'fees payment invoice receipt dues collections finance upi' },

            // Examinations & Grades
            { title: 'Exams & Grades', category: 'Exams', icon: 'ti-medall', url: '{{ route('school.exams.index') }}', keywords: 'exams grades report card marks test assessment' },
            { title: 'Marks Entry Register', category: 'Exams', icon: 'ti-write', url: '{{ route('school.exams.marks-entry') }}', keywords: 'marks entry score grading evaluation' },
            { title: 'Certificates & Badges', category: 'Exams', icon: 'ti-cup', url: '{{ route('certificates.index') }}', keywords: 'certificates badges achievements honors verify' },

            // Reports Center
            @if(Auth::check() && (Auth::user()->isAdmin() || Auth::user()->isTeacher()))
            { title: 'Institutional Reports Center', category: 'Reports', icon: 'ti-pie-chart', url: '{{ route('school.reports.index') }}', keywords: 'reports center intelligence analytics merit attendance finance audit' },
            { title: 'Exam Merit List & Rankings', category: 'Reports', icon: 'ti-medall', url: '{{ route('school.reports.index', ['category' => 'academic', 'report_type' => 'exam_merit_list']) }}', keywords: 'exam merit list rankings gpa pass fail report' },
            { title: 'Daily Attendance Report', category: 'Reports', icon: 'ti-check-box', url: '{{ route('school.reports.index', ['category' => 'attendance', 'report_type' => 'attendance_summary']) }}', keywords: 'attendance summary chronic absenteeism register report' },
            @if(Auth::user()->isAdmin())
            { title: 'Fee Collection & Defaulters Report', category: 'Reports', icon: 'ti-wallet', url: '{{ route('school.reports.index', ['category' => 'finance', 'report_type' => 'fee_collection']) }}', keywords: 'fee collection defaulters arrears dues treasury finance report' },
            @endif
            { title: 'Class Strength & Capacity Report', category: 'Reports', icon: 'ti-id-badge', url: '{{ route('school.reports.index', ['category' => 'students', 'report_type' => 'class_strength']) }}', keywords: 'class strength capacity admissions demographics report' },
            { title: 'Faculty Workload Matrix', category: 'Reports', icon: 'ti-user', url: '{{ route('school.reports.index', ['category' => 'faculty', 'report_type' => 'teacher_workload']) }}', keywords: 'faculty workload teacher periods allocation roster report' },
            @endif

            // Learning Arena
            { title: 'Courses Catalog', category: 'Learning', icon: 'ti-book', url: '{{ route('courses.index') }}', keywords: 'courses catalog lessons materials handouts pdf' },
            { title: 'Daily DPPs & CBT', category: 'Learning', icon: 'ti-timer', url: '{{ route('dpp.index') }}', keywords: 'dpp daily practice problems cbt test online' },
            { title: 'Live Quizzes', category: 'Learning', icon: 'ti-target', url: '{{ route('quizzes.index') }}', keywords: 'quizzes live contest mcq questions arena' },
            { title: 'Homework & Tasks', category: 'Learning', icon: 'ti-pencil-alt', url: '{{ route('homework.index') }}', keywords: 'homework tasks assignment submissions check' },
            { title: 'Assignments', category: 'Learning', icon: 'ti-files', url: '{{ route('assignments.index') }}', keywords: 'assignments projects submissions files' },
            { title: 'Projects & Case Studies', category: 'Learning', icon: 'ti-light-bulb', url: '{{ route('projects.index') }}', keywords: 'projects case study research capstone' },
            { title: 'Hall of Fame & Leaderboard', category: 'Learning', icon: 'ti-crown', url: '{{ route('leaderboard.index') }}', keywords: 'leaderboard rankings score points top students' },

            // Digital Campus
            { title: 'Virtual Live Classes', category: 'Campus', icon: 'ti-video-camera', url: '{{ route('virtual-classes.index') }}', keywords: 'virtual classes live zoom google meet video conference' },
            { title: 'Video Lecture Library', category: 'Campus', icon: 'ti-video-clapper', url: '{{ route('videos.index') }}', keywords: 'videos lecture recordings class playback watch' },
            { title: 'Academy Events', category: 'Campus', icon: 'ti-flag-alt', url: '{{ route('events.index') }}', keywords: 'events seminars workshops sports sports day annual day' },

            // Communication & Notices
            { title: 'Circulars & Notices', category: 'Communication', icon: 'ti-bell', url: '{{ route('notices.index') }}', keywords: 'notices circulars announcements alerts bulletin' },
            { title: 'Notifications Hub', category: 'Communication', icon: 'ti-email', url: '{{ route('notifications.index') }}', keywords: 'notifications unread messages inbox alerts' },

            // System Administration
            @if(Auth::check() && Auth::user()->isAdmin())
            { title: 'Institution Settings', category: 'System', icon: 'ti-settings', url: '{{ route('settings.index') }}', keywords: 'settings system configuration school profile general' },
            { title: 'Themes & Appearance', category: 'System', icon: 'ti-palette', url: '{{ route('appearance.index') }}', keywords: 'themes appearance color dark mode light preloader logo' },
            { title: 'Bulk Data Hub', category: 'System', icon: 'ti-upload', url: '{{ route('school.bulk.index') }}', keywords: 'bulk import export csv students staff credentials' },
            @endif
            { title: 'Academic Analytics', category: 'System', icon: 'ti-bar-chart', url: '{{ route('analytics.index') }}', keywords: 'analytics charts reports metrics performance' }
        ];

        let selectedSearchIndex = -1;

        function handleGlobalSearchFocus() {
            const input = document.getElementById('infixGlobalSearch');
            if (input && input.value.trim().length > 0) {
                handleGlobalSearch(input.value);
            }
        }

        function clearGlobalSearch() {
            const input = document.getElementById('infixGlobalSearch');
            const clearBtn = document.getElementById('clearSearchBtn');
            const results = document.getElementById('globalSearchResults');
            if (input) input.value = '';
            if (clearBtn) clearBtn.classList.add('hidden');
            if (results) results.classList.add('hidden');
            selectedSearchIndex = -1;
        }

        function handleGlobalSearch(query) {
            const clearBtn = document.getElementById('clearSearchBtn');
            const resultsModal = document.getElementById('globalSearchResults');
            const itemsContainer = document.getElementById('searchResultItems');
            const countLabel = document.getElementById('searchResultCount');

            const q = query.trim().toLowerCase();
            if (!q) {
                if (clearBtn) clearBtn.classList.add('hidden');
                if (resultsModal) resultsModal.classList.add('hidden');
                selectedSearchIndex = -1;
                return;
            }

            if (clearBtn) clearBtn.classList.remove('hidden');

            const matches = GLOBAL_SEARCH_INDEX.filter(item => {
                return item.title.toLowerCase().includes(q) || 
                       item.category.toLowerCase().includes(q) || 
                       item.keywords.toLowerCase().includes(q);
            });

            selectedSearchIndex = -1;

            let html = '';
            if (matches.length > 0) {
                if (countLabel) countLabel.textContent = `${matches.length} Result${matches.length > 1 ? 's' : ''} Found`;
                matches.slice(0, 10).forEach((item, idx) => {
                    html += `
                        <a href="${item.url}" class="search-result-item flex items-center justify-between p-2.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition group" data-idx="${idx}">
                            <div class="flex items-center space-x-3 min-w-0">
                                <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-purple-50 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 group-hover:bg-purple-600 group-hover:text-white transition flex-shrink-0">
                                    <i class="${item.icon}"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="text-xs font-bold text-slate-800 dark:text-white truncate">${highlightMatch(item.title, q)}</div>
                                    <div class="text-[10px] text-slate-400 font-medium">${item.category}</div>
                                </div>
                            </div>
                            <i class="ti-arrow-right text-[10px] text-slate-400 opacity-0 group-hover:opacity-100 transition-opacity flex-shrink-0"></i>
                        </a>
                    `;
                });
            } else {
                if (countLabel) countLabel.textContent = 'Quick Actions';
                html += `
                    <div class="p-4 text-center">
                        <i class="ti-search text-2xl text-slate-300 dark:text-slate-600 mb-2 block"></i>
                        <p class="text-xs text-slate-500 font-medium">No direct menu items matching "${escapeHtml(query)}"</p>
                    </div>
                `;
            }

            // Always add general search shortcuts
            html += `
                <div class="pt-2 border-t border-slate-100 dark:border-slate-800 space-y-1">
                    @if(Auth::check() && Auth::user()->isCashier())
                    <a href="{{ route('school.fees.index') }}?search=${encodeURIComponent(query)}" class="search-result-item flex items-center space-x-3 p-2 rounded-xl text-xs font-semibold text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-950/40">
                        <i class="ti-wallet text-sm"></i>
                        <span>Search fee invoices &amp; student ledger for "<strong>${escapeHtml(query)}</strong>"</span>
                    </a>
                    @else
                    <a href="{{ route('school.students.index') }}?q=${encodeURIComponent(query)}" class="search-result-item flex items-center space-x-3 p-2 rounded-xl text-xs font-semibold text-purple-600 dark:text-purple-400 hover:bg-purple-50 dark:hover:bg-purple-950/40">
                        <i class="ti-user text-sm"></i>
                        <span>Search student directory for "<strong>${escapeHtml(query)}</strong>"</span>
                    </a>
                    @if(Auth::check() && (Auth::user()->isAdmin() || Auth::user()->isCashier()))
                    <a href="{{ route('school.fees.index') }}?search=${encodeURIComponent(query)}" class="search-result-item flex items-center space-x-3 p-2 rounded-xl text-xs font-semibold text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-950/40">
                        <i class="ti-wallet text-sm"></i>
                        <span>Search fees &amp; invoices for "<strong>${escapeHtml(query)}</strong>"</span>
                    </a>
                    @endif
                    @endif
                </div>
            `;

            if (itemsContainer) itemsContainer.innerHTML = html;
            if (resultsModal) resultsModal.classList.remove('hidden');
        }

        function highlightMatch(text, query) {
            const index = text.toLowerCase().indexOf(query);
            if (index === -1) return escapeHtml(text);
            const before = text.substring(0, index);
            const match = text.substring(index, index + query.length);
            const after = text.substring(index + query.length);
            return `${escapeHtml(before)}<mark class="bg-amber-200 dark:bg-amber-800 text-slate-900 dark:text-white px-0.5 rounded">${escapeHtml(match)}</mark>${escapeHtml(after)}`;
        }

        function escapeHtml(str) {
            return String(str).replace(/[&<>"']/g, function(m) {
                return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[m];
            });
        }

        function handleGlobalSearchKeydown(e) {
            const resultsModal = document.getElementById('globalSearchResults');
            if (!resultsModal || resultsModal.classList.contains('hidden')) return;

            const items = resultsModal.querySelectorAll('a.search-result-item');
            if (items.length === 0) return;

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                selectedSearchIndex = (selectedSearchIndex + 1) % items.length;
                updateSearchSelection(items);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                selectedSearchIndex = (selectedSearchIndex - 1 + items.length) % items.length;
                updateSearchSelection(items);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (selectedSearchIndex >= 0 && items[selectedSearchIndex]) {
                    items[selectedSearchIndex].click();
                } else if (items[0]) {
                    items[0].click();
                }
            } else if (e.key === 'Escape') {
                clearGlobalSearch();
            }
        }

        function updateSearchSelection(items) {
            items.forEach((item, idx) => {
                if (idx === selectedSearchIndex) {
                    item.classList.add('bg-purple-50', 'dark:bg-purple-950/60');
                    item.scrollIntoView({ block: 'nearest' });
                } else {
                    item.classList.remove('bg-purple-50', 'dark:bg-purple-950/60');
                }
            });
        }

        // Close search when clicking outside
        document.addEventListener('click', function(e) {
            const container = document.getElementById('global-search-container');
            if (container && !container.contains(e.target)) {
                const resultsModal = document.getElementById('globalSearchResults');
                if (resultsModal) resultsModal.classList.add('hidden');
            }
        });

        // Global Keyboard Shortcut: Ctrl+K or '/' focuses global search bar
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey && e.key.toLowerCase() === 'k') || (e.key === '/' && !['INPUT', 'TEXTAREA'].includes(document.activeElement.tagName))) {
                e.preventDefault();
                const searchInput = document.getElementById('infixGlobalSearch');
                if (searchInput) {
                    searchInput.focus();
                    searchInput.select();
                }
            }
        });

        // 2. Sidebar Dropdown Accordion Delegated Handler
        document.addEventListener('click', function(e) {
            const hasArrow = e.target.closest('.has-arrow');
            if (hasArrow) {
                const parent = hasArrow.closest('.sidebar-dropdown');
                if (parent) {
                    e.preventDefault();
                    const isOpen = parent.classList.contains('dropdown-open');
                    parent.classList.toggle('dropdown-open');
                    const submenu = parent.querySelector('.sidebar-submenu');
                    if (submenu) {
                        submenu.style.display = isOpen ? 'none' : 'block';
                    }
                    hasArrow.setAttribute('aria-expanded', !isOpen);
                }
            }
        });

        // Ensure active route submenu is opened on load & SPA navigation
        function syncSidebarDropdowns() {
            document.querySelectorAll('#sidebar_menu .sidebar-dropdown').forEach(dropdown => {
                const hasActiveChild = dropdown.querySelector('a.active, a.infix-nav-active');
                if (hasActiveChild) {
                    dropdown.classList.add('dropdown-open');
                    const submenu = dropdown.querySelector('.sidebar-submenu');
                    if (submenu) submenu.style.display = 'block';
                    const trigger = dropdown.querySelector('.has-arrow');
                    if (trigger) {
                        trigger.setAttribute('aria-expanded', 'true');
                        trigger.classList.add('active-category');
                    }
                }
            });
        }

        window.addEventListener('DOMContentLoaded', syncSidebarDropdowns);
        window.addEventListener('spa:navigated', syncSidebarDropdowns);
    </script>

</body>
</html>
