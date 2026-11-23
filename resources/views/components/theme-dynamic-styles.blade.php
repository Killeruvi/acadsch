@php
    $theme = $appearance['theme'] ?? [];
    $fonts = $appearance['fonts'] ?? [];
    $primary = $theme['primary_color'] ?? '#7c32ff';
    $secondary = $theme['secondary_color'] ?? '#202E3B';
    $gradient = $theme['gradient'] ?? 'linear-gradient(135deg, #7c32ff 0%, #c738d8 100%)';
    $gradientHover = $theme['gradient_hover'] ?? 'linear-gradient(90deg, #c738d8 0%, #7c32ff 100%)';
    $accent = $theme['accent_color'] ?? '#c738d8';
    $bg = $theme['bg_color'] ?? '#f6f7fb';
    $footerBg = $theme['footer_bg'] ?? '#1E2147';
    $footerText = $theme['footer_text'] ?? '#828bb2';
    $footerHover = $theme['footer_text_hover'] ?? '#c738d8';
    $font1 = $fonts['family1'] ?? 'Poppins';
    $font2 = $fonts['family2'] ?? 'Montserrat';
@endphp

<!-- Google Fonts from Infix Appearance -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="{{ $fonts['google_fonts_url'] ?? 'https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Montserrat:wght@400;600;700&display=swap' }}" rel="stylesheet">

<!-- Infix Iconography: Themify Icons & FontAwesome -->
<link rel="stylesheet" href="{{ asset('backend/css/themify-icons.css') }}">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

<!-- Infix Preloader Stylesheet -->
<link rel="stylesheet" href="{{ asset('css/preloader.css') }}">

