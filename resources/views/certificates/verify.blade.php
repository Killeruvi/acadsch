<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate Registry Verification &bull; {{ $school["name"] ?? "PrimeOS Academy" }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-900 text-slate-100 min-h-screen flex flex-col justify-between font-sans p-4 sm:p-8">

    <!-- Header -->
    <header class="max-w-xl mx-auto w-full pt-4 flex items-center justify-between border-b border-slate-800 pb-4">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-500 to-teal-400 flex items-center justify-center text-white font-black text-xl shadow-lg">
                PA
            </div>
            <div>
                <span class="text-base font-extrabold text-white block">{{ $school["name"] ?? "PrimeOS Academy" }}</span>
                <span class="text-[10px] text-emerald-400 font-semibold tracking-wider uppercase">Institutional Credential Registry</span>
            </div>
        </div>
        @if($certificate->revoked_at)
        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-rose-500/15 text-rose-300 border border-rose-500/30">Revoked</span>
        @else
        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-500/15 text-emerald-300 border border-emerald-500/30">Active registry record</span>
        @endif
    </header>

    <!-- Verification Card -->
    <main class="max-w-xl mx-auto w-full my-auto py-8">
        <div class="bg-slate-950/80 rounded-3xl p-6 sm:p-8 border border-slate-800 shadow-2xl backdrop-blur-md space-y-5">
            <div class="text-center space-y-2">
                <div class="w-16 h-16 rounded-full bg-emerald-500/15 border-2 border-emerald-500/30 text-emerald-400 flex items-center justify-center text-2xl mx-auto shadow-inner">
                    ✓
                </div>
                <h1 class="text-xl font-black text-white">Certificate Registry Record</h1>
                <p class="text-xs text-slate-400">
                    This page confirms whether a matching certificate record exists and whether it has been revoked.
                </p>
            </div>

            <!-- Details Table -->
            <div class="p-4 rounded-2xl bg-slate-900/90 border border-slate-800 space-y-2 text-xs">
                <div class="flex items-center justify-between py-1 border-b border-slate-800">
                    <span class="text-slate-400">Certificate Serial:</span>
                    <span class="font-mono font-bold text-amber-400">{{ $certificate->certificate_number }}</span>
                </div>
                <div class="flex items-center justify-between py-1 border-b border-slate-800">
                    <span class="text-slate-400">Awarded To:</span>
                    <span class="font-bold text-white">{{ $certificate->user->name }}</span>
                </div>
                <div class="flex items-center justify-between py-1 border-b border-slate-800">
                    <span class="text-slate-400">Honors Title:</span>
                    <span class="font-extrabold text-emerald-400">{{ $certificate->title }}</span>
                </div>
                <div class="flex items-center justify-between py-1 border-b border-slate-800">
                    <span class="text-slate-400">Date Issued:</span>
                    <span class="font-bold text-slate-300">{{ $certificate->issued_date->format('d F Y') }}</span>
                </div>
                <div class="flex items-center justify-between py-1">
                    <span class="text-slate-400">Issuer:</span>
                    <span class="font-medium text-slate-300">{{ $certificate->issuer?->name ?? 'Issuer record unavailable' }}</span>
                </div>
            </div>

            @if($certificate->revoked_at)
                <div class="rounded-xl border border-rose-500/30 bg-rose-500/10 p-3 text-sm text-rose-200">This certificate was revoked on {{ $certificate->revoked_at->format('d M Y, H:i') }}. It is not valid for use as an active credential.</div>
            @else
                <div class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-3 text-sm text-emerald-200">No revocation is recorded. This confirms registry status, not the identity of the person presenting the certificate.</div>
            @endif
        </div>
    </main>

    <!-- Footer -->
    <footer class="max-w-xl mx-auto w-full pt-4 text-center text-xs text-slate-500 border-t border-slate-800">
        {{ $school["name"] ?? "PrimeOS Academy" }} &bull; Certificate Registry &bull; Aim, Ascend, Achieve
    </footer>

</body>
</html>
