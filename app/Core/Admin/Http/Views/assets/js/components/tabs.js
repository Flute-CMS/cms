const title = translate('admin.home.title');
const icon = '<i class="ph ph-house"></i>';
const contentCache = {};

((window, factory) => {
    if (typeof define == 'function' && define.amd) {
        define(['draggabilly'], (Draggabilly) => factory(window, Draggabilly));
    } else if (typeof module == 'object' && module.exports) {
        module.exports = factory(window, require('draggabilly'));
    } else {
        window.ChromeTabs = factory(window, window.Draggabilly);
    }
})(window, (window, Draggabilly) => {
    const TAB_CONTENT_MARGIN = 10;
    const TAB_CONTENT_OVERLAP_DISTANCE = 1;

    const TAB_OVERLAP_DISTANCE =
        TAB_CONTENT_MARGIN * 2 + TAB_CONTENT_OVERLAP_DISTANCE;

    const TAB_CONTENT_MIN_WIDTH = 24;
    const TAB_CONTENT_MAX_WIDTH = 240;

    const TAB_SIZE_SMALL = 84;
    const TAB_SIZE_SMALLER = 60;
    const TAB_SIZE_MINI = 48;
    const NEW_TAB_BUTTON_AREA = 90;

    const noop = (_) => {};

    const closest = (value, array) => {
        let closest = Infinity;
        let closestIndex = -1;

        array.forEach((v, i) => {
            if (Math.abs(value - v) < closest) {
                closest = Math.abs(value - v);
                closestIndex = i;
            }
        });

        return closestIndex;
    };

    const tabTemplate = `
      <div class="chrome-tab">
        <div class="chrome-tab-dividers"></div>
        <div class="chrome-tab-background">
          <svg version="1.1" xmlns="http://www.w3.org/2000/svg"><defs><symbol id="chrome-tab-geometry-left" viewBox="0 0 214 36"><path d="M17 0h197v36H0v-2c4.5 0 9-3.5 9-8V8c0-4.5 3.5-8 8-8z"/></symbol><symbol id="chrome-tab-geometry-right" viewBox="0 0 214 36"><use xlink:href="#chrome-tab-geometry-left"/></symbol><clipPath id="crop"><rect class="mask" width="100%" height="100%" x="0"/></clipPath></defs><svg width="52%" height="100%"><use xlink:href="#chrome-tab-geometry-left" width="214" height="36" class="chrome-tab-geometry"/></svg><g transform="scale(-1, 1)"><svg width="52%" height="100%" x="-100%" y="0"><use xlink:href="#chrome-tab-geometry-right" width="214" height="36" class="chrome-tab-geometry"/></svg></g></svg>
        </div>
        <div class="chrome-tab-content">
          <div class="chrome-tab-favicon"></div>
          <div class="chrome-tab-title"></div>
          <div class="chrome-tab-drag-handle"></div>
          <div class="chrome-tab-close"></div>
        </div>
      </div>
    `;

    const newTabButtonTemplate = `
      <div class="new-tab-button-wrapper">
        <button class="new-tab-button">✚</button>
      </div>
    `;

    const defaultTapProperties = {
        title: title,
        favicon: icon,
        url: '',
    };

    let instanceId = 0;

    class ChromeTabs {
        constructor() {
            this.draggabillies = [];
            this.contentContainers = {};
        }

        init(el) {
            this.el = el;

            this.instanceId = instanceId;
            this.el.setAttribute(
                'data-chrome-tabs-instance-id',
                this.instanceId,
            );
            instanceId += 1;

            this.setupCustomProperties();
            this.setupStyleEl();
            this.setupEvents();
            this.layoutTabs();
            this.setupNewTabButton();
            this.setupDraggabilly();
        }

        createContentContainerForTab(tabEl) {
            console.log('CONTAINER');
            const tabId = tabEl.getAttribute('data-tab-id');
            tabEl.setAttribute('data-tab-id', tabId);

            const contentContainer = document.createElement('div');
            contentContainer.classList.add('tab-content');
            contentContainer.id = `content-${tabId}`;
            contentContainer.hidden = true;

            // Добавляем контейнер в DOM
            document
                .getElementById('contents_page')
                .appendChild(contentContainer);

            return contentContainer;
        }

        emit(eventName, data) {
            this.el.dispatchEvent(new CustomEvent(eventName, { detail: data }));
        }

        setupCustomProperties() {
            this.el.style.setProperty(
                '--tab-content-margin',
                `${TAB_CONTENT_MARGIN}px`,
            );
        }

        setupStyleEl() {
            this.styleEl = document.createElement('style');
            this.el.appendChild(this.styleEl);
        }

        setupEvents() {
            window.addEventListener('resize', (_) => {
                this.cleanUpPreviouslyDraggedTabs();
                this.layoutTabs();
            });

            this.el.addEventListener('dblclick', (event) => {
                if ([this.el, this.tabContentEl].includes(event.target))
                    this.addTab();
            });

            this.el.addEventListener('click', ({ target }) => {
                if (target.classList.contains('new-tab-button')) this.addTab();
            });

            this.tabEls.forEach((tabEl) =>
                this.setTabCloseEventListener(tabEl),
            );
        }

        get tabEls() {
            return Array.prototype.slice.call(
                this.el.querySelectorAll('.chrome-tab'),
            );
        }

        get tabContentEl() {
            return this.el.querySelector('.chrome-tabs-content');
        }

        get tabContentWidths() {
            const numberOfTabs = this.tabEls.length;
            const tabsContentWidth = this.el.clientWidth - NEW_TAB_BUTTON_AREA;
            const tabsCumulativeOverlappedWidth =
                (numberOfTabs - 1) * TAB_CONTENT_OVERLAP_DISTANCE;
            const targetWidth =
                (tabsContentWidth -
                    2 * TAB_CONTENT_MARGIN +
                    tabsCumulativeOverlappedWidth) /
                numberOfTabs;
            const clampedTargetWidth = Math.max(
                TAB_CONTENT_MIN_WIDTH,
                Math.min(TAB_CONTENT_MAX_WIDTH, targetWidth),
            );
            const flooredClampedTargetWidth = Math.floor(clampedTargetWidth);
            const totalTabsWidthUsingTarget =
                flooredClampedTargetWidth * numberOfTabs +
                2 * TAB_CONTENT_MARGIN -
                tabsCumulativeOverlappedWidth;
            const totalExtraWidthDueToFlooring =
                tabsContentWidth - totalTabsWidthUsingTarget;

            // TODO - Support tabs with different widths / e.g. "pinned" tabs
            const widths = [];
            let extraWidthRemaining = totalExtraWidthDueToFlooring;
            for (let i = 0; i < numberOfTabs; i += 1) {
                const extraWidth =
                    flooredClampedTargetWidth < TAB_CONTENT_MAX_WIDTH &&
                    extraWidthRemaining > 0
                        ? 1
                        : 0;
                widths.push(flooredClampedTargetWidth + extraWidth);
                if (extraWidthRemaining > 0) extraWidthRemaining -= 1;
            }

            return widths;
        }

        get tabContentPositions() {
            const positions = [];
            const tabContentWidths = this.tabContentWidths;

            let position = TAB_CONTENT_MARGIN;
            tabContentWidths.forEach((width, i) => {
                const offset = i * TAB_CONTENT_OVERLAP_DISTANCE;
                positions.push(position - offset);
                position += width;
            });

            return positions;
        }

        get tabPositions() {
            const positions = [];

            this.tabContentPositions.forEach((contentPosition) => {
                positions.push(contentPosition - TAB_CONTENT_MARGIN);
            });

            return positions;
        }

        layoutTabs() {
            const tabContentWidths = this.tabContentWidths;
            let tabsLen = this.tabEls.length;

            this.tabEls.forEach((tabEl, i) => {
                const contentWidth = tabContentWidths[i];
                const width = contentWidth + 2 * TAB_CONTENT_MARGIN;

                tabEl.style.width = width + 'px';
                tabEl.removeAttribute('is-small');
                tabEl.removeAttribute('is-smaller');
                tabEl.removeAttribute('is-mini');

                if (contentWidth < TAB_SIZE_SMALL)
                    tabEl.setAttribute('is-small', '');
                if (contentWidth < TAB_SIZE_SMALLER)
                    tabEl.setAttribute('is-smaller', '');
                if (contentWidth < TAB_SIZE_MINI)
                    tabEl.setAttribute('is-mini', '');
            });

            let styleHTML = '';
            this.tabPositions.forEach((position, i) => {
                styleHTML += `
            .chrome-tabs[data-chrome-tabs-instance-id="${
                this.instanceId
            }"] .chrome-tab:nth-child(${i + 1}) {
              transform: translate3d(${position}px, 0, 0)
            }
          `;
            });
            this.styleEl.innerHTML = styleHTML;

            if (
                this.el.offsetWidth - this.tabContentEl.offsetWidth >
                    NEW_TAB_BUTTON_AREA + TAB_CONTENT_MARGIN / 2 ||
                tabsLen < 5
            ) {
                this.tabContentEl.style.width = `${
                    (this.tabEls[0]
                        ? this.tabEls[0].offsetWidth * tabsLen
                        : 0) -
                    (tabsLen > 0
                        ? tabsLen * TAB_CONTENT_MARGIN * 2 -
                          TAB_CONTENT_MIN_WIDTH +
                          TAB_CONTENT_MARGIN
                        : 0)
                }px`;
                this.tabContentEl.nextElementSibling.classList.remove(
                    'overflow-shadow',
                );
            } else
                this.tabContentEl.nextElementSibling.classList.add(
                    'overflow-shadow',
                );
        }

        createNewTabEl() {
            const div = document.createElement('div');
            div.innerHTML = tabTemplate;
            return div.firstElementChild;
        }

        addTab(tabProperties, { animate = true, background = false } = {}) {
            const tabEl = this.createNewTabEl();

            if (animate) {
                tabEl.classList.add('chrome-tab-was-just-added');
                setTimeout(
                    () => tabEl.classList.remove('chrome-tab-was-just-added'),
                    500,
                );
            }

            tabProperties = Object.assign(
                {},
                defaultTapProperties,
                tabProperties,
            );
            tabEl.setAttribute('data-tab-id', tabProperties.id || uuidv4());
            tabEl.setAttribute('data-original-icon', tabProperties.favicon);
            this.tabContentEl.appendChild(tabEl);
            this.setTabCloseEventListener(tabEl);
            this.updateTab(tabEl, tabProperties);
            this.emit('tabAdd', { tabEl });

            if (!background) {
                this.setCurrentTab(tabEl);
            }

            // const contentContainer = this.createContentContainerForTab(tabEl);

            // this.contentContainers[tabEl.getAttribute('data-tab-id')] =
            //     contentContainer;

            this.layoutTabs();
            this.cleanUpPreviouslyDraggedTabs();
            this.setupDraggabilly();
        }

        setTabCloseEventListener(tabEl) {
            tabEl.querySelector('.chrome-tab-close').onclick = (e) => {
                this.removeTab(tabEl);
            };

            tabEl.addEventListener('auxclick', (event) => {
                if (event.button === 1) {
                    event.preventDefault();
                    this.removeTab(tabEl);
                }
            });

            document.addEventListener('mousedown', (event) => {
                if (event.which === 2) {
                    event.preventDefault();
                }
            });
        }

        get activeTabEl() {
            return this.el.querySelector('.chrome-tab[active]');
        }

        hasActiveTab() {
            return !!this.activeTabEl;
        }

        setCurrentTab(tabEl) {
            const activeTabEl = this.activeTabEl;
            if (activeTabEl === tabEl) return;

            // Hide current active tab content
            if (
                activeTabEl &&
                this.contentContainers[activeTabEl.getAttribute('data-tab-id')]
            ) {
                activeTabEl.removeAttribute('active');
                this.contentContainers[
                    activeTabEl.getAttribute('data-tab-id')
                ].hidden = true;
            }

            // Activate new tab
            tabEl.setAttribute('active', '');
            if (this.contentContainers[tabEl.getAttribute('data-tab-id')]) {
                this.contentContainers[
                    tabEl.getAttribute('data-tab-id')
                ].hidden = false;
            } else {
                // If no content container exists, create one
                const newContentContainer =
                    this.createContentContainerForTab(tabEl);
                this.contentContainers[tabEl.getAttribute('data-tab-id')] =
                    newContentContainer;
                newContentContainer.hidden = false;
            }

            const url = tabEl.getAttribute('data-tab-url') || '/admin/';
            document.getElementById('current_url').innerHTML = url;
            document
                .getElementById('current_url')
                .setAttribute('data-copy', url);

            this.emit('activeTabChange', { tabEl, activeTabEl });

            const title = tabEl.querySelector('.chrome-tab-title').textContent;
            document.title = title;
            history.pushState({ path: url, title: title }, '', url);

            if (url === '/admin/' || url === '') {
                hideAllPages();
                displayLoading(false);
                document.getElementById('start_page').hidden = false;
            } else {
                hideAllPages();

                if (!contentCache[url]) {
                    fetchContentAndAddTab(
                        url,
                        title,
                        tabEl.querySelector('.chrome-tab-favicon').innerHTML,
                    );
                }
            }

            this.updateTab(tabEl, {
                title: title,
            });
        }

        handleBrowserNavigation(event) {
            const path =
                event.state && event.state.path ? event.state.path : '/admin/';
            const title =
                event.state && event.state.title
                    ? event.state.title
                    : document.title;
            document.title = title;
            this.checkForOrphanPage();
        }

        removeTab(tabEl) {
            // Получаем ID вкладки
            const tabId = tabEl.getAttribute('data-tab-id');

            // Удаляем контейнер контента, связанный с этой вкладкой
            const contentContainer = document.getElementById(
                `content-${tabId}`,
            );
            if (contentContainer) {
                contentContainer.parentNode.removeChild(contentContainer);
            }

            // Обработка переключения на другую вкладку при закрытии текущей активной вкладки
            if (tabEl === this.activeTabEl) {
                if (tabEl.nextElementSibling) {
                    this.setCurrentTab(tabEl.nextElementSibling);
                } else if (tabEl.previousElementSibling) {
                    this.setCurrentTab(tabEl.previousElementSibling);
                } else {
                    hideAllPages();
                    document.getElementById('start_page').hidden = false;
                }
            }

            // Удаление самой вкладки
            tabEl.parentNode.removeChild(tabEl);

            // Событие о удалении вкладки
            this.emit('tabRemove', { tabEl });

            // Перерасчет позиций вкладок и обновление UI
            this.cleanUpPreviouslyDraggedTabs();
            this.layoutTabs();
            this.setupDraggabilly();

            this.saveTabs();
        }

        updateTab(tabEl, tabProperties) {
            tabEl.querySelector('.chrome-tab-title').innerHTML =
                tabProperties.title;

            const faviconEl = tabEl.querySelector('.chrome-tab-favicon');
            if (tabProperties.favicon) {
                if (tabProperties.favicon.includes('<i class='))
                    faviconEl.innerHTML = tabProperties.favicon;
                else
                    faviconEl.style.backgroundImage = `url('${tabProperties.favicon}')`;

                faviconEl.removeAttribute('hidden', '');
            } else {
                // faviconEl.setAttribute('hidden', '');
                // faviconEl.removeAttribute('style');
            }

            if (tabProperties.url) {
                tabEl.setAttribute('data-tab-url', tabProperties.url);
            } else {
                // tabEl.removeAttribute('data-tab-url');
            }
        }

        cleanUpPreviouslyDraggedTabs() {
            this.tabEls.forEach((tabEl) =>
                tabEl.classList.remove('chrome-tab-was-just-dragged'),
            );
        }

        setupDraggabilly() {
            const tabEls = this.tabEls;
            const tabPositions = this.tabPositions;

            if (this.isDragging) {
                this.isDragging = false;
                this.el.classList.remove('chrome-tabs-is-sorting');
                this.draggabillyDragging.element.classList.remove(
                    'chrome-tab-is-dragging',
                );
                this.draggabillyDragging.element.style.transform = '';
                this.draggabillyDragging.dragEnd();
                this.draggabillyDragging.isDragging = false;
                this.draggabillyDragging.positionDrag = noop; // Prevent Draggabilly from updating tabEl.style.transform in later frames
                this.draggabillyDragging.destroy();
                this.draggabillyDragging = null;
            }

            this.draggabillies.forEach((d) => d.destroy());

            tabEls.forEach((tabEl, originalIndex) => {
                const originalTabPositionX = tabPositions[originalIndex];
                const draggabilly = new Draggabilly(tabEl, {
                    axis: 'x',
                    handle: '.chrome-tab-drag-handle',
                    containment: this.tabContentEl,
                });

                this.draggabillies.push(draggabilly);

                draggabilly.on('pointerDown', (_) => {
                    this.setCurrentTab(tabEl);
                });

                draggabilly.on('dragStart', (_) => {
                    this.isDragging = true;
                    this.draggabillyDragging = draggabilly;
                    tabEl.classList.add('chrome-tab-is-dragging');
                    this.el.classList.add('chrome-tabs-is-sorting');
                });

                draggabilly.on('dragEnd', (_) => {
                    this.isDragging = false;
                    const finalTranslateX = parseFloat(tabEl.style.left, 10);
                    tabEl.style.transform = `translate3d(0, 0, 0)`;

                    // Animate dragged tab back into its place
                    requestAnimationFrame((_) => {
                        tabEl.style.left = '0';
                        tabEl.style.transform = `translate3d(${finalTranslateX}px, 0, 0)`;

                        requestAnimationFrame((_) => {
                            tabEl.classList.remove('chrome-tab-is-dragging');
                            this.el.classList.remove('chrome-tabs-is-sorting');

                            tabEl.classList.add('chrome-tab-was-just-dragged');

                            requestAnimationFrame((_) => {
                                tabEl.style.transform = '';

                                this.layoutTabs();
                                this.setupDraggabilly();
                            });
                        });
                    });

                    this.saveTabs();
                });

                draggabilly.on('dragMove', (event, pointer, moveVector) => {
                    // Current index be computed within the event since it can change during the dragMove
                    const tabEls = this.tabEls;
                    const currentIndex = tabEls.indexOf(tabEl);

                    const currentTabPositionX =
                        originalTabPositionX + moveVector.x;
                    const destinationIndexTarget = closest(
                        currentTabPositionX,
                        tabPositions,
                    );
                    const destinationIndex = Math.max(
                        0,
                        Math.min(tabEls.length, destinationIndexTarget),
                    );

                    if (currentIndex !== destinationIndex) {
                        this.animateTabMove(
                            tabEl,
                            currentIndex,
                            destinationIndex,
                        );
                    }
                });
            });
        }

        animateTabMove(tabEl, originIndex, destinationIndex) {
            if (destinationIndex < originIndex) {
                tabEl.parentNode.insertBefore(
                    tabEl,
                    this.tabEls[destinationIndex],
                );
            } else {
                tabEl.parentNode.insertBefore(
                    tabEl,
                    this.tabEls[destinationIndex + 1],
                );
            }
            this.emit('tabReorder', { tabEl, originIndex, destinationIndex });
            this.layoutTabs();
        }

        setupNewTabButton() {
            this.tabContentEl.insertAdjacentHTML(
                'afterend',
                newTabButtonTemplate,
            );
            this.layoutTabs();
        }
    }

    return ChromeTabs;
});

