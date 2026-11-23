<div class="space-y-8 sm:space-y-10" x-data="{ activeSection: 'all' }">

    <!-- ========================================================================= -->
    <!-- 1. HERO EYE-CATCHER: LIVE ATTENTION, HEALTH & ACTION-REQUIRED TICKER -->
    <!-- ========================================================================= -->
    <div class="p-3.5 sm:p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-3">
        <div class="flex items-center space-x-3">
            <span class="relative flex h-3.5 w-3.5">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-3.5 w-3.5 bg-emerald-500"></span>
            </span>
            <div>
                <div class="flex items-center space-x-2">
                    <span class="text-xs font-black uppercase tracking-wider text-slate-900 dark:text-white">Live Institutional Command Center</span>
                    <span class="px-2 py-0.5 rounded-full text-[9px] font-bold uppercase bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                        All Systems Nominal
                    </span>
                </div>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                    Today: <strong>{{ $todayAttendanceRate }}%</strong> Student Roll Call &bull; 
                    <strong class="text-emerald-600 dark:text-emerald-400">{{ $school['currency_symbol'] ?? '₹' }}{{ number_format($feesCollectedToday, 0) }}</strong> collected today &bull; 
                    <strong>{{ $todaySessions->count() }} of {{ $allActiveClasses->count() }}</strong> classes active
                </p>
            </div>
        </div>

        <div class="flex items-center flex-wrap gap-2 text-xs font-bold">
            @if(count($abnormalClasses) > 0)
            <a href="{{ route('school.attendance.index') }}" class="px-3 py-1 rounded-full bg-amber-500/10 border border-amber-300 dark:border-amber-800 text-amber-700 dark:text-amber-300 flex items-center space-x-1 hover:bg-amber-500/20 transition">
                <i class="ti-alert text-xs"></i>
                <span>{{ count($abnormalClasses) }} Classes &lt; {{ config('attendance.minimum_percentage') }}%</span>
            </a>
            @endif

            @if(($pendingStudentLeaves + $pendingStaffLeaves) > 0)
            <a href="{{ route('leaves.index') }}" class="px-3 py-1 rounded-full bg-purple-500/10 border border-purple-300 dark:border-purple-800 text-purple-700 dark:text-purple-300 flex items-center space-x-1 hover:bg-purple-500/20 transition">
                <i class="ti-time text-xs"></i>
                <span>{{ $pendingStudentLeaves + $pendingStaffLeaves }} Pending Leaves</span>
            </a>
            @endif

            @if($defaultersCount > 0)
            <a href="{{ route('school.fees.index') }}" class="px-3 py-1 rounded-full bg-rose-500/10 border border-rose-300 dark:border-rose-800 text-rose-700 dark:text-rose-300 flex items-center space-x-1 hover:bg-rose-500/20 transition">
                <i class="ti-wallet text-xs"></i>
                <span>{{ $defaultersCount }} Defaulters Due</span>
            </a>
            @endif
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 2. THE 4 CANONICAL INFIX TOP SUMMARY CARDS (Faculty, Students, Parents, Staff) -->
    <!-- 2x2 Grid on Mobile & Android Devices, 4 across on Desktop Screens -->
    <!-- ========================================================================= -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-4 sm:gap-5">
        <!-- 1. Faculty / Instructors -->
        <a href="{{ route('school.students.index') }}" class="white-box single-summery p-3.5 sm:p-5 rounded-2xl block group shadow-xs hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-[10px] sm:text-xs font-bold text-slate-500 uppercase tracking-wider">Faculty</h3>
                    <p class="text-[9px] sm:text-[11px] text-slate-400 mt-0.5 truncate">Total Instructors</p>
                </div>
                <div class="flex items-center space-x-2 sm:space-x-3">
                    <h1 class="gradient-color2 text-2xl sm:text-3xl font-black font-mono leading-none">
                        {{ $totalTeachers }}
                    </h1>
                    <div class="w-8 h-8 sm:w-11 sm:h-11 rounded-lg sm:rounded-xl flex items-center justify-center text-white shadow-xs group-hover:scale-105 transition-transform" style="background: var(--system_primary_gradient);">
                        <i class="ti-id-badge text-sm sm:text-xl"></i>
                    </div>
                </div>
            </div>
            <div class="mt-2.5 pt-2 sm:mt-3 sm:pt-3 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-[10px] sm:text-[11px]">
                <span class="text-slate-500 dark:text-slate-400">Active duty</span>
                <span class="font-bold text-emerald-600 dark:text-emerald-400 flex items-center space-x-1 whitespace-nowrap">
                    <span>{{ max(0, $totalTeachers - $activeStaffLeaves->count()) }} Present</span>
                </span>
            </div>
        </a>

        <!-- 2. Students -->
        <a href="{{ route('school.students.index') }}" class="white-box single-summery p-3.5 sm:p-5 rounded-2xl block group shadow-xs hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-[10px] sm:text-xs font-bold text-slate-500 uppercase tracking-wider">Students</h3>
                    <p class="text-[9px] sm:text-[11px] text-slate-400 mt-0.5 truncate">Total Enrolled</p>
                </div>
                <div class="flex items-center space-x-2 sm:space-x-3">
                    <h1 class="gradient-color2 text-2xl sm:text-3xl font-black font-mono leading-none">
                        {{ $totalStudents }}
                    </h1>
                    <div class="w-8 h-8 sm:w-11 sm:h-11 rounded-lg sm:rounded-xl flex items-center justify-center text-white shadow-xs group-hover:scale-105 transition-transform" style="background: var(--system_primary_gradient);">
                        <i class="ti-user text-sm sm:text-xl"></i>
                    </div>
                </div>
            </div>
            <div class="mt-2.5 pt-2 sm:mt-3 sm:pt-3 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-[10px] sm:text-[11px]">
                <span class="text-slate-500 dark:text-slate-400 truncate">{{ $allActiveClasses->count() }} classes</span>
                <span class="font-bold text-emerald-600 dark:text-emerald-400 flex items-center space-x-1 whitespace-nowrap">
                    <span>+{{ $newAdmissionsTerm }} term</span>
                </span>
            </div>
        </a>

        <!-- 3. Parents -->
        <a href="{{ route('school.parent.dashboard') }}" class="white-box single-summery p-3.5 sm:p-5 rounded-2xl block group shadow-xs hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-[10px] sm:text-xs font-bold text-slate-500 uppercase tracking-wider">Parents</h3>
                    <p class="text-[9px] sm:text-[11px] text-slate-400 mt-0.5 truncate">Guardians</p>
                </div>
                <div class="flex items-center space-x-2 sm:space-x-3">
                    <h1 class="gradient-color2 text-2xl sm:text-3xl font-black font-mono leading-none">
                        {{ $totalParents }}
                    </h1>
                    <div class="w-8 h-8 sm:w-11 sm:h-11 rounded-lg sm:rounded-xl flex items-center justify-center text-white shadow-xs group-hover:scale-105 transition-transform" style="background: var(--system_primary_gradient);">
                        <i class="ti-heart text-sm sm:text-xl"></i>
                    </div>
                </div>
            </div>
            <div class="mt-2.5 pt-2 sm:mt-3 sm:pt-3 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-[10px] sm:text-[11px]">
                <span class="text-slate-500 dark:text-slate-400">Portal link</span>
                <span class="font-bold text-indigo-600 dark:text-indigo-400">
                    {{ $parentEngagementRate }}% linked
                </span>
            </div>
        </a>

        <!-- 4. Staff Members -->
        <a href="{{ route('school.payroll.index') }}" class="white-box single-summery p-3.5 sm:p-5 rounded-2xl block group shadow-xs hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-[10px] sm:text-xs font-bold text-slate-500 uppercase tracking-wider">Staff</h3>
                    <p class="text-[9px] sm:text-[11px] text-slate-400 mt-0.5 truncate">Ops &amp; Admin</p>
                </div>
                <div class="flex items-center space-x-2 sm:space-x-3">
                    <h1 class="gradient-color2 text-2xl sm:text-3xl font-black font-mono leading-none">
                        {{ $totalStaff }}
                    </h1>
                    <div class="w-8 h-8 sm:w-11 sm:h-11 rounded-lg sm:rounded-xl flex items-center justify-center text-white shadow-xs group-hover:scale-105 transition-transform" style="background: var(--system_primary_gradient);">
                        <i class="ti-briefcase text-sm sm:text-xl"></i>
                    </div>
                </div>
            </div>
            <div class="mt-2.5 pt-2 sm:mt-3 sm:pt-3 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-[10px] sm:text-[11px]">
                <span class="text-slate-500 dark:text-slate-400">Staff payroll</span>
                <span class="font-bold text-purple-600 dark:text-purple-400">100% active</span>
            </div>
        </a>
    </div>

    <!-- ========================================================================= -->
    <!-- 3. SECONDARY INFIX ERP OPERATIONAL & FINANCIAL KPI STRIP (Row 2) -->
    <!-- 2x2 Grid on Mobile & Android Devices, 4 across on Desktop Screens -->
    <!-- ========================================================================= -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-4 sm:gap-5">
        <!-- Fees Collection This Month -->
        <a href="{{ route('school.fees.index') }}" class="white-box single-summery p-3.5 sm:p-5 rounded-2xl block group shadow-xs hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-[10px] sm:text-xs font-bold text-slate-500 uppercase tracking-wider">Fees Collection</h3>
                    <p class="text-[9px] sm:text-[10px] text-slate-400 mt-0.5 truncate">This Month Intake</p>
                </div>
                <div class="w-7 h-7 sm:w-9 sm:h-9 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                    <i class="ti-wallet text-sm sm:text-base"></i>
                </div>
            </div>
            <div class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white mt-1.5 sm:mt-2 font-mono">
                {{ $school['currency_symbol'] ?? '₹' }}{{ number_format($feesCollectedThisMonth, 0) }}
            </div>
            <div class="flex items-center justify-between text-[10px] sm:text-[11px] mt-1.5 sm:mt-2 text-slate-500 dark:text-slate-400">
                <span class="{{ $feeTrendPct >= 0 ? 'text-emerald-600' : 'text-rose-600' }} font-bold font-mono">
                    {{ $feeTrendPct >= 0 ? '+' : '' }}{{ $feeTrendPct }}%
                </span>
                <span>{{ $school['currency_symbol'] ?? '₹' }}{{ number_format($feesCollectedToday, 0) }} today</span>
            </div>
        </a>

        <!-- Monthly Treasury & Cash Flow -->
        <a href="{{ route('school.accounts.index') }}" class="white-box single-summery p-3.5 sm:p-5 rounded-2xl block group shadow-xs hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-[10px] sm:text-xs font-bold text-slate-500 uppercase tracking-wider">Operating Treasury</h3>
                    <p class="text-[9px] sm:text-[10px] text-slate-400 mt-0.5 truncate">Net Cash Flow</p>
                </div>
                <div class="w-7 h-7 sm:w-9 sm:h-9 rounded-lg bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                    <i class="ti-money text-sm sm:text-base"></i>
                </div>
            </div>
            <div class="text-xl sm:text-2xl font-black {{ $monthlyNetCashflow >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600' }} mt-1.5 sm:mt-2 font-mono">
                {{ $monthlyNetCashflow >= 0 ? '+' : '' }}{{ $school['currency_symbol'] ?? '₹' }}{{ number_format($monthlyNetCashflow, 0) }}
            </div>
            <div class="flex items-center justify-between text-[10px] sm:text-[11px] mt-1.5 sm:mt-2 text-slate-500 dark:text-slate-400">
                <span class="text-emerald-600 font-mono">+₹{{ number_format($monthlyIncome, 0) }}</span>
                <span class="text-rose-500 font-mono">-₹{{ number_format($monthlyExpense, 0) }}</span>
            </div>
        </a>

        <!-- Library Management -->
        <a href="{{ route('school.library.index') }}" class="white-box single-summery p-3.5 sm:p-5 rounded-2xl block group shadow-xs hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-[10px] sm:text-xs font-bold text-slate-500 uppercase tracking-wider">Library Center</h3>
                    <p class="text-[9px] sm:text-[10px] text-slate-400 mt-0.5 truncate">Inventory</p>
                </div>
                <div class="w-7 h-7 sm:w-9 sm:h-9 rounded-lg bg-blue-500/10 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                    <i class="ti-book text-sm sm:text-base"></i>
                </div>
            </div>
            <div class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white mt-1.5 sm:mt-2 font-mono">
                {{ $totalBooks }} <span class="text-xs font-normal text-slate-400">Books</span>
            </div>
            <div class="flex items-center justify-between text-[10px] sm:text-[11px] mt-1.5 sm:mt-2 text-slate-500 dark:text-slate-400">
                <span class="text-indigo-600 dark:text-indigo-400 font-bold font-mono">{{ $totalIssuedBooks }} Out</span>
                <span class="text-slate-400">Circulation</span>
            </div>
        </a>

        <!-- Transport & Dormitory -->
        <a href="{{ route('school.transport.index') }}" class="white-box single-summery p-3.5 sm:p-5 rounded-2xl block group shadow-xs hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-[10px] sm:text-xs font-bold text-slate-500 uppercase tracking-wider">Transport &amp; Hostel</h3>
                    <p class="text-[9px] sm:text-[10px] text-slate-400 mt-0.5 truncate">Campus Logistics</p>
                </div>
                <div class="w-7 h-7 sm:w-9 sm:h-9 rounded-lg bg-purple-500/10 text-purple-600 dark:text-purple-400 flex items-center justify-center">
                    <i class="ti-truck text-sm sm:text-base"></i>
                </div>
            </div>
            <div class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white mt-1.5 sm:mt-2 font-mono">
                {{ $totalVehicles }} <span class="text-xs font-normal text-slate-400">Buses</span>
            </div>
            <div class="flex items-center justify-between text-[10px] sm:text-[11px] mt-1.5 sm:mt-2 text-slate-500 dark:text-slate-400">
                <span class="font-mono">{{ $totalRoutes }} routes</span>
                <span class="font-mono text-indigo-600 dark:text-indigo-400">{{ $totalDormitories }} dorms</span>
            </div>
        </a>
    </div>

    <!-- ========================================================================= -->
    <!-- 4. INFIX DOMAIN QUICK-FILTER TABS -->
    <!-- ========================================================================= -->
    <div class="flex items-center space-x-2 overflow-x-auto pb-2 scrollbar-none pt-1 border-b border-slate-200/80 dark:border-slate-800">
        <button type="button" @click="activeSection = 'all'"
                :class="activeSection === 'all' ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900 shadow-sm' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800'"
                class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex-shrink-0 cursor-pointer flex items-center space-x-1.5">
            <i class="ti-dashboard text-xs"></i>
            <span>All Operations</span>
        </button>
        <button type="button" @click="activeSection = 'daily'"
                :class="activeSection === 'daily' ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900 shadow-sm' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800'"
                class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex-shrink-0 cursor-pointer flex items-center space-x-1.5">
            <i class="ti-check-box text-xs"></i>
            <span>Daily Snapshot</span>
        </button>
        <button type="button" @click="activeSection = 'academics'"
                :class="activeSection === 'academics' ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900 shadow-sm' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800'"
                class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex-shrink-0 cursor-pointer flex items-center space-x-1.5">
            <i class="ti-book text-xs"></i>
            <span>Academics &amp; Rosters</span>
        </button>
        <button type="button" @click="activeSection = 'finance'"
                :class="activeSection === 'finance' ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900 shadow-sm' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800'"
                class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex-shrink-0 cursor-pointer flex items-center space-x-1.5">
            <i class="ti-wallet text-xs"></i>
            <span>Finance &amp; Admissions</span>
        </button>
        <button type="button" @click="activeSection = 'trends'"
                :class="activeSection === 'trends' ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900 shadow-sm' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800'"
                class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex-shrink-0 cursor-pointer flex items-center space-x-1.5">
            <i class="ti-pulse text-xs"></i>
            <span>Trends &amp; Alerts</span>
        </button>
        <button type="button" @click="activeSection = 'actions'"
                :class="activeSection === 'actions' ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900 shadow-sm' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800'"
                class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex-shrink-0 cursor-pointer flex items-center space-x-1.5">
            <i class="ti-bolt text-xs"></i>
            <span>Quick Actions</span>
        </button>
        <button type="button" @click="activeSection = 'analytics'"
                :class="activeSection === 'analytics' ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900 shadow-sm' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800'"
                class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex-shrink-0 cursor-pointer flex items-center space-x-1.5">
            <i class="ti-bar-chart text-xs"></i>
            <span>Visual Analytics</span>
        </button>
    </div>

    <!-- ========================================================================= -->
    <!-- 5. MASTER 2-COLUMN OPERATIONAL LAYOUT (Re-ordered by Visual Priority) -->
    <!-- ========================================================================= -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 sm:gap-7">

        <!-- ===================================================================== -->
        <!-- LEFT MASTER COLUMN (8 Cols): Roll Call, Charts, Financials, Tables, Rosters -->
        <!-- ===================================================================== -->
        <div class="lg:col-span-8 space-y-6 sm:space-y-7">

            <!-- WIDGET A: TODAY'S SNAPSHOT & LIVE ROLL CALL COMMAND CENTER -->
            <div class="white-box p-5 sm:p-6 rounded-2xl shadow-xs" x-show="activeSection === 'all' || activeSection === 'daily'" x-transition>
                <div class="box_header flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <div class="main-title flex items-center space-x-2.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                            Today's Snapshot &bull; {{ now()->format('D, M j, Y') }}
                        </h3>
                    </div>
                    <a href="{{ route('school.attendance.index') }}" class="text-xs font-bold text-emerald-600 dark:text-emerald-400 hover:underline flex items-center space-x-1">
                        <span>Live Roll Call</span>
                        <i class="ti-arrow-right text-xs"></i>
                    </a>
                </div>

                <!-- 4 High-Impact Attendance Counters -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4 mt-4">
                    <a href="{{ route('school.attendance.index') }}" class="p-3.5 rounded-xl bg-emerald-50/70 dark:bg-emerald-950/30 border border-emerald-200/80 dark:border-emerald-800/60 block group hover:shadow-xs transition">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">Attendance Today</div>
                        <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1 font-mono">{{ $todayAttendanceRate }}%</div>
                        <div class="text-[10px] text-slate-500 mt-0.5">{{ $todayPresentCount }} Present</div>
                    </a>
                    <a href="{{ route('leaves.index') }}" class="p-3.5 rounded-xl bg-indigo-50/70 dark:bg-indigo-950/30 border border-indigo-200/80 dark:border-indigo-800/60 block group hover:shadow-xs transition">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-indigo-700 dark:text-indigo-400">Faculty Availability</div>
                        <div class="text-2xl font-black text-indigo-600 dark:text-indigo-400 mt-1 font-mono">{{ $activeStaffLeaves->count() }} Absent</div>
                        <div class="text-[10px] text-slate-500 mt-0.5">{{ max(0, $totalTeachers - $activeStaffLeaves->count()) }} active duty</div>
                    </a>
                    <a href="{{ route('leaves.index') }}" class="p-3.5 rounded-xl bg-purple-50/70 dark:bg-purple-950/30 border border-purple-200/80 dark:border-purple-800/60 block group hover:shadow-xs transition">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-purple-700 dark:text-purple-400">Leave Requests</div>
                        <div class="text-2xl font-black text-purple-600 dark:text-purple-400 mt-1 font-mono">{{ $pendingStudentLeaves + $pendingStaffLeaves }}</div>
                        <div class="text-[10px] text-slate-500 mt-0.5">{{ $pendingStudentLeaves }} scholars &bull; {{ $pendingStaffLeaves }} staff</div>
                    </a>
                    <a href="{{ route('school.attendance.index') }}" class="p-3.5 rounded-xl {{ count($abnormalClasses) > 0 ? 'bg-amber-50/80 dark:bg-amber-950/40 border-amber-300 dark:border-amber-800' : 'bg-slate-50/70 dark:bg-slate-800/40 border-slate-200/80 dark:border-slate-700' }} border block group hover:shadow-xs transition">
                        <div class="text-[10px] font-bold uppercase tracking-wider {{ count($abnormalClasses) > 0 ? 'text-amber-700 dark:text-amber-400' : 'text-slate-400' }}">Abnormal Attendance</div>
                        <div class="text-2xl font-black {{ count($abnormalClasses) > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-900 dark:text-white' }} mt-1 font-mono">{{ count($abnormalClasses) }} Classes</div>
                        <div class="text-[10px] text-slate-500 mt-0.5 truncate">{{ count($abnormalClasses) > 0 ? collect($abnormalClasses)->pluck('name')->first() : 'All ≥ ' . config('attendance.minimum_percentage', 75) . '%' }}</div>
                    </a>
                </div>

                <!-- Progress Bar & Completion Summary -->
                <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-800">
                    <div class="flex items-center justify-between text-xs font-bold mb-1.5">
                        <span class="text-slate-700 dark:text-slate-300">Overall Attendance Rate</span>
                        <span class="text-emerald-600 dark:text-emerald-400 font-mono text-sm">{{ $todayAttendanceRate }}%</span>
                    </div>
                    <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-2.5 overflow-hidden">
                        <div class="h-2.5 rounded-full transition-all duration-500" style="width: {{ min(100, max(0, $todayAttendanceRate)) }}%; background: var(--system_primary_gradient);"></div>
                    </div>
                    <div class="flex items-center justify-between text-[11px] text-slate-500 mt-2">
                        <span>Class Attendance Taken: <strong>{{ $todaySessions->count() }} of {{ $allActiveClasses->count() }}</strong> Classes Logged</span>
                        <span>Examinations Readiness: <strong>{{ $upcomingExams->count() }}</strong> Terms Scheduled</span>
                    </div>
                </div>
            </div>

            <!-- WIDGET B: VISUAL ANALYTICS & INSTITUTIONAL TRENDS (CHARTS ELEVATED FOR VISUAL IMPACT) -->
            <div class="white-box p-5 sm:p-6 rounded-2xl shadow-xs" x-show="activeSection === 'all' || activeSection === 'analytics'" x-transition>
                <div class="box_header flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <div class="main-title flex items-center space-x-2">
                        <i class="ti-bar-chart text-slate-700 dark:text-slate-300"></i>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                            Visual Analytics &amp; Institutional Trends
                        </h3>
                    </div>
                    <div class="flex items-center space-x-3 text-xs">
                        <span class="inline-flex items-center space-x-1 text-slate-500">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                            <span>Income</span>
                        </span>
                        <span class="inline-flex items-center space-x-1 text-slate-500">
                            <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                            <span>Expenses</span>
                        </span>
                        <span class="inline-flex items-center space-x-1 text-slate-500">
                            <span class="w-2.5 h-2.5 rounded-full" style="background: var(--system_primery_color);"></span>
                            <span>Fees</span>
                        </span>
                    </div>
                </div>

                <div class="h-64 relative mt-3">
                    <canvas id="chart-income-expense-trend"></canvas>
                </div>
            </div>

            <!-- WIDGET C: 7-DAY ATTENDANCE DYNAMICS TREND -->
            <div class="white-box p-5 sm:p-6 rounded-2xl shadow-xs" x-show="activeSection === 'all' || activeSection === 'analytics'" x-transition>
                <div class="box_header flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <div class="main-title flex items-center space-x-2">
                        <i class="ti-stats-up text-slate-700 dark:text-slate-300"></i>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                            Attendance Dynamics (Past 7 Days)
                        </h3>
                    </div>
                    <span class="text-[10px] font-mono font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/60 px-2.5 py-1 rounded-full">
                        Live Roll Call Sync
                    </span>
                </div>

                <div class="h-60 relative mt-3">
                    <canvas id="chart-attendance-trend"></canvas>
                </div>
            </div>

            <!-- WIDGET D: FEES & FINANCE (Collected Today, Outstanding Dues, Defaulters) -->
            <div class="white-box p-5 sm:p-6 rounded-2xl shadow-xs space-y-4" x-show="activeSection === 'all' || activeSection === 'finance'" x-transition>
                <div class="box_header flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <div class="main-title flex items-center space-x-2">
                        <i class="ti-wallet text-slate-700 dark:text-slate-300"></i>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                            Fees &amp; Finance
                        </h3>
                    </div>
                    <a href="{{ route('school.fees.index') }}" class="text-xs font-bold text-emerald-600 dark:text-emerald-400 hover:underline">
                        Fee Desk &amp; Invoices &rarr;
                    </a>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4">
                    <a href="{{ route('school.fees.index') }}" class="p-4 rounded-xl bg-emerald-50/60 dark:bg-emerald-950/30 border border-emerald-200/80 dark:border-emerald-800/60 block group hover:shadow-xs transition">
                        <div class="text-[10px] font-bold text-emerald-700 dark:text-emerald-400 uppercase tracking-wider">Collected Today</div>
                        <div class="text-2xl font-black text-emerald-600 mt-1 font-mono">
                            {{ $school['currency_symbol'] ?? '₹' }}{{ number_format($feesCollectedToday, 0) }}
                        </div>
                        <div class="text-[10px] text-slate-500 mt-0.5">Instant ledger receipts</div>
                    </a>
                    <a href="{{ route('school.fees.index') }}" class="p-4 rounded-xl bg-rose-50/60 dark:bg-rose-950/30 border border-rose-200/80 dark:border-rose-800/60 block group hover:shadow-xs transition">
                        <div class="text-[10px] font-bold text-rose-700 dark:text-rose-400 uppercase tracking-wider">Outstanding Dues</div>
                        <div class="text-2xl font-black text-rose-600 mt-1 font-mono">
                            {{ $school['currency_symbol'] ?? '₹' }}{{ number_format($totalOutstandingAmount, 0) }}
                        </div>
                        <div class="text-[10px] text-slate-500 mt-0.5">Across {{ $outstandingStudentCount }} scholars</div>
                    </a>
                    <a href="{{ route('school.fees.index') }}" class="p-4 rounded-xl bg-amber-50/60 dark:bg-amber-950/30 border border-amber-200/80 dark:border-amber-800/60 block group hover:shadow-xs transition">
                        <div class="text-[10px] font-bold text-amber-700 dark:text-amber-400 uppercase tracking-wider">Defaulters &amp; Unbilled</div>
                        <div class="text-2xl font-black text-amber-600 mt-1 font-mono">
                            {{ $defaultersCount }} Defaulters
                        </div>
                        <div class="text-[10px] text-slate-500 mt-0.5">{{ $studentsWithoutInvoice }} new scholars pending fee plan</div>
                    </a>
                </div>
            </div>

            <!-- WIDGET E: RECENT FEE COLLECTIONS TABLE (Infix Standard Table) -->
            <div class="white-box p-5 sm:p-6 rounded-2xl shadow-xs space-y-4" x-show="activeSection === 'all' || activeSection === 'finance'" x-transition>
                <div class="box_header flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <div class="main-title flex items-center space-x-2">
                        <i class="ti-receipt text-slate-700 dark:text-slate-300"></i>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                            Recent Fee Collections &amp; Receipts
                        </h3>
                    </div>
                    <a href="{{ route('school.fees.index') }}" class="text-xs font-bold text-emerald-600 dark:text-emerald-400 hover:underline">
                        View All Invoices &rarr;
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-left">
                        <thead>
                            <tr class="text-slate-400 uppercase text-[10px] font-bold border-b border-slate-100 dark:border-slate-800">
                                <th class="pb-2">Scholar</th>
                                <th class="pb-2">Class / Section</th>
                                <th class="pb-2">Receipt No</th>
                                <th class="pb-2">Mode</th>
                                <th class="pb-2">Date</th>
                                <th class="pb-2 text-right">Amount</th>
                                <th class="pb-2 text-right">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse($recentFeePayments as $payment)
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                                <td class="py-2.5 pr-2">
                                    <div class="flex items-center space-x-2">
                                        <div class="w-7 h-7 rounded-full bg-slate-200 dark:bg-slate-700 flex items-center justify-center font-bold text-[11px] text-slate-700 dark:text-slate-200 uppercase">
                                            {{ substr($payment->student?->name ?? 'S', 0, 1) }}
                                        </div>
                                        <span class="font-bold text-slate-800 dark:text-slate-200">{{ $payment->student?->name ?? 'Scholar' }}</span>
                                    </div>
                                </td>
                                <td class="py-2.5 text-slate-500">
                                    {{ $payment->student?->currentClass?->display_name ?? 'Class #' . ($payment->student?->current_class_id ?? '—') }}
                                </td>
                                <td class="py-2.5 font-mono text-[11px] text-slate-500">
                                    {{ $payment->receipt_number ?? $payment->transaction_reference }}
                                </td>
                                <td class="py-2.5">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 uppercase">
                                        {{ $payment->payment_method ?? 'Cash' }}
                                    </span>
                                </td>
                                <td class="py-2.5 text-slate-400 font-mono text-[11px]">
                                    {{ $payment->paid_at ? \Carbon\Carbon::parse($payment->paid_at)->format('d M Y') : '—' }}
                                </td>
                                <td class="py-2.5 font-mono font-bold text-emerald-600 dark:text-emerald-400 text-right">
                                    {{ $school['currency_symbol'] ?? '₹' }}{{ number_format($payment->amount, 0) }}
                                </td>
                                <td class="py-2.5 text-right">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                                        Paid
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="py-4 text-center text-slate-400 text-xs">
                                    No recent fee collections logged this session.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- WIDGET F: ADMISSIONS PIPELINE (Infix Student Flow) -->
            <div class="white-box p-5 sm:p-6 rounded-2xl shadow-xs space-y-4" x-show="activeSection === 'all' || activeSection === 'finance'" x-transition>
                <div class="box_header flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <div class="main-title flex items-center space-x-2">
                        <i class="ti-user text-slate-700 dark:text-slate-300"></i>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                            Admissions Pipeline
                        </h3>
                    </div>
                    <a href="{{ route('school.students.index') }}" class="text-xs font-bold text-emerald-600 dark:text-emerald-400 hover:underline">
                        Admissions Directory &rarr;
                    </a>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
                    <a href="{{ route('school.students.index') }}" class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700 block group hover:shadow-xs transition">
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Applications Received</div>
                        <div class="text-2xl font-black text-slate-900 dark:text-white mt-1 font-mono">{{ $admissionsThisWeek }}</div>
                        <div class="text-[10px] text-slate-500 mt-0.5">{{ $admissionsThisMonth }} this month</div>
                    </a>
                    <a href="{{ route('school.students.index') }}" class="p-3.5 rounded-xl bg-amber-50/60 dark:bg-amber-950/30 border border-amber-200/80 dark:border-amber-800/60 block group hover:shadow-xs transition">
                        <div class="text-[10px] font-bold text-amber-700 dark:text-amber-400 uppercase tracking-wider">Under Review</div>
                        <div class="text-2xl font-black text-amber-600 mt-1 font-mono">{{ $pendingReviews }}</div>
                        <div class="text-[10px] text-slate-500 mt-0.5">Verification pending</div>
                    </a>
                    <a href="{{ route('school.students.index') }}" class="p-3.5 rounded-xl bg-emerald-50/60 dark:bg-emerald-950/30 border border-emerald-200/80 dark:border-emerald-800/60 block group hover:shadow-xs transition">
                        <div class="text-[10px] font-bold text-emerald-700 dark:text-emerald-400 uppercase tracking-wider">Approved &amp; Enrolled</div>
                        <div class="text-2xl font-black text-emerald-600 mt-1 font-mono">{{ $approvedStudents }}</div>
                        <div class="text-[10px] text-slate-500 mt-0.5">Active scholar profiles</div>
                    </a>
                    <a href="{{ route('school.students.index') }}" class="p-3.5 rounded-xl bg-indigo-50/60 dark:bg-indigo-950/30 border border-indigo-200/80 dark:border-indigo-800/60 block group hover:shadow-xs transition">
                        <div class="text-[10px] font-bold text-indigo-700 dark:text-indigo-400 uppercase tracking-wider">Conversion &amp; Gaps</div>
                        <div class="text-2xl font-black text-indigo-600 dark:text-indigo-400 mt-1 font-mono">{{ $conversionRate }}%</div>
                        <div class="text-[10px] text-slate-500 mt-0.5">{{ $missingClassStudents }} pending class</div>
                    </a>
                </div>
            </div>

            <!-- WIDGET G: ACADEMIC OPERATIONS & TIMETABLE GAPS -->
            <div class="white-box p-5 sm:p-6 rounded-2xl shadow-xs space-y-4" x-show="activeSection === 'all' || activeSection === 'academics'" x-transition>
                <div class="box_header flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <div class="main-title flex items-center space-x-2">
                        <i class="ti-book text-slate-700 dark:text-slate-300"></i>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                            Academic Operations
                        </h3>
                    </div>
                    <a href="{{ route('school.assignments.index') }}" class="text-xs font-bold text-emerald-600 dark:text-emerald-400 hover:underline">
                        Master Timetable &rarr;
                    </a>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
                    <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700">
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Enrolled Scholars</div>
                        <div class="text-2xl font-black text-slate-900 dark:text-white mt-1 font-mono">{{ $totalStudents }}</div>
                        <div class="text-[10px] text-slate-500 mt-0.5">+{{ $newAdmissionsTerm }} new this term</div>
                    </div>
                    <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700">
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Faculty &amp; Staff</div>
                        <div class="text-2xl font-black text-slate-900 dark:text-white mt-1 font-mono">{{ $totalTeachers }}</div>
                        <div class="text-[10px] text-slate-500 mt-0.5">{{ $unapprovedStaff }} pending reviews</div>
                    </div>
                    <div class="p-3.5 rounded-xl bg-indigo-50/60 dark:bg-indigo-950/30 border border-indigo-200/80 dark:border-indigo-800/60">
                        <div class="text-[10px] font-bold text-indigo-700 dark:text-indigo-400 uppercase tracking-wider">Curriculum Progress</div>
                        <div class="text-2xl font-black text-indigo-600 mt-1 font-mono">{{ $syllabusProgressRate }}%</div>
                        <div class="text-[10px] text-slate-500 mt-0.5">{{ $completedCurriculumTopics }} of {{ $totalCurriculumTopics }} topics</div>
                    </div>
                    <div class="p-3.5 rounded-xl bg-amber-50/60 dark:bg-amber-950/30 border border-amber-200/80 dark:border-amber-800/60">
                        <div class="text-[10px] font-bold text-amber-700 dark:text-amber-400 uppercase tracking-wider">Allocation Gaps</div>
                        <div class="text-2xl font-black text-amber-600 mt-1 font-mono">{{ $unassignedClassTeachers + $unassignedSubjects }} Gaps</div>
                        <div class="text-[10px] text-slate-500 mt-0.5">{{ $unassignedClassTeachers }} teachers &bull; {{ $unassignedSubjects }} subjects</div>
                    </div>
                </div>
            </div>

            <!-- WIDGET H: STUDENT & STAFF OVERVIEW (Class Rosters Breakdown) -->
            <div class="white-box p-5 sm:p-6 rounded-2xl shadow-xs" x-show="activeSection === 'all' || activeSection === 'academics'" x-transition>
                <div class="box_header flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <div class="main-title flex items-center space-x-2">
                        <i class="ti-layout-grid2 text-slate-700 dark:text-slate-300"></i>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                            Student &amp; Staff Overview
                        </h3>
                    </div>
                    <a href="{{ route('school.classes.index') }}" class="text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:underline">
                        Manage Classes &amp; Sections &rarr;
                    </a>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mt-3">
                    @foreach($classesSummary as $cls)
                    <a href="{{ route('school.classes.show', $cls->id) }}" class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 hover:border-emerald-500 border border-slate-200/80 dark:border-slate-700 transition block group">
                        <div class="font-bold text-slate-900 dark:text-white text-xs truncate group-hover:text-emerald-600 transition-colors">
                            {{ $cls->display_name }}
                        </div>
                        <div class="flex items-center justify-between text-[10px] text-slate-500 mt-2 font-mono">
                            <span>{{ $cls->students_count }} scholars</span>
                            <span class="text-emerald-600 font-bold">Cap: {{ $cls->capacity }}</span>
                        </div>
                    </a>
                    @endforeach
                </div>
            </div>

        </div>

        <!-- ===================================================================== -->
        <!-- RIGHT SIDEBAR COLUMN (4 Cols): Quick Actions, Alerts, Calendar, Notices -->
        <!-- ===================================================================== -->
        <div class="lg:col-span-4 space-y-6 sm:space-y-7">

            <!-- WIDGET I: INFIX QUICK ACTIONS GRID (MOVED TO TOP RIGHT FOR INSTANT ACCESS!) -->
            <div class="white-box p-5 rounded-2xl shadow-xs space-y-3.5" x-show="activeSection === 'all' || activeSection === 'actions'" x-transition>
                <div class="box_header pb-2 border-b border-slate-100 dark:border-slate-800">
                    <div class="main-title flex items-center space-x-2">
                        <i class="ti-bolt text-slate-700 dark:text-slate-300"></i>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                            Quick Actions &amp; Shortcuts
                        </h3>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2.5">
                    <a href="{{ route('school.attendance.index') }}" class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 border border-slate-200/80 dark:border-slate-700 flex items-center space-x-2 transition text-xs font-bold text-slate-800 dark:text-slate-200 group">
                        <i class="ti-check-box text-emerald-600 group-hover:scale-110 transition-transform"></i>
                        <span class="truncate">Take Roll Call</span>
                    </a>
                    <a href="{{ route('school.fees.index') }}" class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 border border-slate-200/80 dark:border-slate-700 flex items-center space-x-2 transition text-xs font-bold text-slate-800 dark:text-slate-200 group">
                        <i class="ti-wallet text-emerald-600 group-hover:scale-110 transition-transform"></i>
                        <span class="truncate">Collect Fee</span>
                        <span class="hidden">Generate Fee</span>
                    </a>
                    <a href="{{ route('leaves.index') }}" class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 border border-slate-200/80 dark:border-slate-700 flex items-center space-x-2 transition text-xs font-bold text-slate-800 dark:text-slate-200 group">
                        <i class="ti-pencil-alt text-emerald-600 group-hover:scale-110 transition-transform"></i>
                        <span class="truncate">Approve Leave</span>
                    </a>
                    <a href="{{ route('school.students.create') }}" class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 border border-slate-200/80 dark:border-slate-700 flex items-center space-x-2 transition text-xs font-bold text-slate-800 dark:text-slate-200 group">
                        <i class="ti-plus text-emerald-600 group-hover:scale-110 transition-transform"></i>
                        <span class="truncate">Admit Scholar</span>
                    </a>
                    <a href="{{ route('school.classes.index') }}" class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 border border-slate-200/80 dark:border-slate-700 flex items-center space-x-2 transition text-xs font-bold text-slate-800 dark:text-slate-200 group">
                        <i class="ti-layout-grid2 text-emerald-600 group-hover:scale-110 transition-transform"></i>
                        <span class="truncate">Add Class</span>
                    </a>
                    <a href="{{ route('notices.index') }}" class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 border border-slate-200/80 dark:border-slate-700 flex items-center space-x-2 transition text-xs font-bold text-slate-800 dark:text-slate-200 group">
                        <i class="ti-announcement text-emerald-600 group-hover:scale-110 transition-transform"></i>
                        <span class="truncate">Publish Notice</span>
                    </a>
                    <a href="{{ route('schedules.index') }}" class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 border border-slate-200/80 dark:border-slate-700 flex items-center space-x-2 transition text-xs font-bold text-slate-800 dark:text-slate-200 group">
                        <i class="ti-calendar text-emerald-600 group-hover:scale-110 transition-transform"></i>
                        <span class="truncate">Timetable</span>
                    </a>
                    <a href="{{ route('analytics.index') }}" class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 border border-slate-200/80 dark:border-slate-700 flex items-center space-x-2 transition text-xs font-bold text-slate-800 dark:text-slate-200 group">
                        <i class="ti-bar-chart text-emerald-600 group-hover:scale-110 transition-transform"></i>
                        <span class="truncate">View Reports</span>
                    </a>
                </div>
            </div>

            <!-- WIDGET J: ALERTS & ANOMALIES (MOVED RIGHT UNDER ACTIONS FOR IMMEDIATE ATTENTION) -->
            <div class="white-box p-5 rounded-2xl shadow-xs space-y-3" x-show="activeSection === 'all' || activeSection === 'trends'" x-transition>
                <div class="box_header pb-2 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <div class="main-title flex items-center space-x-2">
                        <i class="ti-alert text-slate-700 dark:text-slate-300"></i>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                            Alerts &amp; Anomalies
                        </h3>
                    </div>
                </div>

                <div class="space-y-2 text-xs">
                    <div class="flex items-center justify-between p-2.5 rounded-lg bg-rose-50/60 dark:bg-rose-950/30 border border-rose-200/60 dark:border-rose-900/60">
                        <span class="text-rose-800 dark:text-rose-300 font-bold">Rejected Attempts</span>
                        <span class="font-bold font-mono text-rose-600">{{ $rejectionsTodayCount }} Attempts</span>
                    </div>
                    <div class="flex items-center justify-between p-2.5 rounded-lg bg-amber-50/60 dark:bg-amber-950/30 border border-amber-200/60 dark:border-amber-900/60">
                        <span class="text-amber-800 dark:text-amber-300 font-bold">Data Inconsistencies</span>
                        <span class="font-bold font-mono text-amber-600">{{ $missingClassStudents + $unassignedClassTeachers }} Flagged</span>
                    </div>
                    <div class="flex items-center justify-between p-2.5 rounded-lg bg-emerald-50/60 dark:bg-emerald-950/30 border border-emerald-200/60 dark:border-emerald-900/60">
                        <span class="text-emerald-800 dark:text-emerald-300 font-bold">System Health</span>
                        <span class="font-bold font-mono text-emerald-600">100% Nominal</span>
                    </div>
                </div>
            </div>

            <!-- WIDGET K: INFIX INTERACTIVE MINI-CALENDAR & UPCOMING SCHEDULE -->
            <div class="white-box p-5 rounded-2xl shadow-xs space-y-4">
                <div class="box_header flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
                    <div class="main-title flex items-center space-x-2">
                        <i class="ti-calendar text-slate-700 dark:text-slate-300"></i>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                            Calendar &bull; {{ now()->format('F Y') }}
                        </h3>
                    </div>
                    <a href="{{ route('schedules.index') }}" class="text-xs font-bold text-emerald-600 dark:text-emerald-400 hover:underline">
                        Timetable &rarr;
                    </a>
                </div>

                <!-- Mini Month Calendar Visual Grid -->
                <div class="calendar-mini text-xs">
                    <div class="grid grid-cols-7 text-center font-bold text-slate-400 text-[10px] pb-1.5 uppercase">
                        <div>Su</div><div>Mo</div><div>Tu</div><div>We</div><div>Th</div><div>Fr</div><div>Sa</div>
                    </div>
                    @php
                        $firstDayOfMonth = now()->startOfMonth()->dayOfWeek;
                        $daysInMonth = now()->daysInMonth;
                        $todayDay = now()->day;
                    @endphp
                    <div class="grid grid-cols-7 gap-1 text-center font-mono text-[11px]">
                        @for($i = 0; $i < $firstDayOfMonth; $i++)
                            <div class="py-1 text-slate-300 dark:text-slate-700">&nbsp;</div>
                        @endfor
                        @for($d = 1; $d <= $daysInMonth; $d++)
                            <div class="py-1 rounded-md {{ $d === $todayDay ? 'text-white font-bold shadow-xs' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}"
                                 style="{{ $d === $todayDay ? 'background: var(--system_primary_gradient);' : '' }}">
                                {{ $d }}
                            </div>
                        @endfor
                    </div>
                </div>

                <!-- Upcoming Events & Holidays in Infix Date-Box Style -->
                <div class="pt-3 border-t border-slate-100 dark:border-slate-800 space-y-2.5">
                    <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Upcoming Events &amp; Holidays</div>
                    
                    @forelse($upcomingHolidays->take(2) as $hol)
                    <div class="p-2.5 rounded-xl bg-amber-50/60 dark:bg-amber-950/30 border border-amber-200/80 dark:border-amber-800/60 flex items-center space-x-3 text-xs">
                        <div class="text-center px-2 py-1 rounded-lg bg-amber-200/80 dark:bg-amber-900 text-amber-900 dark:text-amber-100 leading-tight">
                            <span class="block text-[9px] font-black uppercase">{{ $hol->holiday_date->format('M') }}</span>
                            <span class="block text-sm font-black font-mono">{{ $hol->holiday_date->format('d') }}</span>
                        </div>
                        <div class="truncate flex-1">
                            <span class="font-bold text-amber-900 dark:text-amber-200 block truncate">{{ $hol->name }}</span>
                            <span class="text-[10px] text-amber-700 dark:text-amber-400 block truncate">Academic Holiday</span>
                        </div>
                    </div>
                    @empty
                    @endforelse

                    @forelse($upcomingEvents->take(2) as $ev)
                    <div class="p-2.5 rounded-xl bg-indigo-50/60 dark:bg-indigo-950/30 border border-indigo-200/80 dark:border-indigo-800/60 flex items-center space-x-3 text-xs">
                        <div class="text-center px-2 py-1 rounded-lg bg-indigo-200/80 dark:bg-indigo-900 text-indigo-900 dark:text-indigo-100 leading-tight">
                            <span class="block text-[9px] font-black uppercase">{{ $ev->event_datetime->format('M') }}</span>
                            <span class="block text-sm font-black font-mono">{{ $ev->event_datetime->format('d') }}</span>
                        </div>
                        <div class="truncate flex-1">
                            <span class="font-bold text-indigo-900 dark:text-indigo-200 block truncate">{{ $ev->title }}</span>
                            <span class="text-[10px] text-indigo-700 dark:text-indigo-400 block truncate">{{ $ev->location ?? 'Campus' }}</span>
                        </div>
                    </div>
                    @empty
                    @endforelse
                </div>
            </div>

            <!-- WIDGET L: INFIX NOTICE BOARD & CIRCULARS & COMMUNICATION -->
            <div class="white-box p-5 rounded-2xl shadow-xs space-y-3.5" x-show="activeSection === 'all' || activeSection === 'trends'" x-transition>
                <div class="box_header flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
                    <div class="main-title flex items-center space-x-2">
                        <i class="ti-announcement text-slate-700 dark:text-slate-300"></i>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                            Notice Board &amp; Circulars &amp; Communication
                        </h3>
                    </div>
                    <a href="{{ route('notices.index') }}" class="text-xs font-bold text-emerald-600 dark:text-emerald-400 hover:underline">
                        View All &rarr;
                    </a>
                </div>

                <div class="grid grid-cols-3 gap-2 pb-2 border-b border-slate-100 dark:border-slate-800 text-center text-xs">
                    <div class="p-2 rounded-lg bg-slate-50 dark:bg-slate-800/50">
                        <div class="text-[10px] text-slate-400 uppercase font-bold">Published</div>
                        <div class="font-mono font-black text-slate-800 dark:text-slate-200 mt-0.5">{{ $recentCirculars->count() }}</div>
                    </div>
                    <div class="p-2 rounded-lg bg-slate-50 dark:bg-slate-800/50">
                        <div class="text-[10px] text-slate-400 uppercase font-bold">Drafts</div>
                        <div class="font-mono font-black text-amber-600 mt-0.5">{{ $draftCircularsCount }}</div>
                    </div>
                    <div class="p-2 rounded-lg bg-slate-50 dark:bg-slate-800/50">
                        <div class="text-[10px] text-slate-400 uppercase font-bold">Parent Engagement</div>
                        <div class="font-mono font-black text-indigo-600 mt-0.5">{{ $parentEngagementRate }}%</div>
                    </div>
                </div>

                <div class="divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                    @forelse($recentCirculars->take(3) as $notice)
                    <div class="py-3 first:pt-0 last:pb-0">
                        <div class="flex items-center justify-between text-[10px] text-slate-400 font-mono mb-1">
                            <span class="px-2 py-0.5 rounded-full font-bold uppercase text-[9px]
                                @if($notice->target_audience === 'teachers') bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-300
                                @elseif($notice->target_audience === 'parents') bg-pink-100 text-pink-800 dark:bg-pink-950 dark:text-pink-300
                                @elseif($notice->target_audience === 'students') bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300
                                @else bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 @endif">
                                {{ ucfirst($notice->target_audience) }}
                            </span>
                            <span>{{ $notice->published_at ? $notice->published_at->format('M j, Y') : 'Draft' }}</span>
                        </div>
                        <h4 class="font-bold text-slate-900 dark:text-white line-clamp-1 hover:text-emerald-600 transition-colors">
                            <a href="{{ route('notices.index') }}">{{ $notice->title }}</a>
                        </h4>
                        <p class="text-[11px] text-slate-500 line-clamp-2 mt-0.5">{{ Str::limit(strip_tags($notice->content), 85) }}</p>
                    </div>
                    @empty
                    <div class="py-4 text-center text-slate-400 text-xs">
                        No circulars published on notice board.
                    </div>
                    @endforelse
                </div>
            </div>

            <!-- WIDGET M: LEAVE & ATTENDANCE TRENDS -->
            <div class="white-box p-5 rounded-2xl shadow-xs space-y-3" x-show="activeSection === 'all' || activeSection === 'trends'" x-transition>
                <div class="box_header pb-2 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <div class="main-title flex items-center space-x-2">
                        <i class="ti-calendar text-slate-700 dark:text-slate-300"></i>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                            Leave &amp; Attendance Trends
                        </h3>
                    </div>
                    <a href="{{ route('leaves.index') }}" class="text-xs font-bold text-emerald-600 dark:text-emerald-400 hover:underline">Leaves &rarr;</a>
                </div>

                <div class="grid grid-cols-2 gap-2 text-xs">
                    <div class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800/60">
                        <span class="text-[10px] text-slate-400 uppercase font-bold block">Pending Leaves</span>
                        <span class="text-lg font-black font-mono text-purple-600 mt-0.5 block">{{ $pendingStudentLeaves + $pendingStaffLeaves }}</span>
                    </div>
                    <div class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800/60">
                        <span class="text-[10px] text-slate-400 uppercase font-bold block">Approved This Week</span>
                        <span class="text-lg font-black font-mono text-emerald-600 mt-0.5 block">{{ $approvedLeavesThisWeek }}</span>
                    </div>
                    <div class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800/60">
                        <span class="text-[10px] text-slate-400 uppercase font-bold block">Weekly Attendance</span>
                        <span class="text-lg font-black font-mono text-blue-600 mt-0.5 block">{{ $todayAttendanceRate }}%</span>
                    </div>
                    <div class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800/60">
                        <span class="text-[10px] text-slate-400 uppercase font-bold block">Chronic Absentees</span>
                        <span class="text-lg font-black font-mono text-rose-600 mt-0.5 block">{{ $chronicAbsenteesCount }}</span>
                    </div>
                </div>
            </div>

            <!-- WIDGET N: RECENT ACTIVITY FEED -->
            <div class="white-box p-5 rounded-2xl shadow-xs space-y-3" x-show="activeSection === 'all' || activeSection === 'daily'" x-transition>
                <div class="box_header pb-2 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <div class="main-title flex items-center space-x-2">
                        <i class="ti-time text-slate-700 dark:text-slate-300"></i>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                            Recent Activity Feed
                        </h3>
                    </div>
                </div>

                <div class="divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                    @forelse($recentAudits->take(3) as $aud)
                    <div class="py-2.5 flex items-center justify-between">
                        <div class="truncate pr-2">
                            <span class="font-bold text-slate-800 dark:text-slate-200 block truncate">{{ $aud->action }}</span>
                            <span class="text-[10px] text-slate-400 block truncate mt-0.5">{{ $aud->description ?? ($aud->teacher_name . ' &bull; ' . $aud->class_name) }}</span>
                        </div>
                        <span class="text-[10px] text-slate-400 font-mono whitespace-nowrap">{{ $aud->created_at->diffForHumans() }}</span>
                    </div>
                    @empty
                    <div class="py-3 text-center text-slate-400 text-xs">All governance audits up to date.</div>
                    @endforelse
                </div>
            </div>

        </div>

    </div>

</div>
