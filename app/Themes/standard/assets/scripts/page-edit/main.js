/**
 * Page Editor — Main orchestrator.
 * Uses GridStack for widget grid layout.
 */
class PageEditor {
    constructor(options = {}) {
        this.config = new window.FlutePageEdit.Config(options);
        this.eventBus = window.FlutePageEdit.eventBus;
        this.utils = window.FlutePageEdit.utils;

        this.hasUnsavedChanges = false;
        this.isProcessing = false;
        this.skipHtmxConfirmation = false;
        this.scope = 'local';

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

        this.elements = {};
        this.initializeElements();
        this.initializeModules();
        this.setupEventListeners();
        this.setupHtmxListeners();
        this.setupFabMenu();
        this.setupSidebarPanel();
        this.attemptRecoveryFromLocalStorage();

        this.eventBus.emit(window.FlutePageEdit.events.EDITOR_READY, { editor: this });
    }

    initializeElements() {
        this.elements = {
            navbar: document.querySelector('#page-edit-nav') || document.querySelector('.pe-topbar'),
            undoBtn: document.getElementById('page-edit-undo'),
            redoBtn: document.getElementById('page-edit-redo'),
            saveBtn: document.getElementById('page-edit-save'),
            cancelBtn: document.getElementById('page-change-cancel'),
            resetBtn: document.getElementById('page-edit-reset'),
            autoPositionBtn: document.getElementById('page-edit-auto-position'),
            widgetsSidebar: document.getElementById('page-edit-sidebar'),
            sidebarClose: document.getElementById('pe-sidebar-close'),
            pageEditFab: document.getElementById('page-edit-fab'),
            fabTrigger: document.querySelector('.page-edit-fab__trigger'),
            fabBackdrop: document.getElementById('page-edit-backdrop'),
            editBtn: document.getElementById('page-change-button'),
            pageEditBtn: document.getElementById('page-change-button'),
            seoBtn: document.getElementById('page-change-seo'),
            widgetGrid: document.getElementById('widget-grid'),
        };

        if (this.config?.selectors) {
            Object.entries(this.config.selectors).forEach(([key, selector]) => {
                if (!this.elements[key]) {
                    this.elements[key] = document.querySelector(selector);
                }
            });
        }
    }

    bindOnce(el, type, handler, key = 'default', options) {
        if (!el) return;
        el._pe = el._pe || {};
        const mark = `page-editor:${type}:${key}`;
        if (el._pe[mark]) return;
        el.addEventListener(type, handler, options);
        el._pe[mark] = true;
    }

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

