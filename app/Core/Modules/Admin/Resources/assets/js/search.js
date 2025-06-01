document.addEventListener('DOMContentLoaded', () => {
    const searchDialog = document.getElementById('search-dialog');
    if (!searchDialog) return;

    const searchInput = searchDialog.querySelector('.search-dialog__input');
    const searchTrigger = document.getElementById('search-trigger');
    const searchResults = document.getElementById('search-results');
    const commandSuggestions = document.getElementById('command-suggestions');

    let currentFocusIndex = -1;
    let isCommandMode = false;
    let activeContainer = searchResults;
    let hasShownCommandsHint = false;
    let lastCommandSearch = '';
    let isExecutingSearch = false; // Flag to prevent concurrent searches

    if (!window.htmx) {
        console.error('HTMX is not loaded');
        return;
    }

    const clearFocusedResult = (container) => {
        const focused = container.querySelector(
            '.search-result-item--focused, .command-suggestion-item--focused'
        );
        if (focused) {
            focused.classList.remove('search-result-item--focused', 'command-suggestion-item--focused');
            focused.setAttribute('aria-selected', 'false');
        }
    };

    const focusResultByIndex = (container, index) => {
        const selector = container === searchResults 
            ? '.search-result-item' 
            : '.command-suggestion-item';
        
        const items = container.querySelectorAll(selector);
        if (items.length === 0) return;

        if (index < 0) {
            index = items.length - 1;
        } else if (index >= items.length) {
            index = 0;
        }

        clearFocusedResult(container);
        const item = items[index];
        const focusClass = container === searchResults 
            ? 'search-result-item--focused' 
            : 'command-suggestion-item--focused';
        
        item.classList.add(focusClass);
        item.setAttribute('aria-selected', 'true');
        item.scrollIntoView({ block: 'nearest' });
        currentFocusIndex = index;
    };

    const openSearchDialog = () => {
        searchDialog.classList.add('showing');
        setTimeout(() => {
            searchDialog.classList.remove('showing');
        }, 10);

        searchResults.classList.add('search-results--hidden');
        commandSuggestions.classList.add('search-results--hidden');
        searchResults.innerHTML = '';
        commandSuggestions.innerHTML = '';
        currentFocusIndex = -1;
        isCommandMode = false;
        activeContainer = searchResults;
        hasShownCommandsHint = false;
        lastCommandSearch = '';
        isExecutingSearch = false;

        searchInput.value = '';
        searchInput.setAttribute('aria-expanded', 'false');

        openModal('search-dialog');
        searchInput.focus();
    };

    if (searchTrigger) {
        searchTrigger.addEventListener('click', openSearchDialog);
    }

    const checkForSlashCommand = (value) => {
        const containsSpace = value.includes(' ');
        
        if (value.startsWith('/')) {
            if (containsSpace && !searchResults.classList.contains('search-results--hidden') && 
                searchResults.querySelector('.search-result-item')) {
                return false;
            }
            
            isCommandMode = true;
            activeContainer = commandSuggestions;
            
            if (value !== lastCommandSearch) {
                lastCommandSearch = value;
                fetchSlashCommands(value);
            }
            return true;
        } else {
            isCommandMode = false;
            activeContainer = searchResults;
            commandSuggestions.classList.add('search-results--hidden');
            
            if (!hasShownCommandsHint && value.length === 1) {
                showCommandsHint();
                hasShownCommandsHint = true;
            }
            
            return false;
        }
    };

    const showCommandsHint = () => {
        const hintHtml = `
            <ul class="command-suggestions-list">
                <li class="command-suggestion-item" data-command="/" data-tooltip="${translate('search.available_commands')}">
                    <span class="command-name">/</span>
                </li>
            </ul>`;

        commandSuggestions.innerHTML = hintHtml;
        commandSuggestions.classList.remove('search-results--hidden');
        commandSuggestions.classList.add('fade-in');
    };

    const fetchSlashCommands = (query = '/') => {
        if (commandSuggestions.classList.contains('search-results--hidden')) {
            commandSuggestions.classList.remove('search-results--hidden');
            commandSuggestions.classList.add('fade-in');
            searchInput.setAttribute('aria-expanded', 'true');
        }

        if (query.includes(' ') && !searchResults.classList.contains('search-results--hidden') && 
            searchResults.querySelector('.search-result-item')) {
            commandSuggestions.classList.add('search-results--hidden');
            return;
        }

        htmx.ajax('GET', `/admin/search/commands?query=${encodeURIComponent(query)}`, {
            target: '#command-suggestions',
            swap: 'innerHTML'
        });
    };

    const selectCommand = (command) => {
        searchInput.value = command;
        commandSuggestions.classList.add('search-results--hidden');
        isCommandMode = true;
        activeContainer = searchResults;
        
        const event = new Event('keyup');
        searchInput.dispatchEvent(event);
        
        searchInput.focus();
    };

    const performSearch = (value) => {
        if (isExecutingSearch) return;
        
        isExecutingSearch = true;
        
        if (searchInput.searchTimer) {
            clearTimeout(searchInput.searchTimer);
        }

        searchInput.searchTimer = setTimeout(() => {
            if (value.length > 0) {
                if (!checkForSlashCommand(value)) {
                    if (searchResults.classList.contains('search-results--hidden')) {
                        searchResults.classList.remove('search-results--hidden');
                        searchResults.classList.add('fade-in');
                        searchInput.setAttribute('aria-expanded', 'true');
                    }
                }
            } else {
                searchResults.classList.add('search-results--hidden');
                commandSuggestions.classList.add('search-results--hidden');
                searchResults.innerHTML = '';
                commandSuggestions.innerHTML = '';
                searchInput.setAttribute('aria-expanded', 'false');
                currentFocusIndex = -1;
                hasShownCommandsHint = false;
                lastCommandSearch = '';
            }
            isExecutingSearch = false;
        }, 200);
    };

    searchInput.addEventListener('input', (e) => {
        const value = searchInput.value.trim();
        performSearch(value);
    });

    searchDialog.addEventListener('click', (e) => {
        if (e.target.classList.contains('modal__container')) {
            closeModal('search-dialog');
        } 
        
        const commandItem = e.target.closest('.command-suggestion-item');
        if (commandItem) {
            const command = commandItem.dataset.command;
            selectCommand(command);
        }
    });

    searchInput.addEventListener('keydown', (e) => {
        const hasCommandItems = commandSuggestions.querySelectorAll('.command-suggestion-item').length > 0;
        const isCommandVisible = !commandSuggestions.classList.contains('search-results--hidden');
        
        if (isCommandMode && hasCommandItems && isCommandVisible) {
            activeContainer = commandSuggestions;
        } else {
            activeContainer = searchResults;
        }

        const selector = activeContainer === searchResults 
            ? '.search-result-item' 
            : '.command-suggestion-item';
            
        const items = activeContainer.querySelectorAll(selector);
        if (items.length === 0) return;

        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                if (currentFocusIndex < items.length - 1) {
                    focusResultByIndex(activeContainer, currentFocusIndex + 1);
                } else {
                    focusResultByIndex(activeContainer, 0);
                }
                break;
            case 'ArrowUp':
                e.preventDefault();
                if (currentFocusIndex > 0) {
                    focusResultByIndex(activeContainer, currentFocusIndex - 1);
                } else {
                    focusResultByIndex(activeContainer, items.length - 1);
                }
                break;
            case 'Enter':
                e.preventDefault();
                if (activeContainer === commandSuggestions && currentFocusIndex > -1) {
                    const focusedItem = activeContainer.querySelector(
                        '.command-suggestion-item--focused'
                    );
                    if (focusedItem) {
                        selectCommand(focusedItem.dataset.command);
                    }
                } else if (currentFocusIndex > -1) {
                    const focusedItem = activeContainer.querySelector(
                        '.search-result-item--focused a'
                    );
                    if (focusedItem) {
                        focusedItem.click();
                        closeModal('search-dialog');
                    }
                } else {
                    const firstLink = activeContainer.querySelector(
                        '.search-result-item a'
                    );
                    if (firstLink) {
                        firstLink.click();
                        closeModal('search-dialog');
                    }
                }
                break;
            case 'Tab':
                if (activeContainer === commandSuggestions && currentFocusIndex > -1) {
                    e.preventDefault();
                    const focusedItem = activeContainer.querySelector(
                        '.command-suggestion-item--focused'
                    );
                    if (focusedItem) {
                        selectCommand(focusedItem.dataset.command);
                    }
                }
                break;
            case 'Escape':
                closeModal('search-dialog');
                break;
            default:
                break;
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.ctrlKey && e.code === 'KeyK') {
            e.preventDefault();
            openSearchDialog();
        }
    });

    document.addEventListener('htmx:afterSwap', (e) => {
        if (e.detail.target.getAttribute('id') === 'search-results') {
            if (searchInput.value.trim().length === 0) {
                searchResults.classList.add('search-results--hidden');
                searchResults.innerHTML = '';
                searchInput.setAttribute('aria-expanded', 'false');
                currentFocusIndex = -1;
            } else {
                clearFocusedResult(searchResults);
                const firstResult = searchResults.querySelector(
                    '.search-result-item a'
                );
                if (firstResult) {
                    const parentItem = firstResult.parentElement;
                    parentItem.classList.add('search-result-item--focused');
                    firstResult.setAttribute('aria-selected', 'true');
                    currentFocusIndex = 0;
                }
                
                if (searchResults.querySelector('.search-result-item') && 
                    !commandSuggestions.classList.contains('search-results--hidden')) {
                    commandSuggestions.classList.add('search-results--hidden');
                }
            }
        }

        if (e.detail.target.getAttribute('id') === 'command-suggestions') {
            const firstCommand = commandSuggestions.querySelector(
                '.command-suggestion-item'
            );
            
            if (commandSuggestions.querySelector('.search-results-list')) {
                searchResults.innerHTML = commandSuggestions.innerHTML;
                commandSuggestions.innerHTML = '';
                commandSuggestions.classList.add('search-results--hidden');
                searchResults.classList.remove('search-results--hidden');
                searchResults.classList.add('fade-in');
                
                const firstResult = searchResults.querySelector('.search-result-item a');
                if (firstResult) {
                    const parentItem = firstResult.parentElement;
                    parentItem.classList.add('search-result-item--focused');
                    firstResult.setAttribute('aria-selected', 'true');
                    currentFocusIndex = 0;
                }
                
                if (searchInput.value.includes(' ')) {
                    isCommandMode = false;
                    activeContainer = searchResults;
                }
            } else if (firstCommand) {
                clearFocusedResult(commandSuggestions);
                firstCommand.classList.add('command-suggestion-item--focused');
                firstCommand.setAttribute('aria-selected', 'true');
                currentFocusIndex = 0;
            }
        }

        if (e.detail.target.getAttribute('id') === 'main') {
            closeModal('search-dialog');
        }
    });
});
