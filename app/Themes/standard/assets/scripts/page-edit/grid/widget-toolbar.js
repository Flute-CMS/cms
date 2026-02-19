/**
 * Widget Toolbar — manages widget action buttons.
 *
 * Toolbar appears on HOVER (no click-to-select).
 * Minimal design with icon buttons and tooltips.
 */
class WidgetToolbar {
    constructor(editor) {
        this.editor = editor;
        this.config = editor.config;
        this.eventBus = window.FlutePageEdit.eventBus;
        this.utils = window.FlutePageEdit.utils;

        this.baseButtons = {
            refresh: {
                icon: this.config.icons.refresh,
                tooltipKey: 'def.refresh_widget',
                order: 10,
                onClick: (widgetEl) => {
                    this.editor.widgetLoader.refreshWidget(widgetEl);
                },
                shouldShow: (widgetEl) => {
                    return widgetEl.getAttribute('data-widget-name') !== 'Content';
                }
            },
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
            excludedPaths: {
                icon: this.config.icons.excludedPaths,
                tooltipKey: 'page-edit.excluded_paths',
                order: 30,
                onClick: (widgetEl) => {
                    this.editor.openExcludedPathsEditor(widgetEl);
                },
                shouldShow: (widgetEl) => {
                    return widgetEl.getAttribute('data-widget-name') !== 'Content'
                        && this.editor.scope === 'global';
                }
            },
            delete: {
                icon: this.config.icons.delete,
                tooltipKey: 'def.delete_widget',
                order: 100,
                onClick: (widgetEl) => {
                    if (widgetEl.getAttribute('data-widget-name') === 'Content') return;
                    this.editor.gridController.removeWidget(widgetEl);
                },
                shouldShow: (widgetEl) => {
                    return widgetEl.getAttribute('data-widget-name') !== 'Content';
                }
            }
        };
    }

    /**
     * Add toolbar to a widget.
     * Toolbar is visible on hover via CSS.
     */
    addToolbar(widgetEl, customButtons = []) {
        if (!widgetEl) return;

        // Append toolbar directly to the grid-stack-item (NOT .grid-stack-item-content)
        // so it is not clipped by content overflow
        const container = widgetEl;
        if (container.querySelector(':scope > .widget-toolbar')) return;

        try {
            const toolbar = document.createElement('div');
            toolbar.className = 'widget-toolbar';

            // Widget name label
            const nameLabel = document.createElement('span');
            nameLabel.className = 'widget-toolbar__name';
            nameLabel.textContent = widgetEl.getAttribute('data-widget-name') || 'Widget';
            toolbar.appendChild(nameLabel);

            // Separator
            const sep = document.createElement('span');
            sep.className = 'widget-toolbar__sep';
            toolbar.appendChild(sep);

            // Combine base + custom buttons
            const allButtons = [
                ...Object.entries(this.baseButtons).map(([key, btn]) => ({
                    ...btn, key, type: 'base'
                })),
                ...customButtons.map(btn => ({
                    ...btn, order: btn.order || 50, type: 'custom'
                }))
            ];

            // Filter + sort
            const filteredButtons = allButtons
                .filter(b => b.type !== 'base' || !b.shouldShow || b.shouldShow(widgetEl))
                .sort((a, b) => a.order - b.order);

            // Create buttons
            filteredButtons.forEach(button => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = `widget-toolbar__btn widget-toolbar__btn--${button.key || button.action || 'custom'}`;

                // Tooltip
                if (button.tooltipKey) {
                    btn.setAttribute('data-translate', button.tooltipKey);
                    btn.setAttribute('data-translate-attribute', 'data-tooltip');
                } else if (button.tooltip) {
                    btn.setAttribute('data-tooltip', button.tooltip);
                }
                btn.setAttribute('data-tooltip-pos', 'top');

                btn.innerHTML = button.icon;
                toolbar.appendChild(btn);

                // Click handler
                btn.addEventListener('click', async (e) => {
                    e.stopPropagation();
                    e.preventDefault();

                    try {
                        if (button.type === 'base') {
                            button.onClick(widgetEl, this.editor);
                        } else {
                            await this.handleCustomButtonClick(widgetEl, button);
                        }
                    } catch (err) {
                        this.utils.logError(`toolbar button: ${button.key || button.action}`, err);
                    }
                });
            });

            container.appendChild(toolbar);

        } catch (err) {
            this.utils.logError('addToolbar', err);
        }
    }

    /**
     * Handle custom button click (widget-specific actions)
     */
    async handleCustomButtonClick(widgetEl, button) {
        if (!widgetEl || !button?.action) return;

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

            if (!res.ok) throw new Error(`Action failed: ${res.status}`);

            const result = await res.json();
            if (result.reload && document.body.contains(widgetEl)) {
                this.editor.widgetLoader.refreshWidget(widgetEl);
            }
        } catch (err) {
            this.utils.logError(`customAction: ${button.action}`, err);
        }
    }

    removeToolbar(widgetEl) {
        widgetEl?.querySelector(':scope > .widget-toolbar')?.remove();
    }

    updateBaseButton(key, config) {
        if (this.baseButtons[key]) Object.assign(this.baseButtons[key], config);
    }

    addBaseButton(key, config) {
        this.baseButtons[key] = config;
    }

    removeBaseButton(key) {
        delete this.baseButtons[key];
    }

    setupToolbarEvents() {}
}

window.FlutePageEdit.register('WidgetToolbar', WidgetToolbar);
