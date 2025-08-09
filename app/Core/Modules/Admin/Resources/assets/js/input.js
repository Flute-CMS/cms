$(document).ready(function () {
    let iconPicker = null;
    let cleanup = null;
    const iconPacksData = {};
    const categorizedPacks = {};
    const lastCategoryByInput = new WeakMap();
    const iconCache = getIconCache();

    initIconPickers();

    document.body.addEventListener('htmx:afterSettle', function () {
        initIconPickers();
    });

    function getIconCache() {
        try {
            return JSON.parse(localStorage.getItem('iconCache')) || {};
        } catch (e) {
            return {};
        }
    }

    function saveIconCache() {
        try {
            const currentCache = { ...iconCache };
            const cacheSize = JSON.stringify(currentCache).length;
            if (cacheSize > 5 * 1024 * 1024) {
                const keys = Object.keys(currentCache);
                for (let i = 0; i < keys.length / 2; i++) {
                    delete currentCache[keys[i]];
                }
            }
            localStorage.setItem('iconCache', JSON.stringify(currentCache));
        } catch (e) {
            console.warn('Не удалось сохранить кеш иконок:', e);
        }
    }

    function initIconPickers() {
        const iconInputs = document.querySelectorAll('.input__field-icon');

        if (!iconInputs.length) return;

        iconInputs.forEach(function (input) {
            if (input.hasIconPickerInitialized) return;

            input.hasIconPickerInitialized = true;

            const iconPacks = JSON.parse(input.dataset.iconPacks || '[]');

            // Auto-opening picker on focus prevented to allow manual SVG input.

            input.addEventListener('input', function () {
                const event = new Event('change', { bubbles: true });
                input.dispatchEvent(event);

                if (!this.value) {
                    const container = this.closest('.icon-input-container');
                    if (container) {
                        const preview = container.querySelector('.icon-input-preview');
                        if (preview) {
                            preview.innerHTML = '';
                        }
                    }
                }
            });

            const container = input.closest('.icon-input-container');
            if (container) {
                const preview = container.querySelector('.icon-input-preview');
                if (preview) {
                    preview.addEventListener('click', function (event) {
                        event.stopPropagation();
                        createAndOpenPicker(input, iconPacks);
                    });
                }

                const pickerBtn = container.querySelector('.input__icon-picker-btn');
                if (pickerBtn) {
                    pickerBtn.addEventListener('click', function (event) {
                        event.stopPropagation();
                        createAndOpenPicker(input, iconPacks);
                    });
                }
            }
        });

        function createAndOpenPicker(input, iconPacks) {
            if (!iconPicker) {
                iconPicker = document.createElement('div');
                iconPicker.id = 'iconPickerModal';
                iconPicker.className = 'icon-picker';

                iconPicker.innerHTML = `
                    <div class="icon-picker__header">
                        <div class="icon-picker__search">
                            <input type="text" placeholder="Поиск иконок..." class="icon-picker__search-input">
                        </div>
                        <button type="button" class="icon-picker__close" aria-label="Закрыть">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                    </div>
                    <div class="icon-picker__body">
                        <div class="icon-picker__categories"></div>
                        <div class="icon-picker__styles"></div>
                        <div class="icon-picker__content"></div>
                        <div class="icon-picker__pagination" style="display: none;">
                            <button class="icon-picker__pagination-prev" disabled>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M15 6L9 12L15 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                            <span class="icon-picker__pagination-info">1 / 10</span>
                            <button class="icon-picker__pagination-next">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M9 6L15 12L9 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                `;

                document.body.appendChild(iconPicker);

                const searchInput = iconPicker.querySelector('.icon-picker__search-input');
                searchInput.addEventListener('input', debounce(function() {
                    const searchText = this.value.toLowerCase();
                    let packName;
                    let styleName;

                    const activePackTab = iconPicker.querySelector('.icon-picker__tab.active');
                    const activeStyle = iconPicker.querySelector('.icon-picker__style.active');
                    styleName = activeStyle ? activeStyle.dataset.style : null;

                    if (activePackTab) {
                        packName = activePackTab.dataset.pack;
                    } else {
                        const firstCategoryElem = iconPicker.querySelector('.icon-picker__category.active');
                        if (firstCategoryElem) {
                            const categoryKey = firstCategoryElem.dataset.category;
                            const packsInCategory = categorizedPacks[categoryKey];
                            if (packsInCategory && packsInCategory.length > 0) {
                                const firstPackData = packsInCategory[0];
                                if (firstPackData) {
                                    packName = firstPackData.prefix;
                                }
                            }
                        }
                    }

                    if (!packName) {
                        console.warn('Icon search: Could not determine active pack. Aborting search.');
                        const contentContainer = iconPicker.querySelector('.icon-picker__content');
                        if (contentContainer) {
                            contentContainer.innerHTML = '<div class="icon-picker__error">Выберите пакет иконок для поиска.</div>';
                        }
                        return;
                    }
                    
                    if (searchText.length < 2) {
                        renderIconsForPack(packName, null, styleName);
                        return;
                    }
                    if (styleName) {
                        searchIcons(packName, searchText, styleName);
                    } else {
                        searchIcons(packName, searchText);
                    }
                }, 300));

                const closeButton = iconPicker.querySelector('.icon-picker__close');
                closeButton.addEventListener('click', hideIconPicker);

                document.addEventListener('click', function (e) {
                    if (iconPicker.classList.contains('active') && 
                        !iconPicker.contains(e.target) && 
                        !e.target.classList.contains('input__field-icon') &&
                        !e.target.closest('.icon-input-preview') && 
                        !e.target.classList.contains('input__icon-picker-btn')) {
                        hideIconPicker();
                    }
                });

                iconPicker.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape') {
                        hideIconPicker();
                    } else if (e.key === 'Tab') {
                        const searchInput = iconPicker.querySelector('.icon-picker__search-input');
                        if (document.activeElement !== searchInput) {
                            e.preventDefault();
                            searchInput.focus({ preventScroll: true });
                        }
                    }
                });

                const prevBtn = iconPicker.querySelector('.icon-picker__pagination-prev');
                const nextBtn = iconPicker.querySelector('.icon-picker__pagination-next');

                prevBtn.addEventListener('click', function () {
                    const activeTab = iconPicker.querySelector('.icon-picker__tab.active');
                    if (!activeTab) return;
                    const packName = activeTab.dataset.pack;
                    const activeStyle = iconPicker.querySelector('.icon-picker__style.active');
                    const styleName = activeStyle ? activeStyle.dataset.style : null;
                    const cacheKey = getCacheKey(packName, styleName);
                    if (!iconPacksData[cacheKey]) return;
                    const packData = iconPacksData[cacheKey];
                    const currentPage = packData.searching ? packData.currentPageSearch : packData.currentPage;
                    if (currentPage <= 1) return;
                    if (packData.searching) {
                        packData.currentPageSearch = currentPage - 1;
                        renderSearchResults(packName, packData.searchQuery, styleName);
                    } else {
                        packData.currentPage = currentPage - 1;
                        renderIconsForPack(packName, null, styleName);
                    }
                });

                nextBtn.addEventListener('click', function () {
                    const activeTab = iconPicker.querySelector('.icon-picker__tab.active');
                    if (!activeTab) return;
                    const packName = activeTab.dataset.pack;
                    const activeStyle = iconPicker.querySelector('.icon-picker__style.active');
                    const styleName = activeStyle ? activeStyle.dataset.style : null;
                    const cacheKey = getCacheKey(packName, styleName);
                    if (!iconPacksData[cacheKey]) return;
                    const packData = iconPacksData[cacheKey];
                    if (packData.searching) {
                        const currentPage = packData.currentPageSearch;
                        const totalPages = packData.totalPagesSearch;
                        if (currentPage >= totalPages) return;
                        packData.currentPageSearch = currentPage + 1;
                        renderSearchResults(packName, packData.searchQuery, styleName);
                    } else {
                        const currentPage = packData.currentPage;
                        const totalPages = packData.totalPages;
                        if (currentPage >= totalPages) return;
                        packData.currentPage = currentPage + 1;
                        renderIconsForPack(packName, null, styleName);
                    }
                });
            }

            iconPicker.style.display = 'none';
            iconPicker.currentInput = input;

            iconPicker.classList.remove('active');
            iconPicker.style.pointerEvents = 'none';

            const categoriesContainer = iconPicker.querySelector('.icon-picker__categories');
            categoriesContainer.innerHTML = '';

            if (Object.keys(categorizedPacks).length === 0) {
                iconPicker.querySelector('.icon-picker__content').innerHTML = createSkeletonLoader();
                fetch(u('admin/api/icons/packages'))
                    .then(response => response.json())
                    .then(packages => {
                        packages.forEach(pack => {
                            const category = pack.category || 'Другие';
                            if (!categorizedPacks[category]) {
                                categorizedPacks[category] = [];
                            }
                            categorizedPacks[category].push(pack);
                        });
                        createCategories();
                        iconPicker.classList.add('active');
                        iconPicker.style.pointerEvents = 'auto';
                        positionPicker(input);
                        setTimeout(() => {
                            const searchInput = iconPicker.querySelector('.icon-picker__search-input');
                            if (searchInput) {
                                searchInput.dispatchEvent(new Event('input'));
                            }
                        }, 0);
                    })
                    .catch(error => {
                        console.error('Ошибка при загрузке пакетов иконок:', error);
                        iconPicker.querySelector('.icon-picker__content').innerHTML = '<div class="icon-picker__error">Не удалось загрузить пакеты иконок</div>';
                        iconPicker.classList.add('active');
                        iconPicker.style.pointerEvents = 'auto';
                        positionPicker(input);
                    });
            } else {
                createCategories();
                iconPicker.classList.add('active');
                iconPicker.style.pointerEvents = 'auto';
                positionPicker(input);
                setTimeout(() => {
                    const searchInput = iconPicker.querySelector('.icon-picker__search-input');
                    if (searchInput) {
                        searchInput.dispatchEvent(new Event('input'));
                    }
                }, 0);
            }
        }

        function createCategories() {
            const categoriesContainer = iconPicker.querySelector('.icon-picker__categories');

            categoriesContainer.innerHTML = '';

            const categories = Object.keys(categorizedPacks);
            const saved = lastCategoryByInput.get(iconPicker.currentInput) || categories[0];
            categories.forEach(category => {
                const categoryEl = document.createElement('div');
                categoryEl.className = 'icon-picker__category';
                categoryEl.textContent = category;
                categoryEl.dataset.category = category;

                if (category === saved) {
                    categoryEl.classList.add('active');
                    createTabsForCategory(category);
                }
                categoryEl.addEventListener('click', function () {
                    categoriesContainer.querySelectorAll('.icon-picker__category').forEach(c => c.classList.remove('active'));
                    this.classList.add('active');
                    lastCategoryByInput.set(iconPicker.currentInput, this.dataset.category);
                    resetSearch();
                    createTabsForCategory(this.dataset.category);
                });

                categoriesContainer.appendChild(categoryEl);
            });
        }

        function createTabsForCategory(category) {
            const stylesContainer = iconPicker.querySelector('.icon-picker__styles');
            
            stylesContainer.innerHTML = '';
            
            const packs = categorizedPacks[category] || [];
            
            packs.forEach((pack, index) => {
                const tab = document.createElement('div');
                tab.className = 'icon-picker__tab';
                tab.textContent = pack.name;
                tab.dataset.pack = pack.prefix;
                
                if (index === 0) {
                    tab.classList.add('active');
                    loadIconsForPack(pack.prefix);
                    
                    if (pack.categories && pack.categories.length > 0) {
                        createStylesForPack(pack.prefix, pack.categories);
                    }
                }
                
                tab.addEventListener('click', function () {
                    stylesContainer.innerHTML = '';
                    const tabs = iconPicker.querySelectorAll('.icon-picker__tab');
                    tabs.forEach(t => t.classList.remove('active'));
                    this.classList.add('active');
                    const packPrefix = this.dataset.pack;
                    resetSearch();
                    
                    const pack = categorizedPacks[category].find(p => p.prefix === packPrefix);
                    
                    if (pack && pack.categories && pack.categories.length > 0) {
                        createStylesForPack(packPrefix, pack.categories);
                    } else {
                        stylesContainer.innerHTML = '';
                        stylesContainer.style.display = 'none';
                    }
                    
                    loadIconsForPack(packPrefix);
                });
                
                stylesContainer.appendChild(tab);
            });
        }

        function createStylesForPack(packPrefix, categories) {
            const stylesContainer = iconPicker.querySelector('.icon-picker__styles');
            stylesContainer.innerHTML = '';

            if (!categories || categories.length === 0) {
                stylesContainer.style.display = 'none';
                return;
            }

            stylesContainer.style.display = 'flex';

            const allStyle = document.createElement('div');
            allStyle.className = 'icon-picker__style active';
            allStyle.textContent = 'All';
            allStyle.dataset.style = '';
            allStyle.addEventListener('click', function () {
                stylesContainer.querySelectorAll('.icon-picker__style').forEach(s => {
                    s.classList.remove('active');
                });
                this.classList.add('active');
                resetSearch();

                loadIconsForPack(packPrefix);
            });
            stylesContainer.appendChild(allStyle);

            categories.forEach(style => {
                const styleEl = document.createElement('div');
                styleEl.className = 'icon-picker__style';
                styleEl.textContent = style.charAt(0).toUpperCase() + style.slice(1);
                styleEl.dataset.style = style;

                styleEl.addEventListener('click', function () {
                    stylesContainer.querySelectorAll('.icon-picker__style').forEach(s => {
                        s.classList.remove('active');
                    });
                    this.classList.add('active');
                    resetSearch();

                    loadIconsForPack(packPrefix, style);
                });

                stylesContainer.appendChild(styleEl);
            });
        }

        function positionPicker(input) {
            if (cleanup) {
                cleanup();
                cleanup = null;
            }

            iconPicker.style.display = 'flex';

            const { computePosition, autoUpdate, offset, flip, shift } = window.FloatingUIDOM;

            const update = () => {
                computePosition(input, iconPicker, {
                    placement: 'bottom-start',
                    middleware: [
                        offset(8),
                        flip({ padding: 16 }),
                        shift({ padding: 16 })
                    ]
                }).then(({ x, y }) => {
                    Object.assign(iconPicker.style, {
                        left: `${x}px`,
                        top: `${y}px`
                    });
                    requestAnimationFrame(() => {
                        if (iconPicker.classList.contains('active')) {
                            const searchInput = iconPicker.querySelector('.icon-picker__search-input');
                            if (searchInput && document.activeElement !== searchInput) {
                                searchInput.focus({ preventScroll: true });
                            }
                        }
                    });
                });
            };

            cleanup = autoUpdate(input, iconPicker, update);
            update();

            setTimeout(() => {
                resetSearch();
            }, 0);
        }

        function hideIconPicker() {
            if (iconPicker) {
                iconPicker.classList.remove('active');
                iconPicker.style.pointerEvents = 'none';

                if (cleanup) {
                    cleanup();
                    cleanup = null;
                }

                setTimeout(() => {
                    resetSearch();
                }, 300);
            }
        }

        function getCacheKey(packPrefix, styleName = null) {
            return packPrefix + (styleName ? `-${styleName}` : '');
        }

        function searchIcons(packPrefix, searchText, styleName = null) {
            if (searchText.length < 2) {
                if (packPrefix) {
                    renderIconsForPack(packPrefix, null, styleName);
                }
                return;
            }
            
            const contentContainer = iconPicker.querySelector('.icon-picker__content');
            contentContainer.innerHTML = createSkeletonLoader();
            
            const cacheKey = getCacheKey(packPrefix, styleName);
            
            let url = `admin/api/icons/search?prefix=${packPrefix}&q=${encodeURIComponent(searchText)}`;
            if (styleName) {
                url += `&category=${styleName}`;
            }
            
            fetch(u(url))
                .then(response => response.json())
                .then(data => {
                    if (!iconPacksData[cacheKey]) {
                        iconPacksData[cacheKey] = {
                            currentPage: 1,
                            totalPages: 1,
                            icons: [],
                            limit: 50,
                            hasPage: {}
                        };
                    }
                    
                    const packData = iconPacksData[cacheKey];
                    
                    const searchResults = data.icons.map(icon => ({
                        path: icon.path,
                        svg: icon.svg,
                        displayName: icon.displayName
                    }));
                    
                    packData.searching = true;
                    packData.searchQuery = searchText;
                    packData.searchResults = searchResults;
                    packData.totalPagesSearch = Math.ceil(searchResults.length / packData.limit);
                    packData.currentPageSearch = 1;
                    
                    renderSearchResults(packPrefix, searchText, styleName);
                })
                .catch(error => {
                    console.error(`Ошибка при поиске иконок для пакета ${packPrefix}:`, error);
                    contentContainer.innerHTML = '<div class="icon-picker__error">Не удалось выполнить поиск иконок</div>';
                });
        }

        function renderSearchResults(packPrefix, searchText, styleName = null) {
            const cacheKey = getCacheKey(packPrefix, styleName);
            const packData = iconPacksData[cacheKey];

            if (!packData || !packData.searchResults) return;

            const contentContainer = iconPicker.querySelector('.icon-picker__content');
            const pagination = iconPicker.querySelector('.icon-picker__pagination');
            const paginationInfo = pagination.querySelector('.icon-picker__pagination-info');
            const prevBtn = pagination.querySelector('.icon-picker__pagination-prev');
            const nextBtn = pagination.querySelector('.icon-picker__pagination-next');

            contentContainer.innerHTML = '';

            const start = (packData.currentPageSearch - 1) * packData.limit;
            const end = Math.min(start + packData.limit, packData.searchResults.length);
            const iconsToDisplay = packData.searchResults.slice(start, end);

            if (iconsToDisplay.length === 0) {
                contentContainer.innerHTML = '<div class="icon-picker__empty">Ничего не найдено</div>';
                pagination.style.display = 'none';
                return;
            }

            if (packData.totalPagesSearch > 1) {
                pagination.style.display = 'flex';
                paginationInfo.textContent = `${packData.currentPageSearch} / ${packData.totalPagesSearch}`;
                prevBtn.disabled = packData.currentPageSearch <= 1;
                nextBtn.disabled = packData.currentPageSearch >= packData.totalPagesSearch;
            } else {
                pagination.style.display = 'none';
            }

            const iconsFragment = document.createDocumentFragment();
            iconsToDisplay.forEach(icon => {
                const iconElement = document.createElement('div');
                iconElement.className = 'icon-picker__icon';
                iconElement.dataset.iconPath = icon.path;
                iconElement.title = icon.displayName;
                iconElement.innerHTML = icon.svg;
                if (iconPicker.currentInput && iconPicker.currentInput.value === icon.path) {
                    iconElement.classList.add('active');
                }
                iconElement.addEventListener('click', function () {
                    selectIcon(this.dataset.iconPath);
                });
                iconsFragment.appendChild(iconElement);
            });

            contentContainer.appendChild(iconsFragment);

            if (cleanup) {
                cleanup();
                const { computePosition, autoUpdate, offset, flip, shift } = window.FloatingUIDOM;
                const update = () => {
                    computePosition(iconPicker.currentInput, iconPicker, {
                        placement: 'bottom-start',
                        middleware: [offset(8), flip({ padding: 16 }), shift({ padding: 16 })]
                    }).then(({ x, y }) => {
                        Object.assign(iconPicker.style, { left: `${x}px`, top: `${y}px` });
                    });
                };
                cleanup = autoUpdate(iconPicker.currentInput, iconPicker, update);
            }
        }

        function loadIconsForPack(packPrefix, styleName = null) {
            const contentContainer = iconPicker.querySelector('.icon-picker__content');
            const pagination = iconPicker.querySelector('.icon-picker__pagination');
            pagination.style.display = 'none';

            contentContainer.innerHTML = createSkeletonLoader();

            const cacheKey = getCacheKey(packPrefix, styleName);

            if (iconPacksData[cacheKey] && iconPacksData[cacheKey].icons.length > 0) {
                renderIconsForPack(packPrefix, null, styleName);
                return;
            }

            if (iconCache[cacheKey]) {
                iconPacksData[cacheKey] = iconCache[cacheKey];
                renderIconsForPack(packPrefix, null, styleName);
                return;
            }

            iconPacksData[cacheKey] = {
                currentPage: 1,
                totalPages: 1,
                icons: [],
                searchResults: [],
                limit: 50,
                hasPage: { 1: false }
            };

            let url = `admin/api/icons/batch-render?prefix=${packPrefix}&limit=${iconPacksData[cacheKey].limit}&page=1`;
            if (styleName) {
                url += `&category=${styleName}`;
            }

            fetch(u(url))
                .then(response => response.json())
                .then(data => {
                    iconPacksData[cacheKey].icons = data.icons.map(icon => ({
                        path: icon.path,
                        svg: icon.svg,
                        displayName: icon.displayName
                    }));

                    iconPacksData[cacheKey].totalPages = data.totalPages;
                    iconPacksData[cacheKey].total = data.total;
                    iconPacksData[cacheKey].hasPage[1] = true;

                    iconCache[cacheKey] = { ...iconPacksData[cacheKey] };
                    saveIconCache();

                    renderIconsForPack(packPrefix, null, styleName);
                })
                .catch(error => {
                    console.error(`Ошибка при загрузке иконок для пакета ${packPrefix}:`, error);
                    contentContainer.innerHTML = '<div class="icon-picker__error">Не удалось загрузить иконки</div>';
                });
        }

        function renderIconsForPack(packPrefix, searchText = null, styleName = null) {
            const cacheKey = getCacheKey(packPrefix, styleName);
            const packData = iconPacksData[cacheKey];
            
            if (!packData) return;

            const contentContainer = iconPicker.querySelector('.icon-picker__content');
            const pagination = iconPicker.querySelector('.icon-picker__pagination');
            const paginationInfo = pagination.querySelector('.icon-picker__pagination-info');
            const prevBtn = pagination.querySelector('.icon-picker__pagination-prev');
            const nextBtn = pagination.querySelector('.icon-picker__pagination-next');

            contentContainer.innerHTML = '';

            if (searchText && searchText.length >= 2) {
                searchIcons(packPrefix, searchText, styleName);
                return;
            }
            
            if (packData.searching) {
                packData.searching = false;
                packData.searchQuery = null;
            }
            
            if (packData.currentPage > 1 && !packData.hasPage[packData.currentPage]) {
                loadPageForPack(packPrefix, packData.currentPage, styleName);
                return;
            }
            
            const start = (packData.currentPage - 1) * packData.limit;
            const iconsToDisplay = packData.icons.slice(start, start + packData.limit);
            
            if (packData.totalPages > 1) {
                pagination.style.display = 'flex';
                paginationInfo.textContent = `${packData.currentPage} / ${packData.totalPages}`;
                prevBtn.disabled = packData.currentPage <= 1;
                nextBtn.disabled = packData.currentPage >= packData.totalPages;
            } else {
                pagination.style.display = 'none';
            }

            if (iconsToDisplay.length === 0) {
                contentContainer.innerHTML = '<div class="icon-picker__empty">Ничего не найдено</div>';
                return;
            }

            const iconsFragment = document.createDocumentFragment();
            iconsToDisplay.forEach(icon => {
                const iconElement = document.createElement('div');
                iconElement.className = 'icon-picker__icon';
                iconElement.dataset.iconPath = icon.path;
                iconElement.title = icon.displayName;
                iconElement.innerHTML = icon.svg;
                if (iconPicker.currentInput && iconPicker.currentInput.value === icon.path) {
                    iconElement.classList.add('active');
                }
                iconElement.addEventListener('click', function () {
                    selectIcon(this.dataset.iconPath);
                });
                iconsFragment.appendChild(iconElement);
            });

            contentContainer.appendChild(iconsFragment);

            if (cleanup) {
                cleanup();
                const { computePosition, autoUpdate, offset, flip, shift } = window.FloatingUIDOM;
                const update = () => {
                    computePosition(iconPicker.currentInput, iconPicker, {
                        placement: 'bottom-start',
                        middleware: [offset(8), flip({ padding: 16 }), shift({ padding: 16 })]
                    }).then(({ x, y }) => {
                        Object.assign(iconPicker.style, { left: `${x}px`, top: `${y}px` });
                    });
                };
                cleanup = autoUpdate(iconPicker.currentInput, iconPicker, update);
            }
        }

        function loadPageForPack(packPrefix, page, styleName = null) {
            const contentContainer = iconPicker.querySelector('.icon-picker__content');
            contentContainer.innerHTML = createSkeletonLoader();

            const cacheKey = getCacheKey(packPrefix, styleName);
            const packData = iconPacksData[cacheKey];
            if (!packData) return;

            if (!packData.hasPage) {
                packData.hasPage = {};
            }

            if (iconCache[cacheKey] && iconCache[cacheKey].hasPage && iconCache[cacheKey].hasPage[page]) {
                iconPacksData[cacheKey] = { ...iconCache[cacheKey] };
                packData.hasPage[page] = true;
                renderIconsForPack(packPrefix, null, styleName);
                return;
            }

            let url = `admin/api/icons/batch-render?prefix=${packPrefix}&limit=${packData.limit}&page=${page}`;
            if (styleName) {
                url += `&category=${styleName}`;
            }

            fetch(u(url))
                .then(response => response.json())
                .then(data => {
                    const newIcons = data.icons.map(icon => ({
                        path: icon.path,
                        svg: icon.svg,
                        displayName: icon.displayName
                    }));

                    packData.hasPage[page] = true;

                    const start = (page - 1) * packData.limit;
                    const end = start + packData.limit;

                    if (packData.icons.length < end) {
                        packData.icons.length = end;
                    }

                    for (let i = 0; i < newIcons.length; i++) {
                        packData.icons[start + i] = newIcons[i];
                    }

                    iconCache[cacheKey] = { ...packData };
                    saveIconCache();

                    renderIconsForPack(packPrefix, null, styleName);
                })
                .catch(error => {
                    console.error(`Ошибка при загрузке страницы ${page} иконок для пакета ${packPrefix}:`, error);
                    contentContainer.innerHTML = '<div class="icon-picker__error">Не удалось загрузить иконки</div>';
                });
        }

        function selectIcon(iconPath) {
            if (!iconPicker.currentInput) return;

            iconPicker.currentInput.value = iconPath;
            updateSelectedIcon(iconPath);
            hideIconPicker();

            iconPicker.currentInput.dispatchEvent(new Event('input', { bubbles: true }));
            iconPicker.currentInput.dispatchEvent(new Event('change', { bubbles: true }));
            iconPicker.currentInput.focus();
        }

        function updateSelectedIcon(iconPath) {
            const container = iconPicker.currentInput.closest('.icon-input-container');
            if (!container) return;

            const preview = container.querySelector('.icon-input-preview');
            if (!preview) return;

            if (!iconPath) {
                preview.innerHTML = '';
                return;
            }

            for (const cacheKey in iconPacksData) {
                const packData = iconPacksData[cacheKey];
                const icon = packData.icons.find(i => i.path === iconPath);

                if (icon) {
                    preview.innerHTML = icon.svg;
                    return;
                }

                if (packData.searchResults) {
                    const searchIcon = packData.searchResults.find(i => i.path === iconPath);
                    if (searchIcon) {
                        preview.innerHTML = searchIcon.svg;
                        return;
                    }
                }
            }

            fetch(u(`/admin/api/icons/render?path=${encodeURIComponent(iconPath)}`))
                .then(response => response.text())
                .then(svgContent => {
                    preview.innerHTML = svgContent;
                })
                .catch(() => {
                    preview.innerHTML = '';
                });
        }

        function resetSearch() {
            const searchInput = iconPicker.querySelector('.icon-picker__search-input');
            if (searchInput) {
                searchInput.value = '';
            }
        }

        function createSkeletonLoader() {
            const count = 24;
            let html = '<div class="icon-picker__skeleton">';

            for (let i = 0; i < count; i++) {
                html += '<div class="icon-picker__skeleton-item"></div>';
            }

            html += '</div>';
            return html;
        }
    }

    window.togglePassword = function (event) {
        const button = event.currentTarget;
        const input = button.previousElementSibling;
        const iconEye = button.querySelector('.icon-eye');
        const iconEyeSlash = button.querySelector('.icon-eye-slash');

        if (input.type === 'password') {
            input.type = 'text';
            iconEye.style.display = 'none';
            iconEyeSlash.style.display = 'block';
        } else {
            input.type = 'password';
            iconEye.style.display = 'block';
            iconEyeSlash.style.display = 'none';
        }
    };
});

function debounce(func, wait) {
    let timeout;
    return function() {
        const context = this;
        const args = arguments;
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            func.apply(context, args);
        }, wait);
    };
}
