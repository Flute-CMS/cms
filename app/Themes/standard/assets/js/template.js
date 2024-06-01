function showErrors(errors, focus = true) {
    errors.forEach((error) => {
        if (error.message) {
            const element = $(error.element);
            if (element.prop('type') !== 'submit') {
                // Проверка типа элемента
                element.attr('aria-invalid', 'true'); // Добавление aria-invalid="true"
            }
            element
                .closest('.input-form')
                .addClass('has-error')
                .find('.error')
                .remove();
            $('<span class="error">').text(error.message).insertAfter(element);
            if (focus && element.focu) {
                element.focus();
                focus = false;
            }
        }
    });
}

function removeErrors(elem) {
    const element = $(elem);
    if (element.prop('type') !== 'submit') {
        // Проверка типа элемента
        element.removeAttr('aria-invalid'); // Удаление атрибута aria-invalid
    }
    if (element.is('form')) {
        $('.has-error', elem).removeClass('has-error');
        $('.error', elem).remove();
    } else {
        element
            .closest('.input-form')
            .removeClass('has-error')
            .find('.error')
            .remove();
    }
}

Nette.showFormErrors = function (form, errors) {
    removeErrors(form);
    showErrors(errors, true);
};

$(function () {
    $(':input[data-nette-rules]').keypress((event) => {
        if ($(event.target).prop('type') !== 'submit') {
            // Проверка типа элемента
            removeErrors(event.target);
        }
    });
    $(':input[data-nette-rules]').blur((event) => {
        Nette.formErrors = [];
        Nette.validateControl(event.target);
        if (Nette.formErrors.length > 0) {
            showErrors(Nette.formErrors);
        } else {
            if ($(event.target).prop('type') !== 'submit') {
                // Проверка типа элемента
                $(event.target).attr('aria-invalid', 'false'); // Добавление aria-invalid="false", если ошибок нет
            }
        }
    });

    $('#editMode').change((event) => {
        if ($(event.target).is(':checked')) {
            window.location.href = appendGet(
                window.location.href,
                'editMode',
                1,
            );
        } else {
            window.location.href = appendGet(
                window.location.href,
                'editMode',
                0,
            );
        }
    });

    $(document).on('click', '.select-button', function () {
        const customSelect = $(this).closest('.custom-select');

        $('.custom-select').not(customSelect).attr('aria-expanded', 'false').removeClass('active');
        
        customSelect.toggleClass('active');
        $(this).attr(
            'aria-expanded',
            $(this).attr('aria-expanded') === 'true' ? 'false' : 'true',
        );
    });

    // Обработчик клика на опции списка
    $(document).on('click keyup', '.select-dropdown li', function (e) {
        // Click Events
        if (e.type === 'click' && e.clientX !== 0 && e.clientY !== 0) {
            const selectedText = $(this).find('label').text();
            $(this)
                .closest('.custom-select')
                .find('.selected-value')
                .text(selectedText);
            $(this).closest('.custom-select').removeClass('active');
        }
        // Key Events
        if (e.type === 'keyup' && e.key === 'Enter') {
            const selectedText = $(this).find('label').text();
            $(this)
                .closest('.custom-select')
                .find('.selected-value')
                .text(selectedText);
            $(this).closest('.custom-select').removeClass('active');
        }
    });
});
