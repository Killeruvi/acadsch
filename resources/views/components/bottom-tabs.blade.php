@php
    $user = Auth::user();
    if (!$user) return;

    $role = $user->role ?? 'student';
    $currentRoute = request()->route() ? request()->route()->getName() : '';
    $currentUrl = request()->fullUrl();

    // Helper to test if a route or URL is active
    $isActive = function($names, $queryParam = null) use ($currentRoute, $currentUrl) {
        if ($queryParam) {
            return str_contains($currentUrl, $queryParam);
        }
        foreach ((array) $names as $n) {
            if ($currentRoute === $n || str_starts_with($currentRoute, $n . '.')) {
                return true;
            }
        }
        return false;
    };

    // Role-specific definitions (Max 5 Tabs each)
    if ($user->isParent()) {
        $tabs = [
            [
                'id' => 'home',
                'label' => 'Home',
                'icon' => 'ti-home',
                'url' => route('school.parent.dashboard'),
                'active' => $isActive(['school.parent.dashboard']) && !request()->has('tab'),
            ],
            [
                'id' => 'attendance',
                'label' => 'Attendance',
                'icon' => 'ti-check-box',
                'url' => route('school.parent.dashboard', ['tab' => 'attendance']),
                'active' => request()->query('tab') === 'attendance',
            ],
            [
                'id' => 'fees',
                'label' => 'Fees',
                'icon' => 'ti-wallet',
                'url' => route('school.fees.index'),
                'active' => $isActive(['school.fees.index']) || request()->query('tab') === 'fees',
            ],
            [
                'id' => 'leaves',
                'label' => 'Leaves',
                'icon' => 'ti-calendar',
                'url' => route('leaves.index'),
                'active' => $isActive(['leaves.index', 'leaves.store']),
            ],
            [
                'id' => 'more',
                'label' => 'More',
                'icon' => 'ti-layout-grid2',
                'is_more' => true,
                'active' => false,
            ],
        ];

        $moreItems = [
            ['title' => 'Circulars & Notices', 'desc' => 'Important campus bulletins', 'icon' => 'ti-bell', 'url' => route('notices.index'), 'color' => 'bg-amber-500'],
            ['title' => 'Timetable & Routine', 'desc' => 'Weekly class schedule', 'icon' => 'ti-time', 'url' => route('school.timetable.index'), 'color' => 'bg-blue-500'],
            ['title' => 'Student Profile', 'desc' => 'Personal & guardian details', 'icon' => 'ti-user', 'url' => route('profile.show'), 'color' => 'bg-indigo-500'],
            ['title' => 'Portal Settings', 'desc' => 'Notifications & passwords', 'icon' => 'ti-settings', 'url' => route('settings.index'), 'color' => 'bg-slate-600'],
            ['title' => 'Appearance', 'desc' => 'Themes & colors', 'icon' => 'ti-palette', 'url' => route('appearance.index'), 'color' => 'bg-purple-600'],
        ];
    } elseif ($user->isTeacher()) {
        $tabs = [
            [
                'id' => 'home',
                'label' => 'Home',
                'icon' => 'ti-home',
                'url' => route('dashboard'),
                'active' => $isActive(['dashboard']),
            ],
            [
                'id' => 'attendance',
                'label' => 'Attendance',
                'icon' => 'ti-check-box',
                'url' => route('school.attendance.index'),
                'active' => $isActive(['school.attendance.index', 'attendance.index']),
            ],
            [
                'id' => 'classes',
                'label' => 'Classes',
                'icon' => 'ti-blackboard',
                'url' => route('school.classes.index'),
                'active' => $isActive(['school.classes.index', 'school.timetable.index']),
            ],
            [
                'id' => 'tasks',
                'label' => 'Tasks',
                'icon' => 'ti-clipboard',
                'url' => route('homework.index'),
                'active' => $isActive(['homework.index', 'school.assignments.index', 'notices.index']),
            ],
            [
                'id' => 'more',
                'label' => 'More',
                'icon' => 'ti-layout-grid2',
                'is_more' => true,
                'active' => false,
            ],
        ];

        $moreItems = [
            ['title' => 'Virtual Classes', 'desc' => 'Live Zoom, Meet & Jitsi classes', 'icon' => 'ti-video-camera', 'url' => route('virtual-classes.index'), 'color' => 'bg-rose-600'],
            ['title' => 'Faculty Profile', 'desc' => 'Profile & credentials', 'icon' => 'ti-user', 'url' => route('profile.show'), 'color' => 'bg-indigo-500'],
            ['title' => 'Leave Requests', 'desc' => 'Apply or review leaves', 'icon' => 'ti-calendar', 'url' => route('leaves.index'), 'color' => 'bg-emerald-500'],
            ['title' => 'Library Hub', 'desc' => 'Books catalog & circulation', 'icon' => 'ti-book', 'url' => route('school.library.index'), 'color' => 'bg-purple-600'],
            ['title' => 'Download Center', 'desc' => 'Study materials & syllabus', 'icon' => 'ti-download', 'url' => route('school.study-materials.index'), 'color' => 'bg-blue-600'],
            ['title' => 'Staff Attendance', 'desc' => 'Attendance & leave approvals', 'icon' => 'ti-check-box', 'url' => route('school.staff-attendance.index'), 'color' => 'bg-teal-600'],
            ['title' => 'Staff Payroll', 'desc' => 'Monthly payroll administration', 'icon' => 'ti-credit-card', 'url' => route('school.payroll.index'), 'color' => 'bg-emerald-600'],
            ['title' => 'My Staff Workspace', 'desc' => 'Payslips, HR documents & tax declarations', 'icon' => 'ti-user', 'url' => route('staff.self-service.index'), 'color' => 'bg-violet-600'],
            ['title' => 'Notices & Circulars', 'desc' => 'Publish student announcements', 'icon' => 'ti-bell', 'url' => route('notices.index'), 'color' => 'bg-amber-500'],
            ['title' => 'Academic Analytics', 'desc' => 'Student marks & trends', 'icon' => 'ti-bar-chart', 'url' => route('analytics.index'), 'color' => 'bg-cyan-600'],
            ['title' => 'Academic Holidays', 'desc' => 'Calendar & event breaks', 'icon' => 'ti-calendar', 'url' => url('/school/holidays'), 'color' => 'bg-rose-500'],
        ];
    } elseif ($user->isAdmin()) {
        $tabs = [
            [
                'id' => 'home',
                'label' => 'Home',
                'icon' => 'ti-home',
                'url' => route('dashboard'),
                'active' => $isActive(['dashboard']),
            ],
            [
                'id' => 'students',
                'label' => 'Students',
                'icon' => 'ti-user',
                'url' => route('school.students.index'),
                'active' => $isActive(['school.students.index']),
            ],
            [
                'id' => 'fees',
                'label' => 'Fees',
                'icon' => 'ti-wallet',
                'url' => route('school.fees.index'),
                'active' => $isActive(['school.fees.index']),
            ],
            [
                'id' => 'academics',
                'label' => 'Academics',
                'icon' => 'ti-book',
                'url' => route('school.classes.index'),
                'active' => $isActive(['school.classes.index', 'school.sections.index', 'school.exams.index', 'school.timetable.index']),
            ],
            [
                'id' => 'more',
                'label' => 'More',
                'icon' => 'ti-layout-grid2',
                'is_more' => true,
                'active' => false,
            ],
        ];

        $moreItems = [
            ['title' => 'Virtual Classes', 'desc' => 'Live Zoom, Meet & Jitsi classes', 'icon' => 'ti-video-camera', 'url' => route('virtual-classes.index'), 'color' => 'bg-rose-600'],
            ['title' => 'Admissions & Bulk', 'desc' => 'Enrollment & Excel data import', 'icon' => 'ti-upload', 'url' => route('school.bulk.index'), 'color' => 'bg-indigo-600'],
            ['title' => 'Library Hub', 'desc' => 'Book catalog & circulation', 'icon' => 'ti-book', 'url' => route('school.library.index'), 'color' => 'bg-purple-600'],
            ['title' => 'Accounts & Treasury', 'desc' => 'P&L income & expenses', 'icon' => 'ti-money', 'url' => route('school.accounts.index'), 'color' => 'bg-emerald-600'],
            ['title' => 'Staff Attendance', 'desc' => 'Attendance & leave approvals', 'icon' => 'ti-check-box', 'url' => route('school.staff-attendance.index'), 'color' => 'bg-teal-600'],
            ['title' => 'Staff Payroll', 'desc' => 'Monthly payroll administration', 'icon' => 'ti-credit-card', 'url' => route('school.payroll.index'), 'color' => 'bg-blue-600'],
            ['title' => 'My Staff Workspace', 'desc' => 'Own payslips, HR documents & declarations', 'icon' => 'ti-user', 'url' => route('staff.self-service.index'), 'color' => 'bg-violet-600'],
            ['title' => 'Tax Declaration Review', 'desc' => 'Review employee declarations', 'icon' => 'ti-clipboard', 'url' => route('school.hr.tax-declarations.index'), 'color' => 'bg-amber-600'],
            ['title' => 'Transport & Fleet', 'desc' => 'Routes & bus logistics', 'icon' => 'ti-car', 'url' => route('school.transport.index'), 'color' => 'bg-amber-600'],
            ['title' => 'Hostel & Dormitory', 'desc' => 'Boarding rooms & beds', 'icon' => 'ti-home', 'url' => route('school.dormitory.index'), 'color' => 'bg-cyan-600'],
            ['title' => 'Front Office Desk', 'desc' => 'Visitor passes & mail', 'icon' => 'ti-clipboard', 'url' => route('school.front-office.index'), 'color' => 'bg-teal-600'],
            ['title' => 'Student Promotion', 'desc' => 'Session rollover audit', 'icon' => 'ti-shift-right', 'url' => route('school.promotion.index'), 'color' => 'bg-rose-600'],
            ['title' => 'Download Center', 'desc' => 'Study materials & docs', 'icon' => 'ti-download', 'url' => route('school.study-materials.index'), 'color' => 'bg-indigo-500'],
            ['title' => 'Circulars & Notices', 'desc' => 'Dispatch institute broadcast', 'icon' => 'ti-bell', 'url' => route('notices.index'), 'color' => 'bg-amber-500'],
            ['title' => 'Institutional Reports', 'desc' => 'Performance & fee insights', 'icon' => 'ti-bar-chart', 'url' => route('analytics.index'), 'color' => 'bg-cyan-600'],
            ['title' => 'System Settings', 'desc' => 'Branding, session, controls', 'icon' => 'ti-settings', 'url' => route('settings.index'), 'color' => 'bg-slate-700'],
            ['title' => 'Themes & Appearance', 'desc' => '6 Infix LMS themes & colors', 'icon' => 'ti-palette', 'url' => route('appearance.index'), 'color' => 'bg-purple-600'],
            ['title' => 'Reusable Sections', 'desc' => 'Rooms & section allocations', 'icon' => 'ti-layout-grid3', 'url' => route('school.sections.index'), 'color' => 'bg-emerald-600'],
        ];
    } elseif ($user->isCashier()) {
        $tabs = [
            [
                'id' => 'fees',
                'label' => 'Fees',
                'icon' => 'ti-wallet',
                'url' => route('school.fees.index'),
                'active' => $isActive(['school.fees.index']) && !request()->has('filter'),
            ],
            [
                'id' => 'defaulters',
                'label' => 'Defaulters',
                'icon' => 'ti-alert',
                'url' => route('school.fees.index', ['filter' => 'defaulters']),
                'active' => request()->query('filter') === 'defaulters',
            ],
            [
                'id' => 'payroll',
                'label' => 'Payroll',
                'icon' => 'ti-credit-card',
                'url' => route('school.payroll.index'),
                'active' => $isActive(['school.payroll.index', 'school.payroll.payslip']),
            ],
            [
                'id' => 'reports',
                'label' => 'Reports',
                'icon' => 'ti-bar-chart',
                'url' => route('school.reports.index', ['category' => 'finance']),
                'active' => $isActive(['school.reports.index']),
            ],
            [
                'id' => 'more',
                'label' => 'More',
                'icon' => 'ti-layout-grid2',
                'is_more' => true,
                'active' => false,
            ],
        ];

        $moreItems = [
            ['title' => 'Circulars & Notices', 'desc' => 'Official announcements', 'icon' => 'ti-bell', 'url' => route('notices.index'), 'color' => 'bg-amber-500'],
            ['title' => 'My Profile', 'desc' => 'Account settings', 'icon' => 'ti-user', 'url' => route('profile.show'), 'color' => 'bg-indigo-500'],
            ['title' => 'Themes & Appearance', 'desc' => 'Dark/light & color scheme', 'icon' => 'ti-palette', 'url' => route('appearance.index'), 'color' => 'bg-purple-600'],
        ];
    } else {
        // Student Role
        $tabs = [
            [
                'id' => 'home',
                'label' => 'Home',
                'icon' => 'ti-home',
                'url' => route('dashboard'),
                'active' => $isActive(['dashboard']),
            ],
            [
                'id' => 'learn',
                'label' => 'Learn',
                'icon' => 'ti-book',
                'url' => route('my-batch'),
                'active' => $isActive(['my-batch', 'courses.index']),
            ],
            [
                'id' => 'attendance',
                'label' => 'Attendance',
                'icon' => 'ti-check-box',
                'url' => route('attendance.index'),
                'active' => $isActive(['attendance.index']),
            ],
            [
                'id' => 'homework',
                'label' => 'Tasks',
                'icon' => 'ti-pencil-alt',
                'url' => route('homework.index'),
                'active' => $isActive(['homework.index']),
            ],
            [
                'id' => 'more',
                'label' => 'More',
                'icon' => 'ti-layout-grid2',
                'is_more' => true,
                'active' => false,
            ],
        ];

        $moreItems = [
            ['title' => 'Virtual Live Classes', 'desc' => 'Interactive live sessions', 'icon' => 'ti-video-camera', 'url' => route('virtual-classes.index'), 'color' => 'bg-rose-600'],
            ['title' => 'Download Center', 'desc' => 'Study materials & docs', 'icon' => 'ti-download', 'url' => route('school.study-materials.index'), 'color' => 'bg-blue-600'],
            ['title' => 'Library Books', 'desc' => 'Catalog & issued books', 'icon' => 'ti-book', 'url' => route('school.library.index'), 'color' => 'bg-purple-600'],
            ['title' => 'Campus Circulars', 'desc' => 'School notices & news', 'icon' => 'ti-bell', 'url' => route('notices.index'), 'color' => 'bg-amber-500'],
            ['title' => 'Arena Quizzes', 'desc' => 'Test your knowledge', 'icon' => 'ti-medall', 'url' => route('quizzes.index'), 'color' => 'bg-indigo-500'],
            ['title' => 'My Digital ID Card', 'desc' => 'Student ID & QR code', 'icon' => 'ti-id-badge', 'url' => route('idcard.show'), 'color' => 'bg-emerald-600'],
            ['title' => 'My Profile & XP', 'desc' => 'Badges, streak, tier info', 'icon' => 'ti-user', 'url' => route('profile.show'), 'color' => 'bg-purple-600'],
            ['title' => 'Theme Customizer', 'desc' => 'Switch palette & mode', 'icon' => 'ti-palette', 'url' => route('appearance.index'), 'color' => 'bg-pink-600'],
        ];
    }
