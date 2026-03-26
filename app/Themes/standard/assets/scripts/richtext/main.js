window.FluteRichText = window.FluteRichText || {};

class FluteRichTextEditor {
    constructor() {
        this.instances = {};
        this.toolbarBuilder = new window.FluteRichText.ToolbarBuilder();
        this.modals = new window.FluteRichText.Modals();
        this.uploader = new window.FluteRichText.ImageUploader();
        window.FluteRichText._modals = this.modals;
        this._setupThemeObserver();
        this._setupEventListeners();
        this._setupEditorObserver();
    }

    isMarkdown(content) {
        if (!content || !content.trim()) return false;
        if (
            /<(?:p|div|h[1-6]|ul|ol|li|table|br|img|a|strong|em|blockquote|pre|hr)\b/i.test(
                content,
            )
        )
            return false;
        return /(?:^#{1,6}\s|^\*\s|^-\s|^\d+\.\s|\*\*|__|~~|!\[|\[.*\]\(|```)/m.test(
            content,
        );
    }

    initialize(target) {
        if (target === undefined) target = '[data-editor="richtext"]';
        var textareas;
        if (typeof target === 'string')
            textareas = document.querySelectorAll(target);
        else if (target instanceof NodeList || Array.isArray(target))
            textareas = target;
        else if (target instanceof Element) textareas = [target];
        else textareas = document.querySelectorAll('[data-editor="richtext"]');

        var self = this;
        var editorsToInit = Array.from(textareas).filter(function (textarea) {
            if (!textarea.id)
                textarea.id =
                    'editor-' + Math.random().toString(36).substring(2, 9);
            if (
                self.instances[textarea.id] &&
                self.instances[textarea.id].textarea !== textarea
            ) {
                self.destroyInstance(textarea.id);
            }
            if (
                textarea.parentElement &&
                textarea.parentElement.querySelector('.tiptap-editor')
            ) {
                if (!self.instances[textarea.id]) {
                    var stale =
                        textarea.parentElement.querySelector('.tiptap-editor');
                    if (stale) stale.remove();
                    textarea.style.display = '';
                } else {
                    return false;
                }
            }
            return !self.instances[textarea.id];
        });

        editorsToInit.forEach(function (t) {
            self._initEditor(t);
        });
    }

