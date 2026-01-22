/**
 * Page Editor - Main class that orchestrates all modules
 */
class PageEditor {
    constructor(options = {}) {
        this.config = new window.FlutePageEdit.Config(options);
        this.eventBus = window.FlutePageEdit.eventBus;
        this.utils = window.FlutePageEdit.utils;

        // State
        this.hasUnsavedChanges = false;
        this.isProcessing = false;
        this.skipHtmxConfirmation = false;
        this._handlers = {
            beforeUnload: (e) => this.handleBeforeUnload(e),
            htmxAfterSwap: (e) => {
                if (e.detail.target?.id === 'main') {
                    this.initializeElements();
                    this.setupEventListeners();
                    this.setupFabMenu();
                }
            }
        };

        // Initialize DOM elements
        this.elements = {};
        this.initializeElements();

        // Initialize modules
        this.initializeModules();

        // Setup event listeners
        this.setupEventListeners();
        this.setupHtmxListeners();

        // Setup FAB menu
        this.setupFabMenu();

        // Attempt recovery from localStorage
        this.attemptRecoveryFromLocalStorage();

        // Mark as ready
        this.eventBus.emit(window.FlutePageEdit.events.EDITOR_READY, { editor: this });
    }

    /**
     * Initialize DOM elements
     */
    initializeElements() {
        Object.entries(this.config.selectors).forEach(([key, selector]) => {
            this.elements[key] = document.querySelector(selector);
        });
    }

    bindOnce(el, type, handler, key = 'default', options) {
        if (!el) return;
        el._pe = el._pe || {};
        const mark = `page-editor:${type}:${key}`;
        if (el._pe[mark]) return;
        el.addEventListener(type, handler, options);
        el._pe[mark] = true;
    }

    /**
     * Initialize all modules
     */
    initializeModules() {
        const HistoryManager = window.FlutePageEdit.get('HistoryManager');
        const OnboardingManager = window.FlutePageEdit.get('OnboardingManager');
        const SidebarManager = window.FlutePageEdit.get('SidebarManager');
        const GridController = window.FlutePageEdit.get('GridController');
        const WidgetLoader = window.FlutePageEdit.get('WidgetLoader');
        const WidgetToolbar = window.FlutePageEdit.get('WidgetToolbar');
        const SearchHandler = window.FlutePageEdit.get('SearchHandler');
        const CategoryAccordion = window.FlutePageEdit.get('CategoryAccordion');
        const KeyboardHandler = window.FlutePageEdit.get('KeyboardHandler');
        const LocalStorageHandler = window.FlutePageEdit.get('LocalStorageHandler');
        const LayoutAPI = window.FlutePageEdit.get('LayoutAPI');

        // Create instances
        this.history = new HistoryManager(this);
        this.onboarding = new OnboardingManager(this);
        this.sidebarManager = new SidebarManager(this);
        this.gridController = new GridController(this);
        this.widgetLoader = new WidgetLoader(this);
        this.widgetToolbar = new WidgetToolbar(this);
        this.searchHandler = new SearchHandler(this);
        this.categoryAccordion = new CategoryAccordion(this);
        this.keyboardHandler = new KeyboardHandler(this);
        this.localStorage = new LocalStorageHandler(this);
        this.layoutAPI = new LayoutAPI(this);

        // Expose widgetButtonsCache for compatibility
        this.widgetButtonsCache = this.widgetLoader.widgetButtonsCache;
    }

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        this.initializeElements();

        // Main buttons
        this.bindOnce(this.elements.editBtn, 'click', () => this.enable(), 'edit-btn');
        this.bindOnce(this.elements.cancelBtn, 'click', () => this.disable(), 'cancel-btn');
        this.bindOnce(this.elements.resetBtn, 'click', () => this.resetLayout(), 'reset-btn');
        this.bindOnce(this.elements.undoBtn, 'click', () => this.history.undo(), 'undo-btn');
        this.bindOnce(this.elements.redoBtn, 'click', () => this.history.redo(), 'redo-btn');
        this.bindOnce(this.elements.saveBtn, 'click', () => this.saveLayout(), 'save-btn');
        this.bindOnce(this.elements.autoPositionBtn, 'click', () => this.autoPositionGrid(), 'auto-position-btn');

