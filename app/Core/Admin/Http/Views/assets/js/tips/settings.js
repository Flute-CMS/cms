$(function () {
    if ($('.start-tour').length === 0) {
        let tourButtonSettings = $('<button/>', {
            html: `<i class="ph ph-rocket-launch"></i> ${translate(
                'admin.start_tour',
            )}`,
            class: 'start-tour',
            click: function () {
                const driverObjSettings = driver({
                    nextBtnText: '<i class="ph ph-arrow-right"></i>',
                    prevBtnText: '<i class="ph ph-arrow-left"></i>',
                    doneBtnText: '<i class="ph ph-x"></i>',
                    onDestroyStarted: async () => {
                        $('body').removeClass('driver-active');
                        console.log('test');

                        if (
                            !driverObjSettings.hasNextStep() ||
                            (await asyncConfirm(
                                translate(
                                    'tutorial.are_you_sure',
                                    {},
                                    null,
                                    false,
                                ),
                            ))
                        ) {
                            driverObjSettings.destroy();
                            completeTip('admin_settings');
                            $('.start-tour').hide();
                        } else {
                            $('body').addClass('driver-active');
                        }
                    },
                    steps: [
                        {
                            element: '.admin-header',
                            popover: {
                                title: translate(
                                    'popover.admin_settings.title.admin_header',
                                ),
                                description: translate(
                                    'popover.admin_settings.description.system_settings_intro',
                                ),
                            },
                        },
                        {
                            element: '[data-id="app"]',
                            popover: {
                                title: translate(
                                    'popover.admin_settings.title.system',
                                ),
                                description: translate(
                                    'popover.admin_settings.description.system_settings_details',
                                ),
                            },
                        },
                        {
                            element: '[data-id="additional"]',
                            popover: {
                                title: translate(
                                    'popover.admin_settings.title.additional',
                                ),
                                description: translate(
                                    'popover.admin_settings.description.additional_settings',
                                ),
                            },
                        },
                        {
                            element: '[data-id="auth"]',
                            popover: {
                                title: translate(
                                    'popover.admin_settings.title.authorization',
                                ),
                                description: translate(
                                    'popover.admin_settings.description.authorization_settings',
                                ),
                            },
                        },
                        {
                            element: '[data-id="database"]',
                            popover: {
                                title: translate(
                                    'popover.admin_settings.title.databases',
                                ),
                                description: translate(
                                    'popover.admin_settings.description.databases_overview',
                                ),
                                onNextClick: () => {
                                    $('[data-id="database"]').click();
                                    driverObjSettings.moveNext();
                                },
                            },
                        },
                        {
                            element: '#database > h1',
                            popover: {
                                title: translate(
                                    'popover.admin_settings.title.what_is_this',
                                ),
                                description: translate(
                                    'popover.admin_settings.description.database_principles',
                                ),
                            },
                        },
                        {
                            element: '#tip_def',
                            popover: {
                                title: translate(
                                    'popover.admin_settings.title.default_db',
                                ),
                                description: translate(
                                    'popover.admin_settings.description.default_db_usage',
                                ),
                            },
                        },
                        {
                            element: '#tip_deb',
                            popover: {
                                title: translate(
                                    'popover.admin_settings.title.debug',
                                ),
                                description: translate(
                                    'popover.admin_settings.description.debug_mode_info',
                                ),
                            },
                        },
                        {
                            popover: {
                                title: translate(
                                    'popover.admin_settings.title.connections_dbs',
                                ),
                                description: translate(
                                    'popover.admin_settings.description.multiple_connections_dbs',
                                ),
                            },
                        },
                        {
                            popover: {
                                title: translate(
                                    'popover.admin_settings.title.connections',
                                ),
                                description: translate(
                                    'popover.admin_settings.description.connections_info',
                                ),
                            },
                        },
                        {
                            popover: {
                                title: translate(
                                    'popover.admin_settings.title.in_short',
                                ),
                                description: translate(
                                    'popover.admin_settings.description.connections_dbs_summary',
                                ),
                            },
                        },
                        {
                            element: '#tip_con',
                            popover: {
                                title: translate(
                                    'popover.admin_settings.title.connections',
                                ),
                                description: translate(
                                    'popover.admin_settings.description.managing_connections',
                                ),
                            },
                        },
                        {
                            element: '#tip_dbs',
                            popover: {
                                title: translate(
                                    'popover.admin_settings.title.dbs',
                                ),
                                description: translate(
                                    'popover.admin_settings.description.setting_up_dbs',
                                ),
                            },
                        },
                        {
                            element: '[data-id="lang"]',
                            popover: {
                                title: translate(
                                    'popover.admin_settings.title.language',
                                ),
                                description: translate(
                                    'popover.admin_settings.description.language_settings',
                                ),
                            },
                        },
                        {
                            element: '[data-id="mail"]',
                            popover: {
                                title: translate(
                                    'popover.admin_settings.title.mail_server',
                                ),
                                description: translate(
                                    'popover.admin_settings.description.mail_server_settings',
                                ),
                            },
                        },
                        {
                            element: '[data-id="profile"]',
                            popover: {
                                title: translate(
                                    'popover.admin_settings.title.profile',
                                ),
                                description: translate(
                                    'popover.admin_settings.description.profile_settings',
                                ),
                            },
                        },
                        {
                            element: '[data-id="lk"]',
                            popover: {
                                title: translate(
                                    'popover.admin_settings.title.replenishment',
                                ),
                                description: translate(
                                    'popover.admin_settings.description.balance_replenishment_settings',
                                ),
                            },
                        },
                        {
                            element: '[data-id="cache"]',
                            popover: {
                                title: translate(
                                    'popover.admin_settings.title.cache',
                                ),
                                description: translate(
                                    'popover.admin_settings.description.cache_settings',
                                ),
                            },
                        },
                        {
                            popover: {
                                title: translate(
                                    'popover.admin_settings.title.summing_up',
                                ),
                                description: translate(
                                    'popover.admin_settings.description.tour_ending',
                                ),
                            },
                        },
                    ],
                });
                driverObjSettings.drive();
                $(this).hide();
            },
        });

        $('.admin-container > section').prepend(tourButtonSettings);
    }
});
