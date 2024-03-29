$(document).ready(function () {
    let selectedRoles = user_roles;

    function renderChips() {
        $('.chips').empty();
        selectedRoles.forEach(function (role) {
            let backgroundColor = role.color || '';
            $('.chips').append(
                `<div class="chip" data-id="${role.id}" style="background-color: ${backgroundColor}">${role.name}<span class="close">&times;</span></div>`,
            );
        });
    }

    function renderSuggestions() {
        $('.suggestions').remove();
        let availableRoles = roles.filter(
            (role) => !selectedRoles.map((r) => r.id).includes(role.id),
        );

        if (availableRoles.length) {
            let suggestionBox = $('<div class="suggestions"></div>');
            availableRoles.forEach(function (role) {
                suggestionBox.append(
                    `<div class="suggestion" data-id="${role.id}">${role.name}</div>`,
                );
            });
            $('.dialog').append(suggestionBox);
        }
    }

    $('.chip-input').on('click', function () {
        renderSuggestions();
        $('.dialog').toggleClass('hidden');
    });

    $(document).on('click', '.suggestion', function () {
        let roleId = $(this).attr('data-id');
        let role = roles.find((r) => r.id.toString() === roleId);
        selectedRoles.push(role);
        renderChips();
        renderSuggestions();
        $('.dialog').toggleClass('hidden');
    });

    $(document).on('click', '.close', function () {
        let roleId = $(this).parent().attr('data-id');
        selectedRoles = selectedRoles.filter((r) => r.id.toString() !== roleId);
        renderChips();
        renderSuggestions();
        $('.dialog').toggleClass('hidden');
    });

    renderChips();
    renderSuggestions();

    $(document).on('submit', '#edit', (ev) => {
        let $form = $(ev.currentTarget);

        ev.preventDefault();

        if (ev.target.checkValidity()) {
            sendRequest(
                { ...serializeForm($form), selectedRoles },
                `admin/api/users/${$form.data('userid')}`,
                'PUT',
            );
        }
    });
});
