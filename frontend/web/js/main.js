'use strict';

(function () {
    const toggle = document.querySelector('[data-menu-toggle]');
    const panel = document.querySelector('[data-menu-panel]');
    if (!toggle || !panel) {
        return;
    }

    const iconOpen = toggle.querySelector('[data-menu-icon-open]');
    const iconClose = toggle.querySelector('[data-menu-icon-close]');

    function setOpen(open) {
        panel.hidden = !open;
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        toggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
        if (iconOpen) iconOpen.hidden = open;
        if (iconClose) iconClose.hidden = !open;
    }

    toggle.addEventListener('click', () => setOpen(panel.hidden));

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !panel.hidden) setOpen(false);
    });

    // Close the menu if the viewport grows to the desktop breakpoint.
    const desktop = window.matchMedia('(min-width: 1024px)');
    desktop.addEventListener('change', (e) => {
        if (e.matches) setOpen(false);
    });
})();

(function () {
    const modal = document.querySelector('[data-search-modal]');
    if (!modal) {
        return;
    }

    const triggers = document.querySelectorAll('[data-search-trigger]');
    const panel = modal.querySelector('[data-search-panel]');
    const input = modal.querySelector('[data-search-input]');
    const spinner = modal.querySelector('[data-search-spinner]');
    const closeBtn = modal.querySelector('[data-search-close]');
    const resultsEl = modal.querySelector('[data-search-results]');
    const idleEl = modal.querySelector('[data-search-state="idle"]');
    const emptyEl = modal.querySelector('[data-search-state="empty"]');
    const endpoint = modal.dataset.searchUrl || '/autocomplete/search';
    const trendingEndpoint = modal.dataset.trendingUrl || '/autocomplete/trending';
    const trackEndpoint = modal.dataset.trackUrl || '/autocomplete/track';

    const DEBOUNCE_MS = 180;
    const MIN_CHARS = 2;

    let debounceTimer = null;
    let inflight = null;
    let currentToken = 0;
    let items = [];
    let activeIndex = -1;
    let lastQuery = '';
    let trendingData = null;
    let trendingLoading = false;

    function openModal() {
        if (!modal.hidden) return;
        modal.hidden = false;
        document.documentElement.style.overflow = 'hidden';
        requestAnimationFrame(() => input.focus());
        if (input.value.trim().length >= MIN_CHARS) {
            scheduleSearch(input.value);
        } else {
            ensureTrending();
        }
    }

    function ensureTrending() {
        if (trendingData) {
            renderTrending();
            return;
        }
        if (trendingLoading) return;
        trendingLoading = true;

        fetch(trendingEndpoint, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            credentials: 'same-origin',
        })
            .then((res) => res.ok ? res.json() : Promise.reject(new Error('Network error')))
            .then((data) => {
                trendingData = data;
                if (input.value.trim().length < MIN_CHARS) {
                    renderTrending();
                }
            })
            .catch(() => { /* silently fall back to idle state */ })
            .finally(() => { trendingLoading = false; });
    }

    function renderTrending() {
        if (!trendingData || !trendingData.groups || !trendingData.groups.length) {
            setState('idle');
            return;
        }
        buildResults({ query: '', groups: trendingData.groups });
    }

    function trackClick(steamAppid) {
        if (!steamAppid) return;

        const tokenMeta = document.querySelector('meta[name="csrf-token"]');
        const token = tokenMeta ? tokenMeta.getAttribute('content') : '';

        const headers = {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest',
        };
        if (token) {
            headers['X-CSRF-Token'] = token;
        }

        fetch(trackEndpoint, {
            method: 'POST',
            headers: headers,
            credentials: 'same-origin',
            body: 'steam_appid=' + encodeURIComponent(steamAppid),
            keepalive: true,
        }).catch(() => {});
    }

    function closeModal() {
        if (modal.hidden) return;
        modal.hidden = true;
        document.documentElement.style.overflow = '';
    }

    function setSpinner(on) {
        if (on) spinner.hidden = false;
        else spinner.hidden = true;
    }

    function setState(state) {
        idleEl.hidden = state !== 'idle';
        emptyEl.hidden = state !== 'empty';
        resultsEl.hidden = state !== 'results';
    }

    function escapeHtml(value) {
        if (value == null) return '';
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function highlight(text, query) {
        if (!text) return '';
        const safe = escapeHtml(text);
        if (!query) return safe;
        const tokens = query
            .split(/\s+/)
            .filter((t) => t.length >= 2)
            .map((t) => t.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'));
        if (!tokens.length) return safe;
        const re = new RegExp('(' + tokens.join('|') + ')', 'ig');
        return safe.replace(re, '<mark class="bg-transparent text-accent font-semibold">$1</mark>');
    }

    function buildResults(data) {
        items = [];
        const query = data.query || '';
        const groups = data.groups || [];

        if (!groups.length) {
            resultsEl.innerHTML = '';
            setState('empty');
            return;
        }

        const html = groups.map((group) => {
            const groupHtml = group.items.map((item) => {
                const index = items.length;
                items.push(item);

                const visual = item.image
                    ? `<img src="${escapeHtml(item.image)}" alt="${escapeHtml(item.title)}" loading="lazy" class="h-10 w-16 object-cover rounded-md bg-surface-2 shrink-0">`
                    : `<span class="h-10 w-10 grid place-items-center rounded-md bg-surface-2 text-fg-subtle shrink-0">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M4 7h16M4 12h10M4 17h16"></path>
                            </svg>
                       </span>`;

                const subtitle = item.subtitle
                    ? `<div class="text-xs text-fg-subtle truncate">${escapeHtml(item.subtitle)}</div>`
                    : '';

                const badge = item.badge
                    ? `<span class="ml-auto text-[11px] font-mono text-fg-subtle shrink-0">${escapeHtml(item.badge)}</span>`
                    : '';

                const price = item.price
                    ? `<span class="ml-auto flex items-center gap-1.5 shrink-0 tabular-nums">
                        ${item.discount ? `<span class="rounded bg-accent/10 px-1.5 py-0.5 text-[10px] font-semibold text-accent">-${item.discount}%</span>` : ''}
                        ${item.price_original ? `<span class="text-[11px] text-fg-subtle line-through">${escapeHtml(item.price_original)}</span>` : ''}
                        <span class="text-xs font-semibold text-fg">${escapeHtml(item.price)}</span>
                       </span>`
                    : badge;

                const trackAttr = item.steam_appid
                    ? `data-steam-appid="${escapeHtml(item.steam_appid)}"`
                    : '';

                return `
                    <a href="${escapeHtml(item.url)}"
                       data-search-result
                       data-index="${index}"
                       ${trackAttr}
                       class="flex items-center gap-3 px-5 py-2.5 transition group">
                        ${visual}
                        <div class="min-w-0 flex-1">
                            <div class="result-title text-sm font-medium text-fg truncate transition-colors">${highlight(item.title, query)}</div>
                            ${subtitle}
                        </div>
                        ${price}
                        <svg class="result-arrow h-4 w-4 text-accent shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                            <polyline points="12 5 19 12 12 19"></polyline>
                        </svg>
                    </a>
                `;
            }).join('');

            return `
                <section class="py-2">
                    <div class="px-5 pt-2 pb-1 text-[10px] font-mono font-medium uppercase tracking-[0.18em] text-fg-subtle">${escapeHtml(group.label)}</div>
                    <div class="flex flex-col">${groupHtml}</div>
                </section>
            `;
        }).join('<div class="border-t border-line/70 mx-5"></div>');

        resultsEl.innerHTML = html;
        setState('results');
        setActive(0);
    }

    function setActive(index) {
        const nodes = resultsEl.querySelectorAll('[data-search-result]');
        if (!nodes.length) {
            activeIndex = -1;
            return;
        }
        if (index < 0) index = nodes.length - 1;
        if (index >= nodes.length) index = 0;
        activeIndex = index;

        nodes.forEach((node) => node.removeAttribute('data-active'));
        const active = nodes[activeIndex];
        if (active) {
            active.setAttribute('data-active', 'true');
            active.scrollIntoView({ block: 'nearest' });
        }
    }

    function moveActive(delta) {
        if (activeIndex === -1) {
            setActive(delta > 0 ? 0 : -1);
        } else {
            setActive(activeIndex + delta);
        }
    }

    function openActive() {
        const nodes = resultsEl.querySelectorAll('[data-search-result]');
        const active = nodes[activeIndex];
        if (active) {
            trackClick(active.getAttribute('data-steam-appid'));
            window.location.href = active.getAttribute('href');
        }
    }

    function scheduleSearch(value) {
        clearTimeout(debounceTimer);
        const q = value.trim();

        if (q.length < MIN_CHARS) {
            cancelInflight();
            setSpinner(false);
            items = [];
            activeIndex = -1;
            lastQuery = '';
            if (trendingData) {
                renderTrending();
            } else {
                setState('idle');
                ensureTrending();
            }
            return;
        }

        debounceTimer = setTimeout(() => runSearch(q), DEBOUNCE_MS);
    }

    function cancelInflight() {
        if (inflight && typeof inflight.abort === 'function') {
            inflight.abort();
        }
        inflight = null;
    }

    function runSearch(q) {
        if (q === lastQuery) return;
        lastQuery = q;
        cancelInflight();

        const token = ++currentToken;
        setSpinner(true);

        const url = endpoint + (endpoint.indexOf('?') === -1 ? '?' : '&') + 'q=' + encodeURIComponent(q);
        const controller = typeof AbortController !== 'undefined' ? new AbortController() : null;
        inflight = controller;

        fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            credentials: 'same-origin',
            signal: controller ? controller.signal : undefined,
        })
            .then((res) => res.ok ? res.json() : Promise.reject(new Error('Network error')))
            .then((data) => {
                if (token !== currentToken) return;
                setSpinner(false);
                buildResults(data);
            })
            .catch((err) => {
                if (err && err.name === 'AbortError') return;
                if (token !== currentToken) return;
                setSpinner(false);
                setState('empty');
            });
    }

    triggers.forEach((btn) => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            openModal();
        });
    });

    if (closeBtn) {
        closeBtn.addEventListener('click', closeModal);
    }

    modal.addEventListener('mousedown', (e) => {
        if (e.target === modal) closeModal();
    });

    input.addEventListener('input', (e) => {
        scheduleSearch(e.target.value);
    });

    resultsEl.addEventListener('mousemove', (e) => {
        const link = e.target.closest('[data-search-result]');
        if (!link) return;
        const idx = parseInt(link.getAttribute('data-index'), 10);
        if (!Number.isNaN(idx) && idx !== activeIndex) {
            setActive(idx);
        }
    });

    resultsEl.addEventListener('click', (e) => {
        const link = e.target.closest('[data-search-result]');
        if (!link) return;
        trackClick(link.getAttribute('data-steam-appid'));
    });

    document.addEventListener('keydown', (e) => {
        const isMac = navigator.platform.toUpperCase().indexOf('MAC') >= 0;
        const cmd = isMac ? e.metaKey : e.ctrlKey;

        if (cmd && (e.key === 'k' || e.key === 'K')) {
            e.preventDefault();
            if (modal.hidden) openModal();
            else closeModal();
            return;
        }

        if (modal.hidden) return;

        if (e.key === 'Escape') {
            e.preventDefault();
            closeModal();
        } else if (e.key === 'ArrowDown') {
            e.preventDefault();
            moveActive(1);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            moveActive(-1);
        } else if (e.key === 'Enter') {
            if (activeIndex >= 0) {
                e.preventDefault();
                openActive();
            }
        }
    });
})();

