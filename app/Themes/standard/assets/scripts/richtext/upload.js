window.FluteRichText = window.FluteRichText || {};

window.FluteRichText.ImageUploader = class {
    setup(editor, wrapper, uploadUrl) {
        editor.view.dom.addEventListener('paste', (e) => {
            if (e.clipboardData?.items) {
                for (const item of e.clipboardData.items) {
                    if (item.type.startsWith('image/')) {
                        const file = item.getAsFile();
                        if (file) {
                            e.preventDefault();
                            this.upload(file, editor, uploadUrl);
                        }
                        return;
                    }
                }
            }
        });

        editor.view.dom.addEventListener('drop', (e) => {
            const file = e.dataTransfer?.files[0];
            if (file?.type.startsWith('image/')) {
                e.preventDefault();
                this.upload(file, editor, uploadUrl);
            }
        });
    }

    trigger(editor, toolbar, uploadUrl) {
        const input = toolbar.querySelector('.tiptap-file-input');
        if (!input) {
            const url = prompt('Image URL:');
            if (url?.trim())
                editor
                    .chain()
                    .focus()
                    .setImage({ src: url.trim() })
                    .run();
            return;
        }
        input.onchange = (e) => {
            const file = e.target.files[0];
            if (file) this.upload(file, editor, uploadUrl);
            input.value = '';
        };
        input.click();
    }

    upload(file, editor, uploadUrl) {
        const wrapper = editor.view.dom.closest('.tiptap-editor');
        const overlay = document.createElement('div');
        overlay.className = 'tiptap-upload-overlay';
        overlay.innerHTML =
            '<div class="upload-spinner"></div><span>Uploading...</span>';
        wrapper?.appendChild(overlay);

        const form = new FormData();
        form.append('image', file);
        const csrf = document.querySelector(
            'meta[name="csrf-token"]',
        )?.content;
        if (csrf) form.append('flute_csrf_token', csrf);

        fetch(uploadUrl, {
            method: 'POST',
            body: form,
            credentials: 'same-origin',
        })
            .then((r) => r.json())
            .then((data) => {
                overlay?.remove();
                if (data.success && data.url) {
                    editor
                        .chain()
                        .focus()
                        .setImage({ src: data.url, alt: file.name })
                        .run();
                }
            })
            .catch(() => overlay?.remove());
    }
};
