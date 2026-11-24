<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $certificate->title }} - Certificate &bull; {{ $school["name"] ?? config('app.name', 'School ERP') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; padding: 0 !important; }
            .parchment-border { border: 8px double #b45309 !important; box-shadow: none !important; }
        }
    </style>
</head>
<body class="bg-slate-100 text-slate-900 min-h-screen p-4 sm:p-8 font-sans flex flex-col justify-between">

    <!-- Top Action Toolbar -->
    <div class="max-w-4xl mx-auto w-full mb-6 flex items-center justify-between no-print bg-white p-4 rounded-2xl shadow-sm border border-slate-200">
        <a href="{{ route('certificates.index') }}" class="text-xs font-bold text-slate-500 hover:text-emerald-600">
            &larr; Return to Credentials
        </a>
        <div class="flex items-center space-x-2">
            <button onclick="window.print()" class="px-5 py-2 bg-amber-600 hover:bg-amber-500 text-white font-bold text-xs rounded-xl shadow-sm flex items-center space-x-1.5">
                <span>🖨️</span> <span>Print Parchment / PDF</span>
            </button>
            <a href="{{ route('certificates.verify', $certificate->verification_hash) }}" target="_blank" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl">
                Verify Online &rarr;
            </a>
        </div>
    </div>

    <!-- Official Parchment Certificate Frame -->
    @if($certificate->revoked_at)
    <div class="max-w-4xl mx-auto w-full mb-4 p-4 rounded-xl border-2 border-rose-600 bg-rose-50 text-rose-900 text-center font-black uppercase tracking-wider">REVOKED — Not a valid active credential ({{ $certificate->revoked_at->format('d M Y') }})</div>
    @endif
    <div class="max-w-4xl mx-auto w-full bg-[#fdfbf7] p-8 sm:p-14 rounded-3xl border-8 border-double border-amber-800/80 shadow-2xl relative overflow-hidden text-center parchment-border">
        
        <!-- Background Watermark / Guilloche Pattern -->
        <div class="absolute inset-0 opacity-[0.03] pointer-events-none flex items-center justify-center font-serif text-[180px] font-black text-amber-900 select-none">
            PA
        </div>

        <!-- Top Emblem & School Crest -->
        <div class="space-y-2 relative z-10">
            <div class="w-16 h-16 mx-auto rounded-2xl bg-amber-700 text-white font-black text-2xl flex items-center justify-center shadow-lg border-2 border-amber-400">
                PA
            </div>
            <h2 class="text-2xl sm:text-3xl font-serif font-black uppercase tracking-widest text-amber-900">
                {{ $school["name"] ?? config('app.name', 'School ERP') }}
            </h2>
            <p class="text-xs font-serif uppercase tracking-widest text-amber-700 font-bold">
                Center for Scholastic Excellence &bull; Aim, Ascend, Achieve
            </p>
            <div class="w-48 h-0.5 bg-gradient-to-r from-transparent via-amber-600 to-transparent mx-auto my-3"></div>
        </div>

        <!-- Certificate Heading -->
        <div class="my-6 space-y-1 relative z-10">
            <span class="text-xs uppercase font-serif font-bold tracking-widest text-slate-500 block">
                This is to officially certify that
            </span>
            <h1 class="text-3xl sm:text-5xl font-serif font-bold text-slate-900 my-3 tracking-wide">
                {{ $certificate->user->name }}
            </h1>
            <span class="text-xs font-mono font-bold text-amber-800 bg-amber-100/70 px-3 py-1 rounded-full border border-amber-300">
                Student ID: {{ $certificate->user->student_id_number ?: 'Not assigned' }}
            </span>
        </div>

        <!-- Citation Body -->
        <div class="max-w-2xl mx-auto my-6 text-xs sm:text-sm font-serif leading-relaxed text-slate-700 relative z-10">
            <p class="mb-2">
                has successfully demonstrated scholastic excellence and fulfilled the institutional standards required for the award of
            </p>
            <h3 class="text-lg sm:text-2xl font-serif font-black text-amber-950 my-2 uppercase tracking-wide">
                {{ $certificate->title }}
            </h3>
            <p class="italic text-slate-600 my-3">
                &ldquo;{{ $certificate->citation }}&rdquo;
            </p>
        </div>

        <!-- Seal & Signatures Grid -->
        <div class="pt-8 mt-6 border-t border-amber-900/20 grid grid-cols-3 items-end text-xs font-serif relative z-10">
            <!-- Left: Registrar Signature -->
            <div class="space-y-1 text-center">
                <div class="font-serif italic text-base text-slate-800 font-bold underline decoration-amber-600">
                    {{ $certificate->issuer?->name ?? 'Authorized Institutional Issuer' }}
                </div>
                <div class="text-[10px] uppercase font-bold text-slate-500">Academic Dean / Evaluator</div>
            </div>

            <!-- Center: Gold Institutional Seal -->
            <div class="text-center">
                <div class="w-20 h-20 mx-auto rounded-full bg-gradient-to-tr from-amber-600 via-yellow-400 to-amber-600 border-4 border-amber-700 flex flex-col items-center justify-center text-white shadow-xl shadow-amber-500/30">
                    <span class="text-base font-black">★ PA ★</span>
                    <span class="text-[8px] uppercase font-black tracking-widest">OFFICIAL SEAL</span>
                </div>
                <span class="text-[9px] font-mono text-slate-400 mt-1 block">Date: {{ $certificate->issued_date->format('d M Y') }}</span>
            </div>

            <!-- Right: institutional record -->
            <div class="space-y-1 text-center">
                <div class="font-serif text-base text-slate-800 font-bold">Institutional Record</div>
                <div class="text-[10px] uppercase font-bold text-slate-500">Verification available online</div>
            </div>
        </div>

        <!-- Verification Footnote & Serial -->
        <div class="mt-8 pt-4 border-t border-amber-900/10 flex flex-col sm:flex-row items-center justify-between text-[10px] font-mono text-slate-400 relative z-10">
            <span>Certificate ID: {{ $certificate->certificate_number }}</span>
            <span>Verification code: {{ substr($certificate->verification_hash, 0, 16) }}...</span>
        </div>

    </div>

    <!-- Footer for page -->
    <div class="text-center text-xs text-slate-400 mt-6 no-print">
        {{ $school["name"] ?? config('app.name', 'School ERP') }} Institutional Management &bull; Certificate record — check online status before relying on it
    </div>

</body>
</html>
