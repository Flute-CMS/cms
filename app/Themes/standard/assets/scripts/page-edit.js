class PageEditorConfig {
    constructor(options = {}) {
        this.selectors = {
            editBtn: '#page-change-button',
            cancelBtn: '#page-change-cancel',
            widgetsSidebar: '.page-edit-widgets',
            pageEditBtn: '#page-edit-button',
            navbar: '.page-edit-nav',
            widgetGrid: '#widget-grid',
            searchInput: '#page-edit-widgets-search',
            resetBtn: '#page-edit-reset',
            undoBtn: '#page-edit-undo',
            redoBtn: '#page-edit-redo',
            saveBtn: '#page-edit-save',
            seoBtn: '#page-change-seo',
            ...options.selectors,
        };

        this.icons = {
            settings:
                document.getElementById('settings-widget-icon')?.innerHTML ||
                '⚙️',
            delete:
                document.getElementById('delete-widget-icon')?.innerHTML ||
                '🗑️',
            refresh:
                document.getElementById('refresh-widget-icon')?.innerHTML ||
                '🔄',
            ...options.icons,
        };

        this.gridOptions = {
            margin: 10,
            acceptWidgets: true,
            sizeToContent: true,
            disableDrag: false,
            disableResize: false,
            animate: true,
            ...options.gridOptions,
        };

        this.shortcuts = {
            undo: { key: 'z', ctrl: true },
            redo: { key: 'y', ctrl: true },
            save: { key: 's', ctrl: true },
            escape: { key: 'Escape' },
            ...options.shortcuts,
        };

        this.translations = {
            unsavedChanges: 'You have unsaved changes. Leave without saving?',
            resetConfirm: 'Reset all changes?',
            errorLoading: 'Error loading widget',
            errorSaving: 'Error saving layout: ',
            finish: translate('def.finish'),
            more: translate('def.more'),
            settings: translate('def.widget_settings'),
            delete: translate('def.delete_widget'),
            refresh: translate('def.refresh_widget'),
            ...options.translations,
        };

        this.widgetButtons = {
            ...options.widgetButtons,
        };
    }
}
class OnboardingManager {
    constructor(editor) {
        this.editor = editor;
        this.config = editor.config;
        this.onboardingShownKey = 'page-edit-onboarding-shown';
        this.container = document.getElementById('pageEditOnboarding');
        this.slidesContainer = document.getElementById('onboardingSlides');
        this.indicatorsContainer = document.getElementById(
            'onboardingIndicators',
        );
        this.nextBtn = document.getElementById('onboardingNextBtn');
        this.currentSlideIndex = 0;
    }

    initialize() {
        if (!this.container || !this.slidesContainer) return;
        if (localStorage.getItem(this.onboardingShownKey)) return;

        this.slides = this.slidesContainer.querySelectorAll(
            '.page-edit-onboarding-slide',
        );

        if (this.slides.length === 0) {
            this.container.style.display = 'none';
            return;
        }

        this.setupIndicators();
        this.setupEventListeners();
        this.show();
    }

    setupIndicators() {
        if (this.indicatorsContainer) {
            this.indicatorsContainer.innerHTML = '';

            this.slides.forEach((_, index) => {
                const indicator = document.createElement('div');
                indicator.classList.add('indicator');
                if (index === 0) indicator.classList.add('active');
                indicator.dataset.slideIndex = index;
                indicator.addEventListener('click', () =>
                    this.goToSlide(index),
                );
                this.indicatorsContainer.appendChild(indicator);
            });
        }
    }

    setupEventListeners() {
        this.nextBtn?.addEventListener('click', () => {
            if (this.currentSlideIndex < this.slides.length - 1) {
                this.currentSlideIndex++;
                this.update();
            } else {
                this.finish();
            }
        });
    }

    update() {
        this.slides.forEach((slide, index) => {
            slide.classList.toggle('active', index === this.currentSlideIndex);
        });

        const indicators =
            this.indicatorsContainer?.querySelectorAll('.indicator');
        if (indicators) {
            indicators.forEach((indicator, index) => {
                indicator.classList.toggle(
                    'active',
                    index === this.currentSlideIndex,
                );
            });
        }

        if (this.nextBtn) {
            const isLastSlide =
                this.currentSlideIndex === this.slides.length - 1;
            this.nextBtn.innerHTML = isLastSlide
                ? translate('page.onboarding.finish')
                : translate('page.onboarding.next');
        }
    }

    goToSlide(index) {
        if (index < 0 || index >= this.slides.length) return;
        this.currentSlideIndex = index;
        this.update();
    }

    show() {
        this.container.style.display = 'flex';
        setTimeout(() => {
            this.container.classList.add('active');
            this.update();
        }, 50);
    }

    finish() {
        this.container.classList.remove('active');
        setTimeout(() => {
            this.container.style.display = 'none';
        }, 300);
        localStorage.setItem(this.onboardingShownKey, 'true');
    }
}
class HistoryManager {
    constructor(editor) {
        this.editor = editor;
        this.states = [];
        this.currentIndex = -1;
        this.isProcessing = false;
        this.maxStates = 50;
    }

    createSnapshot() {
        const items = Array.from(
            document.querySelectorAll('.grid-stack .grid-stack-item'),
        ).map((el) => {
            const node = el.gridstackNode;
            const toolbar = el.querySelector('.widget-toolbar');

            return {
                id: el.getAttribute('data-widget-id'),
                widgetName: el.getAttribute('data-widget-name'),
                settings: el.dataset.widgetSettings,
                content: el.querySelector('.grid-stack-item-content')
                    ?.innerHTML,
                buttons: toolbar
                    ? this.editor.widgetButtonsCache[
                    el.getAttribute('data-widget-name')
                    ]
                    : [],
                position: {
                    x: node.x,
                    y: node.y,
                    w: node.w,
                    h: node.h,
                },
            };
        });

        return {
            items,
            timestamp: Date.now(),
        };
    }

