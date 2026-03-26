document.addEventListener('DOMContentLoaded', function () {
    initTabs();
    initSeoPreview();
    initCharacterCounters();
    initErrorClearing();
    initInstallerLoading();
    initTooltipObserver();
    initAccordion();
    initExpandAnimation();
    initLangSwitchAnimation();
    initLaunchWizard();
    initModuleSearch();
});

htmx.on('htmx:afterSwap', function () {
    initTabs();
    initSeoPreview();
    initCharacterCounters();
    initErrorClearing();
    initAccordion();
    initExpandAnimation();
    initLaunchWizard();
    initModuleSearch();
});

function initTabs() {
    var tabButtons = document.querySelectorAll('.tab-minimal');

    if (!tabButtons.length) return;

    tabButtons.forEach(function (button) {
        if (button._tabInited) return;
        button._tabInited = true;

        button.addEventListener('click', function () {
            var tab = button.dataset.tab;
            var tabsContainer = button.closest('.tabs-minimal__nav');

            if (!tabsContainer) return;

            var siblingButtons = tabsContainer.querySelectorAll('.tab-minimal');
            var tabContents = document.querySelectorAll(
                '.tab-minimal-content[data-tab-content]',
            );

            siblingButtons.forEach(function (btn) { btn.classList.remove('active'); });
            tabContents.forEach(function (content) {
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
    var deviceIcons = document.querySelectorAll('.seo-device-icon');
    var previewContent = document.querySelector('.seo-preview-content');

    if (!deviceIcons.length || !previewContent) return;

    deviceIcons.forEach(function (icon) {
        if (icon._seoInited) return;
        icon._seoInited = true;

        icon.addEventListener('click', function () {
            var device = icon.dataset.device;
            deviceIcons.forEach(function (i) { i.classList.remove('active'); });
            icon.classList.add('active');
            if (device === 'mobile') {
                previewContent.classList.add('mobile');
            } else {
                previewContent.classList.remove('mobile');
            }
        });
    });

    var titleInput = document.getElementById('meta-title');
    var urlInput = document.getElementById('site-url');
    var descriptionInput = document.getElementById('meta-description');
    var previewTitle = document.getElementById('preview-title');
    var previewUrl = document.getElementById('preview-url');
    var previewDescription = document.getElementById('preview-description');

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
    var titleInput = document.getElementById('meta-title');
    var descriptionInput = document.getElementById('meta-description');
    var titleCounter = document.getElementById('title-counter');
    var descriptionCounter = document.getElementById('description-counter');

    if (titleInput && titleCounter) {
        updateCharacterCount(titleInput, titleCounter, 60);
        titleInput.addEventListener('input', function () {
            updateCharacterCount(titleInput, titleCounter, 60);
        });
    }
    if (descriptionInput && descriptionCounter) {
        updateCharacterCount(descriptionInput, descriptionCounter, 160);
        descriptionInput.addEventListener('input', function () {
            updateCharacterCount(descriptionInput, descriptionCounter, 160);
        });
    }
}

function updateCharacterCount(input, counter, limit) {
    var count = input.value.length;
    counter.textContent = count + '/' + limit;
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

function initErrorClearing() {
    document.querySelectorAll('.field__input, .installer-input__field, .installer-select__field').forEach(function (el) {
        if (el._errorClearInited) return;
        el._errorClearInited = true;

        el.addEventListener('input', function () {
            if (this.value.length > 0) {
                var field = this.closest('.field');
                if (field) {
                    field.classList.remove('has-error');
                    var errorEl = field.querySelector('.field__error');
                    if (errorEl) errorEl.remove();
                }
                var wrapper = this.closest('.input-wrapper, .select-wrapper');
                if (wrapper) {
                    wrapper.classList.remove('has-error');
                    var errorEl2 = wrapper.querySelector('.installer-input__error, .select__error');
                    if (errorEl2) errorEl2.style.display = 'none';
                }
                this.setAttribute('aria-invalid', 'false');
            }
        });
    });
}

// ── Loading states for ALL htmx buttons ────────────────────
function initInstallerLoading() {
    if (document._installerLoadingV2) return;
    document._installerLoadingV2 = true;

    document.body.addEventListener('htmx:beforeRequest', function (evt) {
        var el = evt.detail.elt;
        if (!el) return;

        var btn = el.closest('.btn');
        if (!btn) btn = el;
        if (btn.classList && btn.classList.contains('btn')) {
            btn.classList.add('btn--loading');
            btn.disabled = true;
            btn._wasLoading = true;
        }

        var form = el.closest('form');
        if (form) {
            var testBtn = form.querySelector('.test-btn');
            if (testBtn) {
                testBtn.classList.add('test-btn--loading');
                testBtn.disabled = true;
                testBtn._wasLoading = true;
            }
        }
    });

    document.body.addEventListener('htmx:afterRequest', function (evt) {
        var el = evt.detail.elt;
        if (!el) return;

        var btn = el.closest('.btn');
        if (!btn) btn = el;

        if (btn._wasLoading) {
            btn.classList.remove('btn--loading');
            btn.disabled = false;
            btn._wasLoading = false;
        }
    });

    document.body.addEventListener('htmx:beforeSend', function (evt) {
        var el = evt.detail.elt;
        if (!el) return;

        var form = el.closest('form');
        if (!form) return;

        var submitBtns = form.querySelectorAll('button[type="submit"]');
        submitBtns.forEach(function (btn) {
            btn.classList.add('btn--loading');
            btn.disabled = true;
            btn._wasLoading = true;
        });
    });

    document.body.addEventListener('htmx:afterOnLoad', function () {
        document.querySelectorAll('.btn--loading').forEach(function (btn) {
            btn.classList.remove('btn--loading');
            btn.disabled = false;
            btn._wasLoading = false;
        });
    });
}

// ── Tooltips via FloatingUIDOM ──────────────────────────────
var tooltipEl = null;
var activeTooltipTarget = null;
var tooltipCleanup = null;

function initTooltipObserver() {
    document.body.addEventListener('mouseover', function (event) {
        var target = event.target.closest('[data-tooltip]');
        if (target) showInstallerTooltip(target);
    });

    document.body.addEventListener('mouseout', function (event) {
        var target = event.target.closest('[data-tooltip]');
        if (target) hideInstallerTooltip();
    });
}

function showInstallerTooltip(target) {
    var text = target.getAttribute('data-tooltip');
    var placement = target.getAttribute('data-tooltip-placement') || 'top';

    if (!text) return;

    if (!tooltipEl) {
        tooltipEl = document.createElement('div');
        tooltipEl.className = 'installer-tooltip';
        document.body.appendChild(tooltipEl);
    }

    tooltipEl.textContent = text;
    tooltipEl.classList.add('show');
    activeTooltipTarget = target;

    if (!window.FloatingUIDOM) return;

    function updatePosition() {
        if (!activeTooltipTarget || !document.body.contains(activeTooltipTarget) || !tooltipEl) {
            hideInstallerTooltip();
            return;
        }

        window.FloatingUIDOM.computePosition(activeTooltipTarget, tooltipEl, {
            placement: placement,
            middleware: [
                window.FloatingUIDOM.offset(8),
                window.FloatingUIDOM.flip(),
                window.FloatingUIDOM.shift({ padding: 5 }),
            ],
        }).then(function (result) {
            if (!tooltipEl) return;
            tooltipEl.style.left = result.x + 'px';
            tooltipEl.style.top = result.y + 'px';
        });
    }

    updatePosition();

    if (tooltipCleanup) {
        tooltipCleanup();
        tooltipCleanup = null;
    }

    tooltipCleanup = window.FloatingUIDOM.autoUpdate(
        activeTooltipTarget,
        tooltipEl,
        updatePosition,
    );
}

function hideInstallerTooltip() {
    if (tooltipEl) {
        tooltipEl.classList.remove('show');
    }
    if (tooltipCleanup) {
        tooltipCleanup();
        tooltipCleanup = null;
    }
    activeTooltipTarget = null;
}

// ── Accordion open/close animation ───────────────────────
function initAccordion() {
    document.querySelectorAll('[data-accordion]').forEach(function (accordion) {
        if (accordion._accordionInited) return;
        accordion._accordionInited = true;

        var trigger = accordion.querySelector('[data-accordion-trigger]');
        var panel = accordion.querySelector('[data-accordion-panel]');
        if (!trigger || !panel) return;

        trigger.addEventListener('click', function () {
            var isOpen = accordion.classList.contains('is-open');

            if (isOpen) {
                // Closing: set explicit height first, then animate to 0
                panel.style.maxHeight = panel.scrollHeight + 'px';
                panel.offsetHeight; // force reflow
                panel.style.maxHeight = '0';
                panel.style.opacity = '0';
                accordion.classList.remove('is-open');
            } else {
                // Opening: animate from 0 to scrollHeight
                accordion.classList.add('is-open');
                panel.style.opacity = '1';
                var h = panel.scrollHeight;
                panel.style.maxHeight = h + 'px';

                // After transition ends, remove max-height so content can grow
                var onEnd = function () {
                    panel.removeEventListener('transitionend', onEnd);
                    if (accordion.classList.contains('is-open')) {
                        panel.style.maxHeight = 'none';
                    }
                };
                panel.addEventListener('transitionend', onEnd);
            }
        });
    });
}

// ── Smooth expand/collapse for checkDetails ──────────────
function initExpandAnimation() {
    document.querySelectorAll('.expand-btn').forEach(function (btn) {
        if (btn._expandAnimInited) return;
        btn._expandAnimInited = true;

        var targetId = 'checkDetails';
        var target = document.getElementById(targetId);
        if (!target) return;

        // Remove inline onclick and style
        btn.removeAttribute('onclick');
        target.style.display = '';
        target.style.overflow = 'hidden';
        target.style.maxHeight = '0';
        target.style.opacity = '0';
        target.style.transition = 'max-height 0.4s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.3s ease';

        var isOpen = false;

        btn.addEventListener('click', function () {
            var chevron = btn.querySelector('svg');
            if (!isOpen) {
                target.style.display = 'block';
                var h = target.scrollHeight;
                target.style.maxHeight = h + 'px';
                target.style.opacity = '1';
                if (chevron) chevron.classList.add('open');
                isOpen = true;
            } else {
                target.style.maxHeight = '0';
                target.style.opacity = '0';
                if (chevron) chevron.classList.remove('open');
                isOpen = false;
            }
        });
    });
}

// ── Language switch cross-fade animation ─────────────────
var _langSwitchActive = false;

function initLangSwitchAnimation() {
    if (document._langSwitchInited) return;
    document._langSwitchInited = true;

    // Fade out before the request is sent (gives time for transition)
    document.body.addEventListener('htmx:beforeRequest', function (evt) {
        var trigger = evt.detail.elt;
        if (!trigger || !trigger.classList.contains('lang-chip')) return;

        _langSwitchActive = true;
        var content = document.getElementById('welcome-content');
        if (content) {
            content.style.transition = 'opacity 0.18s ease, transform 0.18s ease';
            content.style.opacity = '0';
            content.style.transform = 'translateY(6px)';
        }
    });

    // Fade in after the new content has settled in the DOM
    document.body.addEventListener('htmx:afterSettle', function () {
        if (!_langSwitchActive) return;
        _langSwitchActive = false;

        var content = document.getElementById('welcome-content');
        if (!content) return;

        // Start hidden, shifted up
        content.style.transition = 'none';
        content.style.opacity = '0';
        content.style.transform = 'translateY(-8px)';
        content.offsetHeight; // force reflow

        // Animate in
        content.style.transition = 'opacity 0.32s ease, transform 0.32s cubic-bezier(0.16, 1, 0.3, 1)';
        content.style.opacity = '1';
        content.style.transform = 'translateY(0)';

        // Re-init accordion on new content
        initAccordion();
    });
}

// ── Launch wizard sub-pages ──────────────────────────────
function initLaunchWizard() {
    var container = document.querySelector('.launch-step');
    if (!container || container._launchWizardInited) return;
    container._launchWizardInited = true;

    var currentPage = 1;
    var totalPages = container.querySelectorAll('[data-launch-page]').length;

    function goToPage(target, reverse) {
        if (target < 1 || target > totalPages || target === currentPage) return;

        var currentEl = container.querySelector('[data-launch-page="' + currentPage + '"]');
        var targetEl = container.querySelector('[data-launch-page="' + target + '"]');
        if (!currentEl || !targetEl) return;

        var exitClass = reverse ? 'is-exiting-reverse' : 'is-exiting';
        var enterClass = reverse ? 'is-entering-reverse' : 'is-entering';

        currentEl.classList.add(exitClass);

        setTimeout(function () {
            currentEl.style.display = 'none';
            currentEl.classList.remove(exitClass);

            targetEl.style.display = '';
            targetEl.classList.add(enterClass);

            setTimeout(function () {
                targetEl.classList.remove(enterClass);
            }, 350);

            currentPage = target;

            var contentArea = container.closest('.installer-content');
            if (contentArea) contentArea.scrollTop = 0;
        }, 220);
    }

    container.addEventListener('click', function (e) {
        var nextBtn = e.target.closest('[data-launch-next]');
        if (nextBtn) {
            e.preventDefault();
            goToPage(currentPage + 1, false);
            return;
        }

        var prevBtn = e.target.closest('[data-launch-prev]');
        if (prevBtn) {
            e.preventDefault();
            goToPage(currentPage - 1, true);
            return;
        }
    });
}

// ── Module search (modules step) ─────────────────────────
function initModuleSearch() {
    var container = document.querySelector('.modules-step');
    if (!container) return;

    var searchInput = container.querySelector('[data-module-search]');
    if (!searchInput || searchInput._searchInited) return;
    searchInput._searchInited = true;

    var cards = container.querySelectorAll('.module-card[data-module-name]');
    var countEl = container.querySelector('[data-module-count]');
    var emptyEl = container.querySelector('[data-module-empty]');

    searchInput.addEventListener('input', function () {
        var query = this.value.trim().toLowerCase();
        var visible = 0;

        cards.forEach(function (card) {
            var name = card.getAttribute('data-module-name') || '';
            var desc = card.getAttribute('data-module-desc') || '';
            var matches = !query || name.indexOf(query) !== -1 || desc.indexOf(query) !== -1;

            if (matches) {
                card.classList.remove('is-hidden');
                visible++;
            } else {
                card.classList.add('is-hidden');
            }
        });

        if (countEl) countEl.textContent = visible;

        if (emptyEl) {
            emptyEl.style.display = (visible === 0 && query) ? '' : 'none';
        }
    });
}

window.addEventListener('delayed-redirect', function (event) {
    var url = event.detail.url;
    var delay = event.detail.delay;
    setTimeout(function () {
        if (typeof htmx !== 'undefined') {
            htmx.ajax('GET', url, {
                target: 'body',
                swap: 'morph',
                headers: { 'HX-Push-Url': url }
            }).catch(function () {
                window.location.href = url;
            });
        } else {
            window.location.href = url;
        }
    }, delay);
});
