class Modal {
    constructor() {
        this.parseAbilities = [];
        this.modals = [];
        this.modalContainer = document.createElement('div');
        this.onCloseListeners = [];
        this.closeOnBackground = true;

        this.infoIcons = {
            info: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 192 512"><path d="M48 80a48 48 0 1 1 96 0A48 48 0 1 1 48 80zM0 224c0-17.7 14.3-32 32-32H96c17.7 0 32 14.3 32 32V448h32c17.7 0 32 14.3 32 32s-14.3 32-32 32H32c-17.7 0-32-14.3-32-32s14.3-32 32-32H64V256H32c-17.7 0-32-14.3-32-32z"/></svg>`,
            warning: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 512"><path d="M64 64c0-17.7-14.3-32-32-32S0 46.3 0 64V320c0 17.7 14.3 32 32 32s32-14.3 32-32V64zM32 480a40 40 0 1 0 0-80 40 40 0 1 0 0 80z"/></svg>`,
            success: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M438.6 105.4c12.5 12.5 12.5 32.8 0 45.3l-256 256c-12.5 12.5-32.8 12.5-45.3 0l-128-128c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0L160 338.7 393.4 105.4c12.5-12.5 32.8-12.5 45.3 0z"/></svg>`,
            error: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><path d="M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z"/></svg>`,
        };
    }

    addParser(name, callback) {
        let ability = this.parseAbilities.find((parse) => parse.name === name);

        if (ability)
            this.parseAbilities.splice(this.parseAbilities.indexOf(ability), 1);

        this.parseAbilities.push({
            name,
            callback,
        });
    }

    close(id) {
        let modal = this.modals.find((modal) => modal.id === id);

        if (modal) {
            // удаляем класс 'opened' и ждем конца анимации, прежде чем удалить элементы
            modal.modal.classList.remove('opened');
            this.modals.splice(this.modals.indexOf(modal), 1);

            if (this.modals.length === 0) {
                this.modalContainer.classList.remove('opened');
            }

            let onClose = this.onCloseListeners.find(
                (listener) => listener.id === id,
            );

            if (onClose) {
                onClose.onClose();
            }

            setTimeout(() => {
                modal.modal.remove();

                // Если больше нет активных модальных окон, удаляем
                if (this.modals.length === 0) {
                    this.modalContainer.remove();
                }
            }, 300);
        }
    }

    clear() {
        // удаляем класс 'opened' и ждем конца анимации, прежде чем удалить элементы
        this.modals.forEach((modal) => {
            modal.modal.classList.remove('opened');

            let onClose = this.onCloseListeners.find(
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

        let modalId = `modal-${uuidv4()}`;
        let modal = document.createElement('div');
        modal.id = modalId;
        modal.classList.add('modal');

        if (title) {
            let modalTitle = document.createElement('div');
            modalTitle.classList.add('modal-title');

            let modalTitleText = document.createElement('div');
            modalTitleText.classList.add('modal-title-text');
            modalTitleText.innerHTML = title;

            let modalTitleClose = document.createElement('div');
            modalTitleClose.classList.add('modal-title-close');
            modalTitleClose.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><path d="M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z"/></svg>`;
            modalTitleClose.onclick = () => this.close(modalId);

            modalTitle.append(modalTitleText);
            modalTitle.append(modalTitleClose);

            type !== 'modal' && modalTitle.classList.add(`modal-title-${type}`);

            modal.append(modalTitle);
        }

        let resolve = '';

        let modalContent = document.createElement('div');
        modalContent.classList.add('modal-content');

        if (content) {
            resolve = this.resolveContent(content, modalContent);
        }

        if (type !== 'modal') {
            let modalContentNote = document.createElement('div');
            modalContentNote.classList.add('modal-content-note');
            modalContentNote.classList.add(`modal-content-note-${type}`);

            let modalContentNoteIcon = document.createElement('div');
            modalContentNoteIcon.classList.add('modal-content-note-icon');
            modalContentNoteIcon.innerHTML = this.infoIcons[type];

            let modalContentNoteText = document.createElement('div');
            modalContentNoteText.classList.add('modal-content-note-text');
            modalContentNoteText.innerHTML = infoTitle;

            modalContentNote.append(modalContentNoteIcon);
            modalContentNote.append(modalContentNoteText);
            modalContent.append(modalContentNote);
        }

        modalContent.append(resolve);

        modal.append(modalContent);

        this.parseButtons(buttons, modal);
        this.addToContainer(modal);

        if (url && onUrlLoaded)
            await this.fetchUrl(url, onUrlLoaded, modalContent);

        if (onClose)
            this.onCloseListeners.push({
                id: modal.id,
                onClose,
            });

        modal.focus();

        return modal;
    }