    applySnapshot(snapshot) {
        if (!snapshot || !snapshot.items) return;

        this.isProcessing = true;

        this.editor.grid.removeAll();

        snapshot.items.forEach((item) => {
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
    }

    undo() {
        if (this.currentIndex > 0) {
            this.currentIndex--;
            this.applySnapshot(this.states[this.currentIndex]);
            this.editor.updateUndoRedoButtons();
            return true;
        }
        return false;
    }

    redo() {
        if (this.currentIndex < this.states.length - 1) {
            this.currentIndex++;
            this.applySnapshot(this.states[this.currentIndex]);
            this.editor.updateUndoRedoButtons();
            return true;
        }
        return false;
    }

    canUndo() {
        return this.currentIndex > 0;
    }

    canRedo() {
        return this.currentIndex < this.states.length - 1;
    }

    clear() {
        this.states = [];
        this.currentIndex = -1;
        this.editor.updateUndoRedoButtons();
    }
}
class PageEditor {
    constructor(options = {}) {
        this.config = new PageEditorConfig(options);
        this.history = new HistoryManager(this);
        this.grid = null;
        this.hasUnsavedChanges = false;
        this.isProcessing = false;
        this.animationDuration = 300;

        this.elements = {};
        this.initializeElements();

        this.onboarding = new OnboardingManager(this);
        this.setupEventListeners();

        this.isEditorFocused = false;
        this.pendingOperations = 0;

        this.baseWidgetButtons = {
            settings: {
                icon: this.config.icons.settings,
                tooltip: this.config.translations.settings,
                order: 20,
                onClick: (widgetEl, editor) => {
                    editor.openWidgetSettings(widgetEl);
                },
                shouldShow: (widgetEl) => {
                    return (
                        widgetEl.hasAttribute('data-has-settings') &&
                        widgetEl.getAttribute('data-has-settings') === 'true'
                    );
                },
            },
            refresh: {
                icon: this.config.icons.refresh,
                tooltip: this.config.translations.refresh,
                order: 10,
                onClick: (widgetEl, editor) => {
                    editor.refreshWidget(widgetEl);
                },
            },
            delete: {
                icon: this.config.icons.delete,
                tooltip: this.config.translations.delete,
                order: 100,
                onClick: (widgetEl, editor) => {
                    editor.grid.removeWidget(widgetEl);
                },
            },
        };

        this.widgetButtonsCache = {};

        this.activeCategory = null;
        this.setupCategoryHandlers();

        this.logError = (context, error) => {
            console.error(`PageEditor [${context}]:`, error);
        };

        this.attemptRecoveryFromLocalStorage();
    }

    /**
     * Dispatch custom widget events
     */
    dispatchWidgetEvent(eventName, detail = {}) {
        try {
            const event = new CustomEvent(eventName, {
                detail,
                bubbles: true,
                cancelable: true
            });
            document.dispatchEvent(event);
        } catch (err) {
            this.logError(`dispatchWidgetEvent ${eventName}`, err);
        }
    }

    /**
     * Get current page path
     */
    getCurrentPath() {
        return window.location.pathname || '/';
    }

    /**
     * Get localStorage key for current path
     */
    getLocalStorageKey() {
        return `page-layout-${this.getCurrentPath()}`;
    }

    initializeElements() {
        try {
            Object.entries(this.config.selectors).forEach(([key, selector]) => {
                this.elements[key] = document.querySelector(selector);
            });

            // Add SEO button element
            this.elements.seoBtn = document.querySelector('#page-change-seo');
        } catch (err) {
            console.error('Failed to initialize elements:', err);
        }
    }

    setupEventListeners() {
        try {
            this.elements.editBtn?.addEventListener('click', () =>
                this.enable(),
            );
            this.elements.cancelBtn?.addEventListener('click', () => {
                this.resetActiveCategory();
                this.disable();
            });
            this.elements.resetBtn?.addEventListener('click', () =>
                this.resetLayout(),
            );
            this.elements.undoBtn?.addEventListener('click', () =>
                this.history.undo(),
            );
            this.elements.redoBtn?.addEventListener('click', () =>
                this.history.redo(),
            );
            this.elements.saveBtn?.addEventListener('click', () =>
                this.saveLayout(),
            );

            // Add SEO button event listener
            this.elements.seoBtn?.addEventListener('click', () =>
                app.dropdowns.closeAllDropdowns()
            );

            if (this.elements.searchInput) {
                let searchTimeout;
                this.elements.searchInput.addEventListener('input', (e) => {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(() => this.handleSearch(e), 150);
                });
            }

            window.addEventListener('beforeunload', (e) =>
                this.handleBeforeUnload(e),
            );
            document.addEventListener('keydown', (e) => this.handleKeyDown(e));

            this.setupHtmxListeners();

            document.addEventListener('focusin', (e) => {
                this.isEditorFocused = e.target.matches(
                    'input, textarea, [contenteditable="true"]',
                );
            });

            document.addEventListener('focusout', () => {
                this.isEditorFocused = false;
            });
        } catch (err) {
            this.logError('setupEventListeners', err);
        }
    }

    setupHtmxListeners() {
        try {
            htmx.on('htmx:afterSwap', (evt) => this.handleHtmxAfterSwap(evt));
            htmx.on('htmx:beforeRequest', (evt) =>
                this.handleHtmxBeforeRequest(evt),
            );

            htmx.on('htmx:responseError', (evt) => {
                this.logError('HTMX response error', {
                    status: evt.detail.xhr.status,
                    url: evt.detail.requestConfig?.url,
                    target: evt.detail.target?.id,
                });

                if (evt.detail.target?.id === 'page-edit-dialog-content') {
                    evt.detail.target.innerHTML = `
                        <div class="alert alert-danger">
                            ${this.config.translations.errorLoading ||
                        'Error loading content'
                        }
                        </div>
                    `;
                }
            });

            // Handle settings save response in dialog
            const editor = this;
            htmx.on('htmx:afterRequest', (evt) => {
                const elt = evt.detail.elt;
                if (elt?.id === 'widget-settings-save-btn') {
                    let json;
                    try { json = JSON.parse(evt.detail.xhr.response); } catch { return; }
                    if (json.success && json.html && json.settings && window.currentEditedWidgetEl) {
                        const sidebarContent = document.getElementById('page-edit-dialog-content');
                        window.currentEditedWidgetEl.dataset.widgetSettings = JSON.stringify(json.settings);
                        editor.autoResize(window.currentEditedWidgetEl);
                        editor.history.push();
                        editor.saveToLocalStorage();
                        if (editor.rightSidebarDialog) editor.rightSidebarDialog.hide();
                    }
                }
            });
        } catch (err) {
            this.logError('setupHtmxListeners', err);
        }
    }

    attemptRecoveryFromLocalStorage() {
        try {
            const savedLayout = localStorage.getItem(this.getLocalStorageKey());
            if (
                savedLayout &&
                document.body.classList.contains('page-edit-mode')
            ) {
                console.info('Attempting to recover layout from local storage for path:', this.getCurrentPath());
            }

            if (savedLayout && document.body.classList.contains('page-edit-mode')) {
                console.info('Attempting to recover layout from localStorage for path:', this.getCurrentPath());
                this.loadFromLocalStorage();
                this.hasUnsavedChanges = true;
                this.updateSaveButtonState();
            }
        } catch (err) {
            this.logError('attemptRecoveryFromLocalStorage', err);
        }
    }

    resetActiveCategory() {
        document.querySelectorAll('.widget-category-header.active').forEach(header => {
            header.classList.remove('active');
            if (header.nextElementSibling) {
                header.nextElementSibling.classList.remove('active');
            }
        });
        this.activeCategory = null;
    }

    enable() {
        if (this.isProcessing) return;
        this.isProcessing = true;

        try {
            this.currentPath = this.getCurrentPath();
            this.localStorageKey = this.getLocalStorageKey();
            
            this.onboarding.initialize();
            document.body.classList.add('page-edit-mode');

            this.elements.widgetsSidebar?.classList.add('active');
            this.elements.navbar?.classList.add('active');
            this.elements.pageEditBtn?.classList.add('hide');

            this.resetActiveCategory();

            const mainElement = document.getElementById('main');
            if (!mainElement) {
                throw new Error('Main element not found');
            }

            mainElement.innerHTML = `
                <div class="container">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="grid-stack" id="widget-grid" data-gs-animate="yes"></div>
                        </div>
                    </div>
                </div>
            `;

            this.initializeGrid();
            setTimeout(() => {
                this.elements.searchInput?.focus();
                this.isProcessing = false;
            }, this.animationDuration + 100);

            app.dropdowns.closeAllDropdowns();

            this.activeCategory = null;
            this.setupCategoryHandlers();
        } catch (err) {
            this.isProcessing = false;
            this.logError('enable', err);
            this.disable(true);
        }
    }

    disable(ignoreHtmx = false) {
        if (this.isProcessing) return;

        try {
            if (this.hasUnsavedChanges) {
                const confirmLeave = confirm(
                    this.config.translations.unsavedChanges,
                );
                if (!confirmLeave) return;
            }

            this.isProcessing = true;

            document.body.classList.remove('page-edit-mode');
            this.elements.widgetsSidebar?.classList.remove('active');
            this.elements.navbar?.classList.remove('active');
            this.elements.pageEditBtn?.classList.remove('hide');

            this.resetActiveCategory();

            this.destroyGrid(ignoreHtmx);

            app.dropdowns.closeAllDropdowns();

            setTimeout(() => {
                this.isProcessing = false;
            }, this.animationDuration);
        } catch (err) {
            this.isProcessing = false;
            this.logError('disable', err);
        }
    }

    initializeGrid() {
        if (this.grid) return;

        try {
            this.elements.widgetGrid = document.getElementById('widget-grid');

            if (!this.elements.widgetGrid) {
                throw new Error('Widget grid element not found');
            }

            const gridOptions = {
                ...this.config.gridOptions,
                minRow: 1,
                column: 12,
                animate: true,
            };

            this.grid = GridStack.init(gridOptions, this.elements.widgetGrid);

            if (!this.grid) {
                throw new Error('Failed to initialize GridStack');
            }

            try {
                GridStack.setupDragIn('.page-edit-widgets-item', {
                    helper: 'clone',
                    scroll: true,
                    appendTo: 'body',
                });
            } catch (dragErr) {
                this.logError('setupDragIn', dragErr);
            }

            if (typeof this.grid.cellWidth === 'function') {
                this.grid.cellHeight(this.grid.cellWidth() / 2);
            }

            this.setupGridEvents();

            const savedLayout = this.loadFromLocalStorage();
            if (!savedLayout) {
                this.fetchLayoutFromServer();
            }
        } catch (err) {
            this.logError('initializeGrid', err);
            this.showErrorNotification(
                'Failed to initialize page editor. Please refresh the page.',
            );
        }
    }

    setupGridEvents() {
        try {
            if (!this.grid) return;

            let changeTimeout;
            const handleChange = () => {
                clearTimeout(changeTimeout);
                changeTimeout = setTimeout(() => this.handleGridChange(), 100);
            };

            this.grid.on('added removed change', handleChange);
            this.grid.on('resizestop', handleChange);
            this.grid.on('dropped', (ev, prev, newW) => {
                try {
                    this.handleWidgetDrop(ev, prev, newW);
                } catch (err) {
                    this.logError('widgetDrop event', err);
                }
            });
        } catch (err) {
            this.logError('setupGridEvents', err);
        }
    }

    handleWidgetDrop(ev, prev, newW) {
        if (!newW || !newW.el || prev) return;

        try {
            const content = newW.el.querySelector('.grid-stack-item-content');
            if (!content) return;

            newW.el.style.transition = `all ${this.animationDuration}ms ease-in-out`;
            newW.el.classList.add('widget-dropping');

            setTimeout(() => {
                newW.el.classList.remove('widget-dropping');
                setTimeout(() => {
                    newW.el.style.transition = '';
                }, this.animationDuration);
            }, this.animationDuration);

            this.initializeWidget(newW.el, content);
        } catch (err) {
            this.logError('handleWidgetDrop', err);

            try {
                const content = newW.el.querySelector(
                    '.grid-stack-item-content',
                );
                if (content) {
                    content.innerHTML = `<div class="widget-error">${this.config.translations.errorLoading}</div>`;
                    content.style.pointerEvents = 'auto';
                }
            } catch (recoveryErr) {
                if (this.grid && newW.el) {
                    this.grid.removeWidget(newW.el);
                }
            }
        }
    }

    async initializeWidget(widgetEl, content) {
        if (!widgetEl || !content) return;

        const widgetName = widgetEl.getAttribute('data-widget-name');
        if (!widgetName) return;

        this.pendingOperations++;
        content.style.pointerEvents = 'none';
        content.innerHTML = this.createSkeleton();

        try {
            const [contentResponse, buttonsResponse] = await Promise.all([
                this.loadWidgetContent(widgetEl).catch((err) => {
                    this.logError(`loadWidgetContent for ${widgetName}`, err);
                    return {
                        html: `<div class="widget-error">${this.config.translations.errorLoading}</div>`,
                        settings: {},
                    };
                }),
                this.loadWidgetButtons(widgetName).catch((err) => {
                    this.logError(`loadWidgetButtons for ${widgetName}`, err);
                    return [];
                }),
            ]);

            if (!document.body.contains(widgetEl)) {
                this.pendingOperations--;
                return;
            }

            content.style.opacity = '0';
            content.innerHTML = contentResponse.html || '';

            widgetEl.dataset.widgetSettings = JSON.stringify(
                contentResponse.settings || {},
            );

            setTimeout(() => {
                content.style.transition = `opacity ${this.animationDuration / 2
                    }ms ease-in-out`;
                content.style.opacity = '1';
                content.style.pointerEvents = 'auto';

                setTimeout(() => {
                    content.style.transition = '';
                }, this.animationDuration / 2);
            }, 50);

            this.addToolbar(widgetEl, buttonsResponse);
            this.autoResize(widgetEl);

            this.dispatchWidgetEvent('widgetInitialized', {
                widgetName,
                widgetElement: widgetEl,
                content: contentResponse
            });
        } catch (err) {
            this.logError(`initializeWidget ${widgetName}`, err);

            if (document.body.contains(widgetEl) && content) {
                content.innerHTML = `<div class="widget-error">${this.config.translations.errorLoading}</div>`;
                content.style.pointerEvents = 'auto';
                this.addToolbar(widgetEl, []);
            }
        } finally {
            this.pendingOperations--;
        }
    }

    async loadWidgetContent(widgetEl) {
        const widgetName = widgetEl.getAttribute('data-widget-name');
        const settings = JSON.parse(widgetEl.dataset.widgetSettings || '{}');

        const res = await fetch(u('api/pages/render-widget'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN':
                    document.querySelector('meta[name="csrf-token"]')
                        ?.content || '',
            },
            body: JSON.stringify({
                widget_name: widgetName,
                settings: settings,
            }),
        });

        if (!res.ok) throw new Error('Failed to load widget content');
        const result = await res.json();

        if (result.settings && widgetEl) {
            widgetEl.setAttribute(
                'data-has-settings',
                result.hasSettings !== undefined
                    ? result.hasSettings.toString()
                    : 'false',
            );
        }

        return result;
    }

    async loadWidgetButtons(widgetName) {
        if (this.widgetButtonsCache[widgetName]) {
            return this.widgetButtonsCache[widgetName];
        }

        try {
            const res = await fetch(
                u(
                    `api/pages/widgets/${encodeURIComponent(
                        widgetName,
                    )}/buttons`,
                ),
                {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN':
                            document.querySelector('meta[name="csrf-token"]')
                                ?.content || '',
                    },
                },
            );

            if (!res.ok) return [];

            const buttons = await res.json();
            if (Array.isArray(buttons)) {
                this.widgetButtonsCache[widgetName] = buttons;
                return buttons;
            }
        } catch (err) {
            console.error('Failed to load widget buttons:', err);
        }

        return [];
    }

