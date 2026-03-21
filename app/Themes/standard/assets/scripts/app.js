/**
 * Flute App.js - Main frontend functionality
 *
 * Однажды мудрец сказал:
 * "Если ты в душе не ебешь как это работает, то не трогай"
 */


class FluteApp {
    constructor() {
        this.notyf = new Notyf({
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

        this.notifications = new NotificationManager(this.notyf);
        this.modals = new ModalManager();
        this.tooltips = new TooltipManager();
        this.dropdowns = new DropdownManager();
        this.theme = new ThemeManager();
        this.forms = new FormManager();
        this.confirmations = new ConfirmationManager();

        this.nprogressTimeout = null;
        this.authToken = null;
        this.authTokenInitialized = false;
        this.authCheckInterval = null;

        this.initEvents();
        this.initAuthCheck();
    }

    initEvents() {
        this.setupHtmxEvents();

        $(document).ready(() => {
            this.forms.initInputHandlers();
        });
    }

    initAuthCheck() {
        this.authToken = document
            .querySelector('meta[name="auth-token"]')
            ?.getAttribute("content");

        if (this.authToken) {
            this.authCheckInterval = setInterval(
                () => this.checkAuthStatus(),
                10000
            );

            window.addEventListener("htmx:afterRequest", (evt) => {
                this.checkAuthToken(evt.detail.xhr);
            });
        }
    }

    checkAuthStatus() {
        fetch(u("api/auth/check"), {
            method: "HEAD",
            headers: {
                "X-Requested-With": "XMLHttpRequest",
            },
        })
            .then((response) => {
                this.checkAuthToken(response);
            })
            .catch((error) => {
                console.error("Ошибка проверки статуса авторизации:", error);
            });
    }

    checkAuthToken(response) {
        if (!response.headers) return;

        const newAuthToken = response.headers.get("Auth-Token");
        const isLoggedIn = response.headers.get("Is-Logged-In");

        if (!newAuthToken) return;

        if (!this.authTokenInitialized) {
            this.authToken = newAuthToken;
            this.authTokenInitialized = true;
            return;
        }

        if (this.authToken !== newAuthToken) {
            this.authToken = newAuthToken;

            const verify = () =>
                fetch(u("api/auth/check"), {
                    method: "HEAD",
                    headers: { "X-Requested-With": "XMLHttpRequest" },
                })
                    .then((r) => r.headers.get("Is-Logged-In"))
                    .then((state) => {
                        if (state === "false") {
                            window.location.reload();
                        } else {
                            window.location.reload();
                        }
                    })
                    .catch(() => window.location.reload());

            if (isLoggedIn === "false") {
                setTimeout(verify, 1000);
            } else {
                window.location.reload();
            }
            return;
        }
    }

    setupHtmxEvents() {
        window.addEventListener("htmx:sendError", (evt) => {
            const lang = document.querySelector("html").getAttribute("lang");
            const message =
                lang === "ru"
                    ? "Произошла ошибка при выполнении запроса. Пожалуйста, перезагрузите страницу."
                    : "Error sending request. Please refresh the page and try again.";

            this.notyf.open({ type: "error", message });
        });

        // Handle status codes
        htmx.on("htmx:beforeSwap", (evt) => {
            const status = evt.detail.xhr.status;
            if ([400, 403, 404, 422, 500, 503].includes(status)) {
                const elt = evt.detail.elt;
                const swapAttr =
                    elt && typeof elt.getAttribute === "function"
                        ? elt.getAttribute("hx-swap")
                        : null;
                const isSwapNone = (swapAttr || "").toLowerCase() === "none";
                const url =
                    (evt.detail &&
                        evt.detail.pathInfo &&
                        evt.detail.pathInfo.requestPath) ||
                    (evt.detail &&
                        evt.detail.xhr &&
                        evt.detail.xhr.responseURL) ||
                    "";
                const isApi =
                    typeof url === "string" && url.indexOf("/api/") !== -1;

                if (isApi || isSwapNone) {
                    evt.detail.shouldSwap = false;
                    evt.detail.isError = false;
                    return;
                }

                evt.detail.shouldSwap = true;
                evt.detail.isError = false;
            }
        });

        // Scroll to top instantly before swap to avoid blank space on short pages
        htmx.on("htmx:beforeSwap", (event) => {
            if (event.detail.target && event.detail.target.tagName && event.detail.target.tagName.toLowerCase() === "main") {
                window.scrollTo({ top: 0, behavior: "instant" });
            }
        });

        // Handle navbar on page change
        htmx.on("htmx:afterSwap", (event) => {
            if (event.detail.target.tagName.toLowerCase() === "main") {

                const navbarItems = document.querySelectorAll(
                    ".navbar__items-item"
                );
                navbarItems.forEach((item) => item.classList.remove("active"));

                const currentPath = event.detail.pathInfo.requestPath || "/";

                let bestMatch = null;
                let bestMatchLength = -1;

                navbarItems.forEach((item) => {
                    const href = item.getAttribute("href");
                    if (!href) return;

                    const itemPath = new URL(href, window.location.origin)
                        .pathname;

                    if (itemPath === "/" && currentPath !== "/") {
                        return;
                    }

                    if (
                        currentPath.startsWith(itemPath) &&
                        itemPath.length > bestMatchLength
                    ) {
                        bestMatch = item;
                        bestMatchLength = itemPath.length;
                    }
                });

                if (bestMatch) {
                    bestMatch.classList.add("active");
                }
            }
        });

        htmx.on("htmx:historyRestore", () => {
            window.scrollTo({
                top: 0,
                behavior: "instant",
            });

            const navbarItems = document.querySelectorAll(
                ".navbar__items-item"
            );
            navbarItems.forEach((item) => item.classList.remove("active"));

            const currentPath = new URL(window.location.href).pathname || "/";

            let bestMatch = null;
            let bestMatchLength = -1;

            navbarItems.forEach((item) => {
                const href = item.getAttribute("href");
                if (!href) return;

                const itemPath = new URL(href, window.location.origin).pathname;

                if (itemPath === "/" && currentPath !== "/") {
                    return;
                }

                if (
                    currentPath.startsWith(itemPath) &&
                    itemPath.length > bestMatchLength
                ) {
                    bestMatch = item;
                    bestMatchLength = itemPath.length;
                }
            });

            if (bestMatch) {
                bestMatch.classList.add("active");
            }
        });

        window.addEventListener("htmx:configRequest", (evt) => {
            const csrfToken = document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content");
            if (csrfToken) {
                evt.detail.headers["X-CSRF-Token"] = csrfToken;
            }
        });

        // Refresh CSRF token from response header
        htmx.on("htmx:afterOnLoad", (evt) => {
            const newToken = evt.detail.xhr?.getResponseHeader("X-CSRF-Token");
            if (newToken) {
                const meta = document.querySelector('meta[name="csrf-token"]');
                if (meta) {
                    meta.setAttribute("content", newToken);
                }
            }
        });

        // Main HTMX events
        htmx.on("htmx:afterRequest", (evt) =>
            this.notifications.handleToasts(evt)
        );

        // NProgress integration
        htmx.on("htmx:beforeRequest", (e) => this.handleNProgress(e, "start"));
        htmx.on("htmx:afterRequest", (e) => this.handleNProgress(e, "done"));
        htmx.on("htmx:sendError", (e) => this.handleNProgress(e, "done"));
        htmx.on("htmx:historyRestore", NProgress.remove);

        const ensureScrollUnlocked = () => {
            document.body.classList.remove("no-scroll");
        };
        htmx.on("htmx:beforeSwap", ensureScrollUnlocked);
        htmx.on("htmx:afterSwap", ensureScrollUnlocked);
        htmx.on("htmx:afterSettle", ensureScrollUnlocked);
        htmx.on("htmx:historyRestore", ensureScrollUnlocked);

        // Modal duplication prevention
        htmx.on("htmx:beforeSwap", (event) => {
            const incomingHTML = event.detail.xhr.response;
            if (!incomingHTML) return;

            try {
                const parser = new DOMParser();
                const doc = parser.parseFromString(incomingHTML, "text/html");
                const newModals = doc.querySelectorAll(".modal");

                newModals.forEach((newModal) => {
                    const id = newModal.id;
                    if (!id) return;

                    const existingModals = document.querySelectorAll(
                        `#modals > .modal#${id}`
                    );
                    existingModals.forEach((existingModal) => {
                        existingModal.remove();
                    });
                });
            } catch (error) {
                console.warn("Error handling modals in HTMX swap:", error);
            }
        });

        // Fix for HTMX history caching
        // htmx.on('htmx:pushedIntoHistory', () => {
        //     localStorage.removeItem('htmx-history-cache');
        // });

        htmx.onLoad((content) => {
            $(".clear-input").hide();
        });

        // Prevent error partials from altering document title
        htmx.on("htmx:afterSwap", (evt) => {
            try {
                const status = evt.detail && evt.detail.xhr ? evt.detail.xhr.status : 200;
                if (status >= 400) {
                    const originalTitle = document.title;
                    const titles = document.head.querySelectorAll('title');
                    if (titles.length > 1) {
                        for (let i = 1; i < titles.length; i++) {
                            titles[i].parentNode.removeChild(titles[i]);
                        }
                        document.title = originalTitle;
                    }
                }
            } catch (_) { }
        });

        let focusedInput = null;
        let cursorPosition = null;
        let inputValue = null;
        let restorePending = false;

        const captureFocusedInput = () => {
            const activeEl = document.activeElement;
            if (!activeEl) return;
            if (activeEl.tagName !== 'INPUT' && activeEl.tagName !== 'TEXTAREA') return;

            focusedInput = {
                id: activeEl.id,
                name: activeEl.name,
                type: activeEl.type
            };
            inputValue = activeEl.value;
            restorePending = true;

            try {
                cursorPosition = typeof activeEl.selectionStart === 'number' ? activeEl.selectionStart : null;
            } catch (_) {
                cursorPosition = null;
            }
        };

        const restoreFocusedInput = () => {
            if (!restorePending || !focusedInput) return;

            let newInput = null;
            if (focusedInput.id) {
                newInput = document.getElementById(focusedInput.id);
            }
            if (!newInput && focusedInput.name) {
                newInput = document.querySelector(`input[name="${focusedInput.name}"], textarea[name="${focusedInput.name}"]`);
            }
            if (!newInput) return;

            try {
                newInput.focus({ preventScroll: true });
            } catch (_) {
                try { newInput.focus(); } catch (_) { }
            }

            const pos = cursorPosition === null ? null : Math.min(cursorPosition, (newInput.value || '').length);
            if (pos !== null && typeof newInput.setSelectionRange === 'function') {
                try {
                    newInput.setSelectionRange(pos, pos);
                } catch (_) {
                    // ignore
                }
            }

            // Reset
            focusedInput = null;
            cursorPosition = null;
            inputValue = null;
            restorePending = false;
        };

        // Capture earlier than swap (when input is surely focused)
        htmx.on("htmx:beforeRequest", () => captureFocusedInput());
        // Also capture right before swap as a fallback
        htmx.on("htmx:beforeSwap", () => captureFocusedInput());

        // Try to restore ASAP after DOM replacement
        htmx.on("htmx:afterSwap", () => restoreFocusedInput());
        // And once more after settle (morph animations etc)
        htmx.on("htmx:afterSettle", () => restoreFocusedInput());
    }

    // NProgress handling during HTMX requests
    handleNProgress(event, action) {
        const PROGRESS_DELAY = 150;
        const triggerElement = event.detail.elt;
        const xhr = event.detail.xhr;

        if (
            !triggerElement.hasAttribute("data-noprogress") &&
            xhr.status !== 304
        ) {
            if (action === "start") {
                if (!this.nprogressTimeout) {
                    this.nprogressTimeout = setTimeout(() => {
                        NProgress.start();
                        this.nprogressTimeout = null;
                    }, PROGRESS_DELAY);
                }
            } else if (action === "done") {
                clearTimeout(this.nprogressTimeout);
                this.nprogressTimeout = null;
                NProgress.done();
            }
        }
    }
}

/**
 * Notification management
 */
class NotificationManager {
    constructor(notyf) {
        this.notyf = notyf;
        this.dropdown = null;
        this.toggle = null;
        this.originalParent = null;
        this.isOpen = false;
        this.activeTab = 'unread';
        this.cleanup = null;
        this._pendingMarkRead = new Set();

        this.initCustomEvents();
        this.initAutoMarkRead();
        this.initDropdown();
        this.initPopupNotifications();
    }

    /** CSRF token — cached, refreshed on meta change */
    get csrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    }

    /** Common fetch helper with CSRF + XHR headers */
    apiFetch(url, method = 'GET') {
        const headers = { 'X-Requested-With': 'XMLHttpRequest' };
        if (method !== 'GET') {
            headers['X-CSRF-Token'] = this.csrfToken;
        }
        return fetch(u(url), { method, headers });
    }

    /** Trigger HTMX poll on the dot element */
    triggerNotificationsUpdate() {
        document.body.dispatchEvent(new CustomEvent('notificationsUpdated'));
    }

    /** Find all DOM items with given notification ID across all tabs */
    findItemsById(id) {
        return document.querySelectorAll(`.notification-item[data-id="${id}"]`);
    }

    /** Mark a single item as read in the DOM (all instances) */
    markItemReadInDOM(id) {
        this.findItemsById(id).forEach(item => {
            item.classList.remove('unread');
            item.classList.add('viewed');
            const dot = item.querySelector('.notification-unread-indicator');
            if (dot) dot.remove();
        });
    }

    /** Remove a single item from the DOM (all instances across tabs) */
    removeItemFromDOM(id) {
        this.findItemsById(id).forEach(item => {
            item.style.transition = 'all 0.3s ease';
            item.style.opacity = '0';
            item.style.transform = 'translateX(20px)';
            setTimeout(() => item.remove(), 300);
        });
    }

    /** Fetch counts from API and update all badges + dot */
    updateBadges() {
        this.triggerNotificationsUpdate();

        this.apiFetch('api/notifications/has-unread')
            .then(r => r.json())
            .then(data => {
                const dot = document.getElementById('notification-dot');
                if (dot) {
                    dot.classList.toggle('active', data.hasUnread === true);
                }

                const unreadBadge = document.querySelector('[data-notification-count-unread]');
                if (unreadBadge) {
                    // Count items currently in the unread list DOM
                    const unreadItems = document.querySelectorAll("[data-notification-list='unread'] .notification-item");
                    unreadBadge.textContent = unreadItems.length;
                }

                const allBadge = document.querySelector('[data-notification-count-all]');
                if (allBadge) {
                    const allItems = document.querySelectorAll("[data-notification-list='all'] .notification-item");
                    allBadge.textContent = allItems.length;
                }
            })
            .catch(() => {
                // Fallback: count DOM elements
                const unreadCount = document.querySelectorAll("[data-notification-list='unread'] .notification-item").length;
                const allCount = document.querySelectorAll("[data-notification-list='all'] .notification-item").length;

                const allBadge = document.querySelector('[data-notification-count-all]');
                const unreadBadge = document.querySelector('[data-notification-count-unread]');
                if (allBadge) allBadge.textContent = allCount;
                if (unreadBadge) unreadBadge.textContent = unreadCount;

                const dot = document.getElementById('notification-dot');
                if (dot && unreadCount === 0) dot.classList.remove('active');
            });
    }

    /** Show empty state message in lists that have no items */
    checkEmptyState() {
        const content = document.querySelector('[data-notification-content]');
        const emptyText = content?.dataset.emptyText || 'No notifications';

        document.querySelectorAll('[data-notification-list]').forEach(list => {
            const items = list.querySelectorAll('.notification-item');
            let emptyMsg = list.querySelector('.notifications__empty');

            if (items.length === 0 && !emptyMsg) {
                const p = document.createElement('p');
                p.className = 'notifications__empty';
                p.textContent = emptyText;
                list.appendChild(p);
            } else if (items.length > 0 && emptyMsg) {
                emptyMsg.remove();
            }
        });
    }

    // ── Toasts ──────────────────────────────────────────

    handleToasts(evt) {
        try {
            const toastsHeader = evt.detail.xhr.getResponseHeader('X-Toasts');
            if (toastsHeader) {
                const toasts = JSON.parse(toastsHeader);
                if (Array.isArray(toasts)) {
                    toasts.forEach(toast => this.displayToast(toast));
                }
            }
        } catch (error) {
            console.error('Error handling toast notifications:', error);
        }
    }

    displayToast(toast) {
        if (!toast) return;

        const options = {
            type: toast.type || 'info',
            duration: toast.duration || 4000,
            message: toast.message || '',
            dismissible: toast.dismissible !== false,
        };

        if (toast.className) {
            options.className = toast.className;
        }

        if (toast.events) {
            Object.entries(toast.events).forEach(([eventName, handlerName]) => {
                this.notyf.on(eventName, () => {
                    if (typeof window[handlerName] === 'function') {
                        window[handlerName]();
                    }
                });
            });
        }

        return this.notyf.open(options);
    }

    // ── Custom Events ───────────────────────────────────

    initCustomEvents() {
        window.addEventListener('delayed-redirect', (event) => {
            try {
                const { url, delay } = event.detail;
                const safeUrl = String(url || '');
                if (safeUrl && delay && /^(https?:\/\/|\/)/.test(safeUrl)) {
                    setTimeout(() => { window.location.href = safeUrl; }, delay);
                }
            } catch (error) {
                console.error('Error handling delayed redirect:', error);
            }
        });
    }

    // ── Auto-Mark-Read (IntersectionObserver) ───────────

    initAutoMarkRead() {
        this._markReadObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (!entry.isIntersecting) return;
                const item = entry.target;
                const id = item.getAttribute('data-id');

                if (!id || !item.classList.contains('unread')) return;
                if (this._pendingMarkRead.has(id)) return;

                // Only auto-mark-read items in the unread tab
                const list = item.closest('[data-notification-list]');
                if (list && list.getAttribute('data-notification-list') !== 'unread') {
                    this._markReadObserver.unobserve(item);
                    return;
                }

                this._pendingMarkRead.add(id);

                // Optimistic UI update — sync across all tabs
                this.markItemReadInDOM(id);
                this._markReadObserver.unobserve(item);

                this.apiFetch(`api/notifications/${id}`, 'PUT')
                    .then(() => {
                        this._pendingMarkRead.delete(id);
                        this.updateBadges();
                    })
                    .catch(() => {
                        this._pendingMarkRead.delete(id);
                    });
            });
        }, { threshold: 0.5 });

        this._observeUnreadItems();

        // Watch for new items added to DOM (HTMX swaps)
        this._mutationObserver = new MutationObserver(mutations => {
            let hasNewItems = false;
            for (const mutation of mutations) {
                for (const node of mutation.addedNodes) {
                    if (node.nodeType === 1 && (
                        node.classList?.contains('notification-item') ||
                        node.querySelector?.('.notification-item')
                    )) {
                        hasNewItems = true;
                        break;
                    }
                }
                if (hasNewItems) break;
            }
            if (hasNewItems) {
                this._observeUnreadItems();
                this.checkEmptyState();
            }
        });

        this._mutationObserver.observe(document.body, { childList: true, subtree: true });
    }

    _observeUnreadItems() {
        // Only observe unread items in the unread tab
        const unreadList = document.querySelector("[data-notification-list='unread']");
        if (!unreadList) return;
        unreadList.querySelectorAll('.notification-item.unread').forEach(item => {
            this._markReadObserver.observe(item);
        });
    }

    // ── Dropdown ────────────────────────────────────────

    initDropdown() {
        // Single delegated click handler for all notification actions
        document.addEventListener('click', (e) => {
            // Toggle button
            const toggle = e.target.closest('[data-notification-toggle]');
            if (toggle) {
                e.preventDefault();
                e.stopPropagation();
                this.toggleDropdown();
                return;
            }

            // Delete notification
            const deleteBtn = e.target.closest('[data-notification-delete]');
            if (deleteBtn) {
                e.preventDefault();
                e.stopPropagation();
                this.deleteNotification(deleteBtn.getAttribute('data-notification-delete'), deleteBtn);
                return;
            }

            // Button handler
            const handlerBtn = e.target.closest('[data-notification-handler]');
            if (handlerBtn) {
                e.preventDefault();
                e.stopPropagation();
                const handlerName = handlerBtn.getAttribute('data-notification-handler');
                if (handlerName && typeof window[handlerName] === 'function') {
                    try { window[handlerName](); } catch (err) {
                        console.error('Error executing notification handler:', err);
                    }
                }
                return;
            }

            // Tab switching
            const tab = e.target.closest('[data-notification-tab]');
            if (tab) {
                e.preventDefault();
                this.switchTab(tab.getAttribute('data-notification-tab'));
                return;
            }

            // Mark all as read
            if (e.target.closest('[data-mark-all-read]')) {
                e.preventDefault();
                this.markAllAsRead();
                return;
            }

            // Clear all
            if (e.target.closest('[data-clear-all]')) {
                e.preventDefault();
                this.clearAllNotifications();
                return;
            }

            // Sound toggle
            if (e.target.closest('[data-notification-sound-toggle]')) {
                e.preventDefault();
                e.stopPropagation();
                this.toggleNotificationSound();
                return;
            }

            // Click on notification item to navigate
            const item = e.target.closest('.notification-item');
            if (item && !e.target.closest('.notification-btn') && !e.target.closest('.notification-file-link')) {
                const url = item.getAttribute('data-url');
                if (url && /^(https?:\/\/|\/)/.test(url)) window.location.href = url;
                return;
            }

            // Close on click outside
            if (this.isOpen && !e.target.closest('[data-notification-dropdown]')) {
                this.closeDropdown();
            }
        });

        // Close on Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen) this.closeDropdown();
        });

        // HTMX events for updating badges after tab content loads
        if (typeof htmx !== 'undefined') {
            htmx.on('htmx:afterSwap', (e) => {
                if (e.target.closest('[data-notification-dropdown]')) {
                    this.updateBadges();
                    this.checkEmptyState();
                }
            });
        }
    }

    toggleDropdown() {
        this.dropdown = document.querySelector('[data-notification-dropdown]');
        this.toggle = document.querySelector('[data-notification-toggle]');
        if (!this.dropdown) return;
        this.isOpen ? this.closeDropdown() : this.openDropdown();
    }

    openDropdown() {
        if (!this.dropdown) this.dropdown = document.querySelector('[data-notification-dropdown]');
        if (!this.toggle) this.toggle = document.querySelector('[data-notification-toggle]');
        if (!this.dropdown || !this.toggle) return;

        // Close profile dropdown if open
        const profileDropdown = document.querySelector('[data-profile-dropdown].is-open');
        if (profileDropdown) {
            profileDropdown.classList.remove('is-open');
            profileDropdown.setAttribute('aria-hidden', 'true');
            const profileToggle = document.querySelector('[data-profile-toggle]');
            if (profileToggle) profileToggle.setAttribute('aria-expanded', 'false');
            this.removeBackdrop();
        }

        this.moveToBody();
        this.showBackdrop();
        this.toggle.setAttribute('aria-expanded', 'true');
        this.isOpen = true;

        this.positionDropdown(() => {
            this.dropdown.classList.add('is-open');
            this.dropdown.setAttribute('aria-hidden', 'false');
        });
    }

    moveToBody() {
        if (!this.dropdown || this.dropdown.parentElement === document.body) return;
        this.originalParent = this.dropdown.parentElement;
        document.body.appendChild(this.dropdown);
    }

    restoreToParent() {
        if (!this.dropdown || !this.originalParent) return;
        if (this.dropdown.parentElement === document.body) {
            this.originalParent.appendChild(this.dropdown);
        }
    }

    positionDropdown(onPositioned) {
        if (!this.toggle || !this.dropdown || !window.FloatingUIDOM) {
            if (onPositioned) onPositioned();
            return;
        }

        let firstUpdate = true;
        const updatePosition = () => {
            if (!this.toggle || !this.dropdown) return;

            window.FloatingUIDOM.computePosition(this.toggle, this.dropdown, {
                placement: 'bottom-end',
                strategy: 'fixed',
                middleware: [
                    window.FloatingUIDOM.offset(16),
                    window.FloatingUIDOM.flip({
                        fallbackPlacements: ['top-end', 'bottom-start', 'top-start'],
                    }),
                    window.FloatingUIDOM.shift({ padding: 8 }),
                ],
            }).then(({ x, y, placement }) => {
                if (!this.dropdown) return;
                this.dropdown.style.left = `${x}px`;
                this.dropdown.style.top = `${y}px`;
                this.dropdown.setAttribute('data-placement', placement);
                if (firstUpdate && onPositioned) {
                    firstUpdate = false;
                    requestAnimationFrame(onPositioned);
                }
            });
        };

        if (this.cleanup) this.cleanup();
        this.cleanup = window.FloatingUIDOM.autoUpdate(
            this.toggle, this.dropdown, updatePosition,
            { ancestorScroll: true, ancestorResize: true, elementResize: true, layoutShift: true }
        );
    }

    closeDropdown() {
        if (!this.dropdown) this.dropdown = document.querySelector('[data-notification-dropdown]');
        if (!this.toggle) this.toggle = document.querySelector('[data-notification-toggle]');

        if (this.cleanup) { this.cleanup(); this.cleanup = null; }
        if (!this.dropdown) return;

        this.dropdown.classList.remove('is-open');
        this.dropdown.setAttribute('aria-hidden', 'true');
        if (this.toggle) this.toggle.setAttribute('aria-expanded', 'false');
        this.isOpen = false;
        this.removeBackdrop();

        setTimeout(() => { if (!this.isOpen) this.restoreToParent(); }, 200);
    }

    showBackdrop() {
        if (window.innerWidth > 768) return;
        this.removeBackdrop();
        this.backdrop = document.createElement('div');
        this.backdrop.className = 'dropdown-backdrop';
        this.backdrop.addEventListener('click', () => this.closeDropdown());
        document.body.appendChild(this.backdrop);
        requestAnimationFrame(() => this.backdrop && this.backdrop.classList.add('is-visible'));
    }

    removeBackdrop() {
        if (!this.backdrop) return;
        this.backdrop.classList.remove('is-visible');
        const el = this.backdrop;
        this.backdrop = null;
        setTimeout(() => el.remove(), 200);
    }

    // ── Tab Switching ───────────────────────────────────

    switchTab(tabName) {
        const tabs = document.querySelectorAll('[data-notification-tab]');
        const lists = document.querySelectorAll('[data-notification-list]');

        tabs.forEach(tab => {
            tab.classList.toggle('active', tab.getAttribute('data-notification-tab') === tabName);
        });

        lists.forEach(list => {
            const isActive = list.getAttribute('data-notification-list') === tabName;
            list.style.display = isActive ? '' : 'none';

            // Trigger HTMX load when switching to a tab with hx-trigger="revealed"
            if (isActive && typeof htmx !== 'undefined' && list.hasAttribute('hx-get')) {
                htmx.trigger(list, 'revealed');
            }
        });

        this.activeTab = tabName;
    }

    // ── Notification Actions ────────────────────────────

    markAllAsRead() {
        // Optimistic UI update — mark all as read across both tabs
        const items = document.querySelectorAll('.notification-item.unread');
        items.forEach(item => {
            item.classList.remove('unread');
            item.classList.add('viewed');
            const dot = item.querySelector('.notification-unread-indicator');
            if (dot) dot.remove();
        });

        // Remove all items from unread list (they're now read)
        const unreadList = document.querySelector("[data-notification-list='unread']");
        if (unreadList) {
            unreadList.querySelectorAll('.notification-item').forEach(item => item.remove());
        }

        this.updateBadges();
        this.checkEmptyState();

        this.apiFetch('api/notifications/read-all', 'PUT')
            .then(response => {
                if (!response.ok) {
                    // Rollback: reload lists on failure
                    this._reloadLists();
                }
            })
            .catch(() => this._reloadLists());
    }

    deleteNotification(id, btn) {
        // Optimistic UI — remove from ALL tabs
        this.removeItemFromDOM(id);

        // Wait for animation, then update state
        setTimeout(() => {
            this.updateBadges();
            this.checkEmptyState();
        }, 320);

        this.apiFetch(`api/notifications/${id}`, 'DELETE')
            .catch(() => this._reloadLists());
    }

    clearAllNotifications() {
        const items = document.querySelectorAll('.notification-item');

        // Staggered animation
        items.forEach((item, index) => {
            item.style.transition = `all 0.3s ease ${index * 0.05}s`;
            item.style.opacity = '0';
            item.style.transform = 'translateX(20px)';
        });

        const animDuration = 300 + items.length * 50;

        this.apiFetch('api/notifications/clear', 'DELETE')
            .then(response => {
                if (response.ok) {
                    setTimeout(() => {
                        items.forEach(item => item.remove());
                        this.updateBadges();
                        this.checkEmptyState();
                    }, animDuration);
                } else {
                    this._reloadLists();
                }
            })
            .catch(() => {
                items.forEach(item => { item.style.opacity = ''; item.style.transform = ''; });
            });
    }

    /** Force-reload both tab lists via HTMX */
    _reloadLists() {
        document.querySelectorAll('[data-notification-list]').forEach(list => {
            if (typeof htmx !== 'undefined' && list.hasAttribute('hx-get')) {
                htmx.trigger(list, 'load');
            }
        });
        this.updateBadges();
    }

    // ── Popup Notifications ─────────────────────────────

    initPopupNotifications() {
        const dot = document.getElementById('notification-dot');
        if (!dot) return;

        this.popupEnabled = dot.dataset.popupEnabled === 'true';
        this.soundEnabled = dot.dataset.soundEnabled === 'true';
        this.soundUserEnabled = localStorage.getItem('flute_notification_sound') !== 'false';
        this.notificationAudio = null;

        if (!this.popupEnabled && !this.soundEnabled) return;

        this.knownIds = new Set();
        this.lastNewestId = null;
        this.popupFirstPoll = true;
        this.popupContainer = null;

        this.updateSoundIcons();
        this.initProfileSoundToggle();

        document.body.addEventListener('notificationPoll', (e) => {
            const { hasUnread, newestId } = e.detail;

            if (!hasUnread || newestId == null) {
                this.lastNewestId = null;
                this.popupFirstPoll = false;
                return;
            }

            if (this.popupFirstPoll) {
                this.popupFirstPoll = false;
                this.lastNewestId = newestId;
                this.prefillKnownIds();

                const lastSeenId = localStorage.getItem('flute_notification_last_seen_id');
                if (hasUnread && lastSeenId !== String(newestId)) {
                    this.playNotificationSound();
                }
                localStorage.setItem('flute_notification_last_seen_id', String(newestId));
                return;
            }

            if (newestId !== this.lastNewestId) {
                this.lastNewestId = newestId;
                localStorage.setItem('flute_notification_last_seen_id', String(newestId));
                this.fetchAndShowPopups();
            }
        });
    }

    toggleNotificationSound() {
        this.soundUserEnabled = !this.soundUserEnabled;
        localStorage.setItem('flute_notification_sound', this.soundUserEnabled ? 'true' : 'false');
        this.updateSoundIcons();

        if (this.soundUserEnabled) {
            this.playNotificationSound();
        }
    }

    updateSoundIcons() {
        const btn = document.querySelector('[data-notification-sound-toggle]');
        if (!btn) return;

        const iconOn = btn.querySelector('[data-sound-icon-on]');
        const iconOff = btn.querySelector('[data-sound-icon-off]');

        if (iconOn) iconOn.style.display = this.soundUserEnabled ? '' : 'none';
        if (iconOff) iconOff.style.display = this.soundUserEnabled ? 'none' : '';

        btn.classList.toggle('is-muted', !this.soundUserEnabled);

        const labelOn = btn.dataset.labelOn || 'Sound enabled';
        const labelOff = btn.dataset.labelOff || 'Sound disabled';
        btn.setAttribute('data-tooltip', this.soundUserEnabled ? labelOn : labelOff);

        const profileToggle = document.getElementById('notification-sound-profile-toggle');
        if (profileToggle) {
            profileToggle.checked = this.soundUserEnabled;
        }
    }

    initProfileSoundToggle() {
        this._bindProfileSoundToggle();

        if (typeof htmx !== 'undefined') {
            htmx.on('htmx:afterSettle', () => this._bindProfileSoundToggle());
        }
    }

    _bindProfileSoundToggle() {
        const toggle = document.getElementById('notification-sound-profile-toggle');
        if (!toggle || toggle._soundBound) return;

        toggle._soundBound = true;
        toggle.checked = this.soundUserEnabled;
        toggle.addEventListener('change', () => {
            this.soundUserEnabled = toggle.checked;
            localStorage.setItem('flute_notification_sound', this.soundUserEnabled ? 'true' : 'false');
            this.updateSoundIcons();
        });
    }

    playNotificationSound() {
        if (!this.soundEnabled || !this.soundUserEnabled) return;

        try {
            if (!this.notificationAudio) {
                this.notificationAudio = new Audio(u('assets/sounds/notification.wav'));
                this.notificationAudio.volume = 0.5;
            }
            this.notificationAudio.currentTime = 0;
            this.notificationAudio.play().catch(() => {});
        } catch (e) {}
    }

    prefillKnownIds() {
        this.apiFetch('api/notifications/unread')
            .then(r => r.json())
            .then(data => {
                const flat = Object.values(data.result || {}).flat();
                for (const n of flat) this.knownIds.add(n.id);
            })
            .catch(() => {});
    }

    fetchAndShowPopups() {
        this.apiFetch('api/notifications/unread')
            .then(r => r.json())
            .then(data => {
                const flat = Object.values(data.result || {}).flat();
                let hasNew = false;
                for (const n of flat) {
                    if (!this.knownIds.has(n.id)) {
                        this.knownIds.add(n.id);
                        hasNew = true;
                        if (this.popupEnabled) {
                            this.showNotificationPopup(n);
                        }
                    }
                }

                if (hasNew) {
                    this.playNotificationSound();
                    this._reloadLists();
                }
            })
            .catch(() => {});
    }

    getPopupContainer() {
        if (!this.popupContainer || !document.body.contains(this.popupContainer)) {
            this.popupContainer = document.createElement('div');
            this.popupContainer.className = 'notification-popups';
            document.body.appendChild(this.popupContainer);
        }
        return this.popupContainer;
    }

    showNotificationPopup(notification) {
        const container = this.getPopupContainer();

        const popup = document.createElement('div');
        popup.className = 'notification-popup';
        popup.setAttribute('data-popup-id', notification.id);
        popup.setAttribute('role', 'status');
        popup.setAttribute('aria-live', 'polite');

        // Icon
        let iconHtml;
        if (notification.icon && (notification.icon.startsWith('http://') || notification.icon.startsWith('https://'))) {
            iconHtml = `<img src="${notification.icon}" alt="" loading="lazy">`;
        } else {
            iconHtml = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256" fill="currentColor"><path d="M168,224a8,8,0,0,1-8,8H96a8,8,0,1,1,0-16h64A8,8,0,0,1,168,224Zm53.85-32A15.8,15.8,0,0,1,208,200H48a16,16,0,0,1-8.84-29.35l5.18-3.47A48.23,48.23,0,0,0,65.11,132V104a63,63,0,0,1,126,0v28a48.28,48.28,0,0,0,20.77,35.18l5.18,3.47A15.84,15.84,0,0,1,221.85,192Z"/></svg>`;
        }

        // Content
        let contentHtml = '';
        if (notification.content) {
            const text = notification.content.length > 100
                ? notification.content.substring(0, 100) + '...'
                : notification.content;
            contentHtml = `<p class="notification-popup__text">${this.escapeHtml(text)}</p>`;
        }

        popup.innerHTML = `
            <div class="notification-popup__icon">${iconHtml}</div>
            <div class="notification-popup__body">
                <span class="notification-popup__title">${this.escapeHtml(notification.title || '')}</span>
                ${contentHtml}
            </div>
            <button class="notification-popup__close" aria-label="Close">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 256 256" fill="currentColor"><path d="M205.66,194.34a8,8,0,0,1-11.32,11.32L128,139.31,61.66,205.66a8,8,0,0,1-11.32-11.32L116.69,128,50.34,61.66A8,8,0,0,1,61.66,50.34L128,116.69l66.34-66.35a8,8,0,0,1,11.32,11.32L139.31,128Z"/></svg>
            </button>
            <div class="notification-popup__progress"></div>
        `;

        container.appendChild(popup);
        requestAnimationFrame(() => popup.classList.add('is-visible'));

        // Hover pause — a11y: don't dismiss while user interacts
        popup.addEventListener('mouseenter', () => {
            clearTimeout(popup._dismissTimer);
            const progress = popup.querySelector('.notification-popup__progress');
            if (progress) progress.style.animationPlayState = 'paused';
        });

        popup.addEventListener('mouseleave', () => {
            const progress = popup.querySelector('.notification-popup__progress');
            if (progress) progress.style.animationPlayState = 'running';
            popup._dismissTimer = setTimeout(() => this.dismissPopup(popup, notification.id), 3000);
        });

        // Close button
        popup.querySelector('.notification-popup__close').addEventListener('click', (e) => {
            e.stopPropagation();
            this.dismissPopup(popup, notification.id);
        });

        // Click → navigate
        popup.addEventListener('click', () => {
            if (notification.url && notification.type !== 'button') {
                window.location.href = u(notification.url);
            }
            this.dismissPopup(popup, notification.id);
        });

        // Auto-dismiss after 6s
        popup._dismissTimer = setTimeout(() => this.dismissPopup(popup, notification.id), 6000);
    }

    dismissPopup(popup, id) {
        if (popup._dismissed) return;
        popup._dismissed = true;

        clearTimeout(popup._dismissTimer);
        popup.classList.remove('is-visible');
        popup.classList.add('is-hiding');

        // Mark as read via API + sync dropdown
        if (id) {
            this.apiFetch(`api/notifications/${id}`, 'PUT')
                .then(() => {
                    this.markItemReadInDOM(id);
                    this.updateBadges();
                })
                .catch(() => {});
        }

        setTimeout(() => popup.remove(), 300);
    }

    escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
}

