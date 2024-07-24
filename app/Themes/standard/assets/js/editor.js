class Row {
    static get enableLineBreaks() {
        return true;
    }

    constructor({ data, config, api, readOnly }) {
        this.api = api;
        this.readOnly = readOnly;
        this.config = config || {};
        if (!this.readOnly) {
            this.onKeyUp = this.onKeyUp.bind(this);
        }

        this.editor = null;
        this.data = data;
        this.colWrapper = null;
    }

    static get isReadOnlySupported() {
        return true;
    }

    onKeyUp(e) {
        if (e.code !== 'Backspace' && e.code !== 'Delete') {
            return;
        }
    }

    async _rowContainer() {
        this.render();
    }

    pasteEvent(e) {
        e.stopPropagation();
    }

    keyDownEvent(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            e.stopImmediatePropagation();
            e.stopPropagation();
        }
        if (e.key === 'Tab') {
            e.preventDefault();
            e.stopImmediatePropagation();
            e.stopPropagation();
        }
    }

    render() {
        this.colWrapper = make('div');
        this.colWrapper.classList.add('ce-editorJsRow');

        let editor_col_id = uuidv4();
        this.colWrapper.id = editor_col_id;

        this.colWrapper.addEventListener('paste', this.pasteEvent, true);
        this.colWrapper.addEventListener('keydown', this.keyDownEvent);

        let editorjs_instance = new EditorJS({
            defaultBlock: 'paragraph',
            holder: editor_col_id,
            tools: this.config.tools,
            readOnly: this.readOnly,
            data: this.data,
            minHeight: 50,
            onReady: () => {
                this.colWrapper
                    .querySelector('.codex-editor__redactor')
                    .classList.add('row');
                this.setColMd();
                // new DragDrop(editor);
                // new Undo({ editor })
            },
            onChange: () => {
                this.colWrapper
                    .querySelector('.codex-editor__redactor')
                    .classList.add('row');
                this.setColMd();
            },
        });

        this.editor = editorjs_instance;

        return this.colWrapper;
    }

    // destroy() {
    //     this.colWrapper.removeEventListener('keydown', this.keydown);
    //     this.colWrapper = null;
    // }

    setColMd() {
        let elements = this.colWrapper.querySelectorAll('[data-col-md]');

        for (let card of elements) {
            if (card?.parentNode?.parentNode)
                card?.parentNode?.parentNode.classList.add(
                    'col-md-' + card.getAttribute('data-col-md'),
                );
        }
    }

    async save() {
        if (!this.readOnly) {
            this.data = await this.editor.save();
        }
        return this.data;
    }

    static get toolbox() {
        return {
            icon: `<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 256 256"><path d="M208,136H48a16,16,0,0,0-16,16v40a16,16,0,0,0,16,16H208a16,16,0,0,0,16-16V152A16,16,0,0,0,208,136Zm0,56H48V152H208v40Zm0-144H48A16,16,0,0,0,32,64v40a16,16,0,0,0,16,16H208a16,16,0,0,0,16-16V64A16,16,0,0,0,208,48Zm0,56H48V64H208v40Z"></path></svg>`,
            title: 'Row',
        };
    }
}

class Card {
    static get enableLineBreaks() {
        return true;
    }

    static get toolbox() {
        return {
            title: 'Card',
            icon: `<svg xmlns="http://www.w3.org/2000/svg" width="800px" height="800px" viewBox="-0.5 0 25 25" fill="none">
            <path d="M10.58 3.96997H6C4.93913 3.96997 3.92172 4.39146 3.17157 5.1416C2.42142 5.89175 2 6.9091 2 7.96997V17.97C2 19.0308 2.42142 20.0482 3.17157 20.7983C3.92172 21.5485 4.93913 21.97 6 21.97H18C19.0609 21.97 20.0783 21.5485 20.8284 20.7983C21.5786 20.0482 22 19.0308 22 17.97V13.8999" stroke="#000000" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M10.58 9.96997H2" stroke="#000000" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M5 18.9199H11" stroke="#000000" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M18 10.9199V2.91992" stroke="#000000" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M14 6.91992H22" stroke="#000000" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>`,
        };
    }

