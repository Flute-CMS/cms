function initializeA11yDialog() {
    const modals = document.querySelectorAll('.modal, .right_sidebar');

    modals.forEach((modalElement) => {
        if (modalElement.dialogInstance) {
            return;
        }

        const dialog = new A11yDialog(modalElement);
        modalElement.dialogInstance = dialog;

        dialog.on('show', () => {
            modalElement.removeAttribute('aria-hidden');
            modalElement.classList.add('is-open');
            onModalShow(modalElement);
        });

        dialog.on('hide', () => {
            modalElement.setAttribute('aria-hidden', 'true');

            const handleAnimationEnd = (event) => {
                if (
                    event.animationName === 'mmfadeOut' ||
                    event.animationName === 'mmslideOut' ||
                    event.animationName === 'rightSidebarfadeOut' ||
                    event.animationName === 'rightSidebarslideOut'
                ) {
                    modalElement.classList.remove('is-open');
                    containerEl.removeEventListener(
                        'animationend',
                        handleAnimationEnd,
                    );
                    overlayEl.removeEventListener(
                        'animationend',
                        handleAnimationEnd,
                    );
                }
            };

            const containerEl = modalElement.querySelector(
                '.modal__container, .right_sidebar__container',
            );
            const overlayEl = modalElement.querySelector(
                '.modal__overlay, .right_sidebar__overlay',
            );

            if (containerEl) {
                containerEl.addEventListener(
                    'animationend',
                    handleAnimationEnd,
                );
            }
            if (overlayEl) {
                overlayEl.addEventListener('animationend', handleAnimationEnd);
            }

            onModalHide(modalElement);
        });
    });
}

$(document).on('click', '[data-a11y-dialog-hide]', function (event) {
    event.preventDefault();

    if (event.target !== event.currentTarget) return;

    const dialogElement = event.currentTarget.closest('[data-a11y-dialog]');
    if (dialogElement && dialogElement.dialogInstance) {
        dialogElement.dialogInstance.hide();
    }
});

window.addEventListener('DOMContentLoaded', () => {
    initializeA11yDialog();
});

document.body.addEventListener('htmx:afterSettle', (event) => {
    initializeA11yDialog(event.detail.elt);
});

function openModal(modalId) {
    const modalElement = document.getElementById(modalId);

    if (modalElement && modalElement.dialogInstance) {
        const $modal = $('#' + modalId);

        if (isMobileDevice()) {
            $modal.addClass('bottom-sheet');
            addDragHandle($modal);

            if ($modal[0].dialogInstance) {
                $modal[0].dialogInstance.show();
            }
        } else {
            if ($modal[0].dialogInstance) {
                $modal[0].dialogInstance.show();
            }
        }
    } else {
        console.warn(`Modal '${modalId}' wasn't found or not initialized.`);
    }
}

function closeModal(modalId) {
    const modalElement = document.getElementById(modalId);

    if (modalElement && modalElement.dialogInstance) {
        modalElement.dialogInstance.hide();
    } else {
        console.warn(`Modal '${modalId}' wasn't found or not initialized.`);
    }
}

$(document).on('click', '[data-modal-open]', function (event) {
    event.preventDefault();

    const modalId = $(this).attr('data-modal-open');
    const $modal = $('#' + modalId);

    if (!$modal.length) {
        console.warn(`Modal '${modalId}' wasn't found.`);
        return;
    }

    $(this).data('returnFocus', true);

    openModal(modalId);
});

