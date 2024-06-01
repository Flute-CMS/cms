$(function () {
    function renderChips() {
        var userId = $('#edit').data('userid');

        $('#user_chils').empty();

        if (typeof selectedRoles[userId] === 'undefined') return;

        selectedRoles[userId].forEach(function (role) {
            let backgroundColor = role.color || '';
            $('#user_chils').append(
                `<div class="chip" data-id="${role.id}" style="background-color: ${backgroundColor}">${role.name}<span class="remove-role">&times;</span></div>`,
            );
        });
    }

    function renderSuggestions() {
        var userId = $('#edit').data('userid');

        if (typeof selectedRoles[userId] === undefined) return;

        $('#user_dialog').find('.suggestions').remove();
        let availableRoles = roles.filter(
            (role) => !selectedRoles[userId].map((r) => r.id).includes(role.id),
        );

        if (availableRoles.length) {
            let suggestionBox = $('<div class="suggestions"></div>');
            availableRoles.forEach(function (role) {
                suggestionBox.append(
                    `<div class="suggestion" data-id="${role.id}">${role.name}</div>`,
                );
            });
            $('#user_dialog').append(suggestionBox);
        }
    }

    $(document).on('click', '.chip-input', function () {
        renderSuggestions();
        $('#user_dialog').toggleClass('hidden');
    });

    $(document).on('click', '.suggestion', function () {
        var userId = $('#edit').data('userid');

        let roleId = $(this).attr('data-id');
        let role = roles.find((r) => r.id.toString() === roleId);
        selectedRoles[userId].push(role);
        renderChips();
        renderSuggestions();
        $('#user_dialog').toggleClass('hidden');
    });

    $(document).on('click', '.remove-role', function () {
        var userId = $('#edit').data('userid');

        let roleId = $(this).parent().attr('data-id');
        selectedRoles[userId] = selectedRoles[userId].filter(
            (r) => r.id.toString() !== roleId,
        );
        renderChips();
        renderSuggestions();
        $('#user_dialog').toggleClass('hidden');
    });

    renderChips();
    renderSuggestions();

    document
        .querySelector('.chrome-tabs')
        .addEventListener('contentRender', ({ detail }) => {
            setTimeout(() => {
                renderChips();
                renderSuggestions();
            }, 500);
        });

    $(document).on('submit', '#edit', (ev) => {
        let $form = $(ev.currentTarget);
        var userId = $('#edit').data('userid');

        ev.preventDefault();

        if (ev.target.checkValidity()) {
            sendRequest(
                {
                    ...serializeForm($form),
                    selectedRoles: selectedRoles[userId],
                },
                `admin/api/users/${$form.data('userid')}`,
                'PUT',
            );
        }
    });

    $(document).on('click', '.user-actions button', async function () {
        const action = $(this).data('useraction');
        const userId = $('#edit').data('userid');

        switch (action) {
            case 'unblock':
                if (
                    await asyncConfirm(
                        translate('admin.users.confirm_unblock'),
                        null,
                        translate('admin.users.unblock'),
                        null,
                        'primary',
                    )
                ) {
                    sendRequest(
                        {},
                        `admin/api/users/${userId}/unblock`,
                        'POST',
                    );
                    console.log('unblock');
                }
                break;
            case 'block':
                $('#selected-duration').val('60');
                const customContent = `
                    <div class="reason-container">
                        <label class="confirm-dialog-input-label">${translate(
                            'def.time',
                        )}</label>
                        <div class="radio-group" id="block-duration">
                            <input type="radio" id="60" name="duration" value="60" checked>
                            <label for="60">${translate(
                                'admin.users.times.60',
                            )}</label>
                            <input type="radio" id="600" name="duration" value="600">
                            <label for="600">${translate(
                                'admin.users.times.600',
                            )}</label>
                            <input type="radio" id="3600" name="duration" value="3600">
                            <label for="3600">${translate(
                                'admin.users.times.3600',
                            )}</label>
                            <input type="radio" id="86400" name="duration" value="86400">
                            <label for="86400">${translate(
                                'admin.users.times.86400',
                            )}</label>
                            <input type="radio" id="604800" name="duration" value="604800">
                            <label for="604800">${translate(
                                'admin.users.times.604800',
                            )}</label>
                            <input type="radio" id="0" name="duration" value="0">
                            <label for="0">${translate(
                                'admin.users.times.0',
                            )}</label>
                        </div>
                    </div>
                `;

                if (
                    (confirmValue = await asyncConfirm({
                        customClass: 'block-modal',
                        questionText: translate('admin.users.block_user'),
                        questionDescription: translate(
                            'admin.users.block_desc',
                        ),
                        inputValidator: function (input) {
                            return input.length > 0;
                        },
                        trueButtonText: translate('def.block'),
                        inputPlaceholder: translate('admin.users.enter_reason'),
                        inputLabel: translate('def.reason'),
                        customContent: customContent,
                    }))
                ) {
                    const selectedDuration = $('#selected-duration').val();
                    const reason = confirmValue;

                    sendRequest(
                        {
                            duration: selectedDuration,
                            reason: reason,
                        },
                        `admin/api/users/${userId}/ban`,
                        'POST',
                    );
                }
                break;
            case 'give_money':
                asyncConfirm({
                    customClass: 'give-modal',
                    questionText: translate('admin.users.give_money'),
                    questionDescription: translate(
                        'admin.users.give_money_desc',
                    ),
                    inputLabel: translate('def.amount'),
                    inputPlaceholder: translate('def.enter_amount'),
                    type: 'primary',
                    trueButtonText: translate('def.give'),
                    inputValidator: function (input) {
                        const amount = parseFloat(input);
                        return !isNaN(amount) && amount > 0;
                    },
                }).then(function (result) {
                    if (result) {
                        sendRequest(
                            {
                                amount: result,
                            },
                            `admin/api/users/${userId}/give-money`,
                            'POST',
                        );
                    } else {
                        console.log('Action cancelled');
                    }
                });
                break;
            case 'take_money':
                asyncConfirm({
                    customClass: 'give-modal',
                    questionText: translate('admin.users.take_money'),
                    questionDescription: translate(
                        'admin.users.take_money_desc',
                    ),
                    inputLabel: translate('def.amount'),
                    inputPlaceholder: translate('def.enter_amount'),
                    trueButtonText: translate('def.take'),
                    inputValidator: function (input) {
                        const amount = parseFloat(input);
                        return !isNaN(amount) && amount > 0;
                    },
                }).then(function (result) {
                    if (result) {
                        sendRequest(
                            {
                                amount: result,
                            },
                            `admin/api/users/${userId}/take-money`,
                            'POST',
                        );
                    } else {
                        console.log('Action cancelled');
                    }
                });
                break;
            default:
                console.log('Unknown action:', action);
        }
    });

    // Сохраняем выбранное значение в скрытом элементе
    $(document).on(
        'change',
        '#block-duration input[name="duration"]',
        function () {
            const selectedValue = $(this).val();
            $('#selected-duration').val(selectedValue);
        },
    );
});