    constructor({ data, api }) {
        this.api = api;
        this.data = data || [];
        this.editor = null;
    }

    pasteEvent(e) {
        e.stopPropagation();
    }

    keyDownEvent(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            e.stopImmediatePropagation();
            e.stopPropagation();
        }
        if (e.key === 'Tab') {
            e.preventDefault();
            e.stopImmediatePropagation();
            e.stopPropagation();
        }
    }

    render() {
        const card = document.createElement('div');
        card.classList.add('card');

        const editorContainer = document.createElement('div');
        editorContainer.classList.add('editor-container');
        card.appendChild(editorContainer);

        card.addEventListener('paste', this.pasteEvent, true);
        card.addEventListener('keydown', this.keyDownEvent);

        let config = {
            holder: editorContainer,
            tools: window.editorTools,
            autofocus: true,
            defaultBlock: 'paragraph',
            onReady: () => {
                // new Undo({ editor })
            },
        };

        if (Object.keys(this.data).length !== 0) {
            config.data = {
                blocks: this.data,
            };
        }

        this.editor = new EditorJS(config);

        return card;
    }

    save() {
        return new Promise((resolve, reject) => {
            this.editor
                .save()
                .then((editorData) => {
                    resolve(editorData.blocks);
                })
                .catch((error) => {
                    reject(error);
                });
        });
    }

    validate(savedData) {
        if (!savedData.length) {
            return false;
        }

        return true;
    }

    static get sanitize() {
        return {};
    }

    renderSettings() {
        const wrapper = document.createElement('div');
        return wrapper;
    }
}

class ColMdTune {
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
}

class Widget {
    static get enableLineBreaks() {
        return true;
    }

    static get toolbox() {
        return {
            title: 'Widgets',
            icon: `<svg xmlns="http://www.w3.org/2000/svg" id="Layer_1" data-name="Layer 1" viewBox="0 0 122.88 121.92"><path d="M6.6,121.92H47.51a6.56,6.56,0,0,0,2.83-.64,6.68,6.68,0,0,0,2.27-1.79,6.63,6.63,0,0,0,1.5-4.17V74.58A6.56,6.56,0,0,0,53.58,72,6.62,6.62,0,0,0,50,68.47,6.56,6.56,0,0,0,47.51,68H6.6a6.5,6.5,0,0,0-2.43.48,6.44,6.44,0,0,0-2.11,1.34A6.6,6.6,0,0,0,.55,72,6.3,6.3,0,0,0,0,74.58v40.74a6.54,6.54,0,0,0,.43,2.32,6.72,6.72,0,0,0,1.2,2l.26.27a6.88,6.88,0,0,0,2,1.39,6.71,6.71,0,0,0,2.73.6ZM59.3,28.44,86,1.77A6.19,6.19,0,0,1,88.22.34,6.24,6.24,0,0,1,90.87,0a6,6,0,0,1,3.69,1.74l26.55,26.55a6,6,0,0,1,1.33,2,6.13,6.13,0,0,1-1.33,6.58L94.45,63.58a6,6,0,0,1-1.9,1.27,5.92,5.92,0,0,1-2.24.5,6.11,6.11,0,0,1-2.41-.43,5.74,5.74,0,0,1-2.05-1.34L59.3,37a6.09,6.09,0,0,1-1.76-3.88V32.8a6.14,6.14,0,0,1,1.77-4.36ZM6.6,59.64H47.51a6.56,6.56,0,0,0,5.1-2.43,6.46,6.46,0,0,0,1.11-2,6.59,6.59,0,0,0,.39-2.21V12.31a6.61,6.61,0,0,0-.53-2.58A6.62,6.62,0,0,0,50,6.19a6.56,6.56,0,0,0-2.45-.48H6.6a6.5,6.5,0,0,0-2.43.48A6.44,6.44,0,0,0,2.06,7.53,6.6,6.6,0,0,0,.55,9.71,6.31,6.31,0,0,0,0,12.31V53.05a6.48,6.48,0,0,0,.43,2.31,6.6,6.6,0,0,0,1.2,2l.26.27a6.88,6.88,0,0,0,2,1.39,6.71,6.71,0,0,0,2.73.6Zm40.92-6.57H6.6l0,0V12.28c3.51,0,40.93,0,41,0,0,3.44,0,40.75,0,40.77Zm22.23,68.85h40.91a6.56,6.56,0,0,0,2.83-.64,6.68,6.68,0,0,0,2.27-1.79,6.63,6.63,0,0,0,1.5-4.17V74.58a6.56,6.56,0,0,0-.53-2.57,6.62,6.62,0,0,0-3.62-3.54,6.56,6.56,0,0,0-2.45-.48H69.75a6.75,6.75,0,0,0-4.54,1.82A6.6,6.6,0,0,0,63.7,72a6.3,6.3,0,0,0-.55,2.59v40.74a6.54,6.54,0,0,0,.43,2.32,6.72,6.72,0,0,0,1.2,2l.26.27a6.88,6.88,0,0,0,2,1.39,6.71,6.71,0,0,0,2.73.6Zm40.92-6.57H69.75l0,0,0-40.77c3.51,0,40.93,0,41,0,0,3.44,0,40.75,0,40.77Zm-63.15,0H6.6l0,0V74.56c3.51,0,40.93,0,41,0,0,3.44,0,40.75,0,40.77Z"/></svg>`,
        };
    }

