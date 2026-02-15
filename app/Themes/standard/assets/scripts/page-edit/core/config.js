/**
 * Page Editor Configuration
 */
class PageEditorConfig {
    constructor(options = {}) {
        this.selectors = {
            editBtn: '#page-change-button',
            cancelBtn: '#page-change-cancel',
            widgetsSidebar: '#page-edit-sidebar, .pe-dock, .pe-sidebar',
            pageEditBtn: '#page-edit-button',
            pageEditFab: '#page-edit-fab',
            fabTrigger: '#page-edit-trigger',
            fabMenu: '#page-edit-menu, .page-edit-fab__ring',
            fabBackdrop: '#page-edit-backdrop',
            navbar: '#page-edit-nav, .pe-topbar',
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

        // Icons — read pre-rendered blade <x-icon> from #widget-toolbar-icons template
        const iconsTpl = document.getElementById('widget-toolbar-icons');
        this.icons = {
            settings: iconsTpl?.querySelector('[data-icon="settings"]')?.innerHTML || '',
            delete: iconsTpl?.querySelector('[data-icon="delete"]')?.innerHTML || '',
            refresh: iconsTpl?.querySelector('[data-icon="refresh"]')?.innerHTML || '',
            drag: iconsTpl?.querySelector('[data-icon="drag"]')?.innerHTML || '',
            ...options.icons
        };

        this.gridOptions = {
            column: 12,
            cellHeight: 10,
            float: false,
            animate: true,
            margin: 0,
            sizeToContent: true,
            acceptWidgets: true,
            removable: false,
            disableOneColumnMode: true,
            minRow: 1,
            resizable: { handles: 'e,w' },
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
