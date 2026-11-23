@extends('layouts.app')

@section('title', 'Sign In' . ' — ' . ($school['name'] ?? 'PrimeOS Academy'))

@section('content')
<div class="max-w-md mx-auto my-8">
    <div class="bg-white p-8 rounded-2xl shadow-xl border border-slate-200">
        <div class="text-center mb-6">
            @if(!empty($school['logo_url']))
            <img src="{{ $school['logo_url'] }}" alt="{{ $school['name'] }}" class="w-16 h-16 rounded-2xl object-contain mx-auto mb-3 shadow-md">
            @else
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-emerald-500 via-teal-400 to-cyan-500 mx-auto flex items-center justify-center text-white font-black text-xl shadow-lg shadow-emerald-500/25 mb-3">
                {{ $school['short_name'] ?? 'POS' }}
            </div>
            @endif
            <h2 class="text-xl font-extrabold text-slate-900 tracking-tight">{{ $school['name'] }}</h2>
            <div class="mt-1 flex items-center justify-center space-x-1 text-xs font-extrabold text-emerald-700">
                <span>⚡ Powered by PrimeOS</span>
            </div>
        </div>

        @php $quote = \App\Services\MotivationService::getRandomQuote(); @endphp
        <div class="mb-5 p-3 rounded-xl bg-amber-50/70 border border-amber-200/80 text-center">
            <div class="text-xs font-medium text-amber-900 italic leading-snug">"{{ $quote['quote'] }}"</div>
            <div class="text-xs text-amber-700 font-bold mt-1">— {{ $quote['author'] }}</div>
        </div>

        @if($errors->any())
        <div class="mb-4 p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs">
            <div class="font-bold mb-1 flex items-center space-x-1">
                <span>⚠️</span>
                <span>Security Notice:</span>
            </div>
            <ul class="list-disc pl-4 space-y-0.5">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <!-- Credentials Form -->
        <form method="POST" action="{{ route('login.post') }}" class="space-y-4">
            @csrf
            <div>
                <label for="email" class="block text-xs font-semibold text-slate-700 mb-1">Institutional Email Address</label>
                <input type="email" name="email" id="email" required value="{{ old('email') }}" placeholder="name@school.edu"
                       class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent text-sm transition">
            </div>

            <div>
                <div class="flex items-center justify-between mb-1">
                    <label for="password" class="block text-xs font-semibold text-slate-700">Account Password</label>
                    <a href="{{ route('password.request') }}" class="text-[11px] font-semibold text-emerald-600 hover:text-emerald-700 hover:underline">Forgot Password?</a>
                </div>
                <input type="password" name="password" id="password" required placeholder="Enter your account password"
                       class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent text-sm transition">
            </div>

            <!-- Institutional Security Captcha Challenge -->
            <div class="p-3 bg-slate-50 dark:bg-slate-800/60 rounded-xl border border-slate-200 dark:border-slate-700">
                <div class="flex items-center justify-between mb-1.5">
                    <label for="captcha" class="text-xs font-bold text-slate-700 dark:text-slate-300 flex items-center space-x-1">
                        <span>🛡️</span>
                        <span>Security Verification</span>
                        <span class="text-rose-500">*</span>
                    </label>
                    <button type="button" 
                            onclick="refreshCaptcha('login-captcha-img', 'login-captcha-token')"
                            class="text-[11px] font-bold text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 flex items-center space-x-1 transition">
                        <span>🔄</span>
                        <span>Refresh Code</span>
                    </button>
                </div>

                <div class="flex items-center space-x-2">
                    <div class="flex-shrink-0 rounded-lg overflow-hidden border border-slate-200 dark:border-slate-700 shadow-2xs bg-slate-50">
                        <img src="{{ \App\Services\CaptchaService::getInlineSvgDataUri() }}" id="login-captcha-img" alt="Security Captcha" class="h-10 w-auto">
                    </div>
                    <input type="hidden" name="captcha_token" id="login-captcha-token" value="{{ \App\Services\CaptchaService::getCurrentToken() }}">
                    <input type="text" name="captcha" id="captcha" required autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" placeholder="Enter code above"
                           class="flex-1 px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-mono text-xs uppercase tracking-wider focus:outline-hidden focus:ring-2 focus:ring-emerald-500">
                </div>
                @error('captcha')
                <p class="text-rose-600 text-[11px] font-bold mt-1.5 flex items-center space-x-1">
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

            <div class="flex items-center justify-between text-xs">
                <label class="flex items-center text-slate-600 cursor-pointer">
                    <input type="checkbox" name="remember" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 mr-2">
                    Remember my session
                </label>
                <span class="text-slate-400 font-medium">Secured SSL Session 🔒</span>
            </div>

            <button type="submit" class="w-full py-2.5 px-4 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-sm shadow-md shadow-emerald-600/20 transition">
                Sign In to Portal
            </button>
        </form>

        <div class="mt-6 text-center text-xs text-slate-500">
            Don't have an account yet? 
            <a href="{{ route('register') }}" class="text-emerald-600 hover:underline font-semibold">Register here</a>
        </div>
    </div>
</div>
@endsection
