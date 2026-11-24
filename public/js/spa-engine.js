/**
 * PrimeArc Public School — Seamless SPA Engine & Dynamic Wireframe Loader
 * 
 * Intercepts page transitions and forms to eliminate complete page reloads.
 * Displays a top progress bar, responsive loading states, and updates only required DOM components.
 * Preserves scroll positions for question review, pagination, and filter interactions.
 * 
 * Developed by Prithviraj with love for Bharat 🇮🇳
 */

(function () {
    'use strict';

    let isNavigating = false;
    let progressTimer = null;

    // Cache core DOM elements
    function getElements() {
        return {
            progress: document.getElementById('spa-progress'),
            spinner: document.getElementById('spa-inline-spinner'),
            skeleton: document.getElementById('spa-skeleton-overlay'),
            content: document.getElementById('spa-content'),
            sidebarMenu: document.getElementById('sidebar_menu'),
            toasts: document.getElementById('app-toasts'),
            topNavContainer: document.getElementById('top-nav-container'),
            topNavLinks: document.getElementById('top-nav-links'),
            topNavBell: document.getElementById('top-nav-bell'),
            navbarAuthActions: document.getElementById('navbar-auth-actions'),
            mobileDrawerLinks: document.getElementById('mobile-drawer-links'),
            mobileBottomTabs: document.getElementById('mobile-bottom-tabs'),
        };
    }

    /**
     * Start loading UI:
     * - Animates top progress bar.
     * - Displays inline spinner animation in content area (navbars & sidebars remain 100% intact).
     */
    function startLoading(isPartial = false) {
        isNavigating = true;
        const els = getElements();

        // 1. Top progress bar animation
        if (els.progress) {
            clearInterval(progressTimer);
            els.progress.style.transition = 'width 200ms ease-out, opacity 150ms ease-in';
            els.progress.style.opacity = '1';
            els.progress.style.width = '25%';

            let progress = 25;
            progressTimer = setInterval(() => {
                if (progress < 85) {
                    progress += Math.floor(Math.random() * 12) + 5;
                    if (progress > 85) progress = 85;
                    els.progress.style.width = progress + '%';
                }
            }, 180);
        }

        // 2. Inline Content Loading: Navbars, sidebar & bottom tabs remain 100% intact & interactive
        if (els.content) {
            els.content.style.transition = 'opacity 150ms ease';
            els.content.style.opacity = '0.5';
            els.content.style.pointerEvents = 'none';
        }
        if (els.spinner) {
            els.spinner.classList.remove('hidden');
            els.spinner.style.opacity = '1';
        }
    }

    /**
     * Finish loading UI.
     */
    function finishLoading() {
        const els = getElements();

        if (els.progress) {
            clearInterval(progressTimer);
            els.progress.style.width = '100%';
            setTimeout(() => {
                els.progress.style.opacity = '0';
                setTimeout(() => {
                    els.progress.style.width = '0%';
                }, 200);
            }, 150);
        }

        // Hide inline spinner
        if (els.spinner) {
            els.spinner.style.opacity = '0';
            setTimeout(() => {
                els.spinner.classList.add('hidden');
            }, 200);
        }

        // Reveal content
        if (els.content) {
            els.content.classList.remove('hidden');
            els.content.style.opacity = '1';
            els.content.style.pointerEvents = '';
            els.content.classList.add('animate-fade-in');
            setTimeout(() => {
                els.content.classList.remove('animate-fade-in');
            }, 300);
        }

        isNavigating = false;
    }

    /**
     * Swap newly fetched page elements into the current DOM.
     */
    function swapContent(newDoc, targetUrl, options = {}) {
        const currentEls = getElements();

        // 1. Update Document Title
        if (newDoc.title) {
            document.title = newDoc.title;
        }

        // 2. Update CSRF Token if present
        const newCsrf = newDoc.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (newCsrf) {
            const currentCsrf = document.querySelector('meta[name="csrf-token"]');
            if (currentCsrf) currentCsrf.setAttribute('content', newCsrf);
        }

        // 3. Update Main Content Container
        const newContent = newDoc.getElementById('spa-content') || newDoc.querySelector('main');
        if (currentEls.content && newContent) {
            currentEls.content.innerHTML = newContent.innerHTML;
        }

        // Update Infix Sidebar Menu Active Link
        const newSidebar = newDoc.getElementById('sidebar_menu');
        if (currentEls.sidebarMenu && newSidebar) {
            currentEls.sidebarMenu.innerHTML = newSidebar.innerHTML;
            if (typeof syncSidebarDropdowns === 'function') {
                syncSidebarDropdowns();
            }
        }

        // 4. Update Flash Messages / Notification Toasts
        const newToasts = newDoc.getElementById('app-toasts');
        if (currentEls.toasts && newToasts) {
            currentEls.toasts.innerHTML = newToasts.innerHTML;
        }

        // 5. Update Navigation Links (Only if content changed, to preserve active event listeners)
        const swappedContainers = [currentEls.content];

        const newTopNav = newDoc.getElementById('top-nav-links');
        if (currentEls.topNavLinks && newTopNav && currentEls.topNavLinks.innerHTML !== newTopNav.innerHTML) {
            currentEls.topNavLinks.innerHTML = newTopNav.innerHTML;
            swappedContainers.push(currentEls.topNavLinks);
        }

        const newAuthActions = newDoc.getElementById('navbar-auth-actions');
        if (currentEls.navbarAuthActions && newAuthActions && currentEls.navbarAuthActions.innerHTML !== newAuthActions.innerHTML) {
            currentEls.navbarAuthActions.innerHTML = newAuthActions.innerHTML;
            swappedContainers.push(currentEls.navbarAuthActions);
        }

        const newBell = newDoc.getElementById('top-nav-bell');
        if (currentEls.topNavBell && newBell && currentEls.topNavBell.innerHTML !== newBell.innerHTML) {
            currentEls.topNavBell.innerHTML = newBell.innerHTML;
            swappedContainers.push(currentEls.topNavBell);
        }

        const newDrawer = newDoc.getElementById('mobile-drawer-links');
        if (currentEls.mobileDrawerLinks && newDrawer && currentEls.mobileDrawerLinks.innerHTML !== newDrawer.innerHTML) {
            currentEls.mobileDrawerLinks.innerHTML = newDrawer.innerHTML;
            swappedContainers.push(currentEls.mobileDrawerLinks);
        }

        const newBottomTabs = newDoc.getElementById('mobile-bottom-tabs');
        if (currentEls.mobileBottomTabs && newBottomTabs && currentEls.mobileBottomTabs.innerHTML !== newBottomTabs.innerHTML) {
            currentEls.mobileBottomTabs.innerHTML = newBottomTabs.innerHTML;
            swappedContainers.push(currentEls.mobileBottomTabs);
        }

        // 6. Update History State
        if (options.pushState !== false) {
            window.history.pushState({ spa: true, url: targetUrl }, '', targetUrl);
        }

        // 7. Scroll Stability: Only scroll to top if preserveScroll is NOT explicitly requested
        if (!options.preserveScroll) {
            window.scrollTo({ top: 0, behavior: 'instant' });
        }

        // 8. Rehydrate Alpine.js directives on all swapped DOM nodes (ensures responsive top bar clicks & dropdowns)
        if (window.Alpine) {
            swappedContainers.forEach(container => {
                if (container) {
                    try {
                        if (typeof window.Alpine.destroyTree === 'function') {
                            window.Alpine.destroyTree(container);
                        }
                        window.Alpine.initTree(container);
                    } catch (alpineErr) {
                        console.warn('Alpine rehydration warning:', alpineErr);
                    }
                }
            });
        }

        // 9. Dispatch custom SPA navigation event
        window.dispatchEvent(new CustomEvent('spa:navigated', { detail: { url: targetUrl } }));

        // 10. Re-sync Theme UI states (light/dark toggles, labels)
        if (typeof window.__syncThemeUI === 'function') {
            window.__syncThemeUI(document.documentElement.classList.contains('dark'));
        }
    }

    /**
     * Perform seamless asynchronous navigation without complete page reload.
     */
    async function navigate(url, options = {}) {
        if (isNavigating) return;

        // Skip if exact same URL and not forced
        if (!options.force && url === window.location.href) {
            return;
        }

        const isPartial = Boolean(options.preserveScroll || options.isPartial);
        startLoading(isPartial);

        const controller = new AbortController();
        const timeoutMs = options.timeout || 20000;
        const timeoutId = setTimeout(() => controller.abort(), timeoutMs);

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const headers = {
                'X-Requested-With': 'XMLHttpRequest',
                'X-SPA-Request': 'true',
                'Accept': 'text/html, application/xhtml+xml',
            };
            if (csrfToken) headers['X-CSRF-TOKEN'] = csrfToken;

            const response = await fetch(url, { headers, signal: controller.signal });
            clearTimeout(timeoutId);

            // If server returned non-OK or redirect outside application
            if (!response.ok) {
                if (response.status === 405) {
                    console.error('HTTP 405 Method Not Allowed encountered on:', url);
                    alert('Action not allowed (405). Please verify parameters.');
                    return;
                }
                if (response.status === 419) {
                    console.warn('Session expired (419). Reloading current view...');
                    window.location.reload();
                    return;
                }
                window.location.href = url;
                return;
            }

            const html = await response.text();
            const parser = new DOMParser();
            const newDoc = parser.parseFromString(html, 'text/html');

            const destinationUrl = response.url || url;
            swapContent(newDoc, destinationUrl, options);
        } catch (err) {
            clearTimeout(timeoutId);
            if (err.name === 'AbortError') {
                console.warn('SPA request timed out:', url);
                alert('Request timed out. Please check your network connection and retry.');
            } else {
                console.error('SPA navigation error, falling back:', err);
                window.location.href = url;
            }
        } finally {
            finishLoading();
        }
    }

    /**
     * Submit form asynchronously without complete page reload.
     */
    async function submitForm(form, options = {}) {
        if (isNavigating) return;

        const isPartial = Boolean(form.hasAttribute('data-preserve-scroll') || options.preserveScroll);
        startLoading(isPartial);

        const rawMethod = (form.getAttribute('method') || form.method || 'GET').toUpperCase();
        const hiddenMethod = form.querySelector('input[name="_method"]')?.value;
        const effectiveMethod = (hiddenMethod ? hiddenMethod : rawMethod).toUpperCase();
        const actionUrl = form.action || window.location.href;
        const formData = new FormData(form);

        const controller = new AbortController();
        const timeoutMs = options.timeout || 30000;
        const timeoutId = setTimeout(() => controller.abort(), timeoutMs);

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const headers = {
                'X-Requested-With': 'XMLHttpRequest',
                'X-SPA-Request': 'true',
                'Accept': 'text/html, application/xhtml+xml',
            };
            if (csrfToken) headers['X-CSRF-TOKEN'] = csrfToken;

            let response;
            if (effectiveMethod === 'GET') {
                const urlObj = new URL(actionUrl, window.location.origin);
                for (const [k, v] of formData.entries()) {
                    if (v !== null && v !== undefined && v !== '') {
                        urlObj.searchParams.set(k, v);
                    } else {
                        urlObj.searchParams.delete(k);
                    }
                }
                response = await fetch(urlObj.toString(), { headers, signal: controller.signal });
            } else {
                if (hiddenMethod && !formData.has('_method')) {
                    formData.append('_method', hiddenMethod);
                }
                headers['X-HTTP-Method-Override'] = effectiveMethod;

                response = await fetch(actionUrl, {
                    method: 'POST',
                    body: formData,
                    headers: headers,
                    signal: controller.signal,
                });
            }
            clearTimeout(timeoutId);

            if (!response.ok) {
                if (response.status === 405) {
                    console.error('HTTP 405 on form submission to:', actionUrl);
                    alert('Submission error: Method not allowed (405). Please refresh and retry.');
                    return;
                }
                if (response.status === 419) {
                    console.warn('Session expired on submit. Reloading...');
                    window.location.reload();
                    return;
                }
                form.submit();
                return;
            }

            const html = await response.text();
            const parser = new DOMParser();
            const newDoc = parser.parseFromString(html, 'text/html');

            const destinationUrl = response.url || actionUrl;
            swapContent(newDoc, destinationUrl, {
                pushState: true,
                preserveScroll: isPartial
            });
        } catch (err) {
            clearTimeout(timeoutId);
            if (err.name === 'AbortError') {
                console.warn('SPA Form request timed out:', actionUrl);
                alert('Submission timed out. Please check your connection and retry.');
            } else {
                console.error('SPA form submission error, falling back:', err);
                form.submit();
            }
        } finally {
            finishLoading();
        }
    }

    /**
     * Helper to verify if an anchor link qualifies for SPA interception.
     */
    function shouldInterceptLink(anchor) {
        if (!anchor || !anchor.href) return false;
        if (anchor.target && anchor.target !== '_self') return false;
        if (anchor.hasAttribute('download')) return false;
        if (anchor.hasAttribute('data-no-spa')) return false;

        const href = anchor.getAttribute('href');
        if (!href || href.startsWith('#') || href.startsWith('javascript:') || href.startsWith('mailto:') || href.startsWith('tel:')) {
            return false;
        }

        // Must be internal origin
        try {
            const urlObj = new URL(anchor.href, window.location.origin);
            if (urlObj.origin !== window.location.origin) return false;

            // Exclude direct file downloads like CSV exports, templates, and auth links
            const path = urlObj.pathname.toLowerCase();
            if (path.includes('export-csv') || path.includes('sample-csv') || path.includes('sample-students-csv') || path.includes('/sample') || path.includes('/export') || path.includes('/bulk/sample') || path.includes('/samples/') || path.endsWith('.csv') || path.endsWith('.pdf') || path.includes('login') || path.includes('register') || path.includes('logout') || path.includes('password') || path.includes('captcha')) {
                return false;
            }

            return true;
        } catch (e) {
            return false;
        }
    }

    /**
     * Helper to verify if a form qualifies for SPA interception.
     */
    function shouldInterceptForm(form) {
        if (!form) return false;
        if (form.target && form.target !== '_self') return false;
        if (form.hasAttribute('data-no-spa')) return false;

        const action = form.getAttribute('action');
        if (action) {
            try {
                const urlObj = new URL(action, window.location.origin);
                if (urlObj.origin !== window.location.origin) return false;
                // Exclude auth routes, logout, or file exports
                const path = urlObj.pathname.toLowerCase();
                if (path.includes('login') || path.includes('register') || path.includes('logout') || path.includes('password') || path.includes('export')) {
                    return false;
                }
            } catch (e) {
                return false;
            }
        }

        return true;
    }

    // Intercept Link Clicks
    document.addEventListener('click', function (e) {
        if (e.defaultPrevented) return;

        const anchor = e.target.closest('a');
        if (anchor && shouldInterceptLink(anchor)) {
            // Check if user is holding ctrl/cmd/shift to open new tab
            if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;

            // Support data-confirm attribute
            if (anchor.hasAttribute('data-confirm')) {
                const confirmMsg = anchor.getAttribute('data-confirm');
                if (confirmMsg && !window.confirm(confirmMsg)) {
                    e.preventDefault();
                    return;
                }
            }

            // Auto-close mobile drawer if active in Alpine
            if (window.Alpine && document.body._x_dataStack) {
                try {
                    const rootData = Alpine.$data(document.body);
                    if (rootData && 'mobileMenuOpen' in rootData) {
                        rootData.mobileMenuOpen = false;
                    }
                } catch (_) {}
            }

            e.preventDefault();
            const preserveScroll = anchor.hasAttribute('data-preserve-scroll') ||
                anchor.closest('.pagination') !== null ||
                anchor.href.includes('page=');

            navigate(anchor.href, { pushState: true, preserveScroll: preserveScroll });
        }
    });

    // Intercept Form Submissions
    document.addEventListener('submit', function (e) {
        if (e.defaultPrevented) return;

        const form = e.target.closest('form');
        if (!form || !shouldInterceptForm(form)) return;

        // Support data-confirm attribute
        if (form.hasAttribute('data-confirm')) {
            const confirmMsg = form.getAttribute('data-confirm');
            if (confirmMsg && !window.confirm(confirmMsg)) {
                e.preventDefault();
                return;
            }
        }

        e.preventDefault();
        submitForm(form);
    });

    // Handle interactive form dropdown changes with SPA without full page reload
    document.addEventListener('change', function (e) {
        const target = e.target;
        if (target.matches('select[data-spa-filter], select.spa-auto-submit, form[data-spa-auto-submit] select')) {
            const form = target.closest('form');
            if (form && shouldInterceptForm(form)) {
                e.preventDefault();
                submitForm(form, { preserveScroll: true });
            }
        }
    });

    // Handle Browser History Navigation (Back / Forward)
    window.addEventListener('popstate', function () {
        navigate(window.location.href, { pushState: false, force: true });
    });

    // Expose programmatic SPA navigation API
    window.PrimeOSSPA = window.PrimeArcSPA = {
        navigate: navigate,
        submitForm: submitForm,
        startLoading: startLoading,
        finishLoading: finishLoading,
    };

})();