class ChromeTabsWithStorage extends ChromeTabs {
    init(el) {
        super.init(el);
        if (this.tabEls.length === 0) {
            this.loadTabs();
        }
        this.checkForOrphanPage();
        window.addEventListener(
            'popstate',
            this.handleBrowserNavigation.bind(this),
        );
    }

    checkForOrphanPage() {
        const currentUrl = window.location.pathname;
        const currentTitle = document.title || 'New Tab';
        const existingTab = chromeTabs.findTabByUrl(currentUrl);
        if (!existingTab) {
            chromeTabs.addTab(
                {
                    title: currentTitle,
                    favicon: icon,
                    url: currentUrl,
                },
                { background: false },
            );
        } else {
            this.restoreCurrentTab();
            chromeTabs.setCurrentTab(existingTab);
        }
    }

    handleBrowserNavigation(event) {
        const path =
            event.state && event.state.path ? event.state.path : '/admin/';
        this.checkForOrphanPage();
    }

    loadTabs() {
        const tabsData = JSON.parse(localStorage.getItem('chromeTabsData'));
        if (tabsData && tabsData.length > 0) {
            tabsData.forEach((tabData) =>
                this.addTab(tabData, { background: true }),
            );
        } else {
            // this.addTab({
            //     title: title,
            //     favicon: icon,
            //     url: '',
            // });
        }
    }

