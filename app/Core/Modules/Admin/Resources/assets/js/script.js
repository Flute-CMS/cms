// Initialize Notyf
var notyf = new Notyf({
    duration: 4000,
    position: { x: 'right', y: 'top' },
    dismissible: true,
    ripple: false,
    types: [
        {
            type: 'success',
            className: 'notyf__toast--success',
            icon: { className: 'notyf__icon notyf__icon--success', tagName: 'div' },
        },
        {
            type: 'error',
            className: 'notyf__toast--error',
            icon: { className: 'notyf__icon notyf__icon--error', tagName: 'div' },
        },
        {
            type: 'warning',
            className: 'notyf__toast--warning',
            icon: { className: 'notyf__icon notyf__icon--warning', tagName: 'div' },
        },
        {
            type: 'info',
            className: 'notyf__toast--info',
            icon: { className: 'notyf__icon notyf__icon--info', tagName: 'div' },
        },
    ],
});

// Handle toast messages from HTMX responses
function handleToasts(evt) {
    const toastsHeader = evt.detail.xhr.getResponseHeader('X-Toasts');
    if (toastsHeader) {
        try {
            const toasts = JSON.parse(toastsHeader);
            toasts.forEach(displayToast);
        } catch (e) {
            console.error('Failed to parse toasts header:', e);
        }
    }
}

// Display a toast notification
function displayToast(toast) {
    const type = toast.type || 'info';
    const duration = toast.duration || 4000;
    const message = toast.message || '';

    const options = {
        type: type,
        message: message,
        duration: duration,
        dismissible: toast.dismissible !== false,
    };

    if (toast.className) {
        options.className = toast.className;
    }

    if (toast.events) {
        Object.entries(toast.events).forEach(([eventName, handlerName]) => {
            notyf.on(eventName, () => {
                if (typeof window[handlerName] === 'function') {
                    window[handlerName]();
                } else {
                    console.warn('Toast event handler not found:', handlerName);
                }
            });
        });
    }

    return notyf.open(options);
}

// Show NProgress during HTMX requests.
// Only shows for requests longer than PROGRESS_DELAY to avoid flicker.
// Safety timeout auto-finishes after SAFETY_TIMEOUT to prevent infinite spin.
var nprogressTimeout;
var nprogressSafetyTimeout;

function nprogressFinish() {
    clearTimeout(nprogressTimeout);
    nprogressTimeout = null;
    clearTimeout(nprogressSafetyTimeout);
    nprogressSafetyTimeout = null;
    NProgress.done();
}

function handleNProgress(event, action) {
    const PROGRESS_DELAY = 200;
    const SAFETY_TIMEOUT = 15000;
    const triggerElement = event.detail?.elt;

    if (triggerElement && triggerElement.hasAttribute('data-noprogress')) return;

    if (action === 'start') {
        if (!nprogressTimeout) {
            nprogressTimeout = setTimeout(() => {
                NProgress.start();
                nprogressTimeout = null;

                clearTimeout(nprogressSafetyTimeout);
                nprogressSafetyTimeout = setTimeout(nprogressFinish, SAFETY_TIMEOUT);
            }, PROGRESS_DELAY);
        }
    } else if (action === 'done') {
        nprogressFinish();
    }
}

