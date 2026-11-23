@extends('layouts.app')

@section('title', 'Themes & Appearance System — Infix Style')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    <!-- Header Section with Infix styling -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center space-x-2">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white shadow-md" style="background: var(--system_primary_gradient);">
                    <i class="ti-palette text-sm"></i>
                </div>
                <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight font-heading">
                    Themes &amp; Appearance Management
                </h1>
                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold text-white shadow-xs" style="background: var(--system_primary_gradient);">
                    Infix Design System
                </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Manage system visual themes, customize color schemes, typography, and preloader animations across the entire platform.
            </p>
        </div>

        <div class="flex items-center space-x-2">
            <span class="px-3 py-1.5 rounded-full text-xs font-semibold bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-xs flex items-center space-x-2">
                <span class="w-2.5 h-2.5 rounded-full animate-ping" style="background-color: var(--system_primery_color);"></span>
                <span class="text-slate-700 dark:text-slate-300">Active: <strong>{{ $appearance['theme']['name'] ?? 'Infix Classic' }}</strong></span>
            </span>
        </div>
    </div>

    <!-- Alert Messages -->
    @if(session('success'))
    <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-xs font-semibold flex items-center justify-between shadow-xs">
        <div class="flex items-center space-x-2.5">
            <i class="ti-check-box text-base"></i>
            <span>{{ session('success') }}</span>
        </div>
        <button onclick="this.parentElement.remove()" class="text-emerald-600 hover:text-emerald-900 text-sm">✕</button>
    </div>
    @endif

    @if(session('error'))
    <div class="p-4 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-300 text-xs font-semibold flex items-center justify-between shadow-xs">
        <div class="flex items-center space-x-2.5">
            <i class="ti-alert text-base"></i>
            <span>{{ session('error') }}</span>
        </div>
        <button onclick="this.parentElement.remove()" class="text-rose-600 hover:text-rose-900 text-sm">✕</button>
    </div>
    @endif

    <!-- LIVE THEME PREVIEW BANNER -->
    <div class="rounded-2xl p-5 text-white shadow-xl relative overflow-hidden" style="background: var(--system_primary_gradient);">
        <div class="absolute -right-10 -bottom-10 opacity-15 pointer-events-none text-9xl">
            <i class="ti-layout-grid2"></i>
        </div>
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center space-x-2 mb-1">
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-white/20 uppercase tracking-widest backdrop-blur-xs">Live System Preview</span>
                    <span class="text-xs opacity-80">{{ $appearance['theme']['version'] ?? '2.5.0' }}</span>
                </div>
                <h2 class="text-xl sm:text-2xl font-bold font-heading">{{ $appearance['theme']['title'] ?? 'Infix LMS System' }}</h2>
                <p class="text-xs opacity-90 max-w-2xl mt-1">{{ $appearance['theme']['description'] ?? '' }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <div class="px-3 py-2 rounded-xl bg-white/10 backdrop-blur-md border border-white/20 text-center">
                    <div class="text-[10px] uppercase tracking-wider opacity-75">Primary Color</div>
                    <div class="text-xs font-mono font-bold mt-0.5 flex items-center justify-center gap-1.5">
                        <span class="w-3 h-3 rounded-full border border-white/40" style="background-color: var(--system_primery_color);"></span>
                        {{ $appearance['theme']['primary_color'] }}
                    </div>
                </div>
                <div class="px-3 py-2 rounded-xl bg-white/10 backdrop-blur-md border border-white/20 text-center">
                    <div class="text-[10px] uppercase tracking-wider opacity-75">Secondary Color</div>
                    <div class="text-xs font-mono font-bold mt-0.5 flex items-center justify-center gap-1.5">
                        <span class="w-3 h-3 rounded-full border border-white/40" style="background-color: var(--system_secendory_color);"></span>
                        {{ $appearance['theme']['secondary_color'] }}
                    </div>
                </div>
                <div class="px-3 py-2 rounded-xl bg-white/10 backdrop-blur-md border border-white/20 text-center">
                    <div class="text-[10px] uppercase tracking-wider opacity-75">Typography</div>
                    <div class="text-xs font-bold mt-0.5">{{ $appearance['fonts']['family1'] }} / {{ $appearance['fonts']['family2'] }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- TABS NAVIGATION -->
    <div x-data="{ tab: 'themes' }" class="space-y-6">
        <div class="flex items-center space-x-2 border-b border-slate-200 dark:border-slate-800 pb-2">
            <button @click="tab = 'themes'" :class="tab === 'themes' ? 'infix-nav-active' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 bg-white dark:bg-slate-800'" class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center space-x-2 shadow-xs">
                <i class="ti-gallery"></i>
                <span>Installed Themes ({{ count($availableThemes) }})</span>
            </button>
            <button @click="tab = 'customize'" :class="tab === 'customize' ? 'infix-nav-active' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 bg-white dark:bg-slate-800'" class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center space-x-2 shadow-xs">
                <i class="ti-brush-alt"></i>
                <span>Theme Customizer &amp; Colors</span>
            </button>
            <button @click="tab = 'fonts'" :class="tab === 'fonts' ? 'infix-nav-active' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 bg-white dark:bg-slate-800'" class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center space-x-2 shadow-xs">
                <i class="ti-text"></i>
                <span>Typography &amp; Google Fonts</span>
            </button>
            <button @click="tab = 'preloader'" :class="tab === 'preloader' ? 'infix-nav-active' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 bg-white dark:bg-slate-800'" class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center space-x-2 shadow-xs">
                <i class="ti-reload"></i>
                <span>Preloader Settings</span>
            </button>
        </div>

        <!-- TAB 1: THEMES GALLERY -->
        <div x-show="tab === 'themes'" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                @foreach($availableThemes as $key => $thm)
                @php
                    $isActive = ($appearance['active_key'] ?? '') === $key;
                @endphp
                <div class="white-box flex flex-col justify-between overflow-hidden relative group {{ $isActive ? 'ring-2' : '' }}" style="{{ $isActive ? 'border-color: var(--system_primery_color);' : '' }}">
                    @if($isActive)
                    <div class="absolute top-3 right-3 z-20">
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold text-white shadow-md flex items-center space-x-1" style="background: var(--system_primary_gradient);">
                            <i class="ti-check text-[9px]"></i>
                            <span>Active Theme</span>
                        </span>
                    </div>
                    @else
                    <div class="absolute top-3 right-3 z-20">
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-900/70 text-white backdrop-blur-xs">
                            {{ $thm['badge'] ?? 'Theme' }}
                        </span>
                    </div>
                    @endif

                    <div>
                        <!-- Theme Screenshot & Gradient Header -->
                        <div class="h-36 rounded-xl relative overflow-hidden flex items-center justify-center p-4 text-white shadow-xs" style="background: {{ $thm['gradient'] }};">
                            <div class="text-center">
                                <div class="w-12 h-12 mx-auto rounded-full bg-white/20 backdrop-blur-md flex items-center justify-center text-xl mb-2">
                                    <i class="ti-layout-tab-window"></i>
                                </div>
                                <h3 class="text-base font-bold font-heading">{{ $thm['name'] }}</h3>
                                <div class="text-[10px] opacity-80">{{ $thm['title'] }}</div>
                            </div>
                        </div>

                        <!-- Theme Meta Details -->
                        <div class="mt-4 space-y-2">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-bold text-slate-800 dark:text-slate-200">{{ $thm['name'] }}</span>
                                <span class="text-[11px] text-slate-400">v{{ $thm['version'] }}</span>
                            </div>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 line-clamp-2 leading-relaxed">
                                {{ $thm['description'] }}
                            </p>

                            <!-- Color Swatches -->
                            <div class="pt-2 flex items-center space-x-2">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Palette:</span>
                                <div class="flex items-center space-x-1.5">
                                    <span class="w-4 h-4 rounded-full border border-slate-200 dark:border-slate-700 shadow-xs" title="Primary: {{ $thm['primary_color'] }}" style="background-color: {{ $thm['primary_color'] }};"></span>
                                    <span class="w-4 h-4 rounded-full border border-slate-200 dark:border-slate-700 shadow-xs" title="Secondary: {{ $thm['secondary_color'] }}" style="background-color: {{ $thm['secondary_color'] }};"></span>
                                    <span class="w-4 h-4 rounded-full border border-slate-200 dark:border-slate-700 shadow-xs" title="Accent: {{ $thm['accent_color'] }}" style="background-color: {{ $thm['accent_color'] }};"></span>
                                    <span class="w-4 h-4 rounded-full border border-slate-200 dark:border-slate-700 shadow-xs" title="Footer: {{ $thm['footer_bg'] }}" style="background-color: {{ $thm['footer_bg'] }};"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Activate or Status Button -->
                    <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                        <span class="text-[10px] text-slate-400">Author: {{ $thm['author'] }}</span>
                        @if($isActive)
                            <button type="button" disabled class="px-4 py-2 rounded-full text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-400 cursor-default">
                                Current Active
                            </button>
                        @else
                            <form action="{{ route('appearance.activate') }}" method="POST">
                                @csrf
                                <input type="hidden" name="theme_id" value="{{ $key }}">
                                <button type="submit" class="primary-btn" style="padding: 0.4rem 1.25rem; font-size: 0.75rem;">
                                    <i class="ti-check"></i>
                                    <span>Activate Theme</span>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- TAB 2: THEME CUSTOMIZER & COLORS -->
        <div x-show="tab === 'customize'" class="space-y-6">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Customizer Form -->
                <div class="lg:col-span-2 white-box space-y-5">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider flex items-center space-x-2">
                            <i class="ti-paint-bucket text-theme-primary"></i>
                            <span>Customize System Color Palette</span>
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                            Adjust colors individually or pick an Infix color preset to re-theme the entire application.
                        </p>
                    </div>

                    <!-- Universal Light / Dark Mode Toggle Selector -->
                    <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/70">
                        <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-2">
                            Universal Color Mode
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <button type="button" 
                                    onclick="if(document.documentElement.classList.contains('dark')) toggleThemeMode();" 
                                    class="p-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 transition text-left flex items-center justify-between hover:border-amber-400">
                                <div class="flex items-center space-x-2.5">
                                    <span class="w-8 h-8 rounded-lg bg-amber-100 text-amber-600 flex items-center justify-center font-bold">
                                        <i class="ti-sun text-base"></i>
                                    </span>
                                    <div>
                                        <div class="text-xs font-bold text-slate-800 dark:text-slate-100">Light Mode</div>
                                        <div class="text-[10px] text-slate-400">Crisp white canvas &amp; clean contrast</div>
                                    </div>
                                </div>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 dark:hidden">Active</span>
                            </button>

                            <button type="button" 
                                    onclick="if(!document.documentElement.classList.contains('dark')) toggleThemeMode();" 
                                    class="p-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 transition text-left flex items-center justify-between hover:border-purple-500">
                                <div class="flex items-center space-x-2.5">
                                    <span class="w-8 h-8 rounded-lg bg-purple-950/80 text-purple-400 flex items-center justify-center font-bold">
                                        <i class="ti-moon text-base"></i>
                                    </span>
                                    <div>
                                        <div class="text-xs font-bold text-slate-800 dark:text-slate-100">Dark Mode</div>
                                        <div class="text-[10px] text-slate-400">Deep OLED slate &amp; low eye-strain</div>
                                    </div>
                                </div>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-purple-900 text-purple-200 hidden dark:inline-block">Active</span>
                            </button>
                        </div>
                    </div>

                    <!-- Infix Quick Presets -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-2">Quick Palette Presets</label>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                            <button type="button" onclick="applyPreset('#7c32ff', '#202E3B', '#c738d8', 'linear-gradient(135deg, #7c32ff 0%, #c738d8 100%)', '#f6f7fb', '#1E2147', '#828bb2')" class="p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 hover:border-purple-400 transition text-left flex items-center space-x-2">
                                <span class="w-4 h-4 rounded-full shadow-xs" style="background: linear-gradient(135deg, #7c32ff, #c738d8);"></span>
                                <span class="text-xs font-bold text-slate-700 dark:text-slate-200">Infix Purple</span>
                            </button>
                            <button type="button" onclick="applyPreset('#FB1159', '#202E3B', '#FF5A36', 'linear-gradient(135deg, #FB1159 0%, #FF5A36 100%)', '#f8f9fa', '#1E2147', '#828bb2')" class="p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 hover:border-pink-400 transition text-left flex items-center space-x-2">
                                <span class="w-4 h-4 rounded-full shadow-xs" style="background: linear-gradient(135deg, #FB1159, #FF5A36);"></span>
                                <span class="text-xs font-bold text-slate-700 dark:text-slate-200">Infix Vibrant</span>
                            </button>
                            <button type="button" onclick="applyPreset('#0066FF', '#1E293B', '#00B4D8', 'linear-gradient(135deg, #0066FF 0%, #00B4D8 100%)', '#f4f7fe', '#0A1128', '#94A3B8')" class="p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 hover:border-blue-400 transition text-left flex items-center space-x-2">
                                <span class="w-4 h-4 rounded-full shadow-xs" style="background: linear-gradient(135deg, #0066FF, #00B4D8);"></span>
                                <span class="text-xs font-bold text-slate-700 dark:text-slate-200">Edume Blue</span>
                            </button>
                            <button type="button" onclick="applyPreset('#00C895', '#202E3B', '#02B789', 'linear-gradient(135deg, #00C895 0%, #009670 100%)', '#f0fdf4', '#1E2147', '#5B5C6E')" class="p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 hover:border-emerald-400 transition text-left flex items-center space-x-2">
                                <span class="w-4 h-4 rounded-full shadow-xs" style="background: linear-gradient(135deg, #00C895, #009670);"></span>
                                <span class="text-xs font-bold text-slate-700 dark:text-slate-200">WeTech Emerald</span>
                            </button>
                            <button type="button" onclick="applyPreset('#6366F1', '#0F172A', '#8B5CF6', 'linear-gradient(135deg, #6366F1 0%, #8B5CF6 100%)', '#F5F3FF', '#0F172A', '#94A3B8')" class="p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 hover:border-indigo-400 transition text-left flex items-center space-x-2">
                                <span class="w-4 h-4 rounded-full shadow-xs" style="background: linear-gradient(135deg, #6366F1, #8B5CF6);"></span>
                                <span class="text-xs font-bold text-slate-700 dark:text-slate-200">Royal Indigo</span>
                            </button>
                            <button type="button" onclick="applyPreset('#818CF8', '#1E293B', '#A855F7', 'linear-gradient(135deg, #6366F1 0%, #A855F7 100%)', '#0B0F19', '#060911', '#64748B')" class="p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 hover:border-purple-400 transition text-left flex items-center space-x-2">
                                <span class="w-4 h-4 rounded-full shadow-xs" style="background: linear-gradient(135deg, #6366F1, #A855F7);"></span>
                                <span class="text-xs font-bold text-slate-700 dark:text-slate-200">Dark Sapphire</span>
                            </button>
                        </div>
                    </div>

                    <form action="{{ route('appearance.customize') }}" method="POST" id="customizerForm" class="space-y-4">
                        @csrf
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Primary System Color</label>
                                <div class="flex items-center space-x-2">
                                    <input type="color" id="primaryColorPicker" value="{{ $appearance['theme']['primary_color'] }}" onchange="document.getElementById('primaryColorText').value = this.value; updateLiveSwatch();" class="w-10 h-10 rounded-lg cursor-pointer border border-slate-300 dark:border-slate-700 p-0.5">
                                    <input type="text" name="primary_color" id="primaryColorText" value="{{ $appearance['theme']['primary_color'] }}" oninput="document.getElementById('primaryColorPicker').value = this.value; updateLiveSwatch();" class="primary_input_field flex-1 font-mono text-xs uppercase font-bold">
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Secondary Navy Color</label>
                                <div class="flex items-center space-x-2">
                                    <input type="color" id="secColorPicker" value="{{ $appearance['theme']['secondary_color'] }}" onchange="document.getElementById('secColorText').value = this.value; updateLiveSwatch();" class="w-10 h-10 rounded-lg cursor-pointer border border-slate-300 dark:border-slate-700 p-0.5">
                                    <input type="text" name="secondary_color" id="secColorText" value="{{ $appearance['theme']['secondary_color'] }}" oninput="document.getElementById('secColorPicker').value = this.value; updateLiveSwatch();" class="primary_input_field flex-1 font-mono text-xs uppercase font-bold">
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Accent Color (Gradient End)</label>
                                <div class="flex items-center space-x-2">
                                    <input type="color" id="accentColorPicker" value="{{ $appearance['theme']['accent_color'] ?? '#c738d8' }}" onchange="document.getElementById('accentColorText').value = this.value; updateLiveSwatch();" class="w-10 h-10 rounded-lg cursor-pointer border border-slate-300 dark:border-slate-700 p-0.5">
                                    <input type="text" name="accent_color" id="accentColorText" value="{{ $appearance['theme']['accent_color'] ?? '#c738d8' }}" oninput="document.getElementById('accentColorPicker').value = this.value; updateLiveSwatch();" class="primary_input_field flex-1 font-mono text-xs uppercase font-bold">
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">App Light Background</label>
                                <div class="flex items-center space-x-2">
                                    <input type="color" id="bgColorPicker" value="{{ $appearance['theme']['bg_color'] ?? '#f6f7fb' }}" onchange="document.getElementById('bgColorText').value = this.value;" class="w-10 h-10 rounded-lg cursor-pointer border border-slate-300 dark:border-slate-700 p-0.5">
                                    <input type="text" name="bg_color" id="bgColorText" value="{{ $appearance['theme']['bg_color'] ?? '#f6f7fb' }}" oninput="document.getElementById('bgColorPicker').value = this.value;" class="primary_input_field flex-1 font-mono text-xs uppercase font-bold">
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Footer Background Color</label>
                                <div class="flex items-center space-x-2">
                                    <input type="color" id="footerBgPicker" value="{{ $appearance['theme']['footer_bg'] ?? '#1E2147' }}" onchange="document.getElementById('footerBgText').value = this.value;" class="w-10 h-10 rounded-lg cursor-pointer border border-slate-300 dark:border-slate-700 p-0.5">
                                    <input type="text" name="footer_bg" id="footerBgText" value="{{ $appearance['theme']['footer_bg'] ?? '#1E2147' }}" oninput="document.getElementById('footerBgPicker').value = this.value;" class="primary_input_field flex-1 font-mono text-xs uppercase font-bold">
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Footer Muted Text Color</label>
                                <div class="flex items-center space-x-2">
                                    <input type="color" id="footerTextPicker" value="{{ $appearance['theme']['footer_text'] ?? '#828bb2' }}" onchange="document.getElementById('footerTextText').value = this.value;" class="w-10 h-10 rounded-lg cursor-pointer border border-slate-300 dark:border-slate-700 p-0.5">
                                    <input type="text" name="footer_text" id="footerTextText" value="{{ $appearance['theme']['footer_text'] ?? '#828bb2' }}" oninput="document.getElementById('footerTextPicker').value = this.value;" class="primary_input_field flex-1 font-mono text-xs uppercase font-bold">
                                </div>
                            </div>
                        </div>

                        <input type="hidden" name="gradient" id="customGradient" value="{{ $appearance['theme']['gradient'] }}">

                        <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex justify-end">
                            <button type="submit" class="primary-btn">
                                <i class="ti-save"></i>
                                <span>Save Appearance Colors</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Live Components Card Preview -->
                <div class="white-box space-y-4">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider flex items-center space-x-2">
                        <i class="ti-eye text-theme-primary"></i>
                        <span>Live Component Preview</span>
                    </h3>
                    <p class="text-xs text-slate-500">How UI components render in this theme:</p>

                    <!-- Infix Button Previews -->
                    <div class="space-y-2">
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Button Variations</div>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" class="primary-btn" id="previewPrimaryBtn">
                                <i class="ti-bolt"></i> Primary Pill
                            </button>
                            <button type="button" class="primary-btn outline" id="previewOutlineBtn">
                                Outline Button
                            </button>
                        </div>
                    </div>

                    <!-- Infix Badge Previews -->
                    <div class="space-y-2 pt-2">
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Theme Badges &amp; Icons</div>
                        <div class="flex items-center space-x-2">
                            <span class="infix-nav-icon" id="previewIconBox">
                                <i class="ti-cup"></i>
                            </span>
                            <span class="px-3 py-1 rounded-full text-xs font-bold text-white shadow-xs" id="previewBadge" style="background: var(--system_primary_gradient);">
                                Active Status
                            </span>
                        </div>
                    </div>

                    <!-- Infix Mini Card Preview -->
                    <div class="space-y-2 pt-2">
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Card Elevation</div>
                        <div class="p-3.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-sm space-y-1.5">
                            <div class="text-xs font-bold text-slate-800 dark:text-slate-200">School Course Card</div>
                            <div class="text-[11px] text-slate-500">Student analytics and grade tracker.</div>
                            <div class="h-1.5 w-full bg-slate-100 rounded-full overflow-hidden mt-2">
                                <div class="h-full rounded-full" id="previewProgressBar" style="width: 75%; background: var(--system_primary_gradient);"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 3: TYPOGRAPHY & GOOGLE FONTS -->
        <div x-show="tab === 'fonts'" class="space-y-4">
            <div class="max-w-2xl white-box space-y-5">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider flex items-center space-x-2">
                        <i class="ti-text text-theme-primary"></i>
                        <span>System Typography &amp; Google Fonts</span>
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        Select Google Font pairings for body content and headings. Fonts load automatically across the system.
                    </p>
                </div>

                <form action="{{ route('appearance.fonts') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Primary Body Font (Family 1)</label>
                        <select name="family1" class="primary_input_field w-full">
                            <option value="Poppins" {{ ($appearance['fonts']['family1'] ?? '') === 'Poppins' ? 'selected' : '' }}>Poppins (Infix Signature Body Font)</option>
                            <option value="Source Sans Pro" {{ ($appearance['fonts']['family1'] ?? '') === 'Source Sans Pro' ? 'selected' : '' }}>Source Sans Pro (Infix Alternate)</option>
                            <option value="Inter" {{ ($appearance['fonts']['family1'] ?? '') === 'Inter' ? 'selected' : '' }}>Inter (Clean Modern UI)</option>
                            <option value="Plus Jakarta Sans" {{ ($appearance['fonts']['family1'] ?? '') === 'Plus Jakarta Sans' ? 'selected' : '' }}>Plus Jakarta Sans</option>
                            <option value="Roboto" {{ ($appearance['fonts']['family1'] ?? '') === 'Roboto' ? 'selected' : '' }}>Roboto</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Headings Font (Family 2)</label>
                        <select name="family2" class="primary_input_field w-full">
                            <option value="Montserrat" {{ ($appearance['fonts']['family2'] ?? '') === 'Montserrat' ? 'selected' : '' }}>Montserrat (Infix Headings Bold Font)</option>
                            <option value="Jost" {{ ($appearance['fonts']['family2'] ?? '') === 'Jost' ? 'selected' : '' }}>Jost (Infix LMS Sans-Serif)</option>
                            <option value="Poppins" {{ ($appearance['fonts']['family2'] ?? '') === 'Poppins' ? 'selected' : '' }}>Poppins</option>
                            <option value="Outfit" {{ ($appearance['fonts']['family2'] ?? '') === 'Outfit' ? 'selected' : '' }}>Outfit</option>
                        </select>
                    </div>

                    <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700">
                        <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Live Font Sample</div>
                        <div class="text-base font-bold text-slate-800 dark:text-white font-heading">
                            The quick brown fox jumps over the lazy dog.
                        </div>
                        <div class="text-xs text-slate-600 dark:text-slate-300 mt-1">
                            Academic excellence and institutional performance metrics rendered in crisp typography.
                        </div>
                    </div>

                    <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex justify-end">
                        <button type="submit" class="primary-btn">
                            <i class="ti-save"></i>
                            <span>Save Typography Settings</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- TAB 4: PRELOADER SETTINGS -->
        <div x-show="tab === 'preloader'" class="space-y-4">
            <div class="max-w-2xl white-box space-y-5">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider flex items-center space-x-2">
                        <i class="ti-reload text-theme-primary"></i>
                        <span>Preloader Animation Configuration</span>
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        Control the animated page preloader that appears when pages load.
                    </p>
                </div>

                <form action="{{ route('appearance.preloader') }}" method="POST" class="space-y-4">
                    @csrf
                    <div class="flex items-center space-x-3 p-3 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700">
                        <input type="checkbox" name="preloader_status" value="1" id="preloaderStatusCheck" {{ ($appearance['preloader']['status'] ?? true) ? 'checked' : '' }} class="w-4 h-4 rounded text-purple-600 focus:ring-purple-500">
                        <label for="preloaderStatusCheck" class="text-xs font-bold text-slate-800 dark:text-slate-200 cursor-pointer">
                            Enable Infix Animated Preloader
                        </label>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Preloader Animation Style</label>
                        <select name="preloader_style" class="primary_input_field w-full">
                            <option value="rocket" {{ ($appearance['preloader']['style'] ?? '') === 'rocket' ? 'selected' : '' }}>Infix Speed / Rocket Animation (Signature)</option>
                            <option value="circle" {{ ($appearance['preloader']['style'] ?? '') === 'circle' ? 'selected' : '' }}>Spinning Ring Loader</option>
                            <option value="pulse" {{ ($appearance['preloader']['style'] ?? '') === 'pulse' ? 'selected' : '' }}>Bouncing Pulse Dots</option>
                        </select>
                    </div>

                    <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex justify-end">
                        <button type="submit" class="primary-btn">
                            <i class="ti-save"></i>
                            <span>Save Preloader Settings</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function applyPreset(p, s, a, grad, bg, footerBg, footerText) {
        document.getElementById('primaryColorPicker').value = p;
        document.getElementById('primaryColorText').value = p;
        document.getElementById('secColorPicker').value = s;
        document.getElementById('secColorText').value = s;
        document.getElementById('accentColorPicker').value = a;
        document.getElementById('accentColorText').value = a;
        document.getElementById('bgColorPicker').value = bg;
        document.getElementById('bgColorText').value = bg;
        document.getElementById('footerBgPicker').value = footerBg;
        document.getElementById('footerBgText').value = footerBg;
        document.getElementById('footerTextPicker').value = footerText;
        document.getElementById('footerTextText').value = footerText;
        document.getElementById('customGradient').value = grad;

        updateLiveSwatch();
    }

    function updateLiveSwatch() {
        const p = document.getElementById('primaryColorText').value;
        const a = document.getElementById('accentColorText').value;
        const grad = `linear-gradient(135deg, ${p} 0%, ${a} 100%)`;
        document.getElementById('customGradient').value = grad;

        const btn = document.getElementById('previewPrimaryBtn');
        if (btn) btn.style.background = grad;
        const badge = document.getElementById('previewBadge');
        if (badge) badge.style.background = grad;
        const pbar = document.getElementById('previewProgressBar');
        if (pbar) pbar.style.background = grad;
        const outlineBtn = document.getElementById('previewOutlineBtn');
        if (outlineBtn) {
            outlineBtn.style.color = p;
            outlineBtn.style.borderColor = p;
        }
        const iconBox = document.getElementById('previewIconBox');
        if (iconBox) {
            iconBox.style.color = p;
            iconBox.style.backgroundColor = p + '1a';
        }
    }
</script>
@endsection
