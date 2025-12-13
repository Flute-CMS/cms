class OtpInput {
    constructor(wrapper) {
        this.wrapper = wrapper;
        this.container = wrapper.querySelector('.otp-input');
        this.hiddenInput = wrapper.querySelector('input[type="hidden"]');
        this.inputs = wrapper.querySelectorAll('.otp-input__field');
        
        if (!this.container || !this.hiddenInput || !this.inputs.length) return;
        
        this.init();
    }

    init() {
        this.inputs.forEach((input, index) => {
            input.addEventListener('input', (e) => this.handleInput(e, index));
            input.addEventListener('keydown', (e) => this.handleKeydown(e, index));
            input.addEventListener('paste', (e) => this.handlePaste(e));
            input.addEventListener('focus', () => input.select());
        });
    }

    handleInput(e, index) {
        const value = e.target.value.replace(/[^0-9]/g, '');
        e.target.value = value.slice(-1);
        
        if (value && index < this.inputs.length - 1) {
            this.inputs[index + 1].focus();
        }
        
        this.updateHiddenValue();
    }

    handleKeydown(e, index) {
        if (e.key === 'Backspace' && !e.target.value && index > 0) {
            this.inputs[index - 1].focus();
        }
        if (e.key === 'ArrowLeft' && index > 0) {
            e.preventDefault();
            this.inputs[index - 1].focus();
        }
        if (e.key === 'ArrowRight' && index < this.inputs.length - 1) {
            e.preventDefault();
            this.inputs[index + 1].focus();
        }
    }

    handlePaste(e) {
        e.preventDefault();
        const paste = (e.clipboardData || window.clipboardData).getData('text');
        const digits = paste.replace(/[^0-9]/g, '').slice(0, this.inputs.length);
        
        digits.split('').forEach((digit, i) => {
            if (this.inputs[i]) {
                this.inputs[i].value = digit;
            }
        });
        
        if (digits.length > 0) {
            const focusIndex = Math.min(digits.length, this.inputs.length - 1);
            this.inputs[focusIndex].focus();
        }
        
        this.updateHiddenValue();
    }

    updateHiddenValue() {
        let value = '';
        this.inputs.forEach(input => value += input.value);
        this.hiddenInput.value = value;
        this.hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
    }
}

function initOtpInputs(container = document) {
    container.querySelectorAll('.otp-input-wrapper').forEach(wrapper => {
        if (!wrapper.dataset.otpInitialized) {
            new OtpInput(wrapper);
            wrapper.dataset.otpInitialized = 'true';
        }
    });
}

document.addEventListener('DOMContentLoaded', () => initOtpInputs());
document.addEventListener('yoyo:load', (e) => initOtpInputs(e.target));
document.addEventListener('htmx:load', (e) => initOtpInputs(e.target));
document.addEventListener('htmx:afterSwap', (e) => initOtpInputs(e.target));