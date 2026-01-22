/**
 * Widget Loader - handles loading widget content from API
 */
class WidgetLoader {
    constructor(editor) {
        this.editor = editor;
        this.config = editor.config;
        this.eventBus = window.FlutePageEdit.eventBus;
        this.utils = window.FlutePageEdit.utils;

        this.pendingOperations = 0;
        this.widgetButtonsCache = {};
    }

    /**
     * Initialize a widget after it's added to the grid
     * @param {Element} widgetEl - Widget element
     * @param {Element} content - Content element
     */
    async initializeWidget(widgetEl, content) {
        if (!widgetEl || !content) return;

        const widgetName = widgetEl.getAttribute('data-widget-name');
        if (!widgetName) return;

        this.pendingOperations++;
        content.style.pointerEvents = 'none';
        content.innerHTML = this.utils.createSkeleton();

        try {
            // Load content and buttons in parallel
            const [contentResponse, buttonsResponse] = await Promise.all([
                this.loadWidgetContent(widgetEl).catch(err => {
                    this.utils.logError(`loadWidgetContent for ${widgetName}`, err);
                    return {
                        html: `<div class="widget-error">${this.config.translations.errorLoading}</div>`,
                        settings: {}
                    };
                }),
                this.loadWidgetButtons(widgetName).catch(err => {
                    this.utils.logError(`loadWidgetButtons for ${widgetName}`, err);
                    return [];
                })
            ]);

            // Check if widget still exists in DOM
            if (!document.body.contains(widgetEl)) {
                this.pendingOperations--;
                return;
            }

            // Apply content with fade animation
            content.style.opacity = '0';
            content.innerHTML = contentResponse.html || '';

            widgetEl.dataset.widgetSettings = JSON.stringify(contentResponse.settings || {});

            // Fade in
            setTimeout(() => {
                content.style.transition = `opacity ${this.config.animationDuration / 2}ms ease-in-out`;
                content.style.opacity = '1';
                content.style.pointerEvents = 'auto';

                setTimeout(() => {
                    content.style.transition = '';
                }, this.config.animationDuration / 2);
            }, 50);

            // Add toolbar
            this.editor.widgetToolbar.addToolbar(widgetEl, buttonsResponse);

            this.eventBus.emit(window.FlutePageEdit.events.WIDGET_INITIALIZED, {
                widgetName,
                widgetElement: widgetEl,
                content: contentResponse
            });

        } catch (err) {
            this.utils.logError(`initializeWidget ${widgetName}`, err);

            if (document.body.contains(widgetEl) && content) {
                content.innerHTML = `<div class="widget-error">${this.config.translations.errorLoading}</div>`;
                content.style.pointerEvents = 'auto';
                this.editor.widgetToolbar.addToolbar(widgetEl, []);
            }
        } finally {
            this.pendingOperations--;
        }
    }

