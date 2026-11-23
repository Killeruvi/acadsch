/**
 * Prime Arc Academy - Fullscreen Mode & Progressive Web App (PWA) Manager
 * Developed with love for Bharat 🇮🇳
 */

(function () {
    'use strict';

    // 1. Fullscreen API Management
    function isFullscreenActive() {
        return !!(
            document.fullscreenElement ||
            document.webkitFullscreenElement ||
            document.mozFullScreenElement ||
            document.msFullscreenElement
        );
    }

    function toggleAppFullscreen() {
        if (!isFullscreenActive()) {
            const el = document.documentElement;
            if (el.requestFullscreen) {
                el.requestFullscreen().catch(err => console.warn('[Fullscreen] Request error:', err));
            } else if (el.webkitRequestFullscreen) {
                el.webkitRequestFullscreen();
            } else if (el.msRequestFullscreen) {
                el.msRequestFullscreen();
            }
        } else {
            if (document.exitFullscreen) {
                document.exitFullscreen().catch(err => console.warn('[Fullscreen] Exit error:', err));
            } else if (document.webkitExitFullscreen) {
                document.webkitExitFullscreen();
            } else if (document.msExitFullscreen) {
                document.msExitFullscreen();
            }
        }
    }

    function syncFullscreenUI() {
        const active = isFullscreenActive();
        const expandIcons = document.querySelectorAll('#fs-expand-icon');
        const compressIcons = document.querySelectorAll('#fs-compress-icon');
        const mobileStatus = document.getElementById('mobile-fs-status');
        const fsButtons = document.querySelectorAll('#fullscreen-toggle-btn');

        expandIcons.forEach(icon => {
            if (active) {
                icon.classList.remove('block');
                icon.classList.add('hidden');
            } else {
                icon.classList.remove('hidden');
                icon.classList.add('block');
            }
        });

        compressIcons.forEach(icon => {
            if (active) {
                icon.classList.remove('hidden');
                icon.classList.add('block');
            } else {
                icon.classList.remove('block');
                icon.classList.add('hidden');
            }
        });

        if (mobileStatus) {
            mobileStatus.textContent = active ? 'Active' : 'Normal';
            mobileStatus.className = active 
                ? 'text-[10px] text-cyan-400 font-extrabold uppercase'
                : 'text-[10px] text-emerald-400 font-extrabold uppercase';
        }

        fsButtons.forEach(btn => {
            btn.title = active ? 'Exit Fullscreen' : 'Enter Fullscreen (F11)';
        });
    }

    document.addEventListener('fullscreenchange', syncFullscreenUI);
    document.addEventListener('webkitfullscreenchange', syncFullscreenUI);
    document.addEventListener('mozfullscreenchange', syncFullscreenUI);
    document.addEventListener('MSFullscreenChange', syncFullscreenUI);

    document.addEventListener('keydown', (e) => {
        if (e.key === 'F11') {
            setTimeout(syncFullscreenUI, 100);
        }
    });

    window.toggleAppFullscreen = toggleAppFullscreen;

    // 2. Service Worker Registration
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js')
                .then((registration) => {
                    console.log('[PWA] Service Worker registered with scope:', registration.scope);

                    registration.addEventListener('updatefound', () => {
                        const installingWorker = registration.installing;
                        if (installingWorker) {
                            installingWorker.addEventListener('statechange', () => {
                                if (installingWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                    console.log('[PWA] New version available.');
                                }
                            });
                        }
                    });
                })
                .catch((error) => {
                    console.warn('[PWA] Service Worker registration failed:', error);
                });
        });
    }

    // 3. BeforeInstallPrompt Handling (PWA Install Banner & Buttons)
    let deferredInstallPrompt = null;

    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredInstallPrompt = e;

        const installBtns = document.querySelectorAll('#pwa-install-btn, #pwa-mobile-install-btn');
        installBtns.forEach(btn => {
            btn.classList.remove('hidden');
            btn.classList.add('inline-flex');
        });
    });

    function triggerPwaInstall() {
        if (!deferredInstallPrompt) {
            alert('School App is ready and installed on your device via PrimeOS.');
            return;
        }

        deferredInstallPrompt.prompt();
        deferredInstallPrompt.userChoice.then((choiceResult) => {
            if (choiceResult.outcome === 'accepted') {
                console.log('[PWA] User accepted the installation prompt.');
            } else {
                console.log('[PWA] User dismissed the installation prompt.');
            }
            deferredInstallPrompt = null;
            const installBtns = document.querySelectorAll('#pwa-install-btn, #pwa-mobile-install-btn');
            installBtns.forEach(btn => {
                btn.classList.add('hidden');
                btn.classList.remove('inline-flex');
            });
        });
    }

    window.triggerPwaInstall = triggerPwaInstall;

    window.addEventListener('appinstalled', () => {
        console.log('[PWA] PrimeOS School App installed successfully!');
        deferredInstallPrompt = null;
        const installBtns = document.querySelectorAll('#pwa-install-btn, #pwa-mobile-install-btn');
        installBtns.forEach(btn => {
            btn.classList.add('hidden');
            btn.classList.remove('inline-flex');
        });
    });

    document.addEventListener('DOMContentLoaded', () => {
        syncFullscreenUI();
    });
})();