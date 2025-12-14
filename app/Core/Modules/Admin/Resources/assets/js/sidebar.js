$(document).ready(function () {
    const RECENT_PAGES_KEY = 'admin_recent_pages';
    const COLLAPSED_SECTIONS_KEY = 'admin_collapsed_sections';
    const MAX_RECENT_PAGES = 5;

    function getRecentPages() {
        try {
            return JSON.parse(localStorage.getItem(RECENT_PAGES_KEY)) || [];
        } catch {
            return [];
        }
    }

    function saveRecentPages(pages) {
        localStorage.setItem(RECENT_PAGES_KEY, JSON.stringify(pages));
    }

    function getCollapsedSections() {
        try {
            return JSON.parse(localStorage.getItem(COLLAPSED_SECTIONS_KEY)) || [];
        } catch {
            return [];
        }
    }

    function saveCollapsedSections(sections) {
        localStorage.setItem(COLLAPSED_SECTIONS_KEY, JSON.stringify(sections));
    }

    function addRecentPage(title, url, svgHtml, parentTitle) {
        if (!title || !url || url === '/admin' || url === '/admin/') return;

        let pages = getRecentPages();
        pages = pages.filter(p => p.url !== url);

        pages.unshift({
            title,
            url,
            svg: svgHtml || '',
            parent: parentTitle || null,
            timestamp: Date.now()
        });
        pages = pages.slice(0, MAX_RECENT_PAGES);
        saveRecentPages(pages);
        renderRecentPages();
    }

    function renderRecentPages() {
        const $list = $('#recent-pages-list');
        const $container = $('#recent-pages');
        const pages = getRecentPages();

        if (pages.length === 0) {
            $container.hide();
            return;
        }

        $container.show();
        $list.empty();

        pages.forEach(page => {
            const parentHtml = page.parent ? `<span class="recent-path">${page.parent} →</span>` : '';
            const svgHtml = page.svg || '<svg viewBox="0 0 256 256"><path d="M213.66,82.34l-56-56A8,8,0,0,0,152,24H56A16,16,0,0,0,40,40V216a16,16,0,0,0,16,16H200a16,16,0,0,0,16-16V88A8,8,0,0,0,213.66,82.34ZM160,51.31,188.69,80H160ZM200,216H56V40h88V88a8,8,0,0,0,8,8h48V216Z"></path></svg>';

            const $item = $(`
                <li class="sidebar__recent-item">
                    <a href="${page.url}">
                        <span class="recent-icon">${svgHtml}</span>
                        <span class="recent-title">
                            ${parentHtml}
                            <span class="recent-name">${page.title}</span>
                        </span>
                    </a>
                </li>
            `);
            $list.append($item);
        });

        htmx.process($list[0]);
    }

    function trackCurrentPage() {
        const $activeItem = $('.sidebar__menu .menu-item.active');
        if ($activeItem.length) {
            const title = $activeItem.find('.menu-title').text().trim();
            const url = $activeItem.attr('href');

            const $svg = $activeItem.find('.menu-icon svg').first();
            let svgHtml = '';
            if ($svg.length) {
                svgHtml = $svg[0].outerHTML;
            }

            let parentTitle = null;
            const $parentSubmenu = $activeItem.closest('.menu-sub');
            if ($parentSubmenu.length) {
                const $parentItem = $parentSubmenu.prev('.menu-item');
                if ($parentItem.length) {
                    parentTitle = $parentItem.find('.menu-title').text().trim();
                }
            }

            addRecentPage(title, url, svgHtml, parentTitle);
        }
    }

    function initCollapsibleSections() {
        const collapsedSections = getCollapsedSections();

        $('.sidebar__section, .sidebar__section--recent').each(function () {
            const $section = $(this);
            const $header = $section.find('.sidebar__section-header');
            const sectionId = $header.data('section');
            const $content = $section.find('.sidebar__section-content');

            if (collapsedSections.includes(sectionId) || collapsedSections.includes(String(sectionId))) {
                $section.addClass('collapsed');
                $content.css('display', 'none');
            }
        });

        $(document).on('click', '.sidebar__section-header', function (e) {
            e.preventDefault();
            e.stopPropagation();

            const $header = $(this);
            const $section = $header.closest('.sidebar__section, .sidebar__section--recent');
            const $content = $section.find('.sidebar__section-content');
            const sectionId = String($header.data('section'));
            const isCollapsed = $section.hasClass('collapsed');

            if (isCollapsed) {
                $section.removeClass('collapsed');
                $content.css('display', 'block');
            } else {
                $section.addClass('collapsed');
                $content.css('display', 'none');
            }

            let sections = getCollapsedSections().map(String);
            if (isCollapsed) {
                sections = sections.filter(s => s !== sectionId);
            } else {
                if (!sections.includes(sectionId)) {
                    sections.push(sectionId);
                }
            }
            saveCollapsedSections(sections);
        });
    }

    function closeSubmenuAnimated($parentItem, callback) {
        const $submenu = $parentItem.find('> .menu-sub');
        if (!$submenu.length) return;

        $submenu.css('display', 'none');
        $parentItem.removeClass('open');
        if (callback) callback();
    }

    function openSubmenuAnimated($parentItem, callback) {
        const $submenu = $parentItem.find('> .menu-sub');
        if (!$submenu.length) return;

        $submenu.css('display', 'block');
        $parentItem.addClass('open');
        if (callback) callback();
    }

    function closeAllSubmenus(callback) {
        $('.sidebar__menu-item.sub-menu.open').each(function () {
            const $item = $(this);
            $item.removeClass('open');
            $item.find('> .menu-sub').css('display', 'none');
        });
        if (callback) callback();
    }

    function positionSubmenu($menuItem, $submenu) {
        const isCollapsed = $('.sidebar').hasClass('collapsed') || document.body.classList.contains('sidebar-collapsed');

        const sidebarEl = document.querySelector('.sidebar');
        const sidebarRect = sidebarEl ? sidebarEl.getBoundingClientRect() : null;

        const applyPos = (x, y) => {
            if (sidebarRect) {
                const minX = sidebarRect.right + (isCollapsed ? 5 : 2);
                x = Math.max(x, minX);
            }

            $submenu.css({
                left: `${x}px`,
                top: `${y}px`,
                position: 'fixed',
                // Keep above everything (dropdown has 1000000 in SCSS)
                zIndex: 1000001,
            });

            setTimeout(() => {
                if (!sidebarRect) return;
                const rect = $submenu[0]?.getBoundingClientRect?.();
                if (!rect) return;
                if (rect.left < sidebarRect.right + 5) {
                    $submenu.css({ left: `${sidebarRect.right + 5}px` });
                }
            }, 0);
        };

        try {
            if (typeof FloatingUIDOM === 'undefined' || !FloatingUIDOM.computePosition) {
                throw new Error('FloatingUIDOM not available');
            }

            FloatingUIDOM.computePosition($menuItem[0], $submenu[0], {
                placement: 'right-start',
                middleware: [
                    FloatingUIDOM.offset(1),
                    FloatingUIDOM.flip(),
                    FloatingUIDOM.shift({ padding: 8 }),
                ],
            }).then(({ x, y }) => applyPos(x, y));
        } catch (e) {
            const r = $menuItem[0].getBoundingClientRect();
            const x = sidebarRect ? sidebarRect.right : (r.right);
            const y = Math.min(Math.max(8, r.top), window.innerHeight - 200);
            applyPos(x, y);
        }
    }

    var sidebarState = getCookie('admin-sidebar-collapsed');
    if (sidebarState === 'true') {
        $('body').addClass('sidebar-collapsed');
        $('.sidebar').addClass('collapsed');
        $('.navbar .sidebar__toggle').show();

        $('.sidebar__menu > .sidebar__menu-item > .menu-item').each(function () {
            const $menuItem = $(this);
            const menuTitle = $menuItem.find('.menu-title').text().trim();
            $menuItem.attr('data-tooltip', menuTitle);
            $menuItem.attr('data-tooltip-placement', 'right');
        });
    } else {
        $('body').removeClass('sidebar-collapsed');
        $('.sidebar').removeClass('collapsed');
        $('.navbar .sidebar__toggle').hide();
        $('.sidebar__menu > .sidebar__menu-item > .menu-item').removeAttr('data-tooltip');
    }

    initCollapsibleSections();

    // Delegate so it still works after sidebar HTML refresh.
    $(document).on('click', '.sidebar__menu-item.sub-menu > .menu-item', function (e) {
        e.preventDefault();
        e.stopPropagation();

        const $menuItem = $(this);
        const $parentItem = $menuItem.closest('.sidebar__menu-item.sub-menu');
        const $submenu = $menuItem.next('.menu-sub');
        const isCollapsed = $('.sidebar').hasClass('collapsed');
        const isOpen = $parentItem.hasClass('open');

        if (isOpen) {
            if (isCollapsed) {
                const $clone = $('.cloned-menu-sub');
                if ($clone.length) {
                    $clone.removeClass('open');
                    setTimeout(() => {
                        $clone.remove();
                        $parentItem.removeClass('open');
                    }, 150);
                } else {
                    $parentItem.removeClass('open');
                }
            } else {
                closeSubmenuAnimated($parentItem);
            }
        } else {
            if (isCollapsed) {
                $('.cloned-menu-sub').remove();
                // Keep the original submenu hidden in collapsed mode; we only show the flyout clone.
                $submenu.css('display', 'none');
                const $clone = $submenu.clone().addClass('cloned-menu-sub').appendTo('body');
                htmx.process($clone[0]);

                $clone.css({
                    display: 'block',
                    position: 'fixed',
                    width: '220px',
                    opacity: 0,
                    height: 'auto',
                });

                positionSubmenu($menuItem, $clone);

                requestAnimationFrame(() => {
                    // Make sure it's visible even if CSS transition is disrupted
                    $clone.addClass('open').css({ opacity: 1 });
                    $parentItem.addClass('open');
                });
            } else {
                $parentItem.siblings('.sub-menu.open').each(function () {
                    closeSubmenuAnimated($(this));
                });
                openSubmenuAnimated($parentItem);
            }
        }

        updateIndicator();
    });

    $(document).on('click', function (e) {
        const $sidebar = $('.sidebar');
        if (!$sidebar.is(e.target) && $sidebar.has(e.target).length === 0) {
            if ($('.sidebar').hasClass('collapsed')) {
                closeAllSubmenus();
                $('.cloned-menu-sub').removeClass('open');
                setTimeout(() => $('.cloned-menu-sub').remove(), 150);
            }
        }
    });

    $(document).on('click', '.sidebar__toggle', function () {
        $('.sidebar').toggleClass('collapsed');

        if ($('.sidebar').hasClass('collapsed')) {
            $('body').addClass('sidebar-collapsed');
            $('.navbar .sidebar__toggle').show();
            setCookie('admin-sidebar-collapsed', 'true', 7);

            $('.cloned-menu-sub').remove();
            $('.sidebar__menu-item.sub-menu.open').each(function () {
                $(this).removeClass('open');
                $(this).find('.menu-sub').css('display', 'none');
            });

            $('.sidebar__menu > .sidebar__menu-item > .menu-item').each(function () {
                const $menuItem = $(this);
                const menuTitle = $menuItem.find('.menu-title').text().trim();
                $menuItem.attr('data-tooltip', menuTitle);
                $menuItem.attr('data-tooltip-placement', 'right');
            });
        } else {
            $('body').removeClass('sidebar-collapsed');
            $('.navbar .sidebar__toggle').hide();
            setCookie('admin-sidebar-collapsed', 'false', 7);

            $('.cloned-menu-sub').remove();
            $('.sidebar__menu-item.sub-menu.open').each(function () {
                $(this).removeClass('open');
                $(this).find('.menu-sub').css('display', 'none');
            });

            $('.sidebar__menu > .sidebar__menu-item > .menu-item').removeAttr('data-tooltip');
        }

        updateIndicator();
    });

    const $toggleButton = $('.sidebar__toggle-mobile');
    const $sidebar = $('.sidebar');

    $toggleButton.on('click', function () {
        $sidebar.toggleClass('active');
        toggleOverlay($sidebar.hasClass('active'));
    });

    function toggleOverlay(show) {
        let $overlay = $('.sidebar-overlay');
        if (show) {
            if (!$overlay.length) {
                $overlay = $('<div class="sidebar-overlay"></div>').appendTo('body');
                $overlay.on('click', function () {
                    $sidebar.removeClass('active');
                    toggleOverlay(false);
                });
            }
            requestAnimationFrame(() => $overlay.addClass('active'));
        } else {
            $overlay.removeClass('active');
        }
    }

    $(document).on('htmx:afterSwap', function () {
        $('.sidebar').removeClass('active');
        toggleOverlay(false);
        $('.cloned-menu-sub').remove();
        updateIndicator();
        trackCurrentPage();
    });

    function updateIndicator() {
        const currentUrl = u(window.location.pathname.slice(1));
        const menuItems = document.querySelectorAll('.sidebar__menu .menu-item');

        menuItems.forEach((item) => {
            const href = item.getAttribute('href');
            if (href && (href === currentUrl || href === currentUrl + '/')) {
                item.classList.add('active');
            } else {
                item.classList.remove('active');
            }
        });
    }

    updateIndicator();
    renderRecentPages();
    trackCurrentPage();

    $('.sidebar__container').on('scroll', updateIndicator);

    function refreshSidebar() {
        const sidebar = document.querySelector('.sidebar');
        if (!sidebar) return;

        fetch(u('admin/api/sidebar'), {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html',
            },
        })
            .then(response => response.ok ? response.text() : Promise.reject('Failed'))
            .then(html => {
                if (html && html.trim()) {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newSidebar = doc.querySelector('.sidebar');
                    if (newSidebar) {
                        const wasCollapsed = sidebar.classList.contains('collapsed');
                        sidebar.innerHTML = newSidebar.innerHTML;
                        if (wasCollapsed) {
                            sidebar.classList.add('collapsed');
                        }
                        if (typeof htmx !== 'undefined') {
                            htmx.process(sidebar);
                        }
                        renderRecentPages();
                        initCollapsibleSections();
                        updateIndicator();
                    }
                }
            })
            .catch(() => { });
    }

    window.refreshAdminSidebar = refreshSidebar;

    window.addEventListener('sidebar-refresh', refreshSidebar);
    document.addEventListener('sidebar-refresh', refreshSidebar);
});
