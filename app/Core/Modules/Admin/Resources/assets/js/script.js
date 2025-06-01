// Initialize Notyf for notifications
var notyf = new Notyf({
    duration: 4000,
    position: { x: 'right', y: 'bottom' },
    dismissible: true,
    ripple: false,
    types: [
        { type: 'success', background: 'var(--success-light)' },
        { type: 'warning', background: 'var(--warning-light)' },
        { type: 'error', background: 'var(--error-light)' },
        { type: 'info', background: 'var(--info-light)' },
    ],
});

// Handle toast messages from HTMX responses
function handleToasts(evt) {
    const toastsHeader = evt.detail.xhr.getResponseHeader('X-Toasts');
    if (toastsHeader) {
        const toasts = JSON.parse(toastsHeader);
        toasts.forEach(displayToast);
    }
}

// Display a toast notification
function displayToast(toast) {
    const options = {};

    if (toast.type) {
        options.type = toast.type;
    }
    if (toast.message) {
        options.message = toast.message;
    }
    if (toast.duration) {
        options.duration = toast.duration;
    }
    if (toast.dismissible) {
        options.dismissible = toast.dismissible;
    }
    if (toast.ripple) {
        options.ripple = toast.ripple;
    }
    if (toast.position) {
        options.position = toast.position;
    }
    if (toast.icon) {
        options.icon = toast.icon;
    }
    if (toast.className) {
        options.className = toast.className;
    }

    if (toast.events) {
        const eventHandlers = {};
        Object.entries(toast.events).forEach(([eventName, handler]) => {
            eventHandlers[eventName] = () => {
                new Function(handler)();
            };
        });

        Object.entries(eventHandlers).forEach(([eventName, handlerFn]) => {
            notyf.on(eventName, handlerFn);
        });
    }

    notyf.open(options);
}

// Show NProgress during HTMX requests
var nprogressTimeout;

function handleNProgress(event, action) {
    const PROGRESS_DELAY = 150;
    const triggerElement = event.detail.elt;
    const xhr = event.detail.xhr;

    if (!triggerElement.hasAttribute('data-noprogress') && xhr.status !== 304) {
        if (action === 'start') {
            if (!nprogressTimeout) {
                nprogressTimeout = setTimeout(() => {
                    NProgress.start();
                    nprogressTimeout = null;
                }, PROGRESS_DELAY);
            }
        } else if (action === 'done') {
            clearTimeout(nprogressTimeout);
            nprogressTimeout = null;
            NProgress.done();
        }
    }
}

htmx.on('htmx:beforeSwap', (evt) => {
    const status = evt.detail.xhr.status;
    // if ([400, 403, 404, 422, 500, 503].includes(status)) {
    if ([400, 403, 404, 422].includes(status)) {
        evt.detail.shouldSwap = true;
        evt.detail.isError = false;
    }
});

// Add CSRF token to HTMX requests
window.addEventListener('htmx:configRequest', (evt) => {
    const csrfToken = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute('content');
    if (csrfToken) {
        evt.detail.headers['X-CSRF-Token'] = csrfToken;
    }
});

window.addEventListener('htmx:sendError', (evt) => {
    notyf.open({
        type: 'error',
        message:
            'Error sending request. Please refresh the page and try again.',
    });
});

// HTMX events
window.addEventListener('htmx:afterOnLoad', handleToasts);

window.addEventListener('htmx:beforeRequest', (e) =>
    handleNProgress(e, 'start'),
);
window.addEventListener('htmx:afterRequest', (e) => handleNProgress(e, 'done'));
window.addEventListener('htmx:historyRestore', NProgress.remove);

// КОСТЫЛЬ!!! Убирает кеш HTMX чтобы при возврату к прошлой вкладке страница рефрешалась заново.
// --------------------
window.addEventListener('htmx:pushedIntoHistory', (evt) => {
    localStorage.removeItem('htmx-history-cache');
});
// --------------------

window.addEventListener('delayed-redirect', function (event) {
    const { url, delay } = event.detail;
    setTimeout(() => {
        window.location.href = url;
    }, delay);
});

$(document).on('click', '[data-modal-close]', function (e) {
    e.preventDefault();
    const modalAttr = $(this).attr('data-modal-close');
    const modalId = modalAttr
        ? modalAttr.replace('#', '')
        : $(this).closest('.modal').attr('id');
    if (modalId) {
        closeModal(modalId);
    }
});