    saveTabs() {
        const tabs = this.tabEls.map((tabEl) => {
            const title = tabEl.querySelector('.chrome-tab-title').textContent;
            const url = tabEl.getAttribute('data-tab-url') || '/admin/';
            const id = tabEl.getAttribute('data-tab-id');
            const favicon = tabEl.getAttribute('data-original-icon') || '';

            return { title, favicon, url, id };
        });
        localStorage.setItem('chromeTabsData', JSON.stringify(tabs));
    }

    addTab(tabProperties, options = {}) {
        super.addTab(tabProperties, options);
        this.saveTabs();
    }

    updateTab(tabEl, tabProperties) {
        super.updateTab(tabEl, tabProperties);
        this.saveTabs();
    }

    removeTab(tabEl) {
        super.removeTab(tabEl);
        this.saveTabs();
        if (this.tabEls.length === 0) {
            this.addTab({
                title: title,
                favicon: icon,
                url: '',
            });
        }
    }

    findTabByUrl(url) {
        return this.tabEls.find(
            (tabEl) => tabEl.getAttribute('data-tab-url') === url,
        );
    }

    setCurrentTab(tabEl) {
        super.setCurrentTab(tabEl);
        localStorage.setItem('currentTabId', tabEl.getAttribute('data-tab-id'));
    }

