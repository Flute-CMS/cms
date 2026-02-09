window.FluteRichText = window.FluteRichText || {};

window.FluteRichText.ToolbarBuilder = class {
    constructor() {
        this.Icons = window.FluteRichText.Icons;
    }

    _t(key, fallback) {
        var i18n = window.FluteRichText && window.FluteRichText.i18n;
        return (i18n && i18n[key]) || fallback || key;
    }

    getConfig(textarea) {
        const custom = textarea.getAttribute('data-toolbar');
        if (custom)
            try {
                return JSON.parse(custom);
            } catch {}
        return this.getDefault();
    }

    getDefault() {
        var t = this._t.bind(this);
        return [
            { group: 'history', items: ['undo', 'redo'] },
            '|',
            {
                group: 'format',
                items: ['bold', 'italic', 'underline', 'strikethrough'],
            },
            '|',
            {
                group: 'heading',
                type: 'dropdown',
                label: t('heading', 'Heading'),
                icon: 'heading',
                items: [
                    { name: 'paragraph', label: t('paragraph', 'Paragraph') },
                    { name: 'heading-1', label: t('heading_1', 'Heading 1') },
                    { name: 'heading-2', label: t('heading_2', 'Heading 2') },
                    { name: 'heading-3', label: t('heading_3', 'Heading 3') },
                ],
            },
            '|',
            {
                group: 'lists',
                items: ['unordered-list', 'ordered-list', 'task-list'],
            },
            { group: 'blocks', items: ['quote', 'code', 'horizontal-rule'] },
            '|',
            {
                group: 'align',
                type: 'dropdown',
                label: t('align', 'Align'),
                icon: 'align-left',
                items: [
                    { name: 'align-left', label: t('left', 'Left') },
                    { name: 'align-center', label: t('center', 'Center') },
                    { name: 'align-right', label: t('right', 'Right') },
                ],
            },
            '|',
            { group: 'insert', items: ['link', 'image', 'table', 'youtube'] },
            '|',
            {
                group: 'misc',
                items: [
                    'highlight',
                    'superscript',
                    'subscript',
                    'inline-code',
                    'translation',
                    'clear',
                ],
            },
            '|',
            { group: 'view', items: ['fullscreen'] },
        ];
    }

    build(config, uploadEnabled, editorId) {
        const toolbar = document.createElement('div');
        toolbar.className = 'tiptap-toolbar';

        config.forEach((item) => {
            if (item === '|') {
                const sep = document.createElement('span');
                sep.className = 'toolbar-separator';
                toolbar.appendChild(sep);
                return;
            }
            if (typeof item === 'string') {
                toolbar.appendChild(this._btn(item));
                return;
            }
            if (item.type === 'dropdown') {
                toolbar.appendChild(this._dropdown(item, editorId));
                return;
            }
            if (item.items) {
                const group = document.createElement('div');
                group.className = 'toolbar-group';
                item.items.forEach((name) =>
                    group.appendChild(this._btn(name)),
                );
                toolbar.appendChild(group);
            }
        });

        if (uploadEnabled) {
            const input = document.createElement('input');
            input.type = 'file';
            input.accept = 'image/png,image/jpeg,image/gif,image/webp';
            input.style.display = 'none';
            input.className = 'tiptap-file-input';
            toolbar.appendChild(input);
        }

        return toolbar;
    }

    _btn(name) {
        const icon = this.Icons[name];
        if (!icon)
            return Object.assign(document.createElement('span'), {
                style: 'display:none',
            });
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.dataset.action = name;
        btn.innerHTML = icon;
        btn.setAttribute('data-tooltip', this._title(name));
        btn.setAttribute('data-tooltip-placement', 'top');
        btn.setAttribute('tabindex', '-1');
        return btn;
    }

    _dropdown(config, editorId) {
        const id = editorId + '-' + config.group;
        const container = document.createElement('div');
        container.className = 'toolbar-dropdown';

        const toggle = document.createElement('button');
        toggle.type = 'button';
        toggle.className = 'toolbar-dropdown-toggle';
        toggle.setAttribute('data-dropdown-open', id);
        toggle.setAttribute('data-tooltip', config.label);
        toggle.setAttribute('data-tooltip-placement', 'top');
        toggle.setAttribute('tabindex', '-1');
        toggle.innerHTML =
            (this.Icons[config.icon] || '') +
            '<span class="toolbar-dropdown-chevron">' +
            this.Icons['chevron-down'] +
            '</span>';
        container.appendChild(toggle);

        const menu = document.createElement('div');
        menu.setAttribute('data-dropdown', id);
        menu.className = 'toolbar-dropdown-menu';

        config.items.forEach((opt) => {
            const item = document.createElement('button');
            item.type = 'button';
            item.dataset.action = opt.name;
            item.className = 'toolbar-dropdown-item';
            item.setAttribute('tabindex', '-1');
            const iconHtml = this.Icons[opt.name] || '';
            item.innerHTML =
                (iconHtml
                    ? '<span class="dropdown-item-icon">' +
                      iconHtml +
                      '</span>'
                    : '') +
                '<span class="dropdown-item-label">' +
                opt.label +
                '</span>';
            menu.appendChild(item);
        });

        container.appendChild(menu);
        return container;
    }

    _title(name) {
        var t = this._t.bind(this);
        var keyMap = {
            'bold': 'bold',
            'italic': 'italic',
            'underline': 'underline',
            'strikethrough': 'strikethrough',
            'heading': 'heading',
            'heading-1': 'heading_1',
            'heading-2': 'heading_2',
            'heading-3': 'heading_3',
            'code': 'code',
            'inline-code': 'inline_code',
            'quote': 'quote',
            'unordered-list': 'unordered_list',
            'ordered-list': 'ordered_list',
            'task-list': 'task_list',
            'horizontal-rule': 'horizontal_rule',
            'link': 'link',
            'image': 'image',
            'table': 'table',
            'fullscreen': 'fullscreen',
            'translation': 'translation',
            'clear': 'clear',
            'align-left': 'align_left',
            'align-center': 'align_center',
            'align-right': 'align_right',
            'highlight': 'highlight',
            'youtube': 'youtube',
            'undo': 'undo',
            'redo': 'redo',
            'paragraph': 'paragraph',
            'superscript': 'superscript',
            'subscript': 'subscript',
        };
        var fallbacks = {
            'bold': 'Bold',
            'italic': 'Italic',
            'underline': 'Underline',
            'strikethrough': 'Strikethrough',
            'heading': 'Heading',
            'heading-1': 'Heading 1',
            'heading-2': 'Heading 2',
            'heading-3': 'Heading 3',
            'code': 'Code Block',
            'inline-code': 'Inline Code',
            'quote': 'Blockquote',
            'unordered-list': 'Bullet List',
            'ordered-list': 'Numbered List',
            'task-list': 'Task List',
            'horizontal-rule': 'Horizontal Line',
            'link': 'Link',
            'image': 'Image',
            'table': 'Table',
            'fullscreen': 'Fullscreen',
            'translation': 'Translation Key',
            'clear': 'Clear Formatting',
            'align-left': 'Align Left',
            'align-center': 'Align Center',
            'align-right': 'Align Right',
            'highlight': 'Highlight',
            'youtube': 'YouTube Video',
            'undo': 'Undo',
            'redo': 'Redo',
            'paragraph': 'Paragraph',
            'superscript': 'Superscript',
            'subscript': 'Subscript',
        };
        var shortcuts = {
            'bold': ' (Ctrl+B)',
            'italic': ' (Ctrl+I)',
            'underline': ' (Ctrl+U)',
            'link': ' (Ctrl+K)',
            'undo': ' (Ctrl+Z)',
            'redo': ' (Ctrl+Y)',
        };
        var i18nKey = keyMap[name];
        var label = i18nKey ? t(i18nKey, fallbacks[name] || name) : (fallbacks[name] || name);
        var shortcut = shortcuts[name] || '';
        return label + shortcut;
    }

    execAction(editor, action, ctx) {
        const chain = editor.chain().focus();
        switch (action) {
            case 'bold': chain.toggleBold().run(); break;
            case 'italic': chain.toggleItalic().run(); break;
            case 'underline': chain.toggleUnderline().run(); break;
            case 'strikethrough': chain.toggleStrike().run(); break;
            case 'heading-1': chain.toggleHeading({ level: 1 }).run(); break;
            case 'heading-2': chain.toggleHeading({ level: 2 }).run(); break;
            case 'heading-3': chain.toggleHeading({ level: 3 }).run(); break;
            case 'paragraph': chain.setParagraph().run(); break;
            case 'code': chain.toggleCodeBlock().run(); break;
            case 'inline-code': chain.toggleCode().run(); break;
            case 'quote': chain.toggleBlockquote().run(); break;
            case 'unordered-list': chain.toggleBulletList().run(); break;
            case 'ordered-list': chain.toggleOrderedList().run(); break;
            case 'task-list': chain.toggleTaskList().run(); break;
            case 'horizontal-rule': chain.setHorizontalRule().run(); break;
            case 'align-left': chain.setTextAlign('left').run(); break;
            case 'align-center': chain.setTextAlign('center').run(); break;
            case 'align-right': chain.setTextAlign('right').run(); break;
            case 'highlight': chain.toggleHighlight().run(); break;
            case 'superscript': chain.toggleSuperscript().run(); break;
            case 'subscript': chain.toggleSubscript().run(); break;
            case 'undo': chain.undo().run(); break;
            case 'redo': chain.redo().run(); break;
            case 'clear':
                editor.chain().focus().clearNodes().unsetAllMarks().run();
                break;
            case 'link': ctx.modals.showLink(editor); break;
            case 'image': ctx.uploader.trigger(editor, ctx.toolbar, ctx.uploadUrl); break;
            case 'table': ctx.modals.showTable(editor); break;
            case 'youtube': ctx.modals.showYoutube(editor); break;
            case 'translation': ctx.modals.showTranslation(editor); break;
            case 'fullscreen': ctx.toggleFullscreen(); break;
            case 'add-col-after': editor.chain().focus().addColumnAfter().run(); break;
            case 'add-row-after': editor.chain().focus().addRowAfter().run(); break;
            case 'delete-col': editor.chain().focus().deleteColumn().run(); break;
            case 'delete-row': editor.chain().focus().deleteRow().run(); break;
            case 'merge-cells': editor.chain().focus().mergeCells().run(); break;
            case 'delete-table': editor.chain().focus().deleteTable().run(); break;
        }
    }

    updateState(editor, toolbar) {
        toolbar.querySelectorAll('button[data-action]').forEach((btn) => {
            const a = btn.dataset.action;
            let active = false;
            switch (a) {
                case 'bold': active = editor.isActive('bold'); break;
                case 'italic': active = editor.isActive('italic'); break;
                case 'underline': active = editor.isActive('underline'); break;
                case 'strikethrough': active = editor.isActive('strike'); break;
                case 'heading-1': active = editor.isActive('heading', { level: 1 }); break;
                case 'heading-2': active = editor.isActive('heading', { level: 2 }); break;
                case 'heading-3': active = editor.isActive('heading', { level: 3 }); break;
                case 'code': active = editor.isActive('codeBlock'); break;
                case 'inline-code': active = editor.isActive('code'); break;
                case 'quote': active = editor.isActive('blockquote'); break;
                case 'unordered-list': active = editor.isActive('bulletList'); break;
                case 'ordered-list': active = editor.isActive('orderedList'); break;
                case 'task-list': active = editor.isActive('taskList'); break;
                case 'link': active = editor.isActive('link'); break;
                case 'table': active = editor.isActive('table'); break;
                case 'align-left': active = editor.isActive({ textAlign: 'left' }); break;
                case 'align-center': active = editor.isActive({ textAlign: 'center' }); break;
                case 'align-right': active = editor.isActive({ textAlign: 'right' }); break;
                case 'highlight': active = editor.isActive('highlight'); break;
                case 'superscript': active = editor.isActive('superscript'); break;
                case 'subscript': active = editor.isActive('subscript'); break;
            }
            btn.classList.toggle('is-active', active);
        });
    }

    cleanupDropdowns(editorId) {
        document
            .querySelectorAll('[data-dropdown^="' + editorId + '-"]')
            .forEach((el) => {
                if (!el.closest('.tiptap-toolbar')) el.remove();
            });
    }
};