window.addEventListener('switch-theme', function (event) {
    const theme = event.detail.theme;
    if (theme) {
        applyTheme(theme);
        setCookie('theme', theme, 365);
    }
});

const cleanupMap = new WeakMap();

function closeAllDropdowns() {
    const activeMenus = document.querySelectorAll('[data-dropdown].active');
    activeMenus.forEach((menu) => {
        menu.classList.remove('active');

        const cleanup = cleanupMap.get(menu);
        if (cleanup && typeof cleanup === 'function') {
            cleanup();
            cleanupMap.delete(menu);
        }

        const handleTransitionEnd = () => {
            menu.style.display = 'none';
            menu.removeEventListener('transitionend', handleTransitionEnd);
        };
        menu.addEventListener('transitionend', handleTransitionEnd);
    });
}

function updatePosition(toggle, menu) {
    return () => {
        if (
            !toggle ||
            !menu ||
            !document.body.contains(toggle) ||
            !document.body.contains(menu)
        ) {
            return;
        }

        window.FloatingUIDOM.computePosition(toggle, menu, {
            placement: 'bottom',
            middleware: [
                window.FloatingUIDOM.offset(10),
                window.FloatingUIDOM.flip({
                    fallbackPlacements: ['top'],
                }),
                window.FloatingUIDOM.shift({ padding: 5 }),
            ],
        }).then(({ x, y, placement }) => {
            if (!menu || !document.body.contains(menu)) return;

            menu.style.left = `${x}px`;
            menu.style.top = `${y}px`;
            menu.style.position = 'absolute';
            menu.style.zIndex = '100';
            menu.setAttribute('data-placement', placement);
        });
    };
}

function handleDropdownToggle(event) {
    event.preventDefault();
    event.stopPropagation();

    const toggle = event.currentTarget;
    const dropdownName = toggle.getAttribute('data-dropdown-open');
    const menu = document.querySelector(`[data-dropdown="${dropdownName}"]`);

    if (!menu) {
        console.error(
            `Dropdown menu с data-dropdown="${dropdownName}" не найден.`,
        );
        return;
    }

    const allMenus = document.querySelectorAll('[data-dropdown]');
    allMenus.forEach((otherMenu) => {
        if (otherMenu !== menu && otherMenu.classList.contains('active')) {
            otherMenu.classList.remove('active');

            const cleanup = cleanupMap.get(otherMenu);
            if (cleanup && typeof cleanup === 'function') {
                cleanup();
                cleanupMap.delete(otherMenu);
            }

            const handleTransitionEnd = () => {
                otherMenu.style.display = 'none';
                otherMenu.removeEventListener(
                    'transitionend',
                    handleTransitionEnd,
                );
            };
            otherMenu.addEventListener('transitionend', handleTransitionEnd);
        }
    });

    if (menu.classList.contains('active')) {
        menu.classList.remove('active');

        const cleanup = cleanupMap.get(menu);
        if (cleanup && typeof cleanup === 'function') {
            cleanup();
            cleanupMap.delete(menu);
        }

        const handleTransitionEnd = () => {
            menu.style.display = 'none';
            menu.removeEventListener('transitionend', handleTransitionEnd);
        };
        menu.addEventListener('transitionend', handleTransitionEnd);
    } else {
        menu.style.display = 'block';
        menu.classList.add('active');

        const updatePos = updatePosition(toggle, menu);
        updatePos();

        const cleanup = window.FloatingUIDOM.autoUpdate(
            toggle,
            menu,
            updatePos,
        );
        cleanupMap.set(menu, cleanup);
    }
}

function handleDocumentClick(event) {
    const target = event.target;
    if (
        !target.closest('[data-dropdown-open]') &&
        !target.closest('[data-dropdown]')
    ) {
        closeAllDropdowns();
    }
}

function handleDropdownLinkClick(event) {
    closeAllDropdowns();
}

