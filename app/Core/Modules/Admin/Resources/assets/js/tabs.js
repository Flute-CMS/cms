function isMobile() {
    return window.innerWidth < 768;
}

const activeTabRequests = new Map();

function generateTabSkeleton() {
    return `
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
                        <div class="tabs-skeleton-card__row">
                            <div class="tabs-skeleton-card__field">
                                <div class="skeleton tabs-skeleton-card__label"></div>
                                <div class="skeleton tabs-skeleton-card__textarea"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="tabs-skeleton-card">
                    <div class="tabs-skeleton-card__header">
                        <div class="skeleton tabs-skeleton-card__icon"></div>
                        <div class="tabs-skeleton-card__title-group">
                            <div class="skeleton tabs-skeleton-card__title"></div>
                        </div>
                    </div>
                    <div class="tabs-skeleton-card__content">
                        <div class="tabs-skeleton-card__toggle">
                            <div class="skeleton tabs-skeleton-card__toggle-switch"></div>
                            <div class="skeleton tabs-skeleton-card__toggle-label"></div>
                        </div>
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
            <div class="col-md-12">
                <div class="tabs-skeleton-card">
                    <div class="tabs-skeleton-card__header">
                        <div class="skeleton tabs-skeleton-card__icon"></div>
                        <div class="tabs-skeleton-card__title-group">
                            <div class="skeleton tabs-skeleton-card__title" style="width: 35%"></div>
                            <div class="skeleton tabs-skeleton-card__subtitle" style="width: 20%"></div>
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
                            <div class="tabs-skeleton-card__field">
                                <div class="skeleton tabs-skeleton-card__label"></div>
                                <div class="skeleton tabs-skeleton-card__input"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
}

function abortPreviousTabRequest(containerId) {
    if (activeTabRequests.has(containerId)) {
        const controller = activeTabRequests.get(containerId);
        controller.abort();
        activeTabRequests.delete(containerId);
    }
}

function handleTabsFix() {
    const stickyThreshold = 0;
    const stickyThresholdPills = 10;

    if (isMobile()) return;

    document.querySelectorAll('.tabs-container').forEach(function (container) {
        const nav = container.querySelector('.tabs-nav');
        if (!nav) return;
        const scroller = container.querySelector('.tabs-nav-scroll') || nav;

        if (container.dataset.sticky === 'false') {
            const existingPlaceholder = container.querySelector('.tabs-placeholder');
            if (existingPlaceholder) {
                scroller.classList.remove('tabs-nav--sticky');
                scroller.style.position = '';
                scroller.style.top = '';
                scroller.style.left = '';
                scroller.style.width = '';
                scroller.style.zIndex = '';
                existingPlaceholder.remove();
                container._stickyActive = false;
            }
            return;
        }

        const containerRect = container.getBoundingClientRect();
        const placeholder = container.querySelector('.tabs-placeholder');
        const isCurrentlySticky = !!placeholder;
        const isPills = container.classList.contains('pills');

        const shouldBeSticky = containerRect.top <= (isPills ? stickyThresholdPills : stickyThreshold);

        if (shouldBeSticky && !isCurrentlySticky) {
            const scrollerRect = scroller.getBoundingClientRect();
            const newPlaceholder = document.createElement('div');
            newPlaceholder.className = 'tabs-placeholder';
            newPlaceholder.style.height = scroller.offsetHeight + 'px';
            container.insertBefore(newPlaceholder, scroller);

            scroller.classList.add('tabs-nav--sticky');
            scroller.style.position = 'fixed';
            scroller.style.top = (isPills ? stickyThresholdPills : stickyThreshold) + 'px';
            scroller.style.left = scrollerRect.left + 'px';
            scroller.style.width = scrollerRect.width + 'px';
            scroller.style.zIndex = '10';
            container._stickyActive = true;

        } else if (!shouldBeSticky && isCurrentlySticky) {
            scroller.classList.remove('tabs-nav--sticky');
            scroller.style.position = '';
            scroller.style.top = '';
            scroller.style.left = '';
            scroller.style.width = '';
            scroller.style.zIndex = '';
            placeholder.remove();
            container._stickyActive = false;
        } else if (isCurrentlySticky) {
            const placeholderRect = placeholder.getBoundingClientRect();
            const scrollerRect = scroller.getBoundingClientRect();
            if (Math.abs(scrollerRect.left - placeholderRect.left) > 1 || Math.abs(scrollerRect.width - placeholderRect.width) > 1) {
                scroller.style.left = placeholderRect.left + 'px';
                scroller.style.width = placeholderRect.width + 'px';
            }
        }
    });
}

function updateUnderline(container) {
    setTimeout(() => {
        const activeTab = container.querySelector('.tab-item.active');
        const underline = container.querySelector('.underline');

        if (activeTab && underline) {
            const width = activeTab.offsetWidth + 'px';
            const left = activeTab.offsetLeft + 'px';
            underline.style.width = width;
            underline.style.left = left;
        } else if (underline) {
            underline.style.width = '0px';
            underline.style.left = '0px';
        }
    }, 100);
}

function getTabSlugFromTabId(tabId) {
    if (!tabId) return null;

    const raw = tabId.startsWith('tab__') ? tabId.slice('tab__'.length) : tabId;
    const separatorIndex = raw.indexOf('__');

    // New scheme: "<tabsSlug>__<tabSlug>" (tabsSlug itself may contain underscores)
    if (separatorIndex !== -1) {
        return raw.slice(separatorIndex + 2);
    }

    // Old scheme: "<tabSlug>"
    return raw;
}

function getQueryParamKeyForContainer(container) {
    if (!container) return null;
    const tabsId = container.dataset.tabsId; // e.g. "tab__settings"
    if (!tabsId || !tabsId.startsWith('tab__')) return null;
    return 'tab-' + tabsId.slice('tab__'.length);
}

function updateUrlQueryParam(key, value) {
    if (!key) return;

    try {
        const url = new URL(window.location.href);
        if (value === null || value === undefined || value === '') {
            url.searchParams.delete(key);
        } else {
            url.searchParams.set(key, value);
        }
        url.searchParams.delete('yoyo-id');
        window.history.replaceState(window.history.state, '', url.toString());
    } catch (e) {
        // ignore
    }
}

function onTabActivated(tabContentEl) {
    if (!tabContentEl) return;

    // Re-init/refresh RichText editor (EasyMDE/CodeMirror) when tab becomes visible.
    if (!window.fluteRichTextEditor) {
        const tries = parseInt(tabContentEl.dataset.richtextInitTries || '0', 10);
        if (tries < 10) {
            tabContentEl.dataset.richtextInitTries = String(tries + 1);
            setTimeout(() => onTabActivated(tabContentEl), 120);
        }
    } else {
        try {
            const textareas = tabContentEl.querySelectorAll(
                '[data-editor="markdown"]',
            );
            if (textareas.length) {
                window.fluteRichTextEditor.initialize(textareas);

                textareas.forEach((textarea) => {
                    if (!textarea.id) return;
                    const instance =
                        window.fluteRichTextEditor.instances &&
                        window.fluteRichTextEditor.instances[textarea.id];
                    if (instance && instance.codemirror) {
                        try {
                            instance.codemirror.refresh();
                        } catch (e) {
                            // ignore
                        }
                        // Refresh again after layout settles / transitions end
                        try {
                            requestAnimationFrame(() => {
                                try {
                                    instance.codemirror.refresh();
                                } catch (e) {
                                    // ignore
                                }
                            });
                        } catch (e) {
                            // ignore
                        }
                        setTimeout(() => {
                            try {
                                instance.codemirror.refresh();
                            } catch (e) {
                                // ignore
                            }
                        }, 80);
                    }
                });
            }
        } catch (e) {
            // ignore
        }
    }

    // Optional UI inits used across admin.
    if (window.initColorPickers) {
        try {
            window.initColorPickers(tabContentEl);
        } catch (e) {
            // ignore
        }
    }
    if (window.initIconPickers) {
        try {
            window.initIconPickers(tabContentEl);
        } catch (e) {
            // ignore
        }
    }
}

function getTabsContentContainer(container) {
    if (!container) return null;

    const tabsId = container.dataset.tabsId;
    if (!tabsId) return null;

    const inside = container.querySelector(`.tabs-content[data-name="${tabsId}"]`);
    if (inside) return inside;

    // tabs-content is rendered as a sibling of tabs-container, within the same parent node.
    const parent = container.parentElement;
    if (!parent) return null;

    return (
        parent.querySelector(`.tabs-content[data-name="${tabsId}"]`) ||
        (parent.parentElement
            ? parent.parentElement.querySelector(`.tabs-content[data-name="${tabsId}"]`)
            : null) ||
        document.querySelector(`.tabs-content[data-name="${tabsId}"]`)
    );
}

function findTabContent(wrapper, tabId) {
    if (!wrapper || !tabId) return null;

    try {
        return (
            wrapper.querySelector(`:scope > #${CSS.escape(tabId)}`) ||
            wrapper.querySelector(`#${CSS.escape(tabId)}`)
        );
    } catch (e) {
        return wrapper.querySelector('#' + tabId);
    }
}

