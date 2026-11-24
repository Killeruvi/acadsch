<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>@yield('title', 'Institutional Setup Wizard') &bull; School ERP Installer</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#f5f3ff',
                            100: '#ede9fe',
                            500: '#7c32ff',
                            600: '#6b20e6',
                            700: '#5814be',
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-slate-900 min-h-screen text-slate-100 font-sans antialiased flex flex-col justify-between selection:bg-purple-500 selection:text-white">

    <!-- Top Header -->
    <header class="border-b border-slate-800/80 bg-slate-950/60 backdrop-blur-md sticky top-0 z-50">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-purple-600 to-indigo-500 flex items-center justify-center shadow-lg shadow-purple-500/20 text-white font-black text-xl">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-base font-extrabold tracking-tight text-white">School ERP Setup Wizard</h1>
                    <p class="text-xs text-slate-400">Production-Grade Automated Web Deployment</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    v2.5.0 Production Ready
                </span>
            </div>
        </div>
    </header>

    <!-- Main Content Container -->
    <main class="flex-grow py-8 px-4 sm:px-6 lg:px-8">
        <div class="max-w-4xl mx-auto">

            <!-- Stepper Indicator -->
            @php
                $currentRoute = Route::currentRouteName();
                $steps = [
                    ['route' => 'install.requirements', 'label' => '1. Server Health'],
                    ['route' => 'install.database', 'label' => '2. Database'],
                    ['route' => 'install.branding', 'label' => '3. Branding'],
                    ['route' => 'install.admin', 'label' => '4. Super Admin'],
                    ['route' => 'install.complete', 'label' => '5. Ready'],
                ];
            @endphp
            <div class="mb-8">
                <div class="grid grid-cols-5 gap-2 sm:gap-4 text-center">
                    @foreach($steps as $idx => $step)
                        @php
                            $isActive = $currentRoute === $step['route'];
                        @endphp
                        <div class="flex flex-col items-center">
                            <div class="w-full h-1.5 rounded-full mb-2 {{ $isActive ? 'bg-purple-500 shadow-sm shadow-purple-500/50' : 'bg-slate-800' }}"></div>
                            <span class="text-[11px] sm:text-xs font-semibold tracking-wide {{ $isActive ? 'text-purple-400' : 'text-slate-500' }}">
                                {{ $step['label'] }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Flash Alerts -->
            @if(session('error'))
                <div class="mb-6 p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-300 text-sm flex items-start gap-3 shadow-lg">
                    <svg class="w-5 h-5 text-rose-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <div>
                        <div class="font-bold text-rose-200">Attention Required</div>
                        <div class="mt-0.5">{{ session('error') }}</div>
                    </div>
                </div>
            @endif

            @if(session('success'))
                <div class="mb-6 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-300 text-sm flex items-start gap-3 shadow-lg">
                    <svg class="w-5 h-5 text-emerald-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <div>
                        <div class="font-bold text-emerald-200">Success</div>
                        <div class="mt-0.5">{{ session('success') }}</div>
                    </div>
                </div>
            @endif

            <!-- Body Card -->
            <div class="bg-slate-950/80 border border-slate-800 rounded-2xl shadow-2xl p-6 sm:p-8 backdrop-blur-sm">
                @yield('content')
            </div>

        </div>
    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-800/60 py-6 text-center text-xs text-slate-500">
        <p>&copy; {{ date('Y') }} Institutional School ERP &bull; Pure Clean Commercial Deployment &bull; 100% White-Label</p>
    </footer>

</body>
</html>
