class SidebarNav {
    constructor() {
        this.sidebar = document.getElementById('sidebar-nav');
        this.overlay = document.getElementById('sidebar-overlay');
        this.toggleBtn = document.getElementById('sidebar-toggle');
        this.mobileToggle = document.getElementById('mobile-sidebar-toggle');
        this.mobileCloseBtn = document.getElementById('sidebar-mobile-close');

        if (!this.sidebar) return;

        this.isMini = document.documentElement.getAttribute('data-sidebar-style') === 'mini';
        this.isContained = document.documentElement.getAttribute('data-sidebar-contained') === 'true';
        // Don't use collapsed state in mini mode (unless contained) - they are mutually exclusive
        this.isCollapsed = (this.isContained || !this.isMini) && document.documentElement.getAttribute('data-sidebar-collapsed') === 'true';
        this.isMobileOpen = false;

        this.floatingContainer = null;
        this.currentFloatingElement = null;
        this.activeDropdown = null;
        this.floatingCleanup = null;
        this.hideTimeout = null;

        this.hoverZone = null;
        this.hoverPreviewTimeout = null;
        this.hoverPreviewActive = false;

        this.init();
    }

    isMobile() {
        return window.innerWidth <= 768;
    }

    init() {
        this.createFloatingContainer();
        this.bindEvents();
        this.initDropdowns();
        this.initSubgroups();
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
        this.containedToggle = document.getElementById('sidebar-contained-toggle');
        this.containedCollapse = document.getElementById('sidebar-contained-collapse');

        this.toggleBtn?.addEventListener('click', () => this.toggle());
        this.containedToggle?.addEventListener('click', () => this.toggle());
        this.containedCollapse?.addEventListener('click', () => this.toggle());
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

        // Hover preview for contained+collapsed mode
        this.hoverZone = document.getElementById('sidebar-hover-zone');

        if (this.hoverZone) {
            this.hoverZone.addEventListener('mouseenter', () => {
                if (this.isContained && this.isCollapsed && !this.isMobile()) {
                    this.showHoverPreview();
                }
            });

            this.hoverZone.addEventListener('mouseleave', (e) => {
                if (this.sidebar.contains(e.relatedTarget)) return;
                this.scheduleHideHoverPreview();
            });
        }

        this.sidebar.addEventListener('mouseenter', () => {
            if (this.hoverPreviewActive) {
                clearTimeout(this.hoverPreviewTimeout);
            }
        });

        this.sidebar.addEventListener('mouseleave', (e) => {
            if (!this.hoverPreviewActive) return;
            if (this.hoverZone && this.hoverZone.contains(e.relatedTarget)) return;
            if (this.currentFloatingElement && this.currentFloatingElement.contains(e.relatedTarget)) return;
            this.scheduleHideHoverPreview();
        });
    }

    shouldUseFloatingDropdown() {
        if (this.isMobile() || this.hoverPreviewActive) {
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
            const menuSource = submenu;

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

    initSubgroups() {
        const subgroups = this.sidebar.querySelectorAll('[data-sidebar-subgroup]');

        subgroups.forEach(subgroup => {
            if (subgroup.dataset.subgroupBound) return;

            const trigger = subgroup.querySelector('[data-sidebar-subgroup-trigger]');
            if (!trigger) return;

            // Auto-open if contains active item
            const hasActive = subgroup.querySelector('.sidebar-nav__subitem.active');
            if (hasActive) {
                subgroup.classList.add('is-open');
            }

            trigger.addEventListener('click', (e) => {
                e.preventDefault();
                subgroup.classList.toggle('is-open');
            });

            subgroup.dataset.subgroupBound = 'true';
        });
    }

    toggleInlineDropdown(dropdown, submenu) {
        const isOpen = dropdown.classList.contains('is-open');

        if (!isOpen) {
            const siblings = Array.from(dropdown.parentNode.children);
            siblings.forEach(sibling => {
                if (sibling !== dropdown && sibling.classList.contains('sidebar-nav__dropdown')) {
                    sibling.classList.remove('is-open');
                    const siblingSubmenu = sibling.querySelector('.sidebar-nav__submenu');
                    if (siblingSubmenu) {
                        siblingSubmenu.classList.remove('is-open');
                    }
                }
            });
        }

        // Toggle current
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

        if (window.htmx) {
            htmx.process(floatingDropdown);
        }

        floatingDropdown.addEventListener('mouseenter', () => {
            clearTimeout(this.hideTimeout);
            if (this.hoverPreviewActive) {
                clearTimeout(this.hoverPreviewTimeout);
            }
        });

        floatingDropdown.addEventListener('mouseleave', (e) => {
            if (e.relatedTarget && this.sidebar.contains(e.relatedTarget)) {
                return;
            }
            this.scheduleHide();
            if (this.hoverPreviewActive) {
                this.scheduleHideHoverPreview();
            }
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

        const titleText = trigger.querySelector('.sidebar-nav__item-text')?.textContent?.trim() || '';
        const titleIcon = trigger.querySelector('.sidebar-nav__item-icon')?.innerHTML || '';

        let html = `
            <div class="sidebar-floating-dropdown__header">
                ${titleIcon ? `<span class="sidebar-floating-dropdown__header-icon">${titleIcon}</span>` : ''}
                <span class="sidebar-floating-dropdown__header-text">${titleText}</span>
            </div>
            <div class="sidebar-floating-dropdown__content">
                <div class="sidebar-floating-dropdown__items">
        `;

        const innerWrapper = menuSource.querySelector('.sidebar-nav__submenu-inner');
        const itemsContainer = innerWrapper || menuSource;
        const items = itemsContainer.querySelectorAll(':scope > a, :scope > .sidebar-nav__subgroup, :scope > .sidebar-nav__subitem');

        let itemIndex = 0;

        items.forEach(item => {
            if (item.classList.contains('sidebar-nav__subgroup')) {
                const groupTitle = item.querySelector('.sidebar-nav__subgroup-title')?.textContent?.trim() || '';
                html += `<div class="sidebar-floating-dropdown__group">`;
                html += `<div class="sidebar-floating-dropdown__group-title">${groupTitle}</div>`;
                html += `<div class="sidebar-floating-dropdown__group-items">`;

                const subItems = item.querySelectorAll('a');
                subItems.forEach(subItem => {
                    html += this.createDropdownItemHTML(subItem, itemIndex++);
                });

                html += `</div></div>`;
            } else if (item.tagName === 'A') {
                html += this.createDropdownItemHTML(item, itemIndex++);
            }
        });

        html += `</div></div>`;
        dropdown.innerHTML = html;

        dropdown.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                this.hideFloatingDropdown();
            });
        });

        return dropdown;
    }