function trapFocus(element) {
    const focusableElements = element.querySelectorAll(
        'a[href]:not([disabled]), button:not([disabled]), textarea:not([disabled]), input[type="text"]:not([disabled]), input[type="radio"]:not([disabled]), input[type="checkbox"]:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])',
    );

    if (focusableElements.length === 0) return;

    const firstElement = focusableElements[0];
    const lastElement = focusableElements[focusableElements.length - 1];

    focusableElements.forEach((el) => {
        if (!el.hasAttribute('tabindex')) {
            el.setAttribute(
                'data-original-tabindex',
                el.getAttribute('tabindex') || null,
            );
        }
    });

    element.addEventListener('keydown', function (e) {
        // If Escape key is pressed, close the modal
        if (e.key === 'Escape') {
            const closeButton = element.querySelector(
                '[data-a11y-dialog-hide], [data-modal-close]',
            );
            if (closeButton) {
                closeButton.click();
            }
            return;
        }

        // Only handle Tab key
        if (e.key !== 'Tab') return;

        // If Shift + Tab and focus is on first element, move to last element
        if (e.shiftKey && document.activeElement === firstElement) {
            e.preventDefault();
            lastElement.focus();
        }
        // If Tab and focus is on last element, move to first element
        else if (!e.shiftKey && document.activeElement === lastElement) {
            e.preventDefault();
            firstElement.focus();
        }
    });
}

function onModalShow(modalElement) {
    const $modalElement = $(modalElement);

    lockBodyScroll();

    const $autofocusElement = $modalElement.find('[autofocus]');
    if ($autofocusElement.length) {
        $autofocusElement[0].focus();
    } else {
        trapFocus(modalElement);
    }

    if (isMobileDevice()) {
        const $container = $modalElement.find('.modal__container');

        if ($container.length) {
            $container.css('height', '0vh');
            $container[0].offsetHeight;
        }

        calculateModalHeight($modalElement);
        addDragEvents($modalElement);

        const contentNode = $modalElement.find('.modal__content')[0];
        if (contentNode) {
            const observer = new MutationObserver(
                debounce(() => {
                    calculateModalHeight($modalElement);
                }, 100),
            );
            observer.observe(contentNode, {
                childList: true,
                subtree: true,
                characterData: true,
                attributes: true,
            });
            $modalElement.data('observer', observer);
        }

        const resizeHandler = debounce(() => {
            calculateModalHeight($modalElement);
        }, 100);
        $(window).on('resize', resizeHandler);
        $modalElement.data('resizeHandler', resizeHandler);
    }

    observeModalRemoval(modalElement);
}

function onModalHide(modalElement) {
    const $modalElement = $(modalElement);

    unlockBodyScroll();

    const triggerElements = document.querySelectorAll('[data-modal-open]');
    for (let i = 0; i < triggerElements.length; i++) {
        const trigger = triggerElements[i];
        if (
            $(trigger).data('returnFocus') &&
            $(trigger).attr('data-modal-open') === modalElement.id
        ) {
            trigger.focus();
            $(trigger).data('returnFocus', false);
            break;
        }
    }

    if (isMobileDevice()) {
        if ($modalElement.hasClass('bottom-sheet')) {
            closeBottomSheet($modalElement);
        } else {
            $modalElement.removeClass('fullscreen');
            removeDragEvents($modalElement);
        }

        const observer = $modalElement.data('observer');
        if (observer) {
            observer.disconnect();
            $modalElement.removeData('observer');
        }

        const resizeHandler = $modalElement.data('resizeHandler');
        if (resizeHandler) {
            $(window).off('resize', resizeHandler);
            $modalElement.removeData('resizeHandler');
        }
    }
}

function closeBottomSheet($modal) {
    const $container = $modal.find('.modal__container');
    $container.css('height', '0vh');

    $modal.removeClass('fullscreen');

    const handleTransitionEnd = (event) => {
        if (event.target === $container[0] && event.propertyName === 'height') {
            $modal.removeClass('bottom-sheet dragging');
            removeDragEvents($modal);
            $container.css('height', '');

            if ($modal[0].dialogInstance) {
                $modal[0].dialogInstance.hide();
            }
            $container.off('transitionend', handleTransitionEnd);
        }
    };
    $container.on('transitionend', handleTransitionEnd);
}

