const driverObj = driver({
    nextBtnText: '<i class="ph ph-arrow-right"></i>',
    prevBtnText: '<i class="ph ph-arrow-left"></i>',
    doneBtnText: '<i class="ph ph-x"></i>',
    onDestroyStarted: () => {
        if (
            !driverObj.hasNextStep() ||
            confirm(translate('tutorial.are_you_sure'))
        ) {
            driverObj.destroy();
            completeTip('admin_stats');
        }
    },
    steps: [
        {
            element: '.sidebar',
            popover: {
                title: translate('popover.admin_stats.title.sidebar', {}, null, false),
                description: translate('popover.admin_stats.description.sidebar', {}, null, false),
            },
        },
        {
            element: '.main-menu',
            popover: {
                title: translate('popover.admin_stats.title.main_menu', {}, null, false),
                description: translate('popover.admin_stats.description.main_menu', {}, null, false),
            },
        },
        {
            element: '.additional-menu',
            popover: {
                title: translate('popover.admin_stats.title.additional_menu', {}, null, false),
                description: translate('popover.admin_stats.description.additional_menu', {}, null, false),
            },
        },
        {
            element: '.recent-menu',
            popover: {
                title: translate('popover.admin_stats.title.recent_menu', {}, null, false),
                description: translate('popover.admin_stats.description.recent_menu', {}, null, false),
            },
        },
        {
            popover: {
                title: translate('popover.admin_stats.title.sidebar_complete', {}, null, false),
                description: translate('popover.admin_stats.description.sidebar_complete', {}, null, false),
            },
        },
        {
            element: '.content-header',
            popover: {
                title: translate('popover.admin_stats.title.navbar', {}, null, false),
                description: translate('popover.admin_stats.description.navbar', {}, null, false),
            },
        },
        {
            element: '.header_search',
            popover: {
                title: translate('popover.admin_stats.title.search', {}, null, false),
                description: translate('popover.admin_stats.description.search', {}, null, false),
            },
        },
        {
            element: '.header_version',
            popover: {
                title: translate('popover.admin_stats.title.version', {}, null, false),
                description: translate('popover.admin_stats.description.version', {}, null, false),
            },
        },
        {
            element: '.header_log',
            popover: {
                title: translate('popover.admin_stats.title.report_generation', {}, null, false),
                description: translate('popover.admin_stats.description.report_generation', {}, null, false),
            },
        },
        {
            popover: {
                title: translate('popover.admin_stats.title.final', {}, null, false),
                description: translate('popover.admin_stats.description.final', {}, null, false),
            },
        }
    ],
});

driverObj.drive();
