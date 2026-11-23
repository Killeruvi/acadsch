@extends('layouts.public')

@section('title', 'Terms & Conditions — ' . ($school['name'] ?? 'PrimeArc Public School'))
@section('meta_description', 'Official Institutional Terms of Service governing enrollment, academic integrity, portal usage, and faculty authorization. Powered by PrimeOS.')

@section('content')
<section class="py-12 bg-white border-b border-slate-200">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <span class="text-xs font-bold uppercase tracking-wider text-emerald-700 block mb-1">Institutional Terms</span>
        <h1 class="font-display text-2xl sm:text-4xl font-black tracking-tight text-slate-900">
            Terms &amp; Conditions
        </h1>
        <p class="text-xs text-slate-500 mt-1">
            Effective Date: October 2026 &bull; Governing All Students, Guardians, and Faculty Enrolled with {{ $school['name'] ?? 'the Institution' }}.
        </p>
    </div>
</section>

<section class="py-12 bg-slate-50">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8 text-xs sm:text-sm text-slate-600 leading-relaxed">
        
        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-2xs space-y-2">
            <h2 class="text-base font-bold text-slate-900 flex items-center space-x-2">
                <span class="text-emerald-700">1.</span>
                <span>Acceptance of Institutional Terms</span>
            </h2>
            <p>
                By enrolling in {{ $school['name'] ?? 'our school' }}, accessing our web portal, utilizing digital classroom streams, or completing student registration, you agree to be bound by these Terms &amp; Conditions and our Academic Honor Code (Powered by PrimeOS).
            </p>
        </div>

        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-2xs space-y-2">
            <h2 class="text-base font-bold text-slate-900 flex items-center space-x-2">
                <span class="text-teal-700">2.</span>
                <span>Academic Integrity &amp; Student Conduct</span>
            </h2>
            <p>
                {{ $school['name'] ?? 'Our Institution' }} is founded on genuine intellectual growth. Students agree to:
            </p>
            <ul class="list-disc list-inside space-y-1.5 pl-2 text-xs">
                <li>Submit authentic, independently completed homework and Daily Practice Problem (DPP) solutions.</li>
                <li>Refrain from automated scraping, plagiarism, unauthorized answer distribution, or sharing portal credentials.</li>
                <li>Treat fellow students, educators, and institutional staff with dignity, focus, and respect across all digital forums.</li>
            </ul>
        </div>

        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-2xs space-y-2">
            <h2 class="text-base font-bold text-slate-900 flex items-center space-x-2">
                <span class="text-indigo-700">3.</span>
                <span>Intellectual Property &amp; Study Materials</span>
            </h2>
            <p>
                All course syllabi, chapter lecture notes, Bloom's cognitive question banks, custom diagrams, and PWA assets hosted within this platform are proprietary assets. Students are granted a personal, non-transferable license for study purposes. Resale or unauthorized commercial distribution is strictly prohibited.
            </p>
        </div>

        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-2xs space-y-2">
            <h2 class="text-base font-bold text-slate-900 flex items-center space-x-2">
                <span class="text-amber-700">4.</span>
                <span>Faculty Authorization &amp; Device Restrictions</span>
            </h2>
            <p>
                Access to faculty rosters, grading consoles, and question authoring tools is strictly limited to authorized instructors. Student sessions are monitored for safety and integrity across institutional and personal devices.
            </p>
        </div>

    </div>
</section>

@endsection
