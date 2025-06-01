htmx.on('htmx:afterSwap', function () {
    initTabs();
    initSeoPreview();
    initCharacterCounters();
});

document.addEventListener('DOMContentLoaded', function () {
    initTabs();
    initSeoPreview();
    initCharacterCounters();
});

function initTabs() {
    const tabButtons = document.querySelectorAll('.tab-minimal');

    if (!tabButtons.length) return;

    tabButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const tab = button.dataset.tab;
            const tabsContainer = button.closest('.tabs-minimal__nav');

            if (!tabsContainer) return;

            const siblingButtons =
                tabsContainer.querySelectorAll('.tab-minimal');
            const tabContents = document.querySelectorAll(
                '.tab-minimal-content[data-tab-content]',
            );

            siblingButtons.forEach((btn) => btn.classList.remove('active'));
            tabContents.forEach((content) => {
                if (content.getAttribute('data-tab-content') === tab) {
                    content.classList.add('active');
                } else {
                    content.classList.remove('active');
                }
            });

            button.classList.add('active');
        });
    });
}

function initSeoPreview() {
    // Device switcher
    const deviceIcons = document.querySelectorAll('.seo-device-icon');
    const previewContent = document.querySelector('.seo-preview-content');

    if (!deviceIcons.length || !previewContent) return;

    deviceIcons.forEach((icon) => {
        icon.addEventListener('click', () => {
            const device = icon.dataset.device;

            deviceIcons.forEach((i) => i.classList.remove('active'));
            icon.classList.add('active');

            if (device === 'mobile') {
                previewContent.classList.add('mobile');
            } else {
                previewContent.classList.remove('mobile');
            }
        });
    });

    // Live preview updates
    const titleInput = document.getElementById('meta-title');
    const urlInput = document.getElementById('site-url');
    const descriptionInput = document.getElementById('meta-description');

    const previewTitle = document.getElementById('preview-title');
    const previewUrl = document.getElementById('preview-url');
    const previewDescription = document.getElementById('preview-description');

    if (titleInput && previewTitle) {
        updatePreviewTitle();
        titleInput.addEventListener('input', updatePreviewTitle);
    }

    if (descriptionInput && previewDescription) {
        updatePreviewDescription();
        descriptionInput.addEventListener('input', updatePreviewDescription);
    }

    if (urlInput && previewUrl) {
        updatePreviewUrl();
        urlInput.addEventListener('input', updatePreviewUrl);
    }

    function updatePreviewTitle() {
        previewTitle.textContent = titleInput.value || 'Your Website Title';
    }

    function updatePreviewDescription() {
        previewDescription.textContent = descriptionInput.value ||
            'Your website description will appear here. Make it compelling to attract visitors.';
    }

    function updatePreviewUrl() {
        previewUrl.textContent = urlInput.value || 'https://yourwebsite.com';
    }
}

function initCharacterCounters() {
    const titleInput = document.getElementById('meta-title');
    const descriptionInput = document.getElementById('meta-description');
    const titleCounter = document.getElementById('title-counter');
    const descriptionCounter = document.getElementById('description-counter');

    if (titleInput && titleCounter) {
        updateCharacterCount(titleInput, titleCounter, 60);
        titleInput.addEventListener('input', () => {
            updateCharacterCount(titleInput, titleCounter, 60);
        });
    }

    if (descriptionInput && descriptionCounter) {
        updateCharacterCount(descriptionInput, descriptionCounter, 160);
        descriptionInput.addEventListener('input', () => {
            updateCharacterCount(descriptionInput, descriptionCounter, 160);
        });
    }
}

function updateCharacterCount(input, counter, limit) {
    const count = input.value.length;
    counter.textContent = `${count}/${limit}`;

    if (count > limit) {
        counter.classList.add('error');
        counter.classList.remove('warning');
    } else if (count > limit * 0.8) {
        counter.classList.add('warning');
        counter.classList.remove('error');
    } else {
        counter.classList.remove('warning', 'error');
    }
}

$(document).ready(function () {
    $(document).on('input', 'input', function () {
        const errorElement = $(this)
            .parent()
            .parent()
            .find('.installer-input__error');

        if ($(this).val().length > 0) {
            setTimeout(() => {
                $(this).closest('.input-wrapper').removeClass('has-error');
                $(this).attr('aria-invalid', 'false');
                errorElement.hide();
            }, 400);
        }
    });
});

window.addEventListener('delayed-redirect', function (event) {
    const { url, delay } = event.detail;
    setTimeout(() => {
        window.location.href = url;
    }, delay);
});
