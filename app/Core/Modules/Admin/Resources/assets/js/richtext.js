/**
 * Flute CMS - EasyMDE Rich Text Editor Integration
 */
class FluteRichTextEditor {
    constructor() {
        this.instances = {};
        this.iconCache = {};
        this.setupThemeObserver();
        this.setupEventListeners();
        this.setupEditorObserver();
    }

    /**
     * Initialize EasyMDE on elements matching the selector
     * @param {string|Element|NodeList|Array<Element>} target - CSS selector, DOM element, NodeList, or Array of elements
     */
    initialize(target = '[data-editor="markdown"]') {
        let textareas;
        if (typeof target === 'string') {
            textareas = document.querySelectorAll(target);
        } else if (target instanceof NodeList || Array.isArray(target)) {
            textareas = target;
        } else if (target instanceof Element) {
            textareas = [target];
        } else {
            // Fallback or error handling if target is invalid type
            console.warn('Invalid target type for editor initialization:', target);
            textareas = document.querySelectorAll('[data-editor="markdown"]');
        }

        const editorsToInitialize = Array.from(textareas).filter(textarea => {
            if (!textarea.id) {
                textarea.id = 'editor-' + Math.random().toString(36).substring(2, 9);
            }
            if (this.instances[textarea.id]) {
                return false;
            }
            if (textarea.nextElementSibling && textarea.nextElementSibling.classList.contains('EasyMDEContainer')) {
                return false;
            }
            return true;
        });

        editorsToInitialize.forEach((textarea) => this.initializeEditor(textarea));
    }

    /**
     * Initialize a single editor instance
     * @param {Element} textarea - The textarea element to initialize (must have an ID)
     */
    initializeEditor(textarea) {
        if (!textarea.id || this.instances[textarea.id]) {
            return;
        }
        if (textarea.nextElementSibling && textarea.nextElementSibling.classList.contains('EasyMDEContainer')) {
            return;
        }

        const height = parseInt(
            textarea.getAttribute('data-height') || '300',
            10,
        );
        const currentTheme =
            document.documentElement.getAttribute('data-theme') || 'light';
        const toolbarOptions = this.getToolbarOptions(textarea);

        const easyMDE = new EasyMDE({
            element: textarea,
            spellChecker: textarea.getAttribute('data-spellcheck') === 'true',
            autofocus: textarea.hasAttribute('autofocus'),
            status: ['lines', 'words', 'cursor'],
            minHeight: `${height}px`,
            maxHeight: `${height * 2}px`,
            toolbar: toolbarOptions,
            theme: currentTheme,
            placeholder:
                textarea.getAttribute('placeholder') ||
                'Write your content here...',
            autoDownloadFontAwesome: false,
            uploadImage: textarea.getAttribute('data-upload') === 'true',
            imageUploadEndpoint:
                textarea.getAttribute('data-upload-url') ||
                '/admin/api/upload-image',
            imageCSRFName: 'flute_csrf_token',
            imageCSRFToken:
                document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute('content') || '',
            imageAccept: 'image/png, image/jpeg, image/gif, image/webp',
            imageMaxSize: 5 * 1024 * 1024, // 5MB
            imageTexts: {
                sbInit: 'Attach files by dragging & dropping or selecting them.',
                sbOnDragEnter: 'Drop image to upload it.',
                sbOnDrop: 'Uploading image...',
                sbProgress: 'Uploading: ##progress##%',
                sbSuccess: 'Image uploaded successfully!',
                sbFailure: 'Error uploading image.',
            },
        });

        easyMDE.codemirror.on('change', () => {
            const value = easyMDE.value();
            textarea.value = value;

            document.dispatchEvent(new CustomEvent('editor:change', {
                detail: {
                    id: textarea.id,
                    value: value
                }
            }));
        });

        this.setupImagePasteHandler(easyMDE, textarea);
        this.instances[textarea.id] = easyMDE;
    }

