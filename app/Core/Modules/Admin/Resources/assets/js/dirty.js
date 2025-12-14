(() => {
    // Singleton guard: only one dirty tracker per page
    if (window.__fluteDirtyTracker) return;

    const state = {
        dirty: false,
        pendingNav: null,
        bypass: new WeakSet(),
        initialValues: new Map(),
        trackedElements: new WeakSet(),
    };

    const getScreen = () => document.getElementById('screen-container');
    const getBar = () => document.getElementById('admin-dirty-bar');
    const dialogId = 'dirty-dialog';
    const isAnyModalOpen = () => {
        return !!document.querySelector('.modal.is-open, .right_sidebar.is-open');
    };
    const syncBarVisibility = () => {
        const bar = getBar();
        if (!bar) return;
        if (isAnyModalOpen()) {
            bar.classList.remove('is-visible');
            return;
        }
        bar.classList.toggle('is-visible', !!state.dirty);
    };

    const isEnabled = () => {
        const screen = getScreen();
        return screen?.getAttribute('data-dirty-enabled') === 'true';
    };

    const loadConfig = () => {
        const screen = getScreen();
        if (!screen) return {};
        try {
            return JSON.parse(screen.getAttribute('data-dirty-config') || '{}') || {};
        } catch (_) {
            return {};
        }
    };

    const isInScreen = (el) => {
        const screen = getScreen();
        return !!(screen && el && screen.contains(el));
    };

    const shouldTrack = (el) => {
        if (!el || !isInScreen(el)) return false;
        if (el.closest('[data-no-dirty]')) return false;
        if (el.hasAttribute('data-ignore-dirty')) return false;
        if (el.disabled) return false;
        const tag = el.tagName ? el.tagName.toLowerCase() : '';
        if (tag === 'input') {
            const type = (el.getAttribute('type') || '').toLowerCase();
            if (['submit', 'button', 'reset', 'hidden'].includes(type)) return false;
        }
        return ['input', 'select', 'textarea'].includes(tag) || el.isContentEditable;
    };

    // Get unique key for element
    const getElementKey = (el) => {
        const name = el.name || el.id || '';
        const type = el.type || el.tagName.toLowerCase();
        // For checkboxes/radios, include value in key
        if (type === 'checkbox' || type === 'radio') {
            return `${name}::${type}::${el.value}`;
        }
        return `${name}::${type}`;
    };

    // Get current value of element
    const getElementValue = (el) => {
        const tag = el.tagName.toLowerCase();
        const type = (el.type || '').toLowerCase();

        if (tag === 'input') {
            if (type === 'checkbox' || type === 'radio') {
                return el.checked;
            }
            return el.value;
        }
        if (tag === 'select') {
            if (el.multiple) {
                return Array.from(el.selectedOptions).map(o => o.value).join(',');
            }
            return el.value;
        }
        if (tag === 'textarea') {
            return el.value;
        }
        if (el.isContentEditable) {
            return el.innerHTML;
        }
        return el.value || '';
    };

    // Capture initial values of all tracked elements
    const captureInitialValues = () => {
        const screen = getScreen();
        if (!screen) return;

        state.initialValues.clear();
        const elements = screen.querySelectorAll('input, select, textarea, [contenteditable="true"]');
        elements.forEach(el => {
            if (shouldTrack(el)) {
                const key = getElementKey(el);
                state.initialValues.set(key, getElementValue(el));
                state.trackedElements.add(el);
            }
        });
    };

    // Check if current values match initial values
    const checkIfDirty = () => {
        const screen = getScreen();
        if (!screen) return false;

        const elements = screen.querySelectorAll('input, select, textarea, [contenteditable="true"]');
        for (const el of elements) {
            if (!shouldTrack(el)) continue;
            const key = getElementKey(el);
            const initial = state.initialValues.get(key);
            const current = getElementValue(el);

            // If element wasn't tracked initially, any value makes it dirty
            if (initial === undefined) {
                if (current !== '' && current !== false) return true;
                continue;
            }

            if (current !== initial) return true;
        }
        return false;
    };

    const setDirty = (dirty) => {
        state.dirty = !!dirty;
        syncBarVisibility();
    };

    const updateDirtyState = () => {
        if (!isEnabled()) return;
        const isDirty = checkIfDirty();
        setDirty(isDirty);
    };

    const findSaveElement = () => {
        const screen = getScreen();
        if (!screen) return null;
        const config = loadConfig();

        const saveSelector = config?.save?.selector;
        if (saveSelector) return screen.querySelector(saveSelector) || document.querySelector(saveSelector);

        const yoyoPost = config?.save?.yoyoPost;
        if (yoyoPost) return screen.querySelector(`[yoyo\\:post="${CSS.escape(yoyoPost)}"]`);

        return screen.querySelector('[yoyo\\:post="save"]');
    };

    const performSave = (callback) => {
        const el = findSaveElement();
        if (el) {
            // Ensure yoyo/htmx request includes all current screen fields (selects/toggles/etc).
            // Some action buttons are rendered outside of a <form>, so without hx-include only a
            // subset of values may be sent.
            let cleanupHxInclude = null;
            try {
                const current = el.getAttribute('hx-include');
                if (!current) {
                    el.setAttribute('hx-include', '#screen-container');
                    cleanupHxInclude = () => {
                        try {
                            el.removeAttribute('hx-include');
                        } catch (_) {}
                    };
                }
            } catch (_) {}

            // Store callback for after save
            state.afterSaveCallback = callback;
            state.pendingAction = 'save';
            state.pendingActionEl = el;
            el.click();
            if (cleanupHxInclude) setTimeout(cleanupHxInclude, 0);
            return true;
        }
        const screen = getScreen();
        const form = screen?.querySelector('form');
        if (form) {
            state.afterSaveCallback = callback;
            state.pendingAction = 'save';
            state.pendingActionEl = form;
            try {
                form.requestSubmit();
            } catch (_) {
                form.submit();
            }
            return true;
        }
        return false;
    };

    const findDiscardElement = () => {
        const screen = getScreen();
        if (!screen) return null;
        const config = loadConfig();
        const sel = config?.discard?.selector;
        if (sel) return screen.querySelector(sel) || document.querySelector(sel);
        const yoyoPost = config?.discard?.yoyoPost;
        if (yoyoPost) return screen.querySelector(`[yoyo\\:post="${CSS.escape(yoyoPost)}"]`);
        // default: try yoyo:post="discard" if present
        return screen.querySelector('[yoyo\\:post="discard"]');
    };

    const performDiscard = (callback) => {
        const config = loadConfig();
        const discardEl = findDiscardElement();

        // Prefer calling screen discard method if present; fallback to reload
        if (discardEl) {
            state.afterDiscardCallback = callback;
            state.pendingAction = 'discard';
            state.pendingActionEl = discardEl;
            discardEl.click();
            return true;
        }

        // If no discard method exists, fallback to reload (safe revert)
        setDirty(false);
        state.pendingNav = null;
        if (config?.discard?.mode === 'reload' || !config?.discard) {
            window.location.reload();
            return true;
        }
        window.location.reload();
        return true;
    };

    const openDirtyDialog = () => {
        if (typeof window.openModal === 'function') {
            window.openModal(dialogId);
        }
    };

    const closeDirtyDialog = () => {
        if (typeof window.closeModal === 'function') {
            window.closeModal(dialogId);
        }
    };

    const runPendingNav = () => {
        const pending = state.pendingNav;
        state.pendingNav = null;
        if (!pending) return;

        // Delay slightly to allow modal to close and DOM to settle
        requestAnimationFrame(() => {
            setTimeout(() => {
                if (pending.type === 'href' && pending.href) {
                    window.location.assign(pending.href);
                    return;
                }

                if (pending.type === 'click' && pending.el) {
                    state.bypass.add(pending.el);
                    pending.el.click();
                }
            }, 30);
        });
    };

    // Event handlers
    const onInput = (e) => {
        if (!isEnabled()) return;
        if (!shouldTrack(e.target)) return;
        updateDirtyState();
    };

    const onChange = (e) => {
        if (!isEnabled()) return;
        if (!shouldTrack(e.target)) return;
        updateDirtyState();
    };

    const onBeforeUnload = (e) => {
        if (!state.dirty) return;
        e.preventDefault();
        e.returnValue = '';
    };

    const onBarClick = (e) => {
        const saveBtn = e.target.closest?.('[data-dirty-save]');
        if (saveBtn) {
            e.preventDefault();
            e.stopPropagation();
            closeDirtyDialog();
            const pendingNav = state.pendingNav;
            performSave(() => {
                // defer nav until we confirm save succeeded
                if (pendingNav) state.pendingNav = pendingNav;
            });
            return;
        }

        const discardBtn = e.target.closest?.('[data-dirty-discard]');
        if (discardBtn) {
            e.preventDefault();
            e.stopPropagation();
            closeDirtyDialog();
            const pendingNav = state.pendingNav;
            performDiscard(() => {
                if (pendingNav) state.pendingNav = pendingNav;
            });
            return;
        }

        const stayBtn = e.target.closest?.('[data-dirty-stay]');
        if (stayBtn) {
            e.preventDefault();
            e.stopPropagation();
            state.pendingNav = null;
            closeDirtyDialog();
        }
    };

    const onNavClick = (e) => {
        if (!isEnabled()) return;
        if (!state.dirty) return;
        // Don't show/handle dirty while any modal is open (avoid UX clash)
        if (isAnyModalOpen()) return;

        const a = e.target.closest?.('a');
        if (!a) return;

        // Allow bypassed clicks through
        if (state.bypass.has(a)) {
            state.bypass.delete(a);
            return;
        }

        // Ignore dirty bar/dialog links
        if (a.closest('#admin-dirty-bar') || a.closest('#dirty-dialog') || a.closest('[data-a11y-dialog]')) return;
        if (a.hasAttribute('data-ignore-dirty')) return;

        const isTabs = !!a.closest('.tabs-nav');
        const isSidebar = !!a.closest('.sidebar') || !!a.closest('[data-sidebar]');
        const isBoost = a.getAttribute('hx-boost') === 'true' || a.hasAttribute('hx-get');
        const href = a.getAttribute('href');
        const isRealNav = href && href !== '#' && !href.startsWith('javascript:');

        if (!(isTabs || isSidebar || isBoost || isRealNav)) return;

        e.preventDefault();
        e.stopPropagation();

        // Determine nav type
        if (isRealNav && !isBoost && !isTabs && !isSidebar) {
            state.pendingNav = { type: 'href', href };
        } else {
            state.pendingNav = { type: 'click', el: a };
        }
        
        openDirtyDialog();
    };

    // Register handlers once
    document.addEventListener('input', onInput, true);
    document.addEventListener('change', onChange, true);
    window.addEventListener('beforeunload', onBeforeUnload);
    document.addEventListener('click', onBarClick, true);
    document.addEventListener('click', onNavClick, true);

    // Initialize on DOMContentLoaded
    const initDirty = () => {
        if (isEnabled()) {
            captureInitialValues();
            syncBarVisibility();
        }
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDirty);
    } else {
        initDirty();
    }

    // HTMX integration
    if (typeof htmx !== 'undefined') {
        htmx.on('htmx:afterRequest', (event) => {
            if (!isEnabled()) return;
            const elt = event.detail?.elt;
            const xhr = event.detail?.xhr;
            if (!elt || !xhr) return;

            const saveEl = findSaveElement();
            const isSaveAction =
                (saveEl && (elt === saveEl || saveEl.contains(elt) || elt.contains(saveEl))) ||
                (!!elt.closest?.('[yoyo\\:post="save"]') || elt.getAttribute?.('yoyo:post') === 'save');

            if (!isSaveAction) return;

            if (xhr.status >= 200 && xhr.status < 300) {
                // Re-capture initial values after successful save
                captureInitialValues();
                setDirty(false);
                closeDirtyDialog();
                
                // Execute callback if any
                if (state.afterSaveCallback) {
                    const cb = state.afterSaveCallback;
                    state.afterSaveCallback = null;
                    cb();
                }
                // Only continue navigation if there was something pending
                if (state.pendingNav) runPendingNav();
            }
        });

        // Handle discard success similarly (if discard is implemented as yoyo method)
        htmx.on('htmx:afterRequest', (event) => {
            if (!isEnabled()) return;
            const elt = event.detail?.elt;
            const xhr = event.detail?.xhr;
            if (!elt || !xhr) return;
            if (state.pendingAction !== 'discard') return;
            if (!state.pendingActionEl) return;
            const ok = xhr.status >= 200 && xhr.status < 300;
            // Best-effort: ensure this request is coming from the discard trigger.
            const matches =
                elt === state.pendingActionEl ||
                (state.pendingActionEl.contains && state.pendingActionEl.contains(elt)) ||
                (elt.contains && elt.contains(state.pendingActionEl));
            if (!matches) return;
            if (!ok) return;

            state.pendingAction = null;
            state.pendingActionEl = null;
            captureInitialValues();
            setDirty(false);
            closeDirtyDialog();

            if (state.afterDiscardCallback) {
                const cb = state.afterDiscardCallback;
                state.afterDiscardCallback = null;
                cb();
            }
            if (state.pendingNav) runPendingNav();
        });

        // Re-init on htmx load (after swap)
        htmx.onLoad((target) => {
            if (target?.id === 'screen-container' || target?.closest?.('#screen-container')) {
                // Screen content changed, re-capture initial values
                setTimeout(() => {
                    if (isEnabled()) {
                        captureInitialValues();
                        setDirty(false);
                        syncBarVisibility();
                    }
                }, 50);
            }
        });

        // Reset state on htmx navigation (screen swap)
        htmx.on('htmx:beforeSwap', (event) => {
            const target = event.detail?.target;
            if (target?.id === 'screen-container' || target?.closest?.('#screen-container')) {
                // Screen is being replaced, reset dirty state
                setDirty(false);
                state.pendingNav = null;
                state.initialValues.clear();
            }
        });
    }

    // Public API
    window.__fluteDirtyTracker = true;
    window.AdminDirty = {
        isDirty: () => state.dirty,
        setDirty,
        updateDirtyState,
        reset: () => {
            setDirty(false);
            state.pendingNav = null;
            captureInitialValues();
        },
        recapture: captureInitialValues,
    };
})();