        this.widgetButtonsCache = this.widgetLoader.widgetButtonsCache;
    }

    setupEventListeners() {
        this.initializeElements();

        this.bindOnce(this.elements.editBtn, 'click', () => this.enable(), 'edit-btn');
        this.bindOnce(this.elements.cancelBtn, 'click', () => this.disable(), 'cancel-btn');
        this.bindOnce(this.elements.resetBtn, 'click', () => this.resetLayout(), 'reset-btn');
        this.bindOnce(this.elements.undoBtn, 'click', () => this.history.undo(), 'undo-btn');
        this.bindOnce(this.elements.redoBtn, 'click', () => this.history.redo(), 'redo-btn');
        this.bindOnce(this.elements.saveBtn, 'click', () => this.saveLayout(), 'save-btn');
        this.bindOnce(this.elements.autoPositionBtn, 'click', () => this.autoPositionGrid(), 'auto-position-btn');

        this.bindOnce(this.elements.sidebarClose, 'click', () => {
            this.elements.widgetsSidebar?.classList.remove('active');
        }, 'sidebar-close');

        const containerWidthToggle = document.getElementById('container-width-checkbox');
        if (containerWidthToggle) {
            const savedMode = window.localStorage.getItem('container-width-mode') || 'container';
            const isFullWidth = savedMode === 'fullwidth';
            containerWidthToggle.checked = isFullWidth;
            this.applyContainerWidth(isFullWidth);

            this.bindOnce(containerWidthToggle, 'change', (e) => {
                this.applyContainerWidth(e.target.checked);
                window.localStorage.setItem('container-width-mode', e.target.checked ? 'fullwidth' : 'container');
            }, 'container-width');
        }

        this.bindOnce(this.elements.seoBtn, 'click', () => {
            if (typeof app !== 'undefined' && app.dropdowns) app.dropdowns.closeAllDropdowns();
        }, 'seo-btn');

        this.bindOnce(window, 'beforeunload', this._handlers.beforeUnload, 'beforeunload');
        this.bindOnce(document, 'htmx:afterSwap', this._handlers.htmxAfterSwap, 'after-swap');

        this.setupScopeToggle();
    }

    setupSidebarPanel() {
        const sidebar = document.getElementById('page-edit-sidebar');
        if (!sidebar) return;

        // Support both old (.pe-sidebar__icon-btn) and new (.pe-dock__tab) selectors
        const tabBtns = sidebar.querySelectorAll('.pe-dock__tab, .pe-sidebar__icon-btn');
        const categoryPanels = sidebar.querySelectorAll('.pe-dock__category, .pe-sidebar__category');

        tabBtns.forEach(btn => {
            this.bindOnce(btn, 'click', () => {
                const cat = btn.dataset.category;
                tabBtns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                categoryPanels.forEach(p => p.classList.toggle('active', p.dataset.category === cat));

                const searchInput = sidebar.querySelector('#widget-search');
                if (searchInput) searchInput.value = '';
                this._resetSidebarSearch(sidebar);
            }, `cat-${btn.dataset.category}`);
        });

        const searchInput = sidebar.querySelector('#widget-search');
        const searchClear = sidebar.querySelector('.pe-dock__search-clear, .pe-sidebar__search-clear');
        const searchResults = sidebar.querySelector('.pe-dock__search-results, .pe-sidebar__search-results');
        const noResults = sidebar.querySelector('.pe-dock__no-results, .pe-sidebar__no-results');

        if (searchInput) {
            this.bindOnce(searchInput, 'input', () => {
                const q = searchInput.value.trim().toLowerCase();
                if (searchClear) searchClear.classList.toggle('visible', q.length > 0);

                if (!q) {
                    this._resetSidebarSearch(sidebar);
                    return;
                }

                categoryPanels.forEach(p => p.style.display = 'none');
                searchResults.style.display = 'block';

                const widgetsContainer = searchResults.querySelector('.pe-dock__widgets, .pe-sidebar__widgets');
                if (widgetsContainer) widgetsContainer.innerHTML = '';

                const allCards = sidebar.querySelectorAll('.pe-dock__category:not([data-category="all"]) .pe-widget-card, .pe-sidebar__category .pe-widget-card');
                let matchCount = 0;

                allCards.forEach(card => {
                    const name = card.querySelector('.pe-widget-card__name')?.textContent?.toLowerCase() || '';
                    if (name.includes(q)) {
                        if (widgetsContainer) widgetsContainer.appendChild(card.cloneNode(true));
                        matchCount++;
                    }
                });

                if (matchCount > 0) {
                    if (noResults) noResults.style.display = 'none';
                    if (widgetsContainer) {
                        widgetsContainer.querySelectorAll('.pe-widget-card').forEach(card => this._attachCardEvents(card));
                    }
                    // Re-register drag-in so cloned cards are draggable
                    if (this.gridController?.refreshDragIn) this.gridController.refreshDragIn();
                } else {
                    if (noResults) noResults.style.display = 'flex';
                }
            }, 'search-input');
        }

        if (searchClear) {
            this.bindOnce(searchClear, 'click', () => {
                searchInput.value = '';
                searchClear.classList.remove('visible');
                this._resetSidebarSearch(sidebar);
                searchInput.focus();
            }, 'search-clear');
        }

        // Click-to-add for all widget cards
        sidebar.querySelectorAll('.pe-widget-card').forEach(card => this._attachCardEvents(card));

        const overlay = document.querySelector('.pe-sidebar-overlay');
        if (overlay) {
            this.bindOnce(overlay, 'click', () => sidebar.classList.remove('active'), 'sidebar-overlay');
        }
    }

    _attachCardEvents(card) {
        // Click on the "+" button → add widget
        const addBtn = card.querySelector('.pe-widget-card__add');
        if (addBtn) {
            addBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                e.preventDefault();
                const name = card.dataset.widgetName;
                const width = parseInt(card.dataset.defaultWidth) || 6;
                if (this.gridController?.gsGrid) {
                    this.gridController.createWidget(name, width, null);
                }
            });
        }

        // Click on card → add widget
        card.addEventListener('click', (e) => {
            if (e.target.closest('.pe-widget-card__add')) return;
            const name = card.dataset.widgetName;
            const width = parseInt(card.dataset.defaultWidth) || 6;
            if (this.gridController?.gsGrid) {
                this.gridController.createWidget(name, width, null);
            }
        });
    }

    _resetSidebarSearch(sidebar) {
        const categoryPanels = sidebar.querySelectorAll('.pe-dock__category, .pe-sidebar__category');
        const searchResults = sidebar.querySelector('.pe-dock__search-results, .pe-sidebar__search-results');
        const noResults = sidebar.querySelector('.pe-dock__no-results, .pe-sidebar__no-results');
        const activeTab = sidebar.querySelector('.pe-dock__tab.active, .pe-sidebar__icon-btn.active');

        if (searchResults) searchResults.style.display = 'none';
        if (noResults) noResults.style.display = 'none';

        categoryPanels.forEach(p => {
            p.style.display = '';
            if (activeTab) p.classList.toggle('active', p.dataset.category === activeTab.dataset.category);
        });
    }

    setupScopeToggle() {
        const scopeToggle = document.getElementById('page-edit-scope-toggle');
        if (!scopeToggle) return;

        scopeToggle.querySelectorAll('.pe-scope-toggle__btn, .scope-btn').forEach(btn => {
            this.bindOnce(btn, 'click', async () => {
                const newScope = btn.dataset.scope;
                if (newScope === this.scope) return;

                if (this.hasUnsavedChanges) {
                    const confirmed = await this.confirmScopeSwitch();
                    if (!confirmed) return;
                }
                this.switchScope(newScope);
            }, `scope-${btn.dataset.scope}`);
        });
    }

    confirmScopeSwitch() {
        return new Promise((resolve) => {
            if (typeof app !== 'undefined' && app.confirmations) {
                app.confirmations.showConfirmDialog({
                    message: this.config.translations.unsavedChanges,
                    type: 'warning',
                    onConfirm: () => resolve(true),
                    onCancel: () => resolve(false)
                });
            } else if (confirm(this.config.translations.unsavedChanges)) {
                resolve(true);
            } else {
                resolve(false);
            }
        });
    }

    async switchScope(newScope) {
        if (this.isProcessing) return;
        this.isProcessing = true;

        try {
            const scopeToggle = document.getElementById('page-edit-scope-toggle');
            if (scopeToggle) {
                scopeToggle.querySelectorAll('.pe-scope-toggle__btn, .scope-btn').forEach(btn => {
                    btn.classList.toggle('active', btn.dataset.scope === newScope);
                });
            }

            this.scope = newScope;
            this.hasUnsavedChanges = false;
            this.history.clear();
            this.updateUndoRedoButtons();
            this.updateSaveButtonState();

            // Clear grid
            if (this.gridController?.gsGrid) {
                this.gridController.gsGrid.removeAll();
            }

            this.localStorage.initialize();

            const savedLayout = this.localStorage.loadLayout();
            if (savedLayout) {
                await this.layoutAPI.loadLayoutJson(savedLayout);
                this.hasUnsavedChanges = true;
                this.updateSaveButtonState();
            } else {
                const layout = await this.layoutAPI.fetchLayout();
                if (layout) {
                    await this.layoutAPI.loadLayoutJson(layout);
                }
            }

            this.gridController.updateEmptyState();
            this.refreshAllToolbars();
            this.eventBus.emit(window.FlutePageEdit.events.SCOPE_CHANGED, { scope: newScope });
        } catch (err) {
            this.utils.logError('switchScope', err);
        } finally {
            this.isProcessing = false;
        }
    }

    setupHtmxListeners() {
        if (this._htmxListenersAttached) return;

        htmx.on('htmx:afterSwap', (evt) => this.handleHtmxAfterSwap(evt));
        htmx.on('htmx:beforeRequest', (evt) => this.handleHtmxBeforeRequest(evt));

        htmx.on('htmx:configRequest', (evt) => {
            const token = this.utils.getCsrfToken();
            if (token) evt.detail.headers['X-CSRF-Token'] = token;
        });

        htmx.on('htmx:responseError', (evt) => {
            this.utils.logError('HTMX response error', {
                status: evt.detail.xhr.status,
                url: evt.detail.requestConfig?.url,
            });

            if (evt.detail.target?.id === 'page-edit-dialog-content') {
                evt.detail.target.innerHTML = `
                    <div class="alert alert-danger">
                        ${this.config.translations.errorLoading || 'Error loading content'}
                    </div>`;
            }
        });

        htmx.on('htmx:afterRequest', (evt) => {
            if (evt.detail.elt?.id === 'widget-settings-save-btn') {
                this.handleWidgetSettingsSave(evt);
            }
        });

        this._htmxListenersAttached = true;
    }

    setupFabMenu() {
        const fab = this.elements.pageEditFab;
        const trigger = this.elements.fabTrigger;
        const backdrop = this.elements.fabBackdrop;

        if (!fab || !trigger) return;

        this.bindOnce(trigger, 'click', (e) => { e.stopPropagation(); this.toggleFabMenu(); }, 'fab-trigger');
        if (backdrop) this.bindOnce(backdrop, 'click', () => this.closeFabMenu(), 'fab-backdrop');

        this.bindOnce(document, 'keydown', (e) => {
            if (e.key === 'Escape' && this.elements.pageEditFab?.classList.contains('open')) this.closeFabMenu();
        }, 'fab-escape');

        fab.querySelectorAll('.page-edit-fab__item').forEach(item => {
            this.bindOnce(item, 'click', () => this.closeFabMenu(), 'fab-close');
        });
    }

    toggleFabMenu() { this.elements.pageEditFab?.classList.toggle('open'); }
    openFabMenu()   { this.elements.pageEditFab?.classList.add('open'); }
    closeFabMenu()  { this.elements.pageEditFab?.classList.remove('open'); }

    enable() {
        if (this.isProcessing) return;
        this.isProcessing = true;

        try {
            this.onboarding.initialize();

            document.documentElement.classList.add('page-edit-active');
            document.body.classList.add('page-edit-mode');

            this.elements.widgetsSidebar?.classList.add('active');
            this.elements.navbar?.classList.add('active');
            this.elements.pageEditBtn?.classList.add('hide');
            this.elements.pageEditFab?.classList.add('hide');
            this.closeFabMenu();

            const mainElement = document.getElementById('main');
            if (!mainElement) throw new Error('Main element not found');

            mainElement.innerHTML = `
                <div class="container">
                    <div class="row">
                        <div class="col-md-12">
                            <div id="widget-grid" class="grid-stack"></div>
                        </div>
                    </div>
                </div>
            `;

            // Scroll to top immediately
            window.scrollTo({ top: 0, behavior: 'instant' });

            this.initializeGrid();

            this.sidebarManager.initialize();
            this.searchHandler.initialize();
            this.categoryAccordion.initialize();
            this.keyboardHandler.initialize();
            this.setupSidebarPanel();

            setTimeout(() => {
                this.isProcessing = false;
            }, this.config.animationDuration + 100);

            if (typeof app !== 'undefined' && app.dropdowns) app.dropdowns.closeAllDropdowns();

            this.eventBus.emit(window.FlutePageEdit.events.EDITOR_ENABLED);
        } catch (err) {
            this.isProcessing = false;
            this.utils.logError('enable', err);
            this.disable(true);
        }
    }

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

    performDisable(ignoreHtmx = false) {
        this.isProcessing = true;

        document.documentElement.classList.remove('page-edit-active');
        document.body.classList.remove('page-edit-mode');
        this.elements.widgetsSidebar?.classList.remove('active');
        this.elements.navbar?.classList.remove('active');
        this.elements.pageEditBtn?.classList.remove('hide');
        this.elements.pageEditFab?.classList.remove('hide');

        this.sidebarManager.close();
        this.destroyGrid(ignoreHtmx);

        if (typeof app !== 'undefined' && app.dropdowns) app.dropdowns.closeAllDropdowns();

        setTimeout(() => { this.isProcessing = false; }, this.config.animationDuration);
        this.eventBus.emit(window.FlutePageEdit.events.EDITOR_DISABLED);
    }

    initializeGrid() {
        this.grid = this.gridController.initialize();
        if (!this.grid) throw new Error('Failed to initialize grid');

        this.localStorage.initialize();
        const savedLayout = this.localStorage.loadLayout();

        if (savedLayout) {
            this.layoutAPI.loadLayoutJson(savedLayout);
            this.hasUnsavedChanges = true;
            this.updateSaveButtonState();

            setTimeout(() => {
                const hasContentWidget = document.querySelector('#widget-grid [data-widget-name="Content"]');
                if (!hasContentWidget && this.utils.getCurrentPath() !== '/') this.addContentWidget();
                this.gridController.updateEmptyState();
            }, 500);
        } else {
            this.fetchLayoutFromServer();
        }
    }

    destroyGrid(ignoreHtmx = false) {
        this.gridController.destroy();
        this.grid = null;
        this.history.clear();
        this.hasUnsavedChanges = false;
        this.updateUndoRedoButtons();
        this.updateSaveButtonState();
        this.localStorage.clearLayout();

        if (!ignoreHtmx) this.layoutAPI.refreshPageContent();
    }

    async fetchLayoutFromServer() {
        const layout = await this.layoutAPI.fetchLayout();
        if (layout) {
            await this.layoutAPI.loadLayoutJson(layout);
            this.hasUnsavedChanges = false;
            this.updateSaveButtonState();
        }

        setTimeout(() => this.gridController.updateEmptyState(), 300);
    }

    async saveLayout() {
        if (!this.grid || this.layoutAPI.isSavingLayout()) return;
        if (!this.hasUnsavedChanges) { this.disable(); return; }

        const saveBtn = this.elements.saveBtn;
        if (saveBtn) {
            saveBtn.classList.add('saving');
            saveBtn.disabled = true;
            if (!saveBtn.getAttribute('data-original-text')) saveBtn.setAttribute('data-original-text', saveBtn.innerHTML);
            saveBtn.innerHTML = `<span class="btn-spinner" aria-hidden="true"></span><span>${typeof translate === 'function' ? translate('def.save') : 'Save'}</span>`;
            saveBtn.setAttribute('aria-busy', 'true');
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

        if (saveBtn) {
            saveBtn.classList.remove('saving');
            saveBtn.disabled = false;
            saveBtn.innerHTML = saveBtn.getAttribute('data-original-text') || 'Save';
            saveBtn.removeAttribute('aria-busy');
        }

        if (success) this.disable();
    }

    saveToLocalStorage() {
        const layoutData = this.layoutAPI.getLayoutJson();
        this.localStorage.saveLayout(layoutData);
    }

    resetLayout() {
        const doReset = () => {
            const items = this.gridController.getItems();
            items.forEach(item => {
                if (item.getAttribute('data-widget-name') !== 'Content') {
                    this.gridController.removeWidget(item);
                }
            });
            this.gridController.onGridChange();
            this.gridController.updateEmptyState();
        };

        if (typeof app !== 'undefined' && app.confirmations) {
            app.confirmations.showConfirmDialog({
                message: this.config.translations.resetConfirm,
                type: 'warning',
                onConfirm: doReset
            });
        } else if (confirm(this.config.translations.resetConfirm)) {
            doReset();
        }
    }

    autoPositionGrid() {
        this.gridController.compact();
    }

    addContentWidget() {
        if (!this.grid) return;
        if (this.scope === 'local' && this.utils.getCurrentPath() === '/') return;

        const existing = document.querySelector('#widget-grid [data-widget-name="Content"]');
        if (existing) return;

        try {
            this.gridController.createWidget('Content', 12, null);
        } catch (err) {
            this.utils.logError('addContentWidget', err);
        }
    }

    addToolbar(widgetEl, buttons) {
        this.widgetToolbar.addToolbar(widgetEl, buttons);
    }

    async openWidgetSettings(widgetEl) {
        const widgetName = widgetEl.getAttribute('data-widget-name');
        if (!widgetName) return;

        window.currentEditedWidgetEl = widgetEl;

        const rightSidebar = document.getElementById('page-edit-dialog');
        const sidebarContent = document.getElementById('page-edit-dialog-content');
        if (!rightSidebar || !sidebarContent) return;

        if (!this.rightSidebarDialog) {
            this.rightSidebarDialog = new A11yDialog(rightSidebar);
            this.rightSidebarDialog.on('hide', () => { window.currentEditedWidgetEl = null; });
        }

        sidebarContent.innerHTML = `
            <div class="widget-settings-loading skeleton page-edit-skeleton widget-setting-loading"></div>
            <div class="widget-settings-loading skeleton page-edit-skeleton widget-setting-loading"></div>
        `;
        this.rightSidebarDialog.show();

        try {
            const response = await this.utils.csrfFetch(u('api/pages/widgets/settings-form'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ widget_name: widgetName, settings: widgetEl.dataset.widgetSettings || '{}' })
            });

            if (!response.ok) throw new Error('Failed to load settings form');
            const html = await response.text();

            sidebarContent.style.transition = 'opacity 0.15s ease-in-out';
            sidebarContent.style.opacity = '0';

            setTimeout(() => {
                sidebarContent.innerHTML = html;

                const saveBtn = document.getElementById('widget-settings-save-btn');
                if (saveBtn) {
                    saveBtn.setAttribute('hx-post', u('api/pages/widgets/save-settings'));
                    const csrfToken = this.utils.getCsrfToken();
                    if (csrfToken) saveBtn.setAttribute('hx-headers', JSON.stringify({ 'X-CSRF-Token': csrfToken }));
                    saveBtn.setAttribute('hx-vals', JSON.stringify({ widget_name: widgetName }));
                    htmx.process(saveBtn);
                }

                htmx.process(sidebarContent);
                if (window.FluteSelect) window.FluteSelect.init();

                this.eventBus.emit(window.FlutePageEdit.events.WIDGET_SETTINGS_LOADED, {
                    widgetName, widgetElement: widgetEl, settingsContainer: sidebarContent
                });

                sidebarContent.style.opacity = '1';
            }, 150);
        } catch (err) {
            this.utils.logError('openWidgetSettings', err);
            sidebarContent.innerHTML = `<div class="alert alert-danger">${this.config.translations.errorLoading}</div>`;
        }
    }

    handleWidgetSettingsSave(evt) {
        let json;
        try {
            json = JSON.parse(evt.detail.xhr.response);
        } catch {
            const sidebarContent = document.getElementById('page-edit-dialog-content');
            if (sidebarContent && evt.detail.xhr?.response) {
                sidebarContent.innerHTML = evt.detail.xhr.response;
                try { htmx.process(sidebarContent); } catch {}
                if (window.FluteSelect) window.FluteSelect.init();
            }
            return;
        }

        if (json.success && json.html && json.settings && window.currentEditedWidgetEl) {
            const content = window.currentEditedWidgetEl.querySelector('.widget-content');
            if (content) content.innerHTML = json.html;
            window.currentEditedWidgetEl.dataset.widgetSettings = JSON.stringify(json.settings);

            // Resize widget to fit new content after settings change
            this.widgetLoader._resizeWidgetToContent(window.currentEditedWidgetEl);

            this.hasUnsavedChanges = true;
            this.updateSaveButtonState();
            this.history.push();
            this.saveToLocalStorage();
            if (this.rightSidebarDialog) this.rightSidebarDialog.hide();
        }
    }

    handleHtmxAfterSwap(evt) {
        if (evt.detail.requestConfig?.url === window.location.href && evt.detail.target?.id === 'main') {
            this.localStorage.initialize();
            this.elements.widgetGrid = document.getElementById('widget-grid');
            if (document.body.classList.contains('page-edit-mode') && this.elements.widgetGrid && !this.grid) {
                this.initializeGrid();
            }
        }
    }

    handleHtmxBeforeRequest(evt) {
        if (evt.detail.target?.id === 'main' && document.body.classList.contains('page-edit-mode')) {
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
                            if (triggerElement) htmx.trigger(triggerElement, 'click');
                            setTimeout(() => { this.skipHtmxConfirmation = false; }, 100);
                        }
                    });
                }
                return;
            }
            this.skipHtmxConfirmation = false;
            this.performDisable(true);
        }
    }

    handleBeforeUnload(e) {
        if (this.hasUnsavedChanges) { e.preventDefault(); e.returnValue = ''; }
    }

    /**
     * Open excluded paths editor for a global widget.
     * Uses <template id="pe-excluded-paths-tpl"> from blade.
     */
    openExcludedPathsEditor(widgetEl) {
        const widgetName = widgetEl.getAttribute('data-widget-name');
        if (!widgetName || widgetName === 'Content') return;

        const tpl = document.getElementById('pe-excluded-paths-tpl');
        if (!tpl) return;

        window.currentExcludedPathsWidgetEl = widgetEl;

        const rightSidebar = document.getElementById('page-edit-dialog');
        const sidebarContent = document.getElementById('page-edit-dialog-content');
        if (!rightSidebar || !sidebarContent) return;

        if (!this.rightSidebarDialog) {
            this.rightSidebarDialog = new A11yDialog(rightSidebar);
            this.rightSidebarDialog.on('hide', () => { window.currentEditedWidgetEl = null; });
        }

        // Register per-session cleanup for excluded paths
        const onHide = () => {
            this._cleanupExcludedPaths();
            window.currentExcludedPathsWidgetEl = null;
            this.rightSidebarDialog.off('hide', onHide);
        };
        this.rightSidebarDialog.on('hide', onHide);

        // Clone template into sidebar content
        sidebarContent.innerHTML = '';
        sidebarContent.appendChild(tpl.content.cloneNode(true));

        // Fill current paths
        let currentPaths = [];
        try { currentPaths = JSON.parse(widgetEl.dataset.excludedPaths || '[]'); } catch (_) {}

        const list = sidebarContent.querySelector('.pe-excluded-paths__list');
        const tagTpl = sidebarContent.querySelector('.pe-excluded-paths__tag-tpl');

        currentPaths.forEach(p => this._addExcludedPathTag(list, tagTpl, p));

        // Setup add
        const input = sidebarContent.querySelector('.pe-excluded-paths__input');
        const addBtn = sidebarContent.querySelector('.pe-excluded-paths__add-btn');

        const addPath = () => {
            const val = input.value.trim();
            if (!val) return;
            const existing = this._getExcludedPaths(list);
            if (existing.includes(val)) { input.value = ''; return; }
            this._addExcludedPathTag(list, tagTpl, val);
            input.value = '';
            input.focus();
        };

        addBtn.addEventListener('click', addPath);
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') { e.preventDefault(); addPath(); }
        });

        list.addEventListener('click', (e) => {
            const btn = e.target.closest('.pe-excluded-paths__tag-remove');
            if (btn) btn.closest('.pe-excluded-paths__tag')?.remove();
        });

        // Reconfigure the sidebar Save button for excluded-paths mode
        const saveBtn = document.getElementById('widget-settings-save-btn');
        if (saveBtn) {
            saveBtn.removeAttribute('hx-post');
            saveBtn.removeAttribute('hx-vals');
            htmx.process(saveBtn);

            this._epSaveHandler = () => {
                const paths = this._getExcludedPaths(list);
                widgetEl.dataset.excludedPaths = JSON.stringify(paths);
                this.hasUnsavedChanges = true;
                this.updateSaveButtonState();
                this.history?.push();
                this.saveToLocalStorage();
                if (this.rightSidebarDialog) this.rightSidebarDialog.hide();
            };
            saveBtn.addEventListener('click', this._epSaveHandler);
        }

        this.rightSidebarDialog.show();
    }

    _addExcludedPathTag(list, tagTpl, path) {
        if (!tagTpl) return;
        const clone = tagTpl.content.cloneNode(true);
        clone.querySelector('.pe-excluded-paths__tag-text').textContent = path;
        list.appendChild(clone);
    }

    _getExcludedPaths(list) {
        return Array.from(list.querySelectorAll('.pe-excluded-paths__tag-text'))
            .map(el => el.textContent.trim()).filter(Boolean);
    }

    _cleanupExcludedPaths() {
        window.currentExcludedPathsWidgetEl = null;
        const saveBtn = document.getElementById('widget-settings-save-btn');
        if (saveBtn && this._epSaveHandler) {
            saveBtn.removeEventListener('click', this._epSaveHandler);
            this._epSaveHandler = null;
        }
    }

    /**
     * Refresh all widget toolbars (used on scope change to show/hide scope-specific buttons).
     */
    refreshAllToolbars() {
        const items = this.gridController?.getItems() || [];
        items.forEach(el => {
            this.widgetToolbar.removeToolbar(el);
            const buttons = this.widgetLoader?.widgetButtonsCache?.[el.getAttribute('data-widget-name')] || [];
            this.widgetToolbar.addToolbar(el, buttons);
        });
    }

    updateUndoRedoButtons() {
        if (this.elements.undoBtn) this.elements.undoBtn.disabled = !this.history.canUndo();
        if (this.elements.redoBtn) this.elements.redoBtn.disabled = !this.history.canRedo();
    }

    updateSaveButtonState() {
        if (this.elements.saveBtn) this.elements.saveBtn.disabled = !this.hasUnsavedChanges;
    }

    applyContainerWidth(isFullWidth) {
        document.documentElement.setAttribute('data-container-width', isFullWidth ? 'fullwidth' : 'container');
        document.querySelectorAll('.container').forEach(c => {
            if (!c.classList.contains('keep-container')) c.classList.toggle('container-fullwidth', isFullWidth);
        });
        this.eventBus.emit(window.FlutePageEdit.events.CONTAINER_WIDTH_CHANGED, { isFullWidth });
    }

    attemptRecoveryFromLocalStorage() {
        try {
            this.localStorage.initialize();
        } catch (err) {
            this.utils.logError('attemptRecoveryFromLocalStorage', err);
        }
    }

    handlePageLoad() {
        if (this.history) { this.history.clear(); this.updateUndoRedoButtons(); }
        this.localStorage.initialize();
    }
}

