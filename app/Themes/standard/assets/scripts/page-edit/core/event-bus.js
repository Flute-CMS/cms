/**
 * Event Bus for communication between modules
 */
class EventBus {
    constructor() {
        this.listeners = new Map();
    }

    /**
     * Subscribe to an event
     * @param {string} event - Event name
     * @param {Function} callback - Callback function
     * @returns {Function} Unsubscribe function
     */
    on(event, callback) {
        if (!this.listeners.has(event)) {
            this.listeners.set(event, new Set());
        }
        this.listeners.get(event).add(callback);

        // Return unsubscribe function
        return () => this.off(event, callback);
    }

    /**
     * Subscribe to an event once
     * @param {string} event - Event name
     * @param {Function} callback - Callback function
     */
    once(event, callback) {
        const wrapper = (...args) => {
            this.off(event, wrapper);
            callback(...args);
        };
        this.on(event, wrapper);
    }

    /**
     * Unsubscribe from an event
     * @param {string} event - Event name
     * @param {Function} callback - Callback function
     */
    off(event, callback) {
        if (this.listeners.has(event)) {
            this.listeners.get(event).delete(callback);
        }
    }

    /**
     * Emit an event
     * @param {string} event - Event name
     * @param {*} data - Event data
     */
    emit(event, data = {}) {
        if (this.listeners.has(event)) {
            this.listeners.get(event).forEach(callback => {
                try {
                    callback(data);
                } catch (err) {
                    console.error(`EventBus error in "${event}" handler:`, err);
                }
            });
        }

        // Also dispatch a custom DOM event
        try {
            const customEvent = new CustomEvent(`pageEdit:${event}`, {
                detail: data,
                bubbles: true,
                cancelable: true
            });
            document.dispatchEvent(customEvent);
        } catch (err) {
            console.error(`EventBus DOM event error:`, err);
        }
    }

    /**
     * Clear all listeners for an event or all events
     * @param {string} [event] - Optional event name
     */
    clear(event) {
        if (event) {
            this.listeners.delete(event);
        } else {
            this.listeners.clear();
        }
    }
}

// Event names constants
const PageEditEvents = {
    // Editor lifecycle
    EDITOR_ENABLED: 'editorEnabled',
    EDITOR_DISABLED: 'editorDisabled',
    EDITOR_READY: 'editorReady',

    // Grid events
    GRID_INITIALIZED: 'gridInitialized',
    GRID_DESTROYED: 'gridDestroyed',
    GRID_CHANGED: 'gridChanged',
    GRID_COMPACTED: 'gridCompacted',

    // Widget events
    WIDGET_ADDED: 'widgetAdded',
    WIDGET_REMOVED: 'widgetRemoved',
    WIDGET_MOVED: 'widgetMoved',
    WIDGET_RESIZED: 'widgetResized',
    WIDGET_INITIALIZED: 'widgetInitialized',
    WIDGET_REFRESHED: 'widgetRefreshed',
    WIDGET_DROPPED: 'widgetDropped',
    WIDGET_SETTINGS_OPENED: 'widgetSettingsOpened',
    WIDGET_SETTINGS_SAVED: 'widgetSettingsSaved',
    WIDGET_SETTINGS_LOADED: 'widgetSettingsLoaded',
    WIDGET_CONTENT_LOADED: 'widgetContentLoaded',
    WIDGET_DRAG_START: 'widgetDragStart',
    WIDGET_DRAG_END: 'widgetDragEnd',
    WIDGET_REORDERED: 'widgetReordered',
    WIDGET_RESIZE_START: 'widgetResizeStart',
    WIDGET_SELECTED: 'widgetSelected',
    WIDGET_DESELECTED: 'widgetDeselected',

    // History events
    HISTORY_PUSH: 'historyPush',
    HISTORY_UNDO: 'historyUndo',
    HISTORY_REDO: 'historyRedo',
    HISTORY_CLEAR: 'historyClear',

    // Layout events
    LAYOUT_LOADED: 'layoutLoaded',
    LAYOUT_SAVED: 'layoutSaved',
    LAYOUT_RESET: 'layoutReset',
    LAYOUT_SAVE_ERROR: 'layoutSaveError',

    // UI events
    SIDEBAR_OPENED: 'sidebarOpened',
    SIDEBAR_CLOSED: 'sidebarClosed',
    SIDEBAR_DRAG_START: 'sidebarDragStart',
    SIDEBAR_DRAG_END: 'sidebarDragEnd',
    CATEGORY_OPENED: 'categoryOpened',
    CATEGORY_CLOSED: 'categoryClosed',
    SEARCH_PERFORMED: 'searchPerformed',

    // Mode events
    HEIGHT_MODE_CHANGED: 'heightModeChanged',
    CONTAINER_WIDTH_CHANGED: 'containerWidthChanged',
    SCOPE_CHANGED: 'scopeChanged',
    PREVIEW_CHANGED: 'previewChanged',

    // Error events
    ERROR: 'error',
    NOTIFICATION: 'notification'
};

// Create singleton instance
window.FlutePageEdit.eventBus = new EventBus();
window.FlutePageEdit.events = PageEditEvents;
window.FlutePageEdit.register('EventBus', EventBus);
