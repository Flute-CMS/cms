/**
 * Drag Drop Controller - manages native HTML5 drag-and-drop for widgets
 * Replaces GridStack with a simple flexbox-based system
 * Includes resizable width via drag handles
 */
class DragDropController {
    constructor(editor) {
        this.editor = editor;
        this.config = editor.config;
        this.eventBus = window.FlutePageEdit.eventBus;
        this.utils = window.FlutePageEdit.utils;

        this.grid = null;
        this.draggedEl = null;
        this.dropIndicator = null;
        this.changeTimeout = null;
        this._boundHandlers = {};

        // Resize state
        this.resizing = {
            active: false,
            widget: null,
            startX: 0,
            startWidth: 0,
            gridWidth: 0,
            columnWidth: 0
        };
        this.widthIndicator = null;
        this.gridOverlay = null;
    }

    /**
     * Initialize the drag-drop controller
     * @returns {DragDropController}
     */
    initialize() {
        if (this.grid) return this;

        try {
            this.grid = document.getElementById('widget-grid');

            if (!this.grid) {
                throw new Error('Widget grid element not found');
            }

            this.createDropIndicator();
            this.createWidthIndicator();
            this.createGridOverlay();
            this.setupGridEvents();
            this.setupResizeEvents();

            this.eventBus.emit(window.FlutePageEdit.events.GRID_INITIALIZED, {
                grid: this
            });

            return this;
        } catch (err) {
            this.utils.logError('DragDropController.initialize', err);
            return null;
        }
    }

    /**
     * Create drop indicator element
     */
    createDropIndicator() {
        this.dropIndicator = document.createElement('div');
        this.dropIndicator.className = 'drop-indicator';
        this.dropIndicator.style.display = 'none';
    }

    /**
     * Setup grid event listeners
     */
    setupGridEvents() {
        if (!this.grid) return;

        // Store bound handlers for cleanup
        this._boundHandlers = {
            dragstart: (e) => this.onDragStart(e),
            dragover: (e) => this.onDragOver(e),
            dragleave: (e) => this.onDragLeave(e),
            drop: (e) => this.onDrop(e),
            dragend: (e) => this.onDragEnd(e)
        };

        this.grid.addEventListener('dragstart', this._boundHandlers.dragstart);
        this.grid.addEventListener('dragover', this._boundHandlers.dragover);
        this.grid.addEventListener('dragleave', this._boundHandlers.dragleave);
        this.grid.addEventListener('drop', this._boundHandlers.drop);
        this.grid.addEventListener('dragend', this._boundHandlers.dragend);
    }

    /**
     * Handle drag start event
     * @param {DragEvent} e
     */
    onDragStart(e) {
        const widget = e.target.closest('.widget-item');
        if (!widget || !this.grid.contains(widget)) return;

        this.draggedEl = widget;
        widget.classList.add('dragging');

        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', 'reorder');

        // Set drag image
        if (e.dataTransfer.setDragImage) {
            const rect = widget.getBoundingClientRect();
            e.dataTransfer.setDragImage(widget, rect.width / 2, 20);
        }

        this.eventBus.emit(window.FlutePageEdit.events.WIDGET_DRAG_START, {
            widget: widget
        });
    }

    /**
     * Handle drag over event
     * @param {DragEvent} e
     */
    onDragOver(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = this.draggedEl ? 'move' : 'copy';

        const afterEl = this.getInsertPosition(e.clientX, e.clientY);
        this.showDropIndicator(afterEl);
    }

    /**
     * Handle drag leave event
     * @param {DragEvent} e
     */
    onDragLeave(e) {
        // Only hide if leaving the grid entirely
        if (!this.grid.contains(e.relatedTarget)) {
            this.hideDropIndicator();
        }
    }

    /**
     * Handle drop event
     * @param {DragEvent} e
     */
    onDrop(e) {
        e.preventDefault();
        this.hideDropIndicator();

        // Check if it's a new widget from sidebar
        const widgetName = e.dataTransfer.getData('widget-name');
        if (widgetName) {
            const width = e.dataTransfer.getData('widget-width') || '6';
            const afterEl = this.getInsertPosition(e.clientX, e.clientY);
            this.createWidget(widgetName, width, afterEl);
            return;
        }

        // Reorder existing widget
        if (this.draggedEl) {
            const afterEl = this.getInsertPosition(e.clientX, e.clientY);

            if (afterEl && afterEl !== this.draggedEl) {
                this.grid.insertBefore(this.draggedEl, afterEl);
            } else if (!afterEl) {
                this.grid.appendChild(this.draggedEl);
            }

            this.onGridChange();

            this.eventBus.emit(window.FlutePageEdit.events.WIDGET_REORDERED, {
                widget: this.draggedEl
            });
        }
    }

