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

        /** @type {ResizeObserver|null} */
        this._resizeObserver = null;
        /** @type {WeakMap<Element, number>} debounce timers per widget */
        this._resizeDebounceTimers = new WeakMap();
        /** @type {WeakSet<Element>} widgets currently being resized (loop guard) */
        this._resizingWidgets = new WeakSet();
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

                // Resize widget to fit loaded content
                this._resizeWidgetToContent(widgetEl);

                setTimeout(() => {
                    content.style.transition = '';
                }, this.config.animationDuration / 2);
            }, 50);

            // Add toolbar
            this.editor.widgetToolbar.addToolbar(widgetEl, buttonsResponse);

            // Start monitoring content height changes
            this.observeWidgetContent(widgetEl);

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
     * Render multiple widgets using parallel chunked requests.
     * Splits widgets into small chunks, fires them all at once,
     * and renders each chunk as soon as its response arrives.
     * @param {Array} widgetElements - Array of {el, widgetName, settings}
     */
    async renderWidgetsBatch(widgetElements) {
        if (widgetElements.length === 0) return;

        const CHUNK_SIZE = 4;
        const chunks = [];
        for (let i = 0; i < widgetElements.length; i += CHUNK_SIZE) {
            chunks.push(widgetElements.slice(i, i + CHUNK_SIZE));
        }

        // Start buttons loading in parallel (non-blocking)
        const widgetNames = widgetElements.map(w => w.widgetName);
        const buttonsPromise = this.loadButtonsBatch(widgetNames);

        // Fire all chunk requests in parallel — each renders its widgets on arrival
        const chunkPromises = chunks.map(chunk => this._renderChunk(chunk));

        // Wait for all chunks + buttons to finish
        await Promise.all([...chunkPromises, buttonsPromise]);

        // Re-apply toolbar buttons now that button cache is fully populated
        for (const { el, widgetName } of widgetElements) {
            if (!el || !document.contains(el)) continue;
            const buttons = this.widgetButtonsCache[widgetName] || [];
            this.editor.widgetToolbar.removeToolbar(el);
            this.editor.widgetToolbar.addToolbar(el, buttons);
        }
    }

    /**
     * Render a single chunk of widgets — one HTTP request, applies results immediately.
     * @param {Array} chunk - Array of {el, widgetName, settings}
     */
    async _renderChunk(chunk) {
        const requestData = chunk.map(w => ({
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
                throw new Error(`Chunk render failed: ${res.status}`);
            }

            const results = await res.json();

            results.forEach((result, idx) => {
                const { el, widgetName } = chunk[idx];
                if (!el || !document.contains(el)) return;

                const content = el.querySelector('.widget-content');
                if (content) {
                    content.style.opacity = '0';
                    content.innerHTML = result.html || '<div class="widget-error">Error loading widget</div>';
                    el.dataset.widgetSettings = JSON.stringify(result.settings || {});
                    el.setAttribute('data-has-settings', result.hasSettings ? 'true' : 'false');

                    setTimeout(() => {
                        content.style.transition = `opacity ${this.config.animationDuration / 2}ms ease-in-out`;
                        content.style.opacity = '1';
                        content.style.pointerEvents = 'auto';

                        this._resizeWidgetToContent(el);

                        setTimeout(() => {
                            content.style.transition = '';
                        }, this.config.animationDuration / 2);
                    }, 50);
                }

                this.observeWidgetContent(el);

                // Add toolbar immediately per-chunk (don't wait for all chunks)
                if (!el.querySelector(':scope > .widget-toolbar')) {
                    const buttons = this.widgetButtonsCache[widgetName] || [];
                    this.editor.widgetToolbar.addToolbar(el, buttons);
                }

                // Refresh condition badge immediately
                if (this.editor.visibilityConditions) {
                    this.editor.visibilityConditions._updateConditionsBadge(el);
                }

                this.eventBus.emit(window.FlutePageEdit.events.WIDGET_INITIALIZED, {
                    widgetName,
                    widgetElement: el,
                    content: result
                });
            });

        } catch (err) {
            this.utils.logError('_renderChunk', err);

            chunk.forEach(({ el }) => {
                if (el && document.contains(el)) {
                    const content = el.querySelector('.widget-content');
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
        const content = widgetEl.querySelector('.widget-content');
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

            // Resize widget to fit refreshed content
            this._resizeWidgetToContent(widgetEl);

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
     * Resize widget to fit its content using GridStack's sizeToContent.
     * Uses a small delay to allow DOM to render.
     * @param {Element} el - The grid-stack-item element
     */
    _resizeWidgetToContent(el) {
        if (!el) return;
        const gc = this.editor.gridController;
        if (!gc?.gsGrid) return;

        this._resizingWidgets.add(el);

        // First pass — immediate after DOM paint
        requestAnimationFrame(() => {
            try { gc.gsGrid.resizeToContent(el); } catch (_) {}
        });

        // Second pass — catch images/lazy content that loaded after first paint
        setTimeout(() => {
            try { gc.gsGrid.resizeToContent(el); } catch (_) {}
            this._resizingWidgets.delete(el);
        }, 500);
    }

    /* ──────────────────────── ResizeObserver ──────────────────── */

    /**
     * Lazily create a single ResizeObserver that monitors all widget-content
     * elements and triggers GridStack.resizeToContent when their height changes.
     */
    _ensureResizeObserver() {
        if (this._resizeObserver) return;

        const debounceMs = this.config.heightCalculation?.debounceMs || 50;

        this._resizeObserver = new ResizeObserver((entries) => {
            for (const entry of entries) {
                const contentEl = entry.target;
                const widgetEl = contentEl.closest('.grid-stack-item');
                if (!widgetEl) continue;

                // Skip if we are in the middle of a manual resize
                if (this._resizingWidgets.has(widgetEl)) continue;

                // Debounce per widget
                const existing = this._resizeDebounceTimers.get(widgetEl);
                if (existing) clearTimeout(existing);

                this._resizeDebounceTimers.set(widgetEl, setTimeout(() => {
                    this._resizeDebounceTimers.delete(widgetEl);
                    const gc = this.editor.gridController;
                    if (!gc?.gsGrid || !document.body.contains(widgetEl)) return;
                    try { gc.gsGrid.resizeToContent(widgetEl); } catch (_) {}
                }, debounceMs));
            }
        });
    }

    /**
     * Start observing a widget's .widget-content for size changes.
     * @param {Element} widgetEl - The grid-stack-item element
     */
    observeWidgetContent(widgetEl) {
        if (!widgetEl) return;
        this._ensureResizeObserver();
        const content = widgetEl.querySelector('.widget-content');
        if (content) this._resizeObserver.observe(content);
    }

    /**
     * Stop observing a widget's content.
     * @param {Element} widgetEl - The grid-stack-item element
     */
    unobserveWidgetContent(widgetEl) {
        if (!this._resizeObserver || !widgetEl) return;
        const content = widgetEl.querySelector('.widget-content');
        if (content) this._resizeObserver.unobserve(content);
    }

    /**
     * Check if any operations are pending
     * @returns {boolean}
     */
    hasPendingOperations() {
        return this.pendingOperations > 0;
    }

    /**
     * Clear buttons cache and disconnect observer
     */
    clearCache() {
        this.widgetButtonsCache = {};
        if (this._resizeObserver) {
            this._resizeObserver.disconnect();
            this._resizeObserver = null;
        }
    }
}

window.FlutePageEdit.register('WidgetLoader', WidgetLoader);