    /**
     * Get toolbar configuration
     * @param {Element} textarea - The textarea element
     * @returns {Array} Toolbar options
     */
    getToolbarOptions(textarea) {
        const customToolbar = textarea.getAttribute('data-toolbar');
        if (customToolbar) {
            try {
                return JSON.parse(customToolbar);
            } catch (e) {
                console.error('Invalid toolbar configuration:', e);
                return this.getDefaultToolbar();
            }
        }
        return this.getDefaultToolbar();
    }

    /**
     * Get default toolbar configuration
     * @returns {Array} Default toolbar options
     */
    getDefaultToolbar() {
        return [
            {
                name: 'bold',
                action: EasyMDE.toggleBold,
                className: 'bold',
                title: 'Bold (Ctrl+B)',
            },
            {
                name: 'italic',
                action: EasyMDE.toggleItalic,
                className: 'italic',
                title: 'Italic (Ctrl+I)',
            },
            {
                name: 'strikethrough',
                action: EasyMDE.toggleStrikethrough,
                className: 'strikethrough',
                title: 'Strikethrough',
            },
            {
                name: 'heading',
                action: EasyMDE.toggleHeadingSmaller,
                className: 'heading',
                title: 'Heading (Ctrl+H)',
            },
            {
                name: 'heading-smaller',
                action: EasyMDE.toggleHeadingSmaller,
                className: 'heading-smaller',
                title: 'Smaller Heading',
            },
            {
                name: 'heading-bigger',
                action: EasyMDE.toggleHeadingBigger,
                className: 'heading-bigger',
                title: 'Bigger Heading',
            },
            '|',
            {
                name: 'code',
                action: EasyMDE.toggleCodeBlock,
                className: 'code',
                title: 'Code (Ctrl+Alt+C)',
            },
            {
                name: 'quote',
                action: EasyMDE.toggleBlockquote,
                className: 'quote',
                title: "Quote (Ctrl+')",
            },
            {
                name: 'unordered-list',
                action: EasyMDE.toggleUnorderedList,
                className: 'unordered-list',
                title: 'Unordered List (Ctrl+L)',
            },
            {
                name: 'ordered-list',
                action: EasyMDE.toggleOrderedList,
                className: 'ordered-list',
                title: 'Ordered List (Ctrl+Alt+L)',
            },
            {
                name: 'horizontal-rule',
                action: EasyMDE.drawHorizontalRule,
                className: 'horizontal-rule',
                title: 'Horizontal Line',
            },
            '|',
            {
                name: 'link',
                action: EasyMDE.drawLink,
                className: 'link',
                title: 'Create Link (Ctrl+K)',
            },
            {
                name: 'image',
                action: EasyMDE.drawImage,
                className: 'image',
                title: 'Insert Image (Ctrl+Alt+I)',
            },
            {
                name: 'table',
                action: EasyMDE.drawTable,
                className: 'table',
                title: 'Insert Table',
            },
            '|',
            {
                name: 'preview',
                action: EasyMDE.togglePreview,
                className: 'preview',
                title: 'Toggle Preview (Ctrl+P)',
            },
            {
                name: 'side-by-side',
                action: EasyMDE.toggleSideBySide,
                className: 'side-by-side',
                title: 'Side by Side (F9)',
            },
            {
                name: 'fullscreen',
                action: EasyMDE.toggleFullScreen,
                className: 'fullscreen',
                title: 'Toggle Fullscreen (F11)',
            },
            '|',
            {
                name: 'custom-clear',
                action: function customClear(editor) {
                    if (confirm('Are you sure you want to clear the editor?')) {
                        editor.value('');
                    }
                },
                className: 'custom-clear',
                title: 'Clear Editor',
            },
            {
                name: 'guide',
                action: 'https://www.markdownguide.org/basic-syntax/',
                className: 'guide',
                title: 'Guide',
            },
        ];
    }

    /**
     * Setup image paste handler
     * @param {Object} editor - EasyMDE instance
     * @param {Element} textarea - The textarea element
     */
    setupImagePasteHandler(editor, textarea) {
        editor.codemirror.on('paste', (cm, e) => {
            if (
                textarea.getAttribute('data-upload') === 'true' &&
                e.clipboardData &&
                e.clipboardData.items
            ) {
                for (let i = 0; i < e.clipboardData.items.length; i++) {
                    if (e.clipboardData.items[i].type.indexOf('image') !== -1) {
                        const file = e.clipboardData.items[i].getAsFile();
                        this.uploadImage(
                            file,
                            editor,
                            textarea.getAttribute('data-upload-url') ||
                            '/admin/api/upload-image',
                        );
                        e.preventDefault();
                        return;
                    }
                }
            }
        });
    }