    /**
     * Load widget content from API
     * @param {Element} widgetEl - Widget element
     * @returns {Promise<object>}
     */
    async loadWidgetContent(widgetEl) {
        const widgetName = widgetEl.getAttribute('data-widget-name');
        const settings = JSON.parse(widgetEl.dataset.widgetSettings || '{}');

        const res = await this.utils.csrfFetch(u('api/pages/render-widget'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                widget_name: widgetName,
                settings: settings
            })
        });

        if (!res.ok) throw new Error('Failed to load widget content');

        const result = await res.json();

        if (result.settings && widgetEl) {
            widgetEl.setAttribute(
                'data-has-settings',
                result.hasSettings !== undefined ? result.hasSettings.toString() : 'false'
            );
        }

        return result;
    }

    /**
     * Load widget buttons from API
     * @param {string} widgetName - Widget name
     * @returns {Promise<Array>}
     */
    async loadWidgetButtons(widgetName) {
        if (this.widgetButtonsCache[widgetName]) {
            return this.widgetButtonsCache[widgetName];
        }

        try {
            const res = await this.utils.csrfFetch(u('api/pages/widgets/buttons'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ widget_name: widgetName })
            });

            if (!res.ok) return [];

            const buttons = await res.json();
            if (Array.isArray(buttons)) {
                this.widgetButtonsCache[widgetName] = buttons;
                return buttons;
            }
        } catch (err) {
            this.utils.logError('loadWidgetButtons', err);
        }

        return [];
    }

    /**
     * Load buttons for multiple widgets at once
     * @param {string[]} widgetNames - Array of widget names
     */
    async loadButtonsBatch(widgetNames) {
        const uniqueNames = [...new Set(widgetNames)];
        const missingNames = uniqueNames.filter(name => !this.widgetButtonsCache[name]);

        if (missingNames.length === 0) return;

        try {
            const res = await this.utils.csrfFetch(u('api/pages/widgets/buttons-batch'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ widget_names: missingNames })
            });

            if (!res.ok) {
                throw new Error(`Failed to load batch buttons: ${res.status}`);
            }

            const batchButtons = await res.json();
            Object.entries(batchButtons).forEach(([name, buttons]) => {
                this.widgetButtonsCache[name] = buttons;
            });
        } catch (err) {
            this.utils.logError('loadButtonsBatch', err);
        }
    }

    /**
     * Render multiple widgets at once
     * @param {Array} widgetElements - Array of {el, widgetName, settings}
     */
    async renderWidgetsBatch(widgetElements) {
        if (widgetElements.length === 0) return;

        const requestData = widgetElements.map(w => ({
            widget_name: w.widgetName,
            settings: w.settings
        }));

        try {
            const res = await this.utils.csrfFetch(u('api/pages/render-widgets'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ widgets: requestData })
            });

            if (!res.ok) {
                throw new Error(`Failed to batch render widgets: ${res.status}`);
            }

            const results = await res.json();

            // Load buttons in batch
            const widgetNames = widgetElements.map(w => w.widgetName);
            await this.loadButtonsBatch(widgetNames);

            // Apply results to widgets
            results.forEach((result, idx) => {
                const { el } = widgetElements[idx];
                if (!el || !document.contains(el)) return;

                const content = el.querySelector('.widget-content') || el.querySelector('.grid-stack-item-content');
                if (content) {
                    content.style.opacity = '0';
                    content.innerHTML = result.html || '<div class="widget-error">Error loading widget</div>';
                    el.dataset.widgetSettings = JSON.stringify(result.settings || {});
                    el.setAttribute('data-has-settings', result.hasSettings ? 'true' : 'false');

                    setTimeout(() => {
                        content.style.transition = `opacity ${this.config.animationDuration / 2}ms ease-in-out`;
                        content.style.opacity = '1';
                        content.style.pointerEvents = 'auto';
                        setTimeout(() => {
                            content.style.transition = '';
                        }, this.config.animationDuration / 2);
                    }, 50);
                }

                // Use cached buttons
                const buttons = this.widgetButtonsCache[widgetElements[idx].widgetName] || [];
                this.editor.widgetToolbar.addToolbar(el, buttons);

                this.eventBus.emit(window.FlutePageEdit.events.WIDGET_INITIALIZED, {
                    widgetName: widgetElements[idx].widgetName,
                    widgetElement: el,
                    content: result
                });
            });

        } catch (err) {
            this.utils.logError('renderWidgetsBatch', err);

            // Show error on all widgets
            widgetElements.forEach(({ el }) => {
                if (el && document.contains(el)) {
                    const content = el.querySelector('.widget-content') || el.querySelector('.grid-stack-item-content');
                    if (content) {
                        content.innerHTML = '<div class="widget-error">Failed to load widgets</div>';
                        content.style.pointerEvents = 'auto';
                    }
                }
            });
        }
    }

    /**
     * Refresh a widget's content
     * @param {Element} widgetEl - Widget element
     */
    async refreshWidget(widgetEl) {
        const widgetName = widgetEl.getAttribute('data-widget-name');
        const content = widgetEl.querySelector('.widget-content') || widgetEl.querySelector('.grid-stack-item-content');
        if (!content) return;

        const currentSettings = widgetEl.dataset.widgetSettings;

        content.style.pointerEvents = 'none';
        content.innerHTML = this.utils.createSkeleton();

        try {
            const response = await this.utils.csrfFetch(u('api/pages/render-widget'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    widget_name: widgetName,
                    settings: JSON.parse(currentSettings || '{}')
                })
            });

            if (!response.ok) throw new Error('Failed to refresh widget');

            const json = await response.json();

            if (json.html) {
                content.innerHTML = json.html;
            }
            if (json.settings) {
                widgetEl.dataset.widgetSettings = JSON.stringify(json.settings);
            }

            content.style.pointerEvents = 'auto';

            this.editor.gridController?.onGridChange();

            this.eventBus.emit(window.FlutePageEdit.events.WIDGET_REFRESHED, {
                widgetName,
                widgetElement: widgetEl
            });

        } catch (err) {
            this.utils.logError('refreshWidget', err);
            content.innerHTML = `<div class="widget-error">${this.config.translations.errorLoading}</div>`;
            content.style.pointerEvents = 'auto';
        }
    }

    /**
     * Check if any operations are pending
     * @returns {boolean}
     */
    hasPendingOperations() {
        return this.pendingOperations > 0;
    }

    /**
     * Clear buttons cache
     */
    clearCache() {
        this.widgetButtonsCache = {};
    }
}

window.FlutePageEdit.register('WidgetLoader', WidgetLoader);