/**
 * Modal management
 */
class ModalManager {
    constructor() {
        this.initModalEvents();
    }

    initModalEvents() {
        $(document).on("click", "[data-modal-close]", (e) => {
            e.preventDefault();
            const modalAttr = $(e.currentTarget).attr("data-modal-close");
            const modalId = modalAttr
                ? modalAttr.replace("#", "")
                : $(e.currentTarget).closest(".modal").attr("id");

            if (modalId) {
                closeModal(modalId);
            }
        });

        // Handle tabbar submenu toggle
        $(document).on("click", ".tabbar__modal-submenu-trigger", (e) => {
            e.preventDefault();
            e.stopPropagation();
            const $submenu = $(e.currentTarget).closest(".tabbar__modal-submenu");
            const isExpanded = $submenu.hasClass("expanded");
            $submenu.toggleClass("expanded");
            $(e.currentTarget).attr("aria-expanded", !isExpanded);
        });

        $(document).on("click", ".tabbar__modal-item:not(.tabbar__modal-submenu-trigger)", (e) => {
            closeModal($(e.currentTarget).closest(".modal").attr("id"));
        });

        $(document).on("click", "[data-trigger-right-sidebar]", (e) => {
            e.preventDefault();
            $("#right_sidebar").toggleClass("active");
        });

        $(document).on("click", "#right-sidebar-content a", (e) => {
            const removeHandler = $("#right-sidebar-content").find(
                "[data-remove-handler]"
            );

            if (
                removeHandler.length === 0 &&
                $(e.currentTarget).attr("data-remove-handler") === undefined
            ) {
                closeModal("right-sidebar");
            }
        });

        window.addEventListener("open-right-sidebar", () => {
            openModal("right-sidebar");
        });

        window.addEventListener("open-modal", (event) => {
            openModal(event.detail.value || event.detail.modalId);
        });

        window.addEventListener("close-modal", (event) => {
            closeModal(event.detail.value || event.detail.modalId);
        });
    }
}