@endphp

<div x-data="{ moreSheetOpen: false }">

    <!-- Bottom Navigation Bar (Max 5 Tabs) -->
    <nav id="mobile-bottom-tabs" class="fixed bottom-0 inset-x-0 z-40 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md border-t border-slate-200/90 dark:border-slate-800 shadow-[0_-4px_20px_rgba(0,0,0,0.06)] md:hidden">
        <div class="max-w-md mx-auto px-2 grid grid-cols-5 h-16 items-center">
            @foreach($tabs as $tab)
                @if(!empty($tab['is_more']))
                    <button type="button" 
                            @click="moreSheetOpen = true" 
                            class="flex flex-col items-center justify-center py-1 px-1 transition relative group text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200 focus:outline-none">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center mb-0.5 group-hover:bg-slate-100 dark:group-hover:bg-slate-800 transition">
                            <i class="{{ $tab['icon'] }} text-base"></i>
                        </div>
                        <span class="text-[10px] font-bold tracking-tight">
                            {{ $tab['label'] }}
                        </span>
                    </button>
                @else
                    <a href="{{ $tab['url'] }}" 
                       class="flex flex-col items-center justify-center py-1 px-1 transition relative group {{ $tab['active'] ? 'active-tab' : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200' }}">
                        @if($tab['active'])
                            <!-- Active Top Pip Indicator -->
                            <span class="absolute top-0 w-8 h-0.5 rounded-full shadow-xs" style="background: var(--system_primary_gradient);"></span>
                        @endif
                        <div class="w-8 h-8 rounded-full flex items-center justify-center mb-0.5 transition {{ $tab['active'] ? 'text-white shadow-xs' : 'group-hover:bg-slate-100 dark:group-hover:bg-slate-800' }}"
                             @if($tab['active']) style="background: var(--system_primary_gradient);" @endif>
                            <i class="{{ $tab['icon'] }} text-base {{ $tab['active'] ? 'text-white' : '' }}"></i>
                        </div>
                        <span class="text-[10px] font-bold tracking-tight {{ $tab['active'] ? 'font-black' : '' }}"
                              @if($tab['active']) style="color: var(--system_primery_color);" @endif>
                            {{ $tab['label'] }}
                        </span>
                    </a>
                @endif
            @endforeach
        </div>
    </nav>

    <!-- 'More' Role-Based Bottom Drawer / Modal -->
    <div x-show="moreSheetOpen" 
         style="display: none;"
         class="fixed inset-0 z-50 overflow-hidden md:hidden"
         x-transition:enter="transition-opacity ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs" @click="moreSheetOpen = false"></div>

        <!-- Sheet Panel -->
        <div class="fixed bottom-0 inset-x-0 bg-white dark:bg-slate-900 rounded-t-3xl border-t border-slate-200 dark:border-slate-800 shadow-2xl p-4 max-h-[85vh] overflow-y-auto space-y-3"
             x-transition:enter="transition ease-out duration-250 transform"
             x-transition:enter-start="translate-y-full"
             x-transition:enter-end="translate-y-0"
             x-transition:leave="transition ease-in duration-200 transform"
             x-transition:leave-start="translate-y-0"
             x-transition:leave-end="translate-y-full">
            
            <!-- Drag handle -->
            <div class="w-10 h-1 bg-slate-300 dark:bg-slate-700 rounded-full mx-auto mb-2"></div>

            <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center space-x-2">
                    <span class="w-7 h-7 rounded-lg flex items-center justify-center text-white text-xs shadow-xs" style="background: var(--system_primary_gradient);">
                        <i class="ti-layout-grid2"></i>
                    </span>
                    <div>
                        <h3 class="text-sm font-black text-slate-900 dark:text-white leading-tight">Quick Actions &amp; More</h3>
                        <span class="text-[10px] text-slate-400 uppercase tracking-wider font-semibold">{{ strtoupper($role) }} PORTAL</span>
                    </div>
                </div>
                <button type="button" @click="moreSheetOpen = false" class="w-7 h-7 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 flex items-center justify-center text-xs">
                    <i class="ti-close"></i>
                </button>
            </div>

            <!-- More Items Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-1">
                @foreach($moreItems as $item)
                <a href="{{ $item['url'] }}" 
                   @click="moreSheetOpen = false"
                   class="flex items-center space-x-3 p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-100 dark:border-slate-800 transition group">
                    <div class="w-9 h-9 rounded-lg {{ $item['color'] ?? 'bg-indigo-600' }} text-white flex items-center justify-center text-sm shadow-xs shrink-0 group-hover:scale-105 transition">
                        <i class="{{ $item['icon'] }}"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="text-xs font-bold text-slate-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition leading-tight">
                            {{ $item['title'] }}
                        </div>
                        <div class="text-[10px] text-slate-400 truncate">
                            {{ $item['desc'] }}
                        </div>
                    </div>
                    <i class="ti-angle-right text-xs text-slate-300 dark:text-slate-600 group-hover:translate-x-0.5 transition"></i>
                </a>
                @endforeach
            </div>

            <!-- Quick Dark / Light Mode Switch in Bottom Drawer -->
            <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200/80 dark:border-slate-700/60 flex items-center justify-between my-2">
                <div class="flex items-center space-x-2.5">
                    <div class="w-8 h-8 rounded-lg bg-amber-100 dark:bg-purple-950/80 text-amber-600 dark:text-purple-400 flex items-center justify-center shadow-xs">
                        <i class="ti-sun dark:hidden text-sm"></i>
                        <i class="ti-moon hidden dark:block text-sm"></i>
                    </div>
                    <div>
                        <div class="text-xs font-bold text-slate-900 dark:text-white">Dark Mode</div>
                        <div class="text-[10px] text-slate-400">Switch system appearance</div>
                    </div>
                </div>
                <button type="button" 
                        onclick="toggleThemeMode()" 
                        class="flex items-center space-x-2 px-2.5 py-1.5 rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-700 dark:text-slate-200 shadow-xs active:scale-95 transition">
                    <span class="theme-mode-label">Light</span>
                    <div class="relative w-7 h-4 bg-slate-300 dark:bg-purple-950/80 rounded-full transition-colors flex items-center p-0.5 border border-slate-300/80 dark:border-purple-600/50">
                        <div class="w-3 h-3 rounded-full bg-white dark:bg-purple-400 shadow-xs transform transition-transform translate-x-0 dark:translate-x-3"></div>
                    </div>
                </button>
            </div>

            <!-- Footer: User Info & Logout -->
            <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <div class="w-7 h-7 rounded-full flex items-center justify-center font-bold text-xs text-white shadow-xs" style="background: var(--system_primary_gradient);">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                    <div class="text-left">
                        <div class="text-xs font-bold text-slate-900 dark:text-white leading-none">{{ $user->name }}</div>
                        <div class="text-[10px] text-slate-400 capitalize">{{ $role }}</div>
                    </div>
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="px-2.5 py-1 rounded-lg text-xs font-bold bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 hover:bg-rose-100 transition inline-flex items-center space-x-1">
                        <i class="ti-power-off text-[11px]"></i>
                        <span>Logout</span>
                    </button>
                </form>
            </div>

        </div>
    </div>

</div>
