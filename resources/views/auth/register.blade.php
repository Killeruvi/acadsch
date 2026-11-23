@extends('layouts.app')

@section('title', 'Register' . ' — ' . ($school['name'] ?? 'PrimeOS Academy'))

@section('content')
<div class="max-w-md mx-auto my-6 sm:my-8 px-4" x-data="{ role: '{{ old('role', 'student') }}' }">
    <div class="bg-white dark:bg-slate-900 p-6 sm:p-8 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-800 transition-colors">
        
        <!-- Header -->
        <div class="text-center mb-6">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-emerald-500 via-teal-400 to-cyan-500 mx-auto flex items-center justify-center text-white font-black text-2xl shadow-lg shadow-emerald-500/25 mb-2.5">
                PA
            </div>
            <div class="inline-flex items-center space-x-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300 border border-amber-200 dark:border-amber-800 mb-1">
                <span>🔥</span>
                <span>Aim, Ascend, Achieve</span>
            </div>
            <h2 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight">Institutional Registration</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Create your student or faculty academic profile</p>
        </div>

        @if($errors->any())
        <div class="mb-5 p-3 rounded-xl bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-300 text-xs">
            <div class="font-bold mb-1 flex items-center space-x-1">
                <span>⚠️</span>
                <span>Please correct the following:</span>
            </div>
            <ul class="list-disc pl-4 space-y-0.5 text-xs">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form method="POST" action="{{ route('register.post') }}" class="space-y-4">
            @csrf

            <!-- Full Name -->
            <div>
                <label for="name" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1 flex items-center space-x-1">
                    <span>👤</span>
                    <span>Full Name</span>
                </label>
                <input type="text" name="name" id="name" required value="{{ old('name') }}" placeholder="e.g. Aarav Sharma"
                       class="w-full px-3.5 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-hidden focus:ring-2 focus:ring-emerald-500 text-xs sm:text-sm shadow-2xs">
            </div>

            <!-- Email Address -->
            <div>
                <label for="email" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1 flex items-center space-x-1">
                    <span>📧</span>
                    <span>Email Address</span>
                </label>
                <input type="email" name="email" id="email" required value="{{ old('email') }}" placeholder="student@school.edu"
                       class="w-full px-3.5 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-hidden focus:ring-2 focus:ring-emerald-500 text-xs sm:text-sm shadow-2xs">
            </div>

            <!-- Role & Dynamic Classification Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <!-- Role Selection -->
                <div>
                    <label for="role" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1 flex items-center space-x-1">
                        <span>🎓</span>
                        <span>Role</span>
                    </label>
                    <select name="role" id="role" x-model="role" 
                            class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-hidden focus:ring-2 focus:ring-emerald-500 text-xs sm:text-sm shadow-2xs">
                        <option value="student">Student</option>
                        <option value="teacher">Teacher / Faculty</option>
                    </select>
                </div>

                <!-- Academic Class Selection (Student Only) -->
                <div x-show="role === 'student'">
                    <label for="class_id" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1 flex items-center space-x-1">
                        <span>🏫</span>
                        <span>Class / Grade</span>
                        <span class="text-rose-500">*</span>
                    </label>
                    <select name="class_id" id="class_id" :required="role === 'student'"
                            class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-hidden focus:ring-2 focus:ring-emerald-500 text-xs sm:text-sm shadow-2xs">
                        <option value="">Select Class...</option>
                        @if(!empty($classes))
                            @foreach($classes as $c)
                            <option value="{{ $c->id }}" {{ (string)old('class_id') === (string)$c->id ? 'selected' : '' }}>
                                {{ $c->name }} (Grade {{ $c->grade_level }})
                            </option>
                            @endforeach
                        @endif
                    </select>
                </div>

                <!-- Admin Faculty Authorization Code (Teacher Only) -->
                <div x-show="role === 'teacher'" x-cloak>
                    <label for="faculty_code" class="block text-xs font-bold uppercase tracking-wider text-purple-700 dark:text-purple-400 mb-1 flex items-center space-x-1">
                        <span>🔑</span>
                        <span>Faculty Code</span>
                        <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="faculty_code" id="faculty_code" :required="role === 'teacher'" value="{{ old('faculty_code') }}" placeholder="FAC-XXXX-XXXX" autocomplete="off"
                           class="w-full px-3 py-2 rounded-xl border-2 border-purple-400 dark:border-purple-600 bg-purple-50/50 dark:bg-purple-950/30 text-purple-900 dark:text-purple-200 font-mono font-bold uppercase tracking-wider focus:outline-hidden focus:ring-2 focus:ring-purple-500 text-xs sm:text-sm shadow-2xs">
                </div>
            </div>

            <!-- Teacher Authorization Notice -->
            <div x-show="role === 'teacher'" x-cloak class="p-3 rounded-xl bg-purple-50 dark:bg-purple-950/40 border border-purple-200 dark:border-purple-800 text-purple-900 dark:text-purple-200 text-xs">
                <div class="font-bold flex items-center space-x-1.5 mb-1">
                    <span>🛡️</span>
                    <span>Admin Authorization Required</span>
                </div>
                <p class="text-[11px] text-purple-800 dark:text-purple-300 leading-relaxed">
                    Teacher onboarding requires a one-time authorization code generated manually by the institutional administrator. Each physical device is restricted to only one teacher registration request.
                </p>
            </div>

            <!-- Permanent Phone Number (Mandatory) -->
            <div>
                <label for="phone" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1 flex items-center space-x-1">
                    <span>📱</span>
                    <span>Permanent Phone Number</span>
                    <span class="text-rose-500">*</span>
                </label>
                <input type="tel" name="phone" id="phone" required value="{{ old('phone') }}" placeholder="+91 98765 43210"
                       class="w-full px-3.5 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-hidden focus:ring-2 focus:ring-emerald-500 text-xs sm:text-sm shadow-2xs">
                <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5">Permanent institutional contact number for identity verification and SMS alerts.</p>
            </div>

            <!-- Password -->
            <div>
                <label for="password" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1 flex items-center space-x-1">
                    <span>🔒</span>
                    <span>Password</span>
                </label>
                <input type="password" name="password" id="password" required minlength="6" placeholder="Min. 6 characters"
                       class="w-full px-3.5 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-hidden focus:ring-2 focus:ring-emerald-500 text-xs sm:text-sm shadow-2xs">
            </div>

            <!-- Confirm Password -->
            <div>
                <label for="password_confirmation" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1 flex items-center space-x-1">
                    <span>🔒</span>
                    <span>Confirm Password</span>
                </label>
                <input type="password" name="password_confirmation" id="password_confirmation" required minlength="6" placeholder="Re-enter password"
                       class="w-full px-3.5 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-hidden focus:ring-2 focus:ring-emerald-500 text-xs sm:text-sm shadow-2xs">
            </div>

            <!-- Institutional Security Captcha Challenge -->
            <div class="p-3 bg-slate-50 dark:bg-slate-800/60 rounded-xl border border-slate-200 dark:border-slate-700">
                <div class="flex items-center justify-between mb-1.5">
                    <label for="reg-captcha" class="text-xs font-bold text-slate-700 dark:text-slate-300 flex items-center space-x-1">
                        <span>🛡️</span>
                        <span>Security Verification</span>
                        <span class="text-rose-500">*</span>
                    </label>
                    <button type="button" 
                            onclick="refreshCaptcha('reg-captcha-img', 'reg-captcha-token')"
                            class="text-[11px] font-bold text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 flex items-center space-x-1 transition">
                        <span>🔄</span>
                        <span>Refresh Code</span>
                    </button>
                </div>

                <div class="flex items-center space-x-2">
                    <div class="flex-shrink-0 rounded-lg overflow-hidden border border-slate-200 dark:border-slate-700 shadow-2xs bg-slate-50">
                        <img src="{{ \App\Services\CaptchaService::getInlineSvgDataUri() }}" id="reg-captcha-img" alt="Security Captcha" class="h-10 w-auto">
                    </div>
                    <input type="hidden" name="captcha_token" id="reg-captcha-token" value="{{ \App\Services\CaptchaService::getCurrentToken() }}">
                    <input type="text" name="captcha" id="reg-captcha" required autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" placeholder="Enter code above"
                           class="flex-1 px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-mono text-xs uppercase tracking-wider focus:outline-hidden focus:ring-2 focus:ring-emerald-500">
                </div>
                @error('captcha')
                <p class="text-rose-600 dark:text-rose-400 text-[11px] font-bold mt-1.5 flex items-center space-x-1">
                    <span>⚠️</span>
                    <span>{{ $message }}</span>
                </p>
                @enderror
            </div>

            <script>
            function refreshCaptcha(imgId, tokenId) {
                fetch('{{ route('captcha.refresh') }}')
                    .then(r => r.json())
                    .then(data => {
                        if (data && data.image) {
                            const img = document.getElementById(imgId);
                            if (img) img.src = data.image;
                            const tokenInput = document.getElementById(tokenId);
                            if (tokenInput && data.token) tokenInput.value = data.token;
                        }
                    })
                    .catch(() => {
                        const img = document.getElementById(imgId);
                        if (img) img.src = '{{ route('captcha.image') }}?v=' + Date.now();
                    });
            }
            </script>

            <!-- Submit Button -->
            <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-extrabold text-xs sm:text-sm shadow-md shadow-emerald-600/20 transition flex items-center justify-center space-x-1.5 mt-2">
                <span>Complete Registration</span>
                <span>🚀</span>
            </button>
        </form>

        <div class="mt-5 text-center text-xs text-slate-500 dark:text-slate-400">
            Already have an account? 
            <a href="{{ route('login') }}" class="text-emerald-600 dark:text-emerald-400 hover:underline font-bold">Sign In &rarr;</a>
        </div>
    </div>
</div>
@endsection
