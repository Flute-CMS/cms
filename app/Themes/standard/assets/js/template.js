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
});

const customSelect = document.querySelector('.custom-select');
const selectBtn = document.querySelector('.select-button');

const selectedValue = document.querySelector('.selected-value');
const optionsList = document.querySelectorAll('.select-dropdown li');

if (selectBtn) {
    selectBtn.addEventListener('click', () => {
        customSelect.classList.toggle('active');
        selectBtn.setAttribute(
            'aria-expanded',
            selectBtn.getAttribute('aria-expanded') === 'true'
                ? 'false'
                : 'true',
        );
    });

    optionsList.forEach((option) => {
        function handler(e) {
            // Click Events
            if (e.type === 'click' && e.clientX !== 0 && e.clientY !== 0) {
                selectedValue.textContent = this.children[1].textContent;
                customSelect.classList.remove('active');
            }
            // Key Events
            if (e.key === 'Enter') {
                selectedValue.textContent = this.textContent;
                customSelect.classList.remove('active');
            }
        }

        option.addEventListener('keyup', handler);
        option.addEventListener('click', handler);
    });
}
