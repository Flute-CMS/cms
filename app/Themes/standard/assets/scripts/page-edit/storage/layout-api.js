/**
 * Layout API — server communication for widget layouts.
 * Works with GridStack for layout serialization.
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

    getScope() {
        return this.editor.scope || 'local';
    }

    /**
     * Fetch layout from server
     */
    async fetchLayout(retries = 2) {
        if (this.isFetching) return null;
        this.isFetching = true;

        let retryCount = 0;
        const scope = this.getScope();

        const tryFetch = async () => {
            try {
                const currentPath = this.utils.getCurrentPath();
                const scopeParam = `scope=${encodeURIComponent(scope)}`;
                const pathParam = scope === 'local' ? `&path=${encodeURIComponent(currentPath)}` : '';

                const res = await this.utils.csrfFetch(
                    u(`api/pages/get-layout?${scopeParam}${pathParam}&_=${Date.now()}`),
                    {
                        method: 'GET',
                        headers: { 'Content-Type': 'application/json' },
                        signal: AbortSignal.timeout(10000)
                    }
                );

                if (!res.ok) throw new Error(`Server responded with ${res.status}`);

                const json = await res.json();
                if (!json?.layout) throw new Error('Invalid layout data');

                this.eventBus.emit(window.FlutePageEdit.events.LAYOUT_LOADED, {
                    layout: json.layout, path: currentPath, scope
                });

                return json.layout;
            } catch (err) {
                this.utils.logError(`fetchLayout (attempt ${retryCount + 1})`, err);
                if (retryCount < retries) {
                    retryCount++;
                    await new Promise(r => setTimeout(r, Math.pow(2, retryCount - 1) * 1000));
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
     */
    async saveLayout(layoutData) {
        if (this.isSaving) return false;
        this.isSaving = true;

        const scope = this.getScope();

        try {
            const currentPath = this.utils.getCurrentPath();
            const bodyData = { layout: layoutData, scope };
            if (scope === 'local') bodyData.path = currentPath;

            const res = await this.utils.csrfFetch(u('api/pages/save-layout'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(bodyData),
                signal: AbortSignal.timeout(15000)
            });

            const json = await res.json();
            if (!res.ok) throw new Error(json?.error || `Failed (${res.status})`);

            this.eventBus.emit(window.FlutePageEdit.events.LAYOUT_SAVED, {
                path: currentPath, layout: layoutData, scope
            });
            return true;
        } catch (err) {
            this.utils.logError('saveLayout', err);
            this.eventBus.emit(window.FlutePageEdit.events.LAYOUT_SAVE_ERROR, { error: err.message });
            return false;
        } finally {
            this.isSaving = false;
        }
    }

    /**
     * Get layout JSON from current GridStack state.
     */
    getLayoutJson() {
        const gc = this.editor.gridController;
        if (!gc?.gsGrid) return [];

        try {
            const items = gc.getWidgets();
            if (!items?.length) return [];

            return items.map((el, index) => {
                let settings = {};
                try {
                    settings = JSON.parse(el.dataset.widgetSettings || '{}');
                } catch (_) {}

                let excludedPaths = [];
                try {
                    excludedPaths = JSON.parse(el.dataset.excludedPaths || '[]');
                } catch (_) {}

                let conditions = { auth: 'all', device: 'all' };
                try {
                    const raw = el.dataset.conditions;
                    if (raw) conditions = JSON.parse(raw);
                } catch (_) {}

                const node = el.gridstackNode || {};

                return {
                    index,
                    id: el.getAttribute('data-widget-id') || node.id || null,
                    widgetName: el.getAttribute('data-widget-name') || '',
                    settings,
                    excludedPaths,
                    conditions,
                    layout: { width: node.w || 6, order: index },
                    gridstack: {
                        w: node.w || 6,
                        h: node.h || 1,
                        x: node.x ?? 0,
                        y: node.y ?? 0,
                        sizeToContent: true
                    }
                };
            }).filter(Boolean);
        } catch (err) {
            this.utils.logError('getLayoutJson', err);
            return [];
        }
    }

    /**
     * Load layout JSON into GridStack grid.
     */
    async loadLayoutJson(data) {
        const gc = this.editor.gridController;
        if (!gc?.gsGrid || !Array.isArray(data)) return;

        try {
            // Batch update for performance
            gc.gsGrid.batchUpdate();
            gc.gsGrid.removeAll();

            const sorted = [...data].sort((a, b) => {
                const oA = a.gridstack?.y ?? a.layout?.order ?? a.index ?? 0;
                const oB = b.gridstack?.y ?? b.layout?.order ?? b.index ?? 0;
                return oA - oB;
            });

            const widgetElements = [];
            const hasContent = data.some(d => d.widgetName === 'Content');

            for (const nd of sorted) {
                try {
                    const cols = nd.gridstack?.w || nd.layout?.width || 6;
                    const h = nd.gridstack?.h || 1;
                    const x = nd.gridstack?.x ?? 0;
                    const y = nd.gridstack?.y;
                    const isSystem = nd.widgetName === 'Content';

                    const el = gc.gsGrid.addWidget({
                        w: cols,
                        h: h,
                        x: x,
                        y: y !== undefined ? y : undefined,
                        sizeToContent: true,
                        content: `<div class="widget-content">${this.utils.createSkeleton()}</div>`,
                        id: nd.id || `widget-${Date.now()}-${Math.random().toString(36).substr(2, 6)}`,
                    });

                    if (!el) continue;

                    el.setAttribute('data-widget-name', nd.widgetName || '');
                    if (nd.id) el.setAttribute('data-widget-id', nd.id);
                    el.dataset.widgetSettings = JSON.stringify(nd.settings || {});
                    el.dataset.excludedPaths = JSON.stringify(nd.excludedPaths || []);
                    el.dataset.conditions = JSON.stringify(nd.conditions || { auth: 'all', device: 'all' });
                    el.classList.add('widget-item');

                    if (isSystem) {
                        el.setAttribute('data-system-widget', 'true');
                    }

                    widgetElements.push({
                        el,
                        widgetName: nd.widgetName,
                        settings: nd.settings || {}
                    });
                } catch (err) {
                    this.utils.logError('widget creation', err);
                }
            }

            gc.gsGrid.batchUpdate(false);

            if (widgetElements.length) {
                await this.editor.widgetLoader.renderWidgetsBatch(widgetElements);

                // After all widgets are rendered, resize them to fit content
                setTimeout(() => {
                    gc.resizeAllToContent();
                }, 300);
            }

            // Add Content widget if missing
            const scope = this.getScope();
            if (!hasContent) {
                if (scope === 'global' || this.utils.getCurrentPath() !== '/') {
                    setTimeout(() => this.editor.addContentWidget(), 600);
                }
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
                headers: { 'X-CSRF-Token': this.utils.getCsrfToken() }
            });
        } catch (err) {
            this.utils.logError('refreshPageContent', err);
            window.location.reload();
        }
    }

    isFetchingLayout() { return this.isFetching; }
    isSavingLayout()   { return this.isSaving; }
}

window.FlutePageEdit.register('LayoutAPI', LayoutAPI);