function initializeDropdowns() {
    const oldDropdownToggles = document.querySelectorAll(
        '[data-dropdown-open]',
    );
    oldDropdownToggles.forEach((toggle) => {
        toggle.removeEventListener('click', handleDropdownToggle);
    });

    const dropdownToggles = document.querySelectorAll('[data-dropdown-open]');
    dropdownToggles.forEach((toggle) => {
        toggle.addEventListener('click', handleDropdownToggle);
    });

    const dropdownLinks = document.querySelectorAll('[data-dropdown] a');
    dropdownLinks.forEach((link) => {
        link.removeEventListener('click', handleDropdownLinkClick);
        link.addEventListener('click', handleDropdownLinkClick);
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initializeDropdowns();

    document.addEventListener('click', handleDocumentClick);
});

document.body.addEventListener('htmx:afterSwap', (event) => {
    initializeDropdowns();

    if (event.detail.target.id.toLowerCase() === 'main') {
        window.scrollTo({
            top: 0,
            behavior: 'smooth',
        });
    }
});

var tooltipEl;
const tooltipCleanups = new WeakMap();
var activeTooltipElement = null;
var tooltipObserver = null;

function initTooltipObserver() {
    if (tooltipObserver) {
        tooltipObserver.disconnect();
    }
    
    tooltipObserver = new MutationObserver((mutations) => {
        if (!activeTooltipElement || !tooltipEl) return;
        
        for (const mutation of mutations) {
            if (mutation.type === 'childList') {
                const removed = Array.from(mutation.removedNodes);
                const elementRemoved = removed.some(node => {
                    if (node === activeTooltipElement) return true;
                    if (node.nodeType === 1 && node.contains(activeTooltipElement)) return true;
                    return false;
                });
                
                if (elementRemoved) {
                    hideAllTooltips();
                }
            }
            else if (mutation.type === 'attributes') {
                if (mutation.attributeName === 'data-tooltip') {
                    tooltipEl.textContent = activeTooltipElement.getAttribute('data-tooltip') || '';
                } else if (['style', 'class', 'hidden'].includes(mutation.attributeName)) {
                    const target = mutation.target;
                    if (activeTooltipElement === target || target.contains(activeTooltipElement)) {
                        const isVisible = isElementVisible(activeTooltipElement);
                        if (!isVisible) {
                            hideAllTooltips();
                        }
                    }
                }
            }
        }
    });
    
    tooltipObserver.observe(document.body, { 
        childList: true, 
        subtree: true,
        attributes: true,
        attributeFilter: ['style', 'class', 'hidden', 'data-tooltip']
    });
}

function isElementVisible(element) {
    if (!element) return false;
    
    if (!document.body.contains(element)) return false;
    
    const style = window.getComputedStyle(element);
    if (style.display === 'none' || style.visibility === 'hidden') return false;
    
    const rect = element.getBoundingClientRect();
    if (rect.width === 0 || rect.height === 0) return false;
    
    return true;
}

function showTooltip({ currentTarget }) {
    const tooltipText = currentTarget.getAttribute('data-tooltip');
    const tooltipPlacement =
        currentTarget.getAttribute('data-tooltip-placement') ?? 'top';

    if (!tooltipEl) {
        tooltipEl = document.createElement('div');
        tooltipEl.className = 'tooltip';
        document.body.appendChild(tooltipEl);
    }

    tooltipEl.textContent = tooltipText;
    tooltipEl.classList.add('show');
    activeTooltipElement = currentTarget;

    const updatePosition = () => {
        if (
            !currentTarget ||
            !document.body.contains(currentTarget) ||
            !tooltipEl
        ) {
            hideAllTooltips();
            return;
        }
        
        // Проверяем, видим ли элемент
        if (!isElementVisible(currentTarget)) {
            hideAllTooltips();
            return;
        }

        window.FloatingUIDOM.computePosition(currentTarget, tooltipEl, {
            placement: tooltipPlacement,
            middleware: [
                window.FloatingUIDOM.offset(8),
                window.FloatingUIDOM.flip(),
                window.FloatingUIDOM.shift({ padding: 5 }),
            ],
        }).then(({ x, y }) => {
            if (!tooltipEl) return;
            Object.assign(tooltipEl.style, { left: `${x}px`, top: `${y}px` });
        });
    };

    updatePosition();

    if (tooltipCleanups.has(currentTarget)) {
        const oldCleanup = tooltipCleanups.get(currentTarget);
        if (typeof oldCleanup === 'function') {
            oldCleanup();
        }
    }

    const cleanup = window.FloatingUIDOM.autoUpdate(
        currentTarget,
        tooltipEl,
        updatePosition,
    );

    tooltipCleanups.set(currentTarget, cleanup);
}

function hideTooltip({ currentTarget }) {
    if (tooltipEl) {
        tooltipEl.classList.remove('show');
    }
    
    if (activeTooltipElement === currentTarget) {
        activeTooltipElement = null;
    }

    if (tooltipCleanups.has(currentTarget)) {
        const cleanup = tooltipCleanups.get(currentTarget);
        if (typeof cleanup === 'function') {
            cleanup();
        }
        tooltipCleanups.delete(currentTarget);
    }
}

function hideAllTooltips() {
    if (tooltipEl) {
        tooltipEl.classList.remove('show');
    }
    
    if (activeTooltipElement && tooltipCleanups.has(activeTooltipElement)) {
        const cleanup = tooltipCleanups.get(activeTooltipElement);
        if (typeof cleanup === 'function') {
            cleanup();
        }
        tooltipCleanups.delete(activeTooltipElement);
    }
    
    activeTooltipElement = null;
}

function cleanupTooltips() {
    if (tooltipEl) {
        tooltipEl.remove();
        tooltipEl = null;
    }
    
    if (tooltipObserver) {
        tooltipObserver.disconnect();
        tooltipObserver = null;
    }
    
    activeTooltipElement = null;
}

document.body.addEventListener('mouseover', (event) => {
    const target = event.target.closest('[data-tooltip]');
    if (target) showTooltip({ currentTarget: target });
});

document.body.addEventListener('mouseout', (event) => {
    const target = event.target.closest('[data-tooltip]');
    if (target) hideTooltip({ currentTarget: target });
});

window.addEventListener('beforeunload', () => {
    cleanupTooltips();
    closeAllDropdowns();
});

// Дополнительные обработчики для HTMX событий
htmx.on('htmx:beforeSwap', () => {
    hideAllTooltips();
});

document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'hidden') {
        hideAllTooltips();
    }
});