htmx.on('htmx:beforeSwap', (evt) => {
    const status = evt.detail.xhr.status;
    if ([400, 403, 404, 422, 500].includes(status)) {
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

// Refresh CSRF token from response header
window.addEventListener('htmx:afterOnLoad', (evt) => {
    const newToken = evt.detail.xhr?.getResponseHeader('X-CSRF-Token');
    if (newToken) {
        const meta = document.querySelector('meta[name="csrf-token"]');
        if (meta) {
            meta.setAttribute('content', newToken);
        }
    }
});

window.addEventListener('htmx:sendError', (evt) => {
    nprogressFinish();
    displayToast({
        type: 'error',
        message: 'Error sending request. Please refresh the page and try again.',
    });
});

// Intercept Yoyo-Redirect for admin URLs and use htmx boost instead of full reload.
// Shows toasts before navigating so the user sees feedback immediately.
window.addEventListener('htmx:beforeOnLoad', (evt) => {
    const xhr = evt.detail.xhr;
    const redirectUrl = xhr.getResponseHeader('Yoyo-Redirect');
    if (!redirectUrl || !redirectUrl.startsWith('/admin')) return;

    // Show toasts from response
    handleToasts(evt);

    // Prevent Yoyo's window.location redirect
    const origGetHeader = xhr.getResponseHeader.bind(xhr);
    xhr.getResponseHeader = (name) => {
        if (name === 'Yoyo-Redirect' || name === 'X-Toasts') return null;
        return origGetHeader(name);
    };

    // Navigate via htmx boost (include HX-Boosted so server sends breadcrumb OOB swap)
    htmx.ajax('GET', redirectUrl, {
        target: '#main',
        swap: 'morph:outerHTML transition:true',
        headers: { 'HX-Boosted': 'true' },
    });
});

// HTMX events
window.addEventListener('htmx:afterOnLoad', handleToasts);

window.addEventListener('htmx:beforeRequest', (e) =>
    handleNProgress(e, 'start'),
);
window.addEventListener('htmx:afterRequest', (e) => handleNProgress(e, 'done'));
window.addEventListener('htmx:historyRestore', nprogressFinish);

// КОСТЫЛЬ!!! Убирает кеш HTMX чтобы при возврату к прошлой вкладке страница рефрешалась заново.
// --------------------
window.addEventListener('htmx:pushedIntoHistory', (evt) => {
    localStorage.removeItem('htmx-history-cache');
});
// --------------------

window.addEventListener('delayed-redirect', function (event) {
    const { url, delay } = event.detail;
    const safeUrl = String(url || '');
    if (safeUrl && delay && /^(https?:\/\/|\/)/.test(safeUrl)) {
        setTimeout(() => {
            window.location.href = safeUrl;
        }, delay);
    }
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

window.closeAllDropdowns = closeAllDropdowns;

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
            menu.setAttribute('data-placement', placement);
        });
    };
}

function handleDropdownToggle(event) {
    const toggle = event.target.closest('[data-dropdown-open]');
    if (!toggle) return;

    event.preventDefault();
    event.stopPropagation();

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
            if (menu.dataset.portal === '1' && menu.__originalParent) {
                try {
                    if (menu.__nextSibling && menu.__nextSibling.parentNode === menu.__originalParent) {
                        menu.__originalParent.insertBefore(menu, menu.__nextSibling);
                    } else {
                        menu.__originalParent.appendChild(menu);
                    }
                } catch (e) {}
                delete menu.dataset.portal;
                delete menu.__originalParent;
                delete menu.__nextSibling;
            }
            menu.removeEventListener('transitionend', handleTransitionEnd);
        };
        menu.addEventListener('transitionend', handleTransitionEnd);
    } else {
        const isAdminDropdown = menu.classList?.contains('admin-dropdown');

        if (!isAdminDropdown && menu.parentNode !== document.body) {
            menu.__originalParent = menu.parentNode;
            menu.__nextSibling = menu.nextSibling;
            document.body.appendChild(menu);
            menu.dataset.portal = '1';
        }
        menu.style.display = 'block';
        menu.classList.add('active');

        const ownerZIndexEl = toggle.closest('.sortable-list-item') || toggle.closest('.sortable-container') || null;
        const prevOwnerZIndex = ownerZIndexEl ? ownerZIndexEl.style.zIndex : null;
        if (ownerZIndexEl) {
            ownerZIndexEl.style.zIndex = '1000001';
        }

        const updatePos = updatePosition(toggle, menu);
        updatePos();

        const autoCleanup = window.FloatingUIDOM.autoUpdate(
            toggle,
            menu,
            updatePos,
        );
        const compositeCleanup = () => {
            autoCleanup();
            if (ownerZIndexEl) {
                ownerZIndexEl.style.zIndex = prevOwnerZIndex || '';
            }
        };
        cleanupMap.set(menu, compositeCleanup);
    }
}