    addToolbar(widgetEl, customButtons = []) {
        if (!widgetEl || widgetEl.querySelector('.widget-toolbar')) return;

        try {
            const toolbar = document.createElement('div');
            toolbar.classList.add('widget-toolbar');

            Object.assign(toolbar.style, {
                opacity: '0',
                bottom: '-15px',
                transition: `opacity ${this.animationDuration / 2
                    }ms ease-in-out, transform ${this.animationDuration / 2
                    }ms ease-out`,
                position: 'absolute',
                zIndex: '999',
                pointerEvents: 'none',
            });

            const allButtons = [
                ...Object.entries(this.baseWidgetButtons).map(([key, btn]) => ({
                    ...btn,
                    key,
                    type: 'base',
                })),
                ...customButtons.map((btn) => ({
                    ...btn,
                    order: btn.order || 50,
                    type: 'custom',
                })),
            ];

            const filteredButtons = allButtons.filter((button) => {
                if (button.type === 'base' && button.shouldShow) {
                    return button.shouldShow(widgetEl);
                }
                return true;
            });

            filteredButtons.sort((a, b) => a.order - b.order);

            const toolbarHtml = filteredButtons
                .map((button) => {
                    if (button.type === 'base') {
                        return `
                        <button class="widget-button widget-button-${button.key}" 
                                data-tooltip="${button.tooltip}">
                            ${button.icon}
                        </button>
                    `;
                    } else {
                        return `
                        <button class="widget-button widget-button-custom" 
                                data-action="${button.action}"
                                data-tooltip="${button.tooltip}">
                            ${button.icon}
                        </button>
                    `;
                    }
                })
                .join('');

            toolbar.innerHTML = toolbarHtml;
            widgetEl.appendChild(toolbar);

            this.setupToolbarEvents(widgetEl, toolbar, filteredButtons);

            setTimeout(() => {
                toolbar.style.pointerEvents = 'auto';
            }, 100);
        } catch (err) {
            this.logError('addToolbar', err);
        }
    }

