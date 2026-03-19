/**
 * Image Cropper — Cropper.js modal using the native CMS A11yDialog system.
 *
 * API:
 *   ImageCropper.open(file, { aspectRatio, round, width, height }) → Promise<File>
 *   ImageCropper.addEditButton(pondInstance, container, cropConfig)
 */

(function () {
    'use strict';

    var MODAL_ID = 'image-cropper-dialog';
    var _modalEl = null;
    var _cropper = null;
    var _resolve = null;
    var _reject = null;
    var _file = null;
    var _opts = null;

    function _t(key) {
        var i18n = window.__imageCropperI18n || {};
        var fb = {
            crop_image: 'Crop Image', rotate_left: 'Rotate Left',
            rotate_right: 'Rotate Right', flip_horizontal: 'Flip Horizontal',
            flip_vertical: 'Flip Vertical', zoom_in: 'Zoom In',
            zoom_out: 'Zoom Out', reset: 'Reset', cancel: 'Cancel',
            apply: 'Apply', free: 'Free'
        };
        return i18n[key] || fb[key] || key;
    }

    // ── Icons ────────────────────────────────────────────────────────────────

    var I = {
        rotL: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg>',
        rotR: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.13-9.36L23 10"/></svg>',
        flipH: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3H5a2 2 0 0 0-2 2v14c0 1.1.9 2 2 2h3"/><path d="M16 3h3a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-3"/><line x1="12" y1="20" x2="12" y2="4"/></svg>',
        flipV: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M3 8V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v3"/><path d="M3 16v3a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-3"/><line x1="4" y1="12" x2="20" y2="12"/></svg>',
        zIn: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg>',
        zOut: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="8" y1="11" x2="14" y2="11"/></svg>',
        reset: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>',
        free: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 3 21 3 21 9"/><polyline points="9 21 3 21 3 15"/><line x1="21" y1="3" x2="14" y2="10"/><line x1="3" y1="21" x2="10" y2="14"/></svg>',
        sq: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>',
        land: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/></svg>',
        port: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2"/></svg>',
        edit: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>'
    };

    function icon(svg, cls) { return '<span class="' + (cls || 'image-cropper__icon') + '">' + svg + '</span>'; }

    // Tool button helper
    function toolBtn(action, title, svg) {
        return '<button type="button" class="image-cropper__tool-btn" data-crop-action="' + action + '" title="' + title + '">' + icon(svg) + '</button>';
    }

    // ── Build modal DOM ──────────────────────────────────────────────────────

    function ensureModal() {
        if (_modalEl) return _modalEl;

        var el = document.createElement('div');
        el.id = MODAL_ID;
        el.className = 'modal dialog-container modal--lg';
        el.setAttribute('role', 'dialog');
        el.setAttribute('aria-hidden', 'true');
        el.setAttribute('aria-labelledby', MODAL_ID + '-title');
        el.setAttribute('data-a11y-dialog', '');
        el.setAttribute('data-disable-modal-autofocus', '');

        el.innerHTML =
            '<div class="modal__overlay dialog-overlay" tabindex="-1" data-a11y-dialog-hide></div>' +
            '<div class="modal__container dialog-content image-cropper" role="document" tabindex="0">' +

                '<header class="modal__header">' +
                    '<h4 class="modal__title" id="' + MODAL_ID + '-title">' + _t('crop_image') + '</h4>' +
                    '<button class="modal__close dialog-close" aria-label="Close" data-a11y-dialog-hide="' + MODAL_ID + '"></button>' +
                '</header>' +

                '<div class="modal__content dialog-body image-cropper__body">' +

                    // Canvas
                    '<div class="image-cropper__canvas">' +
                        '<img class="image-cropper__image" alt="">' +
                    '</div>' +

                    // Toolbar: ratios left, tools right
                    '<div class="image-cropper__toolbar">' +
                        '<div class="image-cropper__ratios"></div>' +
                        '<div class="image-cropper__tools">' +
                            toolBtn('rotate-left', _t('rotate_left'), I.rotL) +
                            toolBtn('rotate-right', _t('rotate_right'), I.rotR) +
                            '<div class="image-cropper__tool-sep"></div>' +
                            toolBtn('flip-h', _t('flip_horizontal'), I.flipH) +
                            toolBtn('flip-v', _t('flip_vertical'), I.flipV) +
                            '<div class="image-cropper__tool-sep"></div>' +
                            toolBtn('zoom-in', _t('zoom_in'), I.zIn) +
                            toolBtn('zoom-out', _t('zoom_out'), I.zOut) +
                            '<div class="image-cropper__tool-sep"></div>' +
                            toolBtn('reset', _t('reset'), I.reset) +
                        '</div>' +
                    '</div>' +

                '</div>' +

                '<footer class="modal__footer image-cropper__footer">' +
                    '<button type="button" class="btn btn-outline-primary btn-small" data-crop-action="cancel">' + _t('cancel') + '</button>' +
                    '<button type="button" class="btn btn-accent btn-small" data-crop-action="apply">' +
                        icon('<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>', 'image-cropper__icon--sm') +
                        _t('apply') +
                    '</button>' +
                '</footer>' +

            '</div>';

        (document.getElementById('modals') || document.body).appendChild(el);
        _modalEl = el;

        // Init via CMS modal system
        if (typeof initializeA11yDialog === 'function') {
            initializeA11yDialog(el.parentElement);
        }

        if (el.dialogInstance) {
            el.dialogInstance.on('hide', function () {
                if (_cropper) doCancel(true);
            });
        }

        // Delegated click for all actions
        el.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-crop-action]');
            if (!btn) return;
            e.preventDefault();
            var a = btn.dataset.cropAction;
            if (a === 'apply') return doApply();
            if (a === 'cancel') return doCancel();
            if (!_cropper) return;
            var d;
            switch (a) {
                case 'rotate-left':  _cropper.rotate(-90); break;
                case 'rotate-right': _cropper.rotate(90);  break;
                case 'flip-h': d = _cropper.getData(); _cropper.scaleX(d.scaleX === -1 ? 1 : -1); break;
                case 'flip-v': d = _cropper.getData(); _cropper.scaleY(d.scaleY === -1 ? 1 : -1); break;
                case 'zoom-in':  _cropper.zoom(0.1);  break;
                case 'zoom-out': _cropper.zoom(-0.1); break;
                case 'reset':    _cropper.reset();     break;
            }
        });

        return el;
    }

    // ── Ratio bar ────────────────────────────────────────────────────────────

    function buildRatioButtons(aspect) {
        var box = _modalEl.querySelector('.image-cropper__ratios');
        box.innerHTML = '';

        var presets = [
            { v: NaN,    icon: I.free, label: _t('free') },
            { v: 1,      icon: I.sq,   label: '1:1' },
            { v: 16 / 9, icon: I.land, label: '16:9' },
            { v: 4 / 3,  icon: I.land, label: '4:3' },
            { v: 9 / 16, icon: I.port, label: '9:16' }
        ];

        presets.forEach(function (p) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'image-cropper__ratio-btn';
            btn.innerHTML = icon(p.icon, 'image-cropper__icon--sm') + '<span>' + p.label + '</span>';

            var active = (isNaN(p.v) && isNaN(aspect)) ||
                         (!isNaN(p.v) && !isNaN(aspect) && Math.abs(p.v - aspect) < 0.01);
            if (active) btn.classList.add('is-active');

            btn.addEventListener('click', function () {
                if (!_cropper) return;
                box.querySelectorAll('.image-cropper__ratio-btn').forEach(function (b) { b.classList.remove('is-active'); });
                btn.classList.add('is-active');
                _cropper.setAspectRatio(isNaN(p.v) ? NaN : p.v);
                _modalEl.querySelector('.image-cropper__canvas').classList.toggle('is-round', p.v === 1);
            });

            box.appendChild(btn);
        });
    }

    // ── Open ─────────────────────────────────────────────────────────────────

    function openCropper(file, opts) {
        opts = opts || {};
        _file = file;
        _opts = opts;

        var modal = ensureModal();
        var img = modal.querySelector('.image-cropper__image');
        var canvasEl = modal.querySelector('.image-cropper__canvas');

        var aspect = (opts.aspectRatio !== undefined && !isNaN(opts.aspectRatio)) ? opts.aspectRatio : NaN;
        canvasEl.classList.toggle('is-round', !!opts.round);
        buildRatioButtons(aspect);

        return new Promise(function (resolve, reject) {
            _resolve = resolve;
            _reject = reject;

            var url = URL.createObjectURL(file);
            img.onload = function () {
                if (typeof openModal === 'function') openModal(MODAL_ID);
                else if (modal.dialogInstance) modal.dialogInstance.show();

                _cropper = new Cropper(img, {
                    aspectRatio: isNaN(aspect) ? NaN : aspect,
                    viewMode: 1,
                    dragMode: 'move',
                    autoCropArea: 0.92,
                    responsive: true,
                    restore: false,
                    guides: true,
                    center: true,
                    highlight: false,
                    cropBoxMovable: true,
                    cropBoxResizable: true,
                    toggleDragModeOnDblclick: false,
                    background: true
                });
            };
            img.src = url;
        });
    }

    // ── Apply ────────────────────────────────────────────────────────────────

    function doApply() {
        if (!_cropper || !_resolve) return;

        var co = { imageSmoothingEnabled: true, imageSmoothingQuality: 'high' };
        if (_opts && _opts.width) co.width = _opts.width;
        if (_opts && _opts.height) co.height = _opts.height;

        var canvas = _cropper.getCroppedCanvas(co);
        var mime = (_file && _file.type) || 'image/png';

        canvas.toBlob(function (blob) {
            var name = _file ? _file.name : 'cropped.png';
            var f = new File([blob], name, { type: blob.type || mime, lastModified: Date.now() });
            f._cropped = true;
            var cb = _resolve;
            cleanup();
            cb(f);
        }, mime, 0.92);
    }

    function doCancel(fromHide) {
        var cb = _reject;
        cleanup(fromHide);
        if (cb) cb('cancelled');
    }

    function cleanup(skipHide) {
        if (_cropper) { _cropper.destroy(); _cropper = null; }
        if (!skipHide) {
            if (typeof closeModal === 'function') closeModal(MODAL_ID);
            else if (_modalEl && _modalEl.dialogInstance) _modalEl.dialogInstance.hide();
        }
        if (_modalEl) {
            var img = _modalEl.querySelector('.image-cropper__image');
            if (img && img.src) { URL.revokeObjectURL(img.src); img.removeAttribute('src'); }
        }
        _resolve = null; _reject = null; _file = null; _opts = null;
    }

    // ── Edit button on FilePond ──────────────────────────────────────────────
    // Injects a persistent edit button OUTSIDE the filepond--root (on the
    // .input-wrapper) so it isn't clipped by overflow:hidden.

    function addEditButton(pondInstance, container, cropCfg) {
        // Place on container (.input-wrapper), OUTSIDE filepond--root
        // so FilePond's internal re-renders don't remove it.
        if (container.querySelector('.image-cropper__edit-btn')) return;

        // Mark container for CSS positioning
        container.classList.add('has-crop-edit');

        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'image-cropper__edit-btn';
        btn.title = _t('crop_image');
        btn.innerHTML = icon(I.edit);

        btn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();

            var files = pondInstance.getFiles();
            if (!files.length || !files[0].file) return;
            if (container._cropBusy) return;
            container._cropBusy = true;

            window.ImageCropper.open(files[0].file, cropCfg)
                .then(function (cropped) {
                    pondInstance.removeFiles({ revert: false });
                    pondInstance.addFile(cropped)
                        .then(function () { container._cropBusy = false; })
                        .catch(function () { container._cropBusy = false; });
                })
                .catch(function () { container._cropBusy = false; });
        });

        container.appendChild(btn);
    }

    // ── Public ───────────────────────────────────────────────────────────────

    window.ImageCropper = {
        open: openCropper,
        addEditButton: addEditButton
    };
})();
