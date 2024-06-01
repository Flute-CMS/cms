$(function () {
    if (document.querySelector('.item.opened'))
        document
            .querySelector('.item.opened')
            .scrollIntoView({ behavior: 'smooth' });

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
});