function initializeTabContents(container, guard) {
    if (!container) return;

    guard = guard || new WeakSet();
    if (guard.has(container)) return;
    guard.add(container);

    const activeTabItem = container.querySelector('.tab-item.active');
    if (!activeTabItem) return;

    const activeTabLink = activeTabItem.querySelector('a');
    if (!activeTabLink) return;

    const tabId = activeTabLink.dataset.tabId;
    if (!tabId) return;

    const tabsId = container.dataset.tabsId;
    if (!tabsId) return;

    const tabsContentContainer = getTabsContentContainer(container);

    if (tabsContentContainer) {
        const tabContents =
            tabsContentContainer.querySelectorAll(':scope > .tab-content');
        tabContents.forEach(function (tabContent) {
            tabContent.classList.remove('active');
            tabContent.style.display = 'none';
        });

        const targetTab =
            findTabContent(tabsContentContainer, tabId) ||
            document.getElementById(tabId);
        if (targetTab) {
            targetTab.classList.add('active');
            targetTab.style.display = '';
            // Mark as loaded when it becomes visible and isn't a lazy placeholder anymore.
            try {
                if (!targetTab.classList.contains('lazy-content')) {
                    targetTab.setAttribute('data-tab-loaded', '1');
                }
            } catch (_) {}

            const paramKey = getQueryParamKeyForContainer(container);
            const tabSlug = getTabSlugFromTabId(tabId);
            if (paramKey && tabSlug) {
                updateUrlQueryParam(paramKey, tabSlug);
            }

            initializeNestedTabs(targetTab, guard);
            onTabActivated(targetTab);
        }
    }
}

