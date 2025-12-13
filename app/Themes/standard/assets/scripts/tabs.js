/**
 * Tabs system (theme)
 * - Works with nested tabs
 * - Integrates with HTMX (swap, lazy loads)
 * - Cancels previous tab request per container to avoid race conditions
 */

(() => {
    if (window.__fluteThemeTabsInitialized) return;
    window.__fluteThemeTabsInitialized = true;

    const activeXhrByContainer = new Map(); // containerId -> XMLHttpRequest

    const raf = (cb) => {
        try {
            requestAnimationFrame(cb);
        } catch (e) {
            setTimeout(cb, 0);
        }
    };

    const safeAbort = (xhr) => {
        if (!xhr) return;
        try {
            if (xhr.readyState !== 4) xhr.abort();
        } catch (e) {
            // ignore
        }
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

        // HTMX keeps "once" state internally; cloning resets it.
        const clone = link.cloneNode(true);
        link.replaceWith(clone);
        try {
            htmx.process(clone);
        } catch (e) {
            // ignore
        }
        return clone;
    };

    const markTabLoaded = (tabContentEl) => {
        if (!tabContentEl) return;
        tabContentEl.setAttribute('data-tab-loaded', '1');
        tabContentEl.classList.remove('lazy-content');
    };

    const maybeMarkTabLoaded = (tabContentEl) => {
        if (!tabContentEl) return;
        if (tabContentEl.classList.contains('lazy-content')) return;
        if (tabContentEl.getAttribute('data-tab-loaded') === '1') return;

        // Heuristic: treat as loaded if it has real content (not just skeleton placeholders).
        const hasSkeleton = !!tabContentEl.querySelector(
            '.tabs-skeleton, .tab-skeleton-content',
        );
        const hasText = (tabContentEl.textContent || '').trim().length > 0;
        if (hasText && !hasSkeleton) {
            markTabLoaded(tabContentEl);
        }
    };

    const isTabLoaded = (tabContentEl) => {
        if (!tabContentEl) return false;
        if (tabContentEl.classList.contains('lazy-content')) return false;
        return tabContentEl.getAttribute('data-tab-loaded') === '1';
    };

    const TabUtils = {
        updateUnderline(container) {
            if (!container) return;
            const underline = container.querySelector('.underline');
            if (!underline) return;

            const activeTab = container.querySelector('.tab-item.active');
            if (!activeTab) {
                underline.style.width = '0px';
                underline.style.left = '0px';
                return;
            }

            // Defer to avoid layout thrash during swaps/transitions.
            raf(() => {
                underline.style.width = `${activeTab.offsetWidth}px`;
                underline.style.left = `${activeTab.offsetLeft}px`;
            });
        },

        getTabsContentContainer(container) {
            if (!container) return null;
            const tabsId = container.getAttribute('data-tabs-id');
            if (!tabsId) return null;

            // Theme supports both structures:
            // 1) wrapper inside container (e.g. notifications)
            // 2) wrapper next to container (e.g. profile page)
            const inside = container.querySelector(
                `.tabs-content[data-name="${tabsId}"]`,
            );
            if (inside) return inside;

            const parent = container.parentElement;
            const sibling =
                parent?.querySelector(`.tabs-content[data-name="${tabsId}"]`) ||
                parent?.parentElement?.querySelector(
                    `.tabs-content[data-name="${tabsId}"]`,
                );
            if (sibling) return sibling;

            // Fallback (legacy): some layouts may place wrapper elsewhere.
            return (
                document.querySelector(
                    `.tabs-content[data-name="${tabsId}"]`,
                ) || container
            );
        },

        findTabContent(wrapper, tabId) {
            if (!wrapper || !tabId) return null;
            try {
                return (
                    wrapper.querySelector(
                        `:scope > #${CSS.escape(tabId)}`,
                    ) || wrapper.querySelector(`#${CSS.escape(tabId)}`)
                );
            } catch (e) {
                return wrapper.querySelector('#' + tabId);
            }
        },

        showTab(container, tabId, guard) {
            if (!container || !tabId) return null;
            const wrapper = this.getTabsContentContainer(container);
            if (!wrapper) return null;

            const target =
                this.findTabContent(wrapper, tabId) ||
                document.getElementById(tabId);
            if (!target) return null;

            // Only direct children of the wrapper belong to this tabs instance.
            wrapper.querySelectorAll(':scope > .tab-content').forEach((tc) => {
                    tc.classList.remove('active');
                    tc.style.display = 'none';
                });

            target.classList.add('active');
            target.style.display = '';

            this.processNestedTabs(target, guard);
            maybeMarkTabLoaded(target);
            return target;
        },

        activateTabItem(tabItem) {
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

            this.updateUnderline(container);
        },

        initializeTabContents(container, guard) {
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

            this.showTab(container, tabId, guard);
            this.activateTabItem(activeTabItem);
        },

        processNestedTabs(element, guard) {
            if (!element) return;
            guard = guard || new WeakSet();

            element.querySelectorAll('.tabs-container').forEach((nested) => {
                this.updateUnderline(nested);
                this.initializeTabContents(nested, guard);
            });
        },

        processTabsIn(root) {
            const scope = root || document;
            const containers = scope.querySelectorAll
                ? scope.querySelectorAll('.tabs-container')
                : [];
            containers.forEach((container) => {
                this.updateUnderline(container);
                this.initializeTabContents(container);
            });
        },
    };

    const onTabClick = (e) => {
        const link = e.target.closest?.('.tabs-nav a');
        if (!link) return;
        if (isDisabledLink(link)) return;

        const container = link.closest('.tabs-container');
        if (!container) return;

        const tabId = link.dataset?.tabId;
        if (!tabId) return;

        const li = link.closest('.tab-item');
        if (li?.classList.contains('active')) return;

        // Always switch UI immediately (no "дерганья"), regardless of network.
        const shown = TabUtils.showTab(container, tabId);
        TabUtils.activateTabItem(li);

        // If it isn't an HTMX tab, stop navigation.
        if (!link.hasAttribute('hx-get')) {
            e.preventDefault();
            return;
        }

        // For "click once" tabs: after content is loaded, block further HTMX requests.
        const hxTrigger = link.getAttribute('hx-trigger') || '';
        const isOnce = hxTrigger.includes('once');
        const isReloadable = !isOnce; // heuristic: reloadable tabs won't use "once"

        if (shown && isOnce && isTabLoaded(shown) && !isReloadable) {
            // Prevent HTMX request; keep UI behavior.
            e.preventDefault();
            e.stopPropagation();
            return;
        }
    };

    // Capture phase to run before HTMX triggers.
    document.addEventListener('click', onTabClick, true);

    // Resize: keep underline in sync.
    let resizeTimer;
    window.addEventListener(
        'resize',
        () => {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(() => {
                document
                    .querySelectorAll('.tabs-container')
                    .forEach((c) => TabUtils.updateUnderline(c));
            }, 60);
        },
        { passive: true },
    );

    const onReady = () => TabUtils.processTabsIn(document);
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', onReady, { once: true });
    } else {
        onReady();
    }

    if (typeof htmx !== 'undefined') {
        htmx.on('htmx:beforeRequest', (event) => {
            const elt = event.detail?.elt;
            if (!elt) return;

            const link = elt.closest?.('.tabs-nav a') || elt;
            if (!link || !link.closest?.('.tabs-nav')) return;

            const container = link.closest('.tabs-container');
            if (!container) return;

            const containerId = getContainerId(container);
            abortPreviousForContainer(containerId);

            const xhr = event.detail?.xhr;
            if (xhr) {
                activeXhrByContainer.set(containerId, xhr);
            }

            // If we will potentially abort this request, ensure "once" can be retried.
            // (We will clone the element on abort.)
        });

        htmx.on('htmx:afterRequest', (event) => {
            const elt = event.detail?.elt;
            if (!elt) return;
            const container = elt.closest?.('.tabs-container');
            if (!container) return;
            const containerId = getContainerId(container);
            activeXhrByContainer.delete(containerId);
        });

        htmx.on('htmx:abort', (event) => {
            const elt = event.detail?.elt;
            if (!elt) return;

            const link = elt.closest?.('.tabs-nav a') || elt;
            if (!link || !link.closest?.('.tabs-nav')) return;

            // Reset "once" so user can come back and load again.
            resetHtmxOnceState(link);
        });

        htmx.on('htmx:responseError', (event) => {
            const elt = event.detail?.elt;
            if (!elt) return;
            const link = elt.closest?.('.tabs-nav a') || elt;
            if (!link || !link.closest?.('.tabs-nav')) return;
            resetHtmxOnceState(link);
        });

        htmx.on('htmx:afterSwap', (event) => {
            const swapped = event.detail?.target || event.target;
            if (!swapped) return;

            // If a tab content was swapped, mark it loaded and ensure heading state matches.
            if (swapped.classList?.contains('tab-content')) {
                markTabLoaded(swapped);

                const tabId = swapped.id;
                const headingLink = Array.from(
                    document.querySelectorAll('.tabs-nav a[data-tab-id]'),
                ).find((a) => a.dataset.tabId === tabId);
                if (headingLink) {
                    const li = headingLink.closest('.tab-item');
                    if (li) TabUtils.activateTabItem(li);
                }
            }

            // Re-init only inside swapped subtree (fast).
            if (swapped.classList?.contains('tabs-container')) {
                TabUtils.processTabsIn(swapped);
            } else if (swapped.querySelectorAll) {
                const containers = swapped.querySelectorAll('.tabs-container');
                if (containers.length) TabUtils.processTabsIn(swapped);
            }
        });
    }
})();
