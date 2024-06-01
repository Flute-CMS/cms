class ConfirmDialog {
    constructor({
        questionText,
        questionDescription,
        customContent,
        trueButtonText,
        falseButtonText,
        type,
        parent,
        inputPlaceholder,
        inputValidator,
        inputLabel,
        customClass
    }) {
        this.questionText = questionText || 'Are you sure?';
        this.questionDescription = questionDescription;
        this.customContent = customContent;
        this.trueButtonText = trueButtonText || 'Yes';
        this.falseButtonText = falseButtonText || 'No';
        this.type = type || 'delete';
        this.parent = parent || document.body;
        this.inputPlaceholder = inputPlaceholder || '';
        this.inputValidator = inputValidator || (() => true);
        this.inputLabel = inputLabel || '';
        this.customClass = customClass || '';

        this.dialog = undefined;
        this.trueButton = undefined;
        this.falseButton = undefined;
        this.inputField = undefined;

        this._createDialog();
        this._appendDialog();
    }

    confirm() {
        return new Promise((resolve, reject) => {
            const somethingWentWrongUponCreation =
                !this.dialog || !this.trueButton || !this.falseButton;
            if (somethingWentWrongUponCreation) {
                reject('Something went wrong when creating the modal');
                return;
            }

            this.dialog.showModal();
            if (this.inputField) {
                this.inputField.focus();
            } else {
                this.trueButton.focus();
            }

            this.trueButton.addEventListener('click', () => {
                if (this.inputField) {
                    const inputValue = this.inputField.value;
                    if (this.inputValidator(inputValue)) {
                        resolve(inputValue);
                        this._destroy();
                    } else {
                        this.inputField.classList.add('invalid');
                    }
                } else {
                    resolve(true);
                    this._destroy();
                }
            });

            this.falseButton.addEventListener('click', () => {
                resolve(false);
                this._destroy();
            });
        });
    }

    _createDialog() {
        this.dialog = document.createElement('dialog');
        this.dialog.classList.add('confirm-dialog');

        if (this.customClass) {
            this.dialog.classList.add(this.customClass);
        }

        if (this.type === 'primary') {
            this.dialog.classList.add('confirm-dialog-primary');
        }

        const dialogHeader = document.createElement('div');
        dialogHeader.classList.add('confirm-dialog-header');

        const question = document.createElement('div');
        question.innerHTML = this.questionText;
        question.classList.add('confirm-dialog-header-question');
        dialogHeader.appendChild(question);

        this.dialog.appendChild(dialogHeader);

        const dialogContent = document.createElement('div');
        dialogContent.classList.add('confirm-dialog-content');

        const questionDescription = document.createElement('div');
        questionDescription.innerHTML = this.questionDescription;
        questionDescription.classList.add('confirm-dialog-content-description');
        dialogContent.appendChild(questionDescription);

        if (this.customContent) {
            const dialogCustomContent = document.createElement('div');
            dialogCustomContent.innerHTML = this.customContent;
            dialogCustomContent.classList.add('confirm-dialog-content-custom');
            dialogContent.appendChild(dialogCustomContent);
        }

        if (this.inputPlaceholder) {
            const inputContainer = document.createElement('div');
            inputContainer.classList.add('form-group');

            if (this.inputLabel) {
                const inputLabel = document.createElement('label');
                inputLabel.innerHTML = this.inputLabel;
                inputLabel.classList.add('confirm-dialog-input-label');
                inputContainer.appendChild(inputLabel);
            }

            this.inputField = document.createElement('input');
            this.inputField.type = 'text';
            this.inputField.placeholder = this.inputPlaceholder;
            this.inputField.classList.add('confirm-dialog-input');

            inputContainer.append(this.inputField);
            dialogContent.appendChild(inputContainer);
        }

        this.dialog.appendChild(dialogContent);

        const buttonGroup = document.createElement('div');
        buttonGroup.classList.add('confirm-dialog-button-group');
        this.dialog.appendChild(buttonGroup);

        this.falseButton = document.createElement('button');
        this.falseButton.classList.add(
            'confirm-dialog-button',
            'confirm-dialog-button--false',
        );
        this.falseButton.type = 'button';
        this.falseButton.innerHTML = this.falseButtonText;
        buttonGroup.appendChild(this.falseButton);

        this.trueButton = document.createElement('button');
        this.trueButton.classList.add(
            'confirm-dialog-button',
            'confirm-dialog-button--true',
        );
        this.trueButton.type = 'button';
        this.trueButton.innerHTML = this.trueButtonText;
        buttonGroup.appendChild(this.trueButton);
    }

    _appendDialog() {
        this.parent.appendChild(this.dialog);
    }

    _destroy() {
        this.parent.removeChild(this.dialog);
        delete this;
    }
}

function asyncConfirm(
    questionDescription,
    questionText = null,
    trueButtonText = null,
    falseButtonText = null,
    type = null,
    inputLabel = '',
    inputPlaceholder = '',
    inputValidator = null,
    customClass = '',
    customContent = ''
) {
    if (
        typeof questionDescription === 'object' &&
        questionDescription !== null
    ) {
        ({
            questionDescription,
            questionText = translate('def.are_you_sure'),
            trueButtonText = translate('def.delete'),
            falseButtonText = translate('def.cancel'),
            type = 'delete',
            inputLabel = '',
            inputPlaceholder = '',
            inputValidator = () => true,
            customClass = '',
            customContent = '',
        } = questionDescription);
    } else {
        questionText = questionText || translate('def.are_you_sure');
        trueButtonText = trueButtonText || translate('def.delete');
        falseButtonText = falseButtonText || translate('def.cancel');
        type = type || 'delete';
        inputLabel = inputLabel || '';
        inputPlaceholder = inputPlaceholder || '';
        inputValidator = inputValidator || (() => true);
        customClass = customClass || '';
        customContent = customContent || '';
    }

    const dialog = new ConfirmDialog({
        questionDescription: questionDescription,
        questionText: questionText,
        trueButtonText: trueButtonText,
        falseButtonText: falseButtonText,
        type: type,
        inputPlaceholder: inputPlaceholder,
        inputValidator: inputValidator,
        inputLabel: inputLabel,
        customClass: customClass,
        customContent: customContent,
    });

    return dialog.confirm();
}