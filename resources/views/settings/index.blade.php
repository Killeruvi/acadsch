@extends('layouts.app')

@section('title', 'Institutional Settings & School Branding — PrimeOS')

@section('content')
<div class="max-w-5xl mx-auto space-y-4">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 pb-2 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center space-x-2">
                <h1 class="text-lg sm:text-xl font-black text-slate-900 dark:text-white tracking-tight">
                    Institutional Identity &amp; School Branding
                </h1>
                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                    PrimeOS Enterprise
                </span>
            </div>
            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                Customize your school's unique branding, logos, motto, and credentials — verified with the PrimeOS mark.
            </div>
        </div>
        <div class="flex items-center space-x-2">
            <a href="{{ route('appearance.index') }}" class="primary-btn" style="padding: 0.4rem 1.25rem; font-size: 0.75rem;">
                <i class="ti-palette text-xs"></i>
                <span>Themes &amp; Appearance</span>
            </a>
            <span class="px-2.5 py-1 rounded text-[10px] font-bold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 flex items-center space-x-1">
                <span>⚡</span>
                <span>Powered by PrimeOS</span>
            </span>
        </div>
    </div>

    <!-- Flash Notifications -->
    @if(session('success'))
    <div class="p-3 rounded-lg bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-xs font-bold flex items-center justify-between">
        <div class="flex items-center space-x-2">
            <span>✅</span>
            <span>{{ session('success') }}</span>
        </div>
        <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-800 text-xs">✕</button>
    </div>
    @endif

    @if(session('error'))
    <div class="p-3 rounded-lg bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-300 text-xs font-bold flex items-center justify-between">
        <div class="flex items-center space-x-2">
            <span>⚠️</span>
            <span>{{ session('error') }}</span>
        </div>
        <button onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-800 text-xs">✕</button>
    </div>
    @endif

    @if($errors->any())
    <div class="p-3 rounded-lg bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-300 text-xs">
        <p class="font-bold mb-1">Please correct the following:</p>
        <ul class="list-disc pl-5 space-y-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
    @endif

    <!-- LIVE BRANDING PREVIEW -->
    <div class="bg-gradient-to-br from-slate-900 to-slate-950 rounded-2xl border border-slate-800 p-4 sm:p-5 text-white shadow-xl">
        <div class="flex items-center justify-between pb-3 border-b border-slate-800 mb-4">
            <div class="flex items-center space-x-2">
                <span class="text-sm">👁️</span>
                <span class="text-xs font-extrabold uppercase tracking-wider text-slate-300">Live School Branding &amp; Mark Preview</span>
            </div>
            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                Real-time Rendering
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Preview 1: Top Navigation Bar Look -->
            <div class="bg-slate-900/90 rounded-xl border border-slate-700/60 p-3 space-y-2">
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Top Navbar Header</div>
                <div class="flex items-center justify-between bg-slate-950 px-3 py-2 rounded-lg border border-slate-800">
                    <div class="flex items-center space-x-2">
                        <div class="w-7 h-7 rounded-lg bg-gradient-to-tr from-emerald-500 via-teal-400 to-cyan-500 flex items-center justify-center text-white font-black text-xs shadow-xs">
                            {{ $settings['school_short_name'] ?? '' }}
                        </div>
                        <div>
                            <div class="text-xs font-black text-white leading-tight truncate max-w-[130px]">
                                {{ $settings['institute_name'] ?? 'School name not configured' }}
                            </div>
                            <div class="text-[9px] text-emerald-400 font-bold uppercase tracking-wider">
                                Powered by PrimeOS
                            </div>
                        </div>
                    </div>
                    <span class="text-[9px] bg-slate-800 px-1.5 py-0.5 rounded text-slate-300">Active</span>
                </div>
            </div>

            <!-- Preview 2: Student ID Card Badge Look -->
            <div class="bg-slate-900/90 rounded-xl border border-slate-700/60 p-3 space-y-2">
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Student ID &amp; Hall Pass</div>
                <div class="bg-slate-950 p-2.5 rounded-lg border border-slate-800 space-y-1.5">
                    <div class="flex items-center justify-between text-[11px]">
                        <span class="font-extrabold text-white truncate max-w-[150px]">{{ $settings['institute_name'] ?? 'School name not configured' }}</span>
                        <span class="text-[9px] text-slate-400 font-mono">{{ $settings['affiliation_number'] ?? 'Affiliation not configured' }}</span>
                    </div>
                    <div class="text-[9px] text-slate-400 italic truncate">{{ $settings['motto'] ?? 'Motto not configured' }}</div>
                    <div class="pt-1 border-t border-slate-800/80 flex items-center justify-between text-[9px] text-emerald-400 font-bold">
                        <span>Verified Credential</span>
                        <span>PrimeOS Secured ⚡</span>
                    </div>
                </div>
            </div>

            <!-- Preview 3: Electronic Invoicing & Fee Receipts -->
            <div class="bg-slate-900/90 rounded-xl border border-slate-700/60 p-3 space-y-2">
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Fee Receipt &amp; Report Card</div>
                <div class="bg-slate-950 p-2.5 rounded-lg border border-slate-800 space-y-1 text-[10px]">
                    <div class="font-bold text-white">{{ $settings['institute_name'] ?? 'School name not configured' }}</div>
                    <div class="text-slate-400 text-[9px] truncate">{{ $settings['campus_name'] ?? 'Campus not configured' }} • {{ $settings['academic_session'] ?? 'Session not configured' }}</div>
                    <div class="pt-1 border-t border-slate-800 flex items-center justify-between text-[9px] text-slate-400">
                        <span>UPI: <strong class="text-slate-300 font-mono">{{ $settings['upi_vpa'] ?? 'Not configured' }}</strong></span>
                        <span class="text-emerald-400 font-bold">PrimeOS Ledger</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Institutional Settings Form -->
    <form method="POST" action="{{ route('settings.update') }}" class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-2xs">
        @csrf

        <div class="p-4 sm:p-5 space-y-6">
            
            <!-- Section 1: Institution Identity & Custom Branding -->
            <div>
                <div class="flex items-center space-x-2.5 mb-3 pb-2 border-b border-slate-100 dark:border-slate-800">
                    <span class="w-6 h-6 rounded bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 flex items-center justify-center font-bold text-xs">1</span>
                    <div>
                        <h2 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white">School Identity &amp; Custom Branding</h2>
                        <span class="text-[10px] text-slate-500">Every module, report, ID card, and portal view adapts automatically to these settings.</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    <div class="sm:col-span-2">
                        <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Full School / Academy Name *</label>
                        <input type="text" name="institute_name" required value="{{ old('institute_name', $settings['institute_name'] ?? '') }}"
                               placeholder="e.g. Greenwood Public School or Delhi World Academy"
                               class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 focus:ring-1 focus:ring-emerald-500 text-xs font-bold text-slate-900 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Short Code / Acronym (2-5 letters) *</label>
                        <input type="text" name="school_short_name" maxlength="10" value="{{ old('school_short_name', $settings['school_short_name'] ?? '') }}"
                               placeholder="e.g. MPS, DPS, DWA"
                               class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 focus:ring-1 focus:ring-emerald-500 text-xs font-mono font-bold text-slate-900 dark:text-white uppercase">
                        <span class="text-[10px] text-slate-500 mt-0.5 block">Used in top navigation avatar, mobile drawer, and badges.</span>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Campus / Branch Name *</label>
                        <input type="text" name="campus_name" required value="{{ old('campus_name', $settings['campus_name'] ?? '') }}"
                               placeholder="e.g. North City Campus"
                               class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 focus:ring-1 focus:ring-emerald-500 text-xs font-semibold text-slate-900 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Educational Board</label>
                        <input type="text" name="board_name" value="{{ old('board_name', $settings['board_name'] ?? '') }}"
                               placeholder="e.g. CBSE, ICSE, IB, State Board"
                               class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 focus:ring-1 focus:ring-emerald-500 text-xs font-semibold text-slate-900 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Affiliation / Board Code</label>
                        <input type="text" name="affiliation_number" value="{{ old('affiliation_number', $settings['affiliation_number'] ?? '') }}"
                               placeholder="e.g. CBSE/AFF/2026/089"
                               class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 focus:ring-1 focus:ring-emerald-500 text-xs font-semibold text-slate-900 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Academic Session *</label>
                        @php
                            $activeSession = old('academic_session', $settings['academic_session'] ?? '2026-2027');
                            $sessionOptions = ['2024-2025', '2025-2026', '2026-2027', '2027-2028', '2028-2029'];
                            if (!in_array($activeSession, $sessionOptions)) {
                                array_unshift($sessionOptions, $activeSession);
                            }
                        @endphp
                        <select name="academic_session" required
                                class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 focus:ring-1 focus:ring-emerald-500 text-xs font-semibold text-slate-900 dark:text-white">
                            @foreach($sessionOptions as $opt)
                                <option value="{{ $opt }}" {{ $opt === $activeSession ? 'selected' : '' }}>{{ $opt }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Primary Brand Accent Color</label>
                        <input type="text" name="brand_primary_color" value="{{ old('brand_primary_color', $settings['brand_primary_color'] ?? '#10b981') }}"
                               placeholder="e.g. #10b981 or emerald"
                               class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 focus:ring-1 focus:ring-emerald-500 text-xs font-mono font-bold text-slate-900 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">School Logo URL (Optional)</label>
                        <input type="url" name="school_logo_url" value="{{ old('school_logo_url', $settings['school_logo_url'] ?? '') }}"
                               placeholder="https://example.com/logo.png"
                               class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 focus:ring-1 focus:ring-emerald-500 text-xs text-slate-900 dark:text-white">
                    </div>

                    <div class="sm:col-span-2 lg:col-span-3">
                        <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">School Tagline / Motto</label>
                        <input type="text" name="motto" value="{{ old('motto', $settings['motto'] ?? '') }}"
                               placeholder="e.g. Aim, Ascend, Achieve • Where Passion Meets Precision"
                               class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 focus:ring-1 focus:ring-emerald-500 text-xs font-medium text-slate-900 dark:text-white">
                    </div>
                </div>

                <!-- Platform Mark Banner -->
                <div class="mt-4 p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <div class="flex items-center space-x-2.5">
                        <div class="w-8 h-8 rounded-lg bg-emerald-500/20 border border-emerald-500/30 flex items-center justify-center text-emerald-500 font-black text-sm">
                            ⚡
                        </div>
                        <div>
                            <div class="text-xs font-extrabold text-slate-900 dark:text-white">Company Platform Mark: PrimeOS</div>
                            <div class="text-[10px] text-slate-500 dark:text-slate-400">
                                Powered by PrimeOS — verified platform mark displayed alongside your school branding.
                            </div>
                        </div>
                    </div>
                    <span class="px-2.5 py-1 rounded text-[10px] font-mono font-bold bg-emerald-600 text-white self-start sm:self-auto">
                        PrimeOS Core v2.0
                    </span>
                </div>
            </div>

            <!-- Section 2: Leadership & Official Contact -->
            <div>
                <div class="flex items-center space-x-2.5 mb-3 pb-2 border-b border-slate-100 dark:border-slate-800">
                    <span class="w-6 h-6 rounded bg-indigo-100 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 flex items-center justify-center font-bold text-xs">2</span>
                    <h2 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white">Leadership &amp; Official Communications</h2>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Principal / Head of Institution</label>
                        <input type="text" name="principal_name" value="{{ old('principal_name', $settings['principal_name'] ?? '') }}"
                               class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 focus:ring-1 focus:ring-emerald-500 text-xs font-semibold text-slate-900 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Official Phone</label>
                        <input type="text" name="institution_phone" value="{{ old('institution_phone', $settings['institution_phone'] ?? '') }}"
                               class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 focus:ring-1 focus:ring-emerald-500 text-xs font-semibold text-slate-900 dark:text-white">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Official Email</label>
                        <input type="email" name="institution_email" value="{{ old('institution_email', $settings['institution_email'] ?? '') }}"
                               class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 focus:ring-1 focus:ring-emerald-500 text-xs font-semibold text-slate-900 dark:text-white">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Campus Physical Address</label>
                        <textarea name="institution_address" rows="2"
                                  class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 focus:ring-1 focus:ring-emerald-500 text-xs text-slate-900 dark:text-white">{{ old('institution_address', $settings['institution_address'] ?? '') }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Section 3: Payment Collection Setup (QR + Cash + Bank) -->
            <div id="payment-setup-section" class="pt-2">
                <div class="flex items-center space-x-2.5 mb-3 pb-2 border-b border-slate-100 dark:border-slate-800">
                    <span class="w-6 h-6 rounded bg-teal-100 dark:bg-teal-950 text-teal-700 dark:text-teal-300 flex items-center justify-center font-bold text-xs">3</span>
                    <div>
                        <h2 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white">Payment Collection Setup</h2>
                        <p class="text-[10px] text-slate-500">Upload school QR code for student/parent display. No gateway required — cashier marks payments received manually.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <!-- Currency -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Currency Symbol *</label>
                        <input type="text" name="currency_symbol" required value="{{ old('currency_symbol', $settings['currency_symbol'] ?? '₹') }}" placeholder="₹" class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 focus:ring-1 focus:ring-teal-500 text-sm font-bold text-slate-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Currency Code</label>
                        <input type="text" name="currency_code" value="{{ old('currency_code', $settings['currency_code'] ?? 'INR') }}" placeholder="INR" class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 focus:ring-1 focus:ring-teal-500 text-sm font-mono font-bold text-slate-900 dark:text-white uppercase">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">School UPI ID / VPA</label>
                        <input type="text" name="upi_vpa" value="{{ old('upi_vpa', $settings['upi_vpa'] ?? '') }}" placeholder="schoolname@bank" class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 focus:ring-1 focus:ring-teal-500 text-sm font-mono text-slate-900 dark:text-white">
                        <p class="text-[10px] text-slate-400 mt-0.5">Displayed on receipts for reference only</p>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Auto-Generate Receipt on Payment</label>
                        <label class="flex items-center space-x-2 text-xs font-semibold text-slate-700 dark:text-slate-300 cursor-pointer pt-2">
                            <input type="checkbox" name="auto_receipt_generate" value="1" {{ old('auto_receipt_generate', $settings['auto_receipt_generate'] ?? '1') === '1' ? 'checked' : '' }} class="w-4 h-4 rounded text-teal-600 focus:ring-teal-500">
                            <span>Enabled</span>
                        </label>
                    </div>
                </div>

                <!-- School QR Image Upload -->
                <div class="mt-4 p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900">
                    <h3 class="text-xs font-bold text-slate-800 dark:text-white mb-1">School Payment QR Code</h3>
                    <p class="text-[10px] text-slate-500 mb-3">Upload your school's UPI/bank QR code image. It will be shown on fee invoices for parents to scan and pay. PNG or JPG, max 2MB.</p>
                    <div class="flex flex-col sm:flex-row gap-4 items-start">
                        @if(!empty($qrImageUrl))
                        <div class="p-2 bg-slate-50 dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 text-center shrink-0">
                            <img src="{{ $qrImageUrl }}" alt="Current QR Code" class="w-28 h-28 object-contain rounded-lg mx-auto">
                            <span class="text-[9px] text-slate-500 block mt-1">Current QR</span>
                        </div>
                        @endif
                        <form action="{{ route('settings.qr-image') }}" method="POST" enctype="multipart/form-data" class="flex-1 space-y-2">
                            @csrf
                            <input type="file" name="payment_qr_image" accept="image/png,image/jpeg,image/webp" class="w-full text-xs text-slate-600 dark:text-slate-400 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100 dark:file:bg-teal-950 dark:file:text-teal-300">
                            <button type="submit" class="px-4 py-2 rounded-lg bg-teal-600 hover:bg-teal-700 text-white font-bold text-xs shadow transition">
                                📤 Upload QR Image
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Bank Details -->
                <div class="mt-4 p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900">
                    <h3 class="text-xs font-bold text-slate-800 dark:text-white mb-3">Bank Account Details (for receipts)</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Bank Name</label>
                            <input type="text" name="bank_name" value="{{ old('bank_name', $settings['bank_name'] ?? '') }}" class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white focus:ring-1 focus:ring-teal-500">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Account Number</label>
                            <input type="text" name="bank_account" value="{{ old('bank_account', $settings['bank_account'] ?? $settings['bank_account_number'] ?? '') }}" class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-mono font-bold text-slate-900 dark:text-white focus:ring-1 focus:ring-teal-500">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">IFSC Code</label>
                            <input type="text" name="bank_ifsc" value="{{ old('bank_ifsc', $settings['bank_ifsc'] ?? '') }}" class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-mono font-bold text-slate-900 dark:text-white uppercase focus:ring-1 focus:ring-teal-500">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Account Holder Name</label>
                            <input type="text" name="bank_account_holder" value="{{ old('bank_account_holder', $settings['bank_account_holder'] ?? '') }}" class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white focus:ring-1 focus:ring-teal-500">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Branch Name</label>
                            <input type="text" name="bank_branch" value="{{ old('bank_branch', $settings['bank_branch'] ?? '') }}" class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white focus:ring-1 focus:ring-teal-500">
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION 4: SCHOOL LEGAL IDENTITY -->
            <div>
                <div class="flex items-center space-x-2.5 mb-3 pb-2 border-b border-slate-100 dark:border-slate-800">
                    <span class="w-6 h-6 rounded bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-300 flex items-center justify-center font-bold text-xs">4</span>
                    <div>
                        <h2 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white">School Legal Identity</h2>
                        <p class="text-[10px] text-slate-500">Registered legal entity name, PAN, and affiliation details for official documents and receipts.</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    <div class="sm:col-span-2">
                        <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Registered Legal Entity Name</label>
                        <input type="text" name="school_legal_entity_name" value="{{ old('school_legal_entity_name', $settings['school_legal_entity_name'] ?? '') }}" maxlength="255" placeholder="Name of society, trust, company or proprietor" class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">PAN (Optional)</label>
                        <input type="text" name="school_pan" value="{{ old('school_pan', $settings['school_pan'] ?? '') }}" maxlength="10" placeholder="ABCDE1234F" class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs uppercase font-mono text-slate-900 dark:text-white">
                    </div>
                </div>
            </div>

            <!-- SECTION 5: ATTENDANCE TIMETABLE RULES -->
            <div>
                <div class="flex items-center space-x-2.5 mb-3 pb-2 border-b border-slate-100 dark:border-slate-800">
                    <span class="w-6 h-6 rounded bg-indigo-100 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 flex items-center justify-center font-bold text-xs">5</span>
                    <h2 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white">Attendance Timetable Rules</h2>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Period Grace Window (Minutes)</label>
                        <input type="number" name="attendance_period_grace_minutes" value="{{ old('attendance_period_grace_minutes', $settings['attendance_period_grace_minutes'] ?? '15') }}" min="0" max="120"
                               class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 focus:ring-1 focus:ring-indigo-500 text-xs font-mono text-slate-900 dark:text-white">
                        <p class="text-[10px] text-slate-400 mt-1">Tolerance window before and after assigned timetable period during which teachers can mark attendance.</p>
                    </div>
                </div>
            </div>

        </div>

        <div class="bg-slate-50 dark:bg-slate-800/50 p-4 flex items-center justify-between border-t border-slate-200 dark:border-slate-800">
            <div class="text-[11px] text-slate-500 flex items-center space-x-1.5">
                <span>🔒</span>
                <span>School branding and settings apply instantly across all portals.</span>
            </div>
            <button type="submit" class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-md shadow-emerald-600/20 transition">
                Save School Branding &amp; Settings
            </button>
        </div>
    </form>

    <!-- SCHOOL COMPLIANCE DOCUMENT REGISTER -->
    <section class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
        <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div>
                <h2 class="text-sm font-bold text-slate-900 dark:text-white">School Compliance Documents</h2>
                <p class="text-[10px] text-slate-500">Keep recognition, affiliation, fire-safety and other school documents together. Files are private and admin-only.</p>
            </div>
            <div class="flex gap-2 text-[10px] font-bold">
                <span class="rounded-lg px-2 py-1 bg-rose-50 text-rose-700 dark:bg-rose-950 dark:text-rose-300">{{ $complianceSummary['expired'] ?? 0 }} expired</span>
                <span class="rounded-lg px-2 py-1 bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-300">{{ $complianceSummary['expiring'] ?? 0 }} due within 60 days</span>
            </div>
        </div>
        <form method="POST" action="{{ route('school-compliance-documents.store') }}" enctype="multipart/form-data" class="p-4 border-b border-slate-100 dark:border-slate-800 space-y-3">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <div>
                    <label class="block text-[10px] font-bold mb-1 text-slate-600 dark:text-slate-400">Document Type *</label>
                    <select name="category" required class="w-full rounded-lg border border-slate-300 dark:border-slate-700 dark:bg-slate-800 p-2 text-xs">
                        @foreach(['recognition'=>'School recognition','board_affiliation'=>'Board affiliation','fire_safety'=>'Fire safety','building_safety'=>'Building safety / stability','land_or_lease'=>'Land / lease','society_or_trust'=>'Society / trust registration','sanitation'=>'Sanitation / health','other'=>'Other'] as $value=>$label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-bold mb-1 text-slate-600 dark:text-slate-400">Document Title *</label>
                    <input name="title" required maxlength="180" placeholder="e.g. Fire NOC" class="w-full rounded-lg border border-slate-300 dark:border-slate-700 dark:bg-slate-800 p-2 text-xs">
                </div>
                <div>
                    <label class="block text-[10px] font-bold mb-1 text-slate-600 dark:text-slate-400">Document / Certificate No.</label>
                    <input name="document_number" maxlength="120" class="w-full rounded-lg border border-slate-300 dark:border-slate-700 dark:bg-slate-800 p-2 text-xs">
                </div>
                <div>
                    <label class="block text-[10px] font-bold mb-1 text-slate-600 dark:text-slate-400">Issuing Authority</label>
                    <input name="issuing_authority" maxlength="180" class="w-full rounded-lg border border-slate-300 dark:border-slate-700 dark:bg-slate-800 p-2 text-xs">
                </div>
                <div>
                    <label class="block text-[10px] font-bold mb-1 text-slate-600 dark:text-slate-400">Issue Date</label>
                    <input type="date" name="issued_on" class="w-full rounded-lg border border-slate-300 dark:border-slate-700 dark:bg-slate-800 p-2 text-xs">
                </div>
                <div>
                    <label class="block text-[10px] font-bold mb-1 text-slate-600 dark:text-slate-400">Expiry Date</label>
                    <input type="date" name="expires_on" class="w-full rounded-lg border border-slate-300 dark:border-slate-700 dark:bg-slate-800 p-2 text-xs">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-[10px] font-bold mb-1 text-slate-600 dark:text-slate-400">Upload PDF / JPG / PNG (max 10 MB)</label>
                    <input type="file" name="document_file" accept=".pdf,.jpg,.jpeg,.png" class="w-full text-xs text-slate-600 dark:text-slate-300">
                </div>
            </div>
            <div class="flex flex-col sm:flex-row gap-2">
                <input name="notes" maxlength="3000" placeholder="Notes (optional)" class="flex-1 rounded-lg border border-slate-300 dark:border-slate-700 dark:bg-slate-800 p-2 text-xs">
                <button class="rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs px-4 py-2">Add Document</button>
            </div>
        </form>

        <div class="divide-y divide-slate-100 dark:divide-slate-800">
            @forelse($complianceDocuments as $document)
                <div class="p-4 flex flex-col lg:flex-row lg:items-center gap-3 justify-between">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-xs font-bold text-slate-900 dark:text-white">{{ $document->title }}</span>
                            @php($status = $document->expiry_status)
                            <span class="rounded px-2 py-0.5 text-[9px] font-bold {{ $status === 'expired' ? 'bg-rose-100 text-rose-700' : ($status === 'expiring' ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800') }}">{{ $status === 'no_expiry' ? 'No expiry recorded' : ($status === 'expired' ? 'Expired' : ($status === 'expiring' ? 'Expires soon' : 'Current')) }}</span>
                        </div>
                        <p class="text-[10px] text-slate-500 mt-1">{{ str_replace('_', ' ', ucfirst($document->category)) }} · {{ $document->document_number ?: 'No number recorded' }} · Issued by {{ $document->issuing_authority ?: 'Not recorded' }}</p>
                        <p class="text-[10px] text-slate-500">Issue: {{ $document->issued_on?->format('d M Y') ?? '—' }} · Expiry: {{ $document->expires_on?->format('d M Y') ?? '—' }}</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        @if($document->file_path)
                            <a href="{{ route('school-compliance-documents.download', $document) }}" class="text-[10px] font-bold text-indigo-600 hover:underline">Download file</a>
                        @endif
                        <details class="text-xs">
                            <summary class="cursor-pointer text-indigo-600 font-bold">Edit</summary>
                            <form method="POST" action="{{ route('school-compliance-documents.update', $document) }}" enctype="multipart/form-data" class="mt-2 p-3 rounded-lg bg-slate-50 dark:bg-slate-800 space-y-2 min-w-[260px]">
                                @csrf @method('PUT')
                                <input name="title" required maxlength="180" value="{{ $document->title }}" class="w-full rounded border p-2 text-xs dark:bg-slate-900">
                                <input name="document_number" maxlength="120" value="{{ $document->document_number }}" placeholder="Document number" class="w-full rounded border p-2 text-xs dark:bg-slate-900">
                                <input name="issuing_authority" maxlength="180" value="{{ $document->issuing_authority }}" placeholder="Issuing authority" class="w-full rounded border p-2 text-xs dark:bg-slate-900">
                                <input type="date" name="issued_on" value="{{ $document->issued_on?->format('Y-m-d') }}" class="w-full rounded border p-2 text-xs dark:bg-slate-900">
                                <input type="date" name="expires_on" value="{{ $document->expires_on?->format('Y-m-d') }}" class="w-full rounded border p-2 text-xs dark:bg-slate-900">
                                <input type="file" name="document_file" accept=".pdf,.jpg,.jpeg,.png" class="w-full text-xs">
                                <textarea name="notes" rows="2" maxlength="3000" placeholder="Notes" class="w-full rounded border p-2 text-xs dark:bg-slate-900">{{ $document->notes }}</textarea>
                                <input type="hidden" name="category" value="{{ $document->category }}">
                                <button class="rounded bg-indigo-600 text-white font-bold px-3 py-1.5 text-xs">Save changes</button>
                            </form>
                        </details>
                        <form method="POST" action="{{ route('school-compliance-documents.destroy', $document) }}" onsubmit="return confirm('Delete this compliance document and its uploaded file?')">
                            @csrf @method('DELETE')
                            <button class="text-[10px] font-bold text-rose-600">Delete</button>
                        </form>
                    </div>
                </div>
            @empty
                <p class="p-5 text-center text-xs text-slate-500">No school compliance documents recorded yet.</p>
            @endforelse
        </div>
    </section>

</div>

<script>
    (function () {
</script>
@endsection
