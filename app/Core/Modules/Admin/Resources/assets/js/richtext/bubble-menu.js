window.FluteRichText = window.FluteRichText || {};

window.FluteRichText.BubbleManager = class {
    constructor() {
        this.elements = {};
        this._cleanups = {};
        this._visible = {};
        this._wrapper = null;
        this._editor = null;
        this._modals = null;
    }

    _t(key, fallback) {
        var i18n = window.FluteRichText && window.FluteRichText.i18n;
        return (i18n && i18n[key]) || fallback || key;
    }

    createElements(wrapper) {
        var icon = window.FluteRichText.icon;
        this._wrapper = wrapper;

        this.elements.text = this._createTextBubble(wrapper, icon);
        this.elements.link = this._createLinkBubble(wrapper, icon);
        this.elements.image = this._createImageBubble(wrapper, icon);

        return this.elements;
    }

    bindEvents(editor, modals) {
        this._editor = editor;
        this._modals = modals;

        this._bindTextEvents(editor, modals);
        this._bindLinkEvents(editor, modals);
        this._bindImageEvents(editor, modals);

        // Prevent selection loss on mousedown for all bubble menus
        var self = this;
        Object.keys(this.elements).forEach(function (key) {
            var el = self.elements[key];
            if (el) el.addEventListener('mousedown', function (e) { e.preventDefault(); });
        });

        var update = function () { self._updateBubbles(); };
        // Delayed update ensures DOM is fully synced (NodeView classes applied)
        var delayedUpdate = function () {
            update();
            requestAnimationFrame(update);
        };
        editor.on('selectionUpdate', delayedUpdate);
        editor.on('transaction', delayedUpdate);
    }

    destroy() {
        var self = this;
        Object.keys(this._cleanups).forEach(function (key) {
            if (typeof self._cleanups[key] === 'function') self._cleanups[key]();
        });
        this._cleanups = {};
        Object.keys(this.elements).forEach(function (key) {
            var el = self.elements[key];
            if (el && el.parentNode) el.remove();
        });
        this.elements = {};
        this._visible = {};
    }

    _el(className) {
        var el = document.createElement('div');
        el.className = 'tiptap-bubble-menu ' + className;
        el.style.display = 'none';
        return el;
    }

    // ========= TEXT SELECTION BUBBLE =========
    _createTextBubble(wrapper, icon) {
        var t = this._t.bind(this);
        var bubble = this._el('tiptap-bubble-text');
        bubble.innerHTML =
            '<div class="bubble-heading-dropdown">' +
                '<button type="button" class="bubble-btn bubble-heading-toggle" data-tooltip="' + t('heading', 'Heading') + '">' +
                    '<span class="bubble-heading-label">' + t('paragraph', 'P') + '</span>' +
                    '<span class="bubble-heading-chevron">' + icon('chevron-down') + '</span>' +
                '</button>' +
                '<div class="bubble-heading-menu">' +
                    '<button type="button" class="bubble-heading-item" data-heading="p">' + icon('paragraph') + ' ' + t('paragraph', 'Paragraph') + '</button>' +
                    '<button type="button" class="bubble-heading-item" data-heading="1">' + icon('heading-1') + ' ' + t('heading_1', 'Heading 1') + '</button>' +
                    '<button type="button" class="bubble-heading-item" data-heading="2">' + icon('heading-2') + ' ' + t('heading_2', 'Heading 2') + '</button>' +
                    '<button type="button" class="bubble-heading-item" data-heading="3">' + icon('heading-3') + ' ' + t('heading_3', 'Heading 3') + '</button>' +
                '</div>' +
            '</div>' +
            '<span class="bubble-sep"></span>' +
            '<button type="button" class="bubble-btn" data-cmd="bold" data-tooltip="' + t('bold', 'Bold') + '">' + icon('bold') + '</button>' +
            '<button type="button" class="bubble-btn" data-cmd="italic" data-tooltip="' + t('italic', 'Italic') + '">' + icon('italic') + '</button>' +
            '<button type="button" class="bubble-btn" data-cmd="underline" data-tooltip="' + t('underline', 'Underline') + '">' + icon('underline') + '</button>' +
            '<button type="button" class="bubble-btn" data-cmd="strike" data-tooltip="' + t('strikethrough', 'Strikethrough') + '">' + icon('strikethrough') + '</button>' +
            '<span class="bubble-sep"></span>' +
            '<button type="button" class="bubble-btn" data-cmd="highlight" data-tooltip="' + t('highlight', 'Highlight') + '">' + icon('highlight') + '</button>' +
            '<button type="button" class="bubble-btn" data-cmd="code" data-tooltip="' + t('inline_code', 'Code') + '">' + icon('inline-code') + '</button>' +
            '<button type="button" class="bubble-btn" data-cmd="link" data-tooltip="' + t('link', 'Link') + '">' + icon('link') + '</button>' +
            '<span class="bubble-sep"></span>' +
            '<button type="button" class="bubble-btn" data-cmd="align-left" data-tooltip="' + t('align_left', 'Left') + '">' + icon('align-left') + '</button>' +
            '<button type="button" class="bubble-btn" data-cmd="align-center" data-tooltip="' + t('align_center', 'Center') + '">' + icon('align-center') + '</button>' +
            '<button type="button" class="bubble-btn" data-cmd="align-right" data-tooltip="' + t('align_right', 'Right') + '">' + icon('align-right') + '</button>';
        wrapper.appendChild(bubble);
        return bubble;
    }

    _bindTextEvents(editor, modals) {
        var bubble = this.elements.text;
        if (!bubble) return;

        // Heading dropdown
        var headingToggle = bubble.querySelector('.bubble-heading-toggle');
        var headingMenu = bubble.querySelector('.bubble-heading-menu');
        if (headingToggle && headingMenu) {
            headingToggle.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                headingMenu.classList.toggle('is-open');
            });
            headingMenu.querySelectorAll('[data-heading]').forEach(function (item) {
                item.addEventListener('click', function (e) {
                    e.preventDefault();
                    var h = item.dataset.heading;
                    if (h === 'p') {
                        editor.chain().focus().setParagraph().run();
                    } else {
                        editor.chain().focus().toggleHeading({ level: parseInt(h) }).run();
                    }
                    headingMenu.classList.remove('is-open');
                });
            });
            // Close dropdown when clicking outside
            document.addEventListener('click', function (e) {
                if (!headingToggle.contains(e.target) && !headingMenu.contains(e.target)) {
                    headingMenu.classList.remove('is-open');
                }
            });
        }

        bubble.querySelectorAll('[data-cmd]').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                var cmd = btn.dataset.cmd;
                switch (cmd) {
                    case 'bold': editor.chain().focus().toggleBold().run(); break;
                    case 'italic': editor.chain().focus().toggleItalic().run(); break;
                    case 'underline': editor.chain().focus().toggleUnderline().run(); break;
                    case 'strike': editor.chain().focus().toggleStrike().run(); break;
                    case 'highlight': editor.chain().focus().toggleHighlight().run(); break;
                    case 'code': editor.chain().focus().toggleCode().run(); break;
                    case 'link': modals.showLink(editor); break;
                    case 'align-left': editor.chain().focus().setTextAlign('left').run(); break;
                    case 'align-center': editor.chain().focus().setTextAlign('center').run(); break;
                    case 'align-right': editor.chain().focus().setTextAlign('right').run(); break;
                }
            });
        });
    }

    _updateTextState() {
        var editor = this._editor;
        var bubble = this.elements.text;
        if (!bubble || !editor) return;

        // Update heading dropdown label
        var label = bubble.querySelector('.bubble-heading-label');
        if (label) {
            var t = this._t.bind(this);
            if (editor.isActive('heading', { level: 1 })) label.textContent = 'H1';
            else if (editor.isActive('heading', { level: 2 })) label.textContent = 'H2';
            else if (editor.isActive('heading', { level: 3 })) label.textContent = 'H3';
            else label.textContent = t('paragraph', 'P');
        }
        // Update heading dropdown active item
        var headingMenu = bubble.querySelector('.bubble-heading-menu');
        if (headingMenu) {
            headingMenu.querySelectorAll('[data-heading]').forEach(function (item) {
                var h = item.dataset.heading;
                var active = h === 'p' ? !editor.isActive('heading') : editor.isActive('heading', { level: parseInt(h) });
                item.classList.toggle('is-active', active);
            });
        }

        var states = {
            'bold': editor.isActive('bold'),
            'italic': editor.isActive('italic'),
            'underline': editor.isActive('underline'),
            'strike': editor.isActive('strike'),
            'highlight': editor.isActive('highlight'),
            'code': editor.isActive('code'),
            'link': editor.isActive('link'),
            'align-left': editor.isActive({ textAlign: 'left' }),
            'align-center': editor.isActive({ textAlign: 'center' }),
            'align-right': editor.isActive({ textAlign: 'right' }),
        };

        bubble.querySelectorAll('[data-cmd]').forEach(function (btn) {
            btn.classList.toggle('is-active', !!states[btn.dataset.cmd]);
        });
    }

    // ========= LINK BUBBLE =========
    _createLinkBubble(wrapper, icon) {
        var t = this._t.bind(this);
        var bubble = this._el('tiptap-bubble-link');
        bubble.innerHTML =
            '<span class="bubble-link-url" data-bubble-href></span>' +
            '<button type="button" class="bubble-btn" data-bubble="edit" data-tooltip="' + t('edit', 'Edit') + '">' + icon('pencil') + '</button>' +
            '<button type="button" class="bubble-btn" data-bubble="open" data-tooltip="' + t('open', 'Open') + '">' + icon('external-link') + '</button>' +
            '<button type="button" class="bubble-btn bubble-btn-danger" data-bubble="unlink" data-tooltip="' + t('remove', 'Remove') + '">' + icon('unlink') + '</button>';
        wrapper.appendChild(bubble);
        return bubble;
    }

    _bindLinkEvents(editor, modals) {
        var bubble = this.elements.link;
        if (!bubble) return;

        var urlEl = bubble.querySelector('[data-bubble-href]');
        if (urlEl) {
            urlEl.style.cursor = 'pointer';
            urlEl.addEventListener('click', function () {
                var href = urlEl.dataset.href;
                if (href) window.open(href, '_blank');
            });
        }

        bubble.querySelector('[data-bubble="edit"]').addEventListener('click', function () {
            if (modals) modals.showLink(editor);
        });
        bubble.querySelector('[data-bubble="open"]').addEventListener('click', function () {
            var attrs = editor.getAttributes('link');
            if (attrs.href) window.open(attrs.href, '_blank');
        });
        bubble.querySelector('[data-bubble="unlink"]').addEventListener('click', function () {
            editor.chain().focus().unsetLink().run();
        });
    }

    _updateLinkDisplay() {
        var editor = this._editor;
        var bubble = this.elements.link;
        if (!bubble || !editor) return;

        var urlEl = bubble.querySelector('[data-bubble-href]');
        if (!urlEl) return;
        var attrs = editor.getAttributes('link');
        var href = attrs.href || '';
        urlEl.textContent = href.length > 40 ? href.substring(0, 40) + '\u2026' : href;
        urlEl.setAttribute('data-tooltip', href);
        urlEl.dataset.href = href;
    }

    // ========= IMAGE BUBBLE =========
    _createImageBubble(wrapper, icon) {
        var t = this._t.bind(this);
        var bubble = this._el('tiptap-bubble-image');
        bubble.innerHTML =
            '<button type="button" class="bubble-btn" data-size="small" data-tooltip="' + t('small', 'Small') + '">S</button>' +
            '<button type="button" class="bubble-btn" data-size="medium" data-tooltip="' + t('medium', 'Medium') + '">M</button>' +
            '<button type="button" class="bubble-btn" data-size="full" data-tooltip="' + t('full_width', 'Full width') + '">F</button>' +
            '<span class="bubble-sep"></span>' +
            '<button type="button" class="bubble-btn" data-img-align="left" data-tooltip="' + t('float_left', 'Float left') + '">' + icon('align-left') + '</button>' +
            '<button type="button" class="bubble-btn" data-img-align="center" data-tooltip="' + t('center', 'Center') + '">' + icon('align-center') + '</button>' +
            '<button type="button" class="bubble-btn" data-img-align="right" data-tooltip="' + t('float_right', 'Float right') + '">' + icon('align-right') + '</button>' +
            '<span class="bubble-sep"></span>' +
            '<button type="button" class="bubble-btn" data-bubble="alt" data-tooltip="' + t('alt_text', 'Alt text') + '">' + icon('pencil') + '</button>' +
            '<button type="button" class="bubble-btn bubble-btn-danger" data-bubble="delete" data-tooltip="' + t('delete', 'Delete') + '">' + icon('trash') + '</button>';
        wrapper.appendChild(bubble);
        return bubble;
    }

    _bindImageEvents(editor, modals) {
        var bubble = this.elements.image;
        var self = this;
        if (!bubble) return;

        // Size buttons: direct transaction to avoid focus() losing NodeSelection
        bubble.querySelectorAll('[data-size]').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                try {
                    var ed = self._editor;
                    if (!ed) return;
                    var sel = ed.state.selection;
                    var isNodeSel = sel.node && sel.node.type.name === 'image';
                    if (!isNodeSel && !ed.isActive('image')) return;

                    var ratios = { small: 0.33, medium: 0.5, full: 1 };
                    var ratio = ratios[btn.dataset.size] || 1;

                    var container = self._getImageContainer();
                    if (!container) return;
                    var parentEl = container.parentElement;
                    if (!parentEl || !parentEl.offsetWidth) return;

                    var newWidth = Math.round(parentEl.offsetWidth * ratio);

                    if (isNodeSel) {
                        // Direct transaction preserves NodeSelection
                        var tr = ed.state.tr.setNodeMarkup(sel.from, undefined,
                            Object.assign({}, sel.node.attrs, { width: newWidth }));
                        ed.view.dispatch(tr);
                    } else {
                        ed.commands.updateAttributes('image', { width: newWidth });
                    }
                } catch (err) {
                    console.warn('Image size failed:', err);
                }
            });
        });

        // Alignment buttons: set textAlign on parent paragraph via transaction
        bubble.querySelectorAll('[data-img-align]').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                try {
                    self._setImageParentAlign(self._editor, btn.dataset.imgAlign);
                } catch (err) {
                    console.warn('Image align failed:', err);
                }
            });
        });

        var altBtn = bubble.querySelector('[data-bubble="alt"]');
        if (altBtn) altBtn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            if (modals) modals.showImageAlt(self._editor);
        });

        var delBtn = bubble.querySelector('[data-bubble="delete"]');
        if (delBtn) delBtn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var ed = self._editor;
            if (!ed) return;
            var sel = ed.state.selection;
            if (sel.node) {
                // Direct transaction for NodeSelection delete
                ed.view.dispatch(ed.state.tr.delete(sel.from, sel.to));
            } else {
                ed.chain().focus().deleteSelection().run();
            }
        });
    }

    _getImageContainer() {
        var ed = this._editor;
        if (!ed) return null;
        var view = ed.view;
        var sel = ed.state.selection;
        // Best: use nodeDOM for NodeSelection
        if (sel.node && sel.node.type.name === 'image') {
            try {
                var dom = view.nodeDOM(sel.from);
                if (dom) return dom;
            } catch (e) {}
        }
        // Fallback: ProseMirror-selectednode class or any resize container
        return view.dom.querySelector('.ProseMirror-selectednode') ||
               view.dom.querySelector('[data-resize-container]');
    }

    _setImageParentAlign(editor, align) {
        if (!editor) return;
        var state = editor.state;
        var sel = state.selection;
        var pos = sel.from;
        var resolved = state.doc.resolve(pos);

        // Case 1: image inside a paragraph/heading — set textAlign on the block parent
        for (var d = resolved.depth; d >= 0; d--) {
            var node = resolved.node(d);
            if (node.type.name === 'paragraph' || node.type.name === 'heading') {
                var startPos = resolved.before(d);
                var tr = state.tr.setNodeMarkup(startPos, undefined,
                    Object.assign({}, node.attrs, { textAlign: align }));
                editor.view.dispatch(tr);
                return;
            }
        }
    }

    // ========= POSITIONING & VISIBILITY =========
    _updateBubbles() {
        var editor = this._editor;
        if (!editor || editor.isDestroyed) return;

        var sel = editor.state.selection;
        var isLink = editor.isActive('link');
        // TipTap v3 ResizableNodeView: isActive may fail, also check NodeSelection
        var isImage = editor.isActive('image') || !!(sel.node && sel.node.type.name === 'image');
        var hasTextSelection = !sel.empty;
        // CellSelection: prosemirror-tables sets $anchorCell on cell selections
        var isCellSelection = !!(sel.$anchorCell);

        // Priority: image > link (cursor on link, no selection) > text (text selection, not in cell selection)
        // Table controls are handled by TableControls overlay, not bubble
        var showImage = isImage;
        var showLink = isLink && !hasTextSelection && !isImage;
        var showText = hasTextSelection && !isCellSelection && !isImage;

        this._toggleBubble('text', showText);
        this._toggleBubble('link', showLink);
        this._toggleBubble('image', showImage);
    }

    _toggleBubble(type, show) {
        var el = this.elements[type];
        if (!el) return;

        if (show) {
            if (type === 'link') this._updateLinkDisplay();
            if (type === 'text') this._updateTextState();

            var ref = this._getRefElement(type);
            if (!ref) {
                this._hideBubble(type);
                return;
            }

            // Text bubble: virtual element, position once per update
            if (type === 'text') {
                this._stopPositioning(type);
                this._positionOnce(el, ref);
                this._visible[type] = true;
                return;
            }

            // DOM-based bubbles: check visibility, use autoUpdate
            if (!this._isRefVisible(ref)) {
                this._hideBubble(type);
                return;
            }

            el.style.display = 'flex';
            this._startPositioning(type, ref);
            this._visible[type] = true;
        } else {
            this._hideBubble(type);
        }
    }

    _hideBubble(type) {
        var el = this.elements[type];
        if (!el) return;
        el.style.display = 'none';
        this._visible[type] = false;
        this._stopPositioning(type);
    }

    _getRefElement(type) {
        var editor = this._editor;
        if (!editor) return null;
        var view = editor.view;
        var from = editor.state.selection.from;

        try {
            if (type === 'text') {
                var sel = window.getSelection();
                if (!sel || sel.isCollapsed || sel.rangeCount === 0) return null;
                var range = sel.getRangeAt(0);
                var rect = range.getBoundingClientRect();
                if (rect.width === 0 && rect.height === 0) return null;
                return { getBoundingClientRect: function () { return rect; } };
            }
            if (type === 'link') {
                var domAtPos = view.domAtPos(from);
                var node = domAtPos.node;
                if (node.nodeType === 3) node = node.parentElement;
                return node ? node.closest('a') : null;
            }
            if (type === 'image') {
                var imgSel = editor.state.selection;
                // NodeSelection: use view.nodeDOM for reliable DOM lookup
                if (imgSel.node && imgSel.node.type.name === 'image') {
                    try {
                        var nodeDom = view.nodeDOM(imgSel.from);
                        if (nodeDom) return nodeDom;
                    } catch (e2) {}
                }
                // Fallback: ProseMirror-selectednode class
                var selected = view.dom.querySelector('.ProseMirror-selectednode');
                if (selected) return selected;
                // Fallback: domAtPos
                var domAtPos2 = view.domAtPos(from);
                var node2 = domAtPos2.node;
                if (node2.nodeType === 3) node2 = node2.parentElement;
                return node2 ? (node2.closest('[data-resize-container]') || node2.closest('img')) : null;
            }
        } catch (e) {}
        return null;
    }

    _isRefVisible(refEl) {
        var contentArea = this._wrapper ? this._wrapper.querySelector('.tiptap-content-area') : null;
        if (!contentArea || !refEl) return false;
        var caRect = contentArea.getBoundingClientRect();
        var refRect = refEl.getBoundingClientRect();
        return refRect.bottom > caRect.top && refRect.top < caRect.bottom;
    }

    _positionOnce(el, refEl) {
        if (!el || !refEl) return;
        var FUI = window.FloatingUIDOM;
        if (!FUI) return;

        el.style.display = 'flex';

        FUI.computePosition(refEl, el, {
            placement: 'top',
            strategy: 'absolute',
            middleware: [
                FUI.offset(8),
                FUI.flip({ fallbackPlacements: ['bottom'] }),
                FUI.shift({ padding: 8 }),
            ],
        }).then(function (data) {
            Object.assign(el.style, {
                left: Math.round(data.x) + 'px',
                top: Math.round(data.y) + 'px',
            });
        });
    }

    _startPositioning(type, refEl) {
        this._stopPositioning(type);

        var el = this.elements[type];
        if (!el || !refEl) return;

        var FUI = window.FloatingUIDOM;
        if (!FUI) return;

        var placement = type === 'link' ? 'bottom' : 'top';
        var self = this;

        var updatePosition = function () {
            if (!self._isRefVisible(refEl)) {
                el.style.display = 'none';
                return;
            }
            el.style.display = 'flex';

            FUI.computePosition(refEl, el, {
                placement: placement,
                strategy: 'absolute',
                middleware: [
                    FUI.offset(8),
                    FUI.flip({ fallbackPlacements: ['top', 'bottom'] }),
                    FUI.shift({ padding: 8 }),
                ],
            }).then(function (data) {
                Object.assign(el.style, {
                    left: Math.round(data.x) + 'px',
                    top: Math.round(data.y) + 'px',
                });
            });
        };

        this._cleanups[type] = FUI.autoUpdate(refEl, el, updatePosition, {
            ancestorScroll: true,
            ancestorResize: true,
            elementResize: true,
        });
    }

    _stopPositioning(type) {
        if (typeof this._cleanups[type] === 'function') {
            this._cleanups[type]();
            delete this._cleanups[type];
        }
    }
};
