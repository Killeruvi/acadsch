<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Offline Mode &bull; {{ $school["name"] ?? "PrimeArc Public School" }}</title>
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#0f172a">
    <link rel="icon" type="image/svg+xml" href="/icons/icon.svg">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        navy: { 900: '#0f172a', 950: '#020617' }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-slate-900 text-slate-100 min-h-screen flex flex-col justify-between font-sans selection:bg-emerald-500 selection:text-white p-4">

    <!-- Header -->
    <header class="max-w-4xl mx-auto w-full pt-6 flex items-center justify-between border-b border-slate-800 pb-4">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-500 via-teal-400 to-cyan-500 flex items-center justify-center text-white font-black text-xl shadow-lg shadow-emerald-500/25">
                PA
            </div>
            <div>
                <span class="text-base font-extrabold tracking-tight text-white block">{{ !empty($school["name"]) && $school["name"] !== 'PrimeArc Public School' ? ($school["name"] . ' • PrimeArc Public School') : 'PrimeArc Public School' }}</span>
                <span class="text-[10px] text-emerald-400 font-semibold tracking-wider uppercase">Institutional Offline Gateway</span>
            </div>
        </div>
        <div class="flex items-center space-x-2">
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-amber-500/15 text-amber-300 border border-amber-500/30">
                <span class="w-2 h-2 mr-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                Offline Mode Active
            </span>
        </div>
    </header>

    <!-- Main Offline Content -->
    <main class="max-w-2xl mx-auto w-full my-auto py-8 space-y-6">
        
        <!-- Connection Status Card -->
        <div class="bg-slate-950/70 rounded-3xl p-6 sm:p-8 border border-slate-800 shadow-2xl backdrop-blur-md text-center">
            
            <!-- Animated Satellite Icon -->
            <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-gradient-to-tr from-amber-500/20 to-orange-500/10 border border-amber-500/30 flex items-center justify-center text-amber-400">
                <svg class="w-8 h-8 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"></path>
                </svg>
            </div>

            <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight mb-2">Offline Mode &bull; Connection Interrupted</h1>
            <p class="text-xs text-slate-400 mb-5 leading-relaxed max-w-md mx-auto">
                No active internet connection detected. Your <strong class="text-amber-300">Class Timetable &amp; Offline Schedules</strong> are cached locally on this device and remain fully accessible below.
            </p>

            <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                <button onclick="window.location.reload()" 
                        class="w-full sm:w-auto py-2.5 px-5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-500 hover:from-emerald-500 hover:to-teal-400 text-white font-extrabold text-xs transition shadow-lg shadow-emerald-600/30 flex items-center justify-center space-x-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    <span>Check Connection &amp; Retry</span>
                </button>

                <a href="/schedule" 
                   class="w-full sm:w-auto py-2.5 px-5 rounded-xl bg-slate-800/90 hover:bg-slate-800 text-slate-200 text-xs font-bold border border-slate-700 transition">
                    Open Full Timetable
                </a>
            </div>
        </div>

        <!-- OFFLINE CLASS SCHEDULE VIEWER (LOADED FROM LOCALSTORAGE) -->
        <div id="offline-schedule-container" class="bg-slate-950/70 rounded-3xl p-6 border border-slate-800 shadow-xl backdrop-blur-md">
            <div class="flex items-center justify-between pb-3 border-b border-slate-800 mb-4">
                <div class="flex items-center space-x-2">
                    <span class="text-lg">📅</span>
                    <h2 class="text-sm font-bold text-white uppercase tracking-wider">
                        Offline Class Schedule
                    </h2>
                </div>
                <span id="schedule-count-badge" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-950 text-emerald-300 border border-emerald-800">
                    Locally Cached
                </span>
            </div>

            <!-- Day selector pills (generated by JS) -->
            <div id="offline-day-pills" class="flex items-center space-x-1.5 overflow-x-auto pb-3 mb-3 border-b border-slate-800/60">
                <!-- Injected via script -->
            </div>

            <!-- Schedule slots container -->
            <div id="offline-slots-list" class="space-y-2 text-left">
                <div class="p-4 text-center text-xs text-slate-400">
                    Loading offline timetable from cache...
                </div>
            </div>
        </div>

    </main>

    <!-- Footer -->
    <footer class="max-w-4xl mx-auto w-full pt-4 pb-2 text-center text-xs text-slate-500 border-t border-slate-800/60 flex flex-col sm:flex-row items-center justify-between gap-2">
        <span class="text-amber-400 font-bold">🔥 Aim, Ascend, Achieve</span>
        <span class="text-xs text-slate-400">Developed by Prithviraj with love for India 🇮🇳</span>
    </footer>

    <script>
        // Automatic reconnection detection
        window.addEventListener('online', () => {
            window.location.reload();
        });

        // Offline Schedule Reader
        document.addEventListener('DOMContentLoaded', () => {
            const cachedRaw = localStorage.getItem('school_offline_schedule_cache');
            const slotsContainer = document.getElementById('offline-slots-list');
            const dayPillsContainer = document.getElementById('offline-day-pills');
            const countBadge = document.getElementById('schedule-count-badge');

            if (!cachedRaw) {
                slotsContainer.innerHTML = `
                    <div class="p-6 text-center text-slate-400 text-xs">
                        <p class="font-bold text-slate-300 mb-1">No Offline Schedule Cached Yet</p>
                        <p>Visit the <a href="/schedule" class="text-emerald-400 underline">Timetable page</a> while connected to save your offline class schedule.</p>
                    </div>
                `;
                return;
            }

            try {
                const schedules = JSON.parse(cachedRaw);
                if (!Array.isArray(schedules) || schedules.length === 0) {
                    slotsContainer.innerHTML = `<div class="p-4 text-center text-xs text-slate-400">No scheduled sessions found in cache.</div>`;
                    return;
                }

                countBadge.textContent = `${schedules.length} Sessions Saved`;

                const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                const daysPresent = days.filter(d => schedules.some(s => s.day_of_week === d));
                let activeDay = daysPresent[0] || 'Monday';

                function renderDayPills() {
                    dayPillsContainer.innerHTML = daysPresent.map(d => {
                        const count = schedules.filter(s => s.day_of_week === d).length;
                        const isAct = (d === activeDay);
                        return `
                            <button type="button" 
                                    onclick="switchOfflineDay('${d}')"
                                    class="px-3 py-1.5 rounded-xl text-xs font-bold transition whitespace-nowrap ${isAct ? 'bg-emerald-600 text-white shadow-xs' : 'bg-slate-800 text-slate-300 hover:bg-slate-700'}">
                                ${d} (${count})
                            </button>
                        `;
                    }).join('');
                }

                window.switchOfflineDay = function(d) {
                    activeDay = d;
                    renderDayPills();
                    renderSlots();
                };

                function renderSlots() {
                    const daySlots = schedules.filter(s => s.day_of_week === activeDay);
                    if (daySlots.length === 0) {
                        slotsContainer.innerHTML = `<div class="p-4 text-center text-xs text-slate-400">No classes scheduled on ${activeDay}.</div>`;
                        return;
                    }

                    slotsContainer.innerHTML = daySlots.map((s, idx) => `
                        <div class="bg-slate-900/90 border border-slate-800 rounded-xl p-3 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 rounded-lg bg-emerald-950/80 border border-emerald-800 text-emerald-300 flex items-center justify-center font-bold text-xs flex-shrink-0">
                                    P${idx + 1}
                                </div>
                                <div>
                                    <div class="flex items-center space-x-2">
                                        <span class="font-bold text-white text-xs">${s.subject_name || s.title}</span>
                                        <span class="px-1.5 py-0.2 rounded text-[10px] font-bold ${s.delivery_mode === 'offline' ? 'bg-emerald-950 text-emerald-300 border border-emerald-800' : 'bg-cyan-950 text-cyan-300 border border-cyan-800'}">
                                            ${s.delivery_mode === 'offline' ? '🏫 Offline' : '🌐 Online'}
                                        </span>
                                    </div>
                                    <div class="text-[11px] text-slate-400 mt-0.5">
                                        <span>👤 ${s.teacher_name || 'Faculty'}</span>
                                        <span class="mx-1">&bull;</span>
                                        <span>📍 ${s.room || 'Room TBA'}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="text-right flex-shrink-0 self-end sm:self-center">
                                <span class="font-mono font-bold text-xs text-emerald-400">${s.start_time} - ${s.end_time}</span>
                            </div>
                        </div>
                    `).join('');
                }

                renderDayPills();
                renderSlots();

            } catch (err) {
                console.error('Offline schedule parsing error:', err);
            }
        });
    </script>
</body>
</html>