/**
 * Tooltip management
 */
class TooltipManager {
    constructor() {
        this.tooltipEl = null;
        this.tooltipCleanups = new WeakMap();
        this.activeElement = null;
        this.observer = null;
        this.lastTooltipContent = "";
        this.initTooltipEvents();
        this.initMutationObserver();
    }

    initTooltipEvents() {
        document.body.addEventListener("mouseover", (event) => {
            const target = event.target.closest("[data-tooltip]");
            if (target) this.showTooltip(target);
        });

        document.body.addEventListener("mouseout", (event) => {
            const target = event.target.closest("[data-tooltip]");
            if (target) this.hideTooltip(target);
        });

        window.addEventListener("beforeunload", () => {
            this.cleanup();
        });

        htmx.on("htmx:beforeSwap", () => {
            this.hideAllTooltips();
        });

        // Close all portaled dropdowns/popups on page swap to prevent orphaned elements
        htmx.on("htmx:beforeSwap", (event) => {
            if (event.detail.target && event.detail.target.tagName && event.detail.target.tagName.toLowerCase() === "main") {
                // Close data-dropdown elements (DropdownManager)
                this.dropdowns.closeAllDropdowns(true);

                // Close profile dropdown (ProfileDropdownManager)
                if (typeof profileDropdown !== "undefined" && profileDropdown) {
                    profileDropdown.closeDropdown();
                    profileDropdown.restoreToParent();
                }

                // Close navbar morph dropdown (NavbarMorphDropdown)
                if (typeof navbarMorphDropdown !== "undefined" && navbarMorphDropdown) {
                    navbarMorphDropdown.cancelClose();
                    if (navbarMorphDropdown.dropdown) {
                        navbarMorphDropdown.dropdown.classList.remove("is-open");
                        navbarMorphDropdown.contents?.forEach(c => c.classList.remove("is-active"));
                    }
                    if (navbarMorphDropdown.navbar) {
                        navbarMorphDropdown.navbar.classList.remove("dropdown-open");
                    }
                    navbarMorphDropdown.triggers?.forEach(t => t.classList.remove("is-active"));
                    navbarMorphDropdown.isOpen = false;
                    navbarMorphDropdown.activeId = null;
                    navbarMorphDropdown.resetDropdownPosition();
                    navbarMorphDropdown.restoreFromPortal();
                }

                document.body.classList.remove("no-scroll");
            }
        });

        document.addEventListener("visibilitychange", () => {
            if (document.visibilityState === "hidden") {
                this.hideAllTooltips();
            }
        });
    }

