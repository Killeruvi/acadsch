@extends('layouts.app')

@section('title', 'Certificates & Digital Badges' . ' — ' . ($school['name'] ?? 'PrimeOS Academy'))

@section('content')
<div class="max-w-6xl mx-auto space-y-4">

    <!-- Header & Action Row -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-1 border-b border-slate-100 dark:border-slate-800">
        <div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                Certificates &amp; Digital Badges
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Verifiable credentials for {{ $viewUser->name }}
            </p>
        </div>

        @if($canIssue)
        <div x-data="{ openIssueModal: false }">
            <button @click="openIssueModal = true" class="px-3 py-1.5 bg-amber-600 hover:bg-amber-500 text-white rounded-lg text-xs font-bold shadow-xs transition flex items-center space-x-1.5">
                    <span>+</span>
                    <span>Issue Certificate</span>
                </button>

                <!-- Issue Certificate Modal -->
                <div x-show="openIssueModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs" @keydown.escape.window="openIssueModal = false">
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl max-w-lg w-full p-6 shadow-2xl relative text-left" @click.away="openIssueModal = false">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800 mb-4">
                            <h3 class="text-sm font-extrabold text-slate-900 dark:text-white flex items-center space-x-2">
                                <span>📜</span> <span>Issue Official Certificate</span>
                            </h3>
                            <button @click="openIssueModal = false" class="text-slate-400 hover:text-slate-200 text-lg">&times;</button>
                        </div>
                        <form action="{{ route('certificates.issue') }}" method="POST" class="space-y-3 text-xs">
                            @csrf
                            <div>
                                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Select Student</label>
                                <select name="user_id" required class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs">
                                    @foreach($allStudents as $st)
                                        <option value="{{ $st->id }}">{{ $st->name }} ({{ $st->student_id_number }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Certificate Title</label>
                                <input type="text" name="title" required placeholder="e.g. Certificate of Academic Distinction in Olympiad Physics" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs">
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Category</label>
                                    <select name="category" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs">
                                        <option value="academic_excellence">Academic Excellence</option>
                                        <option value="olympiad">National Olympiad</option>
                                        <option value="debate">Debate Championship</option>
                                        <option value="project_mastery">Capstone Project Mastery</option>
                                        <option value="course_completion">Curriculum Completion</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Issue Date</label>
                                    <input type="date" name="issued_date" required value="{{ now()->format('Y-m-d') }}" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs">
                                </div>
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Official Citation</label>
                                <textarea name="citation" rows="3" required placeholder="In recognition of outstanding scholastic dedication, mastery of foundational theorems, and stellar academic ethics..." class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs"></textarea>
                            </div>
                            <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex justify-end space-x-2">
                                <button type="button" @click="openIssueModal = false" class="px-3 py-1.5 rounded-xl text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800">Cancel</button>
                                <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-500 text-white font-bold rounded-xl shadow-xs">Generate &amp; Seal</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            @endif
    </div>

    <!-- 1. OFFICIAL CERTIFICATES SECTION -->
    <div class="space-y-3">
        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center space-x-1.5">
            <span>📜</span> <span>Issued Institutional Certificates ({{ $certificates->total() }})</span>
        </h2>

        @if($certificates->isEmpty())
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-6 text-center text-xs text-slate-400">
            No official certificates issued yet for this scholar account. Certificates are awarded upon completion of capstones, Olympiad milestones, and term examinations.
        </div>
        @else
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl divide-y divide-slate-100 dark:divide-slate-800 shadow-2xs overflow-hidden">
            @foreach($certificates as $cert)
            <div class="p-3 sm:p-3.5 hover:bg-slate-50 dark:hover:bg-slate-800/40 transition flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
                <div class="space-y-1 min-w-0">
                    <div class="flex flex-wrap items-center gap-1.5">
                        <span class="font-mono text-[9px] font-black px-1.5 py-0.5 rounded bg-amber-100 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300 border border-amber-300 dark:border-amber-800">
                            {{ $cert->certificate_number }}
                        </span>
                        <span class="text-[10px] text-slate-400">Issued {{ $cert->issued_date->format('M d, Y') }}</span>
                    </div>

                    <h3 class="font-bold text-xs sm:text-sm text-slate-900 dark:text-white truncate">
                        {{ $cert->title }}
                    </h3>

                    <p class="text-[11px] text-slate-500 dark:text-slate-400 italic line-clamp-1">
                        &ldquo;{{ $cert->citation }}&rdquo;
                    </p>
                </div>

                <div class="flex items-center space-x-2 shrink-0 pt-1 sm:pt-0 border-t sm:border-t-0 border-slate-100 dark:border-slate-800">
                    <a href="{{ route('certificates.verify', $cert->verification_hash) }}" target="_blank" class="text-[11px] font-bold text-amber-600 dark:text-amber-400 hover:underline">
                        Verify Authenticity
                    </a>
                    @if($canIssue && !$cert->revoked_at)
                    <form action="{{ route('certificates.destroy', $cert->id) }}" method="POST" onsubmit="return confirm('Revoke this certificate? The verification record will be retained.');" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-2 py-1 rounded-lg bg-rose-50 text-rose-600 dark:bg-rose-950/40 dark:text-rose-300 font-bold text-xs hover:bg-rose-100 transition" title="Revoke Certificate">
                            Revoke
                        </button>
                    </form>
                    @endif
                    @if($cert->revoked_at)
                        <span class="px-2 py-1 rounded-lg bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300 font-bold text-xs">Revoked</span>
                    @endif
                    <a href="{{ route('certificates.show', $cert->id) }}" target="_blank" class="px-3 py-1.5 rounded-lg bg-amber-600 hover:bg-amber-500 text-white font-bold text-xs shadow-2xs transition whitespace-nowrap">
                        Parchment 🖨️
                    </a>
                </div>
            </div>
            @endforeach
        </div>

        @if($certificates->hasPages())
        <div class="mt-3">
            {{ $certificates->links() }}
        </div>
        @endif
        @endif
    </div>

    <!-- 2. DIGITAL ACHIEVEMENT BADGES MATRIX -->
    <div class="space-y-3 pt-3">
        <div class="flex items-center justify-between">
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center space-x-1.5">
                <span>⭐</span> <span>Digital Achievement Badges ({{ $badges->count() }} Unlocked)</span>
            </h2>
            <span class="text-[11px] text-slate-400">Streaks, quizzes, debates, projects &amp; birthdays</span>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
            @foreach($allSystemBadges as $sb)
            @php
                $isEarned = in_array($sb['key'], $earnedKeys, true) || $badges->contains('badge_key', $sb['key']);
            @endphp
            <div class="rounded-xl p-2.5 sm:p-3 border text-center transition flex flex-col justify-between
                {{ $isEarned ? 'bg-white dark:bg-slate-900 border-emerald-400/80 dark:border-emerald-700/80 shadow-2xs' : 'bg-slate-50 dark:bg-slate-800/40 border-slate-200 dark:border-slate-800 opacity-60' }}">
                <div class="space-y-1">
                    <div class="text-2xl mx-auto {{ $isEarned ? 'animate-pulse' : 'filter grayscale' }}">
                        {{ $sb['icon'] }}
                    </div>
                    <div>
                        <h4 class="font-bold text-xs text-slate-900 dark:text-white">{{ $sb['title'] }}</h4>
                        <p class="text-[10px] text-slate-500 dark:text-slate-400 line-clamp-1">{{ $sb['desc'] }}</p>
                    </div>
                </div>

                <div class="mt-2 pt-1 border-t border-slate-100 dark:border-slate-800 text-[10px] font-bold">
                    @if($isEarned || $badges->contains(fn ($badge) => str_starts_with($badge->badge_key, $sb['key'] . '_')))
                    <span class="text-emerald-600 dark:text-emerald-400">✓ Unlocked</span>
                    @else
                    <span class="text-slate-400">🔒 Locked</span>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>

</div>
@endsection