    /**
     * Handle drag end event
     * @param {DragEvent} e
     */
    onDragEnd(e) {
        if (this.draggedEl) {
            this.draggedEl.classList.remove('dragging');
            this.draggedEl = null;
        }
        this.hideDropIndicator();

        this.eventBus.emit(window.FlutePageEdit.events.WIDGET_DRAG_END);
    }

    /**
     * Get the element to insert before based on mouse position
     * @param {number} x - Mouse X position
     * @param {number} y - Mouse Y position
     * @returns {Element|null}
     */
    getInsertPosition(x, y) {
        const widgets = [...this.grid.querySelectorAll('.widget-item:not(.dragging)')];

        for (const widget of widgets) {
            const rect = widget.getBoundingClientRect();
            const midY = rect.top + rect.height / 2;
            const midX = rect.left + rect.width / 2;

            // If we're above the middle of this widget, insert before it
            if (y < midY) {
                return widget;
            }
            // If we're on the same row but to the left
            if (y >= rect.top && y <= rect.bottom && x < midX) {
                return widget;
            }
        }

        return null;
    }

    /**
     * Show drop indicator at position
     * @param {Element|null} afterEl - Element to show indicator before
     */
    showDropIndicator(afterEl) {
        if (!this.dropIndicator.parentNode) {
            this.grid.appendChild(this.dropIndicator);
        }

        this.dropIndicator.style.display = 'block';

        if (afterEl) {
            this.grid.insertBefore(this.dropIndicator, afterEl);
        } else {
            this.grid.appendChild(this.dropIndicator);
        }
    }

    /**
     * Hide drop indicator
     */
    hideDropIndicator() {
        this.dropIndicator.style.display = 'none';
        if (this.dropIndicator.parentNode) {
            this.dropIndicator.parentNode.removeChild(this.dropIndicator);
        }
    }

    /**
     * Create a new widget element
     * @param {string} widgetName - Widget name
     * @param {string|number} width - Widget width (1-12)
     * @param {Element|null} beforeEl - Element to insert before
     * @returns {Element}
     */
    createWidget(widgetName, width, beforeEl = null) {
        const widget = document.createElement('div');
        widget.className = 'widget-item';
        widget.draggable = true;
        widget.dataset.widgetName = widgetName;
        widget.dataset.width = width;
        widget.dataset.widgetSettings = '{}';

        const content = document.createElement('div');
        content.className = 'widget-content';
        content.innerHTML = this.utils.createSkeleton();
        widget.appendChild(content);

        // Add resize handle
        this.addResizeHandle(widget);

        // Add dropping animation
        widget.classList.add('widget-dropping');
        setTimeout(() => {
            widget.classList.remove('widget-dropping');
        }, 400);

        // Insert at position
        if (beforeEl) {
            this.grid.insertBefore(widget, beforeEl);
        } else {
            this.grid.appendChild(widget);
        }

        // Initialize widget content
        this.editor.widgetLoader.initializeWidget(widget, content);
        this.onGridChange();

        this.eventBus.emit(window.FlutePageEdit.events.WIDGET_DROPPED, {
            widget: widget,
            widgetName: widgetName
        });

        return widget;
    }

    /**
     * Handle grid change
     */
    onGridChange() {
        if (this.editor.history?.isProcessing) return;

        clearTimeout(this.changeTimeout);
        this.changeTimeout = setTimeout(() => {
            this.editor.hasUnsavedChanges = true;
            this.editor.updateSaveButtonState();
            this.editor.history?.push();
            this.editor.saveToLocalStorage();

            this.eventBus.emit(window.FlutePageEdit.events.GRID_CHANGED);
        }, 100);
    }

    /**
     * Set widget width
     * @param {Element} widget - Widget element
     * @param {string|number} width - Width value (3, 4, 6, 12)
     */
    setWidgetWidth(widget, width) {
        if (!widget) return;
        widget.dataset.width = width;
        this.onGridChange();

        this.eventBus.emit(window.FlutePageEdit.events.WIDGET_RESIZED, {
            widget: widget,
            width: width
        });
    }

    /**
     * Remove a widget
     * @param {Element} widgetEl - Widget element
     */
    removeWidget(widgetEl) {
        if (!widgetEl) return;

        // Prevent removal of Content widget
        if (widgetEl.getAttribute('data-widget-name') === 'Content') {
            return;
        }

        const widgetName = widgetEl.getAttribute('data-widget-name');
        widgetEl.remove();
        this.onGridChange();

        this.eventBus.emit(window.FlutePageEdit.events.WIDGET_REMOVED, {
            widgetName: widgetName
        });
    }

    /**
     * Get all widget items
     * @returns {Element[]}
     */
    getItems() {
        if (!this.grid) return [];
        return [...this.grid.querySelectorAll('.widget-item')];
    }

