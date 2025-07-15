function isMobile() {
    return window.innerWidth < 768;
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

            nav.style.position = 'fixed';
            nav.style.top = (isPills ? stickyThresholdPills : stickyThreshold) + 'px';
            nav.style.left = navRect.left + 'px';
            nav.style.width = navRect.width + 'px';
            nav.style.zIndex = '10';
            container._stickyActive = true;

        } else if (!shouldBeSticky && isCurrentlySticky) {
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

function initializeTabContents(container) {
    if (!container) return;

    const activeTabItem = container.querySelector('.tab-item.active');
    if (!activeTabItem) return;

    const activeTabLink = activeTabItem.querySelector('a');
    if (!activeTabLink) return;

    const tabId = activeTabLink.dataset.tabId;
    if (!tabId) return;

    const tabsId = container.dataset.tabsId;
    if (!tabsId) return;

    const tabsContentContainer = container.parentElement.querySelector(
        `[data-name="${tabsId}"]`,
    );

    if (tabsContentContainer) {
        const tabContents =
            tabsContentContainer.querySelectorAll(':scope > .tab-content');
        tabContents.forEach(function (tabContent) {
            tabContent.classList.remove('active');
            tabContent.style.display = 'none';
        });

        const targetTab = document.getElementById(tabId);
        if (targetTab) {
            targetTab.classList.add('active');
            targetTab.style.display = '';

            // Recursively initialize all nested tabs
            initializeNestedTabs(targetTab);
        }
    }
}

function initializeNestedTabs(element) {
    if (!element) return;

    // Find all nested tab containers
    const nestedContainers = element.querySelectorAll('.tabs-container');

    nestedContainers.forEach((nestedContainer) => {
        updateUnderline(nestedContainer);
        initializeTabContents(nestedContainer);

        // Handle lazy-loaded nested tabs
        initializeLazyNestedTabs(nestedContainer);

        // Also check for deeply nested containers
        const deepNested = nestedContainer.querySelectorAll('.tabs-container');
        deepNested.forEach(deepContainer => {
            updateUnderline(deepContainer);
            initializeTabContents(deepContainer);
            initializeLazyNestedTabs(deepContainer);
        });
    });
}

function initializeLazyNestedTabs(container) {
    if (!container) return;

    const activeTabItem = container.querySelector('.tab-item.active');
    if (!activeTabItem) return;

    const activeTabLink = activeTabItem.querySelector('a[hx-get]');
    if (!activeTabLink) return;

    const tabId = activeTabLink.dataset.tabId;
    const targetElement = document.getElementById(tabId);

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
        if (target && target.classList.contains('tab-content')) {
            target.innerHTML = '<div class="row gx-3 gy-3 tab-skeleton-content">' +
                '<div class="col-md-8"><div class="skeleton tabs-skeleton w-100" style="height: 200px"></div></div>' +
                '<div class="col-md-4"><div class="skeleton tabs-skeleton w-100" style="height: 200px"></div></div>' +
                '<div class="col-md-12"><div class="skeleton tabs-skeleton w-100" style="height: 200px"></div></div>' +
                '</div>';
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
        const wrapper = tabsContainer.parentElement.querySelector(
            `[data-name="${tabsContainer.dataset.tabsId}"]`,
        );

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

            initializeNestedTabs(contentEl);
            if (typeof window.refreshCharts === 'function') {
                window.refreshCharts(contentEl);
            }

            contentEl.classList.remove('lazy-content');
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

                // Verify target exists
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

    if (!link.hasAttribute('hx-get')) {
        e.preventDefault();
    }

    const container = link.closest('.tabs-container');
    if (!container) return;

    const tabsId = container.dataset.tabsId;
    const tabsContentContainer = container.parentElement.querySelector(
        `[data-name="${tabsId}"]`,
    );
    const tabId = link.dataset.tabId;

    if (tabsContentContainer) {
        const tabContents =
            tabsContentContainer.querySelectorAll(':scope > .tab-content');
        tabContents.forEach(function (tabContent) {
            tabContent.classList.remove('active');
            tabContent.style.display = 'none';
        });

        const targetTab = document.getElementById(tabId);
        if (targetTab) {
            targetTab.classList.add('active');
            targetTab.style.display = '';

            initializeNestedTabs(targetTab);
            if (typeof window.refreshCharts === 'function') {
                window.refreshCharts(targetTab);
            }

            if (targetTab.classList.contains('lazy-content') && link.hasAttribute('hx-get')) {
                if (typeof htmx !== 'undefined') {
                    htmx.on('htmx:afterSwap', function handler(evt) {
                        if (evt.detail.target === targetTab) {
                            setTimeout(() => {
                                initializeNestedTabs(targetTab);
                            }, 50);
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