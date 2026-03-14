/**
 * GridController — GridStack-based widget grid system.
 *
 * Uses gridstack.js v12 for:
 *  - Drag & drop (grid reorder + sidebar → grid)
 *  - Resize (horizontal handles)
 *  - sizeToContent — auto-height based on widget content
 *  - Free placement (float mode — widgets not stuck to top)
 *
 * This replaces the old interact.js + native DnD approach.
 */
class DragDropController {
    constructor(editor) {
        this.editor = editor;
        this.config = editor.config;
        this.eventBus = window.FlutePageEdit.eventBus;
        this.events = window.FlutePageEdit.events;
        this.utils = window.FlutePageEdit.utils;

        this.grid = null;       // DOM element
        this.gsGrid = null;     // GridStack instance
        this.changeTimeout = null;
        this._dragInReady = false;
    }

    /* ──────────────────────── static helpers ──────────────────── */

    static getCols(el) {
        if (el?.gridstackNode) return el.gridstackNode.w;
        return el?._cols ?? 6;
    }

    static setCols(el, n) {
        n = Math.max(1, Math.min(12, Math.round(n)));
        el._cols = n;
        if (el.style) el.style.gridColumn = `span ${n}`;
    }

    /* ──────────────────────── init / destroy ──────────────────── */

    initialize() {
        if (this.gsGrid) return this;

        try {
            this.grid = document.getElementById('widget-grid');
            if (!this.grid) throw new Error('Widget grid not found');

            // Ensure grid-stack class
            this.grid.classList.add('grid-stack');

            // v11+: renderCB is required to render HTML content inside widgets.
            // `el` is the .grid-stack-item-content element, `w` is the GridStackNode.
            GridStack.renderCB = function(el, w) {
                if (w.content) {
                    el.innerHTML = w.content;
                }
            };

            const opts = Object.assign({}, this.config.gridOptions, {
                column: 12,
                float: false,
                sizeToContent: true,
                animate: true,
                cellHeight: 10,
                margin: 0,
                acceptWidgets: true,
                removable: false,
                disableOneColumnMode: true,
                minRow: 1,
                resizable: { handles: 'e,w' },
            });

            this.gsGrid = GridStack.init(opts, this.grid);

            // Events
            this.gsGrid.on('change', () => this._emitChange());
            this.gsGrid.on('dragstop', () => {
                this.eventBus.emit(this.events.WIDGET_DRAG_END);
            });
            this.gsGrid.on('resizestop', (ev, el) => {
                const node = el.gridstackNode;
                if (node) {
                    el._cols = node.w;
                    this.eventBus.emit(this.events.WIDGET_RESIZED, { widget: el, width: node.w });
                }
            });
            this.gsGrid.on('dropped', (ev, prevWidget, newWidget) => {
                this._handleDropped(newWidget);
            });

            // Setup sidebar drag-in
            this._setupSidebarDragIn();

            this.eventBus.emit(this.events.GRID_INITIALIZED, { grid: this });
            return this;
        } catch (err) {
            this.utils.logError('GridController.initialize', err);
            return null;
        }
    }

    destroy() {
        if (!this.gsGrid) return;
        clearTimeout(this.changeTimeout);

        try {
            this.gsGrid.destroy(false);
        } catch (e) {
            this.utils.logError('GridController.destroy', e);
        }

        this.gsGrid = null;
        this.grid = null;
        this.eventBus.emit(this.events.GRID_DESTROYED);
    }

    /* ──────────────────────── sidebar drag-in ──────────────────── */

    _setupSidebarDragIn() {
        if (this._dragInReady || typeof GridStack === 'undefined') return;
        this._refreshDragIn();
        this._dragInReady = true;
    }

    /**
     * (Re-)register all .pe-widget-card elements for GridStack drag-in.
     * Called on init and after search results are populated.
     */
    refreshDragIn() {
        this._refreshDragIn();
    }

