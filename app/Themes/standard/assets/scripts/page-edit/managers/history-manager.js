/**
 * History Manager - handles undo/redo functionality
 */
class HistoryManager {
    constructor(editor) {
        this.editor = editor;
        this.states = [];
        this.currentIndex = -1;
        this.isProcessing = false;
        this.maxStates = 50;
        this.eventBus = window.FlutePageEdit.eventBus;
    }

    /**
     * Create a snapshot of the current grid state
     * @returns {object}
     */
    createSnapshot() {
        const items = Array.from(
            document.querySelectorAll('.grid-stack .grid-stack-item')
        ).map(el => {
            const node = el.gridstackNode;
            const toolbar = el.querySelector('.widget-toolbar');

            return {
                id: el.getAttribute('data-widget-id'),
                widgetName: el.getAttribute('data-widget-name'),
                settings: el.dataset.widgetSettings,
                content: el.querySelector('.grid-stack-item-content')?.innerHTML,
                buttons: toolbar
                    ? this.editor.widgetButtonsCache[el.getAttribute('data-widget-name')]
                    : [],
                position: {
                    x: node?.x ?? 0,
                    y: node?.y ?? 0,
                    w: node?.w ?? 1,
                    h: node?.h ?? 1
                }
            };
        });

        return {
            items,
            timestamp: Date.now()
        };
    }

    /**
     * Apply a snapshot to the grid
     * @param {object} snapshot - Snapshot to apply
     */
    applySnapshot(snapshot) {
        if (!snapshot || !snapshot.items) return;

        this.isProcessing = true;

        this.editor.grid.removeAll();

        snapshot.items.forEach(item => {
            const div = document.createElement('div');
            div.classList.add('grid-stack-item');

            if (item.id) div.setAttribute('data-widget-id', item.id);
            div.setAttribute('data-widget-name', item.widgetName || '');
            div.dataset.widgetSettings = item.settings;

            Object.entries(item.position).forEach(([key, value]) => {
                div.setAttribute(`gs-${key}`, value);
            });

            const content = document.createElement('div');
            content.classList.add('grid-stack-item-content');
            content.innerHTML = item.content;
            content.style.pointerEvents = 'auto';
            div.appendChild(content);

            const widget = this.editor.grid.makeWidget(div);

            this.editor.grid.update(widget, item.position);

            if (item.buttons) {
                this.editor.addToolbar(div, item.buttons);
            }
        });

        this.isProcessing = false;
    }

    /**
     * Push current state to history
     */
    push() {
        if (this.isProcessing) return;

        // Remove states after current position (for new branch)
        if (this.currentIndex < this.states.length - 1) {
            this.states = this.states.slice(0, this.currentIndex + 1);
        }

        this.states.push(this.createSnapshot());
        this.currentIndex = this.states.length - 1;

        // Limit history size
        if (this.states.length > this.maxStates) {
            this.states.shift();
            this.currentIndex--;
        }

        this.editor.updateUndoRedoButtons();
        this.eventBus.emit(window.FlutePageEdit.events.HISTORY_PUSH, {
            index: this.currentIndex,
            total: this.states.length
        });
    }

    /**
     * Undo last action
     * @returns {boolean} Success
     */
    undo() {
        if (this.currentIndex > 0) {
            this.currentIndex--;
            this.applySnapshot(this.states[this.currentIndex]);
            this.editor.updateUndoRedoButtons();
            this.eventBus.emit(window.FlutePageEdit.events.HISTORY_UNDO, {
                index: this.currentIndex
            });
            return true;
        }
        return false;
    }

    /**
     * Redo last undone action
     * @returns {boolean} Success
     */
    redo() {
        if (this.currentIndex < this.states.length - 1) {
            this.currentIndex++;
            this.applySnapshot(this.states[this.currentIndex]);
            this.editor.updateUndoRedoButtons();
            this.eventBus.emit(window.FlutePageEdit.events.HISTORY_REDO, {
                index: this.currentIndex
            });
            return true;
        }
        return false;
    }

    /**
     * Check if undo is available
     * @returns {boolean}
     */
    canUndo() {
        return this.currentIndex > 0;
    }

    /**
     * Check if redo is available
     * @returns {boolean}
     */
    canRedo() {
        return this.currentIndex < this.states.length - 1;
    }

    /**
     * Clear all history
     */
    clear() {
        this.states = [];
        this.currentIndex = -1;
        this.editor.updateUndoRedoButtons();
        this.eventBus.emit(window.FlutePageEdit.events.HISTORY_CLEAR);
    }

    /**
     * Get current history state info
     * @returns {object}
     */
    getState() {
        return {
            canUndo: this.canUndo(),
            canRedo: this.canRedo(),
            currentIndex: this.currentIndex,
            totalStates: this.states.length
        };
    }
}

window.FlutePageEdit.register('HistoryManager', HistoryManager);