        // Container width toggle
        const containerWidthToggle = document.getElementById('container-width-checkbox');
        if (containerWidthToggle) {
            const savedMode = window.localStorage.getItem('container-width-mode') || 'container';
            const isFullWidth = savedMode === 'fullwidth';
            containerWidthToggle.checked = isFullWidth;
            this.applyContainerWidth(isFullWidth);

            this.bindOnce(containerWidthToggle, 'change', (e) => {
                const isFullWidth = e.target.checked;
                this.applyContainerWidth(isFullWidth);
                window.localStorage.setItem('container-width-mode', isFullWidth ? 'fullwidth' : 'container');
            }, 'container-width');
        }

        // SEO button
        this.bindOnce(this.elements.seoBtn, 'click', () => {
            if (typeof app !== 'undefined' && app.dropdowns) {
                app.dropdowns.closeAllDropdowns();
            }
        }, 'seo-btn');

        // Window events
        this.bindOnce(window, 'beforeunload', this._handlers.beforeUnload, 'beforeunload');

        // HTMX page navigation
        this.bindOnce(document, 'htmx:afterSwap', this._handlers.htmxAfterSwap, 'after-swap');
    }

    /**
     * Setup HTMX-specific listeners
     */
    setupHtmxListeners() {
        if (this._htmxListenersAttached) return;

        htmx.on('htmx:afterSwap', (evt) => this.handleHtmxAfterSwap(evt));
        htmx.on('htmx:beforeRequest', (evt) => this.handleHtmxBeforeRequest(evt));

        // CSRF header
        htmx.on('htmx:configRequest', (evt) => {
            const token = this.utils.getCsrfToken();
            if (token) evt.detail.headers['X-CSRF-Token'] = token;
        });

        // Response error handling
        htmx.on('htmx:responseError', (evt) => {
            this.utils.logError('HTMX response error', {
                status: evt.detail.xhr.status,
                url: evt.detail.requestConfig?.url,
                target: evt.detail.target?.id
            });

            if (evt.detail.target?.id === 'page-edit-dialog-content') {
                evt.detail.target.innerHTML = `
                    <div class="alert alert-danger">
                        ${this.config.translations.errorLoading || 'Error loading content'}
                    </div>
                `;
            }
        });

        // Widget settings save handling
        htmx.on('htmx:afterRequest', (evt) => {
            const elt = evt.detail.elt;
            if (elt?.id === 'widget-settings-save-btn') {
                this.handleWidgetSettingsSave(evt);
            }
        });

        this._htmxListenersAttached = true;
    }

    /**
     * Setup FAB menu
     */
    setupFabMenu() {
        const fab = this.elements.pageEditFab;
        const trigger = this.elements.fabTrigger;
        const backdrop = this.elements.fabBackdrop;

        if (!fab || !trigger) return;

        this.bindOnce(trigger, 'click', (e) => {
            e.stopPropagation();
            this.toggleFabMenu();
        }, 'fab-trigger');

        if (backdrop) {
            this.bindOnce(backdrop, 'click', () => this.closeFabMenu(), 'fab-backdrop');
        }

        this.bindOnce(document, 'keydown', (e) => {
            const currentFab = this.elements.pageEditFab;
            if (e.key === 'Escape' && currentFab?.classList.contains('open')) {
                this.closeFabMenu();
            }
        }, 'fab-escape');

        const menuItems = fab.querySelectorAll('.page-edit-fab__item');
        menuItems.forEach(item => {
            this.bindOnce(item, 'click', () => this.closeFabMenu(), 'fab-close');
        });
    }

    toggleFabMenu() {
        const fab = this.elements.pageEditFab;
        if (!fab) return;
        fab.classList.toggle('open');
    }

    openFabMenu() {
        this.elements.pageEditFab?.classList.add('open');
    }

    closeFabMenu() {
        this.elements.pageEditFab?.classList.remove('open');
    }

    /**
     * Enable edit mode
     */
    enable() {
        if (this.isProcessing) return;
        this.isProcessing = true;

        try {
            // Show onboarding if needed
            this.onboarding.initialize();

            // Add edit mode class
            document.body.classList.add('page-edit-mode');

            // Show sidebar and navbar
            this.elements.widgetsSidebar?.classList.add('active');
            this.elements.navbar?.classList.add('active');
            this.elements.pageEditBtn?.classList.add('hide');
            this.elements.pageEditFab?.classList.add('hide');
            this.closeFabMenu();

            // Create grid container
            const mainElement = document.getElementById('main');
            if (!mainElement) {
                throw new Error('Main element not found');
            }

            mainElement.innerHTML = `
                <div class="container">
                    <div class="row">
                        <div class="col-md-12">
                            <div id="widget-grid"></div>
                        </div>
                    </div>
                </div>
            `;

            // Initialize grid
            this.initializeGrid();

            // Initialize sidebar components
            this.sidebarManager.initialize();
            this.searchHandler.initialize();
            this.categoryAccordion.initialize();
            this.keyboardHandler.initialize();

            // Open sidebar
            this.sidebarManager.open();

            // Focus search after animation
            setTimeout(() => {
                this.searchHandler.focus();
                this.isProcessing = false;
            }, this.config.animationDuration + 100);

            if (typeof app !== 'undefined' && app.dropdowns) {
                app.dropdowns.closeAllDropdowns();
            }

            this.eventBus.emit(window.FlutePageEdit.events.EDITOR_ENABLED);

        } catch (err) {
            this.isProcessing = false;
            this.utils.logError('enable', err);
            this.disable(true);
        }
    }

    /**
     * Disable edit mode
     * @param {boolean} ignoreHtmx - Whether to skip HTMX page refresh
     */
    disable(ignoreHtmx = false) {
        if (this.isProcessing) return;

        if (this.hasUnsavedChanges) {
            if (typeof app !== 'undefined' && app.confirmations) {
                app.confirmations.showConfirmDialog({
                    message: this.config.translations.unsavedChanges,
                    type: 'warning',
                    onConfirm: () => this.performDisable(ignoreHtmx)
                });
            } else if (confirm(this.config.translations.unsavedChanges)) {
                this.performDisable(ignoreHtmx);
            }
            return;
        }

        this.performDisable(ignoreHtmx);
    }

    /**
     * Actually disable edit mode
     * @param {boolean} ignoreHtmx - Whether to skip HTMX page refresh
     */
    performDisable(ignoreHtmx = false) {
        this.isProcessing = true;

        document.body.classList.remove('page-edit-mode');
        this.elements.widgetsSidebar?.classList.remove('active');
        this.elements.navbar?.classList.remove('active');
        this.elements.pageEditBtn?.classList.remove('hide');
        this.elements.pageEditFab?.classList.remove('hide');

        // Close sidebar
        this.sidebarManager.close();

        // Destroy grid
        this.destroyGrid(ignoreHtmx);

        if (typeof app !== 'undefined' && app.dropdowns) {
            app.dropdowns.closeAllDropdowns();
        }

        setTimeout(() => {
            this.isProcessing = false;
        }, this.config.animationDuration);

        this.eventBus.emit(window.FlutePageEdit.events.EDITOR_DISABLED);
    }

    /**
     * Initialize grid
     */
    initializeGrid() {
        this.grid = this.gridController.initialize();

        if (!this.grid) {
            throw new Error('Failed to initialize grid');
        }

        // Load layout
        this.localStorage.initialize();
        const savedLayout = this.localStorage.loadLayout();

        if (savedLayout) {
            this.layoutAPI.loadLayoutJson(savedLayout);
            this.hasUnsavedChanges = true;
            this.updateSaveButtonState();

            // Check for Content widget
            setTimeout(() => {
                const hasContentWidget = document.querySelector('#widget-grid [data-widget-name="Content"]');
                if (!hasContentWidget && this.utils.getCurrentPath() !== '/') {
                    this.addContentWidget();
                }
            }, 500);
        } else {
            this.fetchLayoutFromServer();
        }
    }

    /**
     * Destroy grid
     * @param {boolean} ignoreHtmx - Whether to skip HTMX page refresh
     */
    destroyGrid(ignoreHtmx = false) {
        this.gridController.destroy();
        this.grid = null;
        this.history.clear();
        this.hasUnsavedChanges = false;
        this.updateUndoRedoButtons();
        this.updateSaveButtonState();

        this.localStorage.clearLayout();

        if (!ignoreHtmx) {
            this.layoutAPI.refreshPageContent();
        }
    }

    /**
     * Fetch layout from server
     */
    async fetchLayoutFromServer() {
        const layout = await this.layoutAPI.fetchLayout();
        if (layout) {
            this.layoutAPI.loadLayoutJson(layout);
            this.hasUnsavedChanges = false;
            this.updateSaveButtonState();
        }
    }

    /**
     * Save layout
     */
    async saveLayout() {
        if (!this.grid || this.layoutAPI.isSavingLayout()) return;

        if (!this.hasUnsavedChanges) {
            this.disable();
            return;
        }

        // Update save button state
        if (this.elements.saveBtn) {
            const btn = this.elements.saveBtn;
            btn.classList.add('saving');
            btn.disabled = true;
            if (!btn.getAttribute('data-original-text')) {
                btn.setAttribute('data-original-text', btn.innerHTML);
            }
            btn.innerHTML = `<span class="btn-spinner" aria-hidden="true"></span><span class="btn-text">${
                btn.getAttribute('data-saving-text') || (typeof translate === 'function' ? translate('def.save') : 'Save')
            }</span>`;
            btn.setAttribute('aria-busy', 'true');
        }

        const layoutData = this.layoutAPI.getLayoutJson();
        const success = await this.layoutAPI.saveLayout(layoutData);

        if (success) {
            this.hasUnsavedChanges = false;
            this.updateSaveButtonState();
            this.history.clear();
            this.updateUndoRedoButtons();
            this.localStorage.clearLayout();

            if (typeof notyf !== 'undefined') {
                notyf.success(typeof translate === 'function' ? translate('page.saved_successfully') : 'Saved successfully');
            }

            this.layoutAPI.refreshPageContent();
        } else {
            if (typeof notyf !== 'undefined') {
                notyf.error(typeof translate === 'function' ? translate('page.error_saving') : 'Error saving');
            }
        }

        // Reset save button
        if (this.elements.saveBtn) {
            const btn = this.elements.saveBtn;
            btn.classList.remove('saving');
            btn.disabled = false;
            btn.innerHTML = btn.getAttribute('data-original-text') || 'Save';
            btn.removeAttribute('aria-busy');
        }

        if (success) {
            this.disable();
        }
    }

    /**
     * Save to localStorage
     */
    saveToLocalStorage() {
        const layoutData = this.layoutAPI.getLayoutJson();
        this.localStorage.saveLayout(layoutData);
    }

    /**
     * Reset layout
     */
    resetLayout() {
        const confirmMessage = this.config.translations.resetConfirm;

        const doReset = () => {
            const items = Array.from(this.gridController.getItems());
            items.forEach(item => {
                if (item.getAttribute('data-widget-name') !== 'Content') {
                    this.gridController.removeWidget(item);
                }
            });
            this.gridController.onGridChange();
        };

        if (typeof app !== 'undefined' && app.confirmations) {
            app.confirmations.showConfirmDialog({
                message: confirmMessage,
                type: 'warning',
                onConfirm: doReset
            });
        } else if (confirm(confirmMessage)) {
            doReset();
        }
    }

    /**
     * Auto-position grid (compact widgets)
     */
    autoPositionGrid() {
        this.gridController.compact();
    }

    /**
     * Add Content widget
     */
    addContentWidget() {
        if (!this.grid) return;
        if (this.utils.getCurrentPath() === '/') return;

        const existingContentWidget = document.querySelector('#widget-grid [data-widget-name="Content"]');
        if (existingContentWidget) return;

        try {
            const div = document.createElement('div');
            div.classList.add('widget-item');
            div.draggable = true;
            div.setAttribute('data-widget-name', 'Content');
            div.setAttribute('data-widget-id', 'content-widget');
            div.setAttribute('data-system-widget', 'true');
            div.dataset.width = '12';
            div.dataset.widgetSettings = '{}';

            const content = document.createElement('div');
            content.classList.add('widget-content');
            content.innerHTML = this.utils.createSkeleton();
            div.appendChild(content);

            const gridEl = document.getElementById('widget-grid');
            if (gridEl) {
                gridEl.appendChild(div);
            }

            this.widgetLoader.initializeWidget(div, content);
        } catch (err) {
            this.utils.logError('addContentWidget', err);
        }
    }

    /**
     * Add toolbar to widget
     */
    addToolbar(widgetEl, buttons) {
        this.widgetToolbar.addToolbar(widgetEl, buttons);
    }

    /**
     * Open widget settings
     * @param {Element} widgetEl - Widget element
     */
    async openWidgetSettings(widgetEl) {
        const widgetName = widgetEl.getAttribute('data-widget-name');
        if (!widgetName) return;

        window.currentEditedWidgetEl = widgetEl;

        const rightSidebar = document.getElementById('page-edit-dialog');
        const sidebarContent = document.getElementById('page-edit-dialog-content');

        if (!rightSidebar || !sidebarContent) {
            this.utils.logError('openWidgetSettings', 'Dialog elements not found');
            return;
        }

        if (!this.rightSidebarDialog) {
            this.rightSidebarDialog = new A11yDialog(rightSidebar);
            this.rightSidebarDialog.on('hide', () => {
                window.currentEditedWidgetEl = null;
            });
        }

        // Show loading skeleton
        sidebarContent.innerHTML = `
            <div class="widget-settings-loading skeleton page-edit-skeleton widget-setting-loading"></div>
            <div class="widget-settings-loading skeleton page-edit-skeleton widget-setting-loading"></div>
            <div class="widget-settings-loading skeleton page-edit-skeleton widget-setting-loading"></div>
        `;

        this.rightSidebarDialog.show();

        try {
            const response = await this.utils.csrfFetch(u('api/pages/widgets/settings-form'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    widget_name: widgetName,
                    settings: widgetEl.dataset.widgetSettings || '{}'
                })
            });

            if (!response.ok) throw new Error('Failed to load settings form');

            const html = await response.text();

            sidebarContent.style.transition = 'opacity 0.15s ease-in-out';
            sidebarContent.style.opacity = '0';

            setTimeout(() => {
                sidebarContent.innerHTML = html;

                // Setup save button
                const saveBtn = document.getElementById('widget-settings-save-btn');
                if (saveBtn) {
                    const settingsUrl = u('api/pages/widgets/save-settings');
                    saveBtn.setAttribute('hx-post', settingsUrl);

                    const csrfToken = this.utils.getCsrfToken();
                    if (csrfToken) {
                        saveBtn.setAttribute('hx-headers', JSON.stringify({ 'X-CSRF-Token': csrfToken }));
                    }
                    saveBtn.setAttribute('hx-vals', JSON.stringify({ widget_name: widgetName }));
                    htmx.process(saveBtn);
                }

                htmx.process(sidebarContent);

                if (window.FluteSelect) {
                    window.FluteSelect.init();
                }

                this.eventBus.emit(window.FlutePageEdit.events.WIDGET_SETTINGS_LOADED, {
                    widgetName,
                    widgetElement: widgetEl,
                    settingsContainer: sidebarContent
                });

                sidebarContent.style.opacity = '1';
            }, 150);

        } catch (err) {
            this.utils.logError('openWidgetSettings', err);
            sidebarContent.innerHTML = `<div class="alert alert-danger">${this.config.translations.errorLoading}</div>`;
        }
    }

    /**
     * Handle widget settings save response
     */
    handleWidgetSettingsSave(evt) {
        let json;
        try {
            json = JSON.parse(evt.detail.xhr.response);
        } catch {
            // HTML response (validation errors)
            const sidebarContent = document.getElementById('page-edit-dialog-content');
            if (sidebarContent && evt.detail.xhr?.response) {
                sidebarContent.innerHTML = evt.detail.xhr.response;
                try { htmx.process(sidebarContent); } catch {}
                if (window.FluteSelect) {
                    window.FluteSelect.init();
                }
            }
            return;
        }

        if (json.success && json.html && json.settings && window.currentEditedWidgetEl) {
            const content = window.currentEditedWidgetEl.querySelector('.widget-content') ||
                           window.currentEditedWidgetEl.querySelector('.grid-stack-item-content');
            if (content) {
                content.innerHTML = json.html;
            }
            window.currentEditedWidgetEl.dataset.widgetSettings = JSON.stringify(json.settings);
            this.history.push();
            this.saveToLocalStorage();
            if (this.rightSidebarDialog) {
                this.rightSidebarDialog.hide();
            }
        }
    }

    /**
     * Handle HTMX after swap
     */
    handleHtmxAfterSwap(evt) {
        if (
            evt.detail.requestConfig?.url === window.location.href &&
            evt.detail.target?.id === 'main'
        ) {
            this.localStorage.initialize();
            this.elements.widgetGrid = document.getElementById('widget-grid');
            if (
                document.body.classList.contains('page-edit-mode') &&
                this.elements.widgetGrid &&
                !this.grid
            ) {
                this.initializeGrid();
            }
        }
    }

    /**
     * Handle HTMX before request
     */
    handleHtmxBeforeRequest(evt) {
        if (
            evt.detail.target?.id === 'main' &&
            document.body.classList.contains('page-edit-mode')
        ) {
            if (this.hasUnsavedChanges && !this.skipHtmxConfirmation) {
                evt.preventDefault();

                const triggerElement = evt.detail.elt;

                if (typeof app !== 'undefined' && app.confirmations) {
                    app.confirmations.showConfirmDialog({
                        message: this.config.translations.unsavedChanges,
                        type: 'warning',
                        onConfirm: () => {
                            this.skipHtmxConfirmation = true;
                            this.performDisable(true);

                            if (triggerElement) {
                                htmx.trigger(triggerElement, 'click');
                            }

                            setTimeout(() => {
                                this.skipHtmxConfirmation = false;
                            }, 100);
                        }
                    });
                }
                return;
            }
            this.skipHtmxConfirmation = false;
            this.performDisable(true);
        }
    }

    /**
     * Handle before unload
     */
    handleBeforeUnload(e) {
        if (this.hasUnsavedChanges) {
            e.preventDefault();
            e.returnValue = '';
        }
    }

    /**
     * Update undo/redo buttons
     */
    updateUndoRedoButtons() {
        if (this.elements.undoBtn) {
            this.elements.undoBtn.disabled = !this.history.canUndo();
        }
        if (this.elements.redoBtn) {
            this.elements.redoBtn.disabled = !this.history.canRedo();
        }
    }

    /**
     * Update save button state
     */
    updateSaveButtonState() {
        if (this.elements.saveBtn) {
            this.elements.saveBtn.disabled = !this.hasUnsavedChanges;
        }
    }

    /**
     * Apply container width mode
     */
    applyContainerWidth(isFullWidth) {
        const containers = document.querySelectorAll('.container');

        document.documentElement.setAttribute(
            'data-container-width',
            isFullWidth ? 'fullwidth' : 'container'
        );

        containers.forEach(container => {
            if (!container.classList.contains('keep-container')) {
                container.classList.toggle('container-fullwidth', isFullWidth);
            }
        });

        this.eventBus.emit(window.FlutePageEdit.events.CONTAINER_WIDTH_CHANGED, { isFullWidth });
    }

    /**
     * Attempt recovery from localStorage
     */
    attemptRecoveryFromLocalStorage() {
        try {
            this.localStorage.initialize();
            const savedLayout = this.localStorage.hasSavedLayout();

            if (savedLayout && document.body.classList.contains('page-edit-mode')) {
                console.info('Attempting to recover layout from localStorage');
            }
        } catch (err) {
            this.utils.logError('attemptRecoveryFromLocalStorage', err);
        }
    }

    /**
     * Handle page load (for HTMX navigation)
     */
    handlePageLoad() {
        if (this.history) {
            this.history.clear();
            this.updateUndoRedoButtons();
        }
        this.localStorage.initialize();
    }
}

