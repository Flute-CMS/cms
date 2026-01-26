/**
 * Notification Templates Editor
 */
(function () {
    'use strict';

    let initialized = false;

    function init() {
        if (initialized) return;
        initialized = true;

        initButtonsEditor();
        initVariablesInsert();
        initPreviewUpdater();
    }

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    /**
     * Buttons Editor
     */
    function initButtonsEditor() {
        const editor = document.querySelector('[data-buttons-editor]');
        if (!editor) return;

        const list = editor.querySelector('[data-buttons-list]');
        const addBtn = editor.querySelector('[data-add-button]');

        // Get translations from data attributes
        const labelPlaceholder = editor.dataset.labelPlaceholder || 'Label';
        const urlPlaceholder = editor.dataset.urlPlaceholder || 'URL';
        const emptyText = editor.dataset.emptyText || 'No buttons';

        // Add button
        addBtn?.addEventListener('click', () => {
            const empty = list.querySelector('[data-buttons-empty]');
            if (empty) empty.remove();

            const index = list.querySelectorAll('[data-button-index]').length;
            const html = createButtonHtml(index, labelPlaceholder, urlPlaceholder);
            list.insertAdjacentHTML('beforeend', html);
            updatePreview();
        });

        // Remove button (delegated)
        list?.addEventListener('click', (e) => {
            const removeBtn = e.target.closest('[data-remove-button]');
            if (removeBtn) {
                const item = removeBtn.closest('[data-button-index]');
                if (item) {
                    item.remove();
                    reindexButtons(list);
                    checkEmptyState(list, emptyText);
                    updatePreview();
                }
            }
        });

        // Update preview on input
        list?.addEventListener('input', updatePreview);
    }

    function createButtonHtml(index, labelPlaceholder, urlPlaceholder) {
        return `
            <div class="buttons-editor__item" data-button-index="${index}">
                <div class="buttons-editor__fields">
                    <div class="input-wrapper">
                        <div class="input__field-container">
                            <input type="text" class="input__field" name="button_${index}_label" placeholder="${escapeHtml(labelPlaceholder)}">
                        </div>
                    </div>
                    <div class="input-wrapper">
                        <div class="input__field-container">
                            <input type="text" class="input__field" name="button_${index}_url" placeholder="${escapeHtml(urlPlaceholder)}">
                        </div>
                    </div>
                </div>
                <button type="button" class="buttons-editor__remove" data-remove-button>✕</button>
            </div>`;
    }

    function reindexButtons(list) {
        const items = list.querySelectorAll('[data-button-index]');
        items.forEach((item, newIndex) => {
            const oldIndex = item.dataset.buttonIndex;
            item.dataset.buttonIndex = newIndex;

            item.querySelectorAll(`[name^="button_${oldIndex}_"]`).forEach(input => {
                input.name = input.name.replace(`button_${oldIndex}_`, `button_${newIndex}_`);
            });
        });
    }

    function checkEmptyState(list, emptyText) {
        const items = list.querySelectorAll('[data-button-index]');
        if (items.length === 0) {
            list.innerHTML = `<div class="buttons-editor__empty" data-buttons-empty>${escapeHtml(emptyText)}</div>`;
        }
    }

    /**
     * Variables Insert
     */
    function initVariablesInsert() {
        document.querySelectorAll('[data-variable]').forEach(btn => {
            btn.addEventListener('click', () => {
                const variable = btn.dataset.variable;
                insertVariable(`{${variable}}`);
            });
        });
    }

    function insertVariable(text) {
        const activeEl = document.activeElement;
        let target = null;

        if (activeEl && (activeEl.tagName === 'TEXTAREA' || activeEl.tagName === 'INPUT')) {
            target = activeEl;
        } else {
            target = document.querySelector('[name="content"]') || document.querySelector('[name="title"]');
        }

        if (target) {
            const start = target.selectionStart;
            const end = target.selectionEnd;
            const value = target.value;

            target.value = value.substring(0, start) + text + value.substring(end);
            target.selectionStart = target.selectionEnd = start + text.length;
            target.focus();
            target.dispatchEvent(new Event('input', { bubbles: true }));
        }
    }

    /**
     * Preview Updater
     */
    function initPreviewUpdater() {
        const titleInput = document.querySelector('[name="title"]');
        const contentInput = document.querySelector('[name="content"]');

        titleInput?.addEventListener('input', updatePreview);
        contentInput?.addEventListener('input', updatePreview);
    }

    function updatePreview() {
        const preview = document.querySelector('[data-preview]');
        const previewTitle = document.querySelector('[data-preview-title]');
        const previewContent = document.querySelector('[data-preview-content]');
        const previewButtons = document.querySelector('[data-preview-buttons]');

        const titleInput = document.querySelector('[name="title"]');
        const contentInput = document.querySelector('[name="content"]');

        // Get translations from preview data attributes
        const defaultTitle = preview?.dataset.defaultTitle || 'Title';
        const defaultContent = preview?.dataset.defaultContent || 'Content';
        const defaultButton = preview?.dataset.defaultButton || 'Button';

        if (previewTitle && titleInput) {
            previewTitle.textContent = titleInput.value || defaultTitle;
        }

        if (previewContent && contentInput) {
            previewContent.textContent = contentInput.value || defaultContent;
        }

        if (previewButtons) {
            const buttons = collectButtons();
            if (buttons.length > 0) {
                previewButtons.innerHTML = buttons.map(btn =>
                    `<span class="notification-preview-item__btn">${escapeHtml(btn.label || defaultButton)}</span>`
                ).join('');
                previewButtons.style.display = '';
            } else {
                previewButtons.innerHTML = '';
                previewButtons.style.display = 'none';
            }
        }
    }

    function collectButtons() {
        const buttons = [];
        let index = 0;

        while (true) {
            const labelInput = document.querySelector(`[name="button_${index}_label"]`);
            const urlInput = document.querySelector(`[name="button_${index}_url"]`);

            if (!labelInput && !urlInput) break;

            const label = labelInput?.value?.trim() || '';
            const url = urlInput?.value?.trim() || '';

            if (label || url) {
                buttons.push({ label, url });
            }
            index++;
        }

        return buttons;
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
})();
