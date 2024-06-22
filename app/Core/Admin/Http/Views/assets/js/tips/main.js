$(function () {
    if ($('.start-tour').length === 0) {
        let tourButtonMain = $('<button/>', {
            html: `<i class="ph ph-rocket-launch"></i> ${translate(
                'admin.start_tour',
            )}`,
            class: 'start-tour',
            click: function () {
                const driverObjMain = driver({
                    nextBtnText: '<i class="ph ph-arrow-right"></i>',
                    prevBtnText: '<i class="ph ph-arrow-left"></i>',
                    doneBtnText: '<i class="ph ph-x"></i>',
                    onDestroyStarted: async () => {
                        $('body').removeClass('driver-active');

                        if (
                            !driverObjMain.hasNextStep() ||
                            (await asyncConfirm(
                                translate('tutorial.are_you_sure'),
                            ))
                        ) {
                            driverObjMain.destroy();
                            completeTip('admin_stats');
                            $('.start-tour').hide();
                        } else {
                            $('body').addClass('driver-active');
                        }
                    },
                    steps: [
                        {
                            element: '.sidebar',
                            popover: {
                                title: translate(
                                    'popover.admin_stats.title.sidebar',
                                    {},
                                    null,
                                    false,
                                ),
                                description: translate(
                                    'popover.admin_stats.description.sidebar',
                                    {},
                                    null,
                                    false,
                                ),
                            },
                        },
                        {
                            element: '.content-header',
                            popover: {
                                title: translate(
                                    'popover.admin_stats.title.navbar',
                                    {},
                                    null,
                                    false,
                                ),
                                description: translate(
                                    'popover.admin_stats.description.navbar',
                                    {},
                                    null,
                                    false,
                                ),
                            },
                        },
                        {
                            element: '.header_search',
                            popover: {
                                title: translate(
                                    'popover.admin_stats.title.search',
                                    {},
                                    null,
                                    false,
                                ),
                                description: translate(
                                    'popover.admin_stats.description.search',
                                    {},
                                    null,
                                    false,
                                ),
                            },
                        },
                        {
                            element: '.header_version',
                            popover: {
                                title: translate(
                                    'popover.admin_stats.title.version',
                                    {},
                                    null,
                                    false,
                                ),
                                description: translate(
                                    'popover.admin_stats.description.version',
                                    {},
                                    null,
                                    false,
                                ),
                            },
                        },
                        {
                            element: '.report-generate',
                            popover: {
                                title: translate(
                                    'popover.admin_stats.title.report_generation',
                                    {},
                                    null,
                                    false,
                                ),
                                description: translate(
                                    'popover.admin_stats.description.report_generation',
                                    {},
                                    null,
                                    false,
                                ),
                            },
                        },
                        {
                            element: '.header-contact',
                            popover: {
                                title: translate(
                                    'popover.admin_stats.title.contacts',
                                    {},
                                    null,
                                    false,
                                ),
                                description: translate(
                                    'popover.admin_stats.description.contacts',
                                    {},
                                    null,
                                    false,
                                ),
                            },
                        },
                        {
                            popover: {
                                title: translate(
                                    'popover.admin_stats.title.final',
                                    {},
                                    null,
                                    false,
                                ),
                                description: translate(
                                    'popover.admin_stats.description.final',
                                    {},
                                    null,
                                    false,
                                ),
                            },
                        },
                    ],
                });

                fetchContentAndAddTab('/admin/settings', 'Loading...');

                driverObjMain.drive();
                $(this).hide();
            },
        });

        $('.admin-container > section').prepend(tourButtonMain);
    }
});