    // Конструктор, который инициализирует объекты, переданные из Editor.js и создает основные HTML элементы.
    constructor({ data, api, block }) {
        this.api = api;
        this.block = block;

        this.widgetBlock = this.createElement('div', 'widget-block');
        this.widgetSettingsSidebar = null;
        this.data = this.defaultData(data);
        this.initDefaultSettings();
    }

    initDefaultSettings() {
        for (let key in this.data.settings) {
            const setting = this.data.settings[key];

            // Значение по умолчанию уже установлено
            if (setting.result !== undefined) continue;

            switch (setting.type) {
                case 'select':
                    setting.result = setting.value.items[0];
                    break;
                case 'radio':
                    setting.result = setting.value.items[0];
                    break;
                case 'checkbox':
                    setting.result = false;
                    break;
                case 'text':
                    setting.result = '';
                    break;
                case 'image':
                    setting.result = null; // установите какое-либо значение по умолчанию для изображения
                    break;
                default:
                    console.warn(`Unknown setting type "${setting.type}"`);
            }
        }
    }

    // Вспомогательный метод для установки значений по умолчанию данных виджета.
    defaultData(data) {
        return {
            loader: data.loader || null,
            image: data.image || null,
            name: data.name || null,
            settings: data.settings || {},
        };
    }

    // Общий метод для создания DOM элементов с заданными атрибутами и классами.
    createElement(type, cssClass = null, attrs = {}) {
        const element = document.createElement(type);
        if (cssClass) {
            element.classList.add(cssClass);
        }
        for (const [attr, value] of Object.entries(attrs)) {
            element.setAttribute(attr, value);
        }
        return element;
    }

    // Создает начальный скелет виджетов.
    createWidgetSkeleton() {
        let widgetsContainer = this.createElement('div', 'widgets-container');
        for (let i = 0; i <= 10; i++) {
            const widgetBlock = this.createElement('div', 'widget');
            const widgetImage = this.createElement('div', [
                'widget-image',
                'skeleton',
            ]);
            const widgetTitle = this.createElement('div', [
                'widget-title',
                'skeleton',
            ]);
            widgetBlock.append(widgetImage, widgetTitle);
            widgetsContainer.append(widgetBlock);
        }
        return widgetsContainer;
    }