// Logged-in user dropdown (avatar button -> library / wishlist / achievements).
(function () {
    const root = document.querySelector('[data-user-menu]');
    if (!root) {
        return;
    }

    const button = root.querySelector('[data-user-menu-button]');
    const panel = root.querySelector('[data-user-menu-panel]');
    const chevron = root.querySelector('[data-user-menu-chevron]');
    if (!button || !panel) {
        return;
    }

    // Closed state: faded out, nudged up and non-interactive. Toggling these
    // (rather than the `hidden` attribute) lets the transition run both ways.
    const closedClasses = ['opacity-0', '-translate-y-1', 'pointer-events-none'];

    function setOpen(open) {
        if (open) {
            panel.classList.remove(...closedClasses);
        } else {
            panel.classList.add(...closedClasses);
        }
        button.setAttribute('aria-expanded', open ? 'true' : 'false');
        panel.setAttribute('aria-hidden', open ? 'false' : 'true');
        if (chevron) chevron.classList.toggle('rotate-180', open);
    }

    function isOpen() {
        return button.getAttribute('aria-expanded') === 'true';
    }

    button.addEventListener('click', (e) => {
        e.stopPropagation();
        setOpen(!isOpen());
    });

    // Click outside closes; clicks inside the panel (e.g. the sign-out form) don't.
    document.addEventListener('click', (e) => {
        if (isOpen() && !root.contains(e.target)) {
            setOpen(false);
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && isOpen()) {
            setOpen(false);
            button.focus();
        }
    });
})();

