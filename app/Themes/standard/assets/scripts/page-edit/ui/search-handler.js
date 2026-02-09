/**
 * Search Handler - manages widget search functionality
 */
class SearchHandler {
    constructor(editor) {
        this.editor = editor;
        this.config = editor.config;
        this.eventBus = window.FlutePageEdit.eventBus;
        this.utils = window.FlutePageEdit.utils;

        this.searchInput = null;
        this.searchTimeout = null;
        this.lastSearchTerm = '';
        this._inputHandler = null;
        this._inputKeydownHandler = null;
        this._docKeydownHandler = null;
    }

    bindOnce(el, type, handler, key = 'default', options) {
        if (!el) return;
        el._pe = el._pe || {};
        const mark = `search-handler:${type}:${key}`;
        if (el._pe[mark]) return;
        el.addEventListener(type, handler, options);
        el._pe[mark] = true;
    }

    /**
     * Initialize search handler
     */
    initialize() {
        this.searchInput = document.querySelector(this.config.selectors.searchInput);

        if (!this.searchInput) return;

        this.setupEventListeners();
    }

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Debounced search
        if (!this._inputHandler) {
            this._inputHandler = (e) => {
                clearTimeout(this.searchTimeout);
                this.searchTimeout = setTimeout(() => {
                    this.handleSearch(e.target.value);
                }, 180);
            };
        }
        this.bindOnce(this.searchInput, 'input', this._inputHandler, 'input');

        // Clear on Escape
        if (!this._inputKeydownHandler) {
            this._inputKeydownHandler = (e) => {
                if (e.key === 'Escape') {
                    this.clear();
                    this.searchInput.blur();
                }
            };
        }
        this.bindOnce(this.searchInput, 'keydown', this._inputKeydownHandler, 'input-escape');

        // Focus on Ctrl+F (when editor is active)
        if (!this._docKeydownHandler) {
            this._docKeydownHandler = (e) => {
                if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
                    if (document.body.classList.contains('page-edit-mode')) {
                        e.preventDefault();
                        this.focus();
                    }
                }
            };
        }
        this.bindOnce(document, 'keydown', this._docKeydownHandler, 'doc-ctrl-f');
    }

    /**
     * Handle search
     * @param {string} term - Search term
     */
    handleSearch(term) {
        const searchTerm = term.toLowerCase().trim();
        this.lastSearchTerm = searchTerm;

        const categories = document.querySelectorAll('.sidebar-category, .pe-sidebar__category');

        categories.forEach(category => {
            const widgets = category.querySelectorAll('.widget-item, .pe-widget-card');
            let hasVisibleWidgets = false;

            widgets.forEach(widget => {
                const name = widget.textContent.toLowerCase();
                const widgetKey = widget.dataset.widgetName?.toLowerCase() || '';
                const isVisible = !searchTerm || name.includes(searchTerm) || widgetKey.includes(searchTerm);

                widget.style.display = isVisible ? '' : 'none';

                if (isVisible) {
                    hasVisibleWidgets = true;

                    // Highlight matching text
                    if (searchTerm) {
                        this.highlightMatch(widget, searchTerm);
                    } else {
                        this.removeHighlight(widget);
                    }
                }
            });

            // Show/hide category
            category.style.display = hasVisibleWidgets ? '' : 'none';

            // Expand categories with results when searching
            if (searchTerm && hasVisibleWidgets) {
                category.classList.add('expanded');
                const content = category.querySelector('.sidebar-category-content');
                if (content) {
                    content.style.maxHeight = 'none';
                }
            }
        });

        // Update no results message
        this.updateNoResultsMessage(searchTerm);

        this.eventBus.emit(window.FlutePageEdit.events.SEARCH_PERFORMED, {
            searchTerm,
            hasResults: document.querySelectorAll('.widget-item:not([style*="display: none"])').length > 0
        });
    }

    /**
     * Highlight matching text in widget name
     * @param {Element} widget - Widget element
     * @param {string} term - Search term
     */
    highlightMatch(widget, term) {
        const nameEl = widget.querySelector('span') || widget.querySelector('p');
        if (!nameEl) return;

        const originalText = nameEl.dataset.originalText || nameEl.textContent;
        nameEl.dataset.originalText = originalText;

        const escaped = originalText.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        const regex = new RegExp(`(${this.escapeRegex(term)})`, 'gi');
        nameEl.innerHTML = escaped.replace(regex, '<mark>$1</mark>');
    }

    /**
     * Remove highlight from widget
     * @param {Element} widget - Widget element
     */
    removeHighlight(widget) {
        const nameEl = widget.querySelector('span') || widget.querySelector('p');
        if (!nameEl || !nameEl.dataset.originalText) return;

        nameEl.textContent = nameEl.dataset.originalText;
    }

    /**
     * Update no results message
     * @param {string} searchTerm - Current search term
     */
    updateNoResultsMessage(searchTerm) {
        const sidebar = document.querySelector('.pe-sidebar') || document.querySelector('.page-edit-sidebar');
        if (!sidebar) return;

        let noResults = sidebar.querySelector('.search-no-results');
        const hasResults = document.querySelectorAll('.sidebar-category:not([style*="display: none"]), .pe-sidebar__category:not([style*="display: none"])').length > 0;

        if (searchTerm && !hasResults) {
            if (!noResults) {
                noResults = document.createElement('div');
                noResults.className = 'search-no-results';
                noResults.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 256 256">
                        <path fill="currentColor" d="M229.66,218.34l-50.07-50.06a88.11,88.11,0,1,0-11.31,11.31l50.06,50.07a8,8,0,0,0,11.32-11.32ZM40,112a72,72,0,1,1,72,72A72.08,72.08,0,0,1,40,112Z"/>
                    </svg>
                    <p>${typeof translate === 'function' ? translate('def.no_results') : 'No results found'}</p>
                `;
                (sidebar.querySelector('.pe-sidebar__content') || sidebar.querySelector('.page-edit-sidebar__categories'))?.appendChild(noResults);
            }
            noResults.style.display = 'flex';
        } else if (noResults) {
            noResults.style.display = 'none';
        }
    }

    /**
     * Focus the search input
     */
    focus() {
        this.searchInput?.focus();
    }

    /**
     * Clear the search
     */
    clear() {
        if (this.searchInput) {
            this.searchInput.value = '';
        }
        this.handleSearch('');
    }

    /**
     * Get current search term
     * @returns {string}
     */
    getCurrentTerm() {
        return this.lastSearchTerm;
    }

    /**
     * Escape regex special characters
     * @param {string} string - String to escape
     * @returns {string}
     */
    escapeRegex(string) {
        return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    /**
     * Destroy handler
     */
    destroy() {
        clearTimeout(this.searchTimeout);
        this.clear();
    }
}

window.FlutePageEdit.register('SearchHandler', SearchHandler);
