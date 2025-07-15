/**
 * Tabs system implementation
 * Supports nested tabs and HTMX integration
 */

// Tab utilities
const TabUtils = {
    /**
     * Updates the underline position and width based on active tab
     * @param {HTMLElement} container - The tabs container element
     */
    updateUnderline(container) {
        if (!container) return;

        const activeTab = container.querySelector('.tab-item.active');
        const underline = container.querySelector('.underline');

        if (!underline) return;

        if (activeTab) {
            underline.style.width = `${activeTab.offsetWidth}px`;
            underline.style.left = `${activeTab.offsetLeft}px`;
        } else {
            underline.style.width = '0px';
            underline.style.left = '0px';
        }
    },

    /**
     * Gets the tab content container associated with a tabs container
     * @param {HTMLElement} container - The tabs container element
     * @returns {HTMLElement|null} - The associated tab content container
     */
    getTabsContentContainer(container) {
        if (!container) return null;

        const tabsId = container.getAttribute('data-tabs-id');
        if (tabsId) {
            const tabsContent = document.querySelector(
                `[name="${tabsId}"], .tabs-content[data-name="${tabsId}"]`,
            );
            if (tabsContent) {
                return tabsContent;
            }
        }
        
        const parentElement = container.parentElement;
        if (parentElement) {
            const siblingTabsContent = parentElement.querySelector('.tabs-content');
            if (siblingTabsContent) {
                return siblingTabsContent;
            }
        }
        
        return container;
    },

    /**
     * Shows a specific tab and hides others in the same container
     * @param {HTMLElement} container - The tabs container element
     * @param {string} tabId - The ID of the tab to show
     * @returns {HTMLElement|null} - The activated tab element
     */
    showTab(container, tabId) {
        if (!container || !tabId) return null;

        const tabsContentContainer = this.getTabsContentContainer(container);
        if (!tabsContentContainer) return null;

        const targetTab = document.getElementById(tabId);
        if (!targetTab) return null;

        const tabContents = tabsContentContainer.querySelectorAll('.tab-content');
        
        tabContents.forEach((tabContent) => {
            tabContent.classList.remove('active');
            tabContent.style.display = 'none';
        });

        targetTab.classList.add('active');
        targetTab.style.display = '';

        this.processNestedTabs(targetTab);
        if (typeof window.refreshCharts === 'function') {
            window.refreshCharts(targetTab);
        }
        
        return targetTab;
    },

    /**
     * Process nested tabs within an element
     * @param {HTMLElement} element - Element containing nested tabs
     */
    processNestedTabs(element) {
        if (!element) return;

        element.querySelectorAll('.tabs-container').forEach((nestedContainer) => {
            this.updateUnderline(nestedContainer);
            this.initializeTabContents(nestedContainer);
            
            const activeTabItem = nestedContainer.querySelector('.tab-item.active');
            if (activeTabItem) {
                const activeTabLink = activeTabItem.querySelector('a');
                if (activeTabLink && activeTabLink.dataset.tabId) {
                    const nestedTabId = activeTabLink.dataset.tabId;
                    const nestedTargetTab = document.getElementById(nestedTabId);
                    if (nestedTargetTab) {
                        this.processNestedTabs(nestedTargetTab);
                    }
                }
            }
        });
    },

    /**
     * Activates a tab item and updates the UI accordingly
     * @param {HTMLElement} tabItem - The tab item to activate
     */
    activateTabItem(tabItem) {
        if (!tabItem) return;

        const container = tabItem.closest('.tabs-container');
        if (!container) return;

        container.querySelectorAll('.tab-item').forEach((item) => {
            item.classList.remove('active');
        });

        tabItem.classList.add('active');
        this.updateUnderline(container);
    },

    /**
     * Initializes tab contents for a container based on active tab
     * @param {HTMLElement} container - The tabs container element
     */
    initializeTabContents(container) {
        if (!container) return;

        const activeTabItem = container.querySelector('.tab-item.active');
        if (!activeTabItem) {
            const firstTabItem = container.querySelector('.tab-item');
            if (firstTabItem) {
                firstTabItem.classList.add('active');
                const firstTabLink = firstTabItem.querySelector('a');
                if (firstTabLink && firstTabLink.dataset.tabId) {
                    this.showTab(container, firstTabLink.dataset.tabId);
                    this.updateUnderline(container);
                    return;
                }
            }
            return;
        }

        const activeTabLink = activeTabItem.querySelector('a');
        if (!activeTabLink) return;

        const tabId = activeTabLink.dataset.tabId;
        if (!tabId) return;

        this.showTab(container, tabId);
    },

    /**
     * Process all tab containers in the document
     */
    processAllTabs() {
        const rootContainers = document.querySelectorAll('.tabs-container');
        rootContainers.forEach((container) => {
            this.updateUnderline(container);
            
            const activeTabItem = container.querySelector('.tab-item.active');
            if (activeTabItem) {
                const activeTabLink = activeTabItem.querySelector('a');
                if (activeTabLink && activeTabLink.dataset.tabId) {
                    const tabId = activeTabLink.dataset.tabId;
                    
                    const targetTab = document.getElementById(tabId);
                    if (targetTab) {
                        const tabsContentContainer = this.getTabsContentContainer(container);
                        if (tabsContentContainer) {
                            tabsContentContainer.querySelectorAll('.tab-content').forEach((tabContent) => {
                                tabContent.classList.remove('active');
                                tabContent.style.display = 'none';
                            });
                            
                            targetTab.classList.add('active');
                            targetTab.style.display = '';
                            
                            this.processNestedTabs(targetTab);
                        }
                    }
                }
            } else {
                this.initializeTabContents(container);
            }
        });
    },

    /**
     * Handle tab link click event
     * @param {Event} e - The click event
     */
    handleTabClick(e) {
        const link = e.target.closest('.tabs-nav a');
        if (!link) return;

        const container = link.closest('.tabs-container');
        if (!container) return;

        if (!link.hasAttribute('hx-get')) {
            e.preventDefault();
        }

        const tabId = link.dataset.tabId;
        if (!tabId) return;

        const isAlreadyActive = link.parentElement.classList.contains('active');
        if (isAlreadyActive) return;
        
        this.showTab(container, tabId);
        this.activateTabItem(link.parentElement);
    },

    /**
     * Handles HTMX content swaps
     * @param {Event} event - The HTMX afterSwap event
     */
    handleHtmxSwap(event) {
        const swappedElement = event.detail?.target || event.target;

        const containersWithUnderlines = [];
        document.querySelectorAll('.tabs-container').forEach(container => {
            if (!swappedElement.contains(container)) {
                const underline = container.querySelector('.underline');
                const activeTab = container.querySelector('.tab-item.active');
                if (underline && activeTab) {
                    containersWithUnderlines.push({
                        container,
                        width: activeTab.offsetWidth + 'px',
                        left: activeTab.offsetLeft + 'px'
                    });
                }
            }
        });

        if (swappedElement.classList.contains('tabs-container')) {
            requestAnimationFrame(() => {
                this.updateUnderline(swappedElement);
                this.initializeTabContents(swappedElement);
            });
        } else {
            swappedElement.querySelectorAll('.tabs-container').forEach((container) => {
                requestAnimationFrame(() => {
                    this.updateUnderline(container);
                    this.initializeTabContents(container);
                });
            });

            if (swappedElement.classList.contains('tab-content')) {
                const contentEl = swappedElement;
                const tabId = contentEl.id;

                const headingLink = document.querySelector(
                    `.tabs-nav a[data-tab-id="${tabId}"]`,
                );
                if (!headingLink) return;

                const tabsContainer = headingLink.closest('.tabs-container');
                if (!tabsContainer) return;

                this.activateTabItem(headingLink.parentElement);

                const wrapper = this.getTabsContentContainer(tabsContainer);
                if (!wrapper) return;

                wrapper.querySelectorAll('.tab-content').forEach((tc) => {
                    tc.classList.remove('active');
                    tc.style.display = 'none';
                });

                contentEl.classList.add('active');
                contentEl.style.display = '';

                this.processNestedTabs(contentEl);
            }
        }

        requestAnimationFrame(() => {
            containersWithUnderlines.forEach(item => {
                const underline = item.container.querySelector('.underline');
                if (underline) {
                    underline.style.width = item.width;
                    underline.style.left = item.left;
                }
            });
        });
    },

    /**
     * Handles window resize events
     */
    handleResize() {
        document.querySelectorAll('.tabs-container').forEach((container) => {
            this.updateUnderline(container);
        });
    },

    /**
     * Initialize the tabs system
     */
    init() {
        document.addEventListener('click', this.handleTabClick.bind(this));
        window.addEventListener('resize', this.handleResize.bind(this));
        htmx.on('htmx:afterSwap', this.handleHtmxSwap.bind(this));

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', this.processAllTabs.bind(this));
        } else {
            this.processAllTabs();
        }
    },
};

TabUtils.init();
