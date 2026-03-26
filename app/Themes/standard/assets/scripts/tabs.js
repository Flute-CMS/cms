/**
 * Tabs system (theme)
 * - Supports variants: underline (default), pills, segment
 * - Works with nested tabs
 * - Integrates with HTMX (swap, lazy loads)
 * - Cancels previous tab request per container to avoid race conditions
 * - Keyboard navigation (arrow keys, Home/End)
 */

(() => {
    if (window.__fluteThemeTabsInitialized) return;
    window.__fluteThemeTabsInitialized = true;

    const activeXhrByContainer = new Map();
    const lastActivatedTab = new Map();

    const raf = (cb) => requestAnimationFrame(cb);

    const safeAbort = (xhr) => {
        if (!xhr) return;
        try {
            if (xhr.readyState !== 4) xhr.abort();
        } catch (_) {}
    };

    const getContainerId = (container) =>
        container?.getAttribute('data-tabs-id') || container?.id || 'default';

    const abortPreviousForContainer = (containerId) => {
        const prev = activeXhrByContainer.get(containerId);
        if (prev) safeAbort(prev);
        activeXhrByContainer.delete(containerId);
    };

    const isDisabledLink = (link) => {
        if (!link) return true;
        const li = link.closest('.tab-item');
        return (
            link.getAttribute('aria-disabled') === 'true' ||
            li?.classList.contains('is-disabled')
        );
    };

    const resetHtmxOnceState = (link) => {
        if (!link || typeof htmx === 'undefined') return link;
        const hxTrigger = link.getAttribute('hx-trigger') || '';
        if (!hxTrigger.includes('once')) return link;

        const clone = link.cloneNode(true);
        link.replaceWith(clone);
        try {
            htmx.process(clone);
        } catch (_) {}
        return clone;
    };

    const markTabLoaded = (el) => {
        if (!el) return;
        el.setAttribute('data-tab-loaded', '1');
        el.classList.remove('lazy-content');
    };

    const isTabLoaded = (el) => {
        if (!el) return false;
        if (el.classList.contains('lazy-content')) return false;
        return el.getAttribute('data-tab-loaded') === '1';
    };

    const maybeMarkTabLoaded = (el) => {
        if (!el || el.classList.contains('lazy-content') || isTabLoaded(el)) return;
        const hasSkeleton = !!el.querySelector('.tabs-skeleton, .tab-skeleton-content');
        const hasText = (el.textContent || '').trim().length > 0;
        if (hasText && !hasSkeleton) markTabLoaded(el);
    };

    // --- Underline variant ---

    const hasUnderline = (container) =>
        !container.classList.contains('pills') &&
        !container.classList.contains('segment');

    const updateUnderline = (container) => {
        if (!container || !hasUnderline(container)) return;
        const underline = container.querySelector('.underline');
        if (!underline) return;

        const activeTab = container.querySelector('.tab-item.active');
        if (!activeTab) {
            underline.style.width = '0px';
            underline.style.left = '0px';
            return;
        }

        raf(() => {
            underline.style.width = `${activeTab.offsetWidth}px`;
            underline.style.left = `${activeTab.offsetLeft}px`;
        });
    };

    // --- Tab content management ---

    const getTabsContentContainer = (container) => {
        if (!container) return null;
        const tabsId = container.getAttribute('data-tabs-id');
        if (!tabsId) return null;

        const inside = container.querySelector(`.tabs-content[data-name="${tabsId}"]`);
        if (inside) return inside;

        const parent = container.parentElement;
        const sibling =
            parent?.querySelector(`.tabs-content[data-name="${tabsId}"]`) ||
            parent?.parentElement?.querySelector(`.tabs-content[data-name="${tabsId}"]`);
        if (sibling) return sibling;

        return (
            document.querySelector(`.tabs-content[data-name="${tabsId}"]`) ||
            container
        );
    };

    const findTabContent = (wrapper, tabId) => {
        if (!wrapper || !tabId) return null;
        try {
            return (
                wrapper.querySelector(`:scope > #${CSS.escape(tabId)}`) ||
                wrapper.querySelector(`#${CSS.escape(tabId)}`)
            );
        } catch (_) {
            return wrapper.querySelector('#' + tabId);
        }
    };

    const showTab = (container, tabId, guard) => {
        if (!container || !tabId) return null;
        const wrapper = getTabsContentContainer(container);
        if (!wrapper) return null;

        const target = findTabContent(wrapper, tabId) || document.getElementById(tabId);
        if (!target) return null;

        wrapper.querySelectorAll(':scope > .tab-content').forEach((tc) => {
            tc.classList.remove('active');
            tc.style.display = 'none';
        });

        target.classList.add('active');
        target.style.display = '';

        processNestedTabs(target, guard);
        maybeMarkTabLoaded(target);
        return target;
    };

    const activateTabItem = (tabItem) => {
        if (!tabItem) return;
        const container = tabItem.closest('.tabs-container');
        if (!container) return;

        container.querySelectorAll('.tab-item').forEach((li) => {
            li.classList.remove('active');
            const a = li.querySelector('a[role="tab"], a');
            if (a) {
                a.setAttribute('aria-selected', 'false');
                a.setAttribute('tabindex', '-1');
            }
        });

        tabItem.classList.add('active');
        const activeLink = tabItem.querySelector('a[role="tab"], a');
        if (activeLink) {
            activeLink.setAttribute('aria-selected', 'true');
            activeLink.setAttribute('tabindex', '0');
        }

        updateUnderline(container);
    };

    const initializeTabContents = (container, guard) => {
        if (!container) return;
        guard = guard || new WeakSet();
        if (guard.has(container)) return;
        guard.add(container);

        const activeTabItem =
            container.querySelector('.tab-item.active') ||
            container.querySelector('.tab-item');
        if (!activeTabItem) return;

        if (!activeTabItem.classList.contains('active')) {
            activeTabItem.classList.add('active');
        }

        const link = activeTabItem.querySelector('a');
        const tabId = link?.dataset?.tabId;
        if (!tabId) return;

        showTab(container, tabId, guard);
        activateTabItem(activeTabItem);
    };

    const processNestedTabs = (element, guard) => {
        if (!element) return;
        guard = guard || new WeakSet();
        element.querySelectorAll('.tabs-container').forEach((nested) => {
            updateUnderline(nested);
            initializeTabContents(nested, guard);
        });
    };

    const processTabsIn = (root) => {
        const scope = root || document;
        const containers = scope.querySelectorAll?.('.tabs-container') || [];
        containers.forEach((container) => {
            updateUnderline(container);
            initializeTabContents(container);
        });
    };

    // --- Skeleton ---

    const generateTabSkeleton = () => `
        <div class="row gx-3 gy-3 tab-skeleton-content">
            <div class="col-md-8">
                <div class="tabs-skeleton-card">
                    <div class="tabs-skeleton-card__header">
                        <div class="skeleton tabs-skeleton-card__icon"></div>
                        <div class="tabs-skeleton-card__title-group">
                            <div class="skeleton tabs-skeleton-card__title"></div>
                            <div class="skeleton tabs-skeleton-card__subtitle"></div>
                        </div>
                    </div>
                    <div class="tabs-skeleton-card__content">
                        <div class="tabs-skeleton-card__row tabs-skeleton-card__row--split">
                            <div class="tabs-skeleton-card__field">
                                <div class="skeleton tabs-skeleton-card__label"></div>
                                <div class="skeleton tabs-skeleton-card__input"></div>
                            </div>
                            <div class="tabs-skeleton-card__field">
                                <div class="skeleton tabs-skeleton-card__label"></div>
                                <div class="skeleton tabs-skeleton-card__input"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="tabs-skeleton-card">
                    <div class="tabs-skeleton-card__content">
                        <div class="tabs-skeleton-card__toggle">
                            <div class="skeleton tabs-skeleton-card__toggle-switch"></div>
                            <div class="skeleton tabs-skeleton-card__toggle-label"></div>
                        </div>
                        <div class="tabs-skeleton-card__toggle">
                            <div class="skeleton tabs-skeleton-card__toggle-switch"></div>
                            <div class="skeleton tabs-skeleton-card__toggle-label"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;

    // --- URL push ---

    const updatePushUrl = (container, link) => {
        if (!container || !link) return;
        const shouldPushUrl =
            container.getAttribute('hx-push-url') === 'true' ||
            link.hasAttribute('hx-push-url');
        if (!shouldPushUrl) return;

        const url = link.getAttribute('hx-get') || link.getAttribute('href');
        if (!url) return;

        try {
            const newUrl = new URL(url, window.location.origin);
            if (newUrl.href !== window.location.href) {
                window.history.pushState({}, '', newUrl.href);
            }
        } catch (_) {}
    };

    // --- Click handler ---

    const onTabClick = (e) => {
        const link = e.target.closest?.('.tabs-nav a');
        if (!link) return;
        if (isDisabledLink(link)) return;

        const container = link.closest('.tabs-container');
        if (!container) return;

        const tabId = link.dataset?.tabId;
        if (!tabId) return;

        const li = link.closest('.tab-item');
        const isReloadable = link.dataset?.reloadable === 'true';

        if (li?.classList.contains('active') && !isReloadable) return;
        if (li?.classList.contains('active') && isReloadable) return;

        const containerId = getContainerId(container);
        lastActivatedTab.set(containerId, tabId);

        showTab(container, tabId);
        activateTabItem(li);

        if (!link.hasAttribute('hx-get') || !link.hasAttribute('hx-push-url')) {
            updatePushUrl(container, link);
        }

        if (!link.hasAttribute('hx-get')) {
            e.preventDefault();
            return;
        }

        const target =
            findTabContent(getTabsContentContainer(container), tabId) ||
            document.getElementById(tabId);
        if (target && isTabLoaded(target)) {
            e.preventDefault();
            e.stopPropagation();
            return;
        }
    };

    document.addEventListener('click', onTabClick, true);

    // --- Keyboard navigation ---

    document.addEventListener('keydown', (e) => {
        const link = e.target.closest?.('.tabs-nav a');
        if (!link) return;

        const nav = link.closest('.tabs-nav');
        if (!nav) return;

        const items = Array.from(nav.querySelectorAll('.tab-item:not(.is-disabled) a'));
        const idx = items.indexOf(link);
        if (idx === -1) return;

        let next;
        switch (e.key) {
            case 'ArrowRight':
            case 'ArrowDown':
                next = items[(idx + 1) % items.length];
                break;
            case 'ArrowLeft':
            case 'ArrowUp':
                next = items[(idx - 1 + items.length) % items.length];
                break;
            case 'Home':
                next = items[0];
                break;
            case 'End':
                next = items[items.length - 1];
                break;
            default:
                return;
        }

        e.preventDefault();
        next?.focus();
        next?.click();
    });

    // --- Resize ---

    let resizeTimer;
    window.addEventListener(
        'resize',
        () => {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(() => {
                document
                    .querySelectorAll('.tabs-container')
                    .forEach(updateUnderline);
            }, 60);
        },
        { passive: true },
    );

    // --- Init ---

    const onReady = () => processTabsIn(document);
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', onReady, { once: true });
    } else {
        onReady();
    }

    // --- HTMX integration ---

    if (typeof htmx !== 'undefined') {
        htmx.on('htmx:beforeRequest', (event) => {
            const elt = event.detail?.elt;
            if (!elt) return;

            const link = elt.closest?.('.tabs-nav a') || elt;
            if (!link?.closest?.('.tabs-nav')) return;

            const container = link.closest('.tabs-container');
            if (!container) return;

            const containerId = getContainerId(container);
            abortPreviousForContainer(containerId);

            const xhr = event.detail?.xhr;
            if (xhr) activeXhrByContainer.set(containerId, xhr);
        });

        htmx.on('htmx:afterRequest', (event) => {
            const elt = event.detail?.elt;
            if (!elt) return;
            const container = elt.closest?.('.tabs-container');
            if (!container) return;
            activeXhrByContainer.delete(getContainerId(container));
        });

        htmx.on('htmx:abort', (event) => {
            const elt = event.detail?.elt;
            if (!elt) return;

            const link = elt.closest?.('.tabs-nav a') || elt;
            if (!link?.closest?.('.tabs-nav')) return;

            resetHtmxOnceState(link);
        });

        htmx.on('htmx:responseError', (event) => {
            const elt = event.detail?.elt;
            if (!elt) return;
            const link = elt.closest?.('.tabs-nav a') || elt;
            if (!link?.closest?.('.tabs-nav')) return;
            resetHtmxOnceState(link);
        });

        htmx.on('htmx:afterSwap', (event) => {
            const swapped = event.detail?.target || event.target;
            if (!swapped) return;

            if (swapped.classList?.contains('tab-content')) {
                const tabId = swapped.id;

                const headingLink = Array.from(
                    document.querySelectorAll('.tabs-nav a[data-tab-id]'),
                ).find((a) => a.dataset.tabId === tabId);

                if (headingLink) {
                    const container = headingLink.closest('.tabs-container');
                    const containerId = getContainerId(container);
                    const lastTab = lastActivatedTab.get(containerId);

                    if (lastTab && lastTab !== tabId) {
                        swapped.classList.remove('active');
                        swapped.style.display = 'none';
                        return;
                    }

                    markTabLoaded(swapped);
                    const li = headingLink.closest('.tab-item');
                    if (li) activateTabItem(li);
                } else {
                    markTabLoaded(swapped);
                }
            }

            if (swapped.classList?.contains('tabs-container')) {
                processTabsIn(swapped);
            } else if (swapped.querySelectorAll) {
                const containers = swapped.querySelectorAll('.tabs-container');
                if (containers.length) processTabsIn(swapped);
            }
        });

        htmx.on('htmx:afterSettle', () => {
            raf(() => {
                document
                    .querySelectorAll('.tabs-container')
                    .forEach(updateUnderline);
            });
        });

        htmx.on('htmx:historyRestore', () => {
            raf(() => {
                document.querySelectorAll('.tabs-container').forEach((c) => {
                    updateUnderline(c);
                    initializeTabContents(c);
                });
            });
        });
    }
})();
