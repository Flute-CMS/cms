$(document).ready(function () {
    var sidebarState = getCookie('admin-sidebar-collapsed');
    if (sidebarState === 'true') {
        $('body').addClass('sidebar-collapsed');
        $('.sidebar').addClass('collapsed');
        toggleMenuTooltips(true);
    } else {
        $('body').removeClass('sidebar-collapsed');
        $('.sidebar').removeClass('collapsed');
        toggleMenuTooltips(false);
    }

    // --- Collapsible sections ---
    function getSectionState() {
        try {
            var raw = localStorage.getItem('sidebar-sections');
            return raw ? JSON.parse(raw) : {};
        } catch (e) {
            return {};
        }
    }

    function saveSectionState(state) {
        try {
            localStorage.setItem('sidebar-sections', JSON.stringify(state));
        } catch (e) { }
    }

    function initSectionCollapse() {
        var state = getSectionState();
        $('.sidebar__section[data-section-id]').each(function () {
            var id = $(this).attr('data-section-id');
            if (state[id] === true) {
                $(this).addClass('collapsed');
            }
        });
    }

    initSectionCollapse();

    $(document).on('click', '.sidebar__section-toggle', function (e) {
        e.preventDefault();
        var $section = $(this).closest('.sidebar__section');
        var id = $section.attr('data-section-id');
        $section.toggleClass('collapsed');

        var state = getSectionState();
        state[id] = $section.hasClass('collapsed');
        saveSectionState(state);
    });

    // Toggle tooltips for menu items based on sidebar state
    function toggleMenuTooltips(show) {
        $('.sidebar .menu-item[data-tooltip-text]').each(function () {
            const $item = $(this);
            if (show) {
                $item.attr('data-tooltip', $item.attr('data-tooltip-text'));
            } else {
                $item.removeAttr('data-tooltip');
            }
        });
        $('.sidebar .menu-item[data-tooltip]').each(function () {
            const $item = $(this);
            if (!$item.attr('data-tooltip-text')) {
                $item.attr('data-tooltip-text', $item.attr('data-tooltip'));
            }
            if (!show) {
                $item.removeAttr('data-tooltip');
            }
        });
    }

    function closeSubmenuAnimated($parentItem, callback) {
        $parentItem.removeClass('open');
        if (callback) setTimeout(callback, 250);
    }

    function openSubmenuAnimated($parentItem, callback) {
        $parentItem.addClass('open');
        if (callback) setTimeout(callback, 250);
    }

    function closeAllSubmenus(callback) {
        $('.sidebar__menu-item.sub-menu.open').removeClass('open');
        if (callback) setTimeout(callback, 250);
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

    // Submenu click handler
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

    // Close submenus when clicking outside
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

    // Toggle sidebar collapse - both old button and new one in profile dropdown
    $(document).on('click', '.sidebar__toggle, .sidebar__toggle-collapse', function (e) {
        e.preventDefault();

        $('.sidebar').toggleClass('collapsed');

        if ($('.sidebar').hasClass('collapsed')) {
            $('body').addClass('sidebar-collapsed');
            setCookie('admin-sidebar-collapsed', 'true', 7);
            toggleMenuTooltips(true);

            $('.cloned-menu-sub').remove();
            $('.sidebar__menu-item.sub-menu.open').removeClass('open');
        } else {
            $('body').removeClass('sidebar-collapsed');
            setCookie('admin-sidebar-collapsed', 'false', 7);
            toggleMenuTooltips(false);

            $('.cloned-menu-sub').remove();
            $('.sidebar__menu-item.sub-menu.open').removeClass('open');
        }

        updateIndicator();
    });

    // Mobile toggle
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
                        updateIndicator();
                        toggleMenuTooltips(wasCollapsed);
                        initSectionCollapse();
                    }
                }
            })
            .catch(() => { });
    }

    window.refreshAdminSidebar = refreshSidebar;

    window.addEventListener('sidebar-refresh', refreshSidebar);
    document.addEventListener('sidebar-refresh', refreshSidebar);
});
