@php
    $preloader = $appearance['preloader'] ?? ['status' => true, 'style' => 'circle'];
    $theme = $appearance['theme'] ?? [];
@endphp

@if($preloader['status'] ?? true)
<div class="preloader" id="infix-preloader" dir="ltr" aria-live="polite" role="status">
    <div class="preloader-card">
        <div class="infix-spinner-wrapper infix-spinner">
            <div class="infix-spinner-ring outer-ring"></div>
            <div class="infix-spinner-ring middle-ring"></div>
            <div class="infix-spinner-ring inner-ring"></div>
            <div class="infix-spinner-core"></div>
            <div class="infix-spinner-orbit-dot"></div>
            <div class="infix-spinner-orbit-dot-2"></div>
        </div>
        <div class="spinner-title">{{ $school['name'] ?? config('app.name', 'School ERP') }}</div>
    </div>
</div>

<script>
    function hideInfixPreloader() {
        var p = document.getElementById('infix-preloader');
        if (p) {
            p.style.opacity = '0';
            p.style.visibility = 'hidden';
            setTimeout(function() {
                p.style.display = 'none';
            }, 250);
        }
    }

    function showInfixPreloader() {
        var p = document.getElementById('infix-preloader');
        if (p) {
            p.style.display = 'flex';
            p.style.visibility = 'visible';
            p.style.opacity = '1';
        }
    }

    // Dismiss spinner swiftly once DOM ready so it never blocks or causes full screen flashes
    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        setTimeout(hideInfixPreloader, 150);
    } else {
        window.addEventListener('DOMContentLoaded', function() {
            setTimeout(hideInfixPreloader, 150);
        });
        window.addEventListener('load', hideInfixPreloader);
    }
</script>
@endif
