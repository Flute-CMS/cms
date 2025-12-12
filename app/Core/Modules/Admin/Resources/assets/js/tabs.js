function isMobile() {
    return window.innerWidth < 768;
}

const activeTabRequests = new Map();

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

        if (container.dataset.sticky === 'false') {
            const existingPlaceholder = container.querySelector('.tabs-placeholder');
            if (existingPlaceholder) {
                nav.classList.remove('tabs-nav--sticky');
                nav.style.position = '';
                nav.style.top = '';
                nav.style.left = '';
                nav.style.width = '';
                nav.style.zIndex = '';
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
            const navRect = nav.getBoundingClientRect();
            const newPlaceholder = document.createElement('div');
            newPlaceholder.className = 'tabs-placeholder';
            newPlaceholder.style.height = nav.offsetHeight + 'px';
            container.insertBefore(newPlaceholder, nav);

            nav.classList.add('tabs-nav--sticky');
            nav.style.position = 'fixed';
            nav.style.top = (isPills ? stickyThresholdPills : stickyThreshold) + 'px';
            nav.style.left = navRect.left + 'px';
            nav.style.width = navRect.width + 'px';
            nav.style.zIndex = '10';
            container._stickyActive = true;

        } else if (!shouldBeSticky && isCurrentlySticky) {
            nav.classList.remove('tabs-nav--sticky');
            nav.style.position = '';
            nav.style.top = '';
            nav.style.left = '';
            nav.style.width = '';
            nav.style.zIndex = '';
            placeholder.remove();
            container._stickyActive = false;
        } else if (isCurrentlySticky) {
            const navRect = nav.getBoundingClientRect();
            const placeholderRect = placeholder.getBoundingClientRect();
            if (Math.abs(navRect.left - placeholderRect.left) > 1 || Math.abs(navRect.width - placeholderRect.width) > 1) {
                nav.style.left = placeholderRect.left + 'px';
                nav.style.width = placeholderRect.width + 'px';
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

    // tabs-content is rendered as a sibling of tabs-container, within the same parent node.
    const parent = container.parentElement;
    if (!parent) return null;

    return (
        parent.querySelector(`[data-name="${tabsId}"]`) ||
        (parent.parentElement
            ? parent.parentElement.querySelector(`[data-name="${tabsId}"]`)
            : null)
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
        
        if (target && target.classList.contains('tab-content')) {
            target.innerHTML = '<div class="row gx-3 gy-3 tab-skeleton-content">' +
                '<div class="col-md-8"><div class="skeleton tabs-skeleton w-100" style="height: 200px"></div></div>' +
                '<div class="col-md-4"><div class="skeleton tabs-skeleton w-100" style="height: 200px"></div></div>' +
                '<div class="col-md-12"><div class="skeleton tabs-skeleton w-100" style="height: 200px"></div></div>' +
                '</div>';
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
    const swappedElement =
        event.detail && event.detail.target
            ? event.detail.target
            : event.target;

    if (swappedElement.classList.contains('tabs-container')) {
        updateUnderline(swappedElement);
        initializeTabContents(swappedElement);
        return;
    }

    swappedElement.querySelectorAll('.tabs-container').forEach((container) => {
        updateUnderline(container);
        initializeTabContents(container);
    });

    if (swappedElement.classList.contains('tab-content')) {
        const contentEl = swappedElement;
        const tabId = contentEl.id;

        const headingLink = document.querySelector(
            `.tabs-nav a[data-tab-id="${tabId}"]`,
        );
        if (!headingLink) return;

        const tabsContainer = headingLink.closest('.tabs-container');
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

        document.querySelectorAll('.tab-content.active.lazy-content').forEach(lazyTab => {
            const tabId = lazyTab.id;
            const link = document.querySelector(`.tabs-nav a[data-tab-id="${tabId}"][hx-get]`);
            if (link && typeof htmx !== 'undefined') {
                const url = link.getAttribute('hx-get');
                const target = link.getAttribute('hx-target') || '#' + tabId;
                const swap = link.getAttribute('hx-swap') || 'innerHTML';

                if (document.querySelector(target)) {
                    htmx.ajax('GET', url, {
                        target: target,
                        swap: swap
                    }).catch(error => {
                        console.error('Failed to load lazy tab content:', error);
                    });
                }
            }
        });
    }, 100);
});

document.addEventListener('click', function (e) {
    const link = e.target.closest('.tabs-nav a');
    if (!link) return;

    const container = link.closest('.tabs-container');
    if (!container) return;
    
    const containerId = container.dataset.tabsId || container.id || 'default';
    abortPreviousTabRequest(containerId);

    if (!link.hasAttribute('hx-get')) {
        e.preventDefault();
    }

    const tabsContentContainer = getTabsContentContainer(container);
    const tabId = link.dataset.tabId;
    const tabSlug = getTabSlugFromTabId(tabId);
    const paramKey = getQueryParamKeyForContainer(container);

    // Persist active tab in the URL so Yoyo requests keep correct active tab after swap.
    if (paramKey && tabSlug) {
        updateUrlQueryParam(paramKey, tabSlug);
    }

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
});

window.addEventListener('resize', function () {
    const containers = document.querySelectorAll('.tabs-container');
    containers.forEach(function (container) {
        updateUnderline(container);
    });
});
