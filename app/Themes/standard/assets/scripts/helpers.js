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

document.body.addEventListener('htmx:afterSwap', function (evt) {
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
            allowHTML: false,
            searchResultLimit: 10,
            fuseOptions: {
                threshold: 0.3,  // Makes search more forgiving
            },
            classNames: {
                containerOuter: 'choices',
                containerInner: 'choices__inner',
                input: 'choices__input',
                inputCloned: 'choices__input--cloned',
                list: 'choices__list',
                listItems: 'choices__list--multiple',
                listSingle: 'choices__list--single',
                listDropdown: 'choices__list--dropdown',
                item: 'choices__item',
                itemSelectable: 'choices__item--selectable',
                itemDisabled: 'choices__item--disabled',
                itemChoice: 'choices__item--choice',
                placeholder: 'choices__placeholder',
                group: 'choices__group',
                groupHeading: 'choices__heading',
                button: 'choices__button',
                activeState: 'is-active',
                focusState: 'is-focused',
                openState: 'is-open',
                disabledState: 'is-disabled',
                highlightedState: 'is-highlighted',
                selectedState: 'is-selected',
                flippedState: 'is-flipped',
                loadingState: 'is-loading',
                noResults: 'has-no-results',
                noChoices: 'has-no-choices'
            }
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

        // Enhanced accessibility attributes
        if (!element.hasAttribute('aria-label') && !element.hasAttribute('aria-labelledby')) {
            const labelElement = document.querySelector(`label[for="${element.id}"]`);
            if (labelElement) {
                element.setAttribute('aria-labelledby', labelElement.id || `${element.id}-label`);
                if (!labelElement.id) {
                    labelElement.id = `${element.id}-label`;
                }
            } else {
                const placeholder = element.getAttribute('placeholder');
                if (placeholder) {
                    element.setAttribute('aria-label', placeholder);
                }
            }
        }

        const helpText = element.parentElement.querySelector('.form-text, .help-text');
        if (helpText && !element.hasAttribute('aria-describedby')) {
            const helpId = helpText.id || `${element.id}-help`;
            if (!helpText.id) {
                helpText.id = helpId;
            }
            element.setAttribute('aria-describedby', helpId);
        }

        const container = choices.containerOuter.element;
        if (container) {
            container.setAttribute('role', 'combobox');
            container.setAttribute('aria-haspopup', 'listbox');
            container.setAttribute('aria-expanded', 'false');

            choices.containerOuter.element.addEventListener('choices:showDropdown', function () {
                container.setAttribute('aria-expanded', 'true');
            });

            choices.containerOuter.element.addEventListener('choices:hideDropdown', function () {
                container.setAttribute('aria-expanded', 'false');
            });
        }
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