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

// Track FilePond instances for proper lifecycle (destroy before morph swap).
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
    const wrapper = element.closest('.input-wrapper') || element.closest('.form-field') || element.parentElement;
    if (!wrapper || _filePondInstances.has(wrapper)) return;
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
        console.error('Error parsing filePondOptions:', e);
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

    // Read crop config & find container BEFORE FilePond.create() removes element from DOM
    var hasCrop = element.dataset.cropAspect !== undefined;
    var cropCfg = null;
    var cropContainer = null;
    if (hasCrop) {
        cropCfg = {
            aspectRatio: element.dataset.cropAspect ? parseFloat(element.dataset.cropAspect) : NaN,
            round: element.dataset.cropRound === 'true',
            width: element.dataset.cropWidth ? parseInt(element.dataset.cropWidth, 10) : undefined,
            height: element.dataset.cropHeight ? parseInt(element.dataset.cropHeight, 10) : undefined
        };
        cropContainer = element.closest('.input-wrapper') || element.closest('.form-field') || element.parentElement;
    }

    var pond = FilePond.create(element, filePondOptions);
    _filePondInstances.set(wrapper, pond);

    // Image crop integration — hook onaddfile to open Cropper.js modal
    if (hasCrop && cropContainer && typeof window.ImageCropper !== 'undefined') {
        var busy = false;

        pond.onaddfile = function (err, item) {
            if (err || !item || !item.file || busy) return;
            if (item.origin !== 1) return; // only user-added files, not server-loaded defaults
            if (!item.file.type || !item.file.type.startsWith('image/')) return;
            if (item.file._cropped) return;

            busy = true;
            window.ImageCropper.open(item.file, cropCfg)
                .then(function (cropped) {
                    pond.removeFile(item.id, { revert: false });
                    pond.addFile(cropped).then(function () { busy = false; }).catch(function () { busy = false; });
                })
                .catch(function () {
                    pond.removeFile(item.id, { revert: false });
                    busy = false;
                });
        };

        // Add edit button for re-cropping
        if (cropContainer) window.ImageCropper.addEditButton(pond, cropContainer, cropCfg);
    }
}

function _registerFilePondPlugins() {
    if (typeof FilePond !== 'undefined' && !window.filePondPluginsRegistered) {
        const plugins = [];
        if (typeof FilePondPluginImagePreview !== 'undefined') plugins.push(FilePondPluginImagePreview);
        if (typeof FilePondPluginFileValidateType !== 'undefined') plugins.push(FilePondPluginFileValidateType);
        if (typeof FilePondPluginFileValidateSize !== 'undefined') plugins.push(FilePondPluginFileValidateSize);
        if (typeof FilePondPluginImageExifOrientation !== 'undefined') plugins.push(FilePondPluginImageExifOrientation);
        if (plugins.length) FilePond.registerPlugin(...plugins);
        window.filePondPluginsRegistered = true;
    }

    document.querySelectorAll('input.filepond').forEach(initializeFilePondElement);
}

document.addEventListener('DOMContentLoaded', function () {
    (typeof requestIdleCallback === 'function' ? requestIdleCallback : setTimeout)(_registerFilePondPlugins);
});

// Destroy FilePond instances BEFORE swap (especially morph) so that the
// original <input> elements are restored and the morph algorithm can
// reconcile old DOM with new server HTML without layout breakage.
document.body.addEventListener('htmx:beforeSwap', function (evt) {
    const target = evt.detail.target;
    if (target) destroyFilePondsIn(target);
});

// Also clean up per-element (Yoyo morph removes individual nodes).
document.body.addEventListener('htmx:beforeCleanupElement', function (evt) {
    destroyFilePondsIn(evt.target);
});

// Re-initialize FilePond after HTMX settles the new DOM.
// htmx:load fires on every element loaded into the DOM (initial + swaps) —
// most reliable hook for catching new filepond inputs.
function _initFilePondsIn(el) {
    if (!el) return;
    if (el.matches && el.matches('input.filepond')) initializeFilePondElement(el);
    else if (el.querySelectorAll) el.querySelectorAll('input.filepond').forEach(initializeFilePondElement);
}

document.body.addEventListener('htmx:load', function (evt) {
    _initFilePondsIn(evt.detail.elt);
});

document.body.addEventListener('htmx:afterSettle', function (evt) {
    _initFilePondsIn(evt.detail && evt.detail.target ? evt.detail.target : evt.target);
});
