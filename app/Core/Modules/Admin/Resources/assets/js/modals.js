function initializeA11yDialog(parentElement = document) {
    const modals = parentElement.querySelectorAll('.modal, .right_sidebar');

    modals.forEach((modalElement) => {
        if (modalElement.dialogInstance) {
            return;
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

        if (modalElement.classList.contains('is-open')) {
            if (isMobileDevice()) {
                modalElement.classList.add('bottom-sheet');
                addDragHandle(modalElement);
            }

            dialog.show();
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initializeA11yDialog();
});

document.body.addEventListener('htmx:afterSwap', (evt) => {
    initializeA11yDialog(evt.target);
});

document.body.addEventListener('htmx:beforeSwap', () => {
    unlockBodyScroll();
});
document.body.addEventListener('htmx:afterSwap', () => {
    unlockBodyScroll();
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

function openModal(modalId) {
    const modalElement = document.getElementById(modalId);

    if (modalElement && modalElement.dialogInstance) {
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
        addDragHandle(modalElement);

        if (modalElement.dialogInstance) {
            modalElement.dialogInstance.show();
        }
    } else {
        if (modalElement.dialogInstance) {
            modalElement.dialogInstance.show();
        }
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
        const container = modalElement.querySelector('.modal__container');
        if (container) {
            container.style.height = '0vh';
            void container.offsetHeight;
        }

        calculateModalHeight(modalElement);
        addDragEvents(modalElement);

        const contentNode = modalElement.querySelector('.modal__content');
        if (contentNode) {
            const observer = new MutationObserver(
                debounce(() => {
                    calculateModalHeight(modalElement);
                    updateContentOverflow(modalElement);
                }, 100),
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
            calculateModalHeight(modalElement);
            updateContentOverflow(modalElement);
        }, 100);
        window.addEventListener('resize', resizeHandler);
        modalElement._resizeHandler = resizeHandler;

        if (!modalElement.classList.contains('bottom-sheet')) {
            modalElement.classList.add('bottom-sheet');
        }
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

    observeModalRemoval(modalElement);
}

function onModalHide(modalElement) {
    unlockBodyScroll();

    if (isMobileDevice()) {
        if (modalElement.classList.contains('bottom-sheet')) {
            closeBottomSheet(modalElement);
        } else {
            modalElement.classList.remove('fullscreen');
            removeDragEvents(modalElement);
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
    const container = modalElement.querySelector('.modal__container');
    if (container) {
        container.style.height = '0vh';

        modalElement.classList.remove('fullscreen');

        const handleTransitionEnd = (event) => {
            if (event.target === container && event.propertyName === 'height') {
                modalElement.classList.remove('bottom-sheet', 'dragging');
                removeDragEvents(modalElement);
                container.style.height = '';

                if (modalElement.dialogInstance) {
                    modalElement.dialogInstance.hide();
                }
                container.removeEventListener(
                    'transitionend',
                    handleTransitionEnd,
                );
            }
        };

        container.addEventListener('transitionend', handleTransitionEnd);
    }
}

function calculateModalHeight(modalElement) {
    setTimeout(() => {
        const content = modalElement.querySelector('.modal__content');
        const header = modalElement.querySelector('.modal__header');
        const footer = modalElement.querySelector('.modal__footer');
        const dragHandle = modalElement.querySelector('.drag-handle');
        if (!content || !header || !dragHandle) {
            return;
        }

        const contentHeightPx =
            content.offsetHeight +
            header.offsetHeight +
            dragHandle.offsetHeight +
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

        const startHeightVh = modalElement._startHeightVh;
        const container = modalElement.querySelector('.modal__container');
        if (container) {
            container.style.height = `${startHeightVh}vh`;
        }
        
        updateContentOverflow(modalElement);
    }, 50);
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
        
        if (isMobileDevice() && modalElement.classList.contains('bottom-sheet') && !modalElement.classList.contains('dragging')) {
            const newContainerHeight = Math.min(
                (contentScrollHeight + headerHeight + footerHeight + dragHandleHeight + 20) / windowHeight * 100,
                100
            );

            const currentHeight = (container.offsetHeight / windowHeight) * 100;
            if (Math.abs(currentHeight - newContainerHeight) > 5) {
                container.style.height = `${newContainerHeight}vh`;
            }
        }
    }
}

function addDragHandle(modalElement) {
    if (!modalElement.querySelector('.drag-handle')) {
        const dragHandleHTML = `
            <div class="drag-handle">
              <span></span>
            </div>
        `;
        const container = modalElement.querySelector('.modal__container');
        if (container) {
            container.insertAdjacentHTML('afterbegin', dragHandleHTML);
        }
    }
}

function addDragEvents(modalElement) {
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
            container.style.height = `${newHeightVh}vh`;
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
        const startHeightVh = modalElement._startHeightVh || 50;
        const maxHeightVh = modalElement._maxHeightVh || 100;

        if (currentHeightVh < maxHeightVh / 3) {
            if (modalElement.dialogInstance) {
                modalElement.dialogInstance.hide();
            }
        } else if (currentHeightVh >= maxHeightVh - 5) {
            container.style.height = `${maxHeightVh}vh`;
            if (maxHeightVh === 100) {
                modalElement.classList.add('fullscreen');
            }
        } else {
            container.style.height = `${startHeightVh}vh`;
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
    const dragHandle = modalElement.querySelector('.drag-handle');
    const handlers = modalElement._dragHandlers;

    if (handlers && dragHandle) {
        dragHandle.removeEventListener('mousedown', handlers.dragStart);
        dragHandle.removeEventListener('touchstart', handlers.dragStart);
        document.removeEventListener('mousemove', handlers.dragging);
        document.removeEventListener('touchmove', handlers.dragging);
        document.removeEventListener('mouseup', handlers.dragEnd);
        document.removeEventListener('touchend', handlers.dragEnd);
        delete modalElement._dragHandlers;
    }

    if (dragHandle) {
        dragHandle.remove();
    }
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