    restoreCurrentTab() {
        const currentTabId = localStorage.getItem('currentTabId');
        const currentTabEl = this.el.querySelector(
            `.chrome-tab[data-tab-id="${currentTabId}"]`,
        );
        if (currentTabEl) {
            this.setCurrentTab(currentTabEl);
        } else if (this.tabEls.length > 0) {
            this.setCurrentTab(this.tabEls[0]);
        }
    }

    refreshTab() {
        console.log(this.activeTabEl);
    }
}

let el = document.querySelector('.chrome-tabs');
let chromeTabs = new ChromeTabsWithStorage();
chromeTabs.init(el);

chromeTabs.restoreCurrentTab();

function refreshCurrentPage() {
    const tabEl = chromeTabs.activeTabEl;
    const tabId = tabEl ? tabEl.getAttribute('data-tab-id') : null;
    const url = tabEl ? tabEl.getAttribute('data-tab-url') : null;
    const contentContainer = document.getElementById(`content-${tabId}`);

    if (url === '/admin/' || !url) {
        displayLoading(false);
        return;
    }

    document.getElementById('start_page').hidden = true;

    //

    displayLoading(true);
    changeTabIcon(tabEl, contentContainer, true);
    contentContainer.classList.add('loading');
    fetchContent(url, contentContainer, tabEl);

    initEditor($(contentContainer));
}

