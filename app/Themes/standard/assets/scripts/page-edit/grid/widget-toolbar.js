/**
 * Widget Toolbar - manages widget action buttons
 * Width is now controlled via resize handles, not toolbar buttons
 */
class WidgetToolbar {
    constructor(editor) {
        this.editor = editor;
        this.config = editor.config;
        this.eventBus = window.FlutePageEdit.eventBus;
        this.utils = window.FlutePageEdit.utils;

        // Base buttons available for all widgets
        this.baseButtons = {
            settings: {
                icon: this.config.icons.settings,
                tooltipKey: 'def.widget_settings',
                order: 20,
                onClick: (widgetEl) => {
                    this.editor.openWidgetSettings(widgetEl);
                },
                shouldShow: (widgetEl) => {
                    return widgetEl.hasAttribute('data-has-settings') &&
                           widgetEl.getAttribute('data-has-settings') === 'true';
                }
            },
            refresh: {
                icon: this.config.icons.refresh,
                tooltipKey: 'def.refresh_widget',
                order: 10,
                onClick: (widgetEl) => {
                    this.editor.widgetLoader.refreshWidget(widgetEl);
                },
                shouldShow: (widgetEl) => {
                    const widgetName = widgetEl.getAttribute('data-widget-name');
                    return widgetName !== 'Content';
                }
            },
            delete: {
                icon: this.config.icons.delete,
                tooltipKey: 'def.delete_widget',
                order: 100,
                onClick: (widgetEl) => {
                    const widgetName = widgetEl.getAttribute('data-widget-name');
                    if (widgetName === 'Content') return;
                    this.editor.gridController.removeWidget(widgetEl);
                },
                shouldShow: (widgetEl) => {
                    const widgetName = widgetEl.getAttribute('data-widget-name');
                    return widgetName !== 'Content';
                }
            }
        };
    }

    /**
     * Add toolbar to a widget
     * @param {Element} widgetEl - Widget element
     * @param {Array} customButtons - Custom buttons from API
     */
    addToolbar(widgetEl, customButtons = []) {
        if (!widgetEl || widgetEl.querySelector('.widget-toolbar')) return;

        try {
            const toolbar = document.createElement('div');
            toolbar.classList.add('widget-toolbar');

            Object.assign(toolbar.style, {
                opacity: '0',
                bottom: '-15px',
                transition: `opacity ${this.config.animationDuration / 2}ms ease-in-out,
                             transform ${this.config.animationDuration / 2}ms ease-out`,
                position: 'absolute',
                zIndex: '999',
                pointerEvents: 'none'
            });

            // Combine base and custom buttons
            const allButtons = [
                ...Object.entries(this.baseButtons).map(([key, btn]) => ({
                    ...btn,
                    key,
                    type: 'base'
                })),
                ...customButtons.map(btn => ({
                    ...btn,
                    order: btn.order || 50,
                    type: 'custom'
                }))
            ];

            // Filter buttons based on shouldShow
            const filteredButtons = allButtons.filter(button => {
                if (button.type === 'base' && button.shouldShow) {
                    return button.shouldShow(widgetEl);
                }
                return true;
            });

            // Sort by order
            filteredButtons.sort((a, b) => a.order - b.order);

            // Create buttons container
            const buttonsContainer = document.createElement('div');
            buttonsContainer.style.display = 'flex';
            buttonsContainer.style.gap = '6px';

            // Generate buttons
            filteredButtons.forEach(button => {
                const btn = document.createElement('button');
                btn.type = 'button';

                if (button.type === 'base') {
                    btn.className = `widget-button widget-button-${button.key}`;
                    if (button.tooltipKey) {
                        btn.setAttribute('data-translate', button.tooltipKey);
                        btn.setAttribute('data-translate-attribute', 'data-tooltip');
                    } else if (button.tooltip) {
                        btn.setAttribute('data-tooltip', button.tooltip);
                    }
                    btn.innerHTML = button.icon;
                } else {
                    btn.className = 'widget-button widget-button-custom';
                    btn.setAttribute('data-action', button.action);
                    if (button.tooltipKey) {
                        btn.setAttribute('data-translate', button.tooltipKey);
                        btn.setAttribute('data-translate-attribute', 'data-tooltip');
                    } else if (button.tooltip) {
                        btn.setAttribute('data-tooltip', button.tooltip);
                    }
                    btn.innerHTML = button.icon;
                }

                buttonsContainer.appendChild(btn);
            });

            toolbar.appendChild(buttonsContainer);
            widgetEl.appendChild(toolbar);

            this.setupToolbarEvents(widgetEl, toolbar, filteredButtons);

            // Enable pointer events after brief delay
            setTimeout(() => {
                toolbar.style.pointerEvents = 'auto';
            }, 100);

        } catch (err) {
            this.utils.logError('addToolbar', err);
        }
    }