function initializeNestedTabs(element, guard) {
    if (!element) return;

    const nestedContainers = element.querySelectorAll('.tabs-container');

    nestedContainers.forEach((nestedContainer) => {
        updateUnderline(nestedContainer);
        initializeTabContents(nestedContainer, guard);

        initializeLazyNestedTabs(nestedContainer);
    });
}

function initializeLazyNestedTabs(container) {
    if (!container) return;

    const activeTabItem = container.querySelector('.tab-item.active');
    if (!activeTabItem) return;

    const activeTabLink = activeTabItem.querySelector('a[hx-get]');
    if (!activeTabLink) return;

    const tabId = activeTabLink.dataset.tabId;
    const wrapper = getTabsContentContainer(container);
    const targetElement =
        (wrapper ? findTabContent(wrapper, tabId) : null) ||
        document.getElementById(tabId);

    if (targetElement && targetElement.classList.contains('lazy-content')) {
        const targetSelector = activeTabLink.getAttribute('hx-target');
        const actualTarget = targetSelector ? document.querySelector(targetSelector) : targetElement;

        if (!actualTarget) {
            console.warn('HTMX target not found:', targetSelector || '#' + tabId);
            return;
        }

        if (activeTabLink.hasAttribute('hx-trigger') && activeTabLink.getAttribute('hx-trigger').includes('load')) {
            if (typeof htmx !== 'undefined') {
                htmx.trigger(activeTabLink, 'load');
            }
        } else {
            if (typeof htmx !== 'undefined') {
                const url = activeTabLink.getAttribute('hx-get');
                const target = activeTabLink.getAttribute('hx-target') || '#' + tabId;
                const swap = activeTabLink.getAttribute('hx-swap') || 'innerHTML';

                htmx.ajax('GET', url, {
                    target: target,
                    swap: swap
                }).catch(error => {
                    console.error('HTMX request failed for nested tab:', error);
                });
            }
        }
    }
}

function processAllTabs() {
    const processContainer = (container) => {
        updateUnderline(container);
        initializeTabContents(container);
    };

    const rootContainers = document.querySelectorAll('.tabs-container:not(.tabs-container .tabs-container)');
    rootContainers.forEach(processContainer);

    const allContainers = document.querySelectorAll('.tabs-container');
    allContainers.forEach(container => {
        if (!container.classList.contains('processed')) {
            processContainer(container);
            container.classList.add('processed');
        }
    });

    setTimeout(() => {
        allContainers.forEach(container => {
            container.classList.remove('processed');
        });
    }, 100);
}

