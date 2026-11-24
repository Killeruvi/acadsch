@extends('install.layout')

@section('title', 'Institutional White-Label Branding')

@section('content')
<div>
    <div class="mb-6">
        <h2 class="text-xl sm:text-2xl font-black text-white tracking-tight">3. Institutional White-Label Branding</h2>
        <p class="text-sm text-slate-400 mt-1">
            Configure your school identity, logos, currency, and accreditation details. All pages, reports, ID cards, and receipts adapt dynamically.
        </p>
    </div>

    <form method="POST" action="{{ route('install.branding.post') }}" enctype="multipart/form-data" class="space-y-5">
        @csrf

        <!-- Core School Name & Code -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="sm:col-span-2">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">Official Institution / School Name <span class="text-rose-400">*</span></label>
                <input type="text" name="institute_name" value="{{ old('institute_name', $branding['name'] !== 'School ERP' ? $branding['name'] : '') }}" required class="w-full px-3.5 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-white text-xs focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none" placeholder="e.g. Grandview International Academy">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">Short Code / Acronym</label>
                <input type="text" name="school_short_name" value="{{ old('school_short_name', $branding['short_name'] !== 'ERP' ? $branding['short_name'] : '') }}" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-white text-xs focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none uppercase" placeholder="e.g. GIA">
            </div>
        </div>

        <!-- Tagline / Motto -->
        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">Institutional Motto / Tagline</label>
            <input type="text" name="motto" value="{{ old('motto', $branding['motto']) }}" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-white text-xs focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none" placeholder="e.g. Inspiring Excellence &bull; Nurturing Character">
        </div>

        <!-- Board & Affiliation -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">Affiliation / Educational Board <span class="text-rose-400">*</span></label>
                <select name="board_name" required class="w-full px-3.5 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-white text-xs focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none">
                    @php
                        $selectedBoard = old('board_name', $branding['board_name'] ?? 'General Board');
                        $boards = ['CBSE', 'ICSE / ISC', 'Cambridge (CAIE)', 'International Baccalaureate (IB)', 'State Board', 'Federal / National Board', 'Edexcel', 'General Board'];
                    @endphp
                    @foreach($boards as $b)
                        <option value="{{ $b }}" {{ $selectedBoard === $b ? 'selected' : '' }}>{{ $b }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">Affiliation / Reg Code</label>
                <input type="text" name="affiliation_number" value="{{ old('affiliation_number', $branding['affiliation']) }}" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-white text-xs focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none" placeholder="e.g. REG-2026-9941">
            </div>
        </div>

        <!-- Campus & Principal -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">Campus Name <span class="text-rose-400">*</span></label>
                <input type="text" name="campus_name" value="{{ old('campus_name', $branding['campus'] ?: 'Main Campus') }}" required class="w-full px-3.5 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-white text-xs focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none" placeholder="e.g. Main Campus">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">Principal / Head of Institution</label>
                <input type="text" name="principal_name" value="{{ old('principal_name', $branding['principal'] ?: 'Principal Office') }}" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-white text-xs focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none" placeholder="e.g. Dr. Robert Vance">
            </div>
        </div>

        <!-- Contact Coordinates -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">Official School Email <span class="text-rose-400">*</span></label>
                <input type="email" name="institution_email" value="{{ old('institution_email', $branding['email']) }}" required class="w-full px-3.5 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-white text-xs focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none" placeholder="info@school.edu">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">Official Phone Number</label>
                <input type="text" name="institution_phone" value="{{ old('institution_phone', $branding['phone']) }}" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-white text-xs focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none" placeholder="+1 (555) 019-2834">
            </div>
        </div>

        <!-- Address -->
        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">Campus Physical Address</label>
            <input type="text" name="institution_address" value="{{ old('institution_address', $branding['address']) }}" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-white text-xs focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none" placeholder="e.g. 100 Academy Boulevard, Education District">
        </div>

        <!-- Currency & Theme -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">Currency Symbol <span class="text-rose-400">*</span></label>
                <input type="text" name="currency_symbol" value="{{ old('currency_symbol', $branding['currency_symbol'] ?: '$') }}" required class="w-full px-3.5 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-white text-xs focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none" placeholder="$ or ₹ or € or £">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">Currency Code <span class="text-rose-400">*</span></label>
                <input type="text" name="currency_code" value="{{ old('currency_code', 'USD') }}" required class="w-full px-3.5 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-white text-xs focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none uppercase" placeholder="USD">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">Brand Accent Color <span class="text-rose-400">*</span></label>
                <div class="flex items-center gap-2">
                    <input type="color" name="brand_primary_color" id="brand_color_picker" value="{{ old('brand_primary_color', $branding['primary_color'] ?: '#7c32ff') }}" class="w-10 h-10 rounded-lg cursor-pointer bg-transparent border-0" onchange="document.getElementById('brand_color_text').value = this.value">
                    <input type="text" id="brand_color_text" value="{{ old('brand_primary_color', $branding['primary_color'] ?: '#7c32ff') }}" class="flex-grow px-3 py-2 rounded-xl bg-slate-900 border border-slate-800 text-white text-xs focus:ring-2 focus:ring-purple-500 outline-none" oninput="document.getElementById('brand_color_picker').value = this.value">
                </div>
            </div>
        </div>

        <!-- Logo Upload -->
        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">School Official Crest / Logo</label>
            <div class="p-4 rounded-xl border border-dashed border-slate-800 bg-slate-900/60 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div>
                    <input type="file" name="school_logo" accept="image/*" class="text-xs text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-purple-600 file:text-white hover:file:bg-purple-500 cursor-pointer">
                    <p class="text-[11px] text-slate-500 mt-1">PNG, JPG, SVG, or WEBP (Transparent background recommended, Max 2MB).</p>
                </div>
                @if(!empty($branding['logo_url']))
                    <img src="{{ $branding['logo_url'] }}" alt="Current Logo" class="h-10 w-auto rounded object-contain bg-white/10 p-1">
                @endif
            </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-between pt-6 border-t border-slate-800">
            <a href="{{ route('install.database') }}" class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-400 hover:text-white bg-slate-900 hover:bg-slate-800 transition">
                &larr; Back to Database
            </a>
            <button type="submit" class="px-6 py-2.5 rounded-xl text-xs font-extrabold text-white bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 shadow-lg shadow-purple-500/25 transition">
                Save Branding & Proceed to Admin &rarr;
            </button>
        </div>
    </form>
</div>
@endsection
