/**
 * Layout API - handles server communication for layouts
 */
class LayoutAPI {
    constructor(editor) {
        this.editor = editor;
        this.config = editor.config;
        this.eventBus = window.FlutePageEdit.eventBus;
        this.utils = window.FlutePageEdit.utils;

        this.isFetching = false;
        this.isSaving = false;
    }

    /**
     * Fetch layout from server
     * @param {number} retries - Number of retries
     * @returns {Promise<Array|null>}
     */
    async fetchLayout(retries = 2) {
        if (this.isFetching) return null;
        this.isFetching = true;

        let retryCount = 0;

        const tryFetch = async () => {
            try {
                const currentPath = this.utils.getCurrentPath();
                const res = await this.utils.csrfFetch(
                    u(`api/pages/get-layout?path=${encodeURIComponent(currentPath)}&_=${Date.now()}`),
                    {
                        method: 'GET',
                        headers: { 'Content-Type': 'application/json' },
                        signal: AbortSignal.timeout(10000)
                    }
                );

                if (!res.ok) {
                    throw new Error(`Server responded with ${res.status}`);
                }

                const json = await res.json();

                if (!json || !json.layout) {
                    throw new Error('Invalid layout data received');
                }

                this.eventBus.emit(window.FlutePageEdit.events.LAYOUT_LOADED, {
                    layout: json.layout,
                    path: currentPath
                });

                return json.layout;

            } catch (err) {
                this.utils.logError(`fetchLayout (attempt ${retryCount + 1})`, err);

                if (retryCount < retries) {
                    retryCount++;
                    const backoff = Math.pow(2, retryCount - 1) * 1000;
                    await new Promise(resolve => setTimeout(resolve, backoff));
                    return tryFetch();
                }

                return null;
            }
        };

        try {
            return await tryFetch();
        } finally {
            this.isFetching = false;
        }
    }

    /**
     * Save layout to server
     * @param {Array} layoutData - Layout data
     * @returns {Promise<boolean>}
     */
    async saveLayout(layoutData) {
        if (this.isSaving) return false;
        this.isSaving = true;

        try {
            const currentPath = this.utils.getCurrentPath();
            const res = await this.utils.csrfFetch(u('api/pages/save-layout'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    layout: layoutData,
                    path: currentPath
                }),
                signal: AbortSignal.timeout(15000)
            });

            const json = await res.json();

            if (!res.ok) {
                throw new Error(json?.error || `Failed to save layout (${res.status})`);
            }

            this.eventBus.emit(window.FlutePageEdit.events.LAYOUT_SAVED, {
                path: currentPath,
                layout: layoutData
            });

            return true;

        } catch (err) {
            this.utils.logError('saveLayout', err);

            this.eventBus.emit(window.FlutePageEdit.events.LAYOUT_SAVE_ERROR, {
                error: err.message
            });

            return false;
        } finally {
            this.isSaving = false;
        }
    }

    /**
     * Get layout JSON from current grid state
     * Uses new format: { widgetName, settings, layout: { width, order } }
     * @returns {Array}
     */
    getLayoutJson() {
        const gridController = this.editor.gridController;
        if (!gridController) return [];

        try {
            const widgets = gridController.getWidgets();
            if (!widgets || widgets.length === 0) return [];

            return widgets.map((el, index) => {
                let parsedSettings = {};

                try {
                    const settingsStr = el.dataset.widgetSettings;
                    parsedSettings = settingsStr ? JSON.parse(settingsStr) : {};
                } catch (err) {
                    this.utils.logError('getLayoutJson parse settings', err);
                }

                return {
                    index,
                    id: el.getAttribute('data-widget-id') || null,
                    widgetName: el.getAttribute('data-widget-name') || '',
                    settings: parsedSettings,
                    layout: {
                        width: parseInt(el.dataset.width) || 6,
                        order: index
                    },
                    // Keep gridstack for backwards compatibility with server
                    gridstack: {
                        w: parseInt(el.dataset.width) || 6,
                        h: 2,
                        x: 0,
                        y: index
                    }
                };
            }).filter(Boolean);

        } catch (err) {
            this.utils.logError('getLayoutJson', err);
            return [];
        }
    }

    /**
     * Load layout JSON into grid
     * Supports both old (gridstack) and new (layout) formats
     * @param {Array} data - Layout data
     */
    async loadLayoutJson(data) {
        const gridController = this.editor.gridController;
        const gridEl = document.getElementById('widget-grid');
        if (!gridController || !gridEl || !Array.isArray(data)) return;

        try {
            // Clear grid
            gridEl.innerHTML = '';

            // Sort by order (supports both formats)
            const sorted = [...data].sort((a, b) => {
                const orderA = a.layout?.order ?? a.gridstack?.y ?? a.index ?? 0;
                const orderB = b.layout?.order ?? b.gridstack?.y ?? b.index ?? 0;
                return orderA - orderB;
            });

            const widgetElements = [];
            const hasContentWidget = data.some(item => item.widgetName === 'Content');

            // Create widget elements
            for (const nd of sorted) {
                try {
                    const div = document.createElement('div');
                    div.classList.add('widget-item');
                    div.draggable = true;

                    // Get width from new or old format
                    const width = nd.layout?.width || nd.gridstack?.w || 6;
                    div.dataset.width = width;

                    // Set widget data
                    div.setAttribute('data-widget-name', nd.widgetName || '');
                    if (nd.id) div.setAttribute('data-widget-id', nd.id);
                    div.dataset.widgetSettings = JSON.stringify(nd.settings || {});

                    // Handle Content widget
                    if (nd.widgetName === 'Content') {
                        div.setAttribute('data-system-widget', 'true');
                    }

                    const content = document.createElement('div');
                    content.classList.add('widget-content');
                    content.innerHTML = this.utils.createSkeleton();
                    div.appendChild(content);

                    // Add resize handle for non-Content widgets
                    if (nd.widgetName !== 'Content' && gridController.addResizeHandle) {
                        gridController.addResizeHandle(div);
                    }

                    // Add appearing animation
                    div.classList.add('widget-appearing');
                    setTimeout(() => {
                        div.classList.remove('widget-appearing');
                    }, 500 + widgetElements.length * 50);

                    // Add to DOM
                    gridEl.appendChild(div);

                    widgetElements.push({
                        el: div,
                        widgetName: nd.widgetName,
                        settings: nd.settings || {}
                    });
                } catch (err) {
                    this.utils.logError('widget creation', err);
                }
            }

            // Batch render widgets
            if (widgetElements.length > 0) {
                await this.editor.widgetLoader.renderWidgetsBatch(widgetElements);
            }

            // Add Content widget if missing
            if (!hasContentWidget && this.utils.getCurrentPath() !== '/') {
                setTimeout(() => {
                    this.editor.addContentWidget();
                }, 600);
            }

        } catch (err) {
            this.utils.logError('loadLayoutJson', err);
        }
    }

    /**
     * Refresh page content after save
     */
    refreshPageContent() {
        try {
            htmx.ajax('GET', window.location.href, '#main', {
                swap: 'innerHTML transition:true',
                headers: {
                    'X-CSRF-Token': this.utils.getCsrfToken()
                }
            });
        } catch (err) {
            this.utils.logError('refreshPageContent', err);
            window.location.reload();
        }
    }

    /**
     * Check if currently fetching
     * @returns {boolean}
     */
    isFetchingLayout() {
        return this.isFetching;
    }

    /**
     * Check if currently saving
     * @returns {boolean}
     */
    isSavingLayout() {
        return this.isSaving;
    }
}

window.FlutePageEdit.register('LayoutAPI', LayoutAPI);