    // Загружает модальное окно для выбора виджета.
    async loadModal() {
        let widgetsContainer = this.createWidgetSkeleton();

        await Modals.open({
            title: translate('def.choose_widget'),
            content: widgetsContainer,
            url: u('widget/all'),
            onUrlLoaded: (parsed, modalContent, error) =>
                this.callbackUrl(parsed, modalContent, error),
            buttons: [
                {
                    text: translate('def.choose'),
                    class: 'btn btn-modal-action right',
                    callback: () => {
                        Modals.clear();

                        if (this.data.loader) {
                            this.widgetBlock = this.render();
                            this.initDefaultSettings();
                        } else {
                            this.api.blocks.delete(
                                this.api.blocks.getCurrentBlockIndex(),
                            );
                            this.data = [];
                        }
                    },
                },
            ],
            onClose: () => {
                if (!this.data.loader) {
                    this.data = [];
                    // this.block.holder.remove(); // Editor shiza error

                    this.api.blocks.delete(
                        this.api.blocks.getCurrentBlockIndex(),
                    );
                    this.widgetBlock = document.createElement('div');
                }
            },
        });

        document
            .querySelector('.btn-modal-action')
            .setAttribute('disabled', 'disabled');
    }

    // Валидирует данные виджета перед сохранением.
    validate(savedData) {
        return !!savedData.loader;
    }

    callbackUrl(parsed, modalContent, error) {
        if (error) {
            modalContent.innerHTML = `<div class="error"><b>Error while fetching widgets (write to Flames):</b> <pre>${error}</pre></div>`;
            return;
        }

        let widgetsContainer = modalContent.querySelector('.widgets-container');
        widgetsContainer.innerHTML = ''; // clear content

        for (let parse of parsed) {
            const widgetBlock = this.createWidgetBlock(parse);
            widgetsContainer.appendChild(widgetBlock);
        }
    }

    createWidgetBlock(parse) {
        let widgetBlock = document.createElement('div');
        widgetBlock.classList.add('widget');

        const widgetImage = this.createWidgetImage(parse.image);
        const widgetTitle = this.createWidgetTitle(parse.name);

        if (parse.lazyload == true) {
            let widgetLazy = this.createWidgetLazy();
            widgetBlock.appendChild(widgetLazy);
        }

        widgetBlock.onclick = () => {
            this.selectWidget(widgetBlock, parse);
        };

        widgetBlock.appendChild(widgetImage);
        widgetBlock.appendChild(widgetTitle);

        return widgetBlock;
    }

    createWidgetImage(imageSrc) {
        let widgetImage;

        if (!imageSrc) {
            widgetImage = document.createElement('div');
            widgetImage.innerHTML = '<i class="ph ph-image"></i>';
        } else {
            widgetImage = document.createElement('img');
            widgetImage.src = imageSrc;
        }

        widgetImage.classList.add('widget-image');
        return widgetImage;
    }

    createWidgetTitle(name) {
        let widgetTitle = document.createElement('div');
        widgetTitle.classList.add('widget-title');
        widgetTitle.innerText = name;
        return widgetTitle;
    }

    createWidgetLazy() {
        let widgetLazy = document.createElement('div');
        widgetLazy.classList.add('widget-lazy');
        widgetLazy.setAttribute('data-tooltip', 'Async load');
        widgetLazy.setAttribute('data-tooltip-conf', 'bottom');
        widgetLazy.innerHTML = '<i class="ph-bold ph-hourglass"></i>';
        return widgetLazy;
    }

    selectWidget(widgetBlock, parse) {
        const selectedWidget = document.querySelector('.widget__selected');
        if (selectedWidget) {
            selectedWidget.classList.remove('widget__selected');
        }

        document.querySelector('.btn-modal-action').removeAttribute('disabled');

        widgetBlock.classList.toggle('widget__selected');

        this.data = {
            image: parse.image,
            loader: parse.loader,
            name: parse.name,
            settings: parse.settings,
        };
    }

    save() {
        let emptyFields = [];

        for (let module in this.data) {
            let settings = this.data[module];
            for (let settingKey in settings) {
                let setting = settings[settingKey];
                if (
                    setting.value?.required &&
                    (!setting.result || setting.result.length === 0)
                ) {
                    emptyFields.push({
                        module: this.data.name,
                        input: setting.description,
                    });
                }
            }
        }

        if (emptyFields.length > 0) {
            let errorMessage = 'Пожалуйста, заполните следующие поля:\n';
            emptyFields.forEach((field) => {
                errorMessage += `Виджет "${field.module}", поле "${field.input}"\n`;
            });
            alert(errorMessage);
            return;
        }

        return this.data;
    }

