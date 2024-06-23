class Modal {
    constructor() {
        this.parseAbilities = [];
        this.modals = [];
        this.modalContainer = document.createElement('div');
        this.onCloseListeners = [];
        this.closeOnBackground = true;
        this.infoIcons = this.getInfoIcons();
        this.initializeEventListeners();
    }

    getInfoIcons() {
        return {
            info: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 192 512"><path d="M48 80a48 48 0 1 1 96 0A48 48 0 1 1 48 80zM0 224c0-17.7 14.3-32 32-32H96c17.7 0 32 14.3 32 32V448h32c17.7 0 32 14.3 32 32s-14.3 32-32 32H32c-17.7 0-32-14.3-32-32s14.3-32 32-32H64V256H32c-17.7 0-32-14.3-32-32z"/></svg>`,
            warning: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 512"><path d="M64 64c0-17.7-14.3-32-32-32S0 46.3 0 64V320c0 17.7 14.3 32 32 32s32-14.3 32-32V64zM32 480a40 40 0 1 0 0-80 40 40 0 1 0 0 80z"/></svg>`,
            success: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M438.6 105.4c12.5 12.5 12.5 32.8 0 45.3l-256 256c-12.5 12.5-32.8 12.5-45.3 0l-128-128c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0L160 338.7 393.4 105.4c12.5-12.5 32.8-12.5 45.3 0z"/></svg>`,
            error: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><path d="M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5 12.5-32.8 12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z"/></svg>`,
            async: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 50 50"><path d="M25 3C12.1 3 2 13.1 2 26s10.1 23 23 23 23-10.1 23-23S37.9 3 25 3zm0 43C13.5 46 4 36.5 4 25S13.5 4 25 4s21 9.5 21 21-9.5 21-21 21z"><animateTransform attributeName="transform" attributeType="XML" type="rotate" dur="1s" from="0 25 25" to="360 25 25" repeatCount="indefinite"/></path></svg>`,
        };
    }

    initializeEventListeners() {
        document.addEventListener('click', (event) => {
            const openButton = event.target.closest('[data-modal-open]');
            if (openButton) {
                const modalId = openButton.getAttribute('data-modal-open');
                const modalElement = document.getElementById(modalId);
                if (modalElement) {
                    this.showModal(modalElement);
                }
            }

            const closeButton = event.target.closest('[data-modal-close]');
            if (closeButton) {
                const modalId = closeButton.getAttribute('data-modal-close');
                this.close(modalId);
            }
        });
    }

    addParser(name, callback) {
        const ability = this.parseAbilities.find(
            (parse) => parse.name === name,
        );
        if (ability) {
            this.parseAbilities.splice(this.parseAbilities.indexOf(ability), 1);
        }
        this.parseAbilities.push({ name, callback });
    }

    close(id) {
        const modal = this.modals.find((modal) => modal.id === id);
        if (modal) {
            modal.modal.classList.remove('opened');
            this.modals.splice(this.modals.indexOf(modal), 1);
            if (this.modals.length === 0) {
                this.modalContainer.classList.remove('opened');
            }
            const onClose = this.onCloseListeners.find(
                (listener) => listener.id === id,
            );
            if (onClose) {
                onClose.onClose();
            }
            setTimeout(() => {
                modal.modal.remove();
                if (this.modals.length === 0) {
                    this.modalContainer.remove();
                }
            }, 300);
        }
    }

    clear() {
        this.modals.forEach((modal) => {
            modal.modal.classList.remove('opened');
            const onClose = this.onCloseListeners.find(
                (listener) => listener.id === modal.id,
            );
            if (onClose) {
                onClose.onClose();
            }
        });
        this.modalContainer.classList.remove('opened');
        setTimeout(() => {
            this.modals.forEach((modal) => modal.modal.remove());
            this.modals = [];
            this.modalContainer.remove();
        }, 300);
    }

    async open({
        title = null,
        content,
        buttons,
        type = 'modal',
        infoTitle = null,
        url = null,
        onUrlLoaded = null,
        onClose = null,
        closeOnBackground = true,
    }) {
        this.closeOnBackground = closeOnBackground;
        const modalId = `modal-${this.generateUUID()}`;
        const modal = this.createModalElement(
            modalId,
            title,
            content,
            buttons,
            type,
            infoTitle,
        );
        this.addToContainer(modal);
        if (url && onUrlLoaded) {
            await this.fetchUrl(
                url,
                onUrlLoaded,
                modal.querySelector('.modal-content'),
            );
        }
        if (onClose) {
            this.onCloseListeners.push({ id: modalId, onClose });
        }
        modal.focus();
        return modal;
    }

    addToContainer(modal) {
        if (!document.querySelector('.modals-container')) {
            this.createModalContainer();
        }
        this.modalContainer.prepend(modal);
        this.modals.push({ id: modal.id, modal });
        setTimeout(() => {
            this.modalContainer.classList.add('opened');
            modal.classList.add('opened');
        }, 10);
    }

    createModalContainer() {
        this.modalContainer.classList.add('modals-container');
        document.body.appendChild(this.modalContainer);
        this.modalContainer.onclick = (e) => {
            if (e.target === this.modalContainer && this.closeOnBackground) {
                this.clear();
            }
        };
    }

    createModalElement(modalId, title, content, buttons, type, infoTitle) {
        const modal = document.createElement('div');
        modal.id = modalId;
        modal.classList.add('modal');
        if (title) {
            modal.append(this.createModalTitleElement(title, type));
        }
        const modalContent = document.createElement('div');
        modalContent.classList.add('modal-content');
        modalContent.append(this.resolveContent(content));
        if (type !== 'modal') {
            modalContent.prepend(this.createModalInfoElement(type, infoTitle));
        }
        modal.append(modalContent);
        this.parseButtons(buttons, modal);
        return modal;
    }

    createModalTitleElement(title, type) {
        const modalTitle = document.createElement('div');
        modalTitle.classList.add('modal-title');
        if (type !== 'modal') {
            modalTitle.classList.add(`modal-title-${type}`);
        }
        const modalTitleText = document.createElement('div');
        modalTitleText.classList.add('modal-title-text');
        modalTitleText.innerHTML = title;
        const modalTitleClose = document.createElement('div');
        modalTitleClose.classList.add('modal-title-close');
        modalTitleClose.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><path d="M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5 12.5-32.8 12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z"/></svg>`;
        modalTitleClose.onclick = () => this.close(modalTitle.parentElement.id);
        modalTitle.append(modalTitleText, modalTitleClose);
        return modalTitle;
    }

    createModalInfoElement(type, infoTitle) {
        const modalContentNote = document.createElement('div');
        modalContentNote.classList.add(
            'modal-content-note',
            `modal-content-note-${type}`,
        );
        const modalContentNoteIcon = document.createElement('div');
        modalContentNoteIcon.classList.add('modal-content-note-icon');
        modalContentNoteIcon.innerHTML = this.infoIcons[type];
        const modalContentNoteText = document.createElement('div');
        modalContentNoteText.classList.add('modal-content-note-text');
        modalContentNoteText.innerHTML = infoTitle;
        modalContentNote.append(modalContentNoteIcon, modalContentNoteText);
        return modalContentNote;
    }

    resolveContent(content) {
        if (!content) return document.createTextNode('');
        if (typeof content === 'object' && !(content instanceof Element)) {
            const contentContainer = document.createElement('div');
            contentContainer.classList.add('content-container');
            contentContainer.append(
                this.getObjectContent(content, contentContainer),
            );
            return contentContainer;
        }
        return content;
    }

    parseButtons(buttons, modal) {
        if (!buttons) return;
        const modalFooter = document.createElement('div');
        modalFooter.classList.add('modal-footer');
        buttons.forEach((button) => {
            const btn = document.createElement('button');
            btn.classList.add('btn', 'size-s', 'outline');
            if (button?.class) {
                button.class
                    .split(' ')
                    .forEach((className) => btn.classList.add(className));
            }
            btn.innerHTML = button?.icon
                ? button.icon + button.text
                : button.text;
            if (button?.id) btn.id = button.id;
            btn.onclick = () => button.callback(this);
            modalFooter.appendChild(btn);
        });
        if (modalFooter.innerHTML === '') modalFooter.style.display = 'none';
        modal.appendChild(modalFooter);
    }

    getObjectContent(content, modalContent) {
        const key = Object.keys(content)[0];
        if (key == 0) {
            const test = document.createElement('div');
            content.forEach((keyContent) =>
                test.append(this.getObjectContent(keyContent, modalContent)),
            );
            return test;
        }
        return this.parseAbility(key, content[key], modalContent);
    }

    parseAbility(key, params, modalContent) {
        const ability = this.parseAbilities.find(
            (ability) => ability.name === key,
        );
        if (!ability?.callback)
            throw new Error(`Ability "${key}" is not defined`);
        return ability.callback(params, modalContent);
    }

    async fetchUrl(url, onUrlLoaded, modalContent) {
        try {
            const response = await fetch(url);
            const parsed = await response.json();
            return onUrlLoaded(parsed, modalContent);
        } catch (error) {
            return onUrlLoaded(null, modalContent, error);
        }
    }

    createToastElement(toastId, message, type, isAsync = false) {
        const toast = document.createElement('div');
        toast.id = toastId;
        toast.classList.add('toast', `toast-${type}`);
        const toastContent = document.createElement('div');
        toastContent.classList.add('toast-content');
        toastContent.innerHTML = message;
        const toastContentIcon = document.createElement('div');
        toastContentIcon.classList.add('toast-content-icon');
        toastContentIcon.innerHTML = this.infoIcons[type];
        const progressBar = document.createElement('div');
        progressBar.classList.add('toast-progress-bar', 'primary');
        progressBar.style.display = !isAsync ? 'block' : 'none';
        const progressBarBack = document.createElement('div');
        progressBarBack.classList.add(
            'toast-progress-bar',
            'progress-bar-back',
        );
        progressBarBack.style.display = !isAsync ? 'block' : 'none';
        toast.append(
            toastContentIcon,
            toastContent,
            progressBar,
            progressBarBack,
        );
        return toast;
    }

    async showToast(toast, duration, isAsync = false) {
        if (!document.querySelector('.toast-container')) {
            const toastContainer = document.createElement('div');
            toastContainer.classList.add('toast-container');
            document.body.appendChild(toastContainer);
        }
        document.querySelector('.toast-container').prepend(toast);
        setTimeout(() => toast.classList.add('show'), 10);
        if (!isAsync) {
            this.setToastTimeouts(toast, duration);
        }
    }

    async updateToast(toastId, message, duration, type) {
        const toast = document.getElementById(toastId);
        if (toast) {
            this.setToastTimeouts(toast, duration);

            toast.className = '';
            toast.classList.add('toast', `toast-${type}`, `show`);

            const toastContent = toast.querySelector('.toast-content');
            const toastContentIcon = toast.querySelector('.toast-content-icon');
            toastContent.innerHTML = message;
            toastContentIcon.innerHTML = this.infoIcons[type];

            toast.querySelector('.toast-progress-bar').style.display = 'block';
            toast.querySelector('.progress-bar-back').style.display = 'block';
        }
    }

    setToastTimeouts(toast, duration) {
        let isHovered = false;
        let hoverStartTime;
        let remainingDuration = duration;
        let progressBarTimeout;
        let removeToastTimeout;

        const startProgressBar = (duration) => {
            const progressBar = toast.querySelector('.toast-progress-bar');
            progressBar.style.transition = `height ${duration}ms linear`;
            progressBar.style.height = '0%';
            progressBarTimeout = setTimeout(
                () => (progressBar.style.height = '100%'),
                10,
            );
        };

        const pauseToast = () => {
            isHovered = true;
            hoverStartTime = new Date();
            const progressBar = toast.querySelector('.toast-progress-bar');
            const currentHeight = window.getComputedStyle(progressBar).height;
            progressBar.style.transition = 'none';
            progressBar.style.height = currentHeight;
            clearTimeout(removeToastTimeout);
            clearTimeout(progressBarTimeout);
        };

        const resumeToast = () => {
            isHovered = false;
            remainingDuration -= new Date() - hoverStartTime;
            startProgressBar(remainingDuration);
            removeToastTimeout = setTimeout(removeToast, remainingDuration);
        };

        const removeToast = () => {
            if (!isHovered) {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 300);
            }
        };

        toast.addEventListener('mouseenter', pauseToast);
        toast.addEventListener('mouseleave', resumeToast);
        toast.onclick = () => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        };

        startProgressBar(remainingDuration);
        removeToastTimeout = setTimeout(removeToast, remainingDuration);
    }

    generateUUID() {
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
            const r = (Math.random() * 16) | 0;
            const v = c === 'x' ? r : (r & 0x3) | 0x8;
            return v.toString(16);
        });
    }

    async toast({
        message,
        type = 'info',
        duration = 3000,
        fetchFunction = null,
    }) {
        if (type === 'async') {
            const toastId = `toast-${this.generateUUID()}`;
            const toast = this.createToastElement(toastId, message, type, true);
            await this.showToast(toast, duration, true);

            try {
                const result = await fetchFunction();
                await this.updateToast(toastId, result, duration, 'success');
            } catch (error) {
                await this.updateToast(toastId, error, duration, 'error');
            }

            return;
        }

        const toastId = `toast-${this.generateUUID()}`;
        const toast = this.createToastElement(toastId, message, type);
        this.showToast(toast, duration);
    }
}

const Modals = new Modal();

Modals.addParser('form', (formConfig, modalContent) => {
    const form = document.createElement('form');
    if (formConfig?.id) form.id = formConfig.id;

    formConfig.fields.forEach((field, index) => {
        const formGroup = document.createElement('div');
        formGroup.classList.add(
            'position-relative',
            'row',
            'form-group',
            'gx-3',
        );

        if (!formConfig.fields[index + 1]) {
            formGroup.classList.add('withoutLine');
        }

        const labelCol = document.createElement('div');
        labelCol.classList.add('col-sm-4', 'col-form-label');

        const label = document.createElement('label');
        label.htmlFor = field.id;
        label.innerHTML = field.label;
        labelCol.appendChild(label);

        if (field.helpText) {
            const small = document.createElement('small');
            small.classList.add('form-text', 'text-muted');
            small.innerHTML = field.helpText;
            labelCol.appendChild(small);
        }

        formGroup.appendChild(labelCol);

        const inputCol = document.createElement('div');
        inputCol.classList.add('col-sm-8');

        let input;
        if (field.type === 'select') {
            input = document.createElement('select');
            if (field.id) input.id = field.id;
            if (field.name) input.name = field.name;

            field.options.forEach((option) => {
                const opt = document.createElement('option');
                opt.value = option.value;

                // Проверка на наличие HTML в option
                if (option.text && /<\/?[a-z][\s\S]*>/i.test(option.text)) {
                    const htmlContainer = document.createElement('div');
                    htmlContainer.innerHTML = option.text;
                    opt.textContent = '';
                    const observer = new MutationObserver(() => {
                        opt.textContent = htmlContainer.textContent;
                        htmlContainer.remove();
                        observer.disconnect();
                    });

                    form.append(htmlContainer);

                    observer.observe(htmlContainer, {
                        childList: true,
                        subtree: true,
                    });
                } else {
                    opt.textContent = option.text;
                }

                if (option.selected) opt.selected = true;
                input.appendChild(opt);
            });
        } else if (field.type === 'checkbox') {
            input = document.createElement('input');
            input.type = 'checkbox';
            if (field.id) input.id = field.id;
            if (field.name) input.name = field.name;
            input.classList.add('form-check-input');
            input.setAttribute('role', 'switch');

            const labelAdd = document.createElement('label');
            labelAdd.setAttribute('for', field.id);
            labelAdd.textContent = field.label;

            if (field.checked) input.checked = true;
            inputCol.appendChild(labelAdd);
        } else {
            input = document.createElement('input');
            input.type = field.type;
            if (field.id) input.id = field.id;
            if (field.name) input.name = field.name;

            // Проверка на наличие HTML в placeholder
            if (
                field.placeholder &&
                /<\/?[a-z][\s\S]*>/i.test(field.placeholder)
            ) {
                const htmlContainer = document.createElement('div');
                htmlContainer.innerHTML = field.placeholder;
                input.placeholder = '';
                const observer = new MutationObserver(() => {
                    input.placeholder = htmlContainer.textContent;
                    htmlContainer.remove();
                    observer.disconnect();
                });

                form.append(htmlContainer);

                observer.observe(htmlContainer, {
                    childList: true,
                    subtree: true,
                });
            } else {
                input.placeholder = field.placeholder;
            }
        }

        if (field.required) {
            input.required = true;
            labelCol.classList.add('required');
        }

        if (!input.name && input.id) input.name = input.id;
        if (field?.default) input.value = field.default;
        input.classList.add('form-control');
        inputCol.appendChild(input);

        if (field.type === 'hidden') {
            form.appendChild(input);
        } else {
            formGroup.appendChild(inputCol);
            form.appendChild(formGroup);
        }
    });

    return form;
});

Modals.addParser('faq', (params, modalContent) => {
    const container = document.createElement('div');
    container.className = 'faq-container';
    const answer = document.createElement('p');
    answer.innerHTML = params.answer;
    container.appendChild(answer);
    return container;
});

const toast = ({
    message,
    type = 'info',
    duration = 3000,
    fetchFunction = null,
}) => Modals.toast({ message, type, duration, fetchFunction });