    toast({ message, type = 'info', duration = 3000 }) {
        let toastId = `toast-${uuidv4()}`;
        let toast = document.createElement('div');
        toast.id = toastId;
        toast.classList.add('toast');
        toast.classList.add(`toast-${type}`);

        let toastContent = document.createElement('div');
        toastContent.classList.add('toast-content');
        toastContent.innerHTML = message;

        let toastContentIcon = document.createElement('div');
        toastContentIcon.classList.add('toast-content-icon');
        toastContentIcon.innerHTML = this.infoIcons[type];

        let progressBar = document.createElement('div');
        progressBar.classList.add('toast-progress-bar', 'primary');
        let progressBarBack = document.createElement('div');
        progressBarBack.classList.add(
            'toast-progress-bar',
            'progress-bar-back',
        );
        toast.append(
            toastContentIcon,
            toastContent,
            progressBar,
            progressBarBack,
        );

        if (!document.querySelector('.toast-container')) {
            let toastContainer = document.createElement('div');
            toastContainer.classList.add('toast-container');
            document.body.appendChild(toastContainer);
        }

        let isHovered = false; // Flag to track hover state
        let hoverStartTime; // Time when the hover started
        let remainingDuration = duration; // Remaining time for the toast
        let progressBarTimeout; // Timeout to control the progress bar
        let removeToastTimeout; // Timeout to control toast removal

        // Function to start the progress bar
        let startProgressBar = (duration) => {
            progressBar.style.transition = `height ${duration}ms linear`;
            progressBar.style.height = '0%';
            progressBarTimeout = setTimeout(
                () => (progressBar.style.height = '100%'),
                10,
            );
        };

        // Function to pause the toast progress bar
        let pauseToast = () => {
            isHovered = true; // Set the flag when the toast is hovered
            hoverStartTime = new Date(); // Record the time when the hover started
            let currentWidth = window.getComputedStyle(progressBar).height;
            progressBar.style.transition = 'none'; // Pause the transition
            progressBar.style.height = currentWidth;
            clearTimeout(removeToastTimeout); // Clear any existing timeout for toast removal
            clearTimeout(progressBarTimeout); // Clear any existing timeout for progress bar
        };

        // Function to resume the toast progress bar
        let resumeToast = () => {
            isHovered = false; // Reset the flag when the mouse leaves the toast
            remainingDuration -= new Date() - hoverStartTime; // Subtract the hover duration from the remaining duration
            startProgressBar(remainingDuration); // Start the progress bar with the remaining duration
            removeToastTimeout = setTimeout(removeToast, remainingDuration); // Set a new timeout for toast removal
        };

        // Function to remove the toast
        let removeToast = () => {
            if (!isHovered) {
                // Only remove the toast if it's not being hovered
                toast.classList.remove('show');
                setTimeout(() => {
                    toast.remove();
                }, 300);
            }
        };

        // Set the hover event listeners
        toast.addEventListener('mouseenter', pauseToast);
        toast.addEventListener('mouseleave', resumeToast); // Resume the toast and set removal timeout on mouse leave

        toast.onclick = () => {
            toast.classList.remove('show');
            setTimeout(() => {
                toast.remove();
            }, 300);
        };

        document.querySelector('.toast-container').prepend(toast);

        setTimeout(() => {
            toast.classList.add('show');
        }, 10);

        startProgressBar(remainingDuration); // Start the progress bar with the initial duration

        removeToastTimeout = setTimeout(removeToast, remainingDuration); // Set initial timeout for toast removal
    }

    addToContainer(modal) {
        if (!document.querySelector('.modals-container')) {
            this.createModalContainer();
        }

        this.modalContainer.prepend(modal);
        this.modals.push({
            id: modal.id,
            modal,
        });

        // добавляем класс 'opened' для анимации
        setTimeout(() => {
            this.modalContainer.classList.add('opened');
            modal.classList.add('opened');
        }, 10);
    }

    createModalContainer() {
        this.modalContainer.classList.add('modals-container');
        document.body.appendChild(this.modalContainer);

        this.modalContainer.onclick = (e) => {
            if (e.target === this.modalContainer) {
                if (this.closeOnBackground) {
                    this.clear();
                }
            }
        };
    }