function calculateModalHeight($modal) {
    setTimeout(() => {
        const $content = $modal.find('.modal__content');
        const $header = $modal.find('.modal__header');
        const $dragHandle = $modal.find('.drag-handle');
        if (!$content.length || !$header.length || !$dragHandle.length) {
            return;
        }

        const contentHeightPx =
            $content.outerHeight(true) +
            $header.outerHeight(true) +
            $dragHandle.outerHeight(true);

        const windowHeightPx = $(window).height();
        let contentHeightVh = (contentHeightPx / windowHeightPx) * 100;

        if (contentHeightVh >= 100) {
            $modal.data('startHeightVh', 100);
            $modal.data('maxHeightVh', 100);
            $modal.addClass('fullscreen');
        } else {
            let startHeightVh = Math.min(contentHeightVh, 100);
            $modal.data('startHeightVh', startHeightVh);
            $modal.data('maxHeightVh', startHeightVh);
            $modal.removeClass('fullscreen');
        }

        const startHeightVh = $modal.data('startHeightVh');
        $modal.find('.modal__container').css('height', `${startHeightVh}vh`);
    }, 50);
}

function addDragHandle($modal) {
    if ($modal.find('.drag-handle').length === 0) {
        const dragHandleHTML = `
        <div class="drag-handle" tabindex="0" role="button" aria-label="Drag to resize modal" aria-expanded="false">
          <span></span>
        </div>
      `;
        $modal.find('.modal__container').prepend(dragHandleHTML);
    }
}

function addDragEvents($modal) {
    const $dragHandle = $modal.find('.drag-handle');
    if (!$dragHandle.length) return;

    let isDragging = false;
    let startY = 0;
    let startHeightVh = 0;

    function dragStart(e) {
        isDragging = true;
        startY =
            e.pageY ||
            (e.originalEvent.touches && e.originalEvent.touches[0].pageY);
        startHeightVh =
            ($modal.find('.modal__container').height() / $(window).height()) *
            100;

        $modal.addClass('dragging');
    }

    function dragging(e) {
        if (!isDragging) return;

        const currentY =
            e.pageY ||
            (e.originalEvent.touches && e.originalEvent.touches[0].pageY);
        if (!currentY) return;

        const delta = startY - currentY;
        const windowHeight = $(window).height();
        let newHeightVh = startHeightVh + (delta / windowHeight) * 100;

        const minHeightVh = 6;
        let maxHeightVh = ($modal.data('maxHeightVh') || 100) + 5;

        newHeightVh = Math.max(minHeightVh, Math.min(newHeightVh, maxHeightVh));
        $modal.find('.modal__container').css('height', `${newHeightVh}vh`);
    }

    function dragEnd() {
        if (!isDragging) return;
        isDragging = false;
        $modal.removeClass('dragging');

        const currentHeightVh =
            ($modal.find('.modal__container').height() / $(window).height()) *
            100;
        const startHeightVh = $modal.data('startHeightVh') || 50;
        const maxHeightVh = $modal.data('maxHeightVh') || 100;

        if (currentHeightVh < maxHeightVh / 3) {
            if ($modal[0].dialogInstance) {
                $modal[0].dialogInstance.hide();
            }
        } else if (currentHeightVh >= maxHeightVh - 5) {
            $modal.find('.modal__container').css('height', `${maxHeightVh}vh`);
            if (maxHeightVh === 100) {
                $modal.addClass('fullscreen');
                $dragHandle.attr('aria-expanded', 'true');
            }
        } else {
            $modal
                .find('.modal__container')
                .css('height', `${startHeightVh}vh`);
            $modal.removeClass('fullscreen');
            $dragHandle.attr('aria-expanded', 'false');
        }
    }

    $dragHandle.on('mousedown touchstart', dragStart);
    $(document).on('mousemove touchmove', dragging);
    $(document).on('mouseup touchend', dragEnd);

    // Add keyboard support for drag handle
    $dragHandle.on('keydown', function (event) {
        // Space or Enter activates the drag handle
        if (event.key === ' ' || event.key === 'Enter') {
            event.preventDefault();
            if (!$modal.hasClass('fullscreen')) {
                // Expand to full height
                $modal.find('.modal__container').css('height', '100vh');
                $modal.addClass('fullscreen');
                $dragHandle.attr('aria-expanded', 'true');
                $dragHandle.attr('aria-label', 'Collapse modal');
            } else {
                // Collapse to starting height
                const startHeightVh = $modal.data('startHeightVh') || 50;
                $modal
                    .find('.modal__container')
                    .css('height', `${startHeightVh}vh`);
                $modal.removeClass('fullscreen');
                $dragHandle.attr('aria-expanded', 'false');
                $dragHandle.attr('aria-label', 'Expand modal');
            }
        }
        // Escape key closes the modal
        else if (event.key === 'Escape') {
            if ($modal[0].dialogInstance) {
                $modal[0].dialogInstance.hide();
            }
        }
        // Arrow keys to adjust height
        else if (event.key === 'ArrowUp' || event.key === 'ArrowDown') {
            event.preventDefault();
            const step = 5; // 5% of viewport height
            const currentHeight = parseInt(
                $modal.find('.modal__container').css('height'),
            );
            const windowHeight = $(window).height();
            let currentHeightVh = (currentHeight / windowHeight) * 100;

            if (event.key === 'ArrowUp') {
                currentHeightVh = Math.min(100, currentHeightVh + step);
                if (currentHeightVh >= 95) {
                    $modal.addClass('fullscreen');
                    $dragHandle.attr('aria-expanded', 'true');
                }
            } else {
                currentHeightVh = Math.max(10, currentHeightVh - step);
                $modal.removeClass('fullscreen');
                $dragHandle.attr('aria-expanded', 'false');
            }

            $modal
                .find('.modal__container')
                .css('height', `${currentHeightVh}vh`);
        }
    });

    $modal.data('dragHandlers', { dragStart, dragging, dragEnd });
}

