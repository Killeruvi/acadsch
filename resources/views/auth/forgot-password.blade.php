@extends('layouts.app')

@section('title', 'Forgot Password' . ' — ' . ($school['name'] ?? 'PrimeArc Public School'))

@section('content')
<div class="max-w-md mx-auto my-8">
    <div class="bg-white p-8 rounded-2xl shadow-xl border border-slate-200">
        <div class="text-center mb-6">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-emerald-500 via-teal-400 to-cyan-500 mx-auto flex items-center justify-center text-white font-black text-2xl shadow-lg shadow-emerald-500/25 mb-3">
                PA
            </div>
            <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">Account Recovery</h2>
            <p class="text-xs text-slate-500 mt-1">Enter your registered email address to receive password reset instructions.</p>
        </div>

        @if(session('status'))
        <div class="mb-5 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs">
            <div class="font-bold flex items-center space-x-1.5 mb-1">
                <span>✅</span>
                <span>Reset Link Dispatched:</span>
            </div>
            <p>{{ session('status') }}</p>
            @if(session('direct_reset_url'))
            <div class="mt-2.5 pt-2.5 border-t border-emerald-200/80">
                <span class="font-bold text-[11px] text-emerald-900 block mb-1">⚡ Quick Recovery Link (Local / Testing):</span>
                <a href="{{ session('direct_reset_url') }}" class="inline-flex items-center space-x-1 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs rounded-lg transition">
                    <span>Open Reset Form 🔑</span>
                </a>
            </div>
            @endif
        </div>
        @endif

        @if($errors->any())
        <div class="mb-5 p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs">
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

        <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
            @csrf
            <div>
                <label for="email" class="block text-xs font-semibold text-slate-700 mb-1">Institutional Email Address</label>
                <input type="email" name="email" id="email" required value="{{ old('email') }}" placeholder="name@school.edu"
                       class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent text-sm transition">
            </div>

            <!-- Institutional Security Captcha Challenge -->
            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                <div class="flex items-center justify-between mb-1.5">
                    <label for="captcha" class="text-xs font-bold text-slate-700 flex items-center space-x-1">
                        <span>🛡️</span>
                        <span>Security Verification</span>
                        <span class="text-rose-500">*</span>
                    </label>
                    <button type="button" 
                            onclick="refreshCaptcha('forgot-captcha-img', 'forgot-captcha-token')"
                            class="text-[11px] font-bold text-emerald-600 hover:text-emerald-700 flex items-center space-x-1 transition">
                        <span>🔄</span>
                        <span>Refresh Code</span>
                    </button>
                </div>

                <div class="flex items-center space-x-2">
                    <div class="flex-shrink-0 rounded-lg overflow-hidden border border-slate-200 shadow-2xs bg-slate-50">
                        <img src="{{ \App\Services\CaptchaService::getInlineSvgDataUri() }}" id="forgot-captcha-img" alt="Security Captcha" class="h-10 w-auto">
                    </div>
                    <input type="hidden" name="captcha_token" id="forgot-captcha-token" value="{{ \App\Services\CaptchaService::getCurrentToken() }}">
                    <input type="text" name="captcha" id="captcha" required autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" placeholder="Enter code above"
                           class="flex-1 px-3 py-2 rounded-lg border border-slate-300 bg-white text-slate-900 font-mono text-xs uppercase tracking-wider focus:outline-hidden focus:ring-2 focus:ring-emerald-500">
                </div>
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

            <button type="submit" class="w-full py-2.5 px-4 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-sm shadow-md shadow-emerald-600/20 transition">
                Send Reset Link
            </button>
        </form>

        <div class="mt-6 text-center text-xs text-slate-500">
            Remembered your password? 
            <a href="{{ route('login') }}" class="text-emerald-600 hover:underline font-semibold">Back to Sign In</a>
        </div>
    </div>
</div>
@endsection