    async openSettingsSidebar() {
        this.widgetSettingsSidebar = this.createElement(
            'div',
            'widget-settings-sidebar',
        );

        const widgetSettingsTitle = this.createElement(
            'div',
            'widget-settings-title',
        );
        const closeWidgetTitle = this.createElement(
            'div',
            'widget-settings-title-name',
            { innerText: this.data.name },
        );
        const closeWidgetSvg = this.createCloseIcon();

        widgetSettingsTitle.append(closeWidgetTitle, closeWidgetSvg);

        const widgetSettingsContainer = this.createElement(
            'div',
            'widget-settings-container',
        );

        for (let key in this.data.settings) {
            const widgetSettingsItem = this.createElement('div', 'input-form');

            const input = await this.createInput(key, this.data.settings[key]);
            const label = this.createElement('label', null, {
                innerHTML: translate(this.data.settings[key].description),
            });

            input.setAttribute('id', key);
            label.setAttribute('for', key);

            if (
                this.data.settings[key].type !== 'select' &&
                this.data.settings[key].type !== 'text'
            ) {
                widgetSettingsItem.classList.add('input-reverse');
            }

            if (this.data.settings[key].type === 'checkbox') {
                widgetSettingsItem.classList.add('form-checkbox');
            }

            // if (this.data.settings[key].type === "checkbox") {
            //     label.classList.add('checkbox-label');
            //     label.prepend(input);
            //     widgetSettingsItem.append(label);
            // } else {
            widgetSettingsItem.append(input, label);
            // }

            widgetSettingsContainer.append(widgetSettingsItem);
        }

        const widgetButtonsContainer = this.createElement(
            'div',
            'widget-dialog-button-group',
        );

        const widgetCloseButton = await this.createCancelButton();
        const widgetSettingsButton = await this.createSaveButton();

        widgetButtonsContainer.append(widgetCloseButton);
        widgetButtonsContainer.append(widgetSettingsButton);

        this.widgetSettingsSidebar.append(
            widgetSettingsTitle,
            widgetSettingsContainer,
            widgetButtonsContainer,
        );

        const widgetBackground = this.createWidgetBackground();

        setTimeout(() => {
            this.widgetSettingsSidebar.classList.add('opened');
            widgetBackground.classList.add('opened');

            this.widgetSettingsSidebar.focus();
        }, 50);

        document.body.append(widgetBackground, this.widgetSettingsSidebar);
    }

    createElement(type, cssClass = null, attrs = {}) {
        const element = document.createElement(type);
        if (cssClass) element.classList.add(cssClass);
        for (const [attr, value] of Object.entries(attrs)) {
            element[attr] = value;
        }
        return element;
    }

    createCloseIcon() {
        const closeWidgetSvg = this.createElement(
            'div',
            'widget-settings-title-close',
            {
                innerHTML:
                    '<svg xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 384 512"><path d="M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z"/></svg>',
            },
        );
        closeWidgetSvg.onclick = () => this.closeSettingsSidebar();
        return closeWidgetSvg;
    }

