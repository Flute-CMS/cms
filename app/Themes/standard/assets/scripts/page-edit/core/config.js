/**
 * Page Editor Configuration
 */
class PageEditorConfig {
    constructor(options = {}) {
        this.selectors = {
            editBtn: '#page-change-button',
            cancelBtn: '#page-change-cancel',
            widgetsSidebar: '.page-edit-sidebar',
            pageEditBtn: '#page-edit-button',
            pageEditFab: '#page-edit-fab',
            fabTrigger: '#page-edit-trigger',
            fabMenu: '#page-edit-menu, .page-edit-fab__ring',
            fabBackdrop: '#page-edit-backdrop',
            navbar: '.page-edit-nav',
            widgetGrid: '#widget-grid',
            searchInput: '#widget-search',
            resetBtn: '#page-edit-reset',
            undoBtn: '#page-edit-undo',
            redoBtn: '#page-edit-redo',
            saveBtn: '#page-edit-save',
            autoPositionBtn: '#page-edit-auto-position',
            heightModeToggle: '#height-mode-toggle',
            seoBtn: '#page-change-seo',
            sidebarToggle: '#sidebar-toggle',
            ...options.selectors
        };

        this.icons = {
            settings: document.getElementById('settings-widget-icon')?.innerHTML || '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 256 256"><path d="M128,80a48,48,0,1,0,48,48A48.05,48.05,0,0,0,128,80Zm0,80a32,32,0,1,1,32-32A32,32,0,0,1,128,160Z"></path></svg>',
            delete: document.getElementById('delete-widget-icon')?.innerHTML || '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 256 256"><path d="M216,48H176V40a24,24,0,0,0-24-24H104A24,24,0,0,0,80,40v8H40a8,8,0,0,0,0,16h8V208a16,16,0,0,0,16,16H192a16,16,0,0,0,16-16V64h8a8,8,0,0,0,0-16ZM96,40a8,8,0,0,1,8-8h48a8,8,0,0,1,8,8v8H96Zm96,168H64V64H192ZM112,104v64a8,8,0,0,1-16,0V104a8,8,0,0,1,16,0Zm48,0v64a8,8,0,0,1-16,0V104a8,8,0,0,1,16,0Z"></path></svg>',
            refresh: document.getElementById('refresh-widget-icon')?.innerHTML || '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 256 256"><path d="M224,48V96a8,8,0,0,1-8,8H168a8,8,0,0,1,0-16h28.69L182.06,73.37a79.56,79.56,0,0,0-56.13-23.43h-.45A79.52,79.52,0,0,0,69.59,72.71,8,8,0,0,1,58.41,61.27a96,96,0,0,1,135,.79L208,76.69V48a8,8,0,0,1,16,0ZM186.41,183.29a79.52,79.52,0,0,1-55.89,22.77h-.45a79.56,79.56,0,0,1-56.13-23.43L59.31,168H88a8,8,0,0,0,0-16H40a8,8,0,0,0-8,8v48a8,8,0,0,0,16,0V179.31l14.63,14.63a96,96,0,0,0,135,.79,8,8,0,0,0-11.18-11.44Z"></path></svg>',
            ...options.icons
        };

        this.gridOptions = {
            margin: 10,
            acceptWidgets: true,
            sizeToContent: false,
            disableDrag: false,
            disableResize: false,
            animate: true,
            cellHeight: 100,
            column: 12,
            float: true,
            minRow: 1,
            ...options.gridOptions
        };

        this.shortcuts = {
            undo: { key: 'z', ctrl: true },
            redo: { key: 'y', ctrl: true },
            save: { key: 's', ctrl: true },
            escape: { key: 'Escape' },
            ...options.shortcuts
        };

        this.translations = {
            unsavedChanges: 'You have unsaved changes. Leave without saving?',
            resetConfirm: 'Reset all changes?',
            errorLoading: 'Error loading widget',
            errorSaving: 'Error saving layout: ',
            finish: typeof translate === 'function' ? translate('def.finish') : 'Finish',
            more: typeof translate === 'function' ? translate('def.more') : 'More',
            settings: typeof translate === 'function' ? translate('def.widget_settings') : 'Settings',
            delete: typeof translate === 'function' ? translate('def.delete_widget') : 'Delete',
            refresh: typeof translate === 'function' ? translate('def.refresh_widget') : 'Refresh',
            ...options.translations
        };

        this.widgetButtons = {
            ...options.widgetButtons
        };

        // Animation settings
        this.animationDuration = options.animationDuration || 300;

        // Height calculation settings
        this.heightCalculation = {
            minHeight: 2,        // Minimum height in grid cells
            paddingPx: 20,       // Extra padding in pixels
            debounceMs: 50,      // Debounce time for height calculation
            ...options.heightCalculation
        };

        // Sidebar settings
        this.sidebar = {
            width: 300,
            mobileBreakpoint: 768,
            collapsedCategories: [],  // Will be loaded from localStorage
            ...options.sidebar
        };

        // Local storage keys
        this.storageKeys = {
            layout: 'page-layout-',
            heightMode: 'pageEditHeightMode',
            containerWidth: 'container-width-mode',
            sidebarState: 'pageEditSidebarState',
            categoryState: 'pageEditCategoryState',
            onboardingShown: 'page-edit-onboarding-shown',
            ...options.storageKeys
        };
    }

    /**
     * Get localStorage key for current path
     * @returns {string}
     */
    getLayoutStorageKey() {
        const path = window.location.pathname || '/';
        return `${this.storageKeys.layout}${path}`;
    }

    /**
     * Update configuration options
     * @param {object} options - New options
     */
    update(options) {
        if (options.selectors) {
            Object.assign(this.selectors, options.selectors);
        }
        if (options.icons) {
            Object.assign(this.icons, options.icons);
        }
        if (options.gridOptions) {
            Object.assign(this.gridOptions, options.gridOptions);
        }
        if (options.shortcuts) {
            Object.assign(this.shortcuts, options.shortcuts);
        }
        if (options.translations) {
            Object.assign(this.translations, options.translations);
        }
    }
}

window.FlutePageEdit.register('PageEditorConfig', PageEditorConfig);
window.FlutePageEdit.Config = PageEditorConfig;