    /**
     * Get widgets (alias for getItems)
     * @returns {Element[]}
     */
    getWidgets() {
        return this.getItems();
    }

    /**
     * Compact the grid (no-op for flexbox, kept for compatibility)
     */
    compact() {
        this.onGridChange();
        this.eventBus.emit(window.FlutePageEdit.events.GRID_COMPACTED);
    }

    /**
     * Make element a widget (for compatibility)
     * @param {Element} el
     * @returns {Element}
     */
    makeWidget(el) {
        if (!el) return el;

        el.classList.add('widget-item');
        el.draggable = true;

        if (!el.dataset.width) {
            el.dataset.width = '6';
        }

        if (!el.querySelector('.widget-content')) {
            const content = el.querySelector('.grid-stack-item-content');
            if (content) {
                content.className = 'widget-content';
            }
        }

        // Add resize handle
        this.addResizeHandle(el);

        // Add appearing animation
        el.classList.add('widget-appearing');
        setTimeout(() => {
            el.classList.remove('widget-appearing');
        }, 500);

        this.grid.appendChild(el);
        return el;
    }

    /**
     * Update widget (for compatibility)
     * @param {Element} widgetEl
     * @param {object} props
     */
    updateWidget(widgetEl, props) {
        if (!widgetEl || !props) return;

        if (props.w !== undefined) {
            this.setWidgetWidth(widgetEl, props.w);
        }
    }

    /**
     * Remove all widgets
     */
    removeAll() {
        if (!this.grid) return;
        this.grid.innerHTML = '';
    }

    /**
     * Get grid instance (for compatibility)
     * @returns {DragDropController}
     */
    getGrid() {
        return this;
    }