let resizeTimeout;
window.addEventListener('resize', () => {
    clearTimeout(resizeTimeout);
    resizeTimeout = setTimeout(handleTabsFix, 50);
}, { passive: true });

window.addEventListener('scroll', handleTabsFix, { passive: true });

document.addEventListener('DOMContentLoaded', function () {
    processAllTabs();
    handleTabsFix();
    try {
        const url = new URL(window.location.href);
        const tabSettings = url.searchParams.get('tab-settings');
        if (tabSettings) {
            const screen = document.getElementById('screen-container');
            if (screen) {
                let hidden = screen.querySelector('input[name="tab-settings"]');
                if (!hidden) {
                    hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = 'tab-settings';
                    screen.appendChild(hidden);
                }
                hidden.value = tabSettings;
            }
        }
    } catch (_) {}
});

if (typeof htmx !== 'undefined') {
    htmx.on('htmx:targetError', function (event) {
        console.error('HTMX target error:', {
            target: event.detail.target,
            xhr: event.detail.xhr,
            element: event.target
        });

        const element = event.target;
        const targetAttr = element.getAttribute('hx-target');
        const selectAttr = element.getAttribute('hx-select');

        if (selectAttr && !document.querySelector(targetAttr)) {
            const selectTarget = document.querySelector(selectAttr);
            if (selectTarget) {
                console.log('Found select target, using as fallback');
                element.setAttribute('hx-target', selectAttr);
            }
        }
    });

    htmx.on('htmx:responseError', function (event) {
        console.error('HTMX response error:', event.detail);
    });

    // Recovery: sometimes the request succeeds but the tab stays on skeleton
    // (usually because hx-select couldn't find the element to swap).
    // In that case, extract the desired element from the response and swap manually.
    htmx.on('htmx:afterRequest', function (event) {
        try {
            const elt = event.detail.elt;
            const xhr = event.detail.xhr;
            if (!elt || !xhr) return;
            if (!elt.closest || !elt.closest('.tabs-nav')) return;

            const hxTarget = elt.getAttribute('hx-target');
            const hxSelect = elt.getAttribute('hx-select') || hxTarget;
            const url = elt.getAttribute('hx-get') || elt.getAttribute('href');

            if (!hxTarget || !hxSelect) return;

            const targetEl = document.querySelector(hxTarget);
            if (!targetEl) return;

            // Only intervene if we're still showing skeleton after a successful response.
            const ok = xhr.status >= 200 && xhr.status < 400;
            const hasSkeleton = !!targetEl.querySelector('.tab-skeleton-content');
            if (!ok || !hasSkeleton) return;

            const html = xhr.responseText || '';
            if (!html) return;

            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const selected = doc.querySelector(hxSelect);

            if (selected) {
                // Replace the whole tab pane (outerHTML) so classes/attrs update.
                targetEl.outerHTML = selected.outerHTML;

                const newTarget = document.querySelector(hxTarget);
                if (newTarget) {
                    try {
                        newTarget.classList.remove('lazy-content');
                    } catch (_) {}

                    try {
                        htmx.process(newTarget);
                    } catch (_) {}

                    // Re-sync tab UI
                    try {
                        const tabsContainer = newTarget.closest('.tabs-container');
                        if (tabsContainer) {
                            updateUnderline(tabsContainer);
                            initializeTabContents(tabsContainer);
                        }
                    } catch (_) {}
                }
            } else if (url) {
                // If we can't find the target in response, fall back to full navigation.
                window.location.assign(url);
            }
        } catch (e) {
            // ignore
        }
    });

    htmx.on('htmx:beforeRequest', function (event) {
        const target = event.detail.target;
        const elt = event.detail.elt;
        
        if (elt && elt.closest('.tabs-nav')) {
            const container = elt.closest('.tabs-container');
            if (container) {
                const containerId = container.dataset.tabsId || container.id || 'default';
                abortPreviousTabRequest(containerId);
                
                const controller = new AbortController();
                activeTabRequests.set(containerId, controller);
                
                event.detail.xhr.onreadystatechange = function() {
                    if (event.detail.xhr.readyState === 4) {
                        activeTabRequests.delete(containerId);
                    }
                };
            }
        }
        
        if (
            target &&
            target.classList.contains('tab-content') &&
            target.classList.contains('lazy-content')
        ) {
            target.innerHTML = generateTabSkeleton();
        }
    });

    htmx.on('htmx:beforeSwap', function (event) {
        const elt = event.detail.elt;
        if (!elt || !elt.closest || !elt.closest('.tabs-nav')) return;

        const hxSelect = elt.getAttribute('hx-select');
        const hxTarget = elt.getAttribute('hx-target');
        const url = elt.getAttribute('hx-get') || elt.getAttribute('href');

        if (!hxSelect || !hxTarget || !url) return;

        try {
            const html = event.detail.serverResponse;
            if (!html || typeof html !== 'string') return;

            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const selected = doc.querySelector(hxSelect);

            if (!selected) {
                try {
                    const targetEl = document.querySelector(hxTarget);
                    if (targetEl && targetEl.querySelector('.tab-skeleton-content')) {
                        targetEl.innerHTML = '';
                    }
                } catch (_) {}

                // Cancel this swap and do a full navigation instead.
                event.detail.shouldSwap = false;
                window.location.assign(url);
            }
        } catch (e) {
            // If parsing fails, don't block swap.
        }
    });
    
    htmx.on('htmx:abort', function(event) {
        const elt = event.detail.elt;
        if (elt && elt.closest('.tabs-nav')) {
            const target = document.querySelector(elt.getAttribute('hx-target'));
            if (target && target.querySelector('.tab-skeleton-content')) {
                target.innerHTML = '';
            }
        }
    });
}