    resolveContent(content, modalContent) {
        if (!content) return;

        if (typeof content === 'object' && !(content instanceof Element)) {
            let contentContainer = document.createElement('div');
            contentContainer.classList.add('content-container');

            let resolvedContent = this.getObjectContent(
                content,
                contentContainer,
            );
            if (resolvedContent !== contentContainer) {
                contentContainer.append(resolvedContent);
            }

            return contentContainer;
        }

        return content;
    }

    parseButtons(buttons, modal) {
        if (!buttons) return;

        let modalFooter = document.createElement('div');
        modalFooter.classList.add('modal-footer');
        modal.appendChild(modalFooter);

        buttons.forEach((button) => {
            let btn = document.createElement('button');

            // btn.classList.add('btn');
            btn.classList.add('btn', 'size-s', 'outline');

            if (button?.class) {
                const split = button?.class.split(' ');

                for (let v of split) btn.classList.add(v);
            }

            btn.innerHTML = button?.icon
                ? button.icon + button.text
                : button.text;

            if (button?.id) btn.id = button.id;

            btn.onclick = () => button.callback(this);

            modalFooter.appendChild(btn);
        });

        if (modalFooter.innerHTML === '') modalFooter.style.display = 'none';
    }

    getObjectContent(content, modalContent) {
        let key = Object.keys(content)[0];

        if (key == 0) {
            let test = make('div');

            for (let keyContent in content)
                test.append(
                    this.getObjectContent(content[keyContent], modalContent),
                );

            return test;
        }

        return this.parseAbility(key, content[key], modalContent);
    }

    removeFromContainer() {
        if (this.modals.length === 0) {
            this.modalContainer.remove();
        }
    }

    parseAbility(key, params, modalContent) {
        let ability = this.parseAbilities.find(
            (ability) => ability.name === key,
        );

        if (!ability?.callback)
            throw new Error(`Ability "${key}" is not defined`);

        return ability.callback(params, modalContent);
    }

    async fetchUrl(url, onUrlLoaded, modalContent) {
        try {
            let response = await fetch(url);
            let parsed = await response.json();

            return onUrlLoaded(parsed, modalContent);
        } catch (error) {
            return onUrlLoaded(null, modalContent, error);
        }
    }
}

const Modals = new Modal();

Modals.addParser('form', (formConfig, modalContent) => {
    let form = document.createElement('form');

    formConfig?.id && form.setAttribute('id', formConfig.id);

    formConfig.fields.forEach((field) => {
        let formGroup = document.createElement('div');
        formGroup.classList.add(
            'position-relative',
            'row',
            'form-group',
            'gx-3',
        );

        let labelCol = document.createElement('div');
        labelCol.classList.add('col-sm-4', 'col-form-label');

        let label = document.createElement('label');
        label.htmlFor = field.id;
        label.innerHTML = field.label; // Используем innerHTML здесь
        labelCol.appendChild(label);

        if (field.helpText) {
            let small = document.createElement('small');
            small.classList.add('form-text', 'text-muted');
            small.innerHTML = field.helpText;
            labelCol.appendChild(small);
        }

        formGroup.appendChild(labelCol);

        let inputCol = document.createElement('div');
        inputCol.classList.add('col-sm-8');

        let input, labelAdd;
        if (field.type === 'select') {
            input = document.createElement('select');

            if (field.id) input.id = field.id;
            if (field.name) input.name = field.name;
            if (field.placeholder) input.placeholder = field.placeholder;

            field.options.forEach((option) => {
                let opt = document.createElement('option');
                opt.value = option.value;
                opt.innerHTML = option.text;
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

            labelAdd = document.createElement('label');
            labelAdd.setAttribute('for', field.id);
            labelAdd.innerHTML = field.label; // Используем innerHTML здесь

            if (field.checked) input.checked = true;
        } else {
            input = document.createElement('input');
            input.type = field.type;
            if (field.id) input.id = field.id;
            if (field.name) input.name = field.name;
            if (field.placeholder) input.placeholder = field.placeholder;
        }

        if (field.required) {
            input.required = true;
            labelCol.classList.add('required');
        }

        if (!input.name && input.id) input.name = input.id;

        if (field?.default) input.value = field.default;

        input.classList.add('form-control');
        inputCol.appendChild(input);

        if (labelAdd) inputCol.appendChild(labelAdd);

        if (field.type === 'hidden') {
            form.appendChild(input);
        } else {
            formGroup.appendChild(inputCol);
            form.appendChild(formGroup);
        }
    });

    return form;
});

const toast = ({ message, type = 'info', duration = 3000 }) =>
    Modals.toast({ message, type, duration });