    _refreshDragIn() {
        try {
            const selector = '.pe-dock .pe-widget-card, .pe-sidebar .pe-widget-card';
            const cards = document.querySelectorAll(selector);
            if (!cards.length) return;

            const widgets = [];
            cards.forEach(card => {
                const w = parseInt(card.getAttribute('gs-w')) || 6;
                widgets.push({ w, h: 4, sizeToContent: true });
            });

            GridStack.setupDragIn(selector, { appendTo: 'body', helper: 'clone' }, widgets);
        } catch (err) {
            this.utils.logError('setupDragIn', err);
        }
    }

    _handleDropped(newWidget) {
        if (!newWidget?.el) return;

        const el = newWidget.el;

        // Find widget name — check:
        // 1) the element itself (if it's the sidebar card clone)
        // 2) any nested element with data-widget-name
        const widgetName = el.getAttribute('data-widget-name')
            || el.querySelector('[data-widget-name]')?.getAttribute('data-widget-name');

        if (!widgetName) {
            try { this.gsGrid.removeWidget(el); } catch (_) {}
            return;
        }

        // Remove the dropped element from the grid — we'll create a proper widget instead
        const node = el.gridstackNode;
        const w = node?.w || parseInt(el.getAttribute('gs-w')) || 6;
        const x = node?.x ?? undefined;
        const y = node?.y ?? undefined;

        try { this.gsGrid.removeWidget(el, true, false); } catch (_) {
            el.remove();
        }

        // Now create a proper widget using the standard flow
        const content = `<div class="widget-content">${this.utils.createSkeleton()}</div>`;
        const opts = {
            w,
            x,
            y,
            sizeToContent: true,
            content: content,
            id: `widget-${Date.now()}-${Math.random().toString(36).substr(2, 6)}`,
        };

        const newEl = this.gsGrid.addWidget(opts);
        if (!newEl) return;

        newEl.setAttribute('data-widget-name', widgetName);
        newEl.dataset.widgetSettings = '{}';
        newEl.classList.add('widget-item');

        // Initialize widget content
        const widgetContent = newEl.querySelector('.widget-content');
        if (widgetContent) {
            this.editor.widgetLoader.initializeWidget(newEl, widgetContent);
        }

        this._emitChange();
        this.updateEmptyState();
        this.eventBus.emit(this.events.WIDGET_DROPPED, { widget: newEl, widgetName });
    }

    /* ──────────────────────── widget CRUD ──────────────────── */

    createWidget(name, cols, beforeEl) {
        if (!this.gsGrid) return null;

        const content = `<div class="widget-content">${this.utils.createSkeleton()}</div>`;

        const opts = {
            w: Math.max(1, Math.min(12, cols || 6)),
            sizeToContent: true,
            content: content,
            id: `widget-${Date.now()}-${Math.random().toString(36).substr(2, 6)}`,
        };

        const el = this.gsGrid.addWidget(opts);
        if (!el) return null;

        el.setAttribute('data-widget-name', name);
        el.dataset.widgetSettings = '{}';
        el.classList.add('widget-item');

        if (isSystem) {
            el.setAttribute('data-system-widget', 'true');
        }

        // Initialize widget content
        const widgetContent = el.querySelector('.widget-content');
        if (widgetContent) {
            this.editor.widgetLoader.initializeWidget(el, widgetContent);
        }

        this._emitChange();
        this.updateEmptyState();
        this.eventBus.emit(this.events.WIDGET_DROPPED, { widget: el, widgetName: name });
        return el;
    }

    removeWidget(el) {
        if (!el || !this.gsGrid) return;
        if (el.getAttribute('data-widget-name') === 'Content') return;

        const name = el.getAttribute('data-widget-name');

        try {
            this.gsGrid.removeWidget(el);
        } catch (e) {
            el.remove();
        }

        this._emitChange();
        this.updateEmptyState();
        this.eventBus.emit(this.events.WIDGET_REMOVED, { widgetName: name });
    }

    setWidgetWidth(el, cols) {
        if (!el || !this.gsGrid) return;
        cols = Math.max(1, Math.min(12, cols));
        this.gsGrid.update(el, { w: cols });
        el._cols = cols;
        this._emitChange();
        this.eventBus.emit(this.events.WIDGET_RESIZED, { widget: el, width: cols });
    }

    /* ──────────────────────── sizeToContent helpers ──────────── */