    createRadioInput(key, setting) {
        const input = this.createElement('div');
        input.classList.add('widget-radio-input');

        const radioImageWrapper = this.createElement('div'); // Создаем div для радиокнопок с изображениями
        radioImageWrapper.classList.add('radio-image-wrapper'); // Добавляем класс для стилизации

        for (let [radioValue, radioLabelValue] of Object.entries(
            setting.value.items,
        )) {
            const inputDiv = this.createElement('div');
            const radioInput = this.createElement('input', null, {
                type: 'radio',
                name: setting.name,
                id: setting.name + radioValue,
                value: radioValue,
            });
            const radioLabel = this.createElement('label', null);
            radioLabel.setAttribute('for', setting.name + radioValue);

            // if radioLabelValue is a link to an image
            if (
                radioLabelValue.match(/\.(jpeg|jpg|gif|png|webp|svg)$/) != null
            ) {
                const img = this.createElement('img', null, {
                    src: radioLabelValue,
                });
                radioLabel.append(img);
                inputDiv.classList.add('radio-image'); // добавляем специальный класс для стилизации
                radioInput.style.display = 'none'; // скрываем radio button
                radioImageWrapper.appendChild(inputDiv); // Добавляем радиокнопку с изображением в radioImageWrapper
            } else {
                radioLabel.innerText = radioLabelValue;
                inputDiv.append(radioInput, radioLabel);
                input.appendChild(inputDiv);
            }

            radioInput.onchange = () => (setting.result = radioValue);
            if (!setting.result) setting.result = radioValue; // Set default value
            radioInput.checked = radioValue === setting.result;
            inputDiv.append(radioInput, radioLabel);
        }

        // Добавляем radioImageWrapper в input, если он содержит элементы
        if (radioImageWrapper.childNodes.length > 0) {
            input.appendChild(radioImageWrapper);
        }

        return input;
    }

    async createInput(key, setting) {
        let input;
        const requiredAttr = setting.value?.required ? { required: true } : {};
        switch (setting.type) {
            case 'select':
                input = await this.createSelectInput(setting);
                break;
            case 'image':
                input = this.createElement('input', null, {
                    type: 'file',
                    name: setting.name,
                    multiple: true,
                    ...requiredAttr,
                });
                input.onchange = (e) => {
                    if (e.target.files) {
                        setting.result = Array.from(e.target.files).map(
                            (file) => {
                                return URL.createObjectURL(file);
                            },
                        );
                    }
                };
                break;
            case 'radio':
                input = this.createRadioInput(key, setting);
                break;
            case 'checkbox':
                input = this.createElement('input', null, {
                    type: 'checkbox',
                    name: setting.name,
                    id: setting.name,
                    ...requiredAttr,
                });
                break;
            default:
                input = this.createElement('input', null, {
                    type: 'text',
                    value: setting.result,
                    name: setting.name,
                    ...requiredAttr,
                });
                break;
        }

        if (setting.type !== 'radio' && setting.type !== 'image') {
            input.onchange = (e) => {
                setting.result = e.target.value;
                if (input.hasAttribute('aria-invalid')) {
                    // если поле было отмечено как aria-invalid
                    input.setAttribute('aria-invalid', 'false'); // установить значение в false, т.к. поле теперь заполнено
                }
            };
        }
        return input;
    }
    async createSelectInput(setting) {
        const input = this.createElement('select', null, {
            name: setting.name,
        });
        for (let optionValue of setting.value.items) {
            const optionElement = this.createElement('option', null, {
                value: optionValue,
                text: translate(optionValue),
            });
            input.appendChild(optionElement);
        }
        if (!setting.result) setting.result = setting.value.items[0]; // Set default value
        input.value = setting.result;
        return input;
    }

    async createCancelButton() {
        const widgetSettingsButton = this.createElement(
            'button',
            'widget-dialog-button--false',
            {
                innerHTML: translate('def.cancel'),
            },
        );
        widgetSettingsButton.onclick = () => {
            this.closeWithoutSave();
        };
        widgetSettingsButton.classList.add('widget-dialog-button');
        return widgetSettingsButton;
    }

    async createSaveButton() {
        const widgetSettingsButton = this.createElement(
            'button',
            'widget-dialog-button--true',
            {
                innerHTML: translate('def.save'),
            },
        );
        widgetSettingsButton.onclick = () => {
            this.closeSettingsSidebar();
        };
        widgetSettingsButton.classList.add('widget-dialog-button');
        return widgetSettingsButton;
    }

    createWidgetBackground() {
        const widgetBackground = this.createElement(
            'div',
            'widget-settings-background',
        );
        widgetBackground.onclick = () => this.closeSettingsSidebar();
        return widgetBackground;
    }

