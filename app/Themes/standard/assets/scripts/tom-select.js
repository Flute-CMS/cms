class ThemeSelect {
    constructor() {
        this.instances = new WeakMap();
        this.init();
    }

    init() {
        document.querySelectorAll('[data-tom-select]:not([data-fs-initialized])').forEach((select) => {
            this.initSelect(select);
        });
    }

    initSelect(select) {
        if (typeof FluteSelect === 'undefined') return;
        if (select.dataset.fsInitialized) return;

        const config = this.getConfig(select);
        const instance = FluteSelect.create(select, config);
        this.instances.set(select, instance);
        select.dataset.fsInitialized = 'true';
    }

    getConfig(select) {
        const isMultiple = select.multiple;
        const allowEmpty = select.dataset.allowEmpty === 'true';
        const allowAdd = select.dataset.allowAdd === 'true';
        const searchable = select.dataset.searchable === 'true';
        const placeholder = this.resolvePlaceholder(select);

        const options = this.collectOptions(select);

        const config = {
            placeholder: placeholder,
            multiple: isMultiple,
            searchable: searchable || options.length > 6,
            clearable: allowEmpty,
            name: select.getAttribute('name') || '',
            disabled: select.disabled,
        };

        if (options.length > 0) {
            config.options = options;
            const selectedValues = Array.from(select.selectedOptions || [])
                .map(o => o.value).filter(Boolean);
            if (selectedValues.length > 0) {
                config.value = isMultiple ? selectedValues : selectedValues[0];
            }
        }

        if (allowAdd) {
            config.creatable = true;
        }

        return config;
    }

    resolvePlaceholder(select) {
        const explicit = select.dataset.placeholder || select.getAttribute('placeholder');
        if (explicit) return explicit;

        const emptyOpt = select.querySelector('option[value=""]');
        if (emptyOpt) {
            const text = emptyOpt.textContent.trim();
            if (text) return text;
        }

        return typeof translate === 'function'
            ? (translate('def.select_option') || 'Select...')
            : 'Select...';
    }

    collectOptions(select) {
        const result = [];
        for (const opt of select.querySelectorAll('option')) {
            if (opt.value === '') continue;
            result.push({
                value: opt.value,
                label: opt.textContent.trim(),
                disabled: opt.disabled,
            });
        }
        return result;
    }

    destroySelect(select) {
        const instance = this.instances.get(select) || FluteSelect.get(select);
        if (instance) {
            try {
                instance.destroy();
            } catch (e) { }
            this.instances.delete(select);
        }
        delete select.dataset.fsInitialized;
    }

    refresh() {
        this.init();
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.ThemeSelect = new ThemeSelect();
});

document.body.addEventListener('htmx:beforeSwap', (evt) => {
    if (!window.ThemeSelect) return;
    const target = evt.detail?.target;
    if (!target) return;

    target.querySelectorAll('[data-tom-select]').forEach((select) => {
        window.ThemeSelect.destroySelect(select);
    });
});

document.body.addEventListener('htmx:afterSettle', (evt) => {
    if (window.ThemeSelect) {
        window.ThemeSelect.init();
    }
});