// Sticky in-page section nav (scrollspy) — e.g. the profile dashboard.
// Highlights the pill whose section is currently in view.
(function () {
    var nav = document.querySelector('[data-scrollspy]');
    if (!nav || !('IntersectionObserver' in window)) {
        return;
    }

    var links = {};
    nav.querySelectorAll('[data-scrollspy-link]').forEach(function (link) {
        links[link.getAttribute('data-scrollspy-link')] = link;
    });

    var sections = Object.keys(links)
        .map(function (id) { return document.getElementById(id); })
        .filter(Boolean);
    if (sections.length === 0) {
        return;
    }

    function setActive(id) {
        Object.keys(links).forEach(function (key) {
            links[key].setAttribute('data-active', key === id ? 'true' : 'false');
        });
    }

    // Top margin clears the sticky site header + this nav; the big bottom margin
    // means a section counts as "active" once its top reaches the upper band.
    var observer = new IntersectionObserver(function (entries) {
        var inView = entries
            .filter(function (e) { return e.isIntersecting; })
            .sort(function (a, b) { return a.boundingClientRect.top - b.boundingClientRect.top; });
        if (inView.length) {
            setActive(inView[0].target.id);
        }
    }, { rootMargin: '-120px 0px -55% 0px', threshold: 0 });

    sections.forEach(function (section) { observer.observe(section); });
})();
