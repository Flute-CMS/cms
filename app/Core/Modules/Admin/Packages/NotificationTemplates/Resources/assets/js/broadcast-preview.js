(function () {
    'use strict';

    var iconCache = {};

    function init() {
        const preview = document.querySelector('[data-broadcast-preview]');
        if (!preview || preview.dataset.initialized) return;
        preview.dataset.initialized = 'true';

        const titleInput = document.querySelector('[name="title"]');
        const contentInput = document.querySelector('[name="content"]');
        const urlInput = document.querySelector('[name="url"]');
        const iconInput = document.querySelector('[name="icon"]');

        const previewTitle = document.querySelector('[data-broadcast-preview-title]');
        const previewContent = document.querySelector('[data-broadcast-preview-content]');
        const previewUrl = document.querySelector('[data-broadcast-preview-url]');
        const previewUrlText = document.querySelector('[data-broadcast-preview-url-text]');
        const previewIcon = document.querySelector('[data-broadcast-preview-icon]');

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

        function updateIcon() {
            if (!previewIcon || !iconInput) return;

            var path = iconInput.value.trim() || 'ph.bold.bell-bold';

            if (iconCache[path]) {
                previewIcon.innerHTML = iconCache[path];
                return;
            }

            fetch(u('admin/api/icons/render') + '?path=' + encodeURIComponent(path), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(function(r) { return r.ok ? r.text() : null; })
                .then(function(svg) {
                    if (svg && previewIcon) {
                        iconCache[path] = svg;
                        previewIcon.innerHTML = svg;
                    }
                })
                .catch(function() {});
        }

        if (titleInput) titleInput.addEventListener('input', update);
        if (contentInput) contentInput.addEventListener('input', update);
        if (urlInput) urlInput.addEventListener('input', update);

        if (iconInput) {
            iconInput.addEventListener('input', updateIcon);
            iconInput.addEventListener('change', updateIcon);
        }

        update();
        updateIcon();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    document.body.addEventListener('htmx:afterSettle', init);
    document.body.addEventListener('htmx:afterSwap', init);
})();