function initializePageEditor() {
    document.querySelectorAll('#page-change-button, .pe-topbar, .pe-sidebar, .page-edit-navbar, .page-edit-sidebar')
        .forEach(el => { if (el) el.removeAttribute('style'); });

    try {
        if (!window.pageEditor) {
            console.info('Initializing page editor v4.0 (GridStack)');
            window.pageEditor = new PageEditor();
        } else {
            window.pageEditor.handlePageLoad();
        }

        window.toggleEditMode = (enable, ignoreHtmx = false) => {
            if (!window.pageEditor) return;
            enable ? window.pageEditor.enable() : window.pageEditor.disable(ignoreHtmx);
        };
    } catch (err) {
        console.error('Failed to initialize page editor:', err);
        window.toggleEditMode = () => showNotyfError('Page editor failed to initialize. Please refresh.');
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializePageEditor);
} else {
    initializePageEditor();
}

window.addEventListener('htmx:afterSwap', () => setTimeout(initializePageEditor, 50));
document.addEventListener('htmx:responseError', (evt) => console.error('HTMX error:', evt.detail.error));

window.getContainerWidthMode = function() {
    const toggle = document.getElementById('container-width-checkbox');
    return toggle?.checked ? 'fullwidth' : 'container';
};

window.FlutePageEdit.register('PageEditor', PageEditor);
window.FlutePageEdit.initializePageEditor = initializePageEditor;