document.addEventListener('DOMContentLoaded', () => {
    initTooltipObserver();
    initializeDropdowns();
    document.addEventListener('click', handleDocumentClick);
});

document.body.addEventListener('htmx:afterSwap', (event) => {
    initializeDropdowns();

    if (event.detail.target.id.toLowerCase() === 'main') {
        window.scrollTo({
            top: 0,
            behavior: 'smooth',
        });
    }
});

function applyTheme(theme) {
    $('html').attr('data-theme', theme);
}

function detectSystemTheme() {
    return window.matchMedia('(prefers-color-scheme: dark)').matches
        ? 'dark'
        : 'light';
}

var currentTheme = getCookie('theme') || detectSystemTheme();
applyTheme(currentTheme);

const darkModeMediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
if (darkModeMediaQuery.addEventListener) {
    darkModeMediaQuery.addEventListener('change', (e) => {
        if (!getCookie('theme')) {
            const newTheme = e.matches ? 'dark' : 'light';
            applyTheme(newTheme);
        }
    });
}

$(document).ready(function () {
    $(document).on('click', '.clear-input', function () {
        const inputName = $(this).data('input');
        $(`input[name="${inputName}"]`).val('');
        $(this).hide();
    });

    $(document).on('input', 'input', function () {
        var errorElement = $(this).parent().parent().find('.input__error');

        if ($(this).val().length > 0) {
            setTimeout(() => {
                $(this)
                    .closest('.input-wrapper > .input__field-container')
                    .removeClass('has-error');
                $(this).attr('aria-invalid', 'false');
                errorElement.hide();
            }, 400);
        }
    });

    $(document).on('keypress', 'input[data-numeric="true"]', function (e) {
        const withDots = $(this).data('with-dots');
        const charCode = e.which || e.keyCode;

        if (
            (charCode < 48 || charCode > 57) &&
            !(withDots && charCode === 46) &&
            charCode > 31
        ) {
            e.preventDefault();
        }
    });

    $(document).on('blur', 'input[data-min], input[data-max]', function () {
        const min = parseFloat($(this).data('min'));
        const max = parseFloat($(this).data('max'));
        const value = parseFloat($(this).val());

        if (!isNaN(min) && value < min) $(this).val(min);
        if (!isNaN(max) && value > max) $(this).val(max);
    });

    $('.clear-input').hide();
});

// HTMX content loaded
htmx.onLoad(function () {
    $('.clear-input').hide();
});

document.addEventListener('reRenderComponent', function (event) {
    const componentId = event.detail[0].componentId;
    const component = document.getElementById(componentId);
    console.log('component', component, event);
    if (component) {
        htmx.ajax('GET', window.location.href, {
            target: `#${componentId}`,
            swap: 'innerHTML',
        });
    }
});

