function initializeA11yDialog(parentElement = document) {
    const modals = parentElement.querySelectorAll('.modal, .right_sidebar');

    modals.forEach((modalElement) => {
        if (modalElement.dialogInstance) {
            return;
        }

        const arrivedOpen = modalElement.classList.contains('is-open');
        const container = modalElement.querySelector('.modal__container, .right_sidebar__container');

        // For modals arriving already open on mobile (HTMX swap):
        // prepare the drawer state and animate the slide-up.
        if (arrivedOpen && isMobileDevice() && container) {
            modalElement.classList.add('bottom-sheet');

            // 1. Force container below viewport (hidden) with no transition.
            container.style.transition = 'none';
            container.style.transform = 'translateY(100%)';
            void container.offsetHeight; // flush layout

            // 2. Measure and set correct height while hidden.
            setModalHeightSync(modalElement);
            void container.offsetHeight; // flush

            // 3. Restore transitions and remove inline transform
            //    → CSS transition kicks in and slides the modal up.
            container.style.transition = '';
            container.style.transform = '';

            // 4. Attach drag events immediately — don't wait for show chain.
            addDragEvents(modalElement);
            setupMobileObservers(modalElement);
            lockBodyScroll();
        }

        const dialog = new A11yDialog(modalElement);
        modalElement.dialogInstance = dialog;

        dialog.on('show', () => {
            modalElement.removeAttribute('aria-hidden');
            modalElement.classList.add('is-open');
            modalElement.removeAttribute('tabindex');
            onModalShow(modalElement);
        });

        dialog.on('hide', () => {
            modalElement.setAttribute('aria-hidden', 'true');

            if (isMobileDevice()) {
                const ctr = modalElement.querySelector('.modal__container');
                const ovl = modalElement.querySelector('.modal__overlay');
                if (ctr) {
                    // Slide container down and fade overlay simultaneously.
                    ctr.style.transform = 'translateY(100%)';
                    if (ovl) ovl.style.opacity = '0';

                    let cleaned = false;
                    const cleanup = () => {
                        if (cleaned) return;
                        cleaned = true;
                        ctr.removeEventListener('transitionend', onEnd);
                        modalElement.classList.remove('is-open');
                        ctr.style.transform = '';
                        if (ovl) ovl.style.opacity = '';
                        onModalHide(modalElement);
                    };

                    const onEnd = (e) => {
                        if (e.target !== ctr || e.propertyName !== 'transform') return;
                        cleanup();
                    };
                    ctr.addEventListener('transitionend', onEnd);

                    setTimeout(cleanup, 400);
                } else {
                    modalElement.classList.remove('is-open');
                    onModalHide(modalElement);
                }
                return;
            }

            const handleAnimationEnd = (event) => {
                const expectedAnimations = [
                    'mmfadeOut',
                    'mmslideOut',
                    'rightSidebarfadeOut',
                    'rightSidebarslideOut',
                ];
                if (expectedAnimations.includes(event.animationName)) {
                    modalElement.classList.remove('is-open');
                    if (containerEl) {
                        containerEl.removeEventListener(
                            'animationend',
                            handleAnimationEnd,
                        );
                    }
                    if (overlayEl) {
                        overlayEl.removeEventListener(
                            'animationend',
                            handleAnimationEnd,
                        );
                    }
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

            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                handleAnimationEnd({ animationName: expectedAnimations[0] });
            }

            onModalHide(modalElement);
        });

        if (arrivedOpen) {
            modalElement._heightReady = true;
            dialog.show();
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initializeA11yDialog();
});

// --- Two detection mechanisms for modals added after page load ---

// 1. MutationObserver: catches any DOM insertion.
const _modalObserver = new MutationObserver((mutations) => {
    let needsInit = false;
    for (const mutation of mutations) {
        for (const node of mutation.addedNodes) {
            if (node.nodeType !== 1) continue;
            if (node.matches && node.matches('.modal, .right_sidebar')) { needsInit = true; break; }
            if (node.querySelector && node.querySelector('.modal, .right_sidebar')) { needsInit = true; break; }
        }
        if (needsInit) break;
    }
    if (needsInit) {
        initializeA11yDialog(document);
    }
});
_modalObserver.observe(document.body, { childList: true, subtree: true });

// 2. htmx:afterSettle: backup for HTMX/Yoyo swaps.
document.body.addEventListener('htmx:afterSettle', () => {
    initializeA11yDialog(document);
});

function hasOpenModals() {
    return document.querySelectorAll('.modal.is-open, .right_sidebar.is-open').length > 0;
}

document.body.addEventListener('htmx:beforeSwap', () => {
    if (!hasOpenModals()) unlockBodyScroll();
});
document.body.addEventListener('htmx:afterSwap', () => {
    if (!hasOpenModals()) unlockBodyScroll();
});
document.body.addEventListener('htmx:historyRestore', () => {
    unlockBodyScroll();
});

document.addEventListener('click', (event) => {
    const hideElements = event.target.closest('[data-a11y-dialog-hide]');
    if (!hideElements) return;

    event.preventDefault();

    if (event.target !== hideElements) return;

    const dialogElement = hideElements.closest('[data-a11y-dialog]');

    if (dialogElement && dialogElement.dialogInstance) {
        dialogElement.dialogInstance.hide();
    }
});

function closeDropdownsBeforeModal() {
    if (typeof window.closeAllDropdowns === 'function') {
        window.closeAllDropdowns();
    } else if (window.flute && window.flute.dropdowns) {
        window.flute.dropdowns.closeAllDropdowns();
    } else {
        $('[data-dropdown].active').each(function () {
            $(this).removeClass('active').hide();
            $('body').removeClass('no-scroll');
        });
    }
}

function openModal(modalId) {
    const modalElement = document.getElementById(modalId);

    if (modalElement && modalElement.dialogInstance) {
        closeDropdownsBeforeModal();

        if (isMobileDevice()) {
            modalElement.classList.add('bottom-sheet');
        }

        modalElement.dialogInstance.show();
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

document.addEventListener('click', (event) => {
    const openElements = event.target.closest('[data-modal-open]');
    if (!openElements) return;

    event.preventDefault();

    const modalId = openElements.getAttribute('data-modal-open');
    const modalElement = document.getElementById(modalId);

    if (!modalElement) {
        console.warn(`Modal '${modalId}' wasn't found.`);
        return;
    }

    if (isMobileDevice()) {
        modalElement.classList.add('bottom-sheet');
    }

    if (modalElement.dialogInstance) {
        closeDropdownsBeforeModal();
        modalElement.dialogInstance.show();
    }
});

function onModalShow(modalElement) {
    lockBodyScroll();

    const disableModalAutofocus = modalElement.hasAttribute('data-disable-modal-autofocus');

    if (!disableModalAutofocus) {
        const autofocusElement = modalElement.querySelector('[autofocus]');
        if (autofocusElement) {
            autofocusElement.focus({ preventScroll: true });
        } else {
            setTimeout(() => {
                const firstInput = modalElement.querySelector(
                    'input:not(.icon-picker__search-input), textarea, select'
                );
                if (firstInput) {
                    firstInput.focus({ preventScroll: true });
                }
            }, 100);
        }
    }

    if (isMobileDevice()) {
        if (!modalElement.classList.contains('bottom-sheet')) {
            modalElement.classList.add('bottom-sheet');
        }

        const alreadyHasDrag = !!modalElement._dragHandlers;

        if (!modalElement._heightReady) {
            // User-opened modal: just got is-open → display:flex,
            // container still at translateY(100%) from CSS.
            // Set height synchronously in the same frame so it slides up
            // at the correct height. Keep only transform transition.
            const container = modalElement.querySelector('.modal__container');
            if (container) {
                container.style.transition = 'transform 0.3s cubic-bezier(0.32, 0.72, 0, 1)';
                void container.offsetHeight;
                setModalHeightSync(modalElement);
                // Restore full transition after this frame.
                requestAnimationFrame(() => {
                    if (container) container.style.transition = '';
                });
            }
        }
        delete modalElement._heightReady;

        if (!alreadyHasDrag) addDragEvents(modalElement);
        setupMobileObservers(modalElement);
    } else {
        updateContentOverflow(modalElement);

        const resizeHandler = debounce(() => {
            updateContentOverflow(modalElement);
        }, 100);
        window.addEventListener('resize', resizeHandler);
        modalElement._resizeHandler = resizeHandler;

        const contentNode = modalElement.querySelector('.modal__content');
        if (contentNode) {
            const observer = new MutationObserver(
                debounce(() => {
                    updateContentOverflow(modalElement);
                }, 100)
            );
            observer.observe(contentNode, {
                childList: true,
                subtree: true,
                characterData: true,
                attributes: true,
            });
            modalElement._observer = observer;
        }
    }

    // Init pickers/components after layout is stable.
    setTimeout(() => {
        if (window.initColorPickers) window.initColorPickers(modalElement);
        if (window.initIconPickers) window.initIconPickers(modalElement);
        if (typeof initRadioCards === 'function') initRadioCards(modalElement);
        if (typeof initButtonGroups === 'function') initButtonGroups(modalElement);
    }, 60);

    observeModalRemoval(modalElement);
}

function setupMobileObservers(modalElement) {
    if (modalElement._observer) {
        modalElement._observer.disconnect();
        delete modalElement._observer;
    }
    if (modalElement._resizeHandler) {
        window.removeEventListener('resize', modalElement._resizeHandler);
        delete modalElement._resizeHandler;
    }

    const contentNode = modalElement.querySelector('.modal__content');
    if (contentNode) {
        const observer = new MutationObserver(
            debounce(() => {
                setModalHeightSync(modalElement);
                updateContentOverflow(modalElement);
            }, 150),
        );
        observer.observe(contentNode, {
            childList: true,
            subtree: true,
            characterData: true,
            attributes: true,
        });
        modalElement._observer = observer;
    }

    const resizeHandler = debounce(() => {
        setModalHeightSync(modalElement);
        updateContentOverflow(modalElement);
    }, 100);
    window.addEventListener('resize', resizeHandler);
    modalElement._resizeHandler = resizeHandler;
}

function onModalHide(modalElement) {
    const otherOpen = document.querySelectorAll('.modal.is-open, .right_sidebar.is-open');
    const stillHasOpen = Array.from(otherOpen).some(m => m !== modalElement);
    if (!stillHasOpen) {
        unlockBodyScroll();
    }

    if (isMobileDevice()) {
        // Close animation already happened in the hide handler.
        // Just clean up state.
        modalElement.classList.remove('bottom-sheet', 'fullscreen', 'dragging');
        const container = modalElement.querySelector('.modal__container');
        if (container) container.style.height = '';
    }

    const observer = modalElement._observer;
    if (observer) {
        observer.disconnect();
        delete modalElement._observer;
    }

    const resizeHandler = modalElement._resizeHandler;
    if (resizeHandler) {
        window.removeEventListener('resize', resizeHandler);
        delete modalElement._resizeHandler;
    }

    if (modalElement.hasAttribute('data-remove-on-close')) {
        if (modalElement.dialogInstance) {
            modalElement.dialogInstance = null;
        }

        setTimeout(() => {
            modalElement.remove();
        }, 200);
    }
}

function closeBottomSheet(modalElement) {
    if (modalElement._closingBottomSheet) return;
    modalElement._closingBottomSheet = true;

    const container = modalElement.querySelector('.modal__container');
    if (container) {
        container.style.height = '0vh';

        modalElement.classList.remove('fullscreen');

        const handleTransitionEnd = (event) => {
            if (event.target === container && event.propertyName === 'height') {
                modalElement.classList.remove('bottom-sheet', 'dragging');
                removeDragEvents(modalElement);
                container.style.height = '';
                modalElement._closingBottomSheet = false;

                container.removeEventListener(
                    'transitionend',
                    handleTransitionEnd,
                );
            }
        };

        container.addEventListener('transitionend', handleTransitionEnd);
    } else {
        modalElement._closingBottomSheet = false;
    }
}

/**
 * Synchronously measure content and set the container height.
 */
function setModalHeightSync(modalElement) {
    const content = modalElement.querySelector('.modal__content');
    const header = modalElement.querySelector('.modal__header');
    const footer = modalElement.querySelector('.modal__footer');
    const dragHandle = modalElement.querySelector('.drag-handle');
    const container = modalElement.querySelector('.modal__container');

    if (!content || !header || !container) return;

    const contentHeightPx =
        content.scrollHeight +
        header.offsetHeight +
        (dragHandle ? dragHandle.offsetHeight : 0) +
        (footer ? footer.offsetHeight : 0);
    const windowHeightPx = window.innerHeight;
    let contentHeightVh = (contentHeightPx / windowHeightPx) * 100;

    if (contentHeightVh >= 100) {
        modalElement._startHeightVh = 100;
        modalElement._maxHeightVh = 100;
        modalElement.classList.add('fullscreen');
    } else {
        let startHeightVh = Math.min(contentHeightVh, 100);
        modalElement._startHeightVh = startHeightVh;
        modalElement._maxHeightVh = startHeightVh;
        modalElement.classList.remove('fullscreen');
    }

    container.style.height = modalElement._startHeightVh + 'vh';

    updateContentOverflow(modalElement);
}

function updateContentOverflow(modalElement) {
    const content = modalElement.querySelector('.modal__content');
    const container = modalElement.querySelector('.modal__container');

    if (!content || !container || modalElement.hasAttribute('data-ignore-overflow')) return;

    const header = modalElement.querySelector('.modal__header');
    const footer = modalElement.querySelector('.modal__footer');
    const dragHandle = modalElement.querySelector('.drag-handle');

    const headerHeight = header ? header.offsetHeight : 0;
    const footerHeight = footer ? footer.offsetHeight : 0;
    const dragHandleHeight = dragHandle ? dragHandle.offsetHeight : 0;

    const windowHeight = window.innerHeight;
    const maxContainerHeight = isMobileDevice() ? windowHeight : Math.min(windowHeight * 0.9, windowHeight - 20);

    const maxContentHeight = maxContainerHeight - headerHeight - footerHeight - dragHandleHeight;

    const contentScrollHeight = content.scrollHeight;

    if (contentScrollHeight > maxContentHeight) {
        content.style.overflow = 'auto';

        if (isMobileDevice() && modalElement.classList.contains('bottom-sheet')) {
            modalElement.classList.add('fullscreen');
            container.style.height = '100vh';
        }
    } else {
        content.style.overflow = 'visible';
    }
}

function addDragHandle(modalElement) {
    if (modalElement.querySelector('.drag-handle')) return;

    const container = modalElement.querySelector('.modal__container');
    if (!container) return;

    const dragHandle = document.createElement('div');
    dragHandle.className = 'drag-handle';
    dragHandle.innerHTML = '<span></span>';
    container.prepend(dragHandle);
}

function addDragEvents(modalElement) {
    if (modalElement._dragHandlers) return;

    const dragHandle = modalElement.querySelector('.drag-handle');
    if (!dragHandle) return;

    let isDragging = false;
    let startY = 0;
    let startHeightVh = 0;

    const dragStart = (e) => {
        isDragging = true;
        startY = e.type.startsWith('touch') ? e.touches[0].pageY : e.pageY;
        const container = modalElement.querySelector('.modal__container');
        if (container) {
            startHeightVh = (container.offsetHeight / window.innerHeight) * 100;
        }

        modalElement.classList.add('dragging');
    };

    const dragging = (e) => {
        if (!isDragging) return;

        const currentY = e.type.startsWith('touch')
            ? e.touches[0].pageY
            : e.pageY;
        if (currentY === undefined) return;

        const delta = startY - currentY;
        const windowHeight = window.innerHeight;
        let newHeightVh = startHeightVh + (delta / windowHeight) * 100;

        const minHeightVh = 6;
        let maxHeightVh = (modalElement._maxHeightVh || 100) + 5;

        newHeightVh = Math.max(minHeightVh, Math.min(newHeightVh, maxHeightVh));
        const container = modalElement.querySelector('.modal__container');
        if (container) {
            container.style.height = newHeightVh + 'vh';
        }
    };

    const dragEnd = () => {
        if (!isDragging) return;
        isDragging = false;
        modalElement.classList.remove('dragging');

        const container = modalElement.querySelector('.modal__container');
        if (!container) return;

        const currentHeightVh =
            (container.offsetHeight / window.innerHeight) * 100;
        const sHeightVh = modalElement._startHeightVh || 50;
        const maxHeightVh = modalElement._maxHeightVh || 100;

        if (currentHeightVh < maxHeightVh / 3) {
            if (modalElement.dialogInstance) {
                modalElement.dialogInstance.hide();
            }
        } else if (currentHeightVh >= maxHeightVh - 5) {
            container.style.height = maxHeightVh + 'vh';
            if (maxHeightVh === 100) {
                modalElement.classList.add('fullscreen');
            }
        } else {
            container.style.height = sHeightVh + 'vh';
            modalElement.classList.remove('fullscreen');
        }
    };

    dragHandle.addEventListener('mousedown', dragStart);
    dragHandle.addEventListener('touchstart', dragStart, { passive: true });
    document.addEventListener('mousemove', dragging);
    document.addEventListener('touchmove', dragging, { passive: true });
    document.addEventListener('mouseup', dragEnd);
    document.addEventListener('touchend', dragEnd);

    modalElement._dragHandlers = { dragStart, dragging, dragEnd };
}

function removeDragEvents(modalElement) {
    const handlers = modalElement._dragHandlers;

    if (handlers) {
        const dragHandle = modalElement.querySelector('.drag-handle');
        if (dragHandle) {
            dragHandle.removeEventListener('mousedown', handlers.dragStart);
            dragHandle.removeEventListener('touchstart', handlers.dragStart);
        }
        document.removeEventListener('mousemove', handlers.dragging);
        document.removeEventListener('touchmove', handlers.dragging);
        document.removeEventListener('mouseup', handlers.dragEnd);
        document.removeEventListener('touchend', handlers.dragEnd);
        delete modalElement._dragHandlers;
    }
    // Drag handle is rendered from blade — don't remove it from DOM.
}

function lockBodyScroll() {
    document.body.classList.add('no-scroll');
}

function unlockBodyScroll() {
    document.body.classList.remove('no-scroll');
}

function observeModalRemoval(modalElement) {
    if (!modalElement.parentNode || modalElement._removalObserver) return;

    const removalObserver = new MutationObserver(() => {
        if (!document.body.contains(modalElement)) {
            cleanupModal(modalElement);
            removalObserver.disconnect();
        }
    });

    removalObserver.observe(document.body, {
        childList: true,
        subtree: true,
    });

    modalElement._removalObserver = removalObserver;
}

function cleanupModal(modalElement) {
    unlockBodyScroll();

    if (modalElement._removalObserver) {
        modalElement._removalObserver.disconnect();
        delete modalElement._removalObserver;
    }
    if (modalElement._observer) {
        modalElement._observer.disconnect();
        delete modalElement._observer;
    }
    if (modalElement._resizeHandler) {
        window.removeEventListener('resize', modalElement._resizeHandler);
        delete modalElement._resizeHandler;
    }
    if (modalElement._dragHandlers) {
        removeDragEvents(modalElement);
    }
    if (modalElement.dialogInstance) {
        const instance = modalElement.dialogInstance;
        try {
            document.removeEventListener('click', instance.handleTriggerClicks, true);
            instance.shown = false;
        } catch (_) {}
        modalElement.dialogInstance = null;
    }
}

document.body.addEventListener('htmx:beforeCleanupElement', function (evt) {
    const el = evt.target;
    if (!el) return;

    const modals = [];
    if (el.matches && el.matches('.modal, .right_sidebar')) modals.push(el);
    if (el.querySelectorAll) {
        modals.push(...el.querySelectorAll('.modal, .right_sidebar'));
    }
    modals.forEach(cleanupModal);
});
