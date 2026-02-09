window.FluteRichText = window.FluteRichText || {};

window.FluteRichText.Modals = class {
    constructor() {
        this._counter = 0;
    }

    _t(key, fallback) {
        var i18n = window.FluteRichText && window.FluteRichText.i18n;
        return (i18n && i18n[key]) || fallback || key;
    }

    _openTemplate(templateId) {
        var tpl = document.getElementById(templateId);
        if (!tpl) {
            console.warn('RichText: template not found: ' + templateId);
            return null;
        }

        var id = 'tiptap-modal-' + ++this._counter;
        var clone = tpl.content.cloneNode(true);
        var modalEl = clone.querySelector('.modal');
        if (!modalEl) return null;

        modalEl.id = id;
        var closeBtn = modalEl.querySelector('.modal__close');
        if (closeBtn) closeBtn.setAttribute('data-a11y-dialog-hide', id);

        // Auto-wire CMS checkboxes: assign unique IDs and link labels via for attribute
        var checkboxes = modalEl.querySelectorAll('.checkbox__field');
        checkboxes.forEach(function (cb, idx) {
            var cbId = id + '-cb-' + idx;
            cb.id = cbId;
            var lbl = cb.nextElementSibling;
            if (lbl && lbl.classList.contains('checkbox__label')) {
                lbl.setAttribute('for', cbId);
            }
        });

        document.body.appendChild(clone);
        var modal = document.getElementById(id);

        var dialog;
        var close = function () {
            if (dialog) dialog.hide();
            else modal.remove();
        };

        if (typeof A11yDialog !== 'undefined') {
            dialog = new A11yDialog(modal);
            modal.dialogInstance = dialog;
            dialog.on('show', function () {
                modal.removeAttribute('aria-hidden');
                modal.classList.add('is-open');
            });
            dialog.on('hide', function () {
                modal.classList.remove('is-open');
                setTimeout(function () {
                    dialog.destroy();
                    modal.remove();
                }, 150);
            });
            dialog.show();
        } else {
            modal.setAttribute('aria-hidden', 'false');
            modal.classList.add('is-open');
        }

        return { modal: modal, close: close };
    }

    showLink(editor) {
        var attrs = editor.getAttributes('link');
        var isEdit = editor.isActive('link');

        var ref = this._openTemplate('tiptap-modal-link-tpl');
        if (!ref) return;
        var modal = ref.modal;
        var close = ref.close;

        var urlInput = modal.querySelector('[data-field="url"]');
        var titleInput = modal.querySelector('[data-field="title"]');
        var blankInput = modal.querySelector('[data-field="blank"]');
        var applyBtn = modal.querySelector('[data-modal-action="apply"]');
        var unlinkBtn = modal.querySelector('[data-modal-action="unlink"]');
        var cancelBtn = modal.querySelector('[data-modal-action="cancel"]');

        if (urlInput) urlInput.value = attrs.href || '';
        if (titleInput) titleInput.value = attrs.title || '';
        if (blankInput) blankInput.checked = attrs.target === '_blank';
        if (unlinkBtn) unlinkBtn.style.display = isEdit ? '' : 'none';

        setTimeout(function () { urlInput && urlInput.focus(); }, 100);

        if (applyBtn) applyBtn.addEventListener('click', function () {
            var url = urlInput.value.trim();
            if (url) {
                var linkAttrs = { href: url };
                var titleVal = titleInput ? titleInput.value.trim() : '';
                if (titleVal) linkAttrs.title = titleVal;
                linkAttrs.target = blankInput && blankInput.checked ? '_blank' : null;
                editor.chain().focus().extendMarkRange('link').setLink(linkAttrs).run();
            }
            close();
        });

        if (unlinkBtn) unlinkBtn.addEventListener('click', function () {
            editor.chain().focus().unsetLink().run();
            close();
        });

        if (cancelBtn) cancelBtn.addEventListener('click', close);

        if (urlInput) urlInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); applyBtn && applyBtn.click(); }
            if (e.key === 'Escape') close();
        });
    }

    showTable(editor) {
        // If cursor is already in a table, overlay controls handle everything
        if (editor.isActive('table')) return;

        var ref = this._openTemplate('tiptap-modal-table-tpl');
        if (!ref) return;
        var modal = ref.modal;
        var close = ref.close;

        var grid = modal.querySelector('.table-grid-picker');
        var label = modal.querySelector('.table-grid-label');

        for (var r = 0; r < 8; r++) {
            for (var c = 0; c < 8; c++) {
                var cell = document.createElement('div');
                cell.className = 'table-grid-cell';
                cell.dataset.row = r + 1;
                cell.dataset.col = c + 1;
                grid.appendChild(cell);
            }
        }

        grid.addEventListener('mouseover', function (e) {
            var cell = e.target.closest('.table-grid-cell');
            if (!cell) return;
            var r = +cell.dataset.row, c = +cell.dataset.col;
            label.textContent = r + ' \u00D7 ' + c;
            grid.querySelectorAll('.table-grid-cell').forEach(function (cl) {
                cl.classList.toggle('is-selected', +cl.dataset.row <= r && +cl.dataset.col <= c);
            });
        });

        grid.addEventListener('click', function (e) {
            var cell = e.target.closest('.table-grid-cell');
            if (!cell) return;
            editor.chain().focus().insertTable({
                rows: +cell.dataset.row,
                cols: +cell.dataset.col,
                withHeaderRow: true,
            }).run();
            close();
        });
    }

    showYoutube(editor) {
        var ref = this._openTemplate('tiptap-modal-youtube-tpl');
        if (!ref) return;
        var modal = ref.modal;
        var close = ref.close;
        var input = modal.querySelector('[data-field="url"]');

        setTimeout(function () { input && input.focus(); }, 100);

        var insertBtn = modal.querySelector('[data-modal-action="insert"]');
        if (insertBtn) insertBtn.addEventListener('click', function () {
            var url = input.value.trim();
            if (url) editor.chain().focus().setYoutubeVideo({ src: url }).run();
            close();
        });

        var cancelBtn = modal.querySelector('[data-modal-action="cancel"]');
        if (cancelBtn) cancelBtn.addEventListener('click', close);

        if (input) input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); insertBtn && insertBtn.click(); }
            if (e.key === 'Escape') close();
        });
    }

    showTranslation(editor) {
        var ref = this._openTemplate('tiptap-modal-translation-tpl');
        if (!ref) return;
        var modal = ref.modal;
        var close = ref.close;
        var input = modal.querySelector('[data-field="key"]');

        setTimeout(function () { input && input.focus(); }, 100);

        var insertBtn = modal.querySelector('[data-modal-action="insert"]');
        if (insertBtn) insertBtn.addEventListener('click', function () {
            var key = input.value.trim();
            if (key) editor.chain().focus().insertContent('{{ __("' + key + '") }}').run();
            close();
        });

        var cancelBtn = modal.querySelector('[data-modal-action="cancel"]');
        if (cancelBtn) cancelBtn.addEventListener('click', close);

        if (input) input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); insertBtn && insertBtn.click(); }
            if (e.key === 'Escape') close();
        });
    }

    showImageAlt(editor) {
        var ref = this._openTemplate('tiptap-modal-image-alt-tpl');
        if (!ref) return;
        var modal = ref.modal;
        var close = ref.close;
        var input = modal.querySelector('[data-field="alt"]');

        var current = editor.getAttributes('image').alt || '';
        if (input) input.value = current;

        setTimeout(function () { input && input.focus(); }, 100);

        var saveBtn = modal.querySelector('[data-modal-action="save"]');
        if (saveBtn) saveBtn.addEventListener('click', function () {
            editor.chain().focus().updateAttributes('image', { alt: input.value }).run();
            close();
        });

        var cancelBtn = modal.querySelector('[data-modal-action="cancel"]');
        if (cancelBtn) cancelBtn.addEventListener('click', close);
    }
};