function fetchContent(url, container, tabEl, reload = false, title = null) {
    fetch(appendGet(url, 'loadByTab', '1'))
        .then((response) => {
            if (!response.ok) {
                throw new Error('Not 2xx response', { cause: response });
            }

            return response.text();
        })
        .then((html) => {
            const containerId = container.getAttribute('id');

            container.classList.remove('loading');
            container.innerHTML = html;

            loadScriptsFromContainer(containerId);

            recoverContainerIDS(containerId);
        })
        .catch((error) => {
            console.error('Error loading the page: ', error);
            showErrorPage(true);
        })
        .finally(() => {
            setTimeout(() => {
                displayLoading(false);
                changeTabIcon(tabEl, container, false);
                const containerId = container.getAttribute('id');

                chromeTabs.emit('contentRender', {
                    container,
                    containerId,
                });
            }, 700);
        });
}
function displayLoading(show) {
    const loadingElement = document.getElementById('loading');
    loadingElement.style.setProperty('--animate-duration', '.3s');
    if (show) {
        loadingElement.classList.remove(
            'animate__animated',
            'animate__fadeOut',
        );
        loadingElement.classList.add('animate__animated', 'animate__fadeIn');
        loadingElement.hidden = false;
    } else {
        hideLoading();
    }
}

function hideLoading() {
    const loadingElement = document.getElementById('loading');
    if (loadingElement.getAttribute('hidden') !== null) return;

    loadingElement.style.setProperty('--animate-duration', '.3s');

    loadingElement.classList.remove('animate__animated', 'animate__fadeIn');
    loadingElement.classList.add('animate__animated', 'animate__fadeOut');

    loadingElement.addEventListener(
        'animationend',
        () => {
            loadingElement.hidden = true;
        },
        { once: true },
    );
}

