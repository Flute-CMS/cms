/**
 * Confirmation dialog management
 */
class ConfirmationManager {
    constructor() {
        this.confirmTypes = {
            accent: {
                buttonClass: 'btn-accent',
                iconClass: 'icon-accent',
            },
            primary: {
                buttonClass: 'btn-primary',
                iconClass: 'icon-primary',
            },
            error: {
                buttonClass: 'btn-error',
                iconClass: 'icon-error',
            },
            warning: {
                buttonClass: 'btn-warning',
                iconClass: 'icon-warning',
            },
            info: {
                buttonClass: 'btn-info',
                iconClass: 'icon-info',
            },
            success: {
                buttonClass: 'btn-success',
                iconClass: 'icon-success',
            },
        };

        this.confirmedActions = new Set();
        this.initConfirmEvents();
    }

    initConfirmEvents() {
        $(document).on('click', '[hx-flute-confirm]', (event) => {
            event.preventDefault();

            const $triggerElement = $(event.currentTarget);
            const confirmMessage = $triggerElement.attr('hx-flute-confirm');
            const confirmType =
                $triggerElement.attr('hx-flute-confirm-type') || 'error';
            const actionKey = $triggerElement.attr('hx-flute-action-key');
            const withoutTrigger = $triggerElement.attr(
                'hx-flute-without-trigger',
            );

            if (actionKey && this.confirmedActions.has(actionKey)) {
                htmx.trigger($triggerElement[0], 'confirmed');
                return;
            }

            this.showConfirmDialog({
                message: confirmMessage,
                type: confirmType,
                actionKey: actionKey,
                withoutTrigger: withoutTrigger,
                onConfirm: () => {
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

        document.addEventListener('confirm', (event) => {
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
                console.error('No component found for confirmation event');
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
                        originalRequestData,
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
            type = 'error',
            withoutTrigger,
            onConfirm,
            onCancel,
        } = options;

        const currentType =
            this.confirmTypes[type] || this.confirmTypes['error'];

        $('#confirmation-dialog-message').text(message);

        let $confirmButton = $('#confirmation-dialog-confirm');
        let $cancelButton = $('#confirmation-dialog-cancel');
        let $title = $('#confirmation-dialog-title');
        
        $('#confirmation-dialog').find('.modal__close').off('click.confirm');

        $confirmButton.removeClass(
            'btn-accent btn-primary btn-error btn-warning btn-info',
        );
        $confirmButton.addClass(currentType.buttonClass);

        if (confirmText) {
            $confirmButton.attr('old-text', $confirmButton.text());
            $confirmButton.text(confirmText);
        }

        if (withoutTrigger) {
            $confirmButton.hide();
        }

        let $iconContainer = $('#confirmation-dialog-icon');
        $iconContainer.children().hide();
        $iconContainer.find('.' + currentType.iconClass).show();

        if (cancelText) {
            $cancelButton.attr('old-text', $cancelButton.text());
            $cancelButton.text(cancelText);
        }

        if (title) {
            $title.attr('old-text', $title.text());
            $title.text(title);
        }

        openModal('confirmation-dialog');

        let confirmHandled = false;
        let cancelHandled = false;
        
        const resetConfirmState = () => {
            if (confirmText) $confirmButton.text($confirmButton.attr('old-text'));
            if (cancelText) $cancelButton.text($cancelButton.attr('old-text'));
            if (title) $title.text($title.attr('old-text'));
            
            if (withoutTrigger) {
                $confirmButton.show();
            }
            
            confirmHandled = false;
            cancelHandled = false;
            
            $confirmButton.off('click');
            $cancelButton.off('click');
            $('#confirmation-dialog').find('.modal__close').off('click.confirm');
        };

        $('#confirmation-dialog').find('.modal__close').on('click.confirm', () => {
            if (typeof onCancel === 'function') {
                onCancel();
            }
            
            setTimeout(resetConfirmState, 300);
        });

        $confirmButton.on('click', () => {
            if (confirmHandled) return;
            confirmHandled = true;
            
            $confirmButton.off('click');
            $cancelButton.off('click');
            
            closeModal('confirmation-dialog');

            if (typeof onConfirm === 'function') {
                onConfirm();
            }

            setTimeout(resetConfirmState, 300);
        });

        $cancelButton.on('click', () => {
            if (cancelHandled) return;
            cancelHandled = true;
            
            $confirmButton.off('click');
            $cancelButton.off('click');
            
            closeModal('confirmation-dialog');

            if (typeof onCancel === 'function') {
                onCancel();
            }

            setTimeout(resetConfirmState, 300);
        });
    }

    handleYoyoConfirmation(
        yoyoComponent,
        action,
        actionKey,
        originalRequestData,
    ) {
        if (!yoyoComponent || !action) return;

        try {
            const requestData = originalRequestData || {};

            if (requestData['confirmed_action']) {
                if (Array.isArray(requestData['confirmed_action'])) {
                    requestData['confirmed_action'].push(actionKey);
                } else {
                    requestData['confirmed_action'] = [
                        requestData['confirmed_action'],
                        actionKey,
                    ];
                }
            } else {
                requestData['confirmed_action'] = actionKey;
            }

            const headers = {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest',
                'X-HX-Request': 'true',
                'HX-Target': 'screen-container',
                'HX-Trigger': 'screen-container-1',
                'X-Csrf-Token': document
                    .querySelector('meta[name="csrf-token"]')
                    .getAttribute('content'),
            };

            const componentName = yoyoComponent.getAttribute('yoyo:name');
            requestData['component'] = `${componentName}/${action}`;

            if (requestData['actionArgs']) {
                requestData['actionArgs'] = JSON.stringify(
                    requestData['actionArgs'],
                );
            }

            const formData = new URLSearchParams(requestData).toString();

            const targetSelector = yoyoComponent.getAttribute('id')
                ? `#${yoyoComponent.getAttribute('id')}`
                : null;

            if (targetSelector) {
                const xhr = new XMLHttpRequest();
                xhr.open('POST', Yoyo.url, true);

                Object.keys(headers).forEach((key) => {
                    xhr.setRequestHeader(key, headers[key]);
                });

                xhr.onload = () => {
                    if (xhr.status >= 200 && xhr.status < 300) {
                        htmx.trigger(document.body, 'htmx:afterRequest', {
                            target: yoyoComponent,
                            xhr: xhr,
                        });

                        if (
                            xhr
                                .getAllResponseHeaders()
                                .indexOf('hx-trigger') !== -1
                        ) {
                            const triggerHeader =
                                xhr.getResponseHeader('hx-trigger');
                            if (triggerHeader) {
                                try {
                                    const triggers = JSON.parse(triggerHeader);
                                    Object.keys(triggers).forEach(
                                        (eventName) => {
                                            htmx.trigger(
                                                document.body,
                                                eventName,
                                                triggers[eventName],
                                            );
                                        },
                                    );
                                } catch (e) {
                                    htmx.trigger(document.body, triggerHeader);
                                }
                            }
                        }

                        const emitHeader = xhr.getResponseHeader('yoyo-emit');
                        if (emitHeader) {
                            Yoyo.processEmitEvents(yoyoComponent, emitHeader);
                        }

                        const browserEventsHeader =
                            xhr.getResponseHeader('yoyo-browser-event');
                        if (browserEventsHeader) {
                            Yoyo.processBrowserEvents(browserEventsHeader);
                        }

                        if (xhr.responseText.trim() !== '') {
                            const temp = document.createElement('div');
                            temp.innerHTML = xhr.responseText;

                            const responseEl = temp.querySelector('#screen-container');

                            if (responseEl) {
                                yoyoComponent.outerHTML = responseEl.outerHTML;
                                YoyoEngine.trigger(yoyoComponent, action);
                                htmx.process(
                                    document.querySelector(targetSelector),
                                );
                            }
                        } else {
                            YoyoEngine.trigger(yoyoComponent, action);
                            htmx.trigger(document.body, 'htmx:afterSwap', {
                                target: yoyoComponent,
                            });
                        }
                    }
                };

                xhr.send(formData);
            }
        } catch (error) {
            console.error('Error in YoYo confirmation handling:', error);
        }
    }
}

let confirmationManager;

document.addEventListener('DOMContentLoaded', () => {
    confirmationManager = new ConfirmationManager();
});