(function () {
    'use strict';

    function init() {
        const preview = document.querySelector('[data-broadcast-preview]');
        if (!preview || preview.dataset.initialized) return;
        preview.dataset.initialized = 'true';

        const titleInput = document.querySelector('[name="title"]');
        const contentInput = document.querySelector('[name="content"]');
        const urlInput = document.querySelector('[name="url"]');

        const previewTitle = document.querySelector('[data-broadcast-preview-title]');
        const previewContent = document.querySelector('[data-broadcast-preview-content]');
        const previewUrl = document.querySelector('[data-broadcast-preview-url]');
        const previewUrlText = document.querySelector('[data-broadcast-preview-url-text]');

        const defaultTitle = preview.dataset.defaultTitle || 'Title';
        const defaultContent = preview.dataset.defaultContent || 'Content';

        function update() {
            if (previewTitle && titleInput) {
                previewTitle.textContent = titleInput.value || defaultTitle;
            }

            if (previewContent && contentInput) {
                previewContent.textContent = contentInput.value || defaultContent;
            }

            if (previewUrl && previewUrlText && urlInput) {
                var url = urlInput.value.trim();
                if (url) {
                    previewUrl.style.display = '';
                    previewUrlText.textContent = url;
                } else {
                    previewUrl.style.display = 'none';
                }
            }
        }

        if (titleInput) titleInput.addEventListener('input', update);
        if (contentInput) contentInput.addEventListener('input', update);
        if (urlInput) urlInput.addEventListener('input', update);

        update();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    document.body.addEventListener('htmx:afterSettle', init);
    document.body.addEventListener('htmx:afterSwap', init);
})();