    initMutationObserver() {
        this.observer = new MutationObserver((mutations) => {
            if (!this.activeElement || !this.tooltipEl) return;

            let elementRemoved = false;
            let contentChanged = false;

            for (const mutation of mutations) {
                // Проверяем, не был ли удален активный элемент
                if (mutation.type === "childList") {
                    const removed = Array.from(mutation.removedNodes);
                    elementRemoved = removed.some((node) => {
                        if (node === this.activeElement) return true;
                        if (
                            node.nodeType === 1 &&
                            node.contains(this.activeElement)
                        )
                            return true;
                        return false;
                    });

                    if (elementRemoved) {
                        this.hideAllTooltips();
                        break;
                    }

                    if (this.activeElement) {
                        const tooltipText =
                            this.activeElement.getAttribute("data-tooltip");

                        // Если tooltip - это селектор
                        if (tooltipText && tooltipText.startsWith("#")) {
                            try {
                                const tooltipContentEl =
                                    document.querySelector(tooltipText);
                                if (tooltipContentEl) {
                                    for (const mutatedNode of mutation.addedNodes) {
                                        if (
                                            tooltipContentEl.contains(
                                                mutatedNode
                                            ) ||
                                            mutatedNode.contains(
                                                tooltipContentEl
                                            )
                                        ) {
                                            contentChanged = true;
                                            break;
                                        }
                                    }

                                    for (const mutatedNode of mutation.removedNodes) {
                                        if (
                                            tooltipContentEl.contains(
                                                mutatedNode
                                            ) ||
                                            (mutatedNode.nodeType === 1 &&
                                                mutatedNode.contains(
                                                    tooltipContentEl
                                                ))
                                        ) {
                                            contentChanged = true;
                                            break;
                                        }
                                    }
                                }
                            } catch (e) { }
                        }
                    }
                } else if (mutation.type === "attributes") {
                    if (
                        mutation.target === this.activeElement &&
                        mutation.attributeName === "data-tooltip"
                    ) {
                        contentChanged = true;
                    }

                    if (
                        (this.activeElement === mutation.target ||
                            mutation.target.contains(this.activeElement)) &&
                        (mutation.attributeName === "style" ||
                            mutation.attributeName === "class" ||
                            mutation.attributeName === "hidden")
                    ) {
                        const isVisible = this.isElementVisible(
                            this.activeElement
                        );
                        if (!isVisible) {
                            this.hideAllTooltips();
                            break;
                        }
                    }
                }
            }

            if (contentChanged && !elementRemoved) {
                this.updateTooltipContent(this.activeElement);
            }
        });

        this.observer.observe(document.body, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ["style", "class", "hidden", "data-tooltip"],
            characterData: true,
        });
    }

    escapeHtmlForTooltip(str) {
        const div = document.createElement("div");
        div.textContent = str;
        return div.innerHTML;
    }

    updateTooltipContent(element) {
        if (!element || !this.tooltipEl) return;

        const tooltipText = element.getAttribute("data-tooltip");
        let content;
        let isHtmlContent = false;

        try {
            if (tooltipText && tooltipText.startsWith('#')) {
                const el = document.querySelector(tooltipText);
                if (el) {
                    content = el.innerHTML;
                    isHtmlContent = true;
                } else {
                    content = tooltipText;
                }
            } else {
                content = tooltipText;
            }
        } catch {
            content = tooltipText;
        }

        if (content !== this.lastTooltipContent) {
            this.lastTooltipContent = content;
            if (isHtmlContent) {
                this.tooltipEl.innerHTML = content;
            } else {
                this.tooltipEl.textContent = content;
            }

            this.updateTooltipPosition(element);
        }
    }

    updateTooltipPosition(element) {
        if (!this.tooltipEl || !element) return;

        const tooltipPlacement =
            element.getAttribute("data-tooltip-placement") ?? "top";

        window.FloatingUIDOM.computePosition(element, this.tooltipEl, {
            placement: tooltipPlacement,
            middleware: [
                window.FloatingUIDOM.offset(10),
                window.FloatingUIDOM.flip(),
                window.FloatingUIDOM.shift({ padding: 8 }),
            ],
        }).then(({ x, y, placement }) => {
            if (!this.tooltipEl) return;
            this.tooltipEl.setAttribute('data-placement', placement);
            Object.assign(this.tooltipEl.style, {
                left: `${x}px`,
                top: `${y}px`,
            });
        });
    }

    isElementVisible(element) {
        if (!element) return false;

        if (!document.body.contains(element)) return false;

        const style = window.getComputedStyle(element);
        if (style.display === "none" || style.visibility === "hidden")
            return false;

        const rect = element.getBoundingClientRect();
        if (rect.width === 0 || rect.height === 0) return false;

        return true;
    }

    showTooltip(element) {
        if (!element) return;

        const tooltipText = element.getAttribute("data-tooltip");
        const tooltipPlacement =
            element.getAttribute("data-tooltip-placement") ?? "top";
        let content;
        let isHtmlContent = false;

        try {
            if (tooltipText && tooltipText.startsWith('#')) {
                const el = document.querySelector(tooltipText);
                if (el) {
                    content = el.innerHTML;
                    isHtmlContent = true;
                } else {
                    content = tooltipText;
                }
            } else {
                content = tooltipText;
            }
        } catch {
            content = tooltipText;
        }

        this.lastTooltipContent = content;

        if (!this.tooltipEl) {
            this.tooltipEl = document.createElement("div");
            this.tooltipEl.className = "tooltip";
            document.body.appendChild(this.tooltipEl);
        }

        if (isHtmlContent) {
            this.tooltipEl.innerHTML = content;
        } else {
            this.tooltipEl.textContent = content;
        }
        this.tooltipEl.classList.add("show");
        this.activeElement = element;

        const updatePosition = () => {
            if (!this.tooltipEl || !element) return;

            if (!this.isElementVisible(element)) {
                this.hideAllTooltips();
                return;
            }

            window.FloatingUIDOM.computePosition(element, this.tooltipEl, {
                placement: tooltipPlacement,
                middleware: [
                    window.FloatingUIDOM.offset(10),
                    window.FloatingUIDOM.flip(),
                    window.FloatingUIDOM.shift({ padding: 8 }),
                ],
            }).then(({ x, y, placement }) => {
                if (!this.tooltipEl) return;
                this.tooltipEl.setAttribute('data-placement', placement);
                Object.assign(this.tooltipEl.style, {
                    left: `${x}px`,
                    top: `${y}px`,
                });
            });
        };

        updatePosition();

        if (this.tooltipCleanups.has(element)) {
            const oldCleanup = this.tooltipCleanups.get(element);
            if (typeof oldCleanup === "function") {
                oldCleanup();
            }
        }

        const cleanup = window.FloatingUIDOM.autoUpdate(
            element,
            this.tooltipEl,
            updatePosition
        );

        this.tooltipCleanups.set(element, cleanup);
    }

    hideTooltip(element) {
        if (this.tooltipEl) {
            this.tooltipEl.classList.remove("show");
        }

        if (this.activeElement === element) {
            this.activeElement = null;
            this.lastTooltipContent = "";
        }

        if (element && this.tooltipCleanups.has(element)) {
            const cleanup = this.tooltipCleanups.get(element);
            if (typeof cleanup === "function") {
                cleanup();
            }
            this.tooltipCleanups.delete(element);
        }
    }

    hideAllTooltips() {
        if (this.tooltipEl) {
            this.tooltipEl.classList.remove("show");
        }

        if (this.activeElement) {
            this.hideTooltip(this.activeElement);
            this.activeElement = null;
            this.lastTooltipContent = "";
        }
    }

    cleanup() {
        if (this.tooltipEl) {
            this.tooltipEl.remove();
            this.tooltipEl = null;
        }

        if (this.observer) {
            this.observer.disconnect();
        }

        this.activeElement = null;
        this.lastTooltipContent = "";
    }
}

