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
        wrapper._pondDestroying = true;
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
            labelIdle: '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 256 256" fill="currentColor" class="filepond--icon-upload"><path d="M178.83,109.17a4,4,0,0,1-5.66,5.66L132,73.66V152a4,4,0,0,1-8,0V73.66L82.83,114.83a4,4,0,0,1-5.66-5.66l48-48a4,4,0,0,1,5.66,0ZM216,204H40a4,4,0,0,0,0,8H216a4,4,0,0,0,0-8Z"/></svg><span class="filepond--label-text">' + (typeof translate === 'function' ? translate('def.drag_and_drop') : 'Drag & Drop your files or') + ' <span class="filepond--label-action">' + (typeof translate === 'function' ? translate('def.browse') : 'Browse') + '</span></span>',
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
            onupdatefiles: (files) => {
                if (fieldName && !wrapper._pondDestroying) {
                    const clearInput = (wrapper.parentElement || wrapper).querySelector(`input[data-filepond-clear="${fieldName}"]`);
                    if (clearInput) clearInput.value = files.length === 0 ? '1' : '0';
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

    // Read crop config BEFORE FilePond.create() which may remove input from DOM
    var cropSrc = element.classList.contains('filepond') ? element : (wrapper.querySelector('input.filepond') || element);
    var hasCrop = cropSrc.dataset && cropSrc.dataset.cropAspect !== undefined;
    var cropCfg = null;
    if (hasCrop) {
        cropCfg = {
            aspectRatio: cropSrc.dataset.cropAspect ? parseFloat(cropSrc.dataset.cropAspect) : NaN,
            round: cropSrc.dataset.cropRound === 'true',
            width: cropSrc.dataset.cropWidth ? parseInt(cropSrc.dataset.cropWidth, 10) : undefined,
            height: cropSrc.dataset.cropHeight ? parseInt(cropSrc.dataset.cropHeight, 10) : undefined
        };
    }

    const pond = FilePond.create(wrapper, filePondOptions);
    _filePondInstances.set(wrapper, pond);

    if (hasCrop && cropCfg && typeof window.ImageCropper !== 'undefined') {
        var busy = false;
        var origOnAdd = pond.onaddfile;

        pond.onaddfile = function (err, item) {
            if (origOnAdd) origOnAdd(err, item);
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
        window.ImageCropper.addEditButton(pond, wrapper, cropCfg);
    }
}

document.addEventListener('DOMContentLoaded', function () {
    // Register FilePond plugins here — deferred scripts have all executed by now.
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
    // Destroy FilePond instances BEFORE swap (especially morph) so that the
    // original <input> elements are restored and the morph algorithm can
    // reconcile old DOM with new server HTML without layout breakage.
    document.body.addEventListener('htmx:beforeSwap', e => {
        if (e.detail && e.detail.target) destroyFilePondsIn(e.detail.target);
    });
    // Also clean up per-element (Yoyo morph removes individual nodes).
    document.body.addEventListener('htmx:beforeCleanupElement', e => destroyFilePondsIn(e.target));
});

// FilePond re-initialization after HTMX swaps is handled by the central
// htmx:afterSettle handler in tabs.js → reinitializeComponents → initFilePonds.

// ── Abort stale boost navigations to #main ──────────────────────────────────
// When the user clicks a new sidebar link before the previous page finishes
// loading, cancel the in-flight request so only the latest one renders.
(function () {
    let pendingMainElt = null;

    if (typeof htmx === 'undefined') return;

    htmx.on('htmx:beforeRequest', function (event) {
        const elt = event.detail.elt;
        if (!elt) return;

        const target = elt.getAttribute('hx-target') ||
            (elt.closest('[hx-target]') ? elt.closest('[hx-target]').getAttribute('hx-target') : null);
        if (target !== '#main') return;

        // Abort the previous in-flight request to #main
        if (pendingMainElt && pendingMainElt !== elt) {
            htmx.trigger(pendingMainElt, 'htmx:abort');
        }
        pendingMainElt = elt;

        const xhr = event.detail.xhr;
        if (xhr) {
            const origChange = xhr.onreadystatechange;
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4) pendingMainElt = null;
                if (origChange) origChange.apply(this, arguments);
            };
        }
    });
})();


// ── Admin profile dropdown ───────────────────────────────────────────────
(function () {
    function initProfileDropdown() {
        const toggle = document.querySelector('[data-admin-profile-toggle]');
        const dropdown = document.querySelector('[data-admin-profile-dropdown]');
        if (!toggle || !dropdown) return;

        function open() {
            dropdown.classList.add('is-open');
            dropdown.setAttribute('aria-hidden', 'false');
            toggle.setAttribute('aria-expanded', 'true');
        }

        function close() {
            dropdown.classList.remove('is-open');
            dropdown.setAttribute('aria-hidden', 'true');
            toggle.setAttribute('aria-expanded', 'false');
        }

        toggle.addEventListener('click', function (e) {
            e.stopPropagation();
            dropdown.classList.contains('is-open') ? close() : open();
        });

        document.addEventListener('click', function (e) {
            if (!dropdown.contains(e.target) && !toggle.contains(e.target)) close();
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') close();
        });

        // Close after navigation
        dropdown.addEventListener('click', function (e) {
            if (e.target.closest('a') || e.target.closest('button[type="submit"]')) {
                setTimeout(close, 100);
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initProfileDropdown);
    } else {
        initProfileDropdown();
    }
})();

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

