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
        this.dropPlaceholder = null;
        this.changeTimeout = null;
        this._boundHandlers = {};

        // Drag state - stores dragged widget dimensions
        this.dragState = {
            width: 6,
            height: 100
        };

        // Drag state throttling and hysteresis
        this._dragOverThrottled = false;
        this._lastInsertPosition = null;

        // Resize state
        this.resizing = {
            active: false,
            widget: null,
            startX: 0,
            startWidth: 0,
            gridWidth: 0,
            columnWidth: 0
        };
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

            this.createDropPlaceholder();
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
     * Create drop placeholder element (sized like the dragged widget)
     */
    createDropPlaceholder() {
        this.dropPlaceholder = document.createElement('div');
        this.dropPlaceholder.className = 'drop-placeholder';
        this.dropPlaceholder.style.display = 'none';
    }

    /**
     * Setup grid event listeners
     */
    setupGridEvents() {
        if (!this.grid) return;

        // Store bound handlers for cleanup (extend, don't replace)
        this._boundHandlers.dragstart = (e) => this.onDragStart(e);
        this._boundHandlers.dragover = (e) => this.onDragOver(e);
        this._boundHandlers.dragleave = (e) => this.onDragLeave(e);
        this._boundHandlers.drop = (e) => this.onDrop(e);
        this._boundHandlers.dragend = (e) => this.onDragEnd(e);

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

        // Store dragged widget dimensions for placeholder
        this.dragState.width = parseInt(widget.dataset.width) || 6;
        this.dragState.height = widget.offsetHeight;

        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', 'reorder');

        // Create custom drag image before hiding the widget
        const ghost = widget.cloneNode(true);
        ghost.style.position = 'absolute';
        ghost.style.top = '-9999px';
        ghost.style.left = '-9999px';
        ghost.style.width = `${widget.offsetWidth}px`;
        ghost.style.opacity = '0.8';
        ghost.style.pointerEvents = 'none';
        document.body.appendChild(ghost);

        if (e.dataTransfer.setDragImage) {
            e.dataTransfer.setDragImage(ghost, ghost.offsetWidth / 2, 20);
        }

        // Remove ghost after drag image is captured
        setTimeout(() => ghost.remove(), 0);

        // Hide original widget and show placeholder in its place
        widget.classList.add('dragging');

        // Insert placeholder where the widget was
        this.dropPlaceholder.dataset.width = this.dragState.width;
        this.dropPlaceholder.style.minHeight = `${this.dragState.height}px`;
        this.dropPlaceholder.style.display = 'block';
        this.grid.insertBefore(this.dropPlaceholder, widget.nextElementSibling);

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

        // Throttle to prevent excessive updates
        if (this._dragOverThrottled) return;
        this._dragOverThrottled = true;
        setTimeout(() => { this._dragOverThrottled = false; }, 100);

        const afterEl = this.getInsertPosition(e.clientX, e.clientY);
        this.showDropPlaceholder(afterEl);
    }

    /**
     * Handle drag leave event
     * @param {DragEvent} e
     */
    onDragLeave(e) {
        // Only hide if leaving the grid entirely
        if (!this.grid.contains(e.relatedTarget)) {
            this.hideDropPlaceholder();
        }
    }

    /**
     * Handle drop event
     * @param {DragEvent} e
     */
    onDrop(e) {
        e.preventDefault();

        // Get position before hiding placeholder
        const afterEl = this.getInsertPositionFromPlaceholder();
        this.hideDropPlaceholder();
        this._lastInsertPosition = null;

        // Check if it's a new widget from sidebar
        const widgetName = e.dataTransfer.getData('widget-name');
        if (widgetName) {
            const width = e.dataTransfer.getData('widget-width') || '6';
            this.createWidget(widgetName, width, afterEl);
            return;
        }

        // Reorder existing widget
        if (this.draggedEl) {
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
     * Get insert position based on placeholder location
     * @returns {Element|null}
     */
    getInsertPositionFromPlaceholder() {
        if (!this.dropPlaceholder || !this.dropPlaceholder.parentNode) {
            return null;
        }
        return this.dropPlaceholder.nextElementSibling;
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
        this.hideDropPlaceholder();
        this._dragOverThrottled = false;
        this._lastInsertPosition = null;

        this.eventBus.emit(window.FlutePageEdit.events.WIDGET_DRAG_END);
    }

    /**
     * Get the element to insert before based on mouse position
     * Works with CSS Grid - finds closest widget and determines before/after
     * Uses hysteresis to prevent jitter at boundaries
     * @param {number} x - Mouse X position
     * @param {number} y - Mouse Y position
     * @returns {Element|null}
     */
    getInsertPosition(x, y) {
        const widgets = [...this.grid.querySelectorAll('.widget-item:not(.dragging)')];
        if (widgets.length === 0) return this._lastInsertPosition;

        let closest = null;
        let closestDist = Infinity;

        // Find closest widget by distance to center
        for (const widget of widgets) {
            const rect = widget.getBoundingClientRect();
            const centerX = rect.left + rect.width / 2;
            const centerY = rect.top + rect.height / 2;

            const dist = Math.sqrt(Math.pow(x - centerX, 2) + Math.pow(y - centerY, 2));

            if (dist < closestDist) {
                closestDist = dist;
                closest = widget;
            }
        }

        if (!closest) return this._lastInsertPosition;

        const rect = closest.getBoundingClientRect();
        const relativeX = (x - rect.left) / rect.width;

        // Hysteresis: use different thresholds based on current position
        // This prevents oscillation at the boundary
        const currentIsBeforeClosest = this._lastInsertPosition === closest;
        const currentIsAfterClosest = this._lastInsertPosition === closest.nextElementSibling;

        let insertBefore;
        if (currentIsBeforeClosest) {
            // Currently inserting before - need to move past 0.65 to switch to after
            insertBefore = relativeX < 0.65;
        } else if (currentIsAfterClosest) {
            // Currently inserting after - need to move below 0.35 to switch to before
            insertBefore = relativeX < 0.35;
        } else {
            // No current position near this widget - use normal 0.5 threshold
            insertBefore = relativeX < 0.5;
        }

        const newPosition = insertBefore ? closest : closest.nextElementSibling;
        this._lastInsertPosition = newPosition;
        return newPosition;
    }

    /**
     * Show drop placeholder at position
     * @param {Element|null} afterEl - Element to show placeholder before
     */
    showDropPlaceholder(afterEl) {
        // Set placeholder size based on dragged widget
        this.dropPlaceholder.dataset.width = this.dragState.width;
        this.dropPlaceholder.style.minHeight = `${Math.max(80, this.dragState.height)}px`;

        // Skip if position hasn't changed
        const currentNext = this.dropPlaceholder.nextElementSibling;
        if (this.dropPlaceholder.parentNode === this.grid && currentNext === afterEl) {
            return;
        }

        // Insert placeholder
        if (!this.dropPlaceholder.parentNode) {
            this.grid.appendChild(this.dropPlaceholder);
        }
        this.dropPlaceholder.style.display = 'block';

        // Move to correct position
        if (afterEl && afterEl !== this.dropPlaceholder) {
            this.grid.insertBefore(this.dropPlaceholder, afterEl);
        } else if (!afterEl && this.dropPlaceholder.nextElementSibling) {
            this.grid.appendChild(this.dropPlaceholder);
        }
    }

    /**
     * Hide drop placeholder
     */
    hideDropPlaceholder() {
        if (this.dropPlaceholder.parentNode) {
            this.dropPlaceholder.parentNode.removeChild(this.dropPlaceholder);
        }
        this.dropPlaceholder.style.display = 'none';
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

        this.hideDropPlaceholder();
        this.grid = null;
        this.draggedEl = null;

        this.eventBus.emit(window.FlutePageEdit.events.GRID_DESTROYED);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // Resize Handle Methods
    // ═══════════════════════════════════════════════════════════════════════════

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
        document.body.style.cursor = 'ew-resize';
        document.body.style.userSelect = 'none';

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

        const { widget, gridWidth } = this.resizing;
        const widgetRect = widget.getBoundingClientRect();
        const gap = 16;

        // Calculate new width based on mouse position relative to widget
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
        }

        document.body.style.cursor = '';
        document.body.style.userSelect = '';

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
