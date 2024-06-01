window.colMdEditor = class ColMdTune {
    static get isTune() {
        return true;
    }

    constructor({ api, data, config, block }) {
        this.api = api;
        this.data = data;
        this.config = config;
        this.block = block;

        this.md = this.data ? this.data : 12;

        if (this.data) {
            let observer = new MutationObserver(() => {
                if (this.block.holder) {
                    this.setMd(this.md);
                    observer.disconnect();
                }
            });

            observer.observe(this.api.ui.nodes.redactor, {
                childList: true,
            });
        }
    }

    setMd(value) {
        for (let t = 1; t <= 12; t++) {
            this.block.holder.classList.remove('col-md-' + t);
        }
        this.block.holder.classList.add('col-md-' + value);
        this.md = value;
    }

    render() {
        this.select = document.createElement('select');

        this.select.addEventListener('change', () =>
            this.setMd(this.select.value),
        );

        for (let i = 1; i <= 12; i++) {
            let option = document.createElement('option');
            option.value = i;
            option.innerText = i + ' md';
            if (i == this.md) {
                option.selected = true;
            }
            this.select.appendChild(option);
        }

        return this.select;
    }

    save() {
        return this.md;
    }
};

window.editorTools = {
    ...(window.editorTools ?? {}),
    ...{
        header: {
            class: Header,
            inlineToolbar: ['link'],
            config: {
                placeholder: 'Header',
            },
            shortcut: 'CMD+SHIFT+H',
            tunes: ['alignment'],
        },
        paragraph: {
            inlineToolbar: true,
            tunes: ['alignment'],
        },
        raw: RawTool,
        image: {
            class: ImageTool,
            inlineToolbar: ['link'],
            tunes: ['alignment'],
            config: {
                endpoints: {
                    byFile: u('page/saveimage'),
                },
            },
        },
        list: {
            class: editorjsNestedChecklist,
            inlineToolbar: true,
            shortcut: 'CMD+SHIFT+L',
        },
        marker: {
            class: Marker,
            shortcut: 'CMD+SHIFT+M',
        },
        delimiter: Delimiter,
        embed: Embed,
        table: {
            class: Table,
            inlineToolbar: true,
            shortcut: 'CMD+ALT+T',
            tunes: ['alignment'],
        },
        alignment: {
            class: AlignmentBlockTune,
            config: {
                default: 'left',
                blocks: {
                    header: 'center',
                    list: 'right',
                },
            },
        },
        col: {
            class: window.colMdEditor,
        },
    },
};

$(function () {
    const phrasesEditorJs =
        $('html').attr('lang') !== 'ru'
            ? {}
            : {
                  messages: {
                      ui: {
                          blockTunes: {
                              toggler: {
                                  'Click to tune': 'Параметры',
                                  'or drag to move': 'или перетащите',
                              },
                          },
                          inlineToolbar: {
                              converter: {
                                  'Convert to': 'Конвертировать в',
                              },
                          },
                          toolbar: {
                              toolbox: {
                                  Add: 'Добавить',
                              },
                          },
                          popover: {
                              Filter: 'Фильтр',
                              'Nothing found': 'Ничего не найдено',
                          },
                      },

                      toolNames: {
                          Text: 'Обычный текст',
                          Heading: 'Заголовок',
                          List: 'Список',
                          Warning: 'Примечание',
                          Checklist: 'Чеклист',
                          Code: 'Код',
                          Delimiter: 'Разделитель',
                          Table: 'Таблица',
                          Link: 'Ссылка',
                          Marker: 'Маркер',
                          Bold: 'Полужирный',
                          Italic: 'Курсив',
                          Row: 'Ряд',
                          Card: 'Блок',
                          Widgets: 'Виджеты',
                          Widget: 'Виджет',
                          'Nested Checklist': 'Список',
                          Image: 'Изображение',
                      },

                      tools: {
                          warning: {
                              Title: 'Название',
                              Message: 'Сообщение',
                          },

                          link: {
                              'Add a link': 'Вставьте ссылку',
                          },
                          stub: {
                              'The block can not be displayed correctly.':
                                  'Блок не может быть отображен',
                          },
                      },

                      blockTunes: {
                          delete: {
                              Delete: 'Удалить',
                          },
                          moveUp: {
                              'Move up': 'Наверх',
                          },
                          moveDown: {
                              'Move down': 'Вниз',
                          },
                      },
                  },
              };

    function initializeAllEditors() {
        setTimeout(() => {
            const editors = document.querySelectorAll(
                '.tab-content:not([hidden]) [data-editorjs]',
            );

            editors.forEach((editorElement) => {
                const editorId = editorElement.id;
                if (!editorId) {
                    console.error('Editor element must have an ID.');
                    return;
                }

                const existingEditor = window['editorInstance_' + editorId];
                if (existingEditor) {
                    existingEditor.destroy();
                    console.log(
                        `Editor ${editorId} destroyed before reinitialization.`,
                    );
                }

                const savedData = localStorage.getItem(
                    'editorData_' + editorId,
                );
                const initialData = savedData ? JSON.parse(savedData) : {};
                const editorDefaults =
                    window.defaultEditorData &&
                    window.defaultEditorData[editorId]
                        ? window.defaultEditorData[editorId]
                        : {};

                const editorConfig = {
                    ...window.editorConfig,
                    tools: window.editorTools,
                    holder: editorId,
                    data: { ...initialData, ...editorDefaults },
                    i18n: phrasesEditorJs,
                    onReady: () => {
                        console.log(`Editor ${editorId} is ready.`);
                        document.body.classList.add('editor-opened');
                    },
                    onSave: () => {
                        localStorage.removeItem('editorData_' + editorId);
                        console.log(
                            `Editor ${editorId} is removed from localstorage.`,
                        );
                    },
                    onChange: (api, event) => {
                        console.log(`Content changed in editor ${editorId}`);
                        api.saver.save().then((outputData) => {
                            localStorage.setItem(
                                'editorData_' + editorId,
                                JSON.stringify(outputData),
                            );
                        });
                    },
                };

                const editorInstance = new EditorJS(editorConfig);
                window['editorInstance_' + editorId] = editorInstance;
            });
        }, 300);
    }
    
    initializeAllEditors();

    document
        .querySelector('.chrome-tabs')
        .addEventListener('contentRender', ({ detail }) => {
            initializeAllEditors();
        });
});

// window.editorConfig = {
//     holder: 'editor',
//     tools: window.editorTools,
//     autofocus: true,
//     defaultBlock: 'paragraph',
//     data: window.editorData,
//     onReady: () => {
//         // new Undo({ editor });
//         // new DragDrop(editor);
//     },
//     onChange: (data, test) => {
//         $('.save_container').addClass('opened');
//     },
//     onSave: () => {
//         console.log('Saving');
//     },
//     i18n:

// };

// const editor = new EditorJS(window.editorConfig);