function removeDragEvents($modal) {
    const $dragHandle = $modal.find('.drag-handle');
    const handlers = $modal.data('dragHandlers');

    if (handlers) {
        $dragHandle.off('mousedown touchstart', handlers.dragStart);
        $(document).off('mousemove touchmove', handlers.dragging);
        $(document).off('mouseup touchend', handlers.dragEnd);
        $modal.removeData('dragHandlers');
    }

    $dragHandle.remove();
}

function lockBodyScroll() {
    document.body.classList.add('no-scroll');
}

function unlockBodyScroll() {
    document.body.classList.remove('no-scroll');
}

function observeModalRemoval(modalElement) {
    if (!modalElement.parentNode) return;

    const removalObserver = new MutationObserver(() => {
        if (!document.body.contains(modalElement)) {
            unlockBodyScroll();
            removalObserver.disconnect();
        }
    });

    removalObserver.observe(document.body, {
        childList: true,
        subtree: true,
    });
}

function setHandleAccessibility(handle, dialog) {
    if (!handle) return;

    handle.setAttribute('tabindex', '0');
    handle.setAttribute('role', 'button');
    handle.setAttribute('aria-label', 'Resize dialog');

    handle.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            // On Enter or Space, toggle between mid-height and full-height
            const currentPosition =
                dialog.style.getPropertyValue('--position-y');
            const newPosition = currentPosition === '50%' ? '10%' : '50%';
            dialog.style.setProperty('--position-y', newPosition);
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            // Move up (make taller)
            const currentPosition = parseFloat(
                dialog.style.getPropertyValue('--position-y') || '50',
            );
            const newPosition = Math.max(10, currentPosition - 10) + '%';
            dialog.style.setProperty('--position-y', newPosition);
        } else if (event.key === 'ArrowDown') {
            event.preventDefault();
            // Move down (make shorter)
            const currentPosition = parseFloat(
                dialog.style.getPropertyValue('--position-y') || '50',
            );
            const newPosition = Math.min(80, currentPosition + 10) + '%';
            dialog.style.setProperty('--position-y', newPosition);
        }
    });
}

function initializeAccessibilityDialog(dialog) {
    // Set up keyboard accessibility for the handle
    const handle = dialog.querySelector('.modal__handle');
    setHandleAccessibility(handle, dialog);
}
