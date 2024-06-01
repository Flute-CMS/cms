$(function () {
    // Функция проверки валидности URL или относительного пути
    function isValidPathOrUrl(string) {
        // Проверка на валидность полного URL
        try {
            new URL(string);
            return true;
        } catch (_) {
            // Проверка на соответствие формату относительного пути
            const relativePathPattern =
                /^\/[A-Za-z0-9\-._~:\/?#\[\]@!$&'()*+,;=]*$/;
            return relativePathPattern.test(string);
        }
    }

    $(document).on('input', '#icon', function () {
        let val = $(this).val().trim();
        $('#icon-output').html(`<i class='${val}'></i>`);
    });

    // Обработчик события изменения текста в поле URL
    $(document).on('input', '#url', function () {
        var pathOrUrl = $(this).val();
        if (isValidPathOrUrl(pathOrUrl)) {
            $('#new_tab').closest('.form-group').fadeIn(300);
        } else {
            $('#new_tab').prop('checked', false);
            $('#new_tab').closest('.form-group').fadeOut(300);
        }
    });

    // Обработчики событий для переключения видимости
    $(document).on('change', '#visible_only_for_guests', function () {
        if ($(this).is(':checked')) {
            $('#visible_only_for_logged_in').prop('checked', false);
        }
    });

    $(document).on('change', '#visible_only_for_logged_in', function () {
        if ($(this).is(':checked')) {
            $('#visible_only_for_guests').prop('checked', false);
        }
    });

    // Обновление состояния чекбокса "visible_only_for_guests"
    function updateVisibleOnlyForGuestsCheckbox() {
        // Проверяем, выбран ли хотя бы один чекбокс роли
        var isAnyRoleChecked = $(
            '#navEdit .checkboxes .form-check-input, #navAdd .checkboxes .form-check-input',
        ).is(':checked');
        $('#visible_only_for_guests').prop('disabled', isAnyRoleChecked);
        if (isAnyRoleChecked) {
            $('#visible_only_for_guests').prop('checked', false);
        }
    }

    // Обработчик события изменения для чекбоксов ролей
    $(document).on(
        'change',
        '#navEdit .checkboxes .form-check-input, #navAdd .checkboxes .form-check-input',
        function () {
            updateVisibleOnlyForGuestsCheckbox();
        },
    );

    $(document).on('submit', '#navAdd, #navEdit', (ev) => {
        let $form = $(ev.currentTarget);

        ev.preventDefault();

        let path = $form.attr('id') === 'navAdd' ? 'add' : 'edit',
            form = serializeForm($form);

        let url = `admin/api/navigation/${path}`,
            method = 'POST';

        if (path === 'edit') {
            url = `admin/api/navigation/${form.id}`;
            method = 'PUT';
        }

        if (ev.target.checkValidity()) {
            sendRequest(form, url, method);
        }
    });
});