    setupToolbarEvents(widgetEl, toolbar, allButtons) {
        if (!widgetEl || !toolbar) return;

        try {
            let hoverTimeout;

            widgetEl.addEventListener('mouseenter', () => {
                clearTimeout(hoverTimeout);
                hoverTimeout = setTimeout(() => {
                    toolbar.style.opacity = '1';
                }, 50);
            });

            widgetEl.addEventListener('mouseleave', () => {
                clearTimeout(hoverTimeout);
                toolbar.style.opacity = '0';
            });

            allButtons.forEach((button) => {
                let btn;
                if (button.type === 'base') {
                    btn = toolbar.querySelector(`.widget-button-${button.key}`);
                } else {
                    btn = toolbar.querySelector(
                        `[data-action="${button.action}"]`,
                    );
                }

                if (btn) {
                    btn.addEventListener('click', async (e) => {
                        e.stopPropagation();
                        e.preventDefault();

                        btn.style.transform = 'scale(0.95)';
                        setTimeout(() => {
                            btn.style.transform = 'scale(1)';
                        }, 100);

                        try {
                            if (button.type === 'base') {
                                button.onClick(widgetEl, this);
                            } else {
                                await this.handleCustomButtonClick(
                                    widgetEl,
                                    button,
                                );
                            }
                        } catch (err) {
                            this.logError(
                                `button click: ${button.type === 'base'
                                    ? button.key
                                    : button.action
                                }`,
                                err,
                            );
                        }
                    });
                }
            });
        } catch (err) {
            this.logError('setupToolbarEvents', err);
        }
    }

    async handleCustomButtonClick(widgetEl, button) {
        if (!widgetEl || !button || !button.action) return;

        const widgetName = widgetEl.getAttribute('data-widget-name');
        if (!widgetName) return;

        this.pendingOperations++;

        try {
            const res = await fetch(
                u(`api/pages/widgets/${encodeURIComponent(widgetName)}/action`),
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN':
                            document.querySelector('meta[name="csrf-token"]')
                                ?.content || '',
                    },
                    body: JSON.stringify({
                        action: button.action,
                        widgetId: widgetEl.getAttribute('data-widget-id'),
                    }),
                },
            );

            if (!res.ok) {
                throw new Error(`Action failed with status: ${res.status}`);
            }

            const result = await res.json();

            if (!document.body.contains(widgetEl)) return;