    /**
     * Resize a single widget to fit its content.
     * Should be called after widget HTML is loaded.
     */
    resizeToContent(el) {
        if (!el || !this.gsGrid) return;
        try {
            this.gsGrid.resizeToContent(el);
        } catch (err) {
            this.utils.logError('resizeToContent', err);
        }
    }

    /**
     * Resize all widgets to fit their content.
     */
    resizeAllToContent() {
        if (!this.gsGrid) return;
        const items = this.getItems();
        items.forEach(el => {
            try {
                this.gsGrid.resizeToContent(el);
            } catch (_) {}
        });
    }

    /* ──────────────────────── empty state ──────────────────── */

    updateEmptyState() {
        if (!this.grid) return;
        const items = this.gsGrid ? this.gsGrid.getGridItems() : [];
        let empty = this.grid.querySelector('.pe-empty-state');

        if (items.length === 0) {
            if (!empty) {
                empty = document.createElement('div');
                empty.className = 'pe-empty-state';
                empty.innerHTML = `
                    <div class="pe-empty-state__icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 256 256"><path d="M224,48H160a40,40,0,0,0-32,16A40,40,0,0,0,96,48H32A16,16,0,0,0,16,64V192a16,16,0,0,0,16,16H96a24,24,0,0,1,24,24,8,8,0,0,0,16,0,24,24,0,0,1,24-24h64a16,16,0,0,0,16-16V64A16,16,0,0,0,224,48ZM96,192H32V64H96a24,24,0,0,1,24,24V200A39.81,39.81,0,0,0,96,192Zm128,0H160a39.81,39.81,0,0,0-24,8V88a24,24,0,0,1,24-24h64Z"/></svg>
                    </div>
                    <div class="pe-empty-state__title">${typeof translate === 'function' ? translate('page-edit.empty_title') : 'No widgets yet'}</div>
                    <div class="pe-empty-state__desc">${typeof translate === 'function' ? translate('page-edit.empty_desc') : 'Drag widgets from the sidebar or click to add them.'}</div>
                `;
                this.grid.appendChild(empty);
            }
        } else {
            empty?.remove();
        }
    }

    /* ──────────────────────── change debounce ──────────────────── */

    _emitChange() {
        if (this.editor.history?.isProcessing) return;
        clearTimeout(this.changeTimeout);
        this.changeTimeout = setTimeout(() => {
            this.editor.hasUnsavedChanges = true;
            this.editor.updateSaveButtonState();
            this.editor.history?.push();
            this.editor.saveToLocalStorage();
            this.eventBus.emit(this.events.GRID_CHANGED);
        }, 150);
    }

    /* ──────────────────────── backward-compat API ──────────────── */

    // Selection (no-op — hover-based toolbar now)
    selectWidget() {}
    deselectWidget() {}
    getSelectedWidget() { return null; }

    // Inserters (no-op — gridstack handles positioning)
    rebuildInserters() {}
    showQuickInserter() {}
    hideQuickInserter() {}

    // Interact.js compat (no-op — gridstack handles)
    enableInteract(el) {}
    disableInteract(el) {}
    addResizeHandle(el) {}
    set _sidebarDragCols(v) {}

    // Grid operations
    onGridChange() { this._emitChange(); }
    getItems() { return this.gsGrid ? this.gsGrid.getGridItems() : []; }
    getWidgets() { return this.getItems(); }
    getGrid() { return this; }

    compact() {
        if (this.gsGrid) this.gsGrid.compact();
        this._emitChange();
        this.eventBus.emit(this.events.GRID_COMPACTED);
    }

    removeAll() {
        if (this.gsGrid) this.gsGrid.removeAll();
    }

    makeWidget(el) {
        if (!el || !this.gsGrid) return el;
        el.classList.add('widget-item');
        this.gsGrid.makeWidget(el);
        return el;
    }

    updateWidget(el, props) {
        if (!el || !props || !this.gsGrid) return;
        if (props.w !== undefined) this.setWidgetWidth(el, props.w);
    }
}

window.FlutePageEdit.register('GridController', DragDropController);
window.FlutePageEdit.register('DragDropController', DragDropController);
