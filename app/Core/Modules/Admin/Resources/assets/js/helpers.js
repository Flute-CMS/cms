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
        const fieldName = element.name;

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
            },
            onremovefile: (error, file) => {
                if (!error && fieldName) {
                    let clearInput = wrapper.querySelector(`input[name="${fieldName}_clear"]`);
                    if (!clearInput) {
                        clearInput = document.createElement('input');
                        clearInput.type = 'hidden';
                        clearInput.name = `${fieldName}_clear`;
                        wrapper.appendChild(clearInput);
                    }
                    clearInput.value = '1';
                }
            },
            onaddfile: (error, file) => {
                if (!error && fieldName) {
                    const clearInput = wrapper.querySelector(`input[name="${fieldName}_clear"]`);
                    if (clearInput) {
                        clearInput.value = '';
                    }
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

            filePondOptions.fileValidateTypeDetectType = (source, type) => {
                return new Promise((resolve, reject) => {
                    if (type && acceptedFileTypes.includes(type)) {
                        resolve(type);
                        return;
                    }

                    const url = typeof source === 'string' ? source : (source.name || '');
                    const extensionMatch = url.match(/\.([a-zA-Z0-9]+)(?:\?.*)?$/);

                    if (extensionMatch) {
                        const extension = extensionMatch[1].toLowerCase();
                        const extensionToMime = {
                            'jpg': 'image/jpeg',
                            'jpeg': 'image/jpeg',
                            'png': 'image/png',
                            'gif': 'image/gif',
                            'webp': 'image/webp',
                            'svg': 'image/svg+xml',
                            'bmp': 'image/bmp'
                        };

                        if (extensionToMime[extension]) {
                            resolve(extensionToMime[extension]);
                            return;
                        }
                    }

                    if (type) {
                        resolve(type);
                    } else {
                        reject();
                    }
                });
            };
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

// htmx:load - triggered for each new element added to DOM
document.body.addEventListener('htmx:load', function (evt) {
    const swappedContent = evt.detail.elt;
    if (swappedContent) {
        if (swappedContent.matches && swappedContent.matches('input.filepond')) {
            initializeFilePondElement(swappedContent);
        } else if (swappedContent.querySelectorAll) {
            const newFileInputs = swappedContent.querySelectorAll('input.filepond');
            newFileInputs.forEach((input) => initializeFilePondElement(input));
        }
    }
});

// htmx:afterSwap - triggered after any swap operation (including yoyo)
document.body.addEventListener('htmx:afterSwap', function (evt) {
    const swappedContent = evt.detail.elt;
    if (swappedContent) {
        setTimeout(() => {
            if (swappedContent.matches && swappedContent.matches('input.filepond')) {
                initializeFilePondElement(swappedContent);
            } else if (swappedContent.querySelectorAll) {
                const newFileInputs = swappedContent.querySelectorAll('input.filepond');
                newFileInputs.forEach((input) => initializeFilePondElement(input));
            }
        }, 10);
    }
});

// htmx:afterSettle - triggered after swap and settle are complete
document.body.addEventListener('htmx:afterSettle', function (evt) {
    // Reinitialize any remaining uninitialized FilePond inputs
    document.querySelectorAll('input.filepond').forEach((input) => {
        if (!input.filepond) {
            initializeFilePondElement(input);
        }
    });
});


let _enforcingLanguages = false;
document.addEventListener('change', function (event) {
    const checkbox = event.target;
    if (!_enforcingLanguages && checkbox && checkbox.matches && checkbox.matches('input[type="checkbox"][name^="available["]')) {
        const boxes = Array.from(document.querySelectorAll('input[type="checkbox"][name^="available["]'));
        if (!boxes.length) return;

        const anyChecked = boxes.some((el) => el.checked);
        if (anyChecked) return;

        _enforcingLanguages = true;
        try {
            const en = document.querySelector('input[type="checkbox"][name="available[en]"]');
            const fallback = en || boxes[0];
            if (fallback) {
                fallback.checked = true;
            }
        } finally {
            setTimeout(() => {
                _enforcingLanguages = false;
            }, 0);
        }
    }
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