/**
 * Dropdown menu management (hover)
 */
class DropdownManager {
    constructor() {
        this.initDropdownEvents();
    }
    initDropdownEvents() {
        $(document).on("click", "[data-dropdown-open]", (event) => {
            event.preventDefault();
            event.stopPropagation();

            const $toggle = $(event.currentTarget);
            const isHoverDropdown =
                $toggle.attr("data-dropdown-hover") === "true";

            if (!isHoverDropdown) {
                this.toggleDropdown($toggle);
            }
        });

        $(document).on(
            "mouseenter",
            '[data-dropdown-open][data-dropdown-hover="true"]',
            (event) => {
                const $toggle = $(event.currentTarget);
                const dropdownName = $toggle.data("dropdown-open");
                const $menu = $(`[data-dropdown="${dropdownName}"]`);

                if ($menu.data("closeTimeout")) {
                    clearTimeout($menu.data("closeTimeout"));
                    $menu.removeData("closeTimeout");
                }

                if ($menu.hasClass("active")) return;

                if ($toggle.data("openTimeout")) return;

                const openTimeout = setTimeout(() => {
                    const toggleHovered = $toggle[0]?.matches(":hover");
                    const menuHovered = $menu[0]?.matches(":hover");
                    if (toggleHovered || menuHovered) {
                        this.openDropdown($toggle, $menu);
                    }
                    $toggle.removeData("openTimeout");
                }, 50);

                $toggle.data("openTimeout", openTimeout);
            }
        );

        $(document).on(
            "mouseleave",
            '[data-dropdown-open][data-dropdown-hover="true"]',
            (event) => {
                const $toggle = $(event.currentTarget);
                const dropdownName = $toggle.data("dropdown-open");
                const $menu = $(`[data-dropdown="${dropdownName}"]`);

                if ($toggle.data("openTimeout")) {
                    clearTimeout($toggle.data("openTimeout"));
                    $toggle.removeData("openTimeout");
                }

                // Longer close delay to allow moving to menu
                const closeTimeout = setTimeout(() => {
                    const toggleHovered = $toggle[0]?.matches(":hover");
                    const menuHovered = $menu[0]?.matches(":hover");
                    if (!toggleHovered && !menuHovered) {
                        this.closeDropdown($menu);
                    }
                }, 300);

                $menu.data("closeTimeout", closeTimeout);
            }
        );

        $(document).on("mouseenter", "[data-dropdown]", (event) => {
            const $menu = $(event.currentTarget);
            const dropdownName = $menu.data("dropdown");
            const $toggle = $(`[data-dropdown-open="${dropdownName}"]`);
            if ($toggle.attr("data-dropdown-hover") !== "true") return;
            if ($menu.data("closeTimeout")) {
                clearTimeout($menu.data("closeTimeout"));
                $menu.removeData("closeTimeout");
            }
        });

        $(document).on("mouseleave", "[data-dropdown]", (event) => {
            const $menu = $(event.currentTarget);
            const dropdownName = $menu.data("dropdown");
            const $toggle = $(
                `[data-dropdown-open="${dropdownName}"][data-dropdown-hover="true"]`
            );
            if ($toggle.length === 0) return;
            // Longer close delay
            const closeTimeout = setTimeout(() => {
                const toggleHovered = $toggle[0]?.matches(":hover");
                const menuHovered = $menu[0]?.matches(":hover");
                if (!toggleHovered && !menuHovered) {
                    this.closeDropdown($menu);
                }
            }, 300);
            $menu.data("closeTimeout", closeTimeout);
        });

        $(document).on("click", (event) => {
            const $target = $(event.target);

            if (
                $target.closest("[data-dropdown-open]").length ||
                $target.closest("[data-dropdown]").length
            ) {
                return;
            }

            $("[data-dropdown].active").each((_, element) => {
                const $menu = $(element);
                const dropdownName = $menu.data("dropdown");
                const $toggle = $(`[data-dropdown-open="${dropdownName}"]`);
                const isHoverDropdown =
                    $toggle.attr("data-dropdown-hover") === "true";

                if (!isHoverDropdown) {
                    this.closeDropdown($menu);
                }
            });
        });

        $(document).on(
            "click",
            "[data-dropdown] a, [data-dropdown] [data-handler]",
            (event) => {
                const $menu = $(event.currentTarget).closest("[data-dropdown]");
                const dropdownName = $menu.data("dropdown");
                const $toggle = $(`[data-dropdown-open="${dropdownName}"]`);
                const isHoverDropdown =
                    $toggle.attr("data-dropdown-hover") === "true";

                if (!isHoverDropdown) {
                    this.closeDropdown($menu);
                }
            }
        );

        window.addEventListener("beforeunload", () => {
            this.closeAllDropdowns();
        });
    }

    toggleDropdown($toggle) {
        if (!$toggle || !$toggle.length) return;

        const dropdownName = $toggle.data("dropdown-open");
        const $menu = $(`[data-dropdown="${dropdownName}"]`);

        if (!$menu.length) return;

        $("[data-dropdown]")
            .not($menu)
            .each((_, element) => {
                const $otherMenu = $(element);
                if ($otherMenu.hasClass("active")) {
                    $otherMenu.removeClass("active");
                    const cleanup = $otherMenu.data("autoUpdateCleanup");
                    if (cleanup && typeof cleanup === "function") {
                        cleanup();
                        $otherMenu.removeData("autoUpdateCleanup");
                    }
                    $otherMenu.one("transitionend", () => $otherMenu.hide());
                }
            });

        if ($menu.hasClass("active")) {
            this.closeDropdown($menu);
        } else {
            this.openDropdown($toggle, $menu);
        }
    }

    openDropdown($toggle, $menu) {
        try {
            const isHoverDropdown = $toggle.attr("data-dropdown-hover") === "true";

            if (isHoverDropdown) {
                $("[data-dropdown].active").each((_, el) => {
                    const $otherMenu = $(el);
                    if ($otherMenu[0] === $menu[0]) return;

                    const otherName = $otherMenu.data("dropdown");
                    const $otherToggle = $(
                        `[data-dropdown-open="${otherName}"]`
                    );
                    if ($otherToggle.attr("data-dropdown-hover") === "true") {
                        this.closeDropdown($otherMenu);
                    }
                });
            }

            const $originalParent = $menu.parent();
            $menu.data("originalParent", $originalParent);

            $menu.appendTo("body");
            $menu.show().addClass("active");

            // Don't block scroll for hover dropdowns (navbar)
            if (!isHoverDropdown) {
                $("body").addClass("no-scroll");
            }

            this.positionDropdown($toggle, $menu);
        } catch (error) {
            console.error("Error opening dropdown:", error);
        }
    }