            if (result.reload) {
                this.refreshWidget(widgetEl);
            }
        } catch (err) {
            this.logError(`handleCustomButtonClick: ${button.action}`, err);

            const content = widgetEl.querySelector('.grid-stack-item-content');
            if (content) {
                const errorEl = document.createElement('div');
                errorEl.className = 'widget-action-error';
                errorEl.textContent = 'Action failed';
                errorEl.style.position = 'absolute';
                errorEl.style.top = '5px';
                errorEl.style.right = '5px';
                errorEl.style.background = 'rgba(220, 53, 69, 0.8)';
                errorEl.style.color = 'white';
                errorEl.style.padding = '3px 8px';
                errorEl.style.borderRadius = '3px';
                errorEl.style.fontSize = '12px';
                errorEl.style.opacity = '0';
                errorEl.style.transition = 'opacity 0.3s ease-in-out';

                content.appendChild(errorEl);

                setTimeout(() => {
                    errorEl.style.opacity = '1';
                    setTimeout(() => {
                        errorEl.style.opacity = '0';
                        setTimeout(() => errorEl.remove(), 300);
                    }, 2000);
                }, 10);
            }
        } finally {
            this.pendingOperations--;
        }
    }

    autoResize(widgetEl) {
        if (!widgetEl || !this.grid) return;

        requestAnimationFrame(() => {
            try {
                if (!widgetEl.isConnected || !document.contains(widgetEl)) {
                    return;
                }

                const content = widgetEl.querySelector(
                    '.grid-stack-item-content',
                );
                if (!content) {
                    return;
                }

                if (typeof this.grid.resizeToContent === 'function') {
                    widgetEl.style.transition = `height ${this.animationDuration}ms ease-in-out, 
                                                width ${this.animationDuration}ms ease-in-out`;

                    this.grid.resizeToContent(widgetEl);
                    this.handleGridChange();

                    setTimeout(() => {
                        widgetEl.style.transition = '';
                    }, this.animationDuration);
                }
            } catch (err) {
                this.logError('autoResize', err);
            }
        });
    }

    createSkeleton() {
        return `<div class="skeleton page-edit-skeleton" 
             style="animation: skeleton-loading 1.5s infinite ease-in-out;">
        </div>`;
    }

    getLayoutJson() {
        if (!this.grid) return [];

        try {
            const items = document.querySelectorAll(
                '.grid-stack .grid-stack-item',
            );
            if (!items || items.length === 0) return [];

            const sortedItems = Array.from(items).sort((a, b) => {
                const aNode = a.gridstackNode || {};
                const bNode = b.gridstackNode || {};
                if ((aNode.y || 0) === (bNode.y || 0)) {
                    return (aNode.x || 0) - (bNode.x || 0);
                }
                return (aNode.y || 0) - (bNode.y || 0);
            });

            return sortedItems
                .map((el, index) => {
                    try {
                        const node = el.gridstackNode || {};
                        let parsedSettings = {};

                        try {
                            const settingsStr = el.dataset.widgetSettings;
                            parsedSettings = settingsStr
                                ? JSON.parse(settingsStr)
                                : {};
                        } catch (jsonErr) {
                            this.logError(
                                'getLayoutJson parse settings',
                                jsonErr,
                            );
                        }

                        return {
                            index,
                            id: el.getAttribute('data-widget-id') || null,
                            widgetName:
                                el.getAttribute('data-widget-name') || '',
                            settings: parsedSettings,
                            gridstack: {
                                h: node.h || 1,
                                w: node.w || 1,
                                x: node.x || 0,
                                y: node.y || 0,
                            },
                        };
                    } catch (itemErr) {
                        this.logError(`getLayoutJson item ${index}`, itemErr);
                        return {
                            index,
                            widgetName:
                                el.getAttribute('data-widget-name') ||
                                'unknown',
                            settings: {},
                            gridstack: { h: 1, w: 1, x: 0, y: 0 },
                        };
                    }
                })
                .filter(Boolean); // Remove any undefined items
        } catch (err) {
            this.logError('getLayoutJson', err);
            return [];
        }
    }

    async fetchLayoutFromServer() {
        if (this.isFetchingLayout) return;
        this.isFetchingLayout = true;

        let retryCount = 0;
        const maxRetries = 2;

        const tryFetch = async () => {
            try {
                const currentPath = window.location.pathname || '/';
                const res = await fetch(
                    u(
                        `api/pages/get-layout?path=${encodeURIComponent(
                            currentPath,
                        )}&_=${Date.now()}`, // Cache busting
                    ),
                    {
                        method: 'GET',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN':
                                document.querySelector(
                                    'meta[name="csrf-token"]',
                                )?.content || '',
                        },
                        signal: AbortSignal.timeout(10000),
                    },
                );

                if (!res.ok) {
                    throw new Error(`Server responded with ${res.status}`);
                }

                const json = await res.json();

                if (!json || !json.layout) {
                    throw new Error('Invalid layout data received');
                }

                this.loadLayoutJson(json.layout);
                this.hasUnsavedChanges = false;
                this.updateSaveButtonState();
                return true;
            } catch (err) {
                this.logError(
                    `fetchLayoutFromServer (attempt ${retryCount + 1})`,
                    err,
                );

                if (retryCount < maxRetries) {
                    retryCount++;
                    const backoff = Math.pow(2, retryCount - 1) * 1000;
                    await new Promise((resolve) =>
                        setTimeout(resolve, backoff),
                    );
                    return tryFetch();
                }

                this.showErrorNotification(
                    'Failed to load page layout. Using default layout.',
                );
                return false;
            }
        };

        try {
            await tryFetch();
        } finally {
            this.isFetchingLayout = false;
        }
    }

    loadLayoutJson(data) {
        if (!this.grid || !Array.isArray(data)) return;

        try {
            this.grid.removeAll();

            const promises = [];

            data.forEach((nd, index) => {
                try {
                    const div = document.createElement('div');
                    div.classList.add('grid-stack-item');

                    div.style.opacity = '0';
                    div.style.transition = `opacity ${this.animationDuration}ms ease-out, 
                                          transform ${this.animationDuration}ms ease-out`;
                    div.style.transitionDelay = `${index * 50}ms`;

                    if (nd.id) div.setAttribute('data-widget-id', nd.id);
                    div.setAttribute('data-widget-name', nd.widgetName || '');
                    div.dataset.widgetSettings = JSON.stringify(
                        nd.settings || {},
                    );

                    Object.entries(nd.gridstack || {}).forEach(
                        ([key, value]) => {
                            if (value !== undefined) {
                                div.setAttribute(`gs-${key}`, value);
                            }
                        },
                    );

                    const content = document.createElement('div');
                    content.classList.add('grid-stack-item-content');
                    content.style.pointerEvents = 'auto';

                    div.appendChild(content);
                    const widget = this.grid.makeWidget(div);

                    if (nd.gridstack) {
                        this.grid.update(widget, {
                            x: nd.gridstack.x,
                            y: nd.gridstack.y,
                            w: nd.gridstack.w,
                            h: nd.gridstack.h,
                        });
                    }

                    setTimeout(() => {
                        div.style.opacity = '1';
                    }, 10);

                    setTimeout(() => {
                        div.style.transition = '';
                    }, this.animationDuration + index * 50);

                    promises.push(
                        new Promise((resolve) => {
                            setTimeout(() => {
                                this.initializeWidget(div, content);
                                resolve();
                            }, Math.min(index * 100, 500)); // Cap stagger at 500ms
                        }),
                    );
                } catch (widgetErr) {
                    this.logError(`loadLayoutJson widget ${index}`, widgetErr);
                }
            });

            Promise.all(promises).catch((err) =>
                this.logError('loadLayoutJson promises', err),
            );
        } catch (err) {
            this.logError('loadLayoutJson', err);
        }
    }

    handleGridChange() {
        if (this.history.isProcessing) return;
        this.hasUnsavedChanges = true;
        this.updateSaveButtonState();
        this.history.push();
        this.saveToLocalStorage();
    }

    saveToLocalStorage() {
        try {
            const layoutJson = this.getLayoutJson();
            if (layoutJson.length === 0) return;

            localStorage.setItem(
                this.getLocalStorageKey(),
                JSON.stringify(layoutJson),
            );
        } catch (err) {
            this.logError('saveToLocalStorage', err);
            // Don't block the UI for localStorage errors
        }
    }

    async saveLayout() {
        if (!this.grid || this.isSaving) return;

        if (!this.hasUnsavedChanges) {
            this.disable();
            return;
        }

        this.isSaving = true;

        if (this.elements.saveBtn) {
            this.elements.saveBtn.classList.add('saving');
            this.elements.saveBtn.disabled = true;
        }

        try {
            const layoutData = this.getLayoutJson();
            // if (layoutData.length === 0) {
            //     throw new Error('No layout data to save');
            // }

            const csrfToken =
                document.querySelector('meta[name="csrf-token"]')?.content ||
                '';
            if (!csrfToken) {
                throw new Error('CSRF token not found');
            }

            const currentPath = window.location.pathname || '/';
            const res = await fetch(u('api/pages/save-layout'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    layout: layoutData,
                    path: currentPath,
                }),
                signal: AbortSignal.timeout(15000),
            });

            const json = await res.json();
            if (!res.ok) {
                throw new Error(
                    json?.error || `Failed to save layout (${res.status})`,
                );
            }

            this.hasUnsavedChanges = false;
            this.updateSaveButtonState();

            // Сброс истории после успешного сохранения
            this.history.clear();
            this.updateUndoRedoButtons();

            localStorage.removeItem(this.getLocalStorageKey());

            notyf.success(translate('page.saved_successfully'));

            this.refreshPageContent();
        } catch (err) {
            this.logError('saveLayout', err);
            notyf.error(translate('page.error_saving') + err.message);

            if (this.elements.saveBtn) {
                this.elements.saveBtn.classList.remove('saving');
                this.elements.saveBtn.disabled = false;

                this.elements.saveBtn.innerHTML =
                    this.elements.saveBtn.getAttribute('data-original-text') ||
                    'Save';
            }

            this.isSaving = false;
            return false;
        }

        this.disable();
        this.isSaving = false;
        return true;
    }

    refreshPageContent() {
        try {
            htmx.ajax('GET', window.location.href, '#main', {
                swap: 'innerHTML transition:true',
                headers: {
                    'X-CSRF-TOKEN':
                        document.querySelector('meta[name="csrf-token"]')
                            ?.content || '',
                },
            });
        } catch (err) {
            this.logError('refreshPageContent', err);
            window.location.reload();
        }
    }

    updateUndoRedoButtons() {
        if (this.elements.undoBtn) {
            this.elements.undoBtn.disabled = !this.history.canUndo();
        }
        if (this.elements.redoBtn) {
            this.elements.redoBtn.disabled = !this.history.canRedo();
        }
    }

    updateSaveButtonState() {
        if (this.elements.saveBtn) {
            this.elements.saveBtn.disabled = !this.hasUnsavedChanges;
        }
    }

    handleSearch(e) {
        const searchTerm = e.target.value.toLowerCase().trim();
        const categories = document.querySelectorAll('.widget-category');

        categories.forEach((category) => {
            const header = category.querySelector('.widget-category-header');
            const items = category.querySelectorAll('.page-edit-widgets-item');
            let hasVisibleItems = false;

            items.forEach((item) => {
                const text = item.textContent.toLowerCase();
                const isVisible = text.includes(searchTerm);
                item.style.display = isVisible ? '' : 'none';
                if (isVisible) hasVisibleItems = true;
            });

            category.style.display = hasVisibleItems ? '' : 'none';

            if (hasVisibleItems && searchTerm) {
                this.toggleCategory(header, true);
            } else if (!searchTerm) {
                this.toggleCategory(
                    header,
                    header.dataset.category === this.activeCategory,
                );
            }
        });
    }

    handleBeforeUnload(e) {
        if (this.hasUnsavedChanges) {
            e.preventDefault();
            e.returnValue = '';
        }
    }

    handleKeyDown(e) {
        if (this.isEditorFocused) return;

        const { shortcuts } = this.config;

        for (const [action, shortcut] of Object.entries(shortcuts)) {
            const isCtrlPressed = shortcut.ctrl ? e.ctrlKey || e.metaKey : true;
            const isShiftPressed = shortcut.shift ? e.shiftKey : true;
            const isAltPressed = shortcut.alt ? e.altKey : true;
            const isKeyPressed =
                e.key.toLowerCase() === shortcut.key.toLowerCase();

            if (
                isCtrlPressed &&
                isShiftPressed &&
                isAltPressed &&
                isKeyPressed
            ) {
                e.preventDefault();

                switch (action) {
                    case 'undo':
                        this.history.undo();
                        break;
                    case 'redo':
                        this.history.redo();
                        break;
                    case 'save':
                        if (this.hasUnsavedChanges) {
                            this.saveLayout();
                        }
                        break;
                    case 'escape':
                        if (
                            document.body.classList.contains('page-edit-mode')
                        ) {
                            const confirmLeave = this.hasUnsavedChanges
                                ? confirm(
                                    this.config.translations.unsavedChanges,
                                )
                                : true;

                            if (confirmLeave) {
                                this.disable();
                            }
                        }
                        break;
                }

                return;
            }
        }
    }

    handleHtmxAfterSwap(evt) {
        if (
            evt.detail.requestConfig?.url === window.location.href &&
            evt.detail.target?.id === 'main'
        ) {
            this.currentPath = this.getCurrentPath();
            this.localStorageKey = this.getLocalStorageKey();
            
            this.elements.widgetGrid = document.getElementById('widget-grid');
            if (
                document.body.classList.contains('page-edit-mode') &&
                this.elements.widgetGrid &&
                !this.grid
            ) {
                this.initializeGrid();
            }
        }

        try {
            if (evt.detail.target?.id === 'page-edit-dialog-content') {
                const response = evt.detail.xhr.response;

                if (
                    response.includes('<form') ||
                    response.includes('<div class="alert')
                ) {
                    return;
                }

                try {
                    const jsonResponse = JSON.parse(response);
                    if (
                        jsonResponse.success &&
                        jsonResponse.html &&
                        jsonResponse.settings &&
                        window.currentEditedWidgetEl
                    ) {
                        const content =
                            window.currentEditedWidgetEl.querySelector(
                                '.grid-stack-item-content',
                            );
                        if (content) {
                            content.innerHTML = jsonResponse.html;
                            window.currentEditedWidgetEl.dataset.widgetSettings =
                                JSON.stringify(jsonResponse.settings);

                            try {
                                this.autoResize(window.currentEditedWidgetEl);
                            } catch (resizeErr) {
                                console.error(
                                    'Error resizing widget:',
                                    resizeErr,
                                );
                            }
                            this.hasUnsavedChanges = true;
                            this.updateSaveButtonState();
                            this.history.push();
                            this.saveToLocalStorage();

                            this.dispatchWidgetEvent('widgetSettingsSaved', {
                                widgetName: window.currentEditedWidgetEl.getAttribute('data-widget-name'),
                                widgetElement: window.currentEditedWidgetEl,
                                settings: jsonResponse.settings
                            });

                            if (this.rightSidebarDialog) {
                                this.rightSidebarDialog.hide();
                            }
                        }
                    }
                    return;
                } catch (parseErr) {
                    return;
                }
            }

            const respText = evt.detail.xhr.response;
            try {
                const respJson = JSON.parse(respText);
                if (
                    respJson.html &&
                    respJson.settings &&
                    window.currentEditedWidgetEl
                ) {
                    const content = window.currentEditedWidgetEl.querySelector(
                        '.grid-stack-item-content',
                    );
                    if (content) {
                        content.innerHTML = respJson.html;

                        window.currentEditedWidgetEl.dataset.widgetSettings =
                            JSON.stringify(respJson.settings);

                        this.autoResize(window.currentEditedWidgetEl);
                        this.hasUnsavedChanges = true;
                        this.updateSaveButtonState();
                        this.history.push();
                        this.saveToLocalStorage();

                        this.dispatchWidgetEvent('widgetSettingsSaved', {
                            widgetName: window.currentEditedWidgetEl.getAttribute('data-widget-name'),
                            widgetElement: window.currentEditedWidgetEl,
                            settings: respJson.settings
                        });
                    }
                }
            } catch (e) {
                // Not JSON, ignore
            }
        } catch (e) {
            console.error('Error handling HTMX swap:', e);
        }
    }

    handleHtmxBeforeRequest(evt) {
        if (
            evt.detail.target?.id === 'main' &&
            document.body.classList.contains('page-edit-mode')
        ) {
            if (this.hasUnsavedChanges) {
                const confirmLeave = confirm(
                    this.config.translations.unsavedChanges,
                );
                if (!confirmLeave) {
                    evt.preventDefault();
                    return;
                }
            }
            this.disable(true);
        }
    }

    resetLayout() {
        if (!confirm(this.config.translations.resetConfirm)) return;
        this.grid.removeAll();
        this.handleGridChange();
    }

    destroyGrid(ignoreHtmx = false) {
        if (!this.grid) return;

        this.grid.destroy();
        this.grid = null;
        this.history.clear();
        this.hasUnsavedChanges = false;
        this.updateUndoRedoButtons();
        this.updateSaveButtonState();

        try {
            localStorage.removeItem(this.getLocalStorageKey());
        } catch (e) {
            console.warn('Failed to clear saved layout from localStorage', e);
        }

        if (!ignoreHtmx) {
            htmx.ajax('GET', window.location.href, '#main', {
                swap: 'innerHTML transition:true',
                headers: {
                    'X-CSRF-TOKEN':
                        document.querySelector('meta[name="csrf-token"]')
                            ?.content || '',
                },
            });
        }
    }

    refreshWidget(widgetEl) {
        const widgetName = widgetEl.getAttribute('data-widget-name');
        const content = widgetEl.querySelector('.grid-stack-item-content');
        if (!content) return;

        const currentSettings = widgetEl.dataset.widgetSettings;

        content.style.pointerEvents = 'none';
        content.innerHTML = this.createSkeleton();

        const tempDiv = document.createElement('div');
        tempDiv.widgetReference = widgetEl;
        tempDiv.setAttribute('hx-post', u('api/pages/render-widget'));
        tempDiv.setAttribute('hx-trigger', 'load');
        tempDiv.setAttribute('hx-swap', 'none');
        tempDiv.setAttribute(
            'hx-headers',
            JSON.stringify({
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN':
                    document.querySelector('meta[name="csrf-token"]')
                        ?.content || '',
            }),
        );
        tempDiv.setAttribute(
            'hx-vals',
            JSON.stringify({
                widget_name: widgetName,
                settings: JSON.parse(currentSettings || '{}'),
            }),
        );

        const onAfterLoad = (evt) => {
            try {
                const responseText = evt.detail.xhr.responseText;
                const json = JSON.parse(responseText);
                const targetWidget = evt.target.widgetReference;
                if (!targetWidget || !document.body.contains(targetWidget)) {
                    tempDiv.remove();
                    return;
                }
                const targetContent = targetWidget.querySelector('.grid-stack-item-content');
                if (!targetContent) {
                    tempDiv.remove();
                    return;
                }

                if (json.html) {
                    targetContent.innerHTML = json.html;
                } else {
                    targetContent.innerHTML = '';
                }
                if (json.settings) {
                    targetWidget.dataset.widgetSettings = JSON.stringify(
                        json.settings,
                    );
                }
            } catch (e) {
                console.error('Failed to parse JSON in refreshWidget:', e);
            }
            const targetWidget = evt.target.widgetReference;
            if (targetWidget) {
                const targetContent = targetWidget.querySelector('.grid-stack-item-content');
                if (targetContent) {
                    targetContent.style.pointerEvents = 'auto';
                }
            }

            tempDiv.removeEventListener('htmx:afterOnLoad', onAfterLoad);
            tempDiv.removeEventListener('htmx:responseError', onResponseError);
            tempDiv.remove();

            if (targetWidget && document.body.contains(targetWidget)) {
                this.autoResize(targetWidget);
                this.handleGridChange();

                this.dispatchWidgetEvent('widgetRefreshed', {
                    widgetName: targetWidget.getAttribute('data-widget-name'),
                    widgetElement: targetWidget
                });
            }
        };

        const onResponseError = () => {
            const targetWidget = tempDiv.widgetReference;
            if (targetWidget && document.body.contains(targetWidget)) {
                const targetContent = targetWidget.querySelector('.grid-stack-item-content');
                if (targetContent) {
                    targetContent.innerHTML = `<div class="widget-error">${this.config.translations.errorLoading}</div>`;
                    targetContent.style.pointerEvents = 'auto';
                }
            }
            tempDiv.removeEventListener('htmx:afterOnLoad', onAfterLoad);
            tempDiv.removeEventListener('htmx:responseError', onResponseError);
            tempDiv.remove();
        };

        tempDiv.addEventListener('htmx:afterOnLoad', onAfterLoad);
        tempDiv.addEventListener('htmx:responseError', onResponseError);

        tempDiv.style.display = 'none';
        content.appendChild(tempDiv);
        htmx.process(tempDiv);
    }

    setupCategoryHandlers() {
        document
            .querySelectorAll('.widget-category-header')
            .forEach((header) => {
                const newHeader = header.cloneNode(true);
                header.parentNode.replaceChild(newHeader, header);
            });

        document
            .querySelectorAll('.widget-category-header')
            .forEach((header) => {
                header.addEventListener('click', (e) =>
                    this.handleCategoryClick(e),
                );
            });

        const firstCategory = document.querySelector('.widget-category:first-child .widget-category-header');
        if (firstCategory) {
            setTimeout(() => {
                this.toggleCategory(firstCategory, true);

                const categoriesContainer = document.querySelector('.page-edit-widgets-categories');
                if (categoriesContainer) {
                    categoriesContainer.scrollLeft = 0;
                }
            }, 300);
        }

        const categoriesContainer = document.querySelector('.page-edit-widgets-categories');
        const scrollLeftBtn = document.querySelector('.categories-scroll-left');
        const scrollRightBtn = document.querySelector('.categories-scroll-right');

        if (categoriesContainer && scrollLeftBtn && scrollRightBtn) {
            const checkScroll = () => {
                const canScrollLeft = categoriesContainer.scrollLeft > 0;
                const canScrollRight = categoriesContainer.scrollLeft < categoriesContainer.scrollWidth - categoriesContainer.clientWidth;

                const widgetsPanel = document.querySelector('.page-edit-widgets');
                if (widgetsPanel) {
                    widgetsPanel.classList.toggle('can-scroll-left', canScrollLeft);
                    widgetsPanel.classList.toggle('can-scroll-right', canScrollRight);
                }
            };

            scrollLeftBtn.addEventListener('click', () => {
                categoriesContainer.scrollBy({
                    left: -150,
                    behavior: 'smooth'
                });
            });

            scrollRightBtn.addEventListener('click', () => {
                categoriesContainer.scrollBy({
                    left: 150,
                    behavior: 'smooth'
                });
            });

            categoriesContainer.addEventListener('scroll', checkScroll);

            checkScroll();

            window.addEventListener('resize', checkScroll);
        }
    }

    handleCategoryClick(e) {
        const header = e.currentTarget;
        const wasActive = header.classList.contains('active');

        document
            .querySelectorAll('.widget-category-header.active')
            .forEach((h) => {
                if (h !== header) {
                    this.toggleCategory(h, false);
                }
            });

        if (!wasActive) {
            this.toggleCategory(header, true);
        }
    }

    toggleCategory(header, show) {
        if (!header || window.pageEditor.grid === null) return;

        const content = header.nextElementSibling;
        const widgetsList = content?.querySelector('.page-edit-widgets-list');
        if (!content || !widgetsList) return;

        document.querySelectorAll('.widget-category-content.active').forEach((el) => {
            if (el !== content) {
                el.classList.remove('active');
            }
        });

        if (show) {
            header.classList.add('active');
            content.classList.add('active');

            const containerWidth = Math.min(window.innerWidth - 40, 900);
            content.style.width = `${containerWidth}px`;

            const widgets = content.querySelectorAll('.page-edit-widgets-item');
            widgets.forEach((widget, index) => {
                widget.style.opacity = '0';
                widget.style.transform = 'translateY(10px)';
                widget.classList.remove('widget-animate');

                setTimeout(() => {
                    widget.style.transition = 'all 0.3s cubic-bezier(0.16, 1, 0.3, 1)';
                    widget.style.opacity = '1';
                    widget.style.transform = 'translateY(0)';

                    setTimeout(() => {
                        widget.classList.add('widget-animate');
                    }, 100);
                }, 30 + index * 20);
            });

            this.activeCategory = header.dataset.category;

            document.addEventListener('click', this.boundHandleOutsideClick);
        } else {
            header.classList.remove('active');
            content.classList.remove('active');

            if (this.activeCategory === header.dataset.category) {
                this.activeCategory = null;
            }

            document.removeEventListener('click', this.boundHandleOutsideClick);
        }
    }

    handleOutsideClick(event) {
        return;
    }

    async openWidgetSettings(widgetEl) {
        const widgetName = widgetEl.getAttribute('data-widget-name');
        if (!widgetName) return;

        window.currentEditedWidgetEl = widgetEl;

        const rightSidebar = document.getElementById('page-edit-dialog');
        const sidebarContent = document.querySelector(
            '#page-edit-dialog-content',
        );
        const saveButton = document.getElementById('widget-settings-save-btn');

        if (!rightSidebar || !sidebarContent) {
            console.error('Right sidebar or content container not found');
            return;
        }

        if (!this.rightSidebarDialog) {
            this.rightSidebarDialog = new A11yDialog(rightSidebar);
            this.rightSidebarDialog.on('hide', () => {
                window.currentEditedWidgetEl = null;
            });
        }

        sidebarContent.innerHTML = `<div class="widget-settings-loading skeleton page-edit-skeleton widget-setting-loading"></div>
        <div class="widget-settings-loading skeleton page-edit-skeleton widget-setting-loading"></div>
        <div class="widget-settings-loading skeleton page-edit-skeleton widget-setting-loading"></div>
        <div class="widget-settings-loading skeleton page-edit-skeleton widget-setting-loading"></div>
        <div class="widget-settings-loading skeleton page-edit-skeleton widget-setting-loading"></div>`;

        this.rightSidebarDialog.show();

        try {
            const response = await fetch(
                u(
                    `api/pages/widgets/${encodeURIComponent(
                        widgetName,
                    )}/settings-form?settings=${encodeURIComponent(
                        widgetEl.dataset.widgetSettings || '{}',
                    )}`,
                ),
                {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN':
                            document.querySelector('meta[name="csrf-token"]')
                                ?.content || '',
                    },
                },
            );

            if (!response.ok) throw new Error('Failed to load settings form');

            const html = await response.text();
            sidebarContent.innerHTML = html;

            if (saveButton) {
                const settingsUrl = u(
                    `api/pages/widgets/${encodeURIComponent(
                        widgetName,
                    )}/save-settings`,
                );
                saveButton.setAttribute('hx-post', settingsUrl);

                const csrfToken =
                    document.querySelector('meta[name="csrf-token"]')
                        ?.content || '';
                saveButton.setAttribute(
                    'hx-headers',
                    JSON.stringify({
                        'X-CSRF-TOKEN': csrfToken,
                    }),
                );

                htmx.process(saveButton);
            }

            htmx.process(sidebarContent);

            this.dispatchWidgetEvent('widgetSettingsLoaded', {
                widgetName,
                widgetElement: widgetEl,
                settingsContainer: sidebarContent
            });
        } catch (err) {
            console.error('Failed to load widget settings:', err);
            sidebarContent.innerHTML = `<div class="alert alert-danger">${this.config.translations.errorLoading}</div>`;
        }
    }

    loadFromLocalStorage() {
        try {
            const savedData = localStorage.getItem(this.getLocalStorageKey());
            if (savedData) {
                const layoutData = JSON.parse(savedData);
                if (Array.isArray(layoutData) && layoutData.length > 0) {
                    this.loadLayoutJson(layoutData);
                    this.hasUnsavedChanges = true;
                    this.updateSaveButtonState();
                    return true;
                }
            }
        } catch (err) {
            this.logError('loadFromLocalStorage', err);
            localStorage.removeItem(this.getLocalStorageKey());
        }
        return false;
    }

    handlePageLoad() {
        if (this.history) {
            this.history.clear();
            this.updateUndoRedoButtons();
        }
        
        this.currentPath = this.getCurrentPath();
        this.localStorageKey = this.getLocalStorageKey();
    }
}