htmx.on('htmx:afterSwap', function (event) {
    let swappedElement =
        event.detail && event.detail.target
            ? event.detail.target
            : event.target;

    // For `outerHTML` swaps `event.detail.target` can be a stale (disconnected) element.
    // Resolve to a live node when possible so class/style changes actually apply.
    try {
        if (swappedElement && swappedElement.id) {
            const live = document.getElementById(swappedElement.id);
            if (live) swappedElement = live;
        }
    } catch (_) {}

    if (swappedElement.classList.contains('tabs-container')) {
        updateUnderline(swappedElement);
        initializeTabContents(swappedElement);
        return;
    }

    swappedElement.querySelectorAll('.tabs-container').forEach((container) => {
        updateUnderline(container);
        initializeTabContents(container);
    });

    // When swapping tab panes via `outerHTML`, the swap target may be the parent,
    // while the real pane is determined by the triggering tab link.
    let tabContentEl = null;
    if (swappedElement && swappedElement.classList && swappedElement.classList.contains('tab-content')) {
        tabContentEl = swappedElement;
    } else {
        try {
            const trigger = event.detail ? event.detail.elt : null;
            const link = trigger && trigger.closest ? trigger.closest('.tabs-nav a') : null;
            const tabId = link && link.dataset ? link.dataset.tabId : null;
            if (tabId) {
                const el = document.getElementById(tabId);
                if (el && el.classList && el.classList.contains('tab-content')) {
                    tabContentEl = el;
                }
            }
        } catch (_) {}
    }

    if (tabContentEl) {
        const contentEl = tabContentEl;
        const tabId = contentEl.id;
        try {
            contentEl.setAttribute('data-tab-loaded', '1');
            contentEl.classList.remove('lazy-content');
        } catch (_) {}

        const headingLink = document.querySelector(
            `.tabs-nav a[data-tab-id="${tabId}"]`,
        );
        if (!headingLink) return;

        const tabsContainer = headingLink.closest('.tabs-container');
        // Race-guard: if user already clicked another tab, don't re-activate old response.
        try {
            const lastTabId = tabsContainer?.dataset?.lastTabId;
            if (lastTabId && lastTabId !== tabId) {
                contentEl.classList.remove('active');
                contentEl.style.display = 'none';
                return;
            }
        } catch (_) {}
        const wrapper = getTabsContentContainer(tabsContainer);

        if (wrapper) {
            tabsContainer
                .querySelectorAll('.tab-item')
                .forEach((li) => li.classList.remove('active'));
            headingLink.parentElement.classList.add('active');

            wrapper.querySelectorAll(':scope > .tab-content').forEach((tc) => {
                tc.classList.remove('active');
                tc.style.display = 'none';
            });
            contentEl.classList.add('active');
            contentEl.style.display = '';

            updateUnderline(tabsContainer);

            initializeNestedTabs(contentEl, new WeakSet());

            contentEl.classList.remove('lazy-content');
            onTabActivated(contentEl);
        }
    }

    setTimeout(() => {
        processAllTabs();
        handleTabsFix();
    }, 100);
});

