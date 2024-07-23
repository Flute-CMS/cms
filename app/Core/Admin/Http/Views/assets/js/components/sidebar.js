$(function () {
    if (document.querySelector('.item.opened'))
        document
            .querySelector('.item.opened')
            .scrollIntoView({ behavior: 'smooth' });

    if (localStorage.getItem('sidebar-collapsed') === 'true') {
        $('#sidebar-container').addClass('collapsed');
        $('#sidebar-container > .item').removeClass('opened');
        addTooltips();
    } else {
        $('#sidebar-container').removeClass('collapsed');
        removeTooltips();
    }

    // Toggle sidebar on button click
    $('#sidebar-toggle').click(function () {
        $('#sidebar-container').toggleClass('collapsed');
        if ($('#sidebar-container').hasClass('collapsed')) {
            localStorage.setItem('sidebar-collapsed', 'true');
            addTooltips();
        } else {
            localStorage.setItem('sidebar-collapsed', 'false');
            removeTooltips();
        }

        setTimeout(() => {
            window.chromeTabs.tabContentEl.style.width = null;
            window.chromeTabs.cleanUpPreviouslyDraggedTabs();
            window.chromeTabs.layoutTabs();
            window.chromeTabs.setupDraggabilly();
        }, 300);
    });

    function addTooltips() {
        // $('.name-icon > p').each(function () {
        //     var tooltipText = $(this).text();
        //     $(this).closest('.head-button').attr('data-tooltip-conf', 'right');
        //     $(this).closest('.head-button').attr('data-tooltip', tooltipText);
        // });
    }

    function removeTooltips() {
        // $('.head-button').removeAttr('data-tooltip');
    }

    $('.menu-section > .items > button > .head-button:not([href])').on(
        'click',
        (e) => {
            const parent = $(e.currentTarget).parent();
            $('.menu-section > .items > button')
                .not(parent)
                .removeClass('opened');

            parent.toggleClass('opened');
        },
    );

    const chromeTabs = document.querySelector('.chrome-tabs');

    chromeTabs.addEventListener('activeTabChange', function (event) {
        const { tabEl } = event.detail;

        // Deactivate all currently active sidebar items
        document
            .querySelectorAll(
                '.sidebar-menu .item.active, .sidebar-menu .item.opened, .btn-add-menu .submenu-item',
            )
            .forEach((activeItem) => {
                activeItem.classList.remove('active', 'opened');
            });

        // Get the new active tab URL
        const newActiveHref = tabEl.getAttribute('data-tab-url');

        // Find the corresponding sidebar item
        const newActiveSidebarItem = document.querySelector(
            `.sidebar-menu a[href="${newActiveHref}"]`,
        );
        if (newActiveSidebarItem) {
            newActiveSidebarItem.classList.add('active');
            newActiveSidebarItem.closest('.item').classList.add('active');

            // If it's a submenu item, open the parent menu
            const parentMenu = newActiveSidebarItem.closest('.btn-add-menu');
            if (parentMenu) {
                parentMenu.closest('.item').classList.add('active', 'opened');
                // parentMenu.scrollIntoView({ behavior: 'smooth' });
            } else {
                // newActiveSidebarItem.scrollIntoView({ behavior: 'smooth' });
            }
        }
    });

    var timeout;

    $(document).on('mouseenter', '.sidebar-container.collapsed .item', function() {
        // Clear any existing timeout
        clearTimeout(timeout);

        // Remove 'opened' class and reset position of any previously hovered item
        $('.sidebar-container.collapsed .item.opened').each(function() {
            var $prevItem = $(this);
            var $prevBtnAddMenu = $prevItem.find('.btn-add-menu');

            $prevBtnAddMenu.css({
                top: '',
                left: ''
            });

            $prevItem.removeClass('opened');
        });

        // Check if the sidebar is in collapsed mode
        if ($('.sidebar-container').hasClass('collapsed')) {
            var $this = $(this);
            var $btnAddMenu = $this.find('.btn-add-menu');

            // Get the position of the hovered item relative to the parent container
            var itemPosition = $this.position();

            // Set the position of the .btn-add-menu
            $btnAddMenu.css({
                top: itemPosition.top - 10, // Adjusting the top position
                left: itemPosition.left + $this.outerWidth()
            });

            // Add the 'opened' class to the hovered item
            $this.addClass('opened');
        }
    });

    $(document).on('mouseleave', '.sidebar-container.collapsed .item', function() {
        // Set a timeout to delay the removal of the 'opened' class
        var $this = $(this);
        var $btnAddMenu = $this.find('.btn-add-menu');

        timeout = setTimeout(function() {
            $btnAddMenu.css({
                top: '',
                left: ''
            });

            $this.removeClass('opened');
        }, 300); // Adjust the delay as needed
    });

    // Keep the .btn-add-menu open when hovering over it
    $(document).on('mouseenter', '.sidebar-container.collapsed .btn-add-menu', function() {
        // Clear any existing timeout
        clearTimeout(timeout);
    });

    $(document).on('mouseleave', '.sidebar-container.collapsed .btn-add-menu', function() {
        // Set a timeout to delay the removal of the 'opened' class
        var $this = $(this);
        var $parentItem = $this.closest('.item');

        timeout = setTimeout(function() {
            $this.css({
                top: '',
                left: ''
            });

            $parentItem.removeClass('opened');
        }, 300); // Adjust the delay as needed
    });
});
