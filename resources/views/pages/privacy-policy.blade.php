@extends('layouts.public')

@section('title', 'Privacy Policy — ' . ($school['name'] ?? 'PrimeArc Public School'))
@section('meta_description', 'Official Privacy Policy detailing strict student data protection, parental consent safeguards, and institutional security standards. Powered by PrimeOS.')

@section('content')
<section class="py-12 bg-white border-b border-slate-200">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <span class="text-xs font-bold uppercase tracking-wider text-emerald-700 block mb-1">Legal &amp; Institutional Governance</span>
        <h1 class="font-display text-2xl sm:text-4xl font-black tracking-tight text-slate-900">
            Privacy Policy
        </h1>
        <p class="text-xs text-slate-500 mt-1">
            Last Updated &amp; Effective Date: October 2026 &bull; Aligned with Child Online Protection and Indian Digital Personal Data Protection (DPDP) Standards.
        </p>
    </div>
</section>

<section class="py-12 bg-slate-50">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8 text-xs sm:text-sm text-slate-600 leading-relaxed">
        
        <!-- Commitment -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-2xs space-y-2">
            <h2 class="text-base font-bold text-slate-900 flex items-center space-x-2">
                <span class="text-emerald-700">1.</span>
                <span>Our Unwavering Commitment to Student Data Privacy</span>
            </h2>
            <p>
                {{ $school['name'] ?? 'Our Institution' }} ("we", "our", or "the Institution", powered by PrimeOS) considers the privacy of our students, parents, and faculty paramount. We categorically reject the commercial exploitation of student data. We do not sell, rent, monetize, or broker student academic logs, contact records, or assessment metrics to advertisers or commercial data brokers under any circumstances.
            </p>
        </div>

        <!-- Information Collected -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-2xs space-y-3">
            <h2 class="text-base font-bold text-slate-900 flex items-center space-x-2">
                <span class="text-teal-700">2.</span>
                <span>Information We Collect</span>
            </h2>
            <p>
                To provide structured educational instruction and accurate academic record-keeping, we collect minimal, necessary information:
            </p>
            <ul class="list-disc list-inside space-y-1.5 pl-2 text-xs">
                <li><strong>Student Identity &amp; Enrollment:</strong> Student's full name, assigned academic class (Classes 1–12), allocated section, roll number, and enrolled subjects.</li>
                <li><strong>Contact Verification:</strong> Mandatory permanent guardian phone number and registered email address utilized strictly for institutional alerts, attendance notifications, and two-factor portal security.</li>
                <li><strong>Academic Performance Records:</strong> Homework submissions, Daily Practice Problem (DPP) scores, Bloom's cognitive taxonomy evaluations, and examination grading.</li>
                <li><strong>Portal Logs &amp; Attendance:</strong> Daily digital session check-ins, time-stamped attendance logs, and video lecture completion metrics.</li>
            </ul>
        </div>

        <!-- Protection of Minors -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-2xs space-y-3">
            <h2 class="text-base font-bold text-slate-900 flex items-center space-x-2">
                <span class="text-indigo-700">3.</span>
                <span>Protection of Minors &amp; Parental Consent</span>
            </h2>
            <p>
                Because {{ $school['name'] ?? 'our school' }} serves students from Classes 1 through 12, guardian consent is verified during enrollment. Guardians have the continuous right to review their child's academic records, request corrections, or export assessment performance logs.
            </p>
        </div>

        <!-- Technical Safeguards -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-2xs space-y-3">
            <h2 class="text-base font-bold text-slate-900 flex items-center space-x-2">
                <span class="text-emerald-700">4.</span>
                <span>Technical Safeguards &amp; Data Security</span>
            </h2>
            <p>
                We enforce comprehensive Technical Safeguards, including encryption in transit and at rest, secure authentication, and strict role-based access control to protect student data against unauthorized disclosure.
            </p>
        </div>

    </div>
</section>

@endsection
