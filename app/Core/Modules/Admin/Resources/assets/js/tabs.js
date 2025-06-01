
function isMobile() {
    return window.innerWidth < 768;
}

function handleTabsFix() {
    const stickyThreshold = 0;
    const stickyThresholdPills = 10;
    console.log('fix')

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
            tabsContentContainer.querySelectorAll('.tab-content');
        tabContents.forEach(function (tabContent) {
            tabContent.classList.remove('active');
            tabContent.style.display = 'none';
        });

        const targetTab = document.getElementById(tabId);
        if (targetTab) {
            targetTab.classList.add('active');
            targetTab.style.display = '';

            targetTab
                .querySelectorAll('.tabs-container')
                .forEach((nestedContainer) => {
                    updateUnderline(nestedContainer);
                    initializeTabContents(nestedContainer);
                });
        }
    }
}

function processAllTabs() {
    const processContainer = (container) => {
        updateUnderline(container);
        initializeTabContents(container);
    };

    const rootContainers = document.querySelectorAll('.tabs-container');
    rootContainers.forEach(processContainer);
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

    processAllTabs();

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

        tabsContainer
            .querySelectorAll('.tab-item')
            .forEach((li) => li.classList.remove('active'));
        headingLink.parentElement.classList.add('active');

        wrapper.querySelectorAll('.tab-content').forEach((tc) => {
            tc.classList.remove('active');
            tc.style.display = 'none';
        });
        contentEl.classList.add('active');
        contentEl.style.display = '';

        updateUnderline(tabsContainer);

        contentEl
            .querySelectorAll('.tabs-container')
            .forEach((nestedContainer) => {
                updateUnderline(nestedContainer);
                initializeTabContents(nestedContainer);
            });
    }

    setTimeout(() => {
        handleTabsFix();
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
            tabsContentContainer.querySelectorAll('.tab-content');
        tabContents.forEach(function (tabContent) {
            tabContent.classList.remove('active');
            tabContent.style.display = 'none';
        });

        const targetTab = document.getElementById(tabId);
        if (targetTab) {
            targetTab.classList.add('active');
            targetTab.style.display = '';

            targetTab
                .querySelectorAll('.tabs-container')
                .forEach((nestedContainer) => {
                    updateUnderline(nestedContainer);
                    initializeTabContents(nestedContainer);
                });
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
