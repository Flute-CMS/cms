(function () {
    const showDelay = 150;
    const hideDelay = 150;
    let showTimeout;
    let hideTimeout;
    let currentPopoverTrigger = null;

    function onMouseEnter(event) {
        const trigger = event.currentTarget;
        clearTimeout(hideTimeout);
        showTimeout = setTimeout(() => {
            showPopover(trigger);
        }, showDelay);
    }

    function onMouseLeave(event) {
        const trigger = event.currentTarget;
        clearTimeout(showTimeout);
        hideTimeout = setTimeout(() => {
            hidePopover(trigger);
        }, hideDelay);
    }

    function onFocus(event) {
        const trigger = event.currentTarget;
        clearTimeout(hideTimeout);
        showTimeout = setTimeout(() => {
            showPopover(trigger);
        }, showDelay);
    }

    function onBlur(event) {
        const trigger = event.currentTarget;
        clearTimeout(showTimeout);
        hideTimeout = setTimeout(() => {
            hidePopover(trigger);
        }, hideDelay);
    }

    function onKeyDown(event) {
        const trigger = event.currentTarget;
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            if (trigger._popover) {
                hidePopover(trigger);
            } else {
                showPopover(trigger);
            }
        }
    }

    function showPopover(trigger) {
        if (currentPopoverTrigger && currentPopoverTrigger !== trigger) {
            hidePopover(currentPopoverTrigger);
        }

        if (trigger._popover) return;

        const popover = document.createElement('div');
        popover.classList.add('popover');
        popover.setAttribute('role', 'tooltip');
        popover.innerHTML =
            trigger.getAttribute('data-popover-content') || 'No content.';

        const arrow = document.createElement('div');
        arrow.classList.add('popover-arrow');
        popover.appendChild(arrow);

        document.body.appendChild(popover);

        const {
            computePosition,
            offset,
            flip,
            shift,
            arrow: arrowMiddleware,
        } = FloatingUIDOM;

        computePosition(trigger, popover, {
            placement: 'top',
            middleware: [
                offset(16),
                flip(),
                shift({ padding: 5 }),
                arrowMiddleware({ element: arrow }),
            ],
        }).then(({ x, y, placement, middlewareData }) => {
            Object.assign(popover.style, {
                left: `${x}px`,
                top: `${y}px`,
            });

            const { x: arrowX, y: arrowY } = middlewareData.arrow;
            const staticSide = {
                top: 'bottom',
                right: 'left',
                bottom: 'top',
                left: 'right',
            }[placement.split('-')[0]];

            Object.assign(arrow.style, {
                left: arrowX != null ? `${arrowX}px` : '',
                top: arrowY != null ? `${arrowY}px` : '',
                [staticSide]: '-4px',
            });

            requestAnimationFrame(() => {
                popover.classList.add('visible');
                trigger.setAttribute('aria-expanded', 'true');
            });

            trigger._popover = popover;
            currentPopoverTrigger = trigger;

            popover.addEventListener('mouseenter', () => {
                clearTimeout(hideTimeout);
            });

            popover.addEventListener('mouseleave', () => {
                hideTimeout = setTimeout(() => {
                    hidePopover(trigger);
                }, hideDelay);
            });
        });
    }

    function hidePopover(trigger) {
        const popover = trigger._popover;
        if (popover) {
            popover.classList.remove('visible');
            popover.addEventListener('transitionend', () => {
                if (popover.parentElement) {
                    popover.parentElement.removeChild(popover);
                }
            });
            trigger.setAttribute('aria-expanded', 'false');
            trigger._popover = null;
            currentPopoverTrigger = null;
        }
    }

    function initializePopovers(root) {
        const scope = (root instanceof Element) ? root : document;
        const triggers = scope.querySelectorAll('[data-popover-trigger="true"]');

        triggers.forEach((trigger) => {
            if (trigger._popoverBound) return;
            trigger._popoverBound = true;

            trigger.addEventListener('mouseenter', onMouseEnter);
            trigger.addEventListener('mouseleave', onMouseLeave);
            trigger.addEventListener('focus', onFocus);
            trigger.addEventListener('blur', onBlur);
            trigger.addEventListener('keydown', onKeyDown);
        });
    }

    window.initializePopovers = initializePopovers;

    document.addEventListener('DOMContentLoaded', () => {
        initializePopovers();
    });

})();