    _initEditor(textarea) {
        if (!textarea.id || this.instances[textarea.id]) return;

        var T = window.TipTapBundle;
        if (!T) {
            console.error('TipTapBundle not loaded');
            return;
        }

        var id = textarea.id;
        var height = parseInt(
            textarea.getAttribute('data-height') || '300',
            10,
        );
        var uploadEnabled = textarea.getAttribute('data-upload') === 'true';
        var uploadUrl =
            textarea.getAttribute('data-upload-url') ||
            '/admin/api/upload-image';
        var toolbarConfig = this.toolbarBuilder.getConfig(textarea);

        // Wrapper
        var wrapper = document.createElement('div');
        wrapper.className = 'tiptap-editor';
        textarea.parentNode.insertBefore(wrapper, textarea);
        textarea.style.display = 'none';

        // Toolbar
        var toolbarEl = this.toolbarBuilder.build(
            toolbarConfig,
            uploadEnabled,
            id,
        );
        wrapper.appendChild(toolbarEl);

        // Content area
        var editorEl = document.createElement('div');
        editorEl.className = 'tiptap-content-area';
        editorEl.style.minHeight = height + 'px';
        editorEl.style.maxHeight = height * 2 + 'px';
        editorEl.style.overflowY = 'auto';
        wrapper.appendChild(editorEl);

        // Markdown conversion
        var content = textarea.value || '';
        if (this.isMarkdown(content)) {
            try {
                content = T.marked.parse(content);
            } catch (e) {}
        }

        // Context for toolbar actions
        var self = this;
        var ctx = {
            modals: this.modals,
            uploader: this.uploader,
            toolbar: toolbarEl,
            uploadUrl: uploadUrl,
            toggleFullscreen: function () {
                self._toggleFullscreen(wrapper);
            },
        };

        // Extensions (no BubbleMenu — managed via FloatingUI in BubbleManager)
        var extensions = window.FluteRichText.createExtensions(textarea, T);

        // Create editor
        var editor = new T.Editor({
            element: editorEl,
            extensions: extensions,
            content: content,
            autofocus: textarea.hasAttribute('autofocus'),
            editable:
                !textarea.hasAttribute('readonly') &&
                !textarea.hasAttribute('disabled'),
            editorProps: {
                handleDOMEvents: {
                    click: function (view, event) {
                        var anchor = event.target.closest('a');
                        if (anchor) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                    },
                },
            },
            onUpdate: function (params) {
                textarea.value = params.editor.getHTML();
                self.toolbarBuilder.updateState(params.editor, toolbarEl);
            },
            onSelectionUpdate: function () {
                self.toolbarBuilder.updateState(editor, toolbarEl);
            },
            onFocus: function () {
                wrapper.classList.add('tiptap-focused');
            },
            onBlur: function () {
                wrapper.classList.remove('tiptap-focused');
            },
        });

        // Bubble menus (created after editor, positioned via FloatingUI)
        var bubble = new window.FluteRichText.BubbleManager();
        bubble.createElements(wrapper);
        bubble.bindEvents(editor, this.modals);

        // Notion-like table controls (+ buttons, grips, context menu)
        var tableControls = null;
        if (window.FluteRichText.TableControls) {
            tableControls = new window.FluteRichText.TableControls(editor, wrapper);
        }

        // Image upload handlers
        if (uploadEnabled) this.uploader.setup(editor, wrapper, uploadUrl);

        // Status bar
        var statusbar = document.createElement('div');
        statusbar.className = 'tiptap-statusbar';
        var _t = function(key, fallback) {
            var i18n = window.FluteRichText && window.FluteRichText.i18n;
            return (i18n && i18n[key]) || fallback || key;
        };
        var updateStatus = function () {
            var text = editor.getText();
            var words = text.trim() ? text.trim().split(/\s+/).length : 0;
            statusbar.textContent =
                words + ' ' + _t('words', 'words') + ' \u00B7 ' + text.length + ' ' + _t('chars', 'chars');
        };
        updateStatus();
        editor.on('update', updateStatus);
        wrapper.appendChild(statusbar);

        // Bind toolbar buttons
        var tb = this.toolbarBuilder;
        toolbarEl
            .querySelectorAll('button[data-action]')
            .forEach(function (btn) {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    tb.execAction(editor, btn.dataset.action, ctx);
                    setTimeout(function () {
                        tb.updateState(editor, toolbarEl);
                    }, 10);
                });
            });

        this.instances[id] = {
            editor: editor,
            wrapper: wrapper,
            toolbar: toolbarEl,
            statusbar: statusbar,
            textarea: textarea,
            uploadUrl: uploadUrl,
            uploadEnabled: uploadEnabled,
            bubble: bubble,
            tableControls: tableControls,
            ctx: ctx,
        };

        this.toolbarBuilder.updateState(editor, toolbarEl);
        textarea.value = editor.getHTML();
    }

    _toggleFullscreen(wrapper) {
        var icon = window.FluteRichText.icon;
        var isFs = wrapper.classList.toggle('tiptap-fullscreen');
        var btn = wrapper.querySelector('[data-action="fullscreen"]');
        if (btn)
            btn.innerHTML = icon(isFs ? 'exit-fullscreen' : 'fullscreen');
    }

    destroyInstance(id) {
        var inst = this.instances[id];
        if (!inst) return;
        try {
            inst.textarea.value = inst.editor.getHTML();
            if (inst.tableControls) inst.tableControls.destroy();
            inst.bubble.destroy();
            inst.editor.destroy();
            this.toolbarBuilder.cleanupDropdowns(id);
            if (inst.wrapper && inst.wrapper.parentNode) inst.wrapper.remove();
            inst.textarea.style.display = '';
        } catch (e) {}
        delete this.instances[id];
    }

    destroy(container) {
        try {
            if (container) {
                var el =
                    typeof container === 'string'
                        ? document.querySelector(container)
                        : container;
                if (el) {
                    var self = this;
                    el.querySelectorAll(
                        '[data-editor="richtext"]',
                    ).forEach(function (ta) {
                        if (ta.id && self.instances[ta.id])
                            self.destroyInstance(ta.id);
                    });
                }
            } else {
                var self2 = this;
                Object.keys(this.instances).forEach(function (id) {
                    self2.destroyInstance(id);
                });
            }
        } catch (e) {}
    }

    updateEditorsTheme() {}

    saveContent(container) {
        if (!container) return;
        var self = this;
        container
            .querySelectorAll('[data-editor="richtext"]')
            .forEach(function (ta) {
                if (ta.id && self.instances[ta.id]) {
                    try {
                        var html = self.instances[ta.id].editor.getHTML();
                        ta.value = html;
                        ta.setAttribute('data-editor-content', html);
                    } catch (e) {}
                }
            });
    }

    _setupThemeObserver() {
        var self = this;
        new MutationObserver(function (mutations) {
            for (var i = 0; i < mutations.length; i++) {
                if (
                    mutations[i].type === 'attributes' &&
                    mutations[i].attributeName === 'data-theme'
                ) {
                    self.updateEditorsTheme();
                    break;
                }
            }
        }).observe(document.documentElement, { attributes: true });
    }

    _setupEventListeners() {
        var self = this;

        document.addEventListener('DOMContentLoaded', function () {
            self.initialize();
        });

        document.addEventListener('htmx:configRequest', function (event) {
            var form = event.detail.elt.closest('form');
            if (form) {
                self.saveContent(form);
                form.querySelectorAll(
                    '[data-editor="richtext"]',
                ).forEach(function (ta) {
                    if (ta.name && ta.form)
                        event.detail.parameters[ta.name] = ta.value;
                });
            }
        });

        document.addEventListener('htmx:beforeSwap', function (event) {
            var container = event.detail.target;
            if (container) {
                container
                    .querySelectorAll('[data-editor="richtext"]')
                    .forEach(function (ta) {
                        if (ta.id && self.instances[ta.id]) {
                            try {
                                ta.setAttribute(
                                    'data-editor-content',
                                    self.instances[ta.id].editor.getHTML(),
                                );
                                self.destroyInstance(ta.id);
                            } catch (e) {}
                        }
                    });
            }
        });

        document.addEventListener('htmx:afterSwap', function (event) {
            var swapTarget = event.detail.target;
            if (
                swapTarget &&
                typeof swapTarget.querySelectorAll === 'function'
            ) {
                var editors = [];
                if (
                    swapTarget.matches &&
                    swapTarget.matches('[data-editor="richtext"]')
                )
                    editors.push(swapTarget);
                editors = editors.concat(
                    Array.from(
                        swapTarget.querySelectorAll(
                            '[data-editor="richtext"]',
                        ),
                    ),
                );
                editors = Array.from(new Set(editors));
                if (editors.length > 0) {
                    self.initialize(editors);
                    editors.forEach(function (ta) {
                        if (ta.id && self.instances[ta.id]) {
                            var saved = ta.getAttribute(
                                'data-editor-content',
                            );
                            if (saved) {
                                self.instances[
                                    ta.id
                                ].editor.commands.setContent(saved);
                                ta.removeAttribute('data-editor-content');
                            }
                        }
                    });
                }
            }
        });

        document.addEventListener('submit', function (event) {
            self.saveContent(event.target);
        });

        document.addEventListener('htmx:beforeRequest', function (event) {
            if (event.detail.elt.tagName === 'FORM')
                self.saveContent(event.detail.elt);
        });

        document.addEventListener('click', function (event) {
            var opener = event.target.closest('[data-modal-open]');
            if (!opener) return;
            var modal = document.getElementById(
                opener.getAttribute('data-modal-open'),
            );
            if (modal)
                setTimeout(function () {
                    var eds = modal.querySelectorAll(
                        '[data-editor="richtext"]',
                    );
                    if (eds.length) self.initialize(eds);
                }, 100);
        });

        var origShow = window.onModalShow || function () {};
        window.onModalShow = function (el) {
            origShow(el);
            setTimeout(function () {
                var e = el.querySelectorAll('[data-editor="richtext"]');
                if (e.length) self.initialize(e);
            }, 100);
        };

        var origHide = window.onModalHide || function () {};
        window.onModalHide = function (el) {
            self.saveContent(el);
            self.destroy(el);
            origHide(el);
        };
    }

    _setupEditorObserver() {
        var self = this;
        new MutationObserver(function (mutations) {
            mutations.forEach(function (m) {
                m.addedNodes.forEach(function (node) {
                    if (node.nodeType === Node.ELEMENT_NODE) {
                        var eds = [];
                        if (
                            node.matches &&
                            node.matches('[data-editor="richtext"]')
                        )
                            eds.push(node);
                        else if (typeof node.querySelectorAll === 'function')
                            eds = eds.concat(
                                Array.from(
                                    node.querySelectorAll(
                                        '[data-editor="richtext"]',
                                    ),
                                ),
                            );
                        if (eds.length)
                            self.initialize(Array.from(new Set(eds)));
                    }
                });
            });
        }).observe(document.body, { childList: true, subtree: true });
    }
}

window.fluteRichTextEditor = new FluteRichTextEditor();
