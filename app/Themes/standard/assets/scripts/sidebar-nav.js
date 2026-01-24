class SidebarNav {
    constructor() {
        this.sidebar = document.getElementById('sidebar-nav');
        this.overlay = document.getElementById('sidebar-overlay');
        this.toggleBtn = document.getElementById('sidebar-toggle');
        this.mobileToggle = document.getElementById('mobile-sidebar-toggle');
        this.mobileCloseBtn = document.getElementById('sidebar-mobile-close');

        if (!this.sidebar) return;

        this.isMini = document.documentElement.getAttribute('data-sidebar-style') === 'mini';
        // Don't use collapsed state in mini mode - they are mutually exclusive
        this.isCollapsed = !this.isMini && document.documentElement.getAttribute('data-sidebar-collapsed') === 'true';
        this.isMobileOpen = false;

        this.floatingContainer = null;
        this.currentFloatingElement = null;
        this.activeDropdown = null;
        this.floatingCleanup = null;
        this.hideTimeout = null;

        this.init();
    }

    isMobile() {
        return window.innerWidth <= 991;
    }

    init() {
        this.createFloatingContainer();
        this.bindEvents();
        this.initDropdowns();
        this.updateState();
        this.updateActiveItems();
        this.updateTooltips();
    }

    createFloatingContainer() {
        this.floatingContainer = document.createElement('div');
        this.floatingContainer.id = 'sidebar-floating-container';
        this.floatingContainer.style.cssText = 'position: fixed; top: 0; left: 0; width: 0; height: 0; z-index: 9999; pointer-events: none;';
        document.body.appendChild(this.floatingContainer);
    }

    bindEvents() {
        this.toggleBtn?.addEventListener('click', () => this.toggle());
        this.mobileToggle?.addEventListener('click', () => this.toggleMobile());
        this.mobileCloseBtn?.addEventListener('click', () => this.closeMobile());
        this.overlay?.addEventListener('click', () => this.closeMobile());

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                if (this.isMobileOpen) {
                    this.closeMobile();
                }
                this.hideFloatingDropdown();
                this.closeAllInlineDropdowns();
            }
        });

        window.addEventListener('resize', () => {
            if (window.innerWidth > 991 && this.isMobileOpen) {
                this.closeMobile();
            }
            this.hideFloatingDropdown();
            // Update state on resize to handle mobile/desktop transitions
            this.updateState();
        });

        document.body.addEventListener('htmx:afterSwap', () => {
            setTimeout(() => this.updateActiveItems(), 50);
        });

        window.addEventListener('popstate', () => {
            setTimeout(() => this.updateActiveItems(), 50);
        });

        document.addEventListener('click', (e) => {
            if (this.activeDropdown && this.currentFloatingElement) {
                if (!this.currentFloatingElement.contains(e.target) && !this.activeDropdown.contains(e.target)) {
                    this.hideFloatingDropdown();
                }
            }
        });

        this.sidebar.querySelector('.sidebar-nav__nav')?.addEventListener('scroll', () => {
            this.hideFloatingDropdown();
        });
    }

    shouldUseFloatingDropdown() {
        if (window.innerWidth <= 991) {
            return false;
        }
        return this.isCollapsed || this.isMini;
    }

    initDropdowns() {
        const dropdowns = this.sidebar.querySelectorAll('[data-sidebar-dropdown]');

        dropdowns.forEach(dropdown => {
            if (dropdown.dataset.sidebarEventsBound) return;

            const trigger = dropdown.querySelector('[data-sidebar-dropdown-trigger]');
            if (!trigger) return;

            const submenu = dropdown.querySelector('[data-sidebar-submenu]');
            const miniDropdown = dropdown.querySelector('.sidebar-nav__mini-dropdown');
            const menuSource = submenu || miniDropdown;

            if (!menuSource) return;

            dropdown.addEventListener('mouseenter', () => {
                if (!this.shouldUseFloatingDropdown()) return;
                clearTimeout(this.hideTimeout);
                this.showFloatingDropdown(dropdown, trigger, menuSource);
            });

            dropdown.addEventListener('mouseleave', (e) => {
                if (!this.shouldUseFloatingDropdown()) return;
                const floatingDropdown = this.currentFloatingElement;
                if (floatingDropdown && !floatingDropdown.classList.contains('is-closing') && e.relatedTarget && floatingDropdown.contains(e.relatedTarget)) {
                    return;
                }
                this.scheduleHide();
            });

            trigger.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();

                if (this.shouldUseFloatingDropdown()) {
                    if (this.activeDropdown === dropdown) {
                        this.hideFloatingDropdown();
                    } else {
                        this.showFloatingDropdown(dropdown, trigger, menuSource);
                    }
                } else {
                    this.toggleInlineDropdown(dropdown, submenu);
                }
            });

            dropdown.dataset.sidebarEventsBound = "true";
        });
    }

    toggleInlineDropdown(dropdown, submenu) {
        const isOpen = dropdown.classList.contains('is-open');
        dropdown.classList.toggle('is-open', !isOpen);
        submenu?.classList.toggle('is-open', !isOpen);
    }

    closeAllInlineDropdowns() {
        const dropdowns = this.sidebar.querySelectorAll('[data-sidebar-dropdown]');
        dropdowns.forEach(d => {
            d.classList.remove('is-open');
            d.querySelector('[data-sidebar-submenu]')?.classList.remove('is-open');
        });
    }

    showFloatingDropdown(dropdown, trigger, menuSource) {
        if (!window.FloatingUIDOM) {
            console.warn('FloatingUIDOM not loaded');
            return;
        }

        this.hideFloatingDropdown();
        this.activeDropdown = dropdown;

        const floatingDropdown = this.createFloatingDropdownElement(menuSource, trigger);
        this.currentFloatingElement = floatingDropdown;
        this.floatingContainer.appendChild(floatingDropdown);
        floatingDropdown.style.pointerEvents = 'auto';

        floatingDropdown.addEventListener('mouseenter', () => {
            clearTimeout(this.hideTimeout);
        });

        floatingDropdown.addEventListener('mouseleave', (e) => {
            if (e.relatedTarget && dropdown.contains(e.relatedTarget)) {
                return;
            }
            this.scheduleHide();
        });

        const { computePosition, offset, flip, shift, autoUpdate } = window.FloatingUIDOM;

        const updatePosition = () => {
            computePosition(trigger, floatingDropdown, {
                placement: 'right-start',
                strategy: 'fixed',
                middleware: [
                    offset(10),
                    flip({
                        fallbackPlacements: ['right-end', 'left-start', 'left-end'],
                        padding: 8,
                    }),
                    shift({ padding: 8 }),
                ],
            }).then(({ x, y, placement }) => {
                Object.assign(floatingDropdown.style, {
                    left: `${x}px`,
                    top: `${y}px`,
                });
                floatingDropdown.setAttribute('data-placement', placement);
            });
        };

        updatePosition();

        requestAnimationFrame(() => {
            floatingDropdown.classList.add('is-visible');
        });

        this.floatingCleanup = autoUpdate(trigger, floatingDropdown, updatePosition);
    }

    createFloatingDropdownElement(menuSource, trigger) {
        const dropdown = document.createElement('div');
        dropdown.className = 'sidebar-floating-dropdown';

        const titleText = trigger.querySelector('.sidebar-nav__item-text')?.textContent || '';
        const titleIcon = trigger.querySelector('.sidebar-nav__item-icon')?.innerHTML || '';

        let html = `
            <div class="sidebar-floating-dropdown__title">
                ${titleIcon ? `<span>${titleIcon}</span>` : ''}
                ${titleText}
            </div>
            <div class="sidebar-floating-dropdown__items">
        `;

        const innerWrapper = menuSource.querySelector('.sidebar-nav__submenu-inner');
        const itemsContainer = innerWrapper || menuSource;
        const items = itemsContainer.querySelectorAll(':scope > a, :scope > .sidebar-nav__subgroup, :scope > .sidebar-nav__mini-dropdown__group, :scope > .sidebar-nav__mini-dropdown__item, :scope > .sidebar-nav__subitem');

        items.forEach(item => {
            if (item.classList.contains('sidebar-nav__subgroup') || item.classList.contains('sidebar-nav__mini-dropdown__group')) {
                const groupTitle = item.querySelector('.sidebar-nav__subgroup-title, .sidebar-nav__mini-dropdown__group-title')?.textContent || '';
                html += `<div class="sidebar-floating-dropdown__group">`;
                html += `<div class="sidebar-floating-dropdown__group-title">${groupTitle}</div>`;

                const subItems = item.querySelectorAll('a');
                subItems.forEach(subItem => {
                    html += this.createDropdownItemHTML(subItem);
                });

                html += `</div>`;
            } else if (item.tagName === 'A') {
                html += this.createDropdownItemHTML(item);
            }
        });

        html += `</div>`;
        dropdown.innerHTML = html;

        dropdown.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                this.hideFloatingDropdown();
            });
        });

        return dropdown;
    }

    createDropdownItemHTML(item) {
        const href = item.getAttribute('href') || '#';
        const target = item.getAttribute('target') || '';
        const text = item.textContent?.trim() || '';
        const isActive = item.classList.contains('active');
        const iconEl = item.querySelector('svg, .sidebar-nav__subitem-icon svg');
        const iconHTML = iconEl ? `<span class="sidebar-floating-dropdown__item-icon">${iconEl.outerHTML}</span>` : '';

        return `
            <a href="${href}"
               ${target ? `target="${target}"` : ''}
               class="sidebar-floating-dropdown__item ${isActive ? 'active' : ''}"
               hx-boost="true"
               hx-target="#main"
               hx-swap="outerHTML transition:true">
                ${iconHTML}
                <span>${text}</span>
            </a>
        `;
    }

    scheduleHide() {
        clearTimeout(this.hideTimeout);
        this.hideTimeout = setTimeout(() => {
            this.hideFloatingDropdown();
        }, 200);
    }

    hideFloatingDropdown() {
        clearTimeout(this.hideTimeout);

        if (this.floatingCleanup) {
            this.floatingCleanup();
            this.floatingCleanup = null;
        }

        const floatingDropdown = this.currentFloatingElement || this.floatingContainer.querySelector('.sidebar-floating-dropdown:not(.is-closing)');

        if (floatingDropdown) {
            floatingDropdown.classList.remove('is-visible');
            floatingDropdown.classList.add('is-closing');

            if (this.currentFloatingElement === floatingDropdown) {
                this.currentFloatingElement = null;
            }

            setTimeout(() => {
                floatingDropdown.remove();
            }, 150);
        }

        this.activeDropdown = null;
    }

    updateActiveItems() {
        const currentPath = window.location.pathname;

        const sidebarItems = this.sidebar.querySelectorAll('.sidebar-nav__item[href], .sidebar-nav__subitem, .sidebar-nav__mini-dropdown__item');
        sidebarItems.forEach(item => {
            if (item.tagName === 'A') {
                const href = item.getAttribute('href');
                if (href) {
                    try {
                        const itemPath = new URL(href, window.location.origin).pathname;
                        const isActive = this.isPathMatch(currentPath, itemPath);
                        item.classList.toggle('active', isActive);
                    } catch (e) { }
                }
            }
        });

        const dropdowns = this.sidebar.querySelectorAll('[data-sidebar-dropdown]');
        dropdowns.forEach(dropdown => {
            const hasActiveChild = dropdown.querySelector('.sidebar-nav__subitem.active, .sidebar-nav__mini-dropdown__item.active');
            const trigger = dropdown.querySelector('.sidebar-nav__item');
            if (trigger && trigger.tagName === 'BUTTON') {
                trigger.classList.toggle('active', !!hasActiveChild);
            }
        });

        const floatingDropdown = this.currentFloatingElement;
        if (floatingDropdown && !floatingDropdown.classList.contains('is-closing')) {
            floatingDropdown.querySelectorAll('.sidebar-floating-dropdown__item').forEach(item => {
                const href = item.getAttribute('href');
                if (href) {
                    try {
                        const itemPath = new URL(href, window.location.origin).pathname;
                        const isActive = this.isPathMatch(currentPath, itemPath);
                        item.classList.toggle('active', isActive);
                    } catch (e) { }
                }
            });
        }
    }

    isPathMatch(currentPath, itemPath) {
        currentPath = currentPath.replace(/\/$/, '') || '/';
        itemPath = itemPath.replace(/\/$/, '') || '/';

        if (currentPath === itemPath) return true;
        if (itemPath !== '/' && currentPath.startsWith(itemPath + '/')) {
            return true;
        }

        return false;
    }

    toggle() {
        // Don't toggle in mini mode - mini mode has its own behavior
        if (this.isMini) return;

        this.isCollapsed = !this.isCollapsed;
        this.updateState();
        this.saveToCookie();

        if (this.isCollapsed) {
            this.closeAllInlineDropdowns();
        }

        this.hideFloatingDropdown();
    }

    toggleMobile() {
        this.isMobileOpen = !this.isMobileOpen;
        this.updateMobileState();
    }

    closeMobile() {
        this.isMobileOpen = false;
        this.updateMobileState();
    }

    updateState() {
        // On mobile, always use standard (not collapsed) sidebar appearance
        const shouldCollapse = this.isCollapsed && !this.isMobile() && !this.isMini;
        this.sidebar.classList.toggle('is-collapsed', shouldCollapse);
        document.documentElement.setAttribute('data-sidebar-collapsed', this.isCollapsed ? 'true' : 'false');
        this.updateTooltips();
    }

    updateTooltips() {
        const items = this.sidebar.querySelectorAll('.sidebar-nav__item');
        // Don't show tooltips on mobile or in mini mode
        const shouldShowTooltips = this.isCollapsed && !this.isMini && !this.isMobile();

        items.forEach(item => {
            const text = item.querySelector('.sidebar-nav__item-text')?.textContent?.trim();
            if (!text) return;

            if (shouldShowTooltips) {
                if (!item.hasAttribute('data-original-tooltip')) {
                    item.setAttribute('data-original-tooltip', item.getAttribute('data-tooltip') || '');
                }
                item.setAttribute('data-tooltip', text);
                item.setAttribute('data-tooltip-position', 'right');
            } else {
                const originalTooltip = item.getAttribute('data-original-tooltip');
                if (originalTooltip) {
                    item.setAttribute('data-tooltip', originalTooltip);
                } else {
                    item.removeAttribute('data-tooltip');
                }
                item.removeAttribute('data-tooltip-position');
                item.removeAttribute('data-original-tooltip');
            }
        });
    }

    updateMobileState() {
        this.sidebar.classList.toggle('is-mobile-open', this.isMobileOpen);
        this.overlay?.classList.toggle('is-visible', this.isMobileOpen);
        document.body.classList.toggle('sidebar-mobile-open', this.isMobileOpen);
    }

    saveToCookie() {
        const date = new Date();
        date.setTime(date.getTime() + (365 * 24 * 60 * 60 * 1000));
        document.cookie = `sidebar_collapsed=${this.isCollapsed};expires=${date.toUTCString()};path=/;SameSite=Lax`;
    }

    destroy() {
        this.hideFloatingDropdown();
        if (this.floatingContainer) {
            this.floatingContainer.remove();
        }
    }
}

function initSidebarNav() {
    const sidebarElement = document.getElementById('sidebar-nav');

    if (!sidebarElement) {
        if (window.sidebarNav) {
            window.sidebarNav.destroy?.();
            window.sidebarNav = null;
        }
        return;
    }

    if (window.sidebarNav) {
        if (window.sidebarNav.sidebar === sidebarElement) {
            window.sidebarNav.updateActiveItems();
            return;
        }

        window.sidebarNav.destroy?.();
    }

    window.sidebarNav = new SidebarNav();
}

window.initSidebarNav = initSidebarNav;

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initSidebarNav);
} else {
    initSidebarNav();
}

document.body.addEventListener('htmx:afterSwap', () => setTimeout(initSidebarNav, 50));