    positionDropdown($toggle, $menu) {
        if (!$toggle[0] || !$menu[0] || !window.FloatingUIDOM) return;

        const updatePosition = () => {
            if (!$toggle[0] || !$menu[0]) return;

            window.FloatingUIDOM.computePosition($toggle[0], $menu[0], {
                placement: "bottom",
                middleware: [
                    window.FloatingUIDOM.offset(10),
                    window.FloatingUIDOM.flip({
                        fallbackPlacements: ["top"],
                    }),
                    window.FloatingUIDOM.shift({ padding: 5 }),
                ],
            }).then(({ x, y, placement }) => {
                if (!$menu[0]) return; // Check if menu still exists

                Object.assign($menu[0].style, {
                    left: `${x}px`,
                    top: `${y}px`,
                    position: "absolute",
                    zIndex: 9999,
                });
                $menu.attr("data-placement", placement);
            });
        };

        updatePosition();

        // Store cleanup function to prevent memory leaks
        try {
            const cleanup = window.FloatingUIDOM.autoUpdate(
                $toggle[0],
                $menu[0],
                updatePosition
            );
            $menu.data("autoUpdateCleanup", cleanup);
        } catch (error) {
            console.error("Error setting up FloatingUI:", error);
        }
    }

    closeDropdown($menu) {
        if (!$menu || !$menu.length) return;

        try {
            $menu.removeClass("active");

            // Clean up FloatingUI autoUpdate
            const cleanup = $menu.data("autoUpdateCleanup");
            if (cleanup && typeof cleanup === "function") {
                cleanup();
                $menu.removeData("autoUpdateCleanup");
            }

            // Use timeout instead of transitionend for reliability
            setTimeout(() => {
                if (!$menu.hasClass("active")) {
                    $menu.hide();
                    $("body").removeClass("no-scroll");

                    // Return to original parent
                    const $originalParent = $menu.data("originalParent");
                    if ($originalParent && $originalParent.length) {
                        $menu.appendTo($originalParent);
                        $menu.removeData("originalParent");
                    }
                }
            }, 250);
        } catch (error) {
            console.error("Error closing dropdown:", error);
            // Fallback in case of error
            $menu.hide();
            $("body").removeClass("no-scroll");
        }
    }

    closeAllDropdowns(immediate = false) {
        $("[data-dropdown].active").each((_, element) => {
            if (immediate) {
                this.closeDropdownImmediate($(element));
            } else {
                this.closeDropdown($(element));
            }
        });
    }

    closeDropdownImmediate($menu) {
        if (!$menu || !$menu.length) return;

        try {
            $menu.removeClass("active").hide();

            const cleanup = $menu.data("autoUpdateCleanup");
            if (cleanup && typeof cleanup === "function") {
                cleanup();
                $menu.removeData("autoUpdateCleanup");
            }

            const $originalParent = $menu.data("originalParent");
            if ($originalParent && $originalParent.length && $.contains(document, $originalParent[0])) {
                $menu.appendTo($originalParent);
            }
            $menu.removeData("originalParent");
        } catch (error) {
            console.error("Error closing dropdown:", error);
            $menu.hide();
        }
    }
}

/**
 * Theme management
 */
class ThemeManager {
    constructor() {
        this.initTheme();
    }

    initTheme() {
        this.themeToggleButton = $("#theme-toggle");
        this.sunIcon = this.themeToggleButton.find(".sun-icon");
        this.moonIcon = this.themeToggleButton.find(".moon-icon");

        const changeThemeEnabled =
            document
                .querySelector('meta[name="change-theme"]')
                ?.getAttribute("content") === "true";

        if (!changeThemeEnabled) {
            this.themeToggleButton.hide();
        }

        const defaultTheme =
            document
                .querySelector('meta[name="default-theme"]')
                ?.getAttribute("content") || "dark";
        const currentTheme = changeThemeEnabled
            ? getCookie("theme") || this.detectSystemTheme() || defaultTheme
            : defaultTheme;
        this.applyTheme(currentTheme);

        this.themeToggleButton.on("click", () => {
            const changeThemeEnabled =
                document
                    .querySelector('meta[name="change-theme"]')
                    ?.getAttribute("content") === "true";

            if (!changeThemeEnabled) {
                return;
            }

            const currentTheme =
                $("html").attr("data-theme") === "light" ? "dark" : "light";
            this.applyTheme(currentTheme);
            setCookie("theme", currentTheme, 365);
        });

        this.initSystemThemeListener();

        window.addEventListener("switch-theme", (event) => {
            const theme = event.detail?.theme;
            if (theme) {
                this.applyTheme(theme);
                setCookie("theme", theme, 365);
            }
        });
    }

    updateIcons(theme) {
        if (theme === "dark") {
            $(".sun-icon").hide(100);
            $(".moon-icon").show(100);
        } else {
            $(".moon-icon").hide(100);
            $(".sun-icon").show(100);
        }
    }

    applyTheme(theme) {
        $("html").attr("data-theme", theme);
        this.updateIcons(theme);
    }

    detectSystemTheme() {
        return window.matchMedia("(prefers-color-scheme: dark)").matches
            ? "dark"
            : "light";
    }

    initSystemThemeListener() {
        const darkModeMediaQuery = window.matchMedia(
            "(prefers-color-scheme: dark)"
        );
        if (darkModeMediaQuery.addEventListener) {
            darkModeMediaQuery.addEventListener("change", (e) => {
                if (!getCookie("theme")) {
                    const defaultTheme =
                        document
                            .querySelector('meta[name="default-theme"]')
                            ?.getAttribute("content") || "dark";
                    const newTheme = e.matches ? "dark" : "light";
                    const changeThemeEnabled =
                        document
                            .querySelector('meta[name="change-theme"]')
                            ?.getAttribute("content") === "true";
                    const finalTheme = changeThemeEnabled
                        ? newTheme
                        : defaultTheme;
                    this.applyTheme(finalTheme);
                }
            });
        }
    }
}

/**
 * Form input management
 */
class FormManager {
    constructor() {
        // No initialization needed
    }

    initInputHandlers() {
        // Clear input button
        $(document).on("click", ".clear-input", (e) => {
            const inputName = $(e.currentTarget).data("input");
            $(`input[name="${inputName}"]`).val("");
            $(e.currentTarget).hide();
        });

        // Input validation
        $(document).on("input", "input", function () {
            const errorElement = $(this)
                .closest(".input-wrapper")
                .find(".input__error");

            if ($(this).val().length > 0) {
                setTimeout(() => {
                    $(this)
                        .closest(".input-wrapper > .input__field-container")
                        .removeClass("has-error");
                    errorElement.hide();
                }, 400);
            } else {
                errorElement.show();
            }
        });

        // Numeric input handling
        $(document).on("keypress", 'input[data-numeric="true"]', function (e) {
            const withDots = $(this).data("with-dots");
            const charCode = e.which || e.keyCode;

            if (
                (charCode < 48 || charCode > 57) &&
                !(withDots && charCode === 46) &&
                charCode > 31
            ) {
                e.preventDefault();
            }
        });

        // Min/max validation
        $(document).on("blur", "input[data-min], input[data-max]", function () {
            const min = parseFloat($(this).data("min"));
            const max = parseFloat($(this).data("max"));
            const value = parseFloat($(this).val());

            if (!isNaN(min) && value < min) $(this).val(min);
            if (!isNaN(max) && value > max) $(this).val(max);
        });

        // Initial state
        $(".clear-input").hide();
    }
}

/**
 * Confirmation dialog management
 */
class ConfirmationManager {
    constructor() {
        this.confirmTypes = {
            accent: {
                buttonClass: "btn-accent",
                iconClass: "icon-accent",
            },
            primary: {
                buttonClass: "btn-primary",
                iconClass: "icon-primary",
            },
            error: {
                buttonClass: "btn-error",
                iconClass: "icon-error",
            },
            warning: {
                buttonClass: "btn-warning",
                iconClass: "icon-warning",
            },
            info: {
                buttonClass: "btn-info",
                iconClass: "icon-info",
            },
            success: {
                buttonClass: "btn-success",
                iconClass: "icon-success",
            },
        };

        this.confirmedActions = new Set();
        this.initConfirmEvents();
    }

    initConfirmEvents() {
        // HTMX confirmation
        $(document).on("click", "[hx-flute-confirm]", (event) => {
            event.preventDefault();

            const $triggerElement = $(event.currentTarget);
            const confirmMessage = $triggerElement.attr("hx-flute-confirm");
            const confirmType =
                $triggerElement.attr("hx-flute-confirm-type") || "error";
            const actionKey = $triggerElement.attr("hx-flute-action-key");
            const withoutTrigger = $triggerElement.attr(
                "hx-flute-without-trigger"
            );

            if (actionKey && this.confirmedActions.has(actionKey)) {
                htmx.trigger($triggerElement[0], "confirmed");
                return;
            }

            this.showConfirmDialog({
                message: confirmMessage,
                type: confirmType,
                actionKey: actionKey,
                withoutTrigger: withoutTrigger,
                onConfirm: () => {
                    htmx.trigger($triggerElement[0], "confirmed");
                    if (actionKey) {
                        this.confirmedActions.add(actionKey);
                    }
                },
                onCancel: () => {
                    if (actionKey) {
                        this.confirmedActions.delete(actionKey);
                    }
                },
            });
        });

        // YoYo confirmation
        document.addEventListener("confirm", (event) => {
            const {
                message,
                title,
                confirmText,
                cancelText,
                type,
                actionKey,
                action,
                originalRequestData,
                withoutTrigger,
            } = event.detail[0];
            const yoyoComponent = event.detail.elt;

            if (!yoyoComponent) {
                console.error("No component found for confirmation event");
                return;
            }

            event.preventDefault();

            this.showConfirmDialog({
                message,
                title,
                confirmText,
                cancelText,
                type,
                withoutTrigger,
                onConfirm: () => {
                    this.handleYoyoConfirmation(
                        yoyoComponent,
                        action,
                        actionKey,
                        originalRequestData
                    );
                },
            });
        });
    }