document.addEventListener('click', function (e) {
    const link = e.target.closest('.tabs-nav a');
    if (!link) return;

    const container = link.closest('.tabs-container');
    if (!container) return;
    
    const containerId = container.dataset.tabsId || container.id || 'default';
    abortPreviousTabRequest(containerId);

    const hasHxGet = link.hasAttribute('hx-get');

    const tabsContentContainer = getTabsContentContainer(container);
    const tabId = link.dataset.tabId;
    // Race-guard: remember last intended tab (late HTMX responses won't override it)
    try {
        container.dataset.lastTabId = tabId || '';
    } catch (_) {}
    const tabSlug = getTabSlugFromTabId(tabId);
    const paramKey = getQueryParamKeyForContainer(container);

    // Persist active tab in the URL so Yoyo requests keep correct active tab after swap.
    if (paramKey && tabSlug) {
        updateUrlQueryParam(paramKey, tabSlug);
    }

    // Ensure Yoyo save receives the currently active top-level settings tab
    // even if Yoyo.url doesn't include query params (it often doesn't).
    if (paramKey === 'tab-settings' && tabSlug) {
        const screen = document.getElementById('screen-container');
        if (screen) {
            let hidden = screen.querySelector('input[name="tab-settings"]');
            if (!hidden) {
                hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'tab-settings';
                screen.appendChild(hidden);
            }
            hidden.value = tabSlug;
        }
    }

    const fallbackWrapper = (() => {
        if (tabsContentContainer) return tabsContentContainer;
        if (!tabId) return null;
        const el = document.getElementById(tabId);
        return el ? el.closest('.tabs-content') : null;
    })();

    const tabElForLoadCheck =
        (fallbackWrapper ? findTabContent(fallbackWrapper, tabId) : null) ||
        (tabId ? document.getElementById(tabId) : null);

    const isLoaded =
        !!tabElForLoadCheck &&
        tabElForLoadCheck.getAttribute('data-tab-loaded') === '1' &&
        !tabElForLoadCheck.classList.contains('lazy-content');

    // If tab is already loaded, block HTMX re-fetch and only switch UI.
    if (hasHxGet && isLoaded) {
        e.preventDefault();
        e.stopPropagation();
    } else if (!hasHxGet) {
        // Non-htmx tabs should never navigate.
        e.preventDefault();
    }

    if (fallbackWrapper) {
        const tabContents =
            fallbackWrapper.querySelectorAll(':scope > .tab-content');
        tabContents.forEach(function (tabContent) {
            tabContent.classList.remove('active');
            tabContent.style.display = 'none';
        });

        const targetTab =
            findTabContent(fallbackWrapper, tabId) ||
            document.getElementById(tabId);
        if (targetTab) {
            targetTab.classList.add('active');
            targetTab.style.display = '';
            // Mark tab content as loaded once it is shown and not a lazy placeholder.
            try {
                if (!targetTab.classList.contains('lazy-content')) {
                    targetTab.setAttribute('data-tab-loaded', '1');
                }
            } catch (_) {}

            initializeNestedTabs(targetTab, new WeakSet());
            onTabActivated(targetTab);

            if (targetTab.classList.contains('lazy-content') && link.hasAttribute('hx-get')) {
                if (typeof htmx !== 'undefined') {
                    htmx.on('htmx:afterSwap', function handler(evt) {
                        if (evt.detail.target === targetTab) {
                            setTimeout(() => {
                                initializeNestedTabs(targetTab, new WeakSet());
                                onTabActivated(targetTab);
                            }, 100);
                            htmx.off('htmx:afterSwap', handler);
                        }
                    });
                }
            }
        }
    }

    const tabItems = container.querySelectorAll('.tab-item');
    tabItems.forEach(function (tabItem) {
        tabItem.classList.remove('active');
    });

    link.parentElement.classList.add('active');

    updateUnderline(container);

    handleTabsFix();
}, true);

// (Removed old workaround that temporarily removed hx-get; we now block requests via capture + stopPropagation above.)

window.addEventListener('resize', function () {
    const containers = document.querySelectorAll('.tabs-container');
    containers.forEach(function (container) {
        updateUnderline(container);
    });
});
