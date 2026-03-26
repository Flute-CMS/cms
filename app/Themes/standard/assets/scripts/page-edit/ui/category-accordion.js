/**
 * Category Accordion - manages category expand/collapse behavior
 */
class CategoryAccordion {
    constructor(editor) {
        this.editor = editor;
        this.config = editor.config;
        this.eventBus = window.FlutePageEdit.eventBus;
        this.utils = window.FlutePageEdit.utils;

        this.expandedCategories = new Set();
        this.allowMultiple = true; // Allow multiple categories to be open
    }

    /**
     * Initialize accordion
     */
    initialize() {
        this.loadState();
        this.setupCategories();
    }

    /**
     * Setup category headers
     */
    setupCategories() {
        const headers = document.querySelectorAll('.sidebar-category-header');

        headers.forEach(header => {
            // Remove existing handlers
            const newHeader = header.cloneNode(true);
            header.parentNode.replaceChild(newHeader, header);

            newHeader.addEventListener('click', (e) => this.handleClick(e));
        });

        // Restore saved state
        this.restoreState();

        // Open first category if none are open
        if (this.expandedCategories.size === 0) {
            const firstCategory = document.querySelector('.sidebar-category:first-child');
            if (firstCategory) {
                this.expand(firstCategory, false);
            }
        }
    }

    /**
     * Handle category header click
     * @param {Event} e - Click event
     */
    handleClick(e) {
        const header = e.currentTarget;
        const category = header.closest('.sidebar-category');
        const isExpanded = category.classList.contains('expanded');

        if (!this.allowMultiple) {
            // Close other categories first
            document.querySelectorAll('.sidebar-category.expanded').forEach(cat => {
                if (cat !== category) {
                    this.collapse(cat);
                }
            });
        }

        if (isExpanded) {
            this.collapse(category);
        } else {
            this.expand(category);
        }
    }

    /**
     * Expand a category
     * @param {Element} category - Category element
     * @param {boolean} animate - Whether to animate
     */
    expand(category, animate = true) {
        if (!category) return;

        const header = category.querySelector('.sidebar-category-header');
        const content = category.querySelector('.sidebar-category-content');
        const categoryId = header?.dataset.category;

        if (!content) return;

        category.classList.add('expanded');

        if (animate) {
            content.style.maxHeight = content.scrollHeight + 'px';

            // Animate widget items
            const widgets = content.querySelectorAll('.widget-item');
            widgets.forEach((widget, index) => {
                widget.style.opacity = '0';
                widget.style.transform = 'translateY(10px)';

                setTimeout(() => {
                    widget.style.transition = 'all 0.3s cubic-bezier(0.16, 1, 0.3, 1)';
                    widget.style.opacity = '1';
                    widget.style.transform = 'translateY(0)';
                }, 30 + index * 20);
            });

            // After animation, allow content to expand naturally
            setTimeout(() => {
                content.style.maxHeight = 'none';
            }, 300);
        } else {
            content.style.maxHeight = 'none';
        }

        if (categoryId) {
            this.expandedCategories.add(categoryId);
            this.saveState();
        }

        this.eventBus.emit(window.FlutePageEdit.events.CATEGORY_OPENED, { categoryId });
    }

    /**
     * Collapse a category
     * @param {Element} category - Category element
     * @param {boolean} animate - Whether to animate
     */
    collapse(category, animate = true) {
        if (!category) return;

        const header = category.querySelector('.sidebar-category-header');
        const content = category.querySelector('.sidebar-category-content');
        const categoryId = header?.dataset.category;

        if (!content) return;

        if (animate) {
            // Set explicit height for animation
            content.style.maxHeight = content.scrollHeight + 'px';
            // Force reflow
            content.offsetHeight;
            // Collapse
            content.style.maxHeight = '0';
        } else {
            content.style.maxHeight = '0';
        }

        category.classList.remove('expanded');

        if (categoryId) {
            this.expandedCategories.delete(categoryId);
            this.saveState();
        }

        this.eventBus.emit(window.FlutePageEdit.events.CATEGORY_CLOSED, { categoryId });
    }

    /**
     * Toggle a category
     * @param {Element} category - Category element
     */
    toggle(category) {
        if (category.classList.contains('expanded')) {
            this.collapse(category);
        } else {
            this.expand(category);
        }
    }

    /**
     * Expand all categories
     */
    expandAll() {
        document.querySelectorAll('.sidebar-category').forEach(category => {
            this.expand(category, false);
        });
    }

    /**
     * Collapse all categories
     */
    collapseAll() {
        document.querySelectorAll('.sidebar-category.expanded').forEach(category => {
            this.collapse(category, false);
        });
    }

    /**
     * Save expanded state to localStorage
     */
    saveState() {
        try {
            localStorage.setItem(
                this.config.storageKeys.categoryState,
                JSON.stringify([...this.expandedCategories])
            );
        } catch (err) {
            this.utils.logError('CategoryAccordion.saveState', err);
        }
    }

    /**
     * Load expanded state from localStorage
     */
    loadState() {
        try {
            const saved = localStorage.getItem(this.config.storageKeys.categoryState);
            if (saved) {
                const categories = JSON.parse(saved);
                this.expandedCategories = new Set(categories);
            }
        } catch (err) {
            this.utils.logError('CategoryAccordion.loadState', err);
        }
    }

    /**
     * Restore saved category states
     */
    restoreState() {
        this.expandedCategories.forEach(categoryId => {
            const header = document.querySelector(`[data-category="${categoryId}"]`);
            const category = header?.closest('.sidebar-category');
            if (category) {
                this.expand(category, false);
            }
        });
    }

    /**
     * Set whether multiple categories can be open
     * @param {boolean} allow - Whether to allow multiple
     */
    setAllowMultiple(allow) {
        this.allowMultiple = allow;

        if (!allow) {
            // Close all but the first expanded
            const expanded = document.querySelectorAll('.sidebar-category.expanded');
            if (expanded.length > 1) {
                for (let i = 1; i < expanded.length; i++) {
                    this.collapse(expanded[i]);
                }
            }
        }
    }

    /**
     * Get expanded category IDs
     * @returns {string[]}
     */
    getExpandedIds() {
        return [...this.expandedCategories];
    }

    /**
     * Check if a category is expanded
     * @param {string} categoryId - Category ID
     * @returns {boolean}
     */
    isExpanded(categoryId) {
        return this.expandedCategories.has(categoryId);
    }

    /**
     * Destroy accordion
     */
    destroy() {
        this.collapseAll();
        this.expandedCategories.clear();
    }
}

window.FlutePageEdit.register('CategoryAccordion', CategoryAccordion);
