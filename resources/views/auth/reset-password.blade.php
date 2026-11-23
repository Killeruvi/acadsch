@extends('layouts.app')

@section('title', 'Set New Password' . ' — ' . ($school['name'] ?? 'PrimeArc Public School'))

@section('content')
<div class="max-w-md mx-auto my-8">
    <div class="bg-white p-8 rounded-2xl shadow-xl border border-slate-200">
        <div class="text-center mb-6">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-emerald-500 via-teal-400 to-cyan-500 mx-auto flex items-center justify-center text-white font-black text-2xl shadow-lg shadow-emerald-500/25 mb-3">
                PA
            </div>
            <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">Create New Password</h2>
            <p class="text-xs text-slate-500 mt-1">Please enter your institutional email and choose a strong new password.</p>
        </div>

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

        <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div>
                <label for="email" class="block text-xs font-semibold text-slate-700 mb-1">Institutional Email Address</label>
                <input type="email" name="email" id="email" required value="{{ old('email', $email) }}" placeholder="name@school.edu"
                       class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent text-sm transition">
            </div>

            <div>
                <label for="password" class="block text-xs font-semibold text-slate-700 mb-1">New Password</label>
                <input type="password" name="password" id="password" required placeholder="Minimum 6 characters"
                       class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent text-sm transition">
            </div>

            <div>
                <label for="password_confirmation" class="block text-xs font-semibold text-slate-700 mb-1">Confirm New Password</label>
                <input type="password" name="password_confirmation" id="password_confirmation" required placeholder="Repeat new password"
                       class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent text-sm transition">
            </div>

            <button type="submit" class="w-full py-2.5 px-4 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-sm shadow-md shadow-emerald-600/20 transition">
                Update Password & Continue
            </button>
        </form>

        <div class="mt-6 text-center text-xs text-slate-500">
            Remembered your credentials? 
            <a href="{{ route('login') }}" class="text-emerald-600 hover:underline font-semibold">Back to Sign In</a>
        </div>
    </div>
</div>
@endsection
