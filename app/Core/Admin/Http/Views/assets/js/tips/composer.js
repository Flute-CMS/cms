if (!COMPOSER_PAGE) {
    const driverObjComp = driver({
        nextBtnText: '<i class="ph ph-arrow-right"></i>',
        prevBtnText: '<i class="ph ph-arrow-left"></i>',
        doneBtnText: '<i class="ph ph-x"></i>',
        onDestroyStarted: async () => {
            if (
                !driverObjComp.hasNextStep() ||
                await asyncConfirm(translate('tutorial.are_you_sure'))
            ) {
                driverObjComp.destroy();
                completeTip('admin_composer');
                $('#start-tour-btn').hide();
            }
        },
        steps: [
            {
                element: '.admin-header',
                popover: {
                    title: translate(
                        'popover.composer.title.composer',
                        {},
                        null,
                        false,
                    ),
                    description: translate(
                        'popover.composer.description.composer',
                        {},
                        null,
                        false,
                    ),
                },
            },
            {
                popover: {
                    title: translate(
                        'popover.composer.title.what_is_this',
                        {},
                        null,
                        false,
                    ),
                    description: translate(
                        'popover.composer.description.what_is_this',
                        {},
                        null,
                        false,
                    ),
                },
            },
            {
                popover: {
                    title: translate(
                        'popover.composer.title.and_then',
                        {},
                        null,
                        false,
                    ),
                    description: translate(
                        'popover.composer.description.and_then',
                        {},
                        null,
                        false,
                    ),
                },
            },
            {
                element: 'table',
                popover: {
                    title: translate(
                        'popover.composer.title.package_list',
                        {},
                        null,
                        false,
                    ),
                    description: translate(
                        'popover.composer.description.package_list',
                        {},
                        null,
                        false,
                    ),
                },
            },
            {
                element: 'tbody>tr',
                popover: {
                    title: translate(
                        'popover.composer.title.deletion',
                        {},
                        null,
                        false,
                    ),
                    description: translate(
                        'popover.composer.description.deletion',
                        {},
                        null,
                        false,
                    ),
                },
            },
            {
                element: '#add_package',
                popover: {
                    title: translate(
                        'popover.composer.title.practice',
                        {},
                        null,
                        false,
                    ),
                    description: translate(
                        'popover.composer.description.practice',
                        {},
                        null,
                        false,
                    ),
                },
            },
        ],
    });
    $(function() {
        // Создание кнопки
        let tourButton = $('<button/>', {
            id: 'start-tour-btn',
            html: '<i class="ph ph-flag"></i>',
            click: function () {
                driverObjComp.drive();
                $(this).hide();
            },
        })
            .attr('data-tooltip', translate('def.start_tour'))
            .attr('data-tooltip-conf', 'left');

        // Добавление кнопки в body
        $('body').append(tourButton);
    });
} else {
    // const driverObjComp = driver({
    //     nextBtnText: '<i class="ph ph-arrow-right"></i>',
    //     prevBtnText: '<i class="ph ph-arrow-left"></i>',
    //     doneBtnText: '<i class="ph ph-x"></i>',
    //     onDestroyStarted: () => {
    //         if (
    //             !driverObjComp.hasNextStep() ||
    //             confirm(translate('tutorial.are_you_sure'))
    //         ) {
    //             driverObjComp.destroy();
    //             completeTip('admin_composer');
    //         }
    //     },
    //     steps: [
    //         {
    //             element: 'table',
    //             popover: {
    //                 title: 'Пакеты',
    //                 description: 'В этой таблице расположены все существующие пакеты в Composer.',
    //             },
    //         },
    //         {
    //             element: '#dt-search-0',
    //             popover: {
    //                 title: 'Поиск',
    //                 description: 'Давайте поищем какой-нибудь пакет. К примеру <kbd>omnipay yoomoney</kbd>',
    //             },
    //         },
    //     ],
    // });
    // driverObjComp.drive();
}
