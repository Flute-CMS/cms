class AdminSearch {
    static instance = null;
    static CONFIG = {
        DEBOUNCE_MS: 200,
        ENDPOINTS: {
            SEARCH: '/admin/search',
            COMMANDS: '/admin/search/commands',
        },
    };

    constructor() {
        if (AdminSearch.instance) return AdminSearch.instance;
        AdminSearch.instance = this;

        this.elements = {};
        this.state = {
            isOpen: false,
            isLoading: false,
            query: '',
            focusIndex: -1,
            isCommandMode: false,
            abortController: null,
        };

        this.debounceTimer = null;
        this.init();
    }

    init() {
        this.cacheElements();
        if (!this.elements.dialog) return;
        this.bindEvents();
    }

    cacheElements() {
        this.elements = {
            dialog: document.getElementById('search-dialog'),
            input: document.querySelector('#search-dialog .search-dialog__input'),
            form: document.querySelector('#search-dialog .search-dialog__form'),
            spinner: document.getElementById('search-spinner'),
            escButton: document.querySelector('#search-dialog [data-close-search]'),
            resultsContainer: document.getElementById('search-results'),
            commandsContainer: document.getElementById('command-suggestions'),
            emptyState: document.getElementById('search-empty'),
            body: document.querySelector('#search-dialog .search-dialog__body'),
            trigger: document.getElementById('search-trigger'),
            sidebarTrigger: document.getElementById('sidebar-search-trigger'),
        };
    }

    bindEvents() {
        this.elements.form?.addEventListener('submit', (e) => e.preventDefault());
        this.elements.input?.addEventListener('input', () => this.handleInput());
        this.elements.input?.addEventListener('keydown', (e) => this.handleKeydown(e));
        this.elements.escButton?.addEventListener('click', () => this.close());
        this.elements.trigger?.addEventListener('click', () => this.open());
        this.elements.sidebarTrigger?.addEventListener('click', () => this.open());

        document.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                this.open();
            }
        });

        this.elements.dialog?.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal__container')) {
                this.close();
            }
        });

        this.elements.emptyState?.addEventListener('click', (e) => {
            const tip = e.target.closest('[data-search-tip]');
            if (tip) {
                this.elements.input.value = tip.dataset.searchTip + ' ';
                this.elements.input.focus();
                this.handleInput();
            }
        });

        this.elements.body?.addEventListener('click', (e) => {
            const commandItem = e.target.closest('.search-command-item');
            if (commandItem) {
                e.preventDefault();
                this.selectCommand(commandItem.dataset.command);
                return;
            }

            const resultLink = e.target.closest('.search-result-item a');
            if (resultLink) {
                e.preventDefault();
                this.navigateTo(resultLink.href);
            }
        });
    }

    open() {
        this.cacheElements();
        this.reset();
        if (typeof openModal === 'function') openModal('search-dialog');
        this.state.isOpen = true;
        requestAnimationFrame(() => this.elements.input?.focus());
    }

    close() {
        if (typeof closeModal === 'function') closeModal('search-dialog');
        this.state.isOpen = false;
        this.cancelPendingRequest();
    }

    reset() {
        this.state.query = '';
        this.state.focusIndex = -1;
        this.state.isCommandMode = false;
        this.state.isLoading = false;

        if (this.elements.input) this.elements.input.value = '';

        this.hideResults();
        this.hideCommands();
        this.showEmptyState();
        this.hideSpinner();
    }

    handleInput() {
        const query = this.elements.input?.value.trim() || '';

        if (query === this.state.query) return;
        this.state.query = query;

        if (this.debounceTimer) clearTimeout(this.debounceTimer);

        if (query.length === 0) {
            this.reset();
            if (this.elements.input) this.elements.input.value = '';
            return;
        }

        this.state.isCommandMode = query.startsWith('/');

        this.debounceTimer = setTimeout(() => {
            const endpoint = this.state.isCommandMode
                ? AdminSearch.CONFIG.ENDPOINTS.COMMANDS
                : AdminSearch.CONFIG.ENDPOINTS.SEARCH;
            this.fetchHTML(endpoint, query);
        }, AdminSearch.CONFIG.DEBOUNCE_MS);
    }

    handleKeydown(event) {
        const { key } = event;
        const items = this.getNavigableItems();
        const totalItems = items.length;

        switch (key) {
            case 'ArrowDown':
                event.preventDefault();
                if (totalItems > 0) {
                    this.state.focusIndex = (this.state.focusIndex + 1) % totalItems;
                    this.updateFocus(items);
                }
                break;

            case 'ArrowUp':
                event.preventDefault();
                if (totalItems > 0) {
                    this.state.focusIndex = this.state.focusIndex <= 0 ? totalItems - 1 : this.state.focusIndex - 1;
                    this.updateFocus(items);
                }
                break;

            case 'Enter':
                event.preventDefault();
                this.selectFocusedItem(items);
                break;

            case 'Tab':
                if (this.state.isCommandMode && this.state.focusIndex >= 0) {
                    event.preventDefault();
                    const focused = items[this.state.focusIndex];
                    if (focused?.dataset?.command) this.selectCommand(focused.dataset.command);
                }
                break;

            case 'Escape':
                event.preventDefault();
                this.close();
                break;
        }
    }

    getNavigableItems() {
        const commandItems = this.elements.commandsContainer?.querySelectorAll('.search-command-item') || [];
        const resultItems = this.elements.resultsContainer?.querySelectorAll('.search-result-item') || [];

        if (commandItems.length > 0 && !this.elements.commandsContainer?.classList.contains('search-section--hidden')) {
            return Array.from(commandItems);
        }
        return Array.from(resultItems);
    }

    updateFocus(items) {
        items.forEach((item, index) => {
            const isCommand = item.classList.contains('search-command-item');
            const focusClass = isCommand ? 'search-command-item--focused' : 'search-result-item--focused';

            if (index === this.state.focusIndex) {
                item.classList.add(focusClass);
                item.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            } else {
                item.classList.remove(focusClass);
            }
        });
    }

    selectFocusedItem(items) {
        if (this.state.focusIndex < 0 && items.length > 0) this.state.focusIndex = 0;
        if (this.state.focusIndex < 0 || this.state.focusIndex >= items.length) return;

        const focused = items[this.state.focusIndex];
        if (!focused) return;

        if (focused.classList.contains('search-command-item')) {
            this.selectCommand(focused.dataset.command);
        } else {
            const link = focused.querySelector('a');
            if (link) this.navigateTo(link.href);
        }
    }

    selectCommand(command) {
        if (!command) return;

        this.elements.input.value = command + ' ';
        this.state.query = command + ' ';
        this.state.isCommandMode = false;
        this.state.focusIndex = -1;

        this.hideCommands();
        this.elements.input.focus();

        setTimeout(() => this.fetchHTML(AdminSearch.CONFIG.ENDPOINTS.SEARCH, this.state.query), 50);
    }

    navigateTo(url) {
        this.close();

        if (window.htmx) {
            htmx.ajax('GET', url, { target: '#main', swap: 'outerHTML transition:true' });
        } else {
            window.location.href = url;
        }
    }

    async fetchHTML(endpoint, query) {
        this.cancelPendingRequest();
        this.showSpinner();

        try {
            this.state.abortController = new AbortController();

            const response = await fetch(`${endpoint}?query=${encodeURIComponent(query)}`, {
                method: 'GET',
                headers: { 'Accept': 'text/html', 'X-Requested-With': 'XMLHttpRequest' },
                signal: this.state.abortController.signal,
            });

            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

            const html = await response.text();

            if (this.state.query === query || this.state.query.startsWith(query)) {
                this.renderHTML(html);
            }
        } catch (error) {
            if (error.name !== 'AbortError') {
                console.error('Search error:', error);
            }
        } finally {
            this.hideSpinner();
        }
    }

    renderHTML(html) {
        this.hideEmptyState();

        const hasCommands = html.includes('search-command-item');
        const hasResults = html.includes('search-result-item') || html.includes('search-group') || html.includes('search-no-results');
        const isTips = html.includes('search-tips__item');

        if (isTips) {
            this.showEmptyState();
            this.hideResults();
            this.hideCommands();
            return;
        }

        if (hasCommands) {
            this.elements.commandsContainer.innerHTML = html;
            this.elements.commandsContainer.classList.remove('search-section--hidden');
            this.elements.commandsContainer.classList.add('search-fade-in');
            this.hideResults();
        } else if (hasResults) {
            this.elements.resultsContainer.innerHTML = html;
            this.elements.resultsContainer.classList.remove('search-section--hidden');
            this.elements.resultsContainer.classList.add('search-fade-in');
            this.hideCommands();
        }

        this.state.focusIndex = 0;
        this.updateFocus(this.getNavigableItems());
    }

    cancelPendingRequest() {
        if (this.state.abortController) {
            this.state.abortController.abort();
            this.state.abortController = null;
        }
    }

    showSpinner() {
        this.state.isLoading = true;
        this.elements.spinner?.classList.add('yoyo-request');
    }

    hideSpinner() {
        this.state.isLoading = false;
        this.elements.spinner?.classList.remove('yoyo-request');
    }

    showEmptyState() {
        if (this.elements.emptyState) this.elements.emptyState.style.display = '';
    }

    hideEmptyState() {
        if (this.elements.emptyState) this.elements.emptyState.style.display = 'none';
    }

    hideResults() {
        if (this.elements.resultsContainer) {
            this.elements.resultsContainer.classList.add('search-section--hidden');
            this.elements.resultsContainer.innerHTML = '';
        }
    }

    hideCommands() {
        if (this.elements.commandsContainer) {
            this.elements.commandsContainer.classList.add('search-section--hidden');
            this.elements.commandsContainer.innerHTML = '';
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.adminSearch = new AdminSearch();
});
