/**
 * History Manager — undo/redo via GridStack-compatible snapshots.
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
     * Create a snapshot of the current grid state.
     * Captures GridStack node positions (x, y, w, h).
     */
    createSnapshot() {
        const gc = this.editor.gridController;
        if (!gc?.gsGrid) return { items: [], timestamp: Date.now() };

        const items = gc.getItems().map((el, index) => {
            const node = el.gridstackNode || {};

            return {
                id: el.getAttribute('data-widget-id'),
                widgetName: el.getAttribute('data-widget-name'),
                settings: el.dataset.widgetSettings,
                conditions: el.dataset.conditions || '{"auth":"all","device":"all"}',
                content: el.querySelector('.widget-content')?.innerHTML,
                buttons: this.editor.widgetButtonsCache?.[el.getAttribute('data-widget-name')] || [],
                isSystem: el.getAttribute('data-system-widget') === 'true',
                hasSettings: el.getAttribute('data-has-settings'),
                position: {
                    width: node.w || DragDropController.getCols(el),
                    height: node.h || 2,
                    x: node.x ?? 0,
                    y: node.y ?? 0,
                    order: index
                }
            };
        });

        return { items, timestamp: Date.now() };
    }

    /**
     * Apply a snapshot to the grid using GridStack API.
     */
    applySnapshot(snapshot) {
        if (!snapshot?.items) return;

        const gc = this.editor.gridController;
        if (!gc?.gsGrid) return;

        this.isProcessing = true;

        try {
            gc.gsGrid.batchUpdate();
            gc.gsGrid.removeAll();

            snapshot.items.forEach(item => {
                const isSystem = item.isSystem;

                const el = gc.gsGrid.addWidget({
                    w: item.position?.width || 6,
                    h: item.position?.height || 1,
                    x: item.position?.x ?? 0,
                    y: item.position?.y,
                    sizeToContent: true,
                    content: `<div class="widget-content">${item.content || ''}</div>`,
                    noResize: isSystem,
                    noMove: isSystem,
                });

                if (!el) return;

                if (item.id) el.setAttribute('data-widget-id', item.id);
                el.setAttribute('data-widget-name', item.widgetName || '');
                el.dataset.widgetSettings = item.settings || '{}';
                el.dataset.conditions = item.conditions || '{"auth":"all","device":"all"}';
                el.classList.add('widget-item');

                if (isSystem) {
                    el.setAttribute('data-system-widget', 'true');
                }

                if (item.hasSettings) {
                    el.setAttribute('data-has-settings', item.hasSettings);
                }

                // Re-add toolbar
                if (item.buttons) {
                    this.editor.addToolbar(el, item.buttons);
                }

                // Make widget content non-interactive
                const widgetContent = el.querySelector('.widget-content');
                if (widgetContent) {
                    widgetContent.style.pointerEvents = 'auto';
                }
            });

            gc.gsGrid.batchUpdate(false);

            // Resize all widgets to content after restoring snapshot
            setTimeout(() => {
                gc.resizeAllToContent();
            }, 100);
        } catch (err) {
            console.error('HistoryManager.applySnapshot error:', err);
        }

        this.isProcessing = false;
    }

    /**
     * Push current state to history
     */
    push() {
        if (this.isProcessing) return;

        if (this.currentIndex < this.states.length - 1) {
            this.states = this.states.slice(0, this.currentIndex + 1);
        }

        this.states.push(this.createSnapshot());
        this.currentIndex = this.states.length - 1;

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

    undo() {
        if (this.currentIndex > 0) {
            this.currentIndex--;
            this.applySnapshot(this.states[this.currentIndex]);
            this.editor.updateUndoRedoButtons();
            this.eventBus.emit(window.FlutePageEdit.events.HISTORY_UNDO, { index: this.currentIndex });
            return true;
        }
        return false;
    }

    redo() {
        if (this.currentIndex < this.states.length - 1) {
            this.currentIndex++;
            this.applySnapshot(this.states[this.currentIndex]);
            this.editor.updateUndoRedoButtons();
            this.eventBus.emit(window.FlutePageEdit.events.HISTORY_REDO, { index: this.currentIndex });
            return true;
        }
        return false;
    }

    canUndo() { return this.currentIndex > 0; }
    canRedo() { return this.currentIndex < this.states.length - 1; }

    clear() {
        this.states = [];
        this.currentIndex = -1;
        this.editor.updateUndoRedoButtons();
        this.eventBus.emit(window.FlutePageEdit.events.HISTORY_CLEAR);
    }

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