    closeWithoutSave() {
        document
            .querySelector('.widget-settings-background')
            .classList.remove('opened');
        document
            .querySelector('.widget-settings-sidebar')
            .classList.remove('opened');

        setTimeout(() => {
            document.querySelector('.widget-settings-background').remove();
            document.querySelector('.widget-settings-sidebar').remove();
        }, 250);

        this.widgetSettingsSidebar = null;
    }

    async closeSettingsSidebar() {
        const requiredInputs = document.querySelectorAll(
            'input[required], select[required]',
        );
        let allFieldsValid = true;

        for (let i = 0; i < requiredInputs.length; i++) {
            if (!requiredInputs[i].value) {
                requiredInputs[i].setAttribute('aria-invalid', 'true'); // если поле не заполнено, установить aria-invalid в true
                allFieldsValid = false;
            }
        }

        if (!allFieldsValid) {
            alert(translate('validator.form_invalid'));
            return;
        }

        document
            .querySelector('.widget-settings-background')
            .classList.remove('opened');
        document
            .querySelector('.widget-settings-sidebar')
            .classList.remove('opened');

        setTimeout(() => {
            document.querySelector('.widget-settings-background').remove();
            document.querySelector('.widget-settings-sidebar').remove();
        }, 250);

        this.widgetSettingsSidebar = null;
    }

    render() {
        if (!this.data.loader) {
            this.loadModal();
            return this.widgetBlock;
        }

        const widgetName = this.createElement('div', 'widget-name', {
            innerText: this.data.name,
        });
        const widgetLoader = this.createElement('div', 'widget-loader', {
            innerText: this.data.loader,
        });

        this.widgetBlock.append(widgetName, widgetLoader);

        if (this.data.settings.length > 0) {
            const widgetSettings = this.createElement('div', 'widget-settings');
            widgetSettings.onclick = async () =>
                await this.openSettingsSidebar();
            const svgNS = 'http://www.w3.org/2000/svg';
            const settingsIcon = document.createElementNS(svgNS, 'svg');
            settingsIcon.setAttributeNS(null, 'height', '1em');
            settingsIcon.setAttributeNS(null, 'viewBox', '0 0 512 512');

            const path = document.createElementNS(svgNS, 'path');
            path.setAttributeNS(
                null,
                'd',
                'M495.9 166.6c3.2 8.7 .5 18.4-6.4 24.6l-43.3 39.4c1.1 8.3 1.7 16.8 1.7 25.4s-.6 17.1-1.7 25.4l43.3 39.4c6.9 6.2 9.6 15.9 6.4 24.6c-4.4 11.9-9.7 23.3-15.8 34.3l-4.7 8.1c-6.6 11-14 21.4-22.1 31.2c-5.9 7.2-15.7 9.6-24.5 6.8l-55.7-17.7c-13.4 10.3-28.2 18.9-44 25.4l-12.5 57.1c-2 9.1-9 16.3-18.2 17.8c-13.8 2.3-28 3.5-42.5 3.5s-28.7-1.2-42.5-3.5c-9.2-1.5-16.2-8.7-18.2-17.8l-12.5-57.1c-15.8-6.5-30.6-15.1-44-25.4L83.1 425.9c-8.8 2.8-18.6 .3-24.5-6.8c-8.1-9.8-15.5-20.2-22.1-31.2l-4.7-8.1c-6.1-11-11.4-22.4-15.8-34.3c-3.2-8.7-.5-18.4 6.4-24.6l43.3-39.4C64.6 273.1 64 264.6 64 256s.6-17.1 1.7-25.4L22.4 191.2c-6.9-6.2-9.6-15.9-6.4-24.6c4.4-11.9 9.7-23.3 15.8-34.3l4.7-8.1c6.6-11 14-21.4 22.1-31.2c5.9-7.2 15.7-9.6 24.5-6.8l55.7 17.7c13.4-10.3 28.2-18.9 44-25.4l12.5-57.1c2-9.1 9-16.3 18.2-17.8C227.3 1.2 241.5 0 256 0s28.7 1.2 42.5 3.5c9.2 1.5 16.2 8.7 18.2 17.8l12.5 57.1c15.8 6.5 30.6 15.1 44 25.4l55.7-17.7c8.8-2.8 18.6-.3 24.5 6.8c8.1 9.8 15.5 20.2 22.1 31.2l4.7 8.1c6.1 11 11.4 22.4 15.8 34.3zM256 336a80 80 0 1 0 0-160 80 80 0 1 0 0 160z',
            );

            settingsIcon.appendChild(path);

            widgetSettings.appendChild(settingsIcon);
            this.widgetBlock.append(widgetSettings);
        }

        return this.widgetBlock;
    }
}

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
                additionalRequestHeaders: {
                    'x-csrf-token': csrfToken,
                },
            },
        },
        list: {
            class: editorjsNestedChecklist,
            inlineToolbar: true,
            shortcut: 'CMD+SHIFT+L',
        },
        widget: {
            class: Widget,
            tunes: ['col'],
        },
        row: {
            class: Row,
            config: {
                tools: {
                    paragraph: {
                        inlineToolbar: true,
                        tunes: ['alignment'],
                    },
                    raw: RawTool,
                    header: {
                        class: Header,
                        inlineToolbar: ['link'],
                        config: {
                            placeholder: 'Header',
                        },
                        shortcut: 'CMD+SHIFT+H',
                        tunes: ['alignment'],
                    },
                    delimiter: Delimiter,
                    card: {
                        class: Card,
                        tunes: ['col'],
                    },
                    widget: {
                        class: Widget,
                        tunes: ['col'],
                    },
                    col: {
                        class: ColMdTune,
                    },
                    image: {
                        class: ImageTool,
                        inlineToolbar: ['link'],
                        tunes: ['alignment'],
                        config: {
                            endpoints: {
                                byFile: u('page/saveimage'),
                            },
                            additionalRequestHeaders: {
                                'x-csrf-token': csrfToken,
                            },
                        },
                    },
                    table: {
                        class: Table,
                        inlineToolbar: true,
                        shortcut: 'CMD+ALT+T',
                        tunes: ['alignment'],
                    },
                    marker: {
                        class: Marker,
                        shortcut: 'CMD+SHIFT+M',
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
                    list: {
                        class: editorjsNestedChecklist,
                        inlineToolbar: true,
                        shortcut: 'CMD+SHIFT+L',
                    },
                },
            },
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
            class: ColMdTune,
        },
    },
};