    /**
     * Upload image to server
     * @param {File} file - Image file to upload
     * @param {Object} editor - EasyMDE instance
     * @param {string} uploadUrl - URL to upload the image
     */
    uploadImage(file, editor, uploadUrl) {
        const formData = new FormData();
        formData.append('image', file);

        const csrfToken = document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content');
        if (csrfToken) {
            formData.append('flute_csrf_token', csrfToken);
        }

        const cm = editor.codemirror;
        const cursor = cm.getCursor();
        const uploadingText = `![Uploading ${file.name}...](uploading)`;
        cm.replaceRange(uploadingText, cursor);

        fetch(uploadUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
        })
            .then((response) => response.json())
            .then((data) => {
                const text = cm.getValue();
                const newText = text.replace(
                    uploadingText,
                    data.success && data.url
                        ? `![${file.name}](${data.url})`
                        : `[Error uploading image: ${data.message || 'Unknown error'
                        }]`,
                );
                cm.setValue(newText);
            })
            .catch((error) => {
                console.error('Error uploading image:', error);
                const text = cm.getValue();
                cm.setValue(
                    text.replace(uploadingText, '[Error uploading image]'),
                );
            });
    }

    /**
     * Initialize icon cache from pre-rendered icons
     */
    initializeIconCache() {
        document
            .querySelectorAll('#richtext-icons-container .editor-icon-template')
            .forEach((template) => {
                const iconKey = template.getAttribute('data-icon');
                if (iconKey) {
                    this.iconCache[iconKey] = {
                        html: template.innerHTML,
                        tooltip: template.getAttribute('data-tooltip'),
                    };
                }
            });
    }

    /**
     * Replace Font Awesome icons with SVG icons
     */
    replaceEditorIcons() {
        setTimeout(() => {
            document
                .querySelectorAll('.editor-toolbar button')
                .forEach((button) => {
                    const classList = Array.from(button.classList);

                    for (const className of classList) {
                        if (this.iconCache[className]) {
                            const iconContainer =
                                document.createElement('span');
                            iconContainer.className = 'editor-toolbar-icon';
                            iconContainer.innerHTML =
                                this.iconCache[className].html;

                            button.innerHTML = '';
                            button.appendChild(iconContainer);

                            if (this.iconCache[className].tooltip) {
                                button.setAttribute(
                                    'data-tooltip',
                                    this.iconCache[className].tooltip,
                                );
                            }

                            button.classList.remove('fa');
                            const faClass = classList.find((cls) =>
                                cls.startsWith('fa-'),
                            );
                            if (faClass) {
                                button.classList.remove(faClass);
                            }

                            break;
                        }
                    }
                });
        }, 100);
    }

    /**
     * Remove editor instances and clean up
     * @param {Element|string} container - Container element or selector
     */
    destroy(container = null) {
        try {
            if (container) {
                let containerEl =
                    typeof container === 'string'
                        ? document.querySelector(container)
                        : container;
                if (containerEl) {
                    containerEl
                        .querySelectorAll('[data-editor="markdown"]')
                        .forEach((textarea) => {
                            if (textarea.id && this.instances[textarea.id]) {
                                try {
                                    this.instances[textarea.id].toTextArea();
                                } catch (e) {
                                    console.warn(
                                        'Error destroying editor instance:',
                                        e,
                                    );
                                }
                                delete this.instances[textarea.id];
                            }
                        });
                }
            } else {
                Object.keys(this.instances).forEach((id) => {
                    if (
                        this.instances[id] &&
                        typeof this.instances[id].toTextArea === 'function'
                    ) {
                        try {
                            this.instances[id].toTextArea();
                        } catch (e) {
                            console.warn(
                                'Error destroying editor instance:',
                                e,
                            );
                        }
                        delete this.instances[id];
                    }
                });
            }
        } catch (e) {
            console.warn('Error in destroy method:', e);
        }
    }

    /**
     * Update editors theme
     */
    updateEditorsTheme() {
        const currentTheme =
            document.documentElement.getAttribute('data-theme') || 'light';

        Object.keys(this.instances).forEach((id) => {
            const editor = this.instances[id];
            if (editor) {
                const content = editor.value();
                const textarea = editor.element;
                const height = parseInt(
                    textarea.getAttribute('data-height') || '300',
                    10,
                );
                const toolbarOptions = this.getToolbarOptions(textarea);

                editor.toTextArea();
                delete this.instances[id];

                setTimeout(() => {
                    const newEditor = new EasyMDE({
                        element: textarea,
                        spellChecker:
                            textarea.getAttribute('data-spellcheck') === 'true',
                        status: ['lines', 'words', 'cursor'],
                        minHeight: `${height}px`,
                        maxHeight: `${height * 2}px`,
                        toolbar: toolbarOptions,
                        theme: currentTheme,
                        placeholder:
                            textarea.getAttribute('placeholder') ||
                            'Write your content here...',
                        autoDownloadFontAwesome: false,
                        uploadImage:
                            textarea.getAttribute('data-upload') === 'true',
                        imageUploadEndpoint:
                            textarea.getAttribute('data-upload-url') ||
                            '/admin/api/upload-image',
                        imageCSRFName: 'flute_csrf_token',
                        imageCSRFToken:
                            document
                                .querySelector('meta[name="csrf-token"]')
                                ?.getAttribute('content') || '',
                        imageAccept:
                            'image/png, image/jpeg, image/gif, image/webp',
                        imageMaxSize: 5 * 1024 * 1024,
                    });

                    newEditor.value(content);
                    this.instances[textarea.id] = newEditor;
                    newEditor.codemirror.setSize(null, `${height}px`);
                }, 0);
            }
        });
    }

    /**
     * Setup theme observer
     */
    setupThemeObserver() {
        const observer = new MutationObserver((mutations) => {
            for (const mutation of mutations) {
                if (
                    mutation.type === 'attributes' &&
                    mutation.attributeName === 'data-theme'
                ) {
                    this.updateEditorsTheme();
                    break;
                }
            }
        });

        observer.observe(document.documentElement, { attributes: true });
    }

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        document.addEventListener('DOMContentLoaded', () => {
            this.initialize();
            this.initializeIconCache();
            this.replaceEditorIcons();
        });

        // HTMX events
        document.addEventListener('htmx:configRequest', (event) => {
            const form = event.detail.elt.closest('form');
            if (form) {
                this.saveContent(form);

                form.querySelectorAll('[data-editor="markdown"]').forEach((textarea) => {
                    if (textarea.name && textarea.form) {
                        event.detail.parameters[textarea.name] = textarea.value;
                    }
                });
            }
        });

        document.addEventListener('htmx:beforeSwap', (event) => {
            const container = event.detail.target;
            if (container) {
                container.querySelectorAll('[data-editor="markdown"]').forEach((textarea) => {
                    if (textarea.id && this.instances[textarea.id]) {
                        try {
                            textarea.setAttribute('data-editor-content', this.instances[textarea.id].value());
                            this.instances[textarea.id].toTextArea();
                        } catch (e) {
                            console.warn('Error reverting editor to textarea before swap:', e);
                        } finally {
                            // Ensure instance is removed even if toTextArea fails
                            delete this.instances[textarea.id];
                        }
                    }
                });
            }
        });

        document.addEventListener('htmx:afterSwap', (event) => {
            const swapTarget = event.detail.target;
            if (swapTarget && typeof swapTarget.querySelectorAll === 'function') {
                let potentialEditors = [];
                if (swapTarget.matches && swapTarget.matches('[data-editor="markdown"]')) {
                    potentialEditors.push(swapTarget);
                }
                potentialEditors = potentialEditors.concat(
                    Array.from(swapTarget.querySelectorAll('[data-editor="markdown"]'))
                );

                potentialEditors = [...new Set(potentialEditors)];

                if (potentialEditors.length > 0) {
                    this.initialize(potentialEditors);
                    this.initializeIconCache();
                    this.replaceEditorIcons();

                    potentialEditors.forEach((textarea) => {
                        if (textarea.id && this.instances[textarea.id]) {
                            const savedContent = textarea.getAttribute('data-editor-content');
                            if (savedContent) {
                                this.instances[textarea.id].value(savedContent);
                                textarea.removeAttribute('data-editor-content');
                            }
                        } else {
                            const savedContent = textarea.getAttribute('data-editor-content');
                            if (savedContent && textarea.value !== savedContent) {
                                textarea.value = savedContent;
                                textarea.removeAttribute('data-editor-content');
                            } else if (savedContent) {
                                textarea.removeAttribute('data-editor-content');
                            }
                        }
                    });
                }
            }
        });

        // Standard form submission
        document.addEventListener('submit', (event) => {
            this.saveContent(event.target);
        });

        // Handle old htmx:beforeRequest for backward compatibility
        document.addEventListener('htmx:beforeRequest', (event) => {
            if (event.detail.elt.tagName === 'FORM') {
                this.saveContent(event.detail.elt);
            }
        });

        document.addEventListener('click', (event) => {
            const openElements = event.target.closest('[data-modal-open]');
            if (!openElements) return;

            const modalId = openElements.getAttribute('data-modal-open');
            const modalElement = document.getElementById(modalId);

            if (modalElement) {
                setTimeout(() => {
                    const newEditors = modalElement.querySelectorAll('[data-editor="markdown"]');
                    if (newEditors.length > 0) {
                        this.initialize(newEditors);
                        this.initializeIconCache();
                        this.replaceEditorIcons();
                    }
                }, 100);
            }
        });

        const originalOnModalShow = window.onModalShow || function () { };
        window.onModalShow = (modalElement) => {
            originalOnModalShow(modalElement);
            setTimeout(() => {
                const newEditors = modalElement.querySelectorAll('[data-editor="markdown"]');
                if (newEditors.length > 0) {
                    this.initialize(newEditors);
                    this.initializeIconCache();
                    this.replaceEditorIcons();
                }
            }, 100);
        };

        const originalOnModalHide = window.onModalHide || function () { };
        window.onModalHide = (modalElement) => {
            this.saveContent(modalElement);
            this.destroy(modalElement);
            originalOnModalHide(modalElement);
        };

        document.addEventListener('editor:change', (event) => {
            if (event.detail && event.detail.id && this.instances[event.detail.id]) {
                const textarea = document.getElementById(event.detail.id);
                if (textarea) {
                    textarea.value = event.detail.value;
                }
            }
        });
    }

    /**
     * Save editor content back to textarea
     * @param {Element} container - The container element
     */
    saveContent(container) {
        if (!container) return;

        container.querySelectorAll('[data-editor="markdown"]').forEach((textarea) => {
            if (textarea.id && this.instances[textarea.id]) {
                try {
                    const content = this.instances[textarea.id].value();
                    textarea.value = content;
                    textarea.setAttribute('data-editor-content', content);
                } catch (e) {
                    console.warn('Error saving editor content:', e);
                }
            }
        });
    }

    /**
     * Observe DOM for new markdown textareas and initialize EasyMDE
     */
    setupEditorObserver() {
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (node.nodeType === Node.ELEMENT_NODE) {
                        let potentialEditors = [];
                        if (node.matches && node.matches('[data-editor="markdown"]')) {
                            potentialEditors.push(node);
                        }
                        else if (typeof node.querySelectorAll === 'function') {
                            potentialEditors = potentialEditors.concat(
                                Array.from(node.querySelectorAll('[data-editor="markdown"]'))
                            );
                        }

                        potentialEditors = [...new Set(potentialEditors)];

                        if (potentialEditors.length) {
                            this.initialize(potentialEditors);
                            this.initializeIconCache();
                            this.replaceEditorIcons();
                        }
                    }
                });
            });
        });
        observer.observe(document.body, { childList: true, subtree: true });
    }
}

window.fluteRichTextEditor = new FluteRichTextEditor();
