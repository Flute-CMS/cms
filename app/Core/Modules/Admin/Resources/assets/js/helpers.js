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

// Plugin registration is deferred to DOMContentLoaded because helpers.js is
// inlined (no defer) and runs before the FilePond <script defer> tags execute.

// Track FilePond instances by wrapper element for proper lifecycle management.
const _filePondInstances = new WeakMap();

function destroyFilePondsIn(scope) {
    if (typeof FilePond === 'undefined' || !scope) return;
    const wrappers = [];
    if (scope.matches?.('.input-wrapper')) wrappers.push(scope);
    scope.querySelectorAll?.('.input-wrapper').forEach(w => wrappers.push(w));
    wrappers.forEach(wrapper => {
        const pond = _filePondInstances.get(wrapper);
        if (pond) {
            try { pond.destroy(); } catch (_) {}
            _filePondInstances.delete(wrapper);
        }
    });
}

function initializeFilePondElement(element) {
    if (!element.classList.contains('filepond')) return;
    const defaultFile = element.dataset.defaultFile || null;
    const wrapper = element.closest('.input-wrapper');
    if (!wrapper || _filePondInstances.has(wrapper)) return;
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

        // Use native fetch for loading existing files to avoid XHR headers
        // that may trigger middleware 400 rejections.
        filePondOptions.server = {
            load: (source, load, error) => {
                fetch(source, { credentials: 'same-origin' })
                    .then(r => r.ok ? r.blob() : Promise.reject(r.status))
                    .then(blob => {
                        const filename = source.split('/').pop().split('?')[0] || 'file';
                        load(new File([blob], filename, { type: blob.type }));
                    })
                    .catch(e => error(String(e)));
            },
        };

        if (defaultFile) {
            filePondOptions.files = [
                {
                    source: defaultFile,
                    options: {
                        type: 'local',
                    },
                },
            ];
        }

    const pond = FilePond.create(wrapper, filePondOptions);
    _filePondInstances.set(wrapper, pond);
}

document.addEventListener('DOMContentLoaded', function () {
    // Register FilePond plugins here — deferred scripts have all executed by now.
    if (typeof FilePond !== 'undefined' && !window.filePondPluginsRegistered) {
        const plugins = [];
        if (typeof FilePondPluginImagePreview !== 'undefined') plugins.push(FilePondPluginImagePreview);
        if (typeof FilePondPluginFileValidateType !== 'undefined') plugins.push(FilePondPluginFileValidateType);
        if (plugins.length) FilePond.registerPlugin(...plugins);
        window.filePondPluginsRegistered = true;
    }

    document.querySelectorAll('input.filepond').forEach(initializeFilePondElement);
    // Clean up per-element (Yoyo morph) but NOT on beforeSwap —
    // destroying before swap causes a visible native-input flash.
    document.body.addEventListener('htmx:beforeCleanupElement', e => destroyFilePondsIn(e.target));
});

// FilePond re-initialization after HTMX swaps is handled by the central
// htmx:afterSettle handler in tabs.js → reinitializeComponents → initFilePonds.


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