<style data-type="infix-appearance-style">
    :root {
        --system_primery_color: {{ $primary }};
        --system_secendory_color: {{ $secondary }};
        --system_primary_gradient: {{ $gradient }};
        --system_primary_gradient_hover: {{ $gradientHover }};
        --system_accent_color: {{ $accent }};
        --bg_color: {{ $bg }};
        --footer_background_color: {{ $footerBg }};
        --footer_headline_color: #ffffff;
        --footer_text_color: {{ $footerText }};
        --footer_text_hover_color: {{ $footerHover }};
        --font_family1: '{{ $font1 }}', sans-serif;
        --font_family2: '{{ $font2 }}', sans-serif;

        /* Infix Alpha opacities */
        --system_primery_color_0: {{ $primary }}00;
        --system_primery_color_05: {{ $primary }}0d;
        --system_primery_color_10: {{ $primary }}1a;
        --system_primery_color_20: {{ $primary }}33;
        --system_primery_color_30: {{ $primary }}4d;
        --system_primery_color_50: {{ $primary }}80;
        --system_primery_color_70: {{ $primary }}b3;
        --system_primery_color_90: {{ $primary }}e6;
        --system_secendory_color_10: {{ $secondary }}1a;
    }

    body {
        font-family: var(--font_family1);
        background-color: var(--bg_color, #f6f7fb);
    }

    .dark body {
        background-color: #0b0f19;
    }

    h1, h2, h3, h4, h5, h6, .font-heading {
        font-family: var(--font_family2);
    }

    /* ========================================================================= */
    /* 1. UNIVERSAL THEME OVERRIDES — APPLIES ACROSS ALL PAGES AUTOMATICALLY    */
    /* ========================================================================= */

    /* Universal Primary Buttons */
    button.bg-emerald-600, a.bg-emerald-600, input[type="submit"].bg-emerald-600,
    button.bg-emerald-500, a.bg-emerald-500, input[type="submit"].bg-emerald-500,
    button.bg-teal-600, a.bg-teal-600,
    button.bg-brand-600, a.bg-brand-600,
    button.bg-indigo-600, a.bg-indigo-600,
    .btn-primary, .primary-btn {
        background: var(--system_primary_gradient) !important;
        color: #ffffff !important;
        border-color: transparent !important;
        box-shadow: 0 4px 14px var(--system_primery_color_30) !important;
    }

    button.bg-emerald-600:hover, a.bg-emerald-600:hover,
    button.bg-emerald-500:hover, a.bg-emerald-500:hover,
    button.bg-teal-600:hover, a.bg-teal-600:hover,
    button.bg-indigo-600:hover, a.bg-indigo-600:hover,
    .hover\:bg-emerald-500:hover, .hover\:bg-emerald-700:hover,
    .primary-btn:hover {
        background: var(--system_primary_gradient_hover) !important;
        box-shadow: 0 6px 20px var(--system_primery_color_50) !important;
        color: #ffffff !important;
    }

    /* Universal Text Colors & Accents */
    .text-emerald-600, .text-emerald-500, .text-brand-600, .text-brand-500, .text-teal-600, .text-indigo-600,
    .hover\:text-emerald-700:hover, .hover\:text-emerald-800:hover, .hover\:text-emerald-600:hover {
        color: var(--system_primery_color) !important;
    }
    .text-emerald-400, .text-emerald-300, .text-brand-400, .text-teal-400, .text-indigo-400, .text-indigo-300 {
        color: var(--system_accent_color) !important;
    }

    /* Universal Soft Badges & Highlights */
    .bg-emerald-50, .bg-emerald-100, .bg-brand-50, .bg-brand-100, .bg-teal-50, .bg-indigo-50, .bg-indigo-100 {
        background-color: var(--system_primery_color_10) !important;
        color: var(--system_primery_color) !important;
    }
    .dark .bg-emerald-950, .dark .bg-emerald-950\/60, .dark .bg-emerald-900\/50,
    .dark .bg-indigo-950, .dark .bg-indigo-950\/60 {
        background-color: var(--system_primery_color_20) !important;
        color: #ffffff !important;
    }

    /* Universal Borders & Focus Rings */
    .border-emerald-500, .border-emerald-600, .border-brand-500, .border-brand-600, .border-indigo-500,
    .hover\:border-emerald-500:hover, .hover\:border-emerald-400:hover {
        border-color: var(--system_primery_color) !important;
    }
    .border-emerald-200, .border-emerald-300, .border-emerald-100, .border-indigo-200 {
        border-color: var(--system_primery_color_30) !important;
    }
    .focus\:ring-emerald-500:focus, .focus\:ring-brand-500:focus, .focus\:ring-indigo-500:focus {
        --tw-ring-color: var(--system_primery_color) !important;
    }

    /* Universal SVG Fill & Stroke */
    svg.text-emerald-600, svg.text-emerald-500 {
        color: var(--system_primery_color) !important;
    }
    .stroke-emerald-500, .stroke-emerald-600 {
        stroke: var(--system_primery_color) !important;
    }
    .fill-emerald-500, .fill-emerald-600 {
        fill: var(--system_primery_color) !important;
    }

    /* Universal Gradients (e.g. from-emerald-500 to-teal-500) */
    .from-emerald-500, .from-emerald-600 {
        --tw-gradient-from: var(--system_primery_color) var(--tw-gradient-from-position) !important;
    }
    .to-teal-500, .to-cyan-500, .to-teal-600 {
        --tw-gradient-to: var(--system_accent_color) var(--tw-gradient-to-position) !important;
    }

    /* Infix LMS Signature Gradients and Helper Classes */
    .fix-gr-bg, .primary-gradient, .bg-infix-gradient {
        background: var(--system_primary_gradient) !important;
        color: #ffffff !important;
    }
    .fix-gr-bg:hover, .primary-gradient:hover, .bg-infix-gradient:hover {
        background: var(--system_primary_gradient_hover) !important;
    }
    .theme_color, .text-theme-primary {
        color: var(--system_primery_color) !important;
    }
    .bg-theme-primary {
        background-color: var(--system_primery_color) !important;
    }
    .border-theme-primary {
        border-color: var(--system_primery_color) !important;
    }
    .bg-theme-soft {
        background-color: var(--system_primery_color_10) !important;
    }

    /* Infix LMS Signature Pill Button */
    .primary-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        background: var(--system_primary_gradient);
        color: #ffffff !important;
        border-radius: 30px;
        padding: 0.55rem 1.4rem;
        font-size: 0.8125rem;
        font-weight: 600;
        text-transform: capitalize;
        border: none;
        outline: none;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 4px 14px var(--system_primery_color_30);
    }

    .primary-btn:hover {
        background: var(--system_primary_gradient_hover);
        transform: translateY(-2px);
        box-shadow: 0 6px 20px var(--system_primery_color_50);
        color: #ffffff !important;
    }

    .primary-btn.white {
        background: #ffffff !important;
        color: var(--system_primery_color) !important;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }
    .primary-btn.white:hover {
        background: #f8fafc !important;
        color: var(--system_primery_color) !important;
    }

    .primary-btn.outline {
        background: transparent !important;
        border: 1.5px solid var(--system_primery_color) !important;
        color: var(--system_primery_color) !important;
        box-shadow: none;
    }
    .primary-btn.outline:hover {
        background: var(--system_primary_gradient) !important;
        color: #ffffff !important;
        border-color: transparent !important;
    }

    /* Infix LMS Card / White-box */
    .white-box, .white_box {
        background: #ffffff;
        border-radius: 14px;
        padding: 1.5rem;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
        border: 1px solid rgba(226, 232, 240, 0.85);
        transition: all 0.3s ease;
        position: relative;
    }
    .dark .white-box, .dark .white_box {
        background: #182032 !important;
        border-color: #27344f !important;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
        color: #f1f5f9;
    }

    /* Infix Signature Single Summary Metric Card */
    .white-box.single-summery {
        position: relative;
        overflow: hidden;
        border-radius: 14px;
        transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        border: 1px solid rgba(226, 232, 240, 0.85);
        background: #ffffff;
    }
    .white-box.single-summery::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: var(--system_primary_gradient);
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    .white-box.single-summery:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 28px rgba(124, 50, 255, 0.12);
        border-color: var(--system_primery_color_30);
    }
    .white-box.single-summery:hover::before {
        opacity: 1;
    }
    .dark .white-box.single-summery {
        background: #182032 !important;
        border-color: #27344f !important;
    }
    .dark .white-box.single-summery:hover {
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.45);
        border-color: var(--system_primery_color_50) !important;
    }

    .gradient-color2 {
        background: var(--system_primary_gradient);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        display: inline-block;
    }

    /* Infix Course & Quiz Card System (.couse_wizged) */
    .couse_wizged, .course_wizged {
        background: #ffffff;
        border-radius: 14px;
        border: 1px solid rgba(226, 232, 240, 0.85);
        overflow: hidden;
        transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        box-shadow: 0 4px 18px rgba(0, 0, 0, 0.04);
        position: relative;
    }
    .couse_wizged:hover, .course_wizged:hover {
        transform: translateY(-4px);
        box-shadow: 0 14px 30px rgba(124, 50, 255, 0.12);
        border-color: var(--system_primery_color_30);
    }
    .dark .couse_wizged, .dark .course_wizged {
        background: #182032 !important;
        border-color: #27344f !important;
    }
    .dark .couse_wizged:hover, .dark .course_wizged:hover {
        box-shadow: 0 14px 30px rgba(0, 0, 0, 0.45);
        border-color: var(--system_primery_color_50) !important;
    }
    .couse_wizged .thumb, .course_wizged .thumb {
        position: relative;
        overflow: hidden;
        height: 140px;
        background: linear-gradient(135deg, var(--system_primery_color_10) 0%, var(--system_primery_color_20) 100%);
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .couse_wizged .thumb img, .course_wizged .thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.4s ease;
    }
    .couse_wizged:hover .thumb img, .course_wizged:hover .thumb img {
        transform: scale(1.06);
    }
    .couse_wizged .thumb .prise_tag, .course_wizged .thumb .prise_tag,
    .couse_wizged .prise_tag, .course_wizged .prise_tag {
        position: absolute;
        top: 12px;
        left: 12px;
        padding: 4px 10px;
        border-radius: 20px;
        background: #ffffff;
        color: var(--system_primery_color);
        font-size: 11px;
        font-weight: 700;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.12);
        z-index: 2;
    }
    .dark .couse_wizged .thumb .prise_tag, .dark .course_wizged .thumb .prise_tag,
    .dark .couse_wizged .prise_tag, .dark .course_wizged .prise_tag {
        background: #1e293b;
        color: #f8fafc;
    }
    .couse_wizged .course_content, .course_wizged .course_content {
        padding: 1.15rem;
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    .couse_wizged .course_content h4, .course_wizged .course_content h4 {
        font-size: 0.9375rem;
        font-weight: 700;
        line-height: 1.35;
        color: #1e293b;
        transition: color 0.2s ease;
    }
    .dark .couse_wizged .course_content h4, .dark .course_wizged .course_content h4 {
        color: #f1f5f9;
    }
    .couse_wizged .course_content h4:hover, .course_wizged .course_content h4:hover {
        color: var(--system_primery_color);
    }
    .couse_wizged .rating_cart, .course_wizged .rating_cart {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin: 0.6rem 0;
    }
    .couse_wizged .rateing, .course_wizged .rateing {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 0.75rem;
        font-weight: 600;
        color: #eab308;
    }
    .couse_wizged .course_less_students, .course_wizged .course_less_students {
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 0.75rem;
        color: #64748b;
        padding-top: 0.5rem;
        border-top: 1px solid rgba(226, 232, 240, 0.8);
    }
    .dark .couse_wizged .course_less_students, .dark .course_wizged .course_less_students {
        color: #94a3b8;
        border-top-color: #27344f;
    }
    .couse_wizged .course_less_students a, .course_wizged .course_less_students span {
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    /* Infix Student / Member Card (.single_member_card) */
    .single_member_card {
        background: #ffffff;
        border-radius: 14px;
        border: 1px solid rgba(226, 232, 240, 0.85);
        padding: 1.25rem;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    .single_member_card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(124, 50, 255, 0.1);
        border-color: var(--system_primery_color_30);
    }
    .dark .single_member_card {
        background: #182032 !important;
        border-color: #27344f !important;
    }
    .dark .single_member_card:hover {
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
        border-color: var(--system_primery_color_50) !important;
    }

    /* Infix Card Header */
    .box_header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-bottom: 0.75rem;
        margin-bottom: 1rem;
        border-bottom: 1px solid rgba(226, 232, 240, 0.8);
    }
    .dark .box_header {
        border-bottom-color: #27344f;
    }
    .box_header .main-title h3, .box_header h3 {
        font-size: 0.9375rem;
        font-weight: 700;
        color: #1e293b;
        letter-spacing: -0.01em;
    }
    .dark .box_header .main-title h3, .dark .box_header h3 {
        color: #f1f5f9;
    }

    /* Smooth Light/Dark Mode Transition */
    html.theme-transitioning,
    html.theme-transitioning *,
    html.theme-transitioning *:before,
    html.theme-transitioning *:after {
        transition: background-color 0.25s ease, border-color 0.25s ease, color 0.2s ease !important;
        transition-delay: 0s !important;
    }

    /* Infix Input Styles */
    .primary_input_field {
        height: 42px;
        border-radius: 25px;
        border: 1.5px solid #e2e8f0;
        padding: 0 1.25rem;
        font-size: 0.875rem;
        color: #202e3b;
        background-color: #ffffff;
        transition: all 0.25s ease;
        outline: none;
    }
    .primary_input_field:focus {
        border-color: var(--system_primery_color);
        box-shadow: 0 0 0 3px var(--system_primery_color_20);
    }
    .dark .primary_input_field {
        background-color: #1e293b;
        border-color: #334155;
        color: #f8fafc;
    }

    /* Infix Notification Pulse Ripple */
    .pulse.theme_color {
        position: relative;
    }
    .pulse.theme_color::after {
        content: '';
        position: absolute;
        top: 2px;
        right: 2px;
        width: 8px;
        height: 8px;
        background-color: var(--system_primery_color);
        border-radius: 50%;
        animation: infixPulse 1.8s infinite;
    }
    @keyframes infixPulse {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 var(--system_primery_color_70); }
        70% { transform: scale(1); box-shadow: 0 0 0 8px transparent; }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 transparent; }
    }

    /* ========================================================================= */
    /* 2. INFIX DESKTOP & MOBILE SIDEBAR (#sidebar)                              */
    /* ========================================================================= */
    .infix-layout-wrapper {
        display: flex;
        min-height: 100vh;
        width: 100%;
        position: relative;
    }

    #sidebar {
        position: fixed;
        top: 0;
        left: 0;
        bottom: 0;
        width: 260px;
        z-index: 50;
        background: #ffffff;
        border-right: 1px solid rgba(226, 232, 240, 0.9);
        box-shadow: 0 0 25px rgba(124, 50, 255, 0.05);
        display: flex;
        flex-direction: column;
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), width 0.3s ease;
        overflow-y: auto;
        overflow-x: hidden;
    }

    .dark #sidebar {
        background: #141b2d;
        border-right-color: #1f293d;
        box-shadow: 0 0 25px rgba(0, 0, 0, 0.4);
    }

    #sidebar .sidebar-header {
        padding: 1.25rem 1.5rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-bottom: 1px solid rgba(226, 232, 240, 0.8);
    }
    .dark #sidebar .sidebar-header {
        border-bottom-color: #1f293d;
    }

    #sidebar .sidebar-user {
        padding: 1.25rem 1rem 1rem 1rem;
        text-align: center;
        border-bottom: 1px solid rgba(226, 232, 240, 0.8);
        background: linear-gradient(180deg, var(--system_primery_color_05) 0%, transparent 100%);
    }
    .dark #sidebar .sidebar-user {
        border-bottom-color: #1f293d;
    }

    #sidebar .sidebar-user .sidebar-profile {
        width: 58px;
        height: 58px;
        margin: 0 auto 0.5rem auto;
        border-radius: 50%;
        border: 2.5px solid var(--system_primery_color);
        padding: 2px;
        box-shadow: 0 4px 12px var(--system_primery_color_30);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 1.125rem;
        color: var(--system_primery_color);
        background: #ffffff;
    }
    .dark #sidebar .sidebar-user .sidebar-profile {
        background: #1e293b;
    }

    #sidebar .menu_seperator {
        display: block;
        padding: 1rem 1.5rem 0.35rem 1.5rem;
        font-size: 0.65rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #94a3b8;
    }

    #sidebar_menu {
        list-style: none;
        padding: 0.5rem 0.75rem 2rem 0.75rem;
        margin: 0;
        flex: 1;
    }

    #sidebar_menu li {
        margin-bottom: 0.25rem;
    }

    #sidebar_menu li a {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.65rem 0.875rem;
        border-radius: 12px;
        color: #475569;
        font-size: 0.8125rem;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s ease;
    }
    .dark #sidebar_menu li a {
        color: #94a3b8;
    }

    #sidebar_menu li a:hover {
        background: var(--system_primery_color_10);
        color: var(--system_primery_color);
    }
    .dark #sidebar_menu li a:hover {
        color: #ffffff;
    }

    #sidebar_menu li a.active, #sidebar_menu li.active a, .infix-nav-active {
        background: var(--system_primary_gradient) !important;
        color: #ffffff !important;
        box-shadow: 0 6px 18px var(--system_primery_color_30) !important;
    }

    /* Sidebar Dropdowns & Submenus */
    .sidebar-dropdown {
        position: relative;
    }
    .sidebar-dropdown > a.has-arrow {
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 100%;
        cursor: pointer;
    }
    .sidebar-dropdown > a.has-arrow .dropdown-chevron {
        font-size: 0.65rem;
        color: #94a3b8;
        transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        margin-left: auto;
    }
    .sidebar-dropdown.dropdown-open > a.has-arrow .dropdown-chevron {
        transform: rotate(180deg);
        color: var(--system_primery_color);
    }
    .dark .sidebar-dropdown.dropdown-open > a.has-arrow .dropdown-chevron {
        color: #ffffff;
    }
    .sidebar-dropdown.dropdown-open > a.has-arrow {
        background: var(--system_primery_color_05);
        color: var(--system_primery_color);
    }
    .sidebar-dropdown > a.has-arrow.active-category {
        font-weight: 700;
        color: var(--system_primery_color);
    }
    .dark .sidebar-dropdown > a.has-arrow.active-category {
        color: #ffffff;
    }
    .sidebar-submenu {
        list-style: none;
        padding: 0.35rem 0 0.35rem 1rem;
        margin: 0.2rem 0 0.4rem 1.4rem;
        border-left: 1.5px dashed rgba(203, 213, 225, 0.7);
        transition: all 0.2s ease;
    }
    .dark .sidebar-submenu {
        border-left-color: rgba(51, 65, 85, 0.7);
    }
    .sidebar-submenu li {
        margin-bottom: 0.15rem;
    }
    .sidebar-submenu li a {
        padding: 0.45rem 0.65rem !important;
        font-size: 0.785rem !important;
        font-weight: 500 !important;
        color: #64748b !important;
        border-radius: 8px !important;
        display: flex !important;
        align-items: center !important;
        gap: 0.5rem !important;
        transition: all 0.2s ease !important;
    }
    .dark .sidebar-submenu li a {
        color: #94a3b8 !important;
    }
    .sidebar-submenu li a:hover {
        background: var(--system_primery_color_10) !important;
        color: var(--system_primery_color) !important;
        padding-left: 0.85rem !important;
    }
    .dark .sidebar-submenu li a:hover {
        color: #ffffff !important;
    }
    .sidebar-submenu li a.active, .sidebar-submenu li a.infix-nav-active {
        background: var(--system_primary_gradient) !important;
        color: #ffffff !important;
        box-shadow: 0 4px 12px var(--system_primery_color_30) !important;
        font-weight: 700 !important;
    }
    .sidebar-submenu li a .sub-bullet {
        width: 5px;
        height: 5px;
        border-radius: 50%;
        background: #94a3b8;
        transition: all 0.2s ease;
        flex-shrink: 0;
    }
    .sidebar-submenu li a:hover .sub-bullet {
        background: var(--system_primery_color);
        transform: scale(1.3);
    }
    .sidebar-submenu li a.active .sub-bullet, .sidebar-submenu li a.infix-nav-active .sub-bullet {
        background: #ffffff;
        transform: scale(1.4);
    }

    .infix-nav-icon {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        background: var(--system_primery_color_10);
        color: var(--system_primery_color);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.95rem;
        flex-shrink: 0;
        transition: all 0.2s ease;
    }

    #sidebar_menu li a:hover .infix-nav-icon {
        background: var(--system_primery_color);
        color: #ffffff;
    }

    #sidebar_menu li a.active .infix-nav-icon, .infix-nav-active .infix-nav-icon {
        background: rgba(255, 255, 255, 0.22) !important;
        color: #ffffff !important;
    }

    /* ========================================================================= */
    /* 3. INFIX MAIN CONTENT AREA & HEADER (.header_iner)                        */
    /* ========================================================================= */
    #main-content {
        flex: 1;
        margin-left: 260px;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        transition: margin-left 0.3s ease;
        background: var(--bg_color, #f6f7fb);
        width: calc(100% - 260px);
    }

    .dark #main-content {
        background: #0b0f19;
    }

    .header_iner {
        position: sticky;
        top: 0;
        z-index: 40;
        height: 64px;
        background: #ffffff;
        border-bottom: 1px solid rgba(226, 232, 240, 0.9);
        padding: 0 1.5rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
    }

    .dark .header_iner {
        background: #141b2d;
        border-bottom-color: #1f293d;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
    }

    /* Global Top Nav Search Field (Responsive Auto-Spacing) */
    #global-search-container {
        flex: 1;
        min-width: 0;
        max-width: 520px;
    }
    .search_field {
        position: relative;
        display: flex;
        align-items: center;
        width: 100%;
    }
    .search_field input {
        height: 38px;
        width: 100%;
        min-width: 0;
        border-radius: 20px;
        padding: 0 2rem 0 2.25rem;
        font-size: 0.775rem;
        border: 1.5px solid #e2e8f0;
        background: #f8fafc;
        outline: none;
        transition: all 0.25s ease;
    }
    .dark .search_field input {
        background: #1a2236;
        border-color: #27344f;
        color: #ffffff;
    }
    .search_field input:focus {
        border-color: var(--system_primery_color);
        background: #ffffff;
        box-shadow: 0 0 0 3px var(--system_primery_color_20);
    }
    .dark .search_field input:focus {
        background: #141b2d;
    }
    .search_field i.ti-search {
        position: absolute;
        left: 0.85rem;
        color: #94a3b8;
        font-size: 0.8125rem;
        pointer-events: none;
    }

    /* Global Search Dropdown Results Modal */
    #globalSearchResults {
        position: absolute;
        top: calc(100% + 8px);
        left: 0;
        right: 0;
        z-index: 999;
        max-height: 70vh;
        overflow-y: auto;
    }
    .search-result-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.6rem 0.85rem;
        border-radius: 10px;
        text-decoration: none;
        transition: all 0.15s ease;
    }
    .search-result-item:hover, .search-result-item.selected {
        background: var(--system_primery_color_10);
    }
    .dark .search-result-item:hover, .dark .search-result-item.selected {
        background: rgba(124, 50, 255, 0.2);
    }

    /* Profile Thumb & Dropdown */
    .profile_info {
        position: relative;
        cursor: pointer;
    }
    .profileThumb {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        border: 2px solid var(--system_primery_color);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.75rem;
        color: #ffffff;
        background: var(--system_primary_gradient);
        box-shadow: 0 2px 8px var(--system_primery_color_30);
    }

    /* Mobile Responsive Sidebar Drawer & Android Top Nav */
    @media (max-width: 991px) {
        #sidebar {
            transform: translateX(-100%);
        }
        #sidebar.sidebar-open {
            transform: translateX(0);
        }
        #main-content {
            margin-left: 0 !important;
            width: 100% !important;
        }
    }

    @media (max-width: 640px) {
        .header_iner {
            padding: 0 0.65rem;
            height: 56px;
            gap: 0.35rem;
        }
        #global-search-container {
            margin: 0 0.25rem;
        }
        .search_field input {
            height: 34px;
            font-size: 0.725rem;
            padding: 0 1.5rem 0 1.95rem;
        }
        .search_field i.ti-search {
            left: 0.65rem;
            font-size: 0.75rem;
        }
    }
</style>
