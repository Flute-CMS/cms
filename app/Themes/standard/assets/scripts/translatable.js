/**
 * Translatable Input Component
 *
 * Wraps regular <input> / <textarea> / TipTap editors with language tabs.
 * When only one language is available — does nothing (field stays plain).
 *
 * Usage:
 *   <div class="translatable-wrap"
 *        data-translatable-languages='[{"code":"ru","name":"Русский","flag":"/assets/img/langs/ru.svg"},...]'
 *        data-translatable-default="ru">
 *       <input type="text" name="title" data-translatable-input value='{"ru":"Главная","en":"Home"}'>
 *   </div>
 *
 * The component:
 *   1. Parses the JSON value (or treats a plain string as default-locale value)
 *   2. Creates language tabs with flags + tooltips
 *   3. On tab switch — swaps the visible input value
 *   4. On any input change — updates a hidden field with the full JSON
 */
(function () {
    'use strict';

    function initTranslatable(root) {
        var wraps = (root || document).querySelectorAll('.translatable-wrap:not([data-translatable-ready])');
        wraps.forEach(function (wrap) {
            setupWrap(wrap);
        });
    }

    function setupWrap(wrap) {
        wrap.setAttribute('data-translatable-ready', '1');

        var langs;
        try {
            langs = JSON.parse(wrap.getAttribute('data-translatable-languages'));
        } catch (e) {
            return;
        }
        if (!langs || langs.length < 2) return;

        var defaultLang = wrap.getAttribute('data-translatable-default') || langs[0].code;
        var input = wrap.querySelector('[data-translatable-input]');
        if (!input) return;

        var isTextarea = input.tagName === 'TEXTAREA';
        var isRichtext = input.hasAttribute('data-editor');
        var originalName = input.getAttribute('data-translatable-name') || input.getAttribute('name');

        // Parse initial value
        var values = parseValue(input.value || (isTextarea ? input.textContent : ''), defaultLang);

        // Create hidden field to hold JSON
        var hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = originalName;
        hidden.value = JSON.stringify(values);
        wrap.appendChild(hidden);

        // Remove name from visible input so it doesn't submit twice
        input.removeAttribute('name');

        // Build tabs
        var tabsBar = document.createElement('div');
        tabsBar.className = 'translatable-tabs';

        var activeLang = defaultLang;

        langs.forEach(function (lang) {
            var tab = document.createElement('button');
            tab.type = 'button';
            tab.className = 'translatable-tab' + (lang.code === activeLang ? ' is-active' : '');
            tab.setAttribute('data-lang', lang.code);
            tab.setAttribute('data-tooltip', lang.name);
            tab.setAttribute('data-tooltip-placement', 'top');

            var img = document.createElement('img');
            img.src = lang.flag;
            img.alt = lang.name;
            img.width = 18;
            img.height = 18;
            img.loading = 'lazy';
            tab.appendChild(img);

            // Dot indicator for filled languages
            var dot = document.createElement('span');
            dot.className = 'translatable-dot';
            if (values[lang.code]) dot.classList.add('has-value');
            tab.appendChild(dot);

            tab.addEventListener('click', function () {
                switchLang(lang.code);
            });

            tabsBar.appendChild(tab);
        });

        wrap.insertBefore(tabsBar, wrap.firstChild);

        // Set initial value in the visible input
        setInputValue(input, values[activeLang] || '', isRichtext);

        // Listen to input changes
        if (!isRichtext) {
            input.addEventListener('input', function () {
                values[activeLang] = input.value;
                syncHidden();
                updateDots();
            });
        }

        function switchLang(code) {
            // Save current
            if (isRichtext) {
                var inst = getRichtextInstance(input);
                if (inst) {
                    values[activeLang] = inst.editor.getHTML();
                }
            } else {
                values[activeLang] = input.value;
            }

            activeLang = code;

            // Update tabs
            tabsBar.querySelectorAll('.translatable-tab').forEach(function (t) {
                t.classList.toggle('is-active', t.getAttribute('data-lang') === code);
            });

            // Load new value
            setInputValue(input, values[activeLang] || '', isRichtext);
            syncHidden();
            updateDots();
        }

        function syncHidden() {
            // Clean empty values
            var clean = {};
            Object.keys(values).forEach(function (k) {
                var v = values[k];
                if (v && v !== '<p></p>' && v.trim()) {
                    clean[k] = v;
                }
            });
            hidden.value = Object.keys(clean).length > 0 ? JSON.stringify(clean) : '';
        }

        function updateDots() {
            tabsBar.querySelectorAll('.translatable-tab').forEach(function (tab) {
                var code = tab.getAttribute('data-lang');
                var dot = tab.querySelector('.translatable-dot');
                if (dot) {
                    var v = values[code];
                    dot.classList.toggle('has-value', !!(v && v.trim() && v !== '<p></p>'));
                }
            });
        }

        // For richtext: listen to editor updates
        if (isRichtext) {
            var checkEditorInterval = setInterval(function () {
                var inst = getRichtextInstance(input);
                if (inst) {
                    clearInterval(checkEditorInterval);
                    var origOnUpdate = inst.editor.options.onUpdate;
                    inst.editor.on('update', function (params) {
                        values[activeLang] = params.editor.getHTML();
                        syncHidden();
                        updateDots();
                    });
                }
            }, 200);

            // Clear interval after 10s to prevent leaks
            setTimeout(function () { clearInterval(checkEditorInterval); }, 10000);
        }

        // Expose API on the wrap element
        wrap._translatable = {
            getValues: function () { return values; },
            setValues: function (v) {
                values = v;
                setInputValue(input, values[activeLang] || '', isRichtext);
                syncHidden();
                updateDots();
            },
            getCurrentLang: function () { return activeLang; },
        };

        // Before form submit via HTMX — make sure current editor content is saved
        var form = input.closest('form');
        if (form) {
            // Store handler on the wrap so we can remove it if re-initialized
            if (wrap._htmxHandler) {
                form.removeEventListener('htmx:configRequest', wrap._htmxHandler);
            }
            wrap._htmxHandler = function (event) {
                if (isRichtext) {
                    var inst = getRichtextInstance(input);
                    if (inst) {
                        values[activeLang] = inst.editor.getHTML();
                        syncHidden();
                    }
                }
                event.detail.parameters[originalName] = hidden.value;
            };
            form.addEventListener('htmx:configRequest', wrap._htmxHandler);
        }
    }

    function parseValue(raw, defaultLang) {
        if (!raw || !raw.trim()) return {};

        // Try JSON
        if (raw[0] === '{') {
            try {
                var parsed = JSON.parse(raw);
                if (typeof parsed === 'object' && parsed !== null && !Array.isArray(parsed)) {
                    return parsed;
                }
            } catch (e) {}
        }

        // Plain string → assign to default lang
        var result = {};
        result[defaultLang] = raw;
        return result;
    }

    function setInputValue(input, value, isRichtext) {
        if (isRichtext) {
            var inst = getRichtextInstance(input);
            if (inst) {
                inst.editor.commands.setContent(value || '');
            } else {
                // Editor not ready yet, set textarea value
                input.value = value || '';
            }
        } else {
            input.value = value || '';
        }
    }

    function getRichtextInstance(textarea) {
        if (!textarea.id || !window.fluteRichTextEditor) return null;
        return window.fluteRichTextEditor.instances[textarea.id] || null;
    }

    // Init on page load
    document.addEventListener('DOMContentLoaded', function () {
        initTranslatable();
    });

    // Re-init after HTMX swaps
    document.addEventListener('htmx:afterSwap', function (event) {
        initTranslatable(event.detail.target);
    });

    // Re-init in modals
    var origModalShow = window.onModalShow;
    window.onModalShow = function (el) {
        if (typeof origModalShow === 'function') origModalShow(el);
        setTimeout(function () {
            initTranslatable(el);
        }, 150);
    };

    // MutationObserver for dynamic content
    new MutationObserver(function (mutations) {
        var hasNew = false;
        mutations.forEach(function (m) {
            m.addedNodes.forEach(function (node) {
                if (node.nodeType === Node.ELEMENT_NODE) {
                    if (node.querySelector && node.querySelector('.translatable-wrap:not([data-translatable-ready])')) {
                        hasNew = true;
                    }
                    if (node.classList && node.classList.contains('translatable-wrap') && !node.hasAttribute('data-translatable-ready')) {
                        hasNew = true;
                    }
                }
            });
        });
        if (hasNew) initTranslatable();
    }).observe(document.body, { childList: true, subtree: true });

    window.initTranslatable = initTranslatable;
})();
