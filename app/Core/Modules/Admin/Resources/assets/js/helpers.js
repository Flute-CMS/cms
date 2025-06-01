function togglePassword(event) {
    var button = event.currentTarget;
    var input = button
        .closest('.input-wrapper')
        .querySelector('input[type="password"], input[type="text"]');
    var iconEye = button.querySelector('.icon-eye');
    var iconEyeSlash = button.querySelector('.icon-eye-slash');

    if (input.type === 'password') {
        input.type = 'text';
        iconEye.style.display = 'none';
        iconEyeSlash.style.display = 'inline';
    } else {
        input.type = 'password';
        iconEye.style.display = 'inline';
        iconEyeSlash.style.display = 'none';
    }
}

function isMobileDevice() {
    return window.innerWidth <= 768;
}

if (
    typeof FilePondPluginFileValidateType !== 'undefined' &&
    typeof FilePondPluginImagePreview !== 'undefined'
) {
    if (!window.filePondPluginsRegistered) {
        FilePond.registerPlugin(
            FilePondPluginFileValidateType,
            FilePondPluginImagePreview,
        );
        window.filePondPluginsRegistered = true;
    }
}

function initializeFilePondElement(element) {
    if (element.classList.contains('filepond') && !element.filepond) {
        const defaultFile = element.dataset.defaultFile || null;
        const wrapper = element.closest('.input-wrapper');

        let filePondOptions = {
            storeAsFile: true,
            credits: false,
            onwarning: (error) => {
                const errorElement = wrapper.querySelector('.has-error');
                if (errorElement) {
                    errorElement.style.display = 'block';
                }
            },
            onerror: (error) => {
                const errorElement = wrapper.querySelector('.has-error');
                if (errorElement) {
                    errorElement.style.display = 'block';
                }
            }
        };

        if (element.hasAttribute('multiple')) {
            filePondOptions.allowReorder = true;
            filePondOptions.allowMultiple = true;
        }

        try {
            filePondOptions = {
                ...(element.dataset.filePondOptions
                    ? JSON.parse(element.dataset.filePondOptions)
                    : {}),
                ...filePondOptions,
            };
        } catch (e) {
            console.error('Ошибка парсинга filePondOptions:', e);
        }

        const acceptAttr = element.dataset.accept || '';
        const acceptedFileTypes = acceptAttr
            .split(',')
            .map((type) => type.trim())
            .filter((type) => type);

        if (acceptedFileTypes.length > 0) {
            filePondOptions.acceptedFileTypes = acceptedFileTypes;
        }

        if (element.name) {
            filePondOptions.name = element.name;
        }

        if (defaultFile) {
            filePondOptions.files = [
                {
                    source: defaultFile,
                },
            ];
        }

        FilePond.create(wrapper, filePondOptions);
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const fileInputs = document.querySelectorAll('input.filepond');
    fileInputs.forEach((input) => initializeFilePondElement(input));
});

document.body.addEventListener('htmx:load', function (evt) {
    const swappedContent = evt.detail.elt;
    const newFileInputs = swappedContent.querySelectorAll('input.filepond');
    newFileInputs.forEach((input) => initializeFilePondElement(input));
});

if (typeof Choices !== 'undefined') {
    if (!window.choicesInitialized) {
        window.choicesInitialized = true;
    }
}

function initializeChoicesElement(element) {
    if (element.classList.contains('choices') && !element.choicesInstance) {
        let choicesOptions = {
            searchEnabled: true,
            shouldSort: true,
        };

        try {
            choicesOptions = {
                ...(element.dataset.choicesOptions
                    ? JSON.parse(element.dataset.choicesOptions)
                    : {}),
                ...choicesOptions,
            };
        } catch (e) {
            console.error('Ошибка парсинга choicesOptions:', e);
        }

        const choices = new Choices(element, choicesOptions);
        element.choicesInstance = choices;
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const selectElements = document.querySelectorAll('select.choices');
    selectElements.forEach((select) => initializeChoicesElement(select));
});

document.body.addEventListener('htmx:load', function (evt) {
    const swappedContent = evt.detail.elt;
    const newSelectElements = swappedContent.querySelectorAll('select.choices');
    newSelectElements.forEach((select) => initializeChoicesElement(select));
});

$(document).on('click', '[hx-flute-confirm]', function (event) {
    event.preventDefault();

    let $triggerElement = $(this);
    let confirmMessage = $triggerElement.attr('hx-flute-confirm');
    let confirmType = $triggerElement.attr('hx-flute-confirm-type') || 'error';

    const confirmTypes = {
        accent: {
            buttonClass: 'btn-accent',
            iconClass: 'icon-accent',
        },
        primary: {
            buttonClass: 'btn-primary',
            iconClass: 'icon-primary',
        },
        error: {
            buttonClass: 'btn-error',
            iconClass: 'icon-error',
        },
        warning: {
            buttonClass: 'btn-warning',
            iconClass: 'icon-warning',
        },
        info: {
            buttonClass: 'btn-info',
            iconClass: 'icon-info',
        },
    };

    let currentType = confirmTypes[confirmType] || confirmTypes['error'];

    $('#confirmation-dialog-message').text(confirmMessage);

    let $confirmButton = $('#confirmation-dialog-confirm');

    $confirmButton.removeClass(
        'btn-accent btn-primary btn-error btn-warning btn-info',
    );
    $confirmButton.addClass(currentType.buttonClass);

    let $iconContainer = $('#confirmation-dialog-icon');
    $iconContainer.children().hide();
    $iconContainer.find('.' + currentType.iconClass).show();

    openModal('confirmation-dialog');

    $('#confirmation-dialog-confirm').one('click', function () {
        closeModal('confirmation-dialog');
        htmx.trigger($triggerElement[0], 'confirmed');
    });

    $('#confirmation-dialog-cancel').one('click', function () {
        closeModal('confirmation-dialog');
    });
});