/**
 * Initialize page editor
 */
function initializePageEditor() {
    const editorElements = document.querySelectorAll(
        '#page-change-button, .page-edit-navbar, .page-edit-sidebar'
    );

    editorElements.forEach(el => {
        if (el) el.removeAttribute('style');
    });

    try {
        if (!window.pageEditor) {
            console.info('Initializing page editor v2.0');

            window.pageEditor = new PageEditor({
                gridOptions: {
                    margin: 10,
                    acceptWidgets: true,
                    sizeToContent: false,
                    disableDrag: false,
                    disableResize: false,
                    animate: true,
                    cellHeight: 100,
                    column: 12,
                    float: true
                }
            });
        } else {
            window.pageEditor.handlePageLoad();
        }

        window.toggleEditMode = (enable, ignoreHtmx = false) => {
            if (!window.pageEditor) {
                console.error('Page editor not initialized');
                return;
            }
            enable ? window.pageEditor.enable() : window.pageEditor.disable(ignoreHtmx);
        };
    } catch (err) {
        console.error('Failed to initialize page editor:', err);
        window.toggleEditMode = () => {
            alert('Page editor failed to initialize. Please refresh the page.');
        };
    }

    // Check for unsaved changes
    try {
        const currentPath = window.location.pathname || '/';
        const localStorageKey = `page-layout-${currentPath}`;
        const hasUnsavedChanges = localStorage.getItem(localStorageKey);

        if (hasUnsavedChanges) {
            console.info('Unsaved changes found for path:', currentPath);
        }
    } catch (err) {
        console.error('Error checking for unsaved changes:', err);
    }
}

// Initialize on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializePageEditor);
} else {
    initializePageEditor();
}

// Re-initialize after HTMX navigation
window.addEventListener('htmx:afterSwap', () => {
    setTimeout(initializePageEditor, 50);
});

// Global error handler
document.addEventListener('htmx:responseError', (evt) => {
    console.error('HTMX response error:', evt.detail.error);
});

// Helper function for container width mode
window.getContainerWidthMode = function() {
    const toggle = document.getElementById('container-width-checkbox');
    return toggle && toggle.checked ? 'fullwidth' : 'container';
};

// Register main class
window.FlutePageEdit.register('PageEditor', PageEditor);
window.FlutePageEdit.initializePageEditor = initializePageEditor;