function showErrorPage(show) {
    const errorPage = document.getElementById('error_page');
    errorPage.style.setProperty('--animate-duration', '.3s');
    if (show) {
        errorPage.classList.remove('animate__animated', 'animate__fadeOut');
        errorPage.classList.add('animate__animated', 'animate__fadeIn');
        errorPage.hidden = false;
    } else {
        errorPage.style.setProperty('--animate-duration', '.3s');

        errorPage.classList.remove('animate__animated', 'animate__fadeIn');
        errorPage.classList.add('animate__animated', 'animate__fadeOut');

        errorPage.addEventListener(
            'animationend',
            () => {
                errorPage.hidden = true;
            },
            { once: true },
        );
    }
}

function hideAllPages() {
    document.getElementById('start_page').hidden = true;
    document.getElementById('error_page').hidden = true;
}

function removeContainerIds(containerId) {
    const container = document.getElementById(containerId);

    if (!container) return;

    const elements = container.querySelectorAll('[name], [id]');

    elements.forEach((el) => {
        if (el.name) {
            el.setAttribute('data-original-name', el.name);
            el.removeAttribute('name');
        }
        if (el.id) {
            el.setAttribute('data-original-id', el.id);
            el.removeAttribute('id');
        }
    });
}

function recoverContainerIDS(containerId) {
    const container = document.getElementById(containerId);

    if (!container) return;

    const elements = container.querySelectorAll(
        '[data-original-name], [data-original-id]',
    );

    elements.forEach((el) => {
        if (el.getAttribute('data-original-name')) {
            el.setAttribute('name', el.getAttribute('data-original-name'));
        }
        if (el.getAttribute('data-original-id')) {
            const originalId = el.getAttribute('data-original-id');
            el.setAttribute('id', originalId);
            const labels = document.querySelectorAll(`label[for="${el.id}"]`);
            labels.forEach((label) => label.setAttribute('for', originalId));
        }
    });
}