    showConfirmDialog(options) {
        const {
            message,
            title,
            confirmText,
            cancelText,
            type = "error",
            withoutTrigger,
            onConfirm,
            onCancel,
        } = options;

        const currentType =
            this.confirmTypes[type] || this.confirmTypes["error"];

        // Set message
        $("#confirmation-dialog-message").text(message);

        // Configure confirm button style
        let $confirmButton = $("#confirmation-dialog-confirm");
        let $cancelButton = $("#confirmation-dialog-cancel");
        let $title = $("#confirmation-dialog-title");

        $confirmButton.removeClass(
            "btn-accent btn-primary btn-error btn-warning btn-info"
        );
        $confirmButton.addClass(currentType.buttonClass);

        // Handle custom text
        if (confirmText) {
            $confirmButton.attr("old-text", $confirmButton.text());
            $confirmButton.text(confirmText);
        }

        // Handle without trigger option
        if (withoutTrigger) {
            $confirmButton.hide();
        }

        // Set icon
        let $iconContainer = $("#confirmation-dialog-icon");
        $iconContainer.children().hide();
        $iconContainer.find("." + currentType.iconClass).show();

        // Custom cancel text
        if (cancelText) {
            $cancelButton.attr("old-text", $cancelButton.text());
            $cancelButton.text(cancelText);
        }

        // Custom title
        if (title) {
            $title.attr("old-text", $title.text());
            $title.text(title);
        }

        let confirmHandled = false;
        let cancelHandled = false;

        // Confirm action
        $confirmButton.on("click", () => {
            if (confirmHandled) return;
            confirmHandled = true;

            $confirmButton.off("click");
            $cancelButton.off("click");

            closeModal("confirmation-dialog");

            if (typeof onConfirm === "function") {
                onConfirm();
            }

            if (confirmText)
                $confirmButton.text($confirmButton.attr("old-text"));
            if (cancelText) $cancelButton.text($cancelButton.attr("old-text"));
            if (title) $title.text($title.attr("old-text"));

            $confirmButton.removeClass(currentType.buttonClass);

            confirmHandled = false;
            cancelHandled = false;
        });

        $cancelButton.on("click", () => {
            if (cancelHandled) return;
            cancelHandled = true;

            $confirmButton.off("click");
            $cancelButton.off("click");

            closeModal("confirmation-dialog");

            if (typeof onCancel === "function") {
                onCancel();
            }

            setTimeout(() => {
                if (confirmText)
                    $confirmButton.text($confirmButton.attr("old-text"));
                if (cancelText)
                    $cancelButton.text($cancelButton.attr("old-text"));
                if (title) $title.text($title.attr("old-text"));

                $confirmButton.removeClass(currentType.buttonClass);

                if (withoutTrigger) {
                    $confirmButton.show();
                }

                confirmHandled = false;
                cancelHandled = false;
            }, 300);
        });

        let $closeButton = $("#confirmation-dialog").find(".modal__close");
        $closeButton.off("click");
        $closeButton.on("click", () => {
            $confirmButton.off("click");
            $cancelButton.off("click");
            $closeButton.off("click");

            setTimeout(() => {
                if (confirmText)
                    $confirmButton.text($confirmButton.attr("old-text"));
                if (cancelText)
                    $cancelButton.text($cancelButton.attr("old-text"));
                if (title) $title.text($title.attr("old-text"));

                $confirmButton.removeClass(currentType.buttonClass);

                if (withoutTrigger) {
                    $confirmButton.show();
                }

                confirmHandled = false;
                cancelHandled = false;
            }, 300);
        });

        // Show modal
        openModal("confirmation-dialog");
    }

    handleYoyoConfirmation(
        yoyoComponent,
        action,
        actionKey,
        originalRequestData
    ) {
        if (!yoyoComponent || !action) return;

        try {
            const requestData = originalRequestData || {};

            if (requestData["confirmed_action"]) {
                if (Array.isArray(requestData["confirmed_action"])) {
                    requestData["confirmed_action"].push(actionKey);
                } else {
                    requestData["confirmed_action"] = [
                        requestData["confirmed_action"],
                        actionKey,
                    ];
                }
            } else {
                requestData["confirmed_action"] = actionKey;
            }

            const headers = {
                "Content-Type": "application/x-www-form-urlencoded",
                "X-Requested-With": "XMLHttpRequest",
                "X-HX-Request": "true",
                "X-Csrf-Token": document
                    .querySelector('meta[name="csrf-token"]')
                    .getAttribute("content"),
            };

            const componentName = yoyoComponent.getAttribute("yoyo:name");
            requestData["component"] = `${componentName}/${action}`;

            if (requestData["actionArgs"]) {
                requestData["actionArgs"] = JSON.stringify(
                    requestData["actionArgs"]
                );
            }

            const formData = new URLSearchParams(requestData).toString();

            const targetSelector = yoyoComponent.getAttribute("id")
                ? `#${yoyoComponent.getAttribute("id")}`
                : null;

            if (targetSelector) {
                const xhr = new XMLHttpRequest();
                xhr.open("POST", Yoyo.url, true);

                Object.keys(headers).forEach((key) => {
                    xhr.setRequestHeader(key, headers[key]);
                });

                xhr.onload = () => {
                    if (xhr.status >= 200 && xhr.status < 300) {
                        const newCsrfToken = xhr.getResponseHeader("X-CSRF-Token");
                        if (newCsrfToken) {
                            const meta = document.querySelector('meta[name="csrf-token"]');
                            if (meta) {
                                meta.setAttribute("content", newCsrfToken);
                            }
                        }

                        htmx.trigger(document.body, "htmx:afterRequest", {
                            target: yoyoComponent,
                            xhr: xhr,
                        });

                        if (
                            xhr
                                .getAllResponseHeaders()
                                .indexOf("hx-trigger") !== -1
                        ) {
                            const triggerHeader =
                                xhr.getResponseHeader("hx-trigger");
                            if (triggerHeader) {
                                try {
                                    const triggers = JSON.parse(triggerHeader);
                                    Object.keys(triggers).forEach(
                                        (eventName) => {
                                            htmx.trigger(
                                                document.body,
                                                eventName,
                                                triggers[eventName]
                                            );
                                        }
                                    );
                                } catch (e) {
                                    htmx.trigger(document.body, triggerHeader);
                                }
                            }
                        }

                        const emitHeader = xhr.getResponseHeader("yoyo-emit");
                        if (emitHeader) {
                            Yoyo.processEmitEvents(yoyoComponent, emitHeader);
                        }

                        const browserEventsHeader =
                            xhr.getResponseHeader("yoyo-browser-event");
                        if (browserEventsHeader) {
                            Yoyo.processBrowserEvents(browserEventsHeader);
                        }

                        if (xhr.responseText.trim() !== "") {
                            const temp = document.createElement("div");
                            temp.innerHTML = xhr.responseText;

                            const responseEl = temp.firstElementChild;

                            if (responseEl) {
                                const activeElement =
                                    document.activeElement;
                                const shouldRestoreFocus =
                                    activeElement &&
                                    yoyoComponent.contains(activeElement);
                                let restoreState = null;

                                if (
                                    shouldRestoreFocus &&
                                    (activeElement.tagName === "INPUT" ||
                                        activeElement.tagName === "TEXTAREA")
                                ) {
                                    const inputType =
                                        (activeElement.getAttribute("type") ||
                                            "")
                                            .toLowerCase();
                                    const isTextLike =
                                        ![
                                            "checkbox",
                                            "radio",
                                            "hidden",
                                            "submit",
                                            "button",
                                            "reset",
                                            "file",
                                        ].includes(inputType);

                                    if (isTextLike) {
                                        restoreState = {
                                            id: activeElement.getAttribute(
                                                "id"
                                            ),
                                            name: activeElement.getAttribute(
                                                "name"
                                            ),
                                            value: activeElement.value,
                                            selectionStart:
                                                activeElement.selectionStart,
                                            selectionEnd:
                                                activeElement.selectionEnd,
                                        };
                                    }
                                }

                                yoyoComponent.outerHTML = responseEl.outerHTML;
                                htmx.process(
                                    document.querySelector(targetSelector)
                                );
                            }
                        } else {
                            YoyoEngine.trigger(yoyoComponent, action);
                            htmx.trigger(document.body, "htmx:afterSwap", {
                                target: yoyoComponent,
                            });
                        }
                    }
                };

                xhr.send(formData);
            }
        } catch (error) {
            console.error("Error in YoYo confirmation handling:", error);
        }
    }
}

/**
 * Profile Dropdown Management with FloatingUI
 */
class ProfileDropdownManager {
    constructor() {
        this.dropdown = null;
        this.toggle = null;
        this.originalParent = null;
        this.isOpen = false;
        this.cleanup = null;
        this.initDropdown();
    }

    initDropdown() {
        // Toggle button click
        document.addEventListener("click", (e) => {
            const toggle = e.target.closest("[data-profile-toggle]");
            if (toggle) {
                e.preventDefault();
                e.stopPropagation();
                this.toggleDropdown();
                return;
            }

            // Close on click outside
            const dropdown = e.target.closest("[data-profile-dropdown]");
            if (!dropdown && this.isOpen) {
                this.closeDropdown();
            }
        });

        // Close on Escape
        document.addEventListener("keydown", (e) => {
            if (e.key === "Escape" && this.isOpen) {
                this.closeDropdown();
            }
        });

        // Close on link click inside dropdown
        document.addEventListener("click", (e) => {
            const link = e.target.closest("[data-profile-dropdown] a, [data-profile-dropdown] button[type='submit']");
            if (link && this.isOpen) {
                setTimeout(() => this.closeDropdown(), 100);
            }
        });
    }

    toggleDropdown() {
        this.dropdown = document.querySelector("[data-profile-dropdown]");
        this.toggle = document.querySelector("[data-profile-toggle]");

        if (!this.dropdown) return;

        if (this.isOpen) {
            this.closeDropdown();
        } else {
            this.openDropdown();
        }
    }

    openDropdown() {
        if (!this.dropdown) {
            this.dropdown = document.querySelector("[data-profile-dropdown]");
        }
        if (!this.toggle) {
            this.toggle = document.querySelector("[data-profile-toggle]");
        }

        if (!this.dropdown || !this.toggle) return;

        // Close notification dropdown if open
        const notifDropdown = document.querySelector("[data-notification-dropdown].is-open");
        if (notifDropdown) {
            notifDropdown.classList.remove("is-open");
            notifDropdown.setAttribute("aria-hidden", "true");
            const notifToggle = document.querySelector("[data-notification-toggle]");
            if (notifToggle) {
                notifToggle.setAttribute("aria-expanded", "false");
            }
            this.removeBackdrop();
        }

        // Move to body for proper backdrop-filter
        this.moveToBody();
        this.showBackdrop();

        this.toggle.setAttribute("aria-expanded", "true");
        this.isOpen = true;

        // Position first, then show with animation
        this.positionDropdown(() => {
            this.dropdown.classList.add("is-open");
            this.dropdown.setAttribute("aria-hidden", "false");
        });
    }

    moveToBody() {
        if (!this.dropdown || this.dropdown.parentElement === document.body) return;
        this.originalParent = this.dropdown.parentElement;
        document.body.appendChild(this.dropdown);
    }

    restoreToParent() {
        if (!this.dropdown || !this.originalParent) return;
        if (this.dropdown.parentElement === document.body) {
            this.originalParent.appendChild(this.dropdown);
        }
    }

    showBackdrop() {
        if (window.innerWidth > 768) return;
        this.removeBackdrop();
        this.backdrop = document.createElement("div");
        this.backdrop.className = "dropdown-backdrop";
        this.backdrop.addEventListener("click", () => this.closeDropdown());
        document.body.appendChild(this.backdrop);
        requestAnimationFrame(() => this.backdrop && this.backdrop.classList.add("is-visible"));
    }

    removeBackdrop() {
        if (!this.backdrop) return;
        this.backdrop.classList.remove("is-visible");
        const el = this.backdrop;
        this.backdrop = null;
        setTimeout(() => el.remove(), 200);
    }