let dropdownDelegationInitialized = false;

function initializeDropdowns() {
    // Use event delegation - attach single listener to document only once
    if (dropdownDelegationInitialized) return;
    dropdownDelegationInitialized = true;

    document.addEventListener('click', function(event) {
        const target = event.target;
        
        // Handle dropdown toggle button clicks
        const toggle = target.closest('[data-dropdown-open]');
        if (toggle) {
            handleDropdownToggle(event);
            return;
        }
        
        // Handle clicks on links inside dropdown - close dropdown after click
        const link = target.closest('[data-dropdown] a');
        if (link) {
            closeAllDropdowns();
            return;
        }
        
        // Handle clicks outside dropdown - close all dropdowns
        if (!target.closest('[data-dropdown]')) {
            closeAllDropdowns();
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initializeDropdowns();
});

document.body.addEventListener('htmx:afterSettle', (event) => {
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
    closeAllDropdowns();
});

document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'hidden') {
        hideAllTooltips();
    }
});

document.addEventListener('DOMContentLoaded', () => {
    initTooltipObserver();
});

document.body.addEventListener('htmx:afterSwap', (event) => {
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

    $(document).on('change', 'select', function () {
        var wrapper = $(this).closest('.field--select, .select-wrapper');
        if (wrapper.length) {
            wrapper.find('.has-error').removeClass('has-error');
            wrapper.find('.select__error, .input__error').hide();
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
    const componentId = event.detail?.[0]?.componentId;
    if (!componentId) return;
    const component = document.getElementById(componentId);
    if (component) {
        htmx.ajax('GET', window.location.href, {
            target: `#${componentId}`,
            swap: 'innerHTML',
        });
    }
});

// Language cards toggle
document.body.addEventListener('change', function (e) {
    if (e.target.classList.contains('language-card__input')) {
        const card = e.target.closest('.language-card');
        if (card) {
            card.classList.toggle('language-card--active', e.target.checked);
        }
    }
});

// Button group toggle is handled by buttongroup.js + central htmx:afterSettle handler in tabs.js

// Scroll to first validation error after Yoyo re-render
(function () {
    function scrollToFirstError(root) {
        var errorField = (root || document).querySelector('.has-error, .input__error, .select__error, .textarea__error');
        if (!errorField) return;

        var container = errorField.closest('.input-wrapper, .field, .form-field');
        var target = container || errorField;

        var modal = target.closest('[data-a11y-dialog]');
        if (modal) {
            var scrollable = modal.querySelector('.modal-body, [data-a11y-dialog-content]') || modal;
            var offset = target.offsetTop - scrollable.offsetTop - 20;
            scrollable.scrollTo({ top: Math.max(0, offset), behavior: 'smooth' });
        } else {
            target.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        target.classList.add('field-shake');
        setTimeout(function () { target.classList.remove('field-shake'); }, 600);
    }

    document.body.addEventListener('htmx:afterSettle', function (e) {
        setTimeout(function () { scrollToFirstError(e.detail.target); }, 80);
    });
})();

// Sticky command bar detection
(function () {
    let currentObserver = null;

    function initStickyObserver() {
        if (currentObserver) {
            currentObserver.disconnect();
            currentObserver = null;
        }

        const sentinel = document.querySelector('.base-legend-sentinel');
        const legend = document.querySelector('.base-legend');
        if (!sentinel || !legend) return;

        currentObserver = new IntersectionObserver(
            ([entry]) => {
                legend.classList.toggle('is-stuck', !entry.isIntersecting);
            },
            { threshold: 0 }
        );
        currentObserver.observe(sentinel);
    }

    initStickyObserver();
    document.body.addEventListener('htmx:afterSettle', initStickyObserver);
})();

