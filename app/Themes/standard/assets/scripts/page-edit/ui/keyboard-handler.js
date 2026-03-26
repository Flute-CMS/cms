/**
 * Keyboard Handler - manages keyboard shortcuts
 */
class KeyboardHandler {
    constructor(editor) {
        this.editor = editor;
        this.config = editor.config;
        this.eventBus = window.FlutePageEdit.eventBus;
        this.utils = window.FlutePageEdit.utils;

        this.isEditorFocused = false;
        this.enabled = true;
        this._focusInHandler = (e) => {
            this.isEditorFocused = e.target.matches(
                'input, textarea, [contenteditable="true"], select'
            );
        };
        this._focusOutHandler = () => {
            this.isEditorFocused = false;
        };
        this._keydownHandler = (e) => this.handleKeyDown(e);
    }

    bindOnce(el, type, handler, key = 'default', options) {
        if (!el) return;
        el._pe = el._pe || {};
        const mark = `keyboard-handler:${type}:${key}`;
        if (el._pe[mark]) return;
        el.addEventListener(type, handler, options);
        el._pe[mark] = true;
    }

    /**
     * Initialize keyboard handler
     */
    initialize() {
        this.setupEventListeners();
    }

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Track if user is focused on an editable element
        this.bindOnce(document, 'focusin', this._focusInHandler, 'focusin');
        this.bindOnce(document, 'focusout', this._focusOutHandler, 'focusout');

        // Main keyboard handler
        this.bindOnce(document, 'keydown', this._keydownHandler, 'keydown');
    }

    /**
     * Handle keydown event
     * @param {KeyboardEvent} e - Keyboard event
     */
    handleKeyDown(e) {
        // Skip if disabled or user is typing
        if (!this.enabled || this.isEditorFocused) return;

        // Only handle shortcuts when in edit mode
        if (!document.body.classList.contains('page-edit-mode')) return;

        const { shortcuts } = this.config;

        for (const [action, shortcut] of Object.entries(shortcuts)) {
            if (this.matchesShortcut(e, shortcut)) {
                e.preventDefault();
                this.executeAction(action);
                return;
            }
        }
    }

    /**
     * Check if key event matches shortcut
     * @param {KeyboardEvent} e - Keyboard event
     * @param {object} shortcut - Shortcut definition
     * @returns {boolean}
     */
    matchesShortcut(e, shortcut) {
        const isCtrlPressed = shortcut.ctrl ? (e.ctrlKey || e.metaKey) : !e.ctrlKey && !e.metaKey;
        const isShiftPressed = shortcut.shift ? e.shiftKey : !e.shiftKey;
        const isAltPressed = shortcut.alt ? e.altKey : !e.altKey;
        const isKeyPressed = e.key.toLowerCase() === shortcut.key.toLowerCase();

        return isCtrlPressed && isShiftPressed && isAltPressed && isKeyPressed;
    }

    /**
     * Execute shortcut action
     * @param {string} action - Action name
     */
    executeAction(action) {
        switch (action) {
            case 'undo':
                this.editor.history?.undo();
                break;

            case 'redo':
                this.editor.history?.redo();
                break;

            case 'save':
                if (this.editor.hasUnsavedChanges) {
                    this.editor.saveLayout();
                }
                break;

            case 'escape':
                this.editor.disable();
                break;

            case 'search':
                this.editor.searchHandler?.focus();
                break;

            case 'compact':
                this.editor.gridController?.compact();
                break;

            default:
                // Custom action
                this.eventBus.emit('shortcutExecuted', { action });
        }
    }

    /**
     * Register a custom shortcut
     * @param {string} action - Action name
     * @param {object} shortcut - Shortcut definition {key, ctrl?, shift?, alt?}
     * @param {Function} handler - Handler function
     */
    registerShortcut(action, shortcut, handler) {
        this.config.shortcuts[action] = shortcut;

        // Listen for custom shortcut
        this.eventBus.on('shortcutExecuted', (data) => {
            if (data.action === action) {
                handler();
            }
        });
    }

    /**
     * Unregister a shortcut
     * @param {string} action - Action name
     */
    unregisterShortcut(action) {
        delete this.config.shortcuts[action];
    }

    /**
     * Get all registered shortcuts
     * @returns {object}
     */
    getShortcuts() {
        return { ...this.config.shortcuts };
    }

    /**
     * Format shortcut for display
     * @param {object} shortcut - Shortcut definition
     * @returns {string}
     */
    formatShortcut(shortcut) {
        const parts = [];

        if (shortcut.ctrl) {
            parts.push(navigator.platform.includes('Mac') ? '⌘' : 'Ctrl');
        }
        if (shortcut.shift) {
            parts.push('Shift');
        }
        if (shortcut.alt) {
            parts.push(navigator.platform.includes('Mac') ? '⌥' : 'Alt');
        }

        // Format key
        let key = shortcut.key;
        if (key === 'Escape') key = 'Esc';
        parts.push(key.toUpperCase());

        return parts.join('+');
    }

    /**
     * Get help text for all shortcuts
     * @returns {Array<{action: string, shortcut: string}>}
     */
    getShortcutsHelp() {
        return Object.entries(this.config.shortcuts).map(([action, shortcut]) => ({
            action,
            shortcut: this.formatShortcut(shortcut)
        }));
    }

    /**
     * Enable keyboard handling
     */
    enable() {
        this.enabled = true;
    }

    /**
     * Disable keyboard handling
     */
    disable() {
        this.enabled = false;
    }

    /**
     * Check if handler is enabled
     * @returns {boolean}
     */
    isEnabled() {
        return this.enabled;
    }
}

window.FlutePageEdit.register('KeyboardHandler', KeyboardHandler);