/**
 * Initialize page editor functionality
 * Sets up the editor on page load and after HTMX content swaps
 */
function initializePageEditor() {
    const editorElements = document.querySelectorAll(
        '#page-change-button, .page-edit-navbar, .page-edit-widgets-sidebar',
    );

    editorElements.forEach((el) => {
        if (el) el.removeAttribute('style');
    });

    try {
        if (!window.pageEditor) {
            console.info('Initializing page editor');

            window.pageEditor = new PageEditor({
                gridOptions: {
                    margin: 10,
                    acceptWidgets: true,
                    sizeToContent: true,
                    disableDrag: false,
                    disableResize: false,
                    animate: true,
                    cellHeight: 'auto',
                    column: 12,
                },
                widgetButtons: {
                    refresh: {
                        icon: '🔄',
                        tooltip: 'Обновить виджет',
                        onClick: (widgetEl, editor) => {
                            editor.refreshWidget(widgetEl);
                        },
                    },
                },
            });

            let resizeTimeout;
            window.addEventListener('resize', () => {
                clearTimeout(resizeTimeout);
                resizeTimeout = setTimeout(() => {
                    if (window.pageEditor && window.pageEditor.grid) {
                        try {
                            window.pageEditor.grid.cellHeight(
                                window.pageEditor.grid.cellWidth() / 2,
                            );
                        } catch (err) {
                            console.error(
                                'Error adjusting grid on resize:',
                                err,
                            );
                        }
                    }
                }, 200);
            });
        } else {
            window.pageEditor.handlePageLoad();
        }

        window.toggleEditMode = (enable, ignoreHtmx = false) => {
            if (!window.pageEditor) {
                console.error('Page editor not initialized');
                return;
            }

            enable
                ? window.pageEditor.enable()
                : window.pageEditor.disable(ignoreHtmx);
        };
    } catch (err) {
        console.error('Failed to initialize page editor:', err);

        window.toggleEditMode = () => {
            alert(
                'Page editor failed to initialize. Please refresh the page.',
            );
        };
    }

    try {
        const currentPath = window.location.pathname || '/';
        const localStorageKey = `page-layout-${currentPath}`;
        const hasUnsavedChanges = localStorage.getItem(localStorageKey);

        if (hasUnsavedChanges) {
            console.info('Unsaved changes found in local storage for path:', currentPath);

            setTimeout(() => {
                if (
                    window.pageEditor &&
                    typeof window.pageEditor.showErrorNotification ===
                    'function'
                ) {
                    window.pageEditor.showErrorNotification(
                        'You have unsaved changes. Click "Edit Page" to continue editing.',
                    );
                }
            }, 1000);
        }
    } catch (err) {
        console.error('Error checking for unsaved changes:', err);
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializePageEditor);
} else {
    initializePageEditor();
}

window.addEventListener('htmx:afterSwap', () => {
    setTimeout(initializePageEditor, 50);
});

document.addEventListener('htmx:responseError', (evt) => {
    console.error('HTMX response error:', evt.detail.error);

    if (
        window.pageEditor &&
        typeof window.pageEditor.showErrorNotification === 'function'
    ) {
        window.pageEditor.showErrorNotification(
            'Error loading content. Please try again.',
        );
    }
});

if (!AbortSignal.timeout) {
    AbortSignal.timeout = function timeout(ms) {
        const controller = new AbortController();
        setTimeout(
            () => controller.abort(new DOMException('TimeoutError')),
            ms,
        );
        return controller.signal;
    };
}