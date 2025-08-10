$(document).ready(function () {
    function closeAllSubmenus() {
        $('.sidebar__menu-item.sub-menu.open').each(function () {
            var $openedParent = $(this);
            var $openedSubmenu = $openedParent.find('> .menu-sub');

            $openedSubmenu.stop(true, true).animate(
                {
                    height: 0,
                    opacity: 0,
                },
                200,
                'swing',
                function () {
                    $openedSubmenu.removeClass('open').hide().css({
                        height: '',
                        position: '',
                        left: '',
                        top: '',
                        width: '',
                        zIndex: '',
                        opacity: '',
                    });
                    $openedParent.removeClass('open');
                },
            );
        });
    }

    function closeSubmenus($parentItem) {
        $parentItem.find('.sidebar__menu-item.sub-menu.open').each(function () {
            var $openedParent = $(this);
            var $openedSubmenu = $openedParent.find('> .menu-sub');

            $openedSubmenu.stop(true, true).animate(
                {
                    height: 0,
                    opacity: 0,
                },
                200,
                'swing',
                function () {
                    $openedSubmenu.removeClass('open').hide().css({
                        height: '',
                        position: '',
                        left: '',
                        top: '',
                        width: '',
                        zIndex: '',
                        opacity: '',
                    });
                    $openedParent.removeClass('open');
                },
            );
        });
    }

    function openSubmenu($parentItem, $submenu, callback) {
        $submenu
            .show()
            .css({
                height: 0,
                opacity: 0,
            })
            .animate(
                {
                    height: $submenu.prop('scrollHeight'),
                    opacity: 1,
                },
                200,
                'swing',
                function () {
                    $submenu.addClass('open').css('height', 'auto');
                    $parentItem.addClass('open');
                    if (callback) callback();
                },
            );
    }

    function positionSubmenu($menuItem, $submenu) {
        FloatingUIDOM.computePosition($menuItem[0], $submenu[0], {
            placement: 'right-start',
            middleware: [
                FloatingUIDOM.offset(1),
                FloatingUIDOM.flip(),
                FloatingUIDOM.shift({ padding: 0 }),
            ],
        }).then(({ x, y }) => {
            $submenu.css({
                left: `${x}px`,
                top: `${y}px`,
                opacity: 1,
                position: 'absolute',
                width: '250px',
                zIndex: 1000,
            });
        });
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

    $('.sidebar__menu-item.sub-menu > .menu-item').on('click', function (e) {
        e.preventDefault();
        e.stopPropagation();

        var $menuItem = $(this);
        var $parentItem = $menuItem.closest('.sidebar__menu-item.sub-menu');
        var $submenu = $menuItem.next('.menu-sub');
        var isCollapsed = $('.sidebar').hasClass('collapsed');

        if ($parentItem.hasClass('open')) {
            if (isCollapsed) {
                var $clone = $('.cloned-menu-sub');
                if ($clone.length) {
                    $clone.removeClass('open');
                    setTimeout(function () {
                        $clone.remove();
                        $submenu.removeClass('open').hide().css({
                            opacity: '',
                            position: '',
                            left: '',
                            top: '',
                            width: '',
                            zIndex: '',
                        });
                        $parentItem.removeClass('open');
                    }, 220);
                } else {
                    $submenu.hide();
                    $parentItem.removeClass('open');
                }
            } else {
                $submenu.stop(true, true).animate(
                    {
                        height: 0,
                        opacity: 0,
                    },
                    200,
                    'swing',
                    function () {
                        $submenu.removeClass('open').hide().css({
                            height: '',
                            position: '',
                            left: '',
                            top: '',
                            width: '',
                            zIndex: '',
                            opacity: '',
                        });
                        $parentItem.removeClass('open');
                        closeSubmenus($parentItem);
                    },
                );
            }
        } else {
            if (isCollapsed) {
                $('.cloned-menu-sub').remove();
                var $clone = $submenu.clone().addClass('cloned-menu-sub').appendTo('body');
                htmx.process($clone[0]);
                $submenu.hide();
                $clone.show().css({
                    position: 'absolute',
                    width: '250px',
                    opacity: 0,
                    height: 'auto',
                });
                positionSubmenu($menuItem, $clone);
                // Use CSS transitions for richer animation
                requestAnimationFrame(() => {
                    $clone.addClass('open');
                    $parentItem.addClass('open');
                });
            } else {
                $parentItem.siblings('.sub-menu.open').each(function () {
                    var $openedParent = $(this);
                    var $openedSubmenu = $openedParent.find('> .menu-sub');

                    $openedSubmenu.stop(true, true).animate(
                        {
                            height: 0,
                            opacity: 0,
                        },
                        200,
                        'swing',
                        function () {
                            $openedSubmenu.removeClass('open').hide().css({
                                height: '',
                                position: '',
                                left: '',
                                top: '',
                                width: '',
                                zIndex: '',
                                opacity: '',
                            });
                            $openedParent.removeClass('open');
                            closeSubmenus($openedParent);
                        },
                    );
                });

                openSubmenu($parentItem, $submenu);
            }
        }

        setTimeout(() => {
            updateIndicator();
        }, 300);
    });

    $(document).on('click', function (e) {
        var $sidebar = $('.sidebar');
        if (!$sidebar.is(e.target) && $sidebar.has(e.target).length === 0) {
            closeAllSubmenus();
            $('.cloned-menu-sub').remove();
            updateIndicator();
        }
    });

    $(document).on('click', '.sidebar__toggle', function () {
        $('.sidebar').toggleClass('collapsed');

        document.querySelector('.sidebar__indicator').style.opacity = '0';

        if ($('.sidebar').hasClass('collapsed')) {
            $('body').addClass('sidebar-collapsed');
            $('.navbar .sidebar__toggle').show();
            setCookie('admin-sidebar-collapsed', 'true', 7);
            $('.cloned-menu-sub').remove();
            closeAllSubmenus();

            // Add tooltips when sidebar is collapsed
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
            closeAllSubmenus();

            // Remove tooltips when sidebar is expanded
            $('.sidebar__menu > .sidebar__menu-item > .menu-item').removeAttr('data-tooltip');
        }

        setTimeout(() => {
            updateIndicator();
        }, 300);
    });

    const $toggleButton = $('.sidebar__toggle-mobile');
    const $sidebar = $('.sidebar');

    $toggleButton.on('click', function () {
        $sidebar.toggleClass('active');
    });

    $(document).on('htmx:afterSwap', function () {
        $('.sidebar').removeClass('active');
        $('.cloned-menu-sub').remove();
        updateIndicator();
    });

    function hideIndicator() {
        const indicator = document.querySelector('.sidebar__indicator');
        if (indicator) {
            indicator.style.opacity = '0';
        }
    }

    function showIndicator() {
        updateIndicator();
    }

    // $(document).on('scroll', function () {
    //     hideIndicator();

    //     clearTimeout(scrollTimeout);

    //     scrollTimeout = setTimeout(function () {
    //         showIndicator();
    //     }, 200);
    // });

    function updateIndicator() {
        const sidebar = document.querySelector('.sidebar');
        const isCollapsed = sidebar.classList.contains('collapsed');

        const currentUrl = u(window.location.pathname.slice(1));
        const menuItems = document.querySelectorAll(
            '.sidebar__menu .menu-item',
        );

        let activeItem = null;

        menuItems.forEach((item) => {
            const href = item.getAttribute('href');
            if (href && (href === currentUrl || href === currentUrl + '/')) {
                activeItem = item;
                item.classList.add('active');
            } else {
                item.classList.remove('active');
            }
        });

        if (activeItem) {
            const $activeItem = $(activeItem);

            if ($activeItem.parents('.menu-sub').length > 0) {
                let $parentSubMenuItem = $activeItem.closest(
                    '.sidebar__menu-item.sub-menu',
                );
                while (
                    $parentSubMenuItem
                        .parent()
                        .closest('.sidebar__menu-item.sub-menu').length > 0
                ) {
                    $parentSubMenuItem = $parentSubMenuItem
                        .parent()
                        .closest('.sidebar__menu-item.sub-menu');
                }

                const $topLevelItem = $parentSubMenuItem.children('.menu-item');
                if ($topLevelItem.length > 0) {
                    activeItem = $topLevelItem[0];
                }
            }
        }

        const indicator = document.querySelector('.sidebar__indicator');
        if (indicator) {
            if (activeItem && !isCollapsed) {
                const sidebarRect = sidebar.getBoundingClientRect();
                const itemRect = activeItem.getBoundingClientRect();

                const topPosition =
                    itemRect.top - sidebarRect.top + sidebar.scrollTop;

                indicator.style.top = `${topPosition}px`;
                indicator.style.height = `${itemRect.height}px`;
                indicator.style.opacity = '1';
            } else {
                indicator.style.opacity = '0';
            }
        }
    }

    updateIndicator();
    $('.sidebar__container').on('scroll', updateIndicator);
});