    positionDropdown(onPositioned) {
        if (!this.toggle || !this.dropdown || !window.FloatingUIDOM) {
            if (onPositioned) onPositioned();
            return;
        }

        let firstUpdate = true;
        const updatePosition = () => {
            if (!this.toggle || !this.dropdown) return;

            window.FloatingUIDOM.computePosition(this.toggle, this.dropdown, {
                placement: "bottom-end",
                strategy: "fixed",
                middleware: [
                    window.FloatingUIDOM.offset(16),
                    window.FloatingUIDOM.flip({
                        fallbackPlacements: ["top-end", "bottom-start", "top-start"],
                    }),
                    window.FloatingUIDOM.shift({ padding: 8 }),
                ],
            }).then(({ x, y, placement }) => {
                if (!this.dropdown) return;

                this.dropdown.style.left = `${x}px`;
                this.dropdown.style.top = `${y}px`;
                this.dropdown.setAttribute("data-placement", placement);

                // Call callback after first positioning
                if (firstUpdate && onPositioned) {
                    firstUpdate = false;
                    requestAnimationFrame(onPositioned);
                }
            });
        };

        if (this.cleanup) {
            this.cleanup();
        }
        this.cleanup = window.FloatingUIDOM.autoUpdate(
            this.toggle,
            this.dropdown,
            updatePosition,
            {
                ancestorScroll: true,
                ancestorResize: true,
                elementResize: true,
                layoutShift: true,
            }
        );
    }

    closeDropdown() {
        if (!this.dropdown) {
            this.dropdown = document.querySelector("[data-profile-dropdown]");
        }
        if (!this.toggle) {
            this.toggle = document.querySelector("[data-profile-toggle]");
        }

        if (this.cleanup) {
            this.cleanup();
            this.cleanup = null;
        }

        if (!this.dropdown) return;

        this.dropdown.classList.remove("is-open");
        this.dropdown.setAttribute("aria-hidden", "true");
        if (this.toggle) {
            this.toggle.setAttribute("aria-expanded", "false");
        }
        this.isOpen = false;
        this.removeBackdrop();

        // Return to original parent after animation
        setTimeout(() => {
            if (!this.isOpen) {
                this.restoreToParent();
            }
        }, 200);
    }
}

class NavbarMorphDropdown {
    constructor() {
        this.navbar = null;
        this.dropdown = null;
        this.box = null;
        this.triggers = [];
        this.contents = [];
        this.activeId = null;
        this.closeTimeout = null;
        this.isOpen = false;
        this.sizeCache = new Map();

        this.init();
        this.initHtmxEvents();
    }

    init() {
        this.navbar = document.querySelector('[data-navbar-morph]');
        this.dropdown = document.querySelector('[data-morph-dropdown]');
        this.box = document.querySelector('[data-morph-box]');
        this.triggers = document.querySelectorAll('[data-morph-trigger]');
        this.contents = document.querySelectorAll('[data-morph-content]');

        // Clear size cache on reinit
        this.sizeCache.clear();

        if (!this.navbar || !this.dropdown || !this.box || this.triggers.length === 0) return;

        this.triggerElements = Array.from(this.triggers);

        this.triggers.forEach(trigger => {
            trigger.addEventListener('mouseenter', (e) => this.handleTriggerEnter(e));
            trigger.addEventListener('mouseleave', (e) => this.handleTriggerLeave(e));
        });

        this.dropdown.addEventListener('mouseenter', () => this.cancelClose());
        this.dropdown.addEventListener('mouseleave', () => this.scheduleClose());

        document.addEventListener('click', (e) => {
            if (!this.navbar?.contains(e.target) && !this.dropdown?.contains(e.target)) {
                this.close();
            }
        });
    }

    initHtmxEvents() {
        document.addEventListener('htmx:afterSettle', () => this.init());
    }

    handleTriggerEnter(e) {
        const trigger = e.currentTarget;
        const id = trigger.getAttribute('data-morph-trigger');

        this.cancelClose();

        if (this.isOpen && this.activeId === id) return;

        if (this.isOpen) {
            this.switchTo(id, trigger);
        } else {
            this.open(id, trigger);
        }
    }

    handleTriggerLeave(e) {
        const relatedTarget = e.relatedTarget;
        if (relatedTarget && this.dropdown?.contains(relatedTarget)) return;
        if (relatedTarget && this.isTriggerElement(relatedTarget)) return;
        this.scheduleClose();
    }

    isTriggerElement(element) {
        return this.triggerElements.some(trigger => trigger === element || trigger.contains(element));
    }

    cancelClose() {
        if (this.closeTimeout) {
            clearTimeout(this.closeTimeout);
            this.closeTimeout = null;
        }
    }

    scheduleClose(delay = 100) {
        this.cancelClose();
        this.closeTimeout = setTimeout(() => this.close(), delay);
    }

    switchTo(id, trigger) {
        const content = document.querySelector(`[data-morph-content="${id}"]`);
        if (!content) return;

        this.triggers.forEach(t => t.classList.remove('is-active'));
        trigger.classList.add('is-active');

        const { width, height } = this.measureContent(content);
        this.box.style.width = `${width}px`;
        this.box.style.height = `${height}px`;

        this.contents.forEach(c => c.classList.remove('is-active'));
        content.classList.add('is-active');

        this.activeId = id;
        this.positionDropdown(trigger);
    }

    open(id, trigger) {
        const content = document.querySelector(`[data-morph-content="${id}"]`);
        if (!content) return;

        this.triggers.forEach(t => t.classList.remove('is-active'));
        trigger.classList.add('is-active');

        // Measure BEFORE portal (while content is in original context)
        const { width, height } = this.measureContent(content);
        this.box.style.width = `${width}px`;
        this.box.style.height = `${height}px`;

        this.contents.forEach(c => c.classList.remove('is-active'));
        content.classList.add('is-active');

        // Position BEFORE showing (moves to portal)
        this.positionDropdown(trigger);

        this.navbar.classList.add('dropdown-open');
        this.isOpen = true;
        this.activeId = id;

        // Add is-open in next frame for animation
        requestAnimationFrame(() => {
            this.dropdown.classList.add('is-open');
        });
    }

    close() {
        this.cancelClose();
        this.dropdown.classList.remove('is-open');
        this.navbar.classList.remove('dropdown-open');
        this.triggers.forEach(t => t.classList.remove('is-active'));
        this.isOpen = false;

        setTimeout(() => {
            if (!this.isOpen) {
                this.contents.forEach(c => c.classList.remove('is-active'));
                this.activeId = null;
                this.resetDropdownPosition();
                this.restoreFromPortal();
            }
        }, 300);
    }

    moveToPortal() {
        if (!this.dropdown || this.dropdown.parentElement === document.body) return;
        this.originalParent = this.dropdown.parentElement;
        document.body.appendChild(this.dropdown);
        this.dropdown.style.position = 'fixed';
        this.dropdown.style.zIndex = '9999';
    }

    restoreFromPortal() {
        if (!this.dropdown || !this.originalParent) return;
        if (this.dropdown.parentElement === document.body) {
            this.originalParent.appendChild(this.dropdown);
            this.dropdown.style.position = '';
            this.dropdown.style.zIndex = '';
        }
    }

    resetDropdownPosition() {
        if (!this.dropdown) return;
        this.dropdown.style.transform = '';
        this.dropdown.style.top = '';
        this.dropdown.style.left = '';
    }

    positionDropdown(trigger) {
        if (!trigger || !this.dropdown || !this.navbar) return;

        this.moveToPortal();

        const triggerRect = trigger.getBoundingClientRect();
        const navbarRect = this.navbar.getBoundingClientRect();
        const boxWidth = this.box ? this.box.offsetWidth : 300;

        this.dropdown.style.left = `${navbarRect.left}px`;
        this.dropdown.style.top = `${triggerRect.bottom}px`;

        let offsetX = triggerRect.left - navbarRect.left + (triggerRect.width / 2) - (boxWidth / 2);
        const maxX = navbarRect.width - boxWidth - 16;
        offsetX = Math.max(0, Math.min(offsetX, maxX));

        this.dropdown.style.transform = `translateX(${offsetX}px)`;
    }

    measureContent(content) {
        if (!content) return { width: 0, height: 0 };

        const id = content.getAttribute('data-morph-content');
        // if (id && this.sizeCache.has(id)) {
        //     return this.sizeCache.get(id);
        // }

        const wasActive = content.classList.contains('is-active');
        const originalStyle = content.style.cssText;

        content.classList.add('is-active');
        content.style.cssText = 'position:absolute; visibility:hidden; width:max-content; height:auto; top:0; left:0; pointer-events:none; transform:none;';

        let width;
        const grid = content.querySelector('.navbar-dropdown__grid');

        if (grid) {
            const items = grid.querySelectorAll('.navbar-dropdown__menu-item');
            const gap = parseFloat(getComputedStyle(grid).gap) || 8;
            const cols = getComputedStyle(grid).gridTemplateColumns.split(' ').length;

            let maxItemWidth = 0;
            items.forEach(item => {
                item.style.width = 'max-content';
                maxItemWidth = Math.max(maxItemWidth, item.scrollWidth);
                item.style.width = '';
            });

            const contentPadding = parseFloat(getComputedStyle(content).paddingLeft) * 2;
            width = (maxItemWidth * cols) + (gap * (cols - 1)) + contentPadding;
        } else {
            width = content.scrollWidth;
        }

        const minW = 240, maxW = 820;
        width = Math.max(minW, Math.min(Math.ceil(width), maxW));

        content.style.width = `${width}px`;

        void content.offsetHeight;
        void content.offsetWidth;
        window.getComputedStyle(content).height;

        const rect = content.getBoundingClientRect();
        const height = Math.ceil(rect.height);

        content.style.cssText = originalStyle;
        if (!wasActive) {
            content.classList.remove('is-active');
        }

        const result = {
            width: width,
            height: Math.max(80, Math.min(height, 720)),
        };

        if (id) {
            this.sizeCache.set(id, result);
        }

        return result;
    }
}

/**
 * Transparent Navbar Handler - handles scroll-based transitions for pill-transparent nav style
 */
class TransparentNavbarHandler {
    constructor() {
        this.navbar = null;
        this.scrollThreshold = 50;
        this.ticking = false;
        this.isScrolled = false;
        this.init();
    }

    init() {
        this.navbar = document.querySelector('.navbar');
        if (!this.navbar) return;

        this.checkNavStyle();

        window.addEventListener('scroll', () => this.onScroll(), { passive: true });

        this.observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.attributeName === 'data-nav-style') {
                    this.checkNavStyle();
                }
            });
        });

        this.observer.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['data-nav-style']
        });
    }

    checkNavStyle() {
        const navStyle = document.documentElement.getAttribute('data-nav-style');
        if (navStyle === 'pill-transparent') {
            this.updateScrollState(true);
        } else {
            document.documentElement.removeAttribute('data-nav-scrolled');
            this.isScrolled = false;
        }
    }

    onScroll() {
        if (document.documentElement.getAttribute('data-nav-style') !== 'pill-transparent') return;

        if (!this.ticking) {
            requestAnimationFrame(() => {
                this.updateScrollState();
                this.ticking = false;
            });
            this.ticking = true;
        }
    }

    updateScrollState(force = false) {
        const scrollY = window.scrollY || window.pageYOffset;
        const shouldBeScrolled = scrollY > this.scrollThreshold;

        if (force || shouldBeScrolled !== this.isScrolled) {
            this.isScrolled = shouldBeScrolled;
            document.documentElement.setAttribute('data-nav-scrolled', shouldBeScrolled ? 'true' : 'false');
        }
    }
}

let app;
let notyf;
let navbarMorphDropdown;
let transparentNavbarHandler;
let profileDropdown;

$(document).ready(function () {
    app = new FluteApp();
    notyf = app.notyf;
    navbarMorphDropdown = new NavbarMorphDropdown();
    transparentNavbarHandler = new TransparentNavbarHandler();
    profileDropdown = new ProfileDropdownManager();
});
