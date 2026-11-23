/**
 * Prime Arc Academy - Enterprise Progressive Web App (PWA) Service Worker
 * Architecture: Stale-While-Revalidate for Static Assets + Network-First with Offline Fallback for Navigation
 * Author: Developed with love for Bharat 🇮🇳
 */

const CACHE_NAME = 'primearc-core-v4';
const OFFLINE_URL = '/offline';

const PRECACHE_ASSETS = [
    OFFLINE_URL,
    '/manifest.json',
    '/js/alpine.min.js',
    '/js/spa-engine.js',
    '/js/pwa-fullscreen.js',
    '/icons/icon.svg',
    '/icons/icon-72x72.png',
    '/icons/icon-96x96.png',
    '/icons/icon-144x144.png',
    '/icons/icon-192x192.png',
    '/icons/icon-512x512.png',
    '/icons/apple-touch-icon.png'
];

// Install: Cache essential app shell and offline fallback screen
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(PRECACHE_ASSETS).catch((err) => {
                console.warn('[PWA-SW] Precache soft warning:', err);
            });
        }).then(() => self.skipWaiting())
    );
});

// Activate: Purge obsolete caches and claim all clients immediately
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((name) => {
                    if (name !== CACHE_NAME) {
                        return caches.delete(name);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

// Fetch: Strategy dispatch
self.addEventListener('fetch', (event) => {
    const request = event.request;

    // Only process HTTP(S) GET requests; let POST / PUT mutations proceed normally
    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    // Bypass cache for Laravel API, Sanctum token, captcha challenges, and auth routes to ensure freshest security state
    if (url.pathname.startsWith('/api/') || url.pathname.startsWith('/captcha') || url.pathname.includes('/login') || url.pathname.includes('/register') || url.pathname.includes('/logout') || url.pathname.includes('/password')) {
        return;
    }

    // 1. Navigation Requests (HTML Pages): Pass directly to network without offline hijacking
    if (request.mode === 'navigate') {
        return;
    }

    // 2. Static Assets (Scripts, Styles, Icons, Images, Fonts): Cache First with Background Revalidation
    if (
        request.destination === 'style' ||
        request.destination === 'script' ||
        request.destination === 'image' ||
        request.destination === 'font' ||
        url.pathname.startsWith('/icons/') ||
        url.pathname.startsWith('/js/')
    ) {
        event.respondWith(
            caches.match(request).then((cachedResponse) => {
                const fetchPromise = fetch(request)
                    .then((networkResponse) => {
                        if (networkResponse && networkResponse.status === 200) {
                            const responseClone = networkResponse.clone();
                            caches.open(CACHE_NAME).then((cache) => {
                                cache.put(request, responseClone);
                            });
                        }
                        return networkResponse;
                    })
                    .catch(() => cachedResponse);

                return cachedResponse || fetchPromise;
            })
        );
        return;
    }

    // 3. Default: Network with Cache Fallback
    event.respondWith(
        fetch(request).catch(() => caches.match(request))
    );
});

// Listen for messages from client (e.g. skip waiting trigger)
self.addEventListener('message', (event) => {
    if (event.data && event.data.action === 'skipWaiting') {
        self.skipWaiting();
    }
});