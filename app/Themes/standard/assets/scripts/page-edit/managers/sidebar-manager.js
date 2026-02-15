/**
 * Sidebar Manager - handles the right sidebar with widget categories
 */
class SidebarManager {
    constructor(editor) {
        this.editor = editor;
        this.config = editor.config;
        this.eventBus = window.FlutePageEdit.eventBus;
        this.utils = window.FlutePageEdit.utils;

        this.sidebar = null;
        this.searchInput = null;
        this.categoriesContainer = null;
        this.isOpen = false;
        this.isMobile = false;
        this.expandedCategories = new Set();
        this._resizeHandler = this.utils.debounce(() => {
            this.checkMobileState();
        }, 200);
        this._escapeHandler = (e) => {
            if (e.key === 'Escape' && this.isOpen && this.isMobile) {
                this.close();
            }
        };
        this._searchHandler = null;
        this._searchKeydownHandler = null;
        this._clearClickHandler = null;
        this._dragInSetup = false;

        this.loadCategoryState();
    }

    bindOnce(el, type, handler, key = 'default', options) {
        if (!el) return;
        el._pe = el._pe || {};
        const mark = `sidebar-manager:${type}:${key}`;
        if (el._pe[mark]) return;
        el.addEventListener(type, handler, options);
        el._pe[mark] = true;
    }

    /**
     * Initialize the sidebar
     */
    initialize() {
        this.sidebar = document.getElementById('page-edit-sidebar') || document.querySelector('.pe-dock') || document.querySelector('.pe-sidebar');
        this.searchInput = document.getElementById('widget-search');
        this.categoriesContainer = this.sidebar?.querySelector('.pe-dock__content') || this.sidebar?.querySelector('.page-edit-sidebar__categories') || this.sidebar?.querySelector('.pe-sidebar__content');

        if (!this.sidebar) {
            this.utils.logError('SidebarManager', 'Sidebar element not found');
            return;
        }

        this.checkMobileState();
        this.setupEventListeners();
        this.setupCategories();
        this.setupSearch();
        this.setupNativeDrag();

        // Open first category by default if no saved state
        if (this.expandedCategories.size === 0) {
            const firstCategory = this.sidebar.querySelector('.sidebar-category');
            if (firstCategory) {
                this.toggleCategory(firstCategory, true, false);
            }
        }
    }

    /**
     * Check if we're in mobile mode
     */
    checkMobileState() {
        this.isMobile = window.innerWidth < (this.config.sidebar?.mobileBreakpoint || 768);
    }

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Window resize
        this.bindOnce(window, 'resize', this._resizeHandler, 'resize');

        // Close on escape
        this.bindOnce(document, 'keydown', this._escapeHandler, 'escape');

