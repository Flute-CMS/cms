$(function () {
    if (!IS_EDITING) {
        // Создание кнопки
        let tourButton = $('<button/>', {
            id: 'start-tour-btn',
            html: '<i class="ph ph-flag"></i>',
            click: function () {
                const driverObj = driver({
                    showButtons: [{}],
                    onDestroyStarted: () => {
                        if (confirm(translate('tutorial.are_you_sure'))) {
                            driverObj.destroy();
                        }
                    },
                    onDestroyed: () => {
                        completeTip('editor');
                        $('#start-tour-btn').hide();
                    },
                    steps: [
                        {
                            element: '#editMode',
                            popover: {
                                title: translate(
                                    'popover.home.title.editor_mode_title',
                                ),
                                description: translate(
                                    'popover.home.description.editor_mode',
                                ),
                            },
                        },
                    ],
                });

                driverObj.drive();
                $(this).hide();
            },
        })
            .attr('data-translate', translate('def.start_tour'))
            .attr('data-translate-attribute', 'data-tooltip')
            .attr('data-tooltip-conf', 'left');

        // Добавление кнопки в body
        $('body').append(tourButton);
    } else {
        const driverObj = driver({
            nextBtnText: '—›',
            prevBtnText: '‹—',
            doneBtnText: '✕',
            onDestroyStarted: () => {
                if (
                    !driverObj.hasNextStep() ||
                    confirm(translate('tutorial.are_you_sure', {}, null, false))
                ) {
                    driverObj.destroy();
                    completeTip('editor');
                }
            },
            steps: [
                {
                    element: '.editor_title',
                    popover: {
                        title: translate('popover.home.title.editor_title'),
                        description: translate(
                            'popover.home.description.editor_title',
                        ),
                    },
                },
                {
                    element: '#editor',
                    popover: {
                        title: translate('popover.home.title.editor_area'),
                        description: translate(
                            'popover.home.description.editor_area',
                        ),
                        onNextClick: () => {
                            let toolBar = document.querySelector(
                                '#editor > .codex-editor > .ce-toolbar',
                            );

                            toolBar.classList.add('ce-toolbar--opened');
                            toolBar.style.top = '0px';
                            toolBar
                                .querySelector('.ce-toolbar__actions')
                                .classList.add('ce-toolbar__actions--opened');
                            toolBar
                                .querySelector('.ce-toolbar__actions')
                                .setAttribute('id', 'editor-toolbar');

                            driverObj.moveNext();
                        },
                    },
                },
                {
                    element: '#editor-toolbar',
                    popover: {
                        title: translate('popover.home.title.editor_toolbar'),
                        description: translate(
                            'popover.home.description.editor_toolbar',
                        ),
                    },
                },
                {
                    element: '#saveButton',
                    popover: {
                        title: translate('popover.home.title.save_button'),
                        description: translate(
                            'popover.home.description.save_button',
                        ),
                    },
                },
                {
                    popover: {
                        title: translate(
                            'popover.home.title.editor_course_completed',
                        ),
                        description: translate(
                            'popover.home.description.editor_course_completed',
                        ),
                    },
                },
            ],
        });

        driverObj.drive();
    }
});