function deepEqual(obj1, obj2) {
    return JSON.stringify(obj1) === JSON.stringify(obj2);
}

let initialData;

window.editorConfig = {
    holder: 'editor',
    tools: window.editorTools,
    autofocus: true,
    defaultBlock: 'paragraph',
    data: window.editorData,
    onReady: () => {
        setInitialData();
        document.body.classList.add('editor-opened');
    },
    onChange: async (data, event) => {
        const currentData = await data.saver.save();

        if (!deepEqual(initialData, currentData)) {
            $('.save_container').addClass('opened');
        } else {
            $('.save_container').removeClass('opened');
        }
    },
    onSave: () => {
        console.log('Saving');
    },
    i18n:
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
              },
};

const editor = new EditorJS(window.editorConfig);

async function setInitialData(data) {
    initialData = data || window.editorData;
}

$('#saveButton').on('click', (e) => {
    editor.save().then((data) => {
        let button = $('#saveButton');

        $.ajax({
            url: u(`page/save`),
            type: 'POST',
            data: {
                data: JSON.stringify(data),
                page: (
                    location.protocol +
                    '//' +
                    location.host +
                    location.pathname
                ).replace(SITE_URL, ''),
            },
            beforeSend: function () {
                $(button).prop('disabled', true);
                $(button).attr('aria-busy', true);

                // clearErrors();
            },
            success: function (response) {
                $(button).attr('aria-busy', false);
                $(button).prop('disabled', false);

                $('.save_container').removeClass('opened');

                setInitialData(data);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                $(button).attr('aria-busy', false);
                $(button).prop('disabled', false);

                console.error('error request', jqXHR, textStatus, errorThrown);
            },
        });
    });
});