function handleTabSwitch(url, title, favicon) {
    fetchContentAndAddTab(url, title, favicon);
}

function tryAndDeleteTab(url) {
    const tab = chromeTabs.findTabByUrl(url);

    if (tab) {
        chromeTabs.removeTab(tab);
    }
}

function fetchContentAndAddTab(url, title, favicon) {
    if (url === '/admin/') {
        displayLoading(false);
        return;
    }

    if (!chromeTabs.findTabByUrl(url)) {
        const tabProperties = {
            title: title,
            favicon: favicon,
            url: url,
        };

        chromeTabs.addTab(tabProperties);
    }

    hideAllPages();
    chromeTabs.setCurrentTab(chromeTabs.findTabByUrl(url));

    const tabEl = chromeTabs.findTabByUrl(url);
    const tabId = tabEl ? tabEl.getAttribute('data-tab-id') : null;

    if (!tabId) {
        const tabProperties = { title, favicon, url };
        chromeTabs.addTab(tabProperties);
    } else {
        const contentContainer = document.getElementById(`content-${tabId}`);

        document.getElementById('start_page').hidden = true;

        if (contentContainer.innerHTML === '' && !contentCache[url]) {
            displayLoading(true);
            changeTabIcon(tabEl, contentContainer, true);
            contentContainer.classList.add('loading');
            fetchContent(url, contentContainer, tabEl, false, title);

            contentCache[url] = 1;
        }

        recoverContainerIDS(`content-${tabId}`);
    }
}

const loadedScripts = new Set();

function isScriptLoaded(src) {
    return (
        document.querySelector(
            `footer script[src="${src}"], head script[src="${src}"]`,
        ) !== null
    );
}

function loadScriptSequentially(script, containerId) {
    return new Promise((resolve, reject) => {
        const src = script.src;
        const contentHash = src
            ? src
            : 'hash-' +
              btoa(unescape(encodeURIComponent(script.textContent))).substring(
                  0,
                  500,
              );

        if (script.textContent.includes('let SITE_URL')) {
            console.log('SITE URL detected - skip');
            resolve();
            return;
        }

        if (
            (loadedScripts.has(contentHash) || (src && isScriptLoaded(src))) &&
            !script.hasAttribute('data-loadevery')
        ) {
            console.log('Script already loaded:', contentHash);
            resolve();
            return;
        }

        const newScript = document.createElement('script');
        Array.from(script.attributes).forEach((attr) => {
            newScript.setAttribute(attr.name, attr.value);
        });
        newScript.setAttribute('data-container-id', containerId);

        if (src) {
            newScript.src = src;
            newScript.onload = () => {
                loadedScripts.add(contentHash);
                resolve();
            };
            newScript.onerror = () => {
                console.error('Error loading script:', src);
                reject(new Error(`Error loading script: ${src}`));
            };
        } else {
            newScript.textContent = script.textContent;
            setTimeout(() => {
                loadedScripts.add(contentHash);
                resolve();
            }, 0);
        }

        document.head.appendChild(newScript);
    });
}

async function loadScriptsFromContainer(containerId) {
    const container = document.getElementById(containerId);
    if (!container) {
        console.error('Container not found:', containerId);
        return;
    }

    const scripts = Array.from(container.querySelectorAll('script'));
    for (const script of scripts) {
        try {
            // console.log('LOADING script', script);
            await loadScriptSequentially(script, containerId);
            script.parentNode.removeChild(script);
        } catch (error) {
            console.error('Error loading script:', error);
            break;
        }
    }

    initEditor($(container));
}

function removeScriptsByContainerId(containerId) {
    document
        .querySelectorAll(`script[data-container-id="${containerId}"]`)
        .forEach((script) => {
            loadedScripts.delete(script.src + containerId);
            document.head.removeChild(script);
        });
}