    /**
     * Destroy the controller
     */
    destroy() {
        if (!this.grid) return;

        clearTimeout(this.changeTimeout);

        // Remove event listeners
        if (this._boundHandlers) {
            this.grid.removeEventListener('dragstart', this._boundHandlers.dragstart);
            this.grid.removeEventListener('dragover', this._boundHandlers.dragover);
            this.grid.removeEventListener('dragleave', this._boundHandlers.dragleave);
            this.grid.removeEventListener('drop', this._boundHandlers.drop);
            this.grid.removeEventListener('dragend', this._boundHandlers.dragend);
        }

        // Remove resize event listeners
        document.removeEventListener('mousemove', this._boundHandlers.resizeMove);
        document.removeEventListener('mouseup', this._boundHandlers.resizeEnd);

        this.hideDropIndicator();
        this.removeWidthIndicator();
        this.removeGridOverlay();
        this.grid = null;
        this.draggedEl = null;

        this.eventBus.emit(window.FlutePageEdit.events.GRID_DESTROYED);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // Resize Handle Methods
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Create width indicator element
     */
    createWidthIndicator() {
        this.widthIndicator = document.createElement('div');
        this.widthIndicator.className = 'widget-width-indicator';
        this.widthIndicator.innerHTML = `
            <span class="widget-width-indicator__value">6</span>
            <span class="widget-width-indicator__label">/12</span>
            <div class="widget-width-indicator__bar">
                ${Array(12).fill(0).map(() => '<span></span>').join('')}
            </div>
        `;
        document.body.appendChild(this.widthIndicator);
    }

    /**
     * Create grid overlay for visual feedback
     */
    createGridOverlay() {
        this.gridOverlay = document.createElement('div');
        this.gridOverlay.className = 'widget-grid-overlay';
        this.gridOverlay.innerHTML = `
            <div class="widget-grid-overlay__columns">
                ${Array(12).fill(0).map(() => '<span></span>').join('')}
            </div>
        `;
        document.body.appendChild(this.gridOverlay);
    }

    /**
     * Remove width indicator
     */
    removeWidthIndicator() {
        if (this.widthIndicator) {
            this.widthIndicator.remove();
            this.widthIndicator = null;
        }
    }

    /**
     * Remove grid overlay
     */
    removeGridOverlay() {
        if (this.gridOverlay) {
            this.gridOverlay.remove();
            this.gridOverlay = null;
        }
    }

    /**
     * Update width indicator display
     * @param {number} width - Current width value
     * @param {number} x - Mouse X position
     * @param {number} y - Mouse Y position
     */
    updateWidthIndicator(width, x, y) {
        if (!this.widthIndicator) return;

        const valueEl = this.widthIndicator.querySelector('.widget-width-indicator__value');
        const bars = this.widthIndicator.querySelectorAll('.widget-width-indicator__bar span');

        if (valueEl) {
            valueEl.textContent = width;
        }

        bars.forEach((bar, i) => {
            bar.classList.toggle('active', i < width);
        });

        // Position near cursor
        this.widthIndicator.style.left = `${x + 20}px`;
        this.widthIndicator.style.top = `${y - 25}px`;
    }

    /**
     * Show width indicator
     */
    showWidthIndicator() {
        if (this.widthIndicator) {
            this.widthIndicator.classList.add('visible');
        }
        if (this.gridOverlay) {
            this.gridOverlay.classList.add('visible');
        }
    }

    /**
     * Hide width indicator
     */
    hideWidthIndicator() {
        if (this.widthIndicator) {
            this.widthIndicator.classList.remove('visible');
        }
        if (this.gridOverlay) {
            this.gridOverlay.classList.remove('visible');
        }
    }

    /**
     * Add resize handle to a widget
     * @param {Element} widget - Widget element
     */
    addResizeHandle(widget) {
        if (!widget || widget.querySelector('.widget-resize-handle')) return;

        const widgetName = widget.getAttribute('data-widget-name');
        if (widgetName === 'Content') return;

        const handle = document.createElement('div');
        handle.className = 'widget-resize-handle';

        handle.addEventListener('mousedown', (e) => {
            e.preventDefault();
            e.stopPropagation();
            this.startResize(widget, e);
        });

        widget.appendChild(handle);
    }

    /**
     * Setup global resize event listeners
     */
    setupResizeEvents() {
        this._boundHandlers.resizeMove = (e) => this.onResizeMove(e);
        this._boundHandlers.resizeEnd = (e) => this.onResizeEnd(e);

        document.addEventListener('mousemove', this._boundHandlers.resizeMove);
        document.addEventListener('mouseup', this._boundHandlers.resizeEnd);
    }

    /**
     * Start resizing a widget
     * @param {Element} widget - Widget to resize
     * @param {MouseEvent} e - Mouse event
     */
    startResize(widget, e) {
        if (!widget || !this.grid) return;

        const gridRect = this.grid.getBoundingClientRect();
        const gap = 16;

        this.resizing = {
            active: true,
            widget: widget,
            startX: e.clientX,
            startWidth: parseInt(widget.dataset.width) || 6,
            gridWidth: gridRect.width,
            columnWidth: (gridRect.width - gap * 11) / 12,
            gridLeft: gridRect.left
        };

        widget.classList.add('resizing');
        widget.style.transition = 'none';
        document.body.style.cursor = 'ew-resize';
        document.body.style.userSelect = 'none';

        this.showWidthIndicator();
        this.updateWidthIndicator(this.resizing.startWidth, e.clientX, e.clientY);

        this.eventBus.emit(window.FlutePageEdit.events.WIDGET_RESIZE_START, {
            widget: widget,
            width: this.resizing.startWidth
        });
    }

    /**
     * Handle resize mouse move
     * @param {MouseEvent} e - Mouse event
     */
    onResizeMove(e) {
        if (!this.resizing.active || !this.resizing.widget) return;

        const { widget, gridLeft, gridWidth } = this.resizing;
        const widgetRect = widget.getBoundingClientRect();
        const gap = 16;

        // Calculate new width based on mouse position relative to grid
        const mouseRelativeToWidget = e.clientX - widgetRect.left;
        const availableGridWidth = gridWidth - gap * 11;
        const columnWidth = availableGridWidth / 12;

        // Calculate how many columns the new width would be
        let newCols = Math.round(mouseRelativeToWidget / (columnWidth + gap));
        newCols = Math.max(1, Math.min(12, newCols));

        // Update widget width visually
        if (newCols !== parseInt(widget.dataset.width)) {
            widget.dataset.width = newCols;
        }

        this.updateWidthIndicator(newCols, e.clientX, e.clientY);
    }

    /**
     * Handle resize mouse up
     * @param {MouseEvent} e - Mouse event
     */
    onResizeEnd(e) {
        if (!this.resizing.active) return;

        const { widget } = this.resizing;
        const finalWidth = parseInt(widget?.dataset.width) || 6;

        if (widget) {
            widget.classList.remove('resizing');
            widget.style.transition = '';
        }

        document.body.style.cursor = '';
        document.body.style.userSelect = '';

        this.hideWidthIndicator();

        // Trigger change if width changed
        if (widget && finalWidth !== this.resizing.startWidth) {
            this.onGridChange();

            this.eventBus.emit(window.FlutePageEdit.events.WIDGET_RESIZED, {
                widget: widget,
                width: finalWidth
            });
        }

        this.resizing = {
            active: false,
            widget: null,
            startX: 0,
            startWidth: 0,
            gridWidth: 0,
            columnWidth: 0
        };
    }
}

// Register with backwards-compatible name
window.FlutePageEdit.register('GridController', DragDropController);
window.FlutePageEdit.register('DragDropController', DragDropController);
