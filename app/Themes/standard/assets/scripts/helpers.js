function togglePassword(event) {
    const button = event.currentTarget;
    const passwordInput = button.closest('.input-wrapper').querySelector('input');
    const type = passwordInput.getAttribute('type');

    passwordInput.setAttribute('type', type === 'password' ? 'text' : 'password');

    const isVisible = passwordInput.getAttribute('type') === 'text';
    button.setAttribute('aria-pressed', isVisible.toString());
    button.setAttribute('aria-label', isVisible ? 'Hide password' : 'Show password');

    var iconEye = button.querySelector('.icon-eye');
    var iconEyeSlash = button.querySelector('.icon-eye-slash');
    if (iconEye) {
        if (isVisible) {
            iconEye.style.display = 'none';
            iconEyeSlash.style.display = 'block';
        } else {
            iconEye.style.display = 'block';
            iconEyeSlash.style.display = 'none';
        }
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

        let filePondOptions = {
            storeAsFile: true,
            credits: false,
            labelIdle: 'Drag & Drop your files or <span class="filepond--label-action">Browse</span>',
            labelButtonAbortItemLoad: 'Cancel',
            labelButtonRetryItemLoad: 'Retry',
            labelButtonAbortItemProcessing: 'Cancel',
            labelButtonUndoItemProcessing: 'Undo',
            labelButtonRetryItemProcessing: 'Retry',
            labelButtonProcessItem: 'Upload',
            accessibility: {
                announceStatusUpdates: true,
                allowBrowseOnMobile: true
            }
        };

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

        const pondElement = FilePond.create(element, filePondOptions);

        if (pondElement && pondElement.element) {
            const root = pondElement.element.querySelector('.filepond--root');
            if (root) {
                root.setAttribute('tabindex', '0');
                root.setAttribute('role', 'button');
                root.setAttribute('aria-label', 'File input, press Enter to browse for files');

                root.addEventListener('keydown', (event) => {
                    if (event.key === 'Enter') {
                        event.preventDefault();
                        const browseButton = root.querySelector('.filepond--browser');
                        if (browseButton) {
                            browseButton.click();
                        }
                    }
                });
            }
        }
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const fileInputs = document.querySelectorAll('input.filepond');
    fileInputs.forEach((input) => initializeFilePondElement(input));
});

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
        }, 50);
    }
});

document.body.addEventListener('htmx:afterSettle', function (evt) {
    document.querySelectorAll('input.filepond').forEach((input) => {
        if (!input.filepond) {
            initializeFilePondElement(input);
        }
    });
});