function initEditor(container) {
    container.find('.editor-ace').each(function () {
        let editor = ace.edit(this);
        let unformattedContent = editor.getSession().getValue();
        let formattedContent = js_beautify(unformattedContent, {
            indent_size: 4,
            space_in_empty_paren: true,
        });
        editor.getSession().setValue(formattedContent);
        editor.setTheme('ace/theme/solarized_dark');
        editor.session.setMode('ace/mode/json');
    });
}

function changeTabIcon(tabEl, container, isLoading) {
    const faviconEl = tabEl.querySelector('.chrome-tab-favicon');
    const titleEl = tabEl.querySelector('.chrome-tab-title');

    if (isLoading) {
        faviconEl.innerHTML = '<span class="tabLoader"></span>';
    } else {
        let originalIcon = tabEl.getAttribute('data-original-icon');
        // if (!originalIcon) {
        const tabUrl = tabEl.getAttribute('data-tab-url');
        const matchingLinkIcon = document.querySelector(
            `.sidebar-menu a[href="${tabUrl}"] i`,
        );
        if (matchingLinkIcon) {
            originalIcon = matchingLinkIcon.outerHTML;
        }
        // }

        if (originalIcon) {
            faviconEl.innerHTML = originalIcon;
            faviconEl.hidden = false;
        } else {
            faviconEl.hidden = true;
        }

        const titleSelect = container.querySelector('[data-page-title]');
        if (titleSelect) {
            titleEl.textContent = titleSelect.innerText;
            document.title = titleSelect.innerText;

            chromeTabs.updateTab(tabEl, {
                title: titleSelect.innerText,
                favicon: originalIcon,
            });
        }
    }
}

$(function () {
    const links = document.querySelectorAll(
        '.flex-menu a[href], .start-page-container-items a[href]',
    );
    links.forEach((link) => {
        link.addEventListener('click', function (event) {
            if ($(event.currentTarget).hasClass('ignore')) return;

            event.preventDefault();
            const iconElement = link.querySelector('i');
            let faviconHTML = '',
                text = null;
            if (iconElement && !iconElement.classList.contains('ignore')) {
                const iconClass = iconElement.className
                    .split(' ')
                    .find((cn) => cn.startsWith('ph-'));
                if (iconClass) {
                    faviconHTML = `<i class="ph ${iconClass}"></i>`;
                }
            }

            if (link.querySelector('.name-desc h3')) {
                text = link.querySelector('.name-desc h3').textContent.trim();
            } else {
                text = link.textContent.trim();
            }
            const relativeUrl = new URL(link.href).pathname;
            handleTabSwitch(relativeUrl, text || 'Loading...', faviconHTML);
        });
    });

    $(document).on(
        'click',
        `.admin-header a.btn[href], 
        .table-action-buttons a[href], 
        .social-action-buttons a[href], 
        .payment-action-buttons a[href], 
        .payment-promo-action-buttons a[href], 
        .servers-action-buttons a[href], 
        .user-action-buttons a[href], 
        .sortable-buttons a[href], 
        .back-btn[href], 
        [data-tab]`,
        (event) => {
            let link = event.currentTarget;

            if ($(link).hasClass('ignore')) return;

            event.preventDefault();
            const iconElement = link.querySelector('i');
            let faviconHTML = '';
            if (iconElement && !iconElement.classList.contains('ignore')) {
                const iconClass = iconElement.className
                    .split(' ')
                    .find((cn) => cn.startsWith('ph-'));
                if (iconClass) {
                    faviconHTML = `<i class="ph ${iconClass}"></i>`;
                }
            }

            const relativeUrl = new URL(link.href).pathname;
            handleTabSwitch(
                relativeUrl,
                link.textContent.trim() || 'Loading...',
                faviconHTML,
            );
        },
    );

    $(document).on('click', '.refresh_page', (event) => refreshCurrentPage());
    $(document).on('click', '#closeErrorBlock', (event) => {
        showErrorPage(false);
    });

    el.addEventListener('activeTabChange', ({ detail }) => {
        if (detail.activeTabEl)
            removeContainerIds(
                `content-${detail.activeTabEl.getAttribute('data-tab-id')}`,
            );

        if (detail.tabEl)
            recoverContainerIDS(
                `content-${detail.tabEl.getAttribute('data-tab-id')}`,
            );
    });

    el.addEventListener('tabRemove', ({ detail }) => {
        if (detail.tabEl)
            contentCache[detail.tabEl.getAttribute('data-tab-url')] = false;
    });
});