    /**
     * Setup toolbar event handlers
     * @param {Element} widgetEl - Widget element
     * @param {Element} toolbar - Toolbar element
     * @param {Array} buttons - Button configurations
     */
    setupToolbarEvents(widgetEl, toolbar, buttons) {
        if (!widgetEl || !toolbar) return;

        try {
            let hoverTimeout;

            // Show/hide toolbar on widget hover
            const showToolbar = () => {
                clearTimeout(hoverTimeout);
                hoverTimeout = setTimeout(() => {
                    toolbar.style.opacity = '1';
                }, 50);
            };

            const hideToolbar = () => {
                clearTimeout(hoverTimeout);
                toolbar.style.opacity = '0';
            };

            widgetEl.addEventListener('mouseenter', showToolbar);
            widgetEl.addEventListener('mouseleave', hideToolbar);

            // Keep toolbar visible when hovering over it
            toolbar.addEventListener('mouseenter', showToolbar);
            toolbar.addEventListener('mouseleave', hideToolbar);

            // Setup button click handlers
            buttons.forEach(button => {
                let btn;
                if (button.type === 'base') {
                    btn = toolbar.querySelector(`.widget-button-${button.key}`);
                } else {
                    btn = toolbar.querySelector(`[data-action="${button.action}"]`);
                }

                if (btn) {
                    btn.addEventListener('click', async (e) => {
                        e.stopPropagation();
                        e.preventDefault();

                        // Visual feedback
                        btn.style.transform = 'scale(0.95)';
                        setTimeout(() => {
                            btn.style.transform = 'scale(1)';
                        }, 100);

                        try {
                            if (button.type === 'base') {
                                button.onClick(widgetEl, this.editor);
                            } else {
                                await this.handleCustomButtonClick(widgetEl, button);
                            }
                        } catch (err) {
                            this.utils.logError(
                                `button click: ${button.type === 'base' ? button.key : button.action}`,
                                err
                            );
                        }
                    });
                }
            });

        } catch (err) {
            this.utils.logError('setupToolbarEvents', err);
        }
    }

    /**
     * Handle custom button click (widget-specific actions)
     * @param {Element} widgetEl - Widget element
     * @param {object} button - Button configuration
     */
    async handleCustomButtonClick(widgetEl, button) {
        if (!widgetEl || !button || !button.action) return;

        const widgetName = widgetEl.getAttribute('data-widget-name');
        if (!widgetName) return;

        try {
            const res = await this.utils.csrfFetch(u('api/pages/widgets/handle-action'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    widget_name: widgetName,
                    action: button.action,
                    widgetId: widgetEl.getAttribute('data-widget-id')
                })
            });

            if (!res.ok) {
                throw new Error(`Action failed with status: ${res.status}`);
            }

            const result = await res.json();

            if (!document.body.contains(widgetEl)) return;

            // Reload widget if requested
            if (result.reload) {
                this.editor.widgetLoader.refreshWidget(widgetEl);
            }

        } catch (err) {
            this.utils.logError(`handleCustomButtonClick: ${button.action}`, err);
            this.showActionError(widgetEl);
        }
    }

    /**
     * Show action error indicator on widget
     * @param {Element} widgetEl - Widget element
     */
    showActionError(widgetEl) {
        const content = widgetEl.querySelector('.widget-content') || widgetEl.querySelector('.grid-stack-item-content');
        if (!content) return;

        const errorEl = document.createElement('div');
        errorEl.className = 'widget-action-error';
        errorEl.textContent = 'Action failed';
        Object.assign(errorEl.style, {
            position: 'absolute',
            top: '5px',
            right: '5px',
            background: 'rgba(220, 53, 69, 0.8)',
            color: 'white',
            padding: '3px 8px',
            borderRadius: '3px',
            fontSize: '12px',
            opacity: '0',
            transition: 'opacity 0.3s ease-in-out'
        });

        content.appendChild(errorEl);

        // Animate in and out
        setTimeout(() => {
            errorEl.style.opacity = '1';
            setTimeout(() => {
                errorEl.style.opacity = '0';
                setTimeout(() => errorEl.remove(), 300);
            }, 2000);
        }, 10);
    }

    /**
     * Remove toolbar from a widget
     * @param {Element} widgetEl - Widget element
     */
    removeToolbar(widgetEl) {
        const toolbar = widgetEl?.querySelector('.widget-toolbar');
        if (toolbar) {
            toolbar.remove();
        }
    }

    /**
     * Update a button's configuration
     * @param {string} key - Button key
     * @param {object} config - New configuration
     */
    updateBaseButton(key, config) {
        if (this.baseButtons[key]) {
            Object.assign(this.baseButtons[key], config);
        }
    }

    /**
     * Add a new base button
     * @param {string} key - Button key
     * @param {object} config - Button configuration
     */
    addBaseButton(key, config) {
        this.baseButtons[key] = config;
    }

    /**
     * Remove a base button
     * @param {string} key - Button key
     */
    removeBaseButton(key) {
        delete this.baseButtons[key];
    }
}

window.FlutePageEdit.register('WidgetToolbar', WidgetToolbar);
