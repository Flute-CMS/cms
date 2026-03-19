/**
 * Steps / Stepper system (theme)
 * - Variants: dots (default), progress, minimal
 * - Linear mode: sequential navigation only (cannot skip ahead)
 * - HTMX integration: lazy-load step panels via hx-get
 * - Keyboard: arrow keys, Home/End
 * - Navigation: data-steps-next / data-steps-prev buttons
 * - Events dispatched: steps:change, steps:complete
 */

(() => {
    if (window.__fluteThemeStepsInitialized) return;
    window.__fluteThemeStepsInitialized = true;

    const raf = (cb) => requestAnimationFrame(cb);

    // ---- Helpers ----

    const getContainer = (el) => el?.closest('.steps-container');

    const getStepsId = (container) =>
        container?.getAttribute('data-steps-id') || 'default';

    const isLinear = (container) =>
        container?.getAttribute('data-steps-linear') === 'true';

    const getStepItems = (container) => {
        if (!container) return [];
        return Array.from(container.querySelectorAll('.steps-list > .step-item'));
    };

    const getActiveIndex = (items) =>
        items.findIndex((item) => item.classList.contains('active'));

    const getStepPanel = (container, stepId) => {
        if (!container || !stepId) return null;
        const content = container.querySelector('.steps-content');
        if (!content) return null;
        try {
            return (
                content.querySelector(`:scope > #${CSS.escape(stepId)}`) ||
                content.querySelector(`#${CSS.escape(stepId)}`)
            );
        } catch (_) {
            return content.querySelector('#' + stepId);
        }
    };

    // ---- Progress bar (variant: progress) ----

    const updateProgress = (container) => {
        if (!container?.classList.contains('steps--progress')) return;
        const items = getStepItems(container);
        if (items.length < 2) return;

        const activeIdx = getActiveIndex(items);
        const completedCount = items.filter((i) =>
            i.classList.contains('completed'),
        ).length;

        const furthest = Math.max(activeIdx, completedCount);
        const pct = (furthest / (items.length - 1)) * 100;

        container
            .querySelector('.steps-nav')
            ?.style.setProperty('--steps-progress', pct + '%');
    };

    // ---- Linear mode: lock/unlock step headings ----

    const updateLinearLocks = (container) => {
        if (!isLinear(container)) return;
        const items = getStepItems(container);
        const activeIdx = getActiveIndex(items);

        items.forEach((item, i) => {
            const trigger = item.querySelector('.step-item__trigger');
            if (!trigger) return;

            // Can click: completed steps, active step, or the very next step
            const isCompleted = item.classList.contains('completed');
            const isActive = item.classList.contains('active');
            const isNext = i === activeIdx + 1;

            if (isCompleted || isActive || isNext) {
                item.classList.remove('is-locked');
                trigger.removeAttribute('disabled');
                trigger.removeAttribute('aria-disabled');
            } else {
                item.classList.add('is-locked');
                trigger.setAttribute('disabled', '');
                trigger.setAttribute('aria-disabled', 'true');
            }
        });
    };

    // ---- Core: activate step ----

    const activateStep = (container, targetItem, options) => {
        if (!container || !targetItem) return false;
        options = options || {};

        const items = getStepItems(container);
        const targetIdx = items.indexOf(targetItem);
        if (targetIdx === -1) return false;

        const activeIdx = getActiveIndex(items);
        if (targetIdx === activeIdx && !options.force) return false;

        // Linear mode: block skipping ahead beyond completed + 1
        if (isLinear(container) && !options.force) {
            // Find the furthest completed index
            let furthestCompleted = -1;
            items.forEach((item, i) => {
                if (item.classList.contains('completed')) furthestCompleted = i;
            });

            const maxAllowed = Math.max(activeIdx + 1, furthestCompleted + 1);
            if (targetIdx > maxAllowed) {
                return false;
            }
        }

        const stepId = targetItem.getAttribute('data-step-id');
        const stepsId = getStepsId(container);

        // Dispatch cancelable before-change event
        const beforeEvent = new CustomEvent('steps:before-change', {
            bubbles: true,
            cancelable: true,
            detail: {
                stepsId,
                stepId,
                stepIndex: targetIdx,
                stepName: targetItem.getAttribute('data-step-heading'),
                fromIndex: activeIdx,
                fromName: activeIdx >= 0 ? items[activeIdx]?.getAttribute('data-step-heading') : null,
            },
        });
        if (!container.dispatchEvent(beforeEvent)) return false;

        // Mark previous steps as completed
        items.forEach((item, i) => {
            item.classList.remove('active');
            const trigger = item.querySelector('.step-item__trigger');
            if (trigger) trigger.setAttribute('aria-current', 'false');

            if (i < targetIdx) {
                item.classList.add('completed');
            } else if (i > targetIdx && isLinear(container)) {
                item.classList.remove('completed');
            }
        });

        // Activate target
        targetItem.classList.add('active');
        targetItem.classList.remove('completed');
        const trigger = targetItem.querySelector('.step-item__trigger');
        if (trigger) trigger.setAttribute('aria-current', 'step');

        // Show step panel
        showPanel(container, stepId);

        // Load via HTMX if url specified
        const url = trigger?.getAttribute('data-step-url');
        if (url) {
            lazyLoadPanel(container, stepId, url);
        }

        // Update progress bar & linear locks
        updateProgress(container);
        updateLinearLocks(container);

        // Dispatch event
        container.dispatchEvent(
            new CustomEvent('steps:change', {
                bubbles: true,
                detail: {
                    stepsId,
                    stepId,
                    stepIndex: targetIdx,
                    stepName: targetItem.getAttribute('data-step-heading'),
                    totalSteps: items.length,
                    isLast: targetIdx === items.length - 1,
                    isFirst: targetIdx === 0,
                },
            }),
        );

        // Dispatch complete if last step
        if (targetIdx === items.length - 1) {
            container.dispatchEvent(
                new CustomEvent('steps:complete', {
                    bubbles: true,
                    detail: { stepsId, stepId },
                }),
            );
        }

        return true;
    };

    // ---- Panel management ----

    const showPanel = (container, stepId) => {
        if (!container || !stepId) return;
        const content = container.querySelector('.steps-content');
        if (!content) return;

        content.querySelectorAll(':scope > .step-panel').forEach((panel) => {
            panel.classList.remove('active');
            panel.style.display = 'none';
        });

        const target = getStepPanel(container, stepId);
        if (target) {
            target.classList.add('active');
            target.style.display = '';
        }
    };

    // ---- HTMX lazy loading ----

    const lazyLoadPanel = (container, stepId, url) => {
        const panel = getStepPanel(container, stepId);
        if (!panel) return;

        if (panel.getAttribute('data-step-loaded') === '1') return;

        if (typeof htmx !== 'undefined') {
            panel.setAttribute('hx-get', url);
            panel.setAttribute('hx-swap', 'innerHTML');
            panel.setAttribute('hx-trigger', 'load');
            htmx.process(panel);
            htmx.trigger(panel, 'load');
        } else {
            fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'HX-Request': 'true',
                },
            })
                .then((r) => r.text())
                .then((html) => {
                    panel.innerHTML = html;
                    panel.setAttribute('data-step-loaded', '1');
                })
                .catch(() => {});
        }
    };

    // ---- Click handler ----

    const onStepClick = (e) => {
        const trigger = e.target.closest?.('.step-item__trigger');
        if (!trigger) return;

        const item = trigger.closest('.step-item');
        if (!item) return;
        if (
            trigger.disabled ||
            trigger.getAttribute('aria-disabled') === 'true'
        )
            return;

        const container = getContainer(item);
        if (!container) return;

        e.preventDefault();
        activateStep(container, item);
    };

    document.addEventListener('click', onStepClick, true);

    // ---- Next / Prev buttons ----

    document.addEventListener('click', (e) => {
        const nextBtn = e.target.closest?.('[data-steps-next]');
        const prevBtn = e.target.closest?.('[data-steps-prev]');
        const btn = nextBtn || prevBtn;
        if (!btn) return;

        const container = getContainer(btn);
        if (!container) return;

        const items = getStepItems(container);
        const activeIdx = getActiveIndex(items);
        if (activeIdx === -1) return;

        const targetIdx = nextBtn ? activeIdx + 1 : activeIdx - 1;
        if (targetIdx < 0 || targetIdx >= items.length) return;

        activateStep(container, items[targetIdx]);
    });

    // ---- Keyboard navigation ----

    document.addEventListener('keydown', (e) => {
        const trigger = e.target.closest?.('.step-item__trigger');
        if (!trigger) return;

        const item = trigger.closest('.step-item');
        const container = getContainer(item);
        if (!container) return;

        const items = getStepItems(container).filter(
            (i) =>
                !i.classList.contains('is-disabled') &&
                !i.classList.contains('is-locked'),
        );
        const triggers = items.map((i) =>
            i.querySelector('.step-item__trigger'),
        );
        const idx = triggers.indexOf(trigger);
        if (idx === -1) return;

        let next;
        switch (e.key) {
            case 'ArrowRight':
            case 'ArrowDown':
                next = triggers[(idx + 1) % triggers.length];
                break;
            case 'ArrowLeft':
            case 'ArrowUp':
                next = triggers[(idx - 1 + triggers.length) % triggers.length];
                break;
            case 'Home':
                next = triggers[0];
                break;
            case 'End':
                next = triggers[triggers.length - 1];
                break;
            case 'Enter':
            case ' ':
                e.preventDefault();
                trigger.click();
                return;
            default:
                return;
        }

        e.preventDefault();
        next?.focus();
    });

    // ---- Programmatic API ----

    window.FluteSteps = {
        goTo(container, stepName) {
            if (typeof container === 'string') {
                container = document.querySelector(
                    `[data-steps-id="${CSS.escape(container)}"]`,
                );
            }
            if (!container) return false;
            const item = container.querySelector(
                `.step-item[data-step-heading="${stepName}"]`,
            );
            return activateStep(container, item);
        },

        goToIndex(container, index) {
            if (typeof container === 'string') {
                container = document.querySelector(
                    `[data-steps-id="${CSS.escape(container)}"]`,
                );
            }
            if (!container) return false;
            const items = getStepItems(container);
            if (index < 0 || index >= items.length) return false;
            return activateStep(container, items[index]);
        },

        next(container) {
            if (typeof container === 'string') {
                container = document.querySelector(
                    `[data-steps-id="${CSS.escape(container)}"]`,
                );
            }
            if (!container) return false;
            const items = getStepItems(container);
            const idx = getActiveIndex(items);
            if (idx === -1 || idx >= items.length - 1) return false;
            return activateStep(container, items[idx + 1]);
        },

        prev(container) {
            if (typeof container === 'string') {
                container = document.querySelector(
                    `[data-steps-id="${CSS.escape(container)}"]`,
                );
            }
            if (!container) return false;
            const items = getStepItems(container);
            const idx = getActiveIndex(items);
            if (idx <= 0) return false;
            return activateStep(container, items[idx - 1]);
        },

        reset(container) {
            if (typeof container === 'string') {
                container = document.querySelector(
                    `[data-steps-id="${CSS.escape(container)}"]`,
                );
            }
            if (!container) return false;
            const items = getStepItems(container);
            items.forEach((item) => {
                item.classList.remove('active', 'completed', 'is-locked');
            });
            if (items[0]) {
                return activateStep(container, items[0], { force: true });
            }
            return false;
        },

        getActive(container) {
            if (typeof container === 'string') {
                container = document.querySelector(
                    `[data-steps-id="${CSS.escape(container)}"]`,
                );
            }
            if (!container) return null;
            const items = getStepItems(container);
            const idx = getActiveIndex(items);
            if (idx === -1) return null;
            return {
                index: idx,
                name: items[idx].getAttribute('data-step-heading'),
                element: items[idx],
                isFirst: idx === 0,
                isLast: idx === items.length - 1,
            };
        },
    };

    // ---- Init ----

    const initSteps = (container) => {
        if (!container) return;
        if (container.getAttribute('data-steps-init') === '1') return;
        container.setAttribute('data-steps-init', '1');

        const items = getStepItems(container);
        if (!items.length) return;

        // Set step count for progress bar track calculation
        const list = container.querySelector('.steps-list');
        if (list) {
            list.style.setProperty('--steps-count', items.length);
        }

        // Ensure at least one active
        const hasActive = items.some((i) => i.classList.contains('active'));
        if (!hasActive) {
            items[0].classList.add('active');
            const trigger = items[0].querySelector('.step-item__trigger');
            if (trigger) trigger.setAttribute('aria-current', 'step');
        }

        // Show the active panel
        const activeItem = items.find((i) => i.classList.contains('active'));
        if (activeItem) {
            const stepId = activeItem.getAttribute('data-step-id');
            showPanel(container, stepId);

            const trigger = activeItem.querySelector('.step-item__trigger');
            const url = trigger?.getAttribute('data-step-url');
            if (url) lazyLoadPanel(container, stepId, url);
        }

        updateProgress(container);
        updateLinearLocks(container);
    };

    const processStepsIn = (root) => {
        const scope = root || document;
        const containers = scope.querySelectorAll?.('.steps-container') || [];
        containers.forEach(initSteps);
    };

    const onReady = () => processStepsIn(document);
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', onReady, { once: true });
    } else {
        onReady();
    }

    // ---- HTMX integration ----

    if (typeof htmx !== 'undefined') {
        htmx.on('htmx:afterSwap', (event) => {
            const swapped = event.detail?.target || event.target;
            if (!swapped) return;

            if (swapped.classList?.contains('step-panel')) {
                swapped.setAttribute('data-step-loaded', '1');
            }

            if (swapped.classList?.contains('steps-container')) {
                initSteps(swapped);
            } else if (swapped.querySelectorAll) {
                processStepsIn(swapped);
            }
        });

        htmx.on('htmx:historyRestore', () => {
            raf(() => processStepsIn(document));
        });
    }
})();