        // Mobile backdrop click
        const backdrop = this.sidebar?.querySelector('.page-edit-sidebar__backdrop') || document.getElementById('pe-sidebar-overlay');
        if (backdrop) {
            this.bindOnce(backdrop, 'click', () => this.close(), 'backdrop');
        }
    }

    /**
     * Setup category accordion behavior
     */
    setupCategories() {
        const categoryHeaders = this.sidebar?.querySelectorAll('.sidebar-category-header');
        if (!categoryHeaders) return;

        categoryHeaders.forEach(header => {
            this.bindOnce(header, 'click', (e) => {
                e.preventDefault();
                const category = header.closest('.sidebar-category');
                const isExpanded = category.classList.contains('expanded');
                this.toggleCategory(category, !isExpanded);
            }, 'category-toggle');
        });

        // Restore saved category states
        this.expandedCategories.forEach(categoryId => {
            const category = this.sidebar.querySelector(`.sidebar-category[data-category="${categoryId}"]`);
            if (category) {
                this.toggleCategory(category, true, false);
            }
        });
    }

    /**
     * Toggle a category open/closed
     */
    toggleCategory(category, expand, animate = true) {
        if (!category) return;

        const content = category.querySelector('.sidebar-category-content');
        const categoryId = category.dataset.category;

        if (!content) return;

        if (expand) {
            category.classList.add('expanded');

            if (categoryId) {
                this.expandedCategories.add(categoryId);
            }

            this.eventBus.emit(window.FlutePageEdit.events.CATEGORY_OPENED, { categoryId });
        } else {
            category.classList.remove('expanded');

            if (categoryId) {
                this.expandedCategories.delete(categoryId);
            }

            this.eventBus.emit(window.FlutePageEdit.events.CATEGORY_CLOSED, { categoryId });
        }

        this.saveCategoryState();
    }

    /**
     * Setup search functionality
     */
    setupSearch() {
        if (!this.searchInput) return;

        const clearBtn = this.sidebar?.querySelector('.page-edit-sidebar__search-clear') || this.sidebar?.querySelector('.pe-sidebar__search-clear');

        if (!this._searchHandler) {
            this._searchHandler = this.utils.debounce((e) => {
                this.filterWidgets(e.target.value);
            }, 150);
        }

        this.bindOnce(this.searchInput, 'input', this._searchHandler, 'search-input');

        if (!this._searchKeydownHandler) {
            this._searchKeydownHandler = (e) => {
                if (e.key === 'Escape') {
                    this.searchInput.value = '';
                    this.filterWidgets('');
                    this.searchInput.blur();
                }
            };
        }

        this.bindOnce(this.searchInput, 'keydown', this._searchKeydownHandler, 'search-escape');

        if (clearBtn) {
            if (!this._clearClickHandler) {
                this._clearClickHandler = () => {
                    this.searchInput.value = '';
                    this.filterWidgets('');
                    this.searchInput.focus();
                };
            }
            this.bindOnce(clearBtn, 'click', this._clearClickHandler, 'search-clear');
        }
    }

    /**
     * Filter widgets by search term
     */
    filterWidgets(searchTerm) {
        const term = searchTerm.toLowerCase().trim();
        const categories = this.sidebar?.querySelectorAll('.sidebar-category');

        categories?.forEach(category => {
            const widgets = category.querySelectorAll('.widget-item');
            let hasVisibleWidgets = false;

            widgets.forEach(widget => {
                const name = widget.querySelector('.widget-item__name')?.textContent?.toLowerCase() || '';
                const widgetKey = widget.dataset.widgetName?.toLowerCase() || '';
                const isVisible = !term || name.includes(term) || widgetKey.includes(term);

                widget.style.display = isVisible ? '' : 'none';
                if (isVisible) hasVisibleWidgets = true;
            });

            category.style.display = hasVisibleWidgets ? '' : 'none';

            // Expand categories with matches when searching
            if (term && hasVisibleWidgets) {
                this.toggleCategory(category, true, false);
            }
        });

        this.eventBus.emit(window.FlutePageEdit.events.SEARCH_PERFORMED, { searchTerm: term });
    }

    /**
     * Setup drag for sidebar widgets.
     * GridStack handles drag-in via setupDragIn() in the grid controller,
     * so we only need minimal setup here.
     */
    setupNativeDrag() {
        // GridStack handles sidebar drag-in natively via GridStack.setupDragIn()
        // No native HTML5 drag setup needed
    }

    /**
     * Open the sidebar
     */
    open() {
        if (!this.sidebar) return;

        this.sidebar.classList.add('active');
        this.isOpen = true;

        if (this.isMobile) {
            document.body.classList.add('sidebar-open');
        }

        this.eventBus.emit(window.FlutePageEdit.events.SIDEBAR_OPENED);
    }

    /**
     * Close the sidebar
     */
    close() {
        if (!this.sidebar) return;

        this.sidebar.classList.remove('active');
        this.isOpen = false;

        if (this.isMobile) {
            document.body.classList.remove('sidebar-open');
        }

        this.eventBus.emit(window.FlutePageEdit.events.SIDEBAR_CLOSED);
    }

    /**
     * Toggle the sidebar
     */
    toggle() {
        this.isOpen ? this.close() : this.open();
    }

    /**
     * Save category expansion state
     */
    saveCategoryState() {
        try {
            const key = this.config.storageKeys?.categoryState || 'page-edit-categories';
            localStorage.setItem(key, JSON.stringify([...this.expandedCategories]));
        } catch (err) {
            // Ignore localStorage errors
        }
    }

    /**
     * Load category expansion state
     */
    loadCategoryState() {
        try {
            const key = this.config.storageKeys?.categoryState || 'page-edit-categories';
            const saved = localStorage.getItem(key);
            if (saved) {
                this.expandedCategories = new Set(JSON.parse(saved));
            }
        } catch (err) {
            // Ignore localStorage errors
        }
    }

    /**
     * Focus the search input
     */
    focus() {
        this.searchInput?.focus();
    }

    /**
     * Destroy the sidebar manager
     */
    destroy() {
        this.close();
        this.expandedCategories.clear();
    }
}

window.FlutePageEdit.register('SidebarManager', SidebarManager);
