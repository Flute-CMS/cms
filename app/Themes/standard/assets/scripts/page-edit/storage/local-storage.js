/**
 * Local Storage Handler - manages localStorage operations for page editor
 */
class LocalStorageHandler {
    constructor(editor) {
        this.editor = editor;
        this.config = editor.config;
        this.utils = window.FlutePageEdit.utils;

        this.currentPath = null;
    }

    /**
     * Initialize handler
     */
    initialize() {
        this.currentPath = this.utils.getCurrentPath();
    }

    /**
     * Get storage key for current page
     * @returns {string}
     */
    getLayoutKey() {
        return `${this.config.storageKeys.layout}${this.currentPath}`;
    }

    /**
     * Save layout to localStorage
     * @param {Array} layoutData - Layout data to save
     * @returns {boolean} Success
     */
    saveLayout(layoutData) {
        try {
            if (!layoutData || layoutData.length === 0) return false;

            localStorage.setItem(
                this.getLayoutKey(),
                JSON.stringify(layoutData)
            );
            return true;
        } catch (err) {
            this.utils.logError('LocalStorageHandler.saveLayout', err);
            return false;
        }
    }

    /**
     * Load layout from localStorage
     * @returns {Array|null}
     */
    loadLayout() {
        try {
            const saved = localStorage.getItem(this.getLayoutKey());
            if (!saved) return null;

            const layoutData = JSON.parse(saved);
            if (!Array.isArray(layoutData) || layoutData.length === 0) {
                return null;
            }
            return layoutData;
        } catch (err) {
            this.utils.logError('LocalStorageHandler.loadLayout', err);
            this.clearLayout();
            return null;
        }
    }

    /**
     * Clear layout from localStorage
     */
    clearLayout() {
        try {
            localStorage.removeItem(this.getLayoutKey());
        } catch (err) {
            this.utils.logError('LocalStorageHandler.clearLayout', err);
        }
    }

    /**
     * Check if there's a saved layout
     * @returns {boolean}
     */
    hasSavedLayout() {
        return localStorage.getItem(this.getLayoutKey()) !== null;
    }

    /**
     * Save generic data
     * @param {string} key - Storage key
     * @param {*} data - Data to save
     * @returns {boolean}
     */
    save(key, data) {
        try {
            localStorage.setItem(key, JSON.stringify(data));
            return true;
        } catch (err) {
            this.utils.logError(`LocalStorageHandler.save(${key})`, err);
            return false;
        }
    }

    /**
     * Load generic data
     * @param {string} key - Storage key
     * @param {*} defaultValue - Default value if not found
     * @returns {*}
     */
    load(key, defaultValue = null) {
        try {
            const saved = localStorage.getItem(key);
            if (saved === null) return defaultValue;
            return JSON.parse(saved);
        } catch (err) {
            this.utils.logError(`LocalStorageHandler.load(${key})`, err);
            return defaultValue;
        }
    }

    /**
     * Remove item from storage
     * @param {string} key - Storage key
     */
    remove(key) {
        try {
            localStorage.removeItem(key);
        } catch (err) {
            this.utils.logError(`LocalStorageHandler.remove(${key})`, err);
        }
    }

    /**
     * Clear all page editor related data
     */
    clearAll() {
        try {
            const keysToRemove = [];

            for (let i = 0; i < localStorage.length; i++) {
                const key = localStorage.key(i);
                if (key && (
                    key.startsWith(this.config.storageKeys.layout) ||
                    key === this.config.storageKeys.heightMode ||
                    key === this.config.storageKeys.containerWidth ||
                    key === this.config.storageKeys.sidebarState ||
                    key === this.config.storageKeys.categoryState
                )) {
                    keysToRemove.push(key);
                }
            }

            keysToRemove.forEach(key => localStorage.removeItem(key));
        } catch (err) {
            this.utils.logError('LocalStorageHandler.clearAll', err);
        }
    }

    /**
     * Get all saved layouts
     * @returns {Array<{path: string, data: Array}>}
     */
    getAllLayouts() {
        const layouts = [];

        try {
            const prefix = this.config.storageKeys.layout;

            for (let i = 0; i < localStorage.length; i++) {
                const key = localStorage.key(i);
                if (key && key.startsWith(prefix)) {
                    const path = key.substring(prefix.length);
                    const data = this.load(key);
                    if (data) {
                        layouts.push({ path, data });
                    }
                }
            }
        } catch (err) {
            this.utils.logError('LocalStorageHandler.getAllLayouts', err);
        }

        return layouts;
    }

    /**
     * Export all data as JSON
     * @returns {string}
     */
    exportData() {
        const data = {
            layouts: this.getAllLayouts(),
            heightMode: this.load(this.config.storageKeys.heightMode),
            containerWidth: this.load(this.config.storageKeys.containerWidth),
            categoryState: this.load(this.config.storageKeys.categoryState),
            exportedAt: new Date().toISOString()
        };
        return JSON.stringify(data, null, 2);
    }

    /**
     * Import data from JSON
     * @param {string} jsonString - JSON data
     * @returns {boolean}
     */
    importData(jsonString) {
        try {
            const data = JSON.parse(jsonString);

            // Import layouts
            if (data.layouts && Array.isArray(data.layouts)) {
                data.layouts.forEach(({ path, data: layoutData }) => {
                    const key = `${this.config.storageKeys.layout}${path}`;
                    this.save(key, layoutData);
                });
            }

            // Import settings
            if (data.heightMode) {
                this.save(this.config.storageKeys.heightMode, data.heightMode);
            }
            if (data.containerWidth) {
                this.save(this.config.storageKeys.containerWidth, data.containerWidth);
            }
            if (data.categoryState) {
                this.save(this.config.storageKeys.categoryState, data.categoryState);
            }

            return true;
        } catch (err) {
            this.utils.logError('LocalStorageHandler.importData', err);
            return false;
        }
    }

    /**
     * Get storage usage info
     * @returns {{used: number, total: number, percentage: number}}
     */
    getStorageInfo() {
        try {
            let used = 0;

            for (let i = 0; i < localStorage.length; i++) {
                const key = localStorage.key(i);
                const value = localStorage.getItem(key);
                if (key && value) {
                    used += key.length + value.length;
                }
            }

            // Assume ~5MB limit
            const total = 5 * 1024 * 1024;

            return {
                used,
                total,
                percentage: Math.round((used / total) * 100)
            };
        } catch (err) {
            return { used: 0, total: 0, percentage: 0 };
        }
    }
}

window.FlutePageEdit.register('LocalStorageHandler', LocalStorageHandler);