    createDropdownItemHTML(item, index = 0) {
        const href = item.getAttribute('href') || '#';
        const target = item.getAttribute('target') || '';
        const text = item.textContent?.trim() || '';
        const isActive = item.classList.contains('active');
        const iconEl = item.querySelector('svg, .sidebar-nav__subitem-icon svg');
        const iconHTML = iconEl ? `<span class="sidebar-floating-dropdown__item-icon">${iconEl.outerHTML}</span>` : '';
        const delay = Math.min(index * 20, 200);

        return `
            <a href="${href}"
               ${target ? `target="${target}"` : ''}
               class="sidebar-floating-dropdown__item ${isActive ? 'active' : ''}"
               style="transition-delay: ${delay}ms"
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

    showHoverPreview() {
        clearTimeout(this.hoverPreviewTimeout);
        this.hoverPreviewActive = true;
        this.sidebar.classList.add('is-hover-open');
        this.sidebar.classList.remove('is-collapsed');
        if (this.containedToggle) this.containedToggle.style.display = 'none';
        this.updateTooltips();
    }

    hideHoverPreview() {
        clearTimeout(this.hoverPreviewTimeout);
        this.hoverPreviewActive = false;
        this.hideFloatingDropdown();
        this.sidebar.classList.remove('is-hover-open');
        if (this.isCollapsed && !this.isMobile() && !this.isMini) {
            this.sidebar.classList.add('is-collapsed');
        }
        if (this.containedToggle) this.containedToggle.style.display = '';
        this.updateTooltips();
    }

    scheduleHideHoverPreview() {
        clearTimeout(this.hoverPreviewTimeout);
        this.hoverPreviewTimeout = setTimeout(() => this.hideHoverPreview(), 200);
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

        const sidebarItems = this.sidebar.querySelectorAll('.sidebar-nav__item[href], .sidebar-nav__subitem');
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
            const hasActiveChild = dropdown.querySelector('.sidebar-nav__subitem.active');
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
        // Don't toggle in mini mode unless contained (contained overrides mini behavior)
        if (this.isMini && !this.isContained) return;

        this.hideHoverPreview();
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

        if (this.isMobile() || !this.isCollapsed) {
            this.hideHoverPreview();
        }

        this.updateTooltips();
    }

    updateTooltips() {
        const items = this.sidebar.querySelectorAll('.sidebar-nav__item');
        const isCompact = this.isCollapsed && !this.isMini && !this.isMobile() && !this.hoverPreviewActive;

        items.forEach(item => {
            const text = item.querySelector('.sidebar-nav__item-text')?.textContent?.trim();
            if (!text) return;

            if (isCompact) {
                if (!item.hasAttribute('data-original-tooltip')) {
                    item.setAttribute('data-original-tooltip', item.getAttribute('data-tooltip') || '');
                }
                item.setAttribute('data-tooltip', text);
                item.setAttribute('data-tooltip-placement', 'right');
            } else {
                const originalTooltip = item.getAttribute('data-original-tooltip');
                if (originalTooltip) {
                    item.setAttribute('data-tooltip', originalTooltip);
                } else {
                    item.removeAttribute('data-tooltip');
                }
                item.removeAttribute('data-tooltip-placement');
                item.removeAttribute('data-original-tooltip');
            }
        });

        // Update footer/guest tooltips position in collapsed/mini mode
        const sidebarTooltips = this.sidebar.querySelectorAll('[data-sidebar-tooltip]');
        sidebarTooltips.forEach(el => {
            const isGuestOrToggle = el.id === 'sidebar-toggle' || el.classList.contains('sidebar-nav__guest');

            if (isCompact) {
                // In compact mode: restore tooltip from original or aria-label
                if (isGuestOrToggle && !el.getAttribute('data-tooltip')) {
                    el.setAttribute('data-tooltip', el.getAttribute('data-original-sidebar-tooltip') || el.getAttribute('aria-label') || '');
                }
                el.setAttribute('data-tooltip-placement', 'right');
            } else {
                // In expanded mode: hide tooltip on elements that have visible text
                if (isGuestOrToggle) {
                    if (!el.hasAttribute('data-original-sidebar-tooltip')) {
                        el.setAttribute('data-original-sidebar-tooltip', el.getAttribute('data-tooltip') || '');
                    }
                    el.removeAttribute('data-tooltip');
                } else {
                    el.setAttribute('data-tooltip-placement', 'top');
                }
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
        this.hideHoverPreview();
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
